<?php
require_once "php-cloudfiles/cloudfiles.php";
class Meanbee_Rackspacecloud_Model_Connection extends Mage_Core_Model_Abstract {

    /**
     * Generate the temporary URL for a file.
     *
     * @TODO Add error checking around the get_container, and throw an exception if something is amiss
     * @TODO Add error checking around get_object
     * @TODO If get_temp_url fails for some reason, try and force a reauth (our auth key might have expired?)
     *
     * @param $url
     * @return mixed
     */
    public function getTempUrl($url) {
        $containerInfo = $this->_getContainerInfo($url);
        $container = $this->getConnection()->get_container($containerInfo['name']);

        $objectName = $this->_getObjectName($url, $containerInfo['url']);
        $object = $container->get_object($objectName);

        return $object->get_temp_url(
            $this->getSharedSecret(),
            $this->getConfig()->getRackspaceRequestTimeout(),
            'GET'
        );
    }

    protected function _getObjectName($url, $containerUrl) {
        $length = strlen($containerUrl);
        $strippedUrl = substr($url, $length, strlen($url));
        return ltrim($strippedUrl, '/');

    }

    protected function _getContainerInfo($url) {
        foreach ($this->getCdnContainerMap() as $key => $value) {
            $keyLength = strlen($key);
            $containerCdn = substr($url, 0, $keyLength);
            if ($containerCdn == $key) {
                return array (
                    "name" => $value,
                    "url" => $key
                );
            }
        }
    }

    /**
     * @return Meanbee_Rackspacecloud_Helper_Config
     */
    public function getConfig() {
        return Mage::helper('rackspace/config');
    }

    /**
     * @return Meanbee_Rackspacecloud_Helper_Cache
     */
    public function getCache() {
        return Mage::helper('rackspace/cache');
    }

    /**
     * @param bool $force_new If set to true, will force a new value to be generated and save that as the new cached
     *                        value.
     * @return CF_Authentication
     */
    public function getAuthInstance($force_new = false) {
        $auth_config = $this->getCache()->getAuthConfig();

        if ($auth_config === false || $force_new) {
            $username = $this->getConfig()->getRackspaceUsername();
            $api_key = $this->getConfig()->getRackspaceApiKey();

            $auth = new CF_Authentication($username, $api_key);
            $auth->authenticate();

            $this->getCache()->setAuthConfig($auth->export_credentials());
        } else {
            $auth = new CF_Authentication();
            $auth->load_cached_credentials($auth_config['auth_token'], $auth_config['storage_url'], $auth_config['cdnm_url']);
        }

        return $auth;
    }

    /**
     * @return Meanbee_Rackspacecloud_Helper_Data
     */
    public function getHelper() {
        return Mage::helper('rackspace');
    }

    /**
     * @return CF_Connection
     */
    public function getConnection() {
        return new CF_Connection($this->getAuthInstance());
    }

    /**
     * @return string
     */
    public function getAuthToken() {
        return $this->getAuthInstance()->auth_token;
    }

    /**
     * @return string
     */
    public function getStorageUrl() {
        return $this->getAuthInstance()->storage_url;
    }

    /**
     * Generate a map of CDN urls to containers.  Uses cache if available.

     * @param bool $force_new If set to true, will force a new value to be generated and save that as the new cached
     *                        value.
     * @return array
     */
    public function getCdnContainerMap($force_new = false) {
        $cdn_map = $this->getCache()->getCdnContainerMap();

        if ($cdn_map === false || $force_new) {
            $cdn_map = array();

            foreach ($this->getConnection()->get_containers() as $container) {
                $container->make_public();

                $cdn_map[$container->cdn_uri] = $container->name;
                $cdn_map[$container->cdn_ssl_uri] = $container->name;
            }

            $this->getCache()->setCdnContainerMap($cdn_map);
        }

        return $cdn_map;
    }

    /**
     * Get the shared secret that's used when generating the temporary url for a file.  If we don't know of one, then
     * set a new one in the account and cache.
     *
     * @TODO Check the headers to see if the secret key was set successfully, otherwise throw an exception.
     *
     * @param bool $force_new If set to true, will force a new value to be generated and save that as the new cached
     *                        value.
     * @return string
     */
    public function getSharedSecret($force_new = false) {
        $secret_key = $this->getCache()->getSharedSecret();

        if ($secret_key === false || $force_new) {
            $secret_key = $this->getHelper()->getRandomSecretKey();

            $curlCh = curl_init();

            curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, True);
            curl_setopt($curlCh, CURLOPT_CAINFO, Mage::getBaseDir('lib') . "/php-cloudfiles/share/cacert.pem");
            curl_setopt($curlCh, CURLOPT_POST, TRUE);
            curl_setopt($curlCh, CURLOPT_POSTFIELDS, "");
            curl_setopt($curlCh, CURLOPT_VERBOSE, 1);
            curl_setopt($curlCh, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curlCh, CURLOPT_MAXREDIRS, 4);
            curl_setopt($curlCh, CURLOPT_HEADER, 0);
            curl_setopt($curlCh, CURLOPT_HTTPHEADER, array("X-Auth-Token: " . $this->getAuthToken(), "X-Account-Meta-Temp-Url-Key: $secret_key"));
            curl_setopt($curlCh, CURLOPT_USERAGENT, USER_AGENT);
            curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curlCh, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curlCh, CURLOPT_URL, $this->getStorageUrl());
            curl_exec($curlCh);
            curl_close($curlCh);

            $this->getCache()->setSharedSecret($secret_key);
        }

        return $secret_key;
    }
}
