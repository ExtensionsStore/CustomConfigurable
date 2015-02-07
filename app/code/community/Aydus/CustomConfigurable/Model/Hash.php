<?php

/**
 * Hash model
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Model_Hash extends Mage_Core_Model_Abstract
{
	/**
	 * Initialize resource model
	 */
	protected function _construct()
	{
        parent::_construct();
        
		$this->_init('aydus_customconfigurable/hash');
	}	
	
}