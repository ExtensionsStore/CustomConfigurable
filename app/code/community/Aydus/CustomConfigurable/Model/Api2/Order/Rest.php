<?php

/**
 * Order resource
 * 
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

abstract class Aydus_CustomConfigurable_Model_Api2_Order_Rest 
    extends Aydus_CustomConfigurable_Model_Api2_Order 
{

    /**
     * Place order
     * 
     * @param $data 
     * @return array
     */
    protected function _create(array $data) 
    {
        $successUrl = '';
        
        try {
            
            $quoteId = (int)$data['quote'];
            
            if ($quoteId){
                
                $model = $this->getWorkingModel();
                $model->loadOptions();
                
                $result = $model->placeOrder($data);
                
                if (!$result['error']){
                
                    $successUrl = str_replace('index.php/','',Mage::getUrl('api/rest/customconfigurable/order/'.$result['data']));
                                    
                } else {
                
                    throw new Exception($result['data']);
                }       
                         
            } else {
                
                throw new Exception('No quote');
                
            }
                                    
        } catch (Exception $e) {

            $this->_critical($e->getMessage(), 400);
        }

        return $successUrl;
    }
    
    /**
     * Retrieve order
     *
     * @return array
     */
    protected function _retrieve()
    {
        $order = array();
        $request = $this->getRequest();
        $incrementId = $request->getParam('increment_id');
    
        try {
    
            $model = $this->getWorkingModel();
            $result = $model->getOrder($incrementId);
    
            if (!$result['error']){
    
                $order['status'] = $result['data'];
    
            } else {
                throw new Exception($result['data']);
            }
    
        } catch (Exception $e) {
    
            $this->_critical($e->getMessage(), 400);
        }
    
        return $order;
    }    

}
