<?php

/**
 * CustomConfigurable API test
 *
 * @category    Aydus
 * @package     Aydus_CustomConfigurable
 * @author      Aydus <davidt@aydus.com>
 */
include('bootstrap.php');

class ApiTest extends PHPUnit_Framework_TestCase {

    protected $_model;
    protected $_consumer;
    protected $_key;
    protected $_secret;
    protected $_callbackUrl;  

    protected $_photoCode = 'photo';
    protected static $_photoUrl;
    protected static $_quoteId;
    
    public function setUp() {
        
        $consumer = Mage::getModel('oauth/consumer');
        $consumer->load('Custom Configurable API', 'name');
        
        $this->_consumer = $consumer;
        $this->_key = $consumer->getKey();
        $this->_secret = $consumer->getSecret();
        $this->_callbackUrl = $consumer->getCallbackUrl();
        
        $this->_model = Mage::getModel('aydus_customconfigurable/customconfigurable');
        $this->_model->loadOptions();
    }

    public function testOption()
    {
        $optionCodes = $this->_model->getOptionCodes();
        
        $endpoint = 'api/rest/customconfigurable/option';
        $url = $this->_callbackUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        
        curl_close($ch);
        
        $params = json_decode($response, true);
        
        $this->assertTrue(is_array($params));
        
        $apiOptions = array_keys($params);
        
        $this->assertTrue(is_array($apiOptions));
        
        $intersection = array_intersect($optionCodes, $apiOptions);
        
        $gotOptions = count($intersection) == count($optionCodes);
        
        $this->assertTrue($gotOptions);
        
    }
    
