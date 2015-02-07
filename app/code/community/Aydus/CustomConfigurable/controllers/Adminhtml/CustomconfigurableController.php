<?php

/**
 * CustomConfigurable adminhtml controller
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Adminhtml_CustomconfigurableController extends Mage_Adminhtml_Controller_Action
{
    
    /**
     * Upload option image
     */
    public function uploadAction()
    {
        $result = array();
        
        if ($data = $this->getRequest()->getPost()){
            
            $productId = $data['product_id'];//new is 0
            $optionId = $data['option_id'];
            $optionTypeId = $data['option_type_id']; //new first is 0
            $filename = $data['filename'];
            $imageData = $data['image_data'];
            
            if (is_numeric($productId) && $optionId && is_numeric($optionTypeId) && $filename && $imageData){
                
                try {
                
                    $optionImage = Mage::getModel('aydus_customconfigurable/optionimage');
                    $result = $optionImage->uploadImage($data);
                
                } catch (Exception $e){
                    
                    $result['error'] = true;
                    $result['data'] = $e->getMessage();
                    
                }
                                
            } else {
                
                $result['error'] = true;
                $result['data'] = 'Missing params';
            }
                                  
        } else {
            
            
            $result['error'] = true;
            $result['data'] = 'No data posted';
            
        }
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true)->setBody(Mage::helper('core')->jsonEncode($result));                
        
    }
    
    /**
     * Remove option image
     */
    public function removeAction()
    {
        $result = array();
        
        if ($data = $this->getRequest()->getPost()){
        
            $productId = $data['product_id'];
            $optionId = (int)$data['option_id'];
            $optionTypeId = (int)$data['option_type_id'];
        
            if (is_numeric($productId) && is_numeric($optionTypeId)){
        
                try {
        
                    $optionImage = Mage::getModel('aydus_customconfigurable/optionimage');
                    $result = $optionImage->removeImage($productId, $optionId, $optionTypeId);
        
                } catch (Exception $e){
        
                    $result['error'] = true;
                    $result['data'] = $e->getMessage();
        
                }
        
            } else {
        
                $result['error'] = true;
                $result['data'] = 'Missing params';
            }
        
        } else {
        
        
            $result['error'] = true;
            $result['data'] = 'No data posted';
        
        }
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true)->setBody(Mage::helper('core')->jsonEncode($result));        
    }

}
