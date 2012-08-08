<?php
class Meanbee_Rackspacecloud_Model_Observer {

    public function observeConfigChange(Varien_Event_Observer $observer) {
        /** @var $config Meanbee_Rackspacecloud_Helper_Config */
        $config = Mage::helper('rackspace/config');

        if (!$config->isConfigured() && $config->isEnabled()) {
            Mage::getSingleton('core/session')->addNotice(
                Mage::helper('rackspace')->__("The Meanbee Rackspace Cloud Files Downloads module is enabled, but without the Amazon Access and Secret keys, the module will not work!")
            );
        }

        if ($config->isLogEnabled()) {
            Mage::getSingleton('core/session')->addNotice(
                Mage::helper('rackspace')->__("Logging is now enabled.")
            );

            $this->_log("Logging is enabled.");
        } else {
            $this->_log("Logging is disabled.  A log message to tell you that logging is disabled.. ingenious, right?");
        }
    }

    protected function _log($message, $level = Zend_Log::DEBUG) {
        Mage::helper('rackspace')->log($message, $level);
    }
}