    public function testPhoto()
    {
        $dir = __DIR__;
        chdir($dir);
        
        $imageFile = 'validImage.jpg';
        $path = 'data'.DS.$imageFile;
        $image = file_get_contents($path);
        $imageData = base64_encode($image);
        
        chdir(Mage::getBaseDir());
        
        $endpoint = 'api/rest/customconfigurable/photo';
        $url = $this->_callbackUrl . $endpoint;
        
        $params = array(
                'filename'	=> $imageFile,
                'code'	=> $this->_photoCode,
                'data'	=> $imageData,
        );
         
        $data = json_encode($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data))
        );
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         
        $response = curl_exec($ch);        
        
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerStr = substr($response, 0, $headerSize);
        $headersAr = Mage::helper('aydus_customconfigurable')->getHeadersArray($headerStr);
        
        curl_close($ch);
         
        $gotLocation  = false;
                
        if (is_array($headersAr) && count($headersAr)>0){
        
            $location = '';
        
            foreach ($headersAr as $headerAr){
                 
                foreach ($headerAr as $headerKey=>$headerValue){
        
                    if ($headerKey == 'Location'){
                        $location = $headerValue;
                        $parsedUrlAr = parse_url($location);
                        	
                        if (is_array($parsedUrlAr) && count($parsedUrlAr)>0){
                            
                            //location looks like http://www.example.com/media/aydus/customconfigurable/v/a/1422388585-validImage.jpg
                            if (is_numeric(strpos($location,$imageFile))){
                                $gotLocation = true;
                                
                                self::$_photoUrl = $location;
                                
                            }
        
                        }
                        	
                    }
        
                }
                 
            }
        
        }
         
        $this->assertTrue($gotLocation);        
    }
    
    public function testQuote()
    {
        if (self::$_photoUrl){
            
            $endpoint = 'api/rest/customconfigurable/quote';
            $url = $this->_callbackUrl . $endpoint;
             
            $options = $this->_model->getOptions();
            
            $params = array();
            
            foreach ($options as $code=>$option){
            
                if ($option['required']){
            
                    $value = reset($option['values']);
                    $params[$code] = (int)$value['id'];
            
                }
            
            }
            
            $params[$this->_photoCode] = self::$_photoUrl;
             
            $data = json_encode($params);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
            );
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             
            $response = curl_exec($ch);
            
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headerStr = substr($response, 0, $headerSize);
            $headersAr = Mage::helper('aydus_customconfigurable')->getHeadersArray($headerStr);
                        
            curl_close($ch);
             
            $gotLocation  = false;
            
            if (is_array($headersAr) && count($headersAr)>0){
            
                $location = '';
            
                foreach ($headersAr as $headerAr){
                     
                    foreach ($headerAr as $headerKey=>$headerValue){
            
                        if ($headerKey == 'Location'){
                            $location = $headerValue;
                            $parsedUrlAr = parse_url($location);
                             
                            if (is_array($parsedUrlAr) && count($parsedUrlAr)>0){
            
                                if (is_numeric(strpos($location,$url))){
                                    $gotLocation = true;
            
                                }
            
                            }
                             
                        }
            
                    }
                     
                }
            
            }
             
            $this->assertTrue($gotLocation);
            
            $hash = substr($location, strlen($url)+1);
            $hash = substr($hash, 0, -1);
                        
            $getQuoteUrl = $url.'/'.$hash;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $getQuoteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            
            curl_close($ch);
            
            $params = json_decode($response, true);
            
            $this->assertEquals($hash, $params['hash']);
            $quoteId = (int)$params['quote'];
            
            $gotQuote = ($quoteId > 0) ? true : false;
            
            $this->assertTrue($gotQuote);
            
            self::$_quoteId = $quoteId;            
        }

    }
    
    public function testOrder()
    {
        if (self::$_quoteId){

            $endpoint = 'api/rest/customconfigurable/order';
            $url = $this->_callbackUrl . $endpoint;
            
            $params = array(
        
                    'quote' => self::$_quoteId,
                    'email' => 'dvd_tay@yahoo.com',
                    'billing' => array(
                            "firstname" => "David",
                            "lastname" => "Tay",
                            "street" => array("919 Bard Avenue"),
                            "city" => "Staten Island",
                            "region" => "New York",
                            "postcode" => "10301",
                            "country" =>  "US",
                            "telephone" => "718-740-7227"
                    ),
                    'shipping' => array(
                            "firstname" => "David",
                            "lastname" => "Tay",
                            "street" => array("919 Bard Avenue"),
                            "city" => "Staten Island",
                            "region" => "New York",
                            "postcode" => "10301",
                            "country" =>  "US",
                            "telephone" => "718-740-7227"
                    ),
                    'shipping_method' => 'flatrate_flatrate',
                    'payment_method' => array(
                            "method" => "ccsave",
                            "cc_type" => "VI",
                            "cc_owner" => "David Tay",
                            "cc_number" => "4111111111111111",
                            "cc_exp_month" => "04",
                            "cc_exp_year" => "2017",
                            "cc_cid" => "122"
                    ),
        
            );
            
            $data = json_encode($params);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
            );
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             
            $response = curl_exec($ch);
                        
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headerStr = substr($response, 0, $headerSize);
            $headersAr = Mage::helper('aydus_customconfigurable')->getHeadersArray($headerStr);
            
            curl_close($ch);
             
            $gotLocation  = false;
            
            if (is_array($headersAr) && count($headersAr)>0){
                
                $location = '';
            
                foreach ($headersAr as $headerAr){
                     
                    foreach ($headerAr as $headerKey=>$headerValue){
            
                        if ($headerKey == 'Location'){
                            $location = $headerValue;
                            $parsedUrlAr = parse_url($location);
                             
                            if (is_array($parsedUrlAr) && count($parsedUrlAr)>0){
            
                                $gotLocation = true;
                                            
                            }
                             
                        }
            
                    }
                     
                }
            
            }
             
            $this->assertTrue($gotLocation);
                        
            $orderStatusUrl = $location;
                        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $orderStatusUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            
            curl_close($ch);
            
            $params = json_decode($response, true);
            
            $gotStatus = ($params['status'] == 'processing' || $params['status'] == 'pending') ? true : false;
                                    
            $this->assertTrue($gotStatus);
        
        }        
    
    }

}
