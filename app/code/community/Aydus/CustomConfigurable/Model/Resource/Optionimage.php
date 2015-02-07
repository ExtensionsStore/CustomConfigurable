<?php

/**
 * Option image resource model
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Model_Resource_Optionimage extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('aydus_customconfigurable/optionimage', 'option_type_image_id');
    }    
	
	
}