<?php

/**
 * Catalog product custom option resource model
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Model_Resource_Catalog_Product_Option_Value extends Mage_Catalog_Model_Resource_Product_Option_Value
{
    /**
     * Proceeed operations after object is saved
     * Save options store data
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $this->_saveValueImages($object);

        return parent::_afterSave($object);
    }

    /**
     * Save option value image data
     *
     * @param Mage_Core_Model_Abstract $object
     */    
    protected function _saveValueImages(Mage_Core_Model_Abstract $object)
    {
        $readAdapter  = $this->_getReadAdapter();
        $writeAdapter = $this->_getWriteAdapter();
        $imageTable = $this->getTable('aydus_customconfigurable/optionimage');
        
        $image = $object->getImage();
        $product = Mage::registry('current_product');
        $optionId = $object->getOptionId();
        $optionTypeId = (int)$object->getId();
        
        if ($image && $product && $product->getId()){
            
            $productId = $product->getId();
            $dateUpdated = date('Y-m-d H:i:s');
            
            $statement = $readAdapter->select()
            ->from($imageTable)
            ->where('image = ?', $image)
            ->where('store_id  = ?', Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
            
            if ($readAdapter->fetchOne($statement)) {
                if ($object->getStoreId() == '0') {
                    $data = $this->_prepareDataForTable(
                            new Varien_Object(
                                    array(
                                            'product_id' => $productId,
                                            'option_id' => $optionId,
                                            'option_type_id' => $optionTypeId,
                                            'store_id' => Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID,
                                            'image' => $image,
                                            'date_updated' => $dateUpdated,
            
                                    )
                            ),
                            $imageTable
                    );
            
                    $writeAdapter->update(
                            $imageTable,
                            $data,
                            array(
                                    'image = ?' => $image,
                                    'store_id  = ?' => Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID
                            )
                    );
                    
                }
                
            } 
                       
        } else {
            
            $writeAdapter->delete(
                    $imageTable,
                    array(
                            'option_id = ?' => $optionId,
                            'option_type_id' => $optionTypeId,
                            'store_id  = ?' => Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID
                    )
            );
                        
        }
        
    }

}
