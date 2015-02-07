<?php

/**
 * Override to add option image to option value
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Block_Adminhtml_Catalog_Product_Edit_Tab_Options_Option extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Options_Option
{
    
    /**
     * Add image to option field value
     */
    public function getOptionValues()
    {
        $optionsArr = array_reverse($this->getProduct()->getOptions(), true);
        //        $optionsArr = $this->getProduct()->getOptions();
        
        if (!$this->_values) {
            $showPrice = $this->getCanReadPrice();
            $values = array();
            $scope = (int) Mage::app()->getStore()->getConfig(Mage_Core_Model_Store::XML_PATH_PRICE_SCOPE);
            foreach ($optionsArr as $option) {
                /* @var $option Mage_Catalog_Model_Product_Option */
        
                $this->setItemCount($option->getOptionId());
        
                $value = array();
        
                $value['id'] = $option->getOptionId();
                $value['item_count'] = $this->getItemCount();
                $value['option_id'] = $option->getOptionId();
                $value['title'] = $this->escapeHtml($option->getTitle());
                $value['type'] = $option->getType();
                $value['is_require'] = $option->getIsRequire();
                $value['sort_order'] = $option->getSortOrder();
                $value['can_edit_price'] = $this->getCanEditPrice();
        
                if ($this->getProduct()->getStoreId() != '0') {
                    $value['checkboxScopeTitle'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'title',
                            is_null($option->getStoreTitle()));
                    $value['scopeTitleDisabled'] = is_null($option->getStoreTitle())?'disabled':null;
                }
        
                if ($option->getGroupByType() == Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
        
                    //                    $valuesArr = array_reverse($option->getValues(), true);
        
                    $i = 0;
                    $itemCount = 0;
                    foreach ($option->getValues() as $_value) {
                        /* @var $_value Mage_Catalog_Model_Product_Option_Value */
                        $value['optionValues'][$i] = array(
                                'item_count' => max($itemCount, $_value->getOptionTypeId()),
                                'option_id' => $_value->getOptionId(),
                                'option_type_id' => $_value->getOptionTypeId(),
                                'title' => $this->escapeHtml($_value->getTitle()),
                                'price' => ($showPrice)
                                ? $this->getPriceValue($_value->getPrice(), $_value->getPriceType()) : '',
                                'price_type' => ($showPrice) ? $_value->getPriceType() : 0,
                                'sku' => $this->escapeHtml($_value->getSku()),
                                'sort_order' => $_value->getSortOrder(),
                        );
                        
                        // Add option image to array to be converted to json for custom options loading
                        $optionImage = Mage::getModel('aydus_customconfigurable/optionimage')->load($_value->getOptionTypeId(),'option_type_id');
                        
                        if ($optionImage->getId() && $optionImage->getImage()){
                            
                            $image = $optionImage->getImage();
                        
                            $value['optionValues'][$i]['image'] = $image;
                        }                        
        
                        if ($this->getProduct()->getStoreId() != '0') {
                            $value['optionValues'][$i]['checkboxScopeTitle'] = $this->getCheckboxScopeHtml(
                                    $_value->getOptionId(), 'title', is_null($_value->getStoreTitle()),
                                    $_value->getOptionTypeId());
                            $value['optionValues'][$i]['scopeTitleDisabled'] = is_null($_value->getStoreTitle())
                            ? 'disabled' : null;
                            if ($scope == Mage_Core_Model_Store::PRICE_SCOPE_WEBSITE) {
                                $value['optionValues'][$i]['checkboxScopePrice'] = $this->getCheckboxScopeHtml(
                                        $_value->getOptionId(), 'price', is_null($_value->getstorePrice()),
                                        $_value->getOptionTypeId());
                                $value['optionValues'][$i]['scopePriceDisabled'] = is_null($_value->getStorePrice())
                                ? 'disabled' : null;
                            }
                        }
                        $i++;
                    }
                } else {
                    $value['price'] = ($showPrice)
                    ? $this->getPriceValue($option->getPrice(), $option->getPriceType()) : '';
                    $value['price_type'] = $option->getPriceType();
                    $value['sku'] = $this->escapeHtml($option->getSku());
                    $value['max_characters'] = $option->getMaxCharacters();
                    $value['file_extension'] = $option->getFileExtension();
                    $value['image_size_x'] = $option->getImageSizeX();
                    $value['image_size_y'] = $option->getImageSizeY();
                    if ($this->getProduct()->getStoreId() != '0' &&
                            $scope == Mage_Core_Model_Store::PRICE_SCOPE_WEBSITE) {
                                $value['checkboxScopePrice'] = $this->getCheckboxScopeHtml($option->getOptionId(),
                                        'price', is_null($option->getStorePrice()));
                                $value['scopePriceDisabled'] = is_null($option->getStorePrice())?'disabled':null;
                            }
                }
                $values[] = new Varien_Object($value);
            }
            $this->_values = $values;
        }
        
        return $this->_values;
    }


}
