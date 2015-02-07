<?php

/**
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

abstract class Aydus_CustomConfigurable_Model_Api2_Quote_Rest 
    extends Aydus_CustomConfigurable_Model_Api2_Quote 
{

    /**
     * Add to cart
     * 
     * @param $data 
     * @return string
     */
    protected function _create(array $data) 
    {
        $quoteUrl = '';
        
        try {
            
            $model = $this->getWorkingModel();
            $productIdOrSku = $data['product'];
            $model->loadOptions($productIdOrSku);
            
            $result = $model->validateCartData($data);
            
            if (!$result['error']){
            
                Mage::app()->setCurrentStore($model->getStoreId());
                $result = $model->addToCart($data);
                
                if (!$result['error']){
                    
                    $quoteUrl = str_replace('index.php/','',Mage::getUrl('api/rest/customconfigurable/quote/'.$result['data']));
                    
                } else {
                    
                    throw new Exception($result['data']);
                }
                
            } else {
                
                throw new Exception($result['data']);
            }
                        
        } catch (Exception $e) {

            $this->_critical($e->getMessage(), 400);
        }

        return $quoteUrl;
    }
    
    /**
     * Retrieve quote
     *
     * @return array
     */
    protected function _retrieve()
    {
        $quote = array();
        $request = $this->getRequest();
        $hash = urldecode($request->getParam('hash'));
    
        try {
    
            $model = $this->getWorkingModel();
            $result = $model->getQuote($hash);
            
            if (!$result['error']){
                
                $quote['hash'] = $hash;
                $quote['quote'] = $result['data'];
                
            } else {
                throw new Exception($result['data']);
            }
    
        } catch (Exception $e) {
    
            $this->_critical($e->getMessage(), 400);
        }
    
        return $quote;
    }
    

}
