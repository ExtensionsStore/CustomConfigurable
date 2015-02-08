<?php

/**
 * CustomConfigurable model
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Model_Resource_Setup extends Mage_Eav_Model_Entity_Setup
{    
    
    /**
     * Run sql setup 
     */
	public function install()
	{
		//make optionimage folder
		mkdir(Mage::helper('aydus_customconfigurable')->getMediaDir().DS.'optionimage',0775,true);
		
        //option select image table
		$this->run("CREATE TABLE IF NOT EXISTS {$this->getTable('aydus_customconfigurable_option_type_image')} (
          `option_type_image_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `product_id` int(11) unsigned NOT NULL,
          `option_id` int(11) unsigned NOT NULL,
          `option_type_id` int(11) unsigned NOT NULL,
          `store_id` int(11) unsigned NOT NULL,
          `filename` varchar(255) NOT NULL,
          `image` varchar(255) NOT NULL DEFAULT '',
          `date_created` datetime NOT NULL,
          `date_updated` datetime NOT NULL,
          PRIMARY KEY (`option_type_image_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
		
		//hash table
		$this->run("CREATE TABLE IF NOT EXISTS {$this->getTable('aydus_customconfigurable_hash')} (
		`hash` varchar(32),
		`entity_id` int(11) unsigned NOT NULL,
        `date_created` datetime NOT NULL,
        `date_updated` datetime NOT NULL,
		PRIMARY KEY (`hash`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
		
	}
	
	/**
	 * Run data setup
	 */
	public function installData()
	{
	    //install app
	    $consumer = Mage::getModel('oauth/consumer');
	    $consumer->load('Custom Configurable API', 'name');
	    
	    if (!$consumer->getId()){
	    
	        try {
	    
	            $helper = Mage::helper('oauth');
	    
	            $data = array(
	                    'name' => 'Custom Configurable API',
	                    'key'  => $helper->generateConsumerKey(),
	                    'secret' => $helper->generateConsumerSecret(),
	                    'callback_url' => 'http://'.$_SERVER['HTTP_HOST'].'/',
	                    'rejected_callback_url' => '',
	            );
	    
	            $consumer->addData($data);
	            $consumer->save();
	    
	        } catch (Exception $e){
	    
	            Mage::log($e->getMessage(),null, 'aydus_customconfigurable.log');
	        }
	    
	    }	 
	    
	    //create attribute set
	    try {
	        
	        if (!$this->attributeSetInstalled()){

	            $entityType = Mage::getModel('eav/entity_type')->getCollection()->addFieldToFilter('entity_type_code','catalog_product')->getFirstItem();
	            $entityTypeId = $entityType->getId();
	            	            
	            $entityAttributeSet = Mage::getModel('eav/entity_attribute_set');
	            $entityAttributeSet->setEntityTypeId($entityTypeId);
	            $entityAttributeSet->setAttributeSetName(Aydus_CustomConfigurable_Model_CustomConfigurable::ATTRIBUTE_SET);
	            $entityAttributeSet->save();
	            $entityAttributeSet->initFromSkeleton(4);
	            $entityAttributeSet->save();
	            
	            $attributeSetId = $entityAttributeSet->getId();
	            
	            //create attribute set group
	            $group = Mage::getModel('eav/entity_attribute_group');
	            $group->setAttributeGroupName(Aydus_CustomConfigurable_Model_CustomConfigurable::ATTRIBUTE_SET)
	                ->setAttributeSetId($attributeSetId)
	                ->setSortOrder(2)
	                ->save();
	            $groupId = $group->getId();
	            
	        }
	         
	    } catch(Exception $e){
	        
	        Mage::log($e->getMessage(),null, 'aydus_customconfigurable.log');
	    }
	     
	    //install product
	    try {
	         
	        
	        
	    } catch(Exception $e){
	        
	    }
	    
	}
	
	public function attributeSetInstalled()
	{
	    $entityType = Mage::getModel('eav/entity_type')->getCollection()->addFieldToFilter('entity_type_code','catalog_product')->getFirstItem();
	    $attributeSetCollection = $entityType->getAttributeSetCollection();
	    $attributeSetCollection->addFieldToFilter('attribute_set_name',Aydus_CustomConfigurable_Model_CustomConfigurable::ATTRIBUTE_SET);
	     
	    return $attributeSetCollection->getSize();
	}
	
	/**
	 * Uninstall
	 */
	public function uninstall()
	{
	    //@todo remove uploads folder 
	    //$setup->removeAttribute( 'catalog_product', 'your_added_attribute' );
	}
	
	/**
	 * Check setup
	 * 
	 * @return bool
	 */
	public function isInstalled()
	{
	    $mediaDirInstalled = file_exists(Mage::helper('aydus_customconfigurable')->getMediaDir().DS.'optionimage');
	    $attributeSetInstalled = $this->attributeSetInstalled();
	    
	    if ($mediaDirInstalled && $attributeSetInstalled){
	        return true;
	    }
	    
	    return false;
	}
	
}