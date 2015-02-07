<?php

/**
 * Override for dynamic attributes
 * 
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Model_Api2_Config extends Mage_Api2_Model_Config
{
    /**
     * Constructor
     * Initializes XML for this configuration
     * Local cache configuration
     *
     * @param string|Varien_Simplexml_Element|null $sourceData
     */
    public function __construct($sourceData = null)
    {
        parent::__construct($sourceData);

        $canUserCache = Mage::app()->useCache('config');
        if ($canUserCache) {
            $this->setCacheId(self::CACHE_ID)
            ->setCacheTags(array(self::CACHE_TAG))
            ->setCacheChecksum(null)
            ->setCache(Mage::app()->getCache());

            if ($this->loadCache()) {
                return;
            }
        }


        // Load data of config files api2.xml
        $config = Mage::getConfig()->loadModulesConfiguration('api2.xml');
        $this->setXml($config->getNode('api2'));
        
        //add configurable custom options as valid api attributes
        $customconfigurableOption = $this->_xml->resources->customconfigurable_option;
        $customconfigurableQuote = $this->_xml->resources->customconfigurable_quote;
        
        $customCase = Mage::getModel('aydus_customconfigurable/customconfigurable');
        $customOptions = $customCase->getConfigOptions();
        
        foreach ($customOptions as $code=>$customOption){
            
            $customconfigurableOption->attributes->{$code} = $customOption['label'];
            $customconfigurableQuote->attributes->{$code} = $customOption['label'];
        }
        
        $translate = implode(' ', array_keys($options));
        $customconfigurableOption->attributes->attributes()->{'translate'} = $translate;
        $customconfigurableQuote->attributes->attributes()->{'translate'} = $translate;
        
        if ($canUserCache) {
            $this->saveCache();
        }
        
    }
    
}