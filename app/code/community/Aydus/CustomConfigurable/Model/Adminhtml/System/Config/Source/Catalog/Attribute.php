<?php

/**
 * Configurable attributes for CustomConfigurable
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Model_Adminhtml_System_Config_Source_Catalog_Attribute
{
    /**
     * Retrieve option values array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection');
        
        foreach ($attributes as $attribute) {
            
            if ($attribute->getIsConfigurable()){
                
                $attributeCode = $attribute->getAttributeCode();
                $label = ($attribute->getFrontendLabel()) ? Mage::helper('catalog')->__($attribute->getFrontendLabel()) : $attributeCode;
                
                $options[] = array(
                        'label' => $label,
                        'value' => $attributeCode
                );
            }
            
        }
        
        return $options;
    }
    

}
