<?php

/**
 * CustomConfigurable observer
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Model_Observer
{
    /**
     * Rewrite select template 
     * 
     * @see core_block_abstract_to_html_before
     * @param Varien_Event_Observer $observer
     */
    public function changeOptionsTypeSelectTemplate($observer)
    {
        $block = $observer->getBlock();
        
        if (get_class($block) == 'Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Options_Type_Select'){
            
            $block->setTemplate('aydus/customconfigurable/catalog/product/edit/options/type/select.phtml');
                        
        }
        
        return $this;
        
    }
            
    /**
     * Delete option images 
     * 
     * @event catalog_product_delete_after_done
     * @param Varien_Event_Observer $observer
     */
    public function deleteOptionImages($observer)
    {
        try {
            $product = $observer->getProduct();
            
            if ($product->getId()){
            
                $collection = Mage::getModel('aydus_customconfigurable/optionimage')->getCollection();
                $collection->addFieldToFilter('product_id', $product->getId());
        
                if ($collection->getSize()>0){
        
                    foreach ($collection as $optionImage){
                        $optionImage->delete();
                    }
        
                }
            
            }
            
        } catch(Exception $e){
            Mage::log($e->getMessage(), null, 'aydus_customconfigurable.log');
        }
        
        return $this;
        
    }
    
}