<?php

/**
 * Upload photo
 * 
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

abstract class Aydus_CustomConfigurable_Model_Api2_Photo_Rest 
    extends Aydus_CustomConfigurable_Model_Api2_Photo
{

    /**
     * Create photo
     * Return photo url in Location header on success
     * 
     * @return array
     */
    protected function _create(array $data) 
    {
        $photoUrl = '';
        
        try {
            
            $model = $this->getWorkingModel();
            $productIdOrSku = $data['product'];
            $model->loadOptions($productIdOrSku);
            $code = $data['code'];
            $filename = $data['filename'];
            $imageData = $data['data'];
            
            if (!$code || !$filename || !$imageData){
                throw new Exception('Missing filename or data parameters');
            }
            
            $result = $model->uploadPhoto($code, $filename, $imageData, true);
            
            if (!$result['error']){
                
                $photoUrl = $result['data'];
                
                
            } else {
                
                throw new Exception($result['data']);
            }
            
            
        } catch (Exception $e) {

            $this->_critical($e->getMessage(), 400);
        }

        return $photoUrl;
    }

}
