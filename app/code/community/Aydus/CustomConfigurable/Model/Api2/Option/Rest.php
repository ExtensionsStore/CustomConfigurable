<?php

/**
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

abstract class Aydus_CustomConfigurable_Model_Api2_Option_Rest 
    extends Aydus_CustomConfigurable_Model_Api2_Option 
{

    /**
     * Retrieve Customconfigurable options
     * 
     * @return array
     */
    protected function _retrieve() 
    {
        $options = array();
        $request = $this->getRequest();
        $productIdOrSku = urldecode($request->getParam('product'));
        $attributeOptionId = urldecode($request->getParam('attribute_option'));
        
        try {
            
            $model = $this->getWorkingModel();
            $model->loadOptions($productIdOrSku, $attributeOptionId);
            $options = $model->getApiOptions();
            
        } catch (Exception $e) {

            $this->_critical($e->getMessage(), 400);
        }

        return $options;
    }

}
