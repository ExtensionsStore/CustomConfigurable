<?php

/**
 * Option image model
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Model_Optionimage extends Mage_Core_Model_Abstract
{
    const MAX_WIDTH = 640;
    const MAX_HEIGHT = 480;
    
	/**
	 * Initialize resource model
	 */
	protected function _construct()
	{
        parent::_construct();
        
		$this->_init('aydus_customconfigurable/optionimage');
	}	
	
	/**
	 * Set option type id, file name and image
	 * 
	 * @param array $data
	 */
	public function uploadImage($data)
	{
	    $result = array();
	    
	    $storeId = Mage::app()->getStore()->getId();
        $productId = (int)$data['product_id'];
	    $optionId = (int)$data['option_id'];
	    $optionTypeId = (int)$data['option_type_id'];
	    $filename = $data['filename'];
	    //no need to url_decode as this is already done!
	    $imageData = $data['image_data'];
	    //filereader always sends the image details, strip it out so we can decode
	    $imageData = substr($imageData, strpos($imageData,'base64,') + 7);
	    $image = base64_decode($imageData);
	     	    
	    //validate image
	    $result = Mage::helper('aydus_customconfigurable')->validateImage($image, self::MAX_WIDTH, self::MAX_HEIGHT, array('image/jpeg', 'image/png'));
	    
	    if ($result['error']){
	        
	        return $result;
	    }
	    
	    //upload image
	    $optionImageDir = Mage::helper('aydus_customconfigurable')->getMediaDir().DS.'optionimage';
	     
	    if (!file_exists($optionImageDir))
	    {
	        mkdir($optionImageDir,0775,true);
	    }
	    
	    $time = time();
	    $timeFilename = $time.'-'. $filename;
	    $optionImageFilePath = $optionImageDir .DS.$timeFilename;
	     
	    $size = file_put_contents($optionImageFilePath, $image);
	     
	    $result['error'] = ($size > 0) ? false : true;
	     
	    if ($result['error']){
	        $result['data'] = 'Image upload failed';
	        return $result;
	    }	    
	    
	    //set optionimage record
	    $imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'aydus/customconfigurable/optionimage/'.$timeFilename;
	    $optionImage = Mage::getModel('aydus_customconfigurable/optionimage')->load($optionTypeId, 'option_type_id');
	    $datetime = date('Y-m-d H:i:s');
	    
	    if (!$optionImage->getId()){
	        $optionImage->setDateCreated($datetime);
	    }
	    	     
	    $optionImage->setProductId($productId)
	    ->setOptionId($optionId)
	    ->setOptionTypeId($optionTypeId)
	    ->setStoreId($storeId)
	    ->setFilename($filename)
	    ->setImage($imageUrl)
	    ->setDateUpdated($datetime);
	    
	    try {
	        
	        $optionImage->save();
	        
	        $result['error'] = false;
	        $result['data'] = $imageUrl;
	         
	    } catch(Exception $e){
	        
	        $result['error'] = true;
	        $result['data'] = $e->getMessage();
	         
	    }
	     
	    return $result;
	    
	}
	
	/**
	 * 
	 * @param int $productId
	 * @param int $optionId
	 * @param int $optionTypeId
	 */
	public function removeImage($productId, $optionId, $optionTypeId)
	{
	    $result = array();
	     
	    try {
	        
	        $collection = Mage::getModel('aydus_customconfigurable/optionimage')->getCollection();
	        $collection->addFieldToFilter('product_id', $productId);
	        $collection->addFieldToFilter('option_id', $optionId);
	        $collection->addFieldToFilter('option_type_id', $optionTypeId);
	        
	        if ($collection->getSize()>0){
	            
	            $optionImage = $collection->getFirstItem();
	            $optionImage->delete();
	        }
	        
	        $result['error'] = false;
	        $result['data'] = 'Image has been deleted';
	         
	         
	    }catch(Exception $e){
	        $result['error'] = true;
	        $result['data'] = $e->getMessage();
	         
	    }
	    
	    return $result;
	    
	}
	
}