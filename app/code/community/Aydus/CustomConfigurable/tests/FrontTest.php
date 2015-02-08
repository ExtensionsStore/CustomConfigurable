<?php

/**
 * CustomConfigurable Frontend test
 *
 * @category    Aydus
 * @package     Aydus_CustomConfigurable
 * @author      Aydus <davidt@aydus.com>
 */
include('bootstrap.php');

class FrontTest extends PHPUnit_Framework_TestCase {

    protected $_callbackUrl;  
    protected $_photoCode = 'photo';
    protected static $_photoUrl;
    protected static $_quoteId;
    
    public function setUp() 
    {
        $consumer = Mage::getModel('oauth/consumer');
        $consumer->load('Custom Configurable API', 'name');
        
        $this->_callbackUrl = $consumer->getCallbackUrl();
        
        $this->_model = Mage::getModel('aydus_customconfigurable/customconfigurable');
        $this->_model->loadOptions();
    }

    public function testOption()
    {
        $optionCodes = $this->_model->getOptionCodes();
        
        $endpoint = 'customconfigurable/index/option';
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
        
        $endpoint = 'customconfigurable/index/photo';
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
        
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        
        $params = json_decode($body, true);
        
        curl_close($ch);
                         
        $gotLocation = (is_array($params) && isset($params['Location']) && $params['Location']) ? true : false;
                
        $this->assertTrue($gotLocation);        
        
        self::$_photoUrl = $params['Location'];
    }
    
    public function testQuote()
    {
        if (self::$_photoUrl){
            
            $endpoint = 'customconfigurable/index/quote';
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
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            
            $params = json_decode($body, true);
            
            curl_close($ch);
             
            $gotLocation = (is_array($params) && isset($params['Location']) && $params['Location']) ? true : false;
                    
            $this->assertTrue($gotLocation);     
                                             
            $getQuoteUrl = $params['Location'];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $getQuoteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            
            curl_close($ch);
            
            $params = json_decode($response, true);
            
            $quoteId = (int)$params['quote'];
            
            $gotQuote = ($quoteId > 0) ? true : false;
            
            $this->assertTrue($gotQuote);
                            
            self::$_quoteId = $quoteId;
        }
    }
    
    public function testOrder()
    {
        if (self::$_quoteId){
        
            $endpoint = 'customconfigurable/index/order';
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
        
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            
            $params = json_decode($body, true);
                        
            curl_close($ch);
             
            $gotLocation = (is_array($params) && isset($params['Location']) && $params['Location']) ? true : false;
                    
            $this->assertTrue($gotLocation);    
        
            $orderStatusUrl = $params['Location'];
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $orderStatusUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
            $response = curl_exec($ch);
            
            $params = json_decode($response, true);
            
            curl_close($ch);
                
            $gotStatus = ($params['status'] == 'processing' || $params['status'] == 'pending') ? true : false;
        
            $this->assertTrue($gotStatus);
        
        }    
        
    }

}
