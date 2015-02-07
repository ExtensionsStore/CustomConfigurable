<?php

/**
 * CustomConfigurable helper
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get media dir
     * 
     * @return string
     */
    public function getMediaDir()
    {
        return Mage::getBaseDir('media').DS.'aydus'.DS.'customconfigurable';
    }
	
    /**
     * http://php.net/manual/en/function.getimagesizefromstring.php
     *
     * @param resource $string_data
     */
    public function getimagesizefromstring($string_data)
    {
        $uri = 'data://application/octet-stream;base64,'  . base64_encode($string_data);
        return getimagesize($uri);
    }

    /**
     * 
     * @param Resource $image
     * @param int $maxWidth
     * @param int $maxHeight
     * @param array $validMimeTypes
     * @return array
     */
    public function validateImage($image, $maxWidth, $maxHeight, $validMimeTypes)
    {
        //store the file first as getimagesizefromstring may be unreliable
        $imageDir = Mage::getBaseDir('var').DS.'tmp';
        
        if (!file_exists($imageDir)){
            mkdir($imageDir, 0777, true);
        }
        
        $tmpfile = $imageDir.DS.time();
        $size = file_put_contents($tmpfile, $image);

        $details = getimagesize($tmpfile);

        if (!is_array($details) || count($details)==0){
            if (!function_exists('getimagesizefromstring')) {
            
                $details = $this->getimagesizefromstring($image);
            
            } else {
            
                $details = getimagesizefromstring($image);
            }            
        }
        
        $width = $details[0];
        $height = $details[1];
        $type = $details['mime'];
        
        if ($width && $height && $type){
        
            if ($width > $maxWidth || $height > $maxHeight){
                $result['error'] = true;
                $result['data'] = 'Wrong dimensions';
        
                return $result;
            }
        
            if (!in_array($type,$validMimeTypes)){
        
                $result['error'] = true;
                $result['data'] = 'Wrong image type';
        
                return $result;
            }
        
            $result['error'] = false;
        
        } else {
        
            $result['error'] = true;
            $result['data'] = 'Could not get image details';
        }  

        return $result;
    }
    
    /**
     * Get the option image
     * 
     * @param Mage_Catalog_Model_Product_Option_Value $optionValue
     * @return string
     */
    public function getOptionImage($optionValue)
    {
        $image = '';
        $optionId = $optionValue->getOptionId();
        $optionTypeId = $optionValue->getOptionTypeId();
        
        $collection = Mage::getModel('aydus_customconfigurable/optionimage')->getCollection();        
        $collection->addFieldToFilter('option_id', $optionId);
        $collection->addFieldToFilter('option_type_id', $optionTypeId);
        
        if ($collection->getSize()){
            
            $optionimage = $collection->getFirstItem();
            
            $image = $optionimage->getImage();
        }
        
        return $image;
    }
    
    /**
     * http://stackoverflow.com/questions/10589889/returning-header-as-array-using-curl
     *
     * @param string $headerContent
     * @return array
     */
    public function getHeadersArray($headerContent)
    {
        $headers = array();
    
        // Split the string on every "double" new line.
        $arrRequests = explode("\r\n\r\n", $headerContent);
    
        // Loop of response headers. The "count() -1" is to
        //avoid an empty row for the extra line break before the body of the response.
        for ($index = 0; $index < count($arrRequests) -1; $index++) {
    
            foreach (explode("\r\n", $arrRequests[$index]) as $i => $line)
            {
                if ($i === 0)
                    $headers[$index]['http_code'] = $line;
                else
                {
                    list ($key, $value) = explode(': ', $line);
                    $headers[$index][$key] = $value;
                }
            }
        }
    
        return $headers;
    }    
}