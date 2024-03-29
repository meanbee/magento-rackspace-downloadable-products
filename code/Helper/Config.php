<?php
class Meanbee_RackspaceCloudFiles_Helper_Config extends Mage_Core_Helper_Abstract {
    const XML_GENERAL_ENABLED        = 'meanbee_rackspacecloudfiles/general/enabled';

    const XML_RACKSPACE_USERNAME     = 'meanbee_rackspacecloudfiles/rackspace_options/username';
    const XML_RACKSPACE_API_KEY      = 'meanbee_rackspacecloudfiles/rackspace_options/api_key';
    const XML_RACKSPACE_REGION       = 'meanbee_rackspacecloudfiles/rackspace_options/region';
    const XML_RACKSPACE_TIMEOUT      = 'meanbee_rackspacecloudfiles/rackspace_options/request_timeout';

    const XML_DEVELOPER_LOG_ENABLED  = 'meanbee_rackspacecloudfiles/developer/log_enabled';

    /**
     * Is the complete module enabled
     *
     * @return bool
     */
    public function isEnabled() {
        return $this->_getStoreConfigFlag(self::XML_GENERAL_ENABLED);
    }

    /**
     * @return bool
     */
    public function isConfigured() {
        return $this->getRackspaceUsername() && $this->getRackspaceApiKey();
    }

    /**
     * @return string
     */
    public function getRackspaceUsername() {
        return $this->_getStoreConfig(self::XML_RACKSPACE_USERNAME);
    }

    /**
     * @return string
     */
    public function getRackspaceApiKey() {
        return $this->_getStoreConfig(self::XML_RACKSPACE_API_KEY);
    }

    /**
     * @return string
     */
    public function getRackspaceRegion() {
        return $this->_getStoreConfig(self::XML_RACKSPACE_REGION);
    }

    /**
     * @return string
     */
    public function getRackspaceRequestTimeout() {
        return $this->_getStoreConfig(self::XML_RACKSPACE_TIMEOUT);
    }

    /**
     * @return string
     */
    public function getLogLocation() {
        return "meanbee_rackspace.log";
    }

    /**
     * @return bool
     */
    public function isLogEnabled() {
        return $this->_getStoreConfigFlag(self::XML_DEVELOPER_LOG_ENABLED);
    }

    /**
     * @param  $xml_path
     * @return string
     */
    protected function _getStoreConfig($xml_path) {
        return Mage::getStoreConfig($xml_path, Mage::app()->getStore()->getCode());
    }

    /**
     * @param  $xml_path
     * @return bool
     */
    protected function _getStoreConfigFlag($xml_path) {
        return Mage::getStoreConfigFlag($xml_path, Mage::app()->getStore()->getCode());
    }
}
