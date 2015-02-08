<?php

/**
 * CustomConfigurable model test
 *
 * @category    Aydus
 * @package     Aydus_CustomConfigurable
 * @author      Aydus <davidt@aydus.com>
 */
include('bootstrap.php');

class ModelTest extends PHPUnit_Framework_TestCase {

    protected $_model;
    protected $_photoCode = 'photo';
    protected static $_photoUrl;
    protected static $_quoteId;
    
    public function setUp() 
    {
        $this->_model = Mage::getModel('aydus_customconfigurable/customconfigurable');
        $this->_model->loadOptions();
    }

    /**
     * Test CustomConfigurable product for options
     */
    public function testOptions()
    {        
        $optionCodes = $this->_model->getOptionCodes();
        
        $options = $this->_model->getOptions();
                
        foreach ($options as $code => $option){
            
            $validOption = in_array($code, $optionCodes);
            
            $this->assertTrue($validOption);
            
        }
        
    }
    
    /**
     * Test uploading a base64 encoded image
     */
    public function testUploadPhoto()
    {
        $dir = __DIR__;
        chdir($dir);
        
        $imageFile = 'validImage.jpg';
        $path = 'data'.DS.$imageFile;
        $image = file_get_contents($path);
        $imageData = base64_encode($image);
        $result = $this->_model->uploadPhoto($this->_photoCode, $imageFile, $imageData, true);
        if (!$result['error']){
            self::$_photoUrl = $result['data'];
        }
                
        $this->assertFalse($result['error']);
        
        $imageFile = 'invalidImage.jpg';
        $path = 'data'.DS.$imageFile;
        $image = file_get_contents($path);
        $imageData = base64_encode($image);
        $result = $this->_model->uploadPhoto($this->_photoCode, $imageFile, $imageData, true);
        $this->assertTrue($result['error']);
        
        chdir(Mage::getBaseDir());
    }
    
    /**
     * Test adding product to cart
     */
    public function testAddToCart()
    {
        if (self::$_photoUrl){
            
            $options = $this->_model->getOptions();
            
            $data = array();
            
            foreach ($options as $code=>$option){
                
                if ($option['required']){
                    
                    $value = reset($option['values']);
                    $data[$code] = $value['id'];
                    
                }
                
            }
            
            $data[$this->_photoCode] = self::$_photoUrl;
            
            $result = $this->_model->addToCart($data); 
            
            $this->assertFalse($result['error']);
                        
            $hash = $result['data'];
            
            $result = $this->_model->getQuote($hash);
            
            $this->assertFalse($result['error']);
            
            $quoteId = $result['data'];
            
            $store = Mage::getSingleton('core/store')->load($this->_model->getStoreId());
            $quote = Mage::getModel('sales/quote')->setStore($store)->load($quoteId);
            
            $gotQuote = ($quote && $quote->getId()) ? true : false; 
            
            $this->assertTrue($gotQuote);
            
            self::$_quoteId = $quoteId;
            
        }
        
    }
    
    public function testPlaceOrder()
    {
        if (self::$_quoteId){
            
            $data = array(
                    
                'quote' => self::$_quoteId,
                'email' => 'davidt@aydus.com',
                'billing' => array(
                    "firstname" => "David",
                    "lastname" => "Tay",
                    "street" => array("1601 Beverly Road"), 
                    "city" => "Brooklyn",
                    "region" => "New York",
                    "postcode" => "11226",
                    "country" =>  "US",
                    "telephone" => "718-233-1719"                            
                ),
                'shipping' => array(
                        "firstname" => "David",
                        "lastname" => "Tay",
                        "street" => array("1601 Beverly Road"),
                        "city" => "Brooklyn",
                        "region" => "New York",
                        "postcode" => "11226",
                        "country" =>  "US",
                        "telephone" => "718-233-1719"                            
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
            
            $result = $this->_model->placeOrder($data);           
            
            $incrementId = $result['data'];
            
            $order = Mage::getModel('sales/order')->load($incrementId,'increment_id');
            
            $gotOrder = ($order && $order->getId()) ? true : false;
            
            $this->assertTrue($gotOrder);            
            
        }
        
    }

}
