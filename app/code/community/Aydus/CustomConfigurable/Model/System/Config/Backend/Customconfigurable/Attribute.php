<?php

/**
 * Add attribute to group 
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Model_System_Config_Backend_Customconfigurable_Attribute extends Mage_Core_Model_Config_Data
{
    /**
     * After config save
     */
    protected function _afterSave()
    {
        $attributeCode     = $this->getData('value');
        
        try {
            $entityType = Mage::getModel('eav/entity_type')->getCollection()->addFieldToFilter('entity_type_code','catalog_product')->getFirstItem();
            $entityTypeId = $entityType->getId();
            
            $attribute = Mage::getModel('eav/entity_attribute')->loadByCode($entityTypeId, $attributeCode);
            $attributeId = $attribute->getId();
            
            if ($attributeId){
                
                $attributeSetCollection = Mage::getModel('eav/entity_attribute_set')->getCollection();
                $attributeSetCollection->addFieldToFilter('attribute_set_name', Aydus_CustomConfigurable_Model_CustomConfigurable::ATTRIBUTE_SET);
                
                if ($attributeSetCollection->getSize()){
                    
                    $attributeSet = $attributeSetCollection->getFirstItem();
                    $attributeSetId = $attributeSet->getId();
                    
                    $groupCollection = Mage::getModel('eav/entity_attribute_group')->getCollection();
                    $groupCollection->addFieldToFilter('attribute_group_name', Aydus_CustomConfigurable_Model_CustomConfigurable::ATTRIBUTE_SET);
                
                    if ($groupCollection->getSize()>0){
                
                        $group = $groupCollection->getFirstItem();
                        $groupId = $group->getId();
                        
                        $itemCollection = Mage::getResourceModel('eav/entity_attribute_collection');
                        $itemCollection->setEntityTypeFilter($entityTypeId);
                        $itemCollection->setAttributeSetFilter($attributeSetId);
                        $itemCollection->setAttributeGroupFilter($groupId);
                        $itemCollection->setCodeFilter($attributeCode);
                        
                        //$select = (string)$itemCollection->getSelect();
                        
                        $attributeSetItem = Mage::getModel('eav/entity_attribute');
                        
                        if ($itemCollection->getSize()>0){
                            $attributeSetItem = $itemCollection->getFirstItem();
                        }
                        
                        $attributeSetItem->setEntityTypeId($entityTypeId)
                        ->setAttributeSetId($attributeSetId)
                        ->setAttributeGroupId($groupId)
                        ->setAttributeId($attributeId)
                        ->setSortOrder(1)
                        ->save();
                    }
                }                
            }

        }
        catch (Exception $e) {
            
            Mage::throwException(Mage::helper('aydus_customconfigurable')->__('Unable to save the Customconfigurable Attribute.'));
        }
        
    }
    
}
