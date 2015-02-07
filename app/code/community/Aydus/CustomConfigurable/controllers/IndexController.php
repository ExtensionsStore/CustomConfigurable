<?php

/**
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * 
     * @return Aydus_CustomConfigurable_Model_CustomConfigurable
     */
    public function getModel()
    {
        $model = Mage::getModel('aydus_customconfigurable/customconfigurable');
        
        return $model;
    }

    /**
     * Get custom configurable options
     */
    public function optionAction() 
    {
        $result = array();
        
        $productIdOrSku = $this->getRequest()->getParam('product');
        $attributeOptionId = $this->getRequest()->getParam('id');

        try {
        
            $model = $this->getModel();
            $model->loadOptions($productIdOrSku, $attributeOptionId);
            $options = $model->getApiOptions();       
                        
            $result = $options;
            
        
        } catch (Exception $e) {
        
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }        
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true)->setBody(Mage::helper('core')->jsonEncode($result));
        
    }
    
    /**
     * Upload photo
     */
    public function photoAction()
    {
        $result = array();
                        
        try {
            
            //json post
            if ($postBody = file_get_contents('php://input')){
                
                $data = (array)json_decode($postBody);
                
            } else {
                
                $data = $this->getRequest()->getPost();
                
                if (is_array($_FILES) && count($_FILES)>0){
                    
                    $file = reset($_FILES);
                    if (!$data['filename']){
                        $data['filename'] = $file['name'];
                    }
                                        
                    $data['data'] = file_get_contents($file['tmp_name']);
                }
                
            }
            
            if ($data ){
                
                $model = $this->getModel();
                $productIdOrSku = $data['product'];
                $model->loadOptions($productIdOrSku);
                $code = $data['code'];
                $filename = $data['filename'];
                $imageData = $data['data'];
                
                if ($code && $filename && $imageData){
                    
                    $result = $model->uploadPhoto($code, $filename, $imageData);
                    
                    if (!$result['error']){
                        
                        $imageUrl = $result['data'];
                        
                        $result = array('Location' => $imageUrl );
                    }
                                                          
                } else {
                    
                    $result['error'] = true;
                    $result['data'] = 'Missing params';
                }
                
            } else {
                
                $result['error'] = true;
                $result['data'] = 'No post data';
            }
        
        } catch (Exception $e) {
        
            $result['error'] = true;
            $result['data'] = $e->getMessage();
            
        }        
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true)->setBody(Mage::helper('core')->jsonEncode($result));
        
    }
    
    /**
     * Add options to cart
     */
    public function quoteAction()
    {
        $result = array();
        
        try {
            
            //hash to get quote
            $hash = $this->getRequest()->getParam('hash');
            
            if (!$hash){
                //json post
                if ($postBody = file_get_contents('php://input')){
                
                    $data = (array)json_decode($postBody);
                
                } else {
                
                    $data = $this->getRequest()->getPost();
                }
                
                if (is_array($data) && count($data)>0){
                
                    $model = $this->getModel();
                    $productIdOrSku = $data['product'];
                    $model->loadOptions($productIdOrSku);
                    $result = $model->validateCartData($data);
                
                    if (!$result['error']){
                
                        $result = $model->addToCart($data);
                        
                        if ($result['error']){
                            
                            $quoteUrl = $result['data'];
                            $result = array('Location' => $quoteUrl);
                        }
                    }
                
                } else {
                
                    $result['error'] = true;
                    $result['data'] = 'No post data';
                }
                
            } else {
                
                $model = $this->getModel();
                $model->loadOptions();
                
                $result = $model->getQuote($hash);
                
                if (!$result['error']){                    
                    unset($result['error']);
                    $result['hash'] = $hash;
                    $result['quote'] = $result['data'];
                    unset($result['data']);
                }
                
            }
            
        } catch (Exception $e) {
        
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true)->setBody(Mage::helper('core')->jsonEncode($result));
                
    }
    
    /**
     * Place order
     */
    public function orderAction()
    {
        $result = array();
        
        try {

            $incrementId = $this->getRequest()->getParam('id');
            
            if (!$incrementId){
                
                //json post
                if ($postBody = file_get_contents('php://input')){
                
                    $data = (array)json_decode($postBody);
                
                } else {
                
                    $data = $this->getRequest()->getPost();
                }
                
                if (is_array($data) && count($data)>0){
                
                    $model = $this->getModel();
                    $productIdOrSku = $data['product'];
                    $model->loadOptions($productIdOrSku);
                    $result = $model->placeOrder($data);
                    
                    if (!$result['error']){
                        
                        $orderUrl = $result['data'];
                        $result = array('Location' => $orderUrl);
                    }
                
                } else {
                
                    $result['error'] = true;
                    $result['data'] = 'No post data';
                }                
                
            } else {
                
                $model = $this->getModel();
                $result = $model->getOrder($incrementId);
                
                if (!$result['error']){
                    unset($result['error']);
                    $result['status'] = $result['data'];
                    unset($result['data']);
                }
            }
        
        } catch (Exception $e) {
        
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true)->setBody(Mage::helper('core')->jsonEncode($result));        
                
    }
    
}