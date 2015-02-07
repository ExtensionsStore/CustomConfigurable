<?php

/**
 * CustomConfigurable model
 *
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_CustomConfigurable_Model_Customconfigurable extends Mage_Core_Model_Abstract
{
    const ATTRIBUTE_SET = 'CustomConfigurable';
    
    /**
     * 
     * @var eav/attribute_set
     */
    protected $_attributeSet;
    
    /**
     * Configurable values
     * 
     * @var string
     */
    protected $_attribute;
    protected $_attributeCode;
    protected $_attributeLabel;
    protected $_sku;
    protected $_consumer;
    
    /**
     * The main configurable product
     * 
     * @var Mage_Catalog_Model_Product
     */
    protected $_product;
    
    /**
     * Options for the configurable product
     * 
     * @var array
     */
    protected $_options = array();
    protected $_apiOptions;
    protected $_requiredOptions;
        
    /**
     * Auto construct
     */
    protected function _construct()
    {
        //load attribute
        $attributeCode = Mage::getStoreConfig('aydus_customconfigurable/configuration/attribute');
        if ($attributeCode){
            
            $config = Mage::getSingleton('eav/config');
            $attribute = $config->getAttribute('catalog_product', $attributeCode);
            
            if ($attribute && $attribute->getId()){
                
                $this->_attribute = $attribute;
                $this->_attributeCode = $attributeCode;
                $this->_attributeLabel = $attribute->getFrontendLabel();
                
            } else {
                
                Mage::log('Could not load attribute', null, 'aydus_customconfigurable.log');
            }
        
        }
        
        //sku of configurable product
        $sku = Mage::getStoreConfig('aydus_customconfigurable/configuration/configurable_product');
        if ($sku){
                    
            $this->_sku = $sku;
        }
        
        //load consumer
        $this->_consumer = Mage::getModel('oauth/consumer');
        $this->_consumer->load('Custom Case API', 'name');
        
        if (!$this->_consumer->getId()){
        
            Mage::log('CustomConfigurable consumer is not installed', null, 'aydus_customconfigurable.log');
        }
        
    }
    
    /**
     * Lighter load for config only
     */
    public function getConfigOptions()
    {
        $this->loadOptions(null, null, false);
        
        return $this->_options;
    }
        
    /**
     * Load options data
     * 
     * @param int|string $productIdOrSku - The configurable product
     * @param int $attributeOptionValueId - Option Id of associated simple product
     * @param bool $loadImage Catalog image requires frontend to be loaded
     * @return Aydus_CustomConfigurable_Model_CustomConfigurable
     */
    public function loadOptions($productIdOrSku = null, $attributeOptionValueId =  null, $loadImage = true)
    {        
        //load configurable product
        if ($productIdOrSku){
        
            $productId = Mage::getModel('catalog/product')->getIdBySku($productIdOrSku);
        
            if (!$productId){
                $productId = (int)$productIdOrSku;
            }
        
            $product = Mage::getModel('catalog/product')->load($productId);
        
            if ($product && $product->getId()){
                $this->setProduct($product);
            }
        }
        
        //load overridden options of associated simple
        if ($attributeOptionValueId){
        
            $attributeSet = $this->getAttributeSet();
            $collection = Mage::getModel('catalog/product')->getCollection();
            $collection->addAttributeToFilter('attribute_set_id',$attributeSet->getId());
            $collection->addAttributeToFilter($this->_attributeCode, $attributeOptionValueId);
                
            if ($collection->getSize()> 0){
        
                $associatedProduct = $collection->getFirstItem();
            }
        
        }
        
        //load attribute options
        $attributeProducts = $this->getAttributeProducts();
        //overridden options
        $associatedOptions = array();
        $values = array();
        
        foreach ($attributeProducts as $attributeProduct) {
        
            $id = $attributeProduct->getData($this->_attributeCode);
            $title = $attributeProduct->getName();
            $image = ($loadImage) ? (string)Mage::helper('catalog/image')->init($attributeProduct, 'thumbnail') : null;
            //get options of associated product
            if ($associatedProduct && $associatedProduct->getId() == $attributeProduct->getId() && $attributeProduct->getHasOptions()){
                $options = $attributeProduct->getOptions();
                if (is_array($options) && count($options)>0){
                    
                    foreach ($options as $option){
                        
                        $code = strtolower(preg_replace('/\s+/', '_',$option->getTitle()));
                        
                        $associatedOptions[$code] = $option;
                    }
                }
            }
        
            $values[$id] = array(
                    'id' => $id,
                    'label' =>$title,
                    'image' => $image,
            );
             
        }
        
        $attribute = $this->getAttribute();
        $attributeCode = $this->getAttributeCode();
        
        $this->_options[$attributeCode]['id'] = $attribute->getId();
        $this->_options[$attributeCode]['label'] = $attribute->getFrontendLabel();;
        $this->_options[$attributeCode]['type'] = $attribute->getFrontendInput();
        $this->_options[$attributeCode]['required'] = $attribute->getIsRequired();
        $this->_options[$attributeCode]['object'] = $attribute;
        $this->_options[$attributeCode]['values'] = $values;

        
        //load custom options
        $customOptions = $this->getProduct()->getOptions();
        
        if (is_array($customOptions) && count($customOptions)>0){
                    
            //custom options
            foreach ($customOptions as $customOption){
        
                $code = strtolower(preg_replace('/\s+/', '_',$customOption->getTitle()));
                
                $customOptionValues = $customOption->getValues();
                $values = array();
                //use overridden option for image
                $useAssociatedOption = false;
                if (is_array($associatedOptions) && count($associatedOptions)>0 && in_array($code, array_keys($associatedOptions))){
                
                    $associatedOption = $associatedOptions[$code];
                    $associatedOptionValues = $associatedOption->getValues();
                    $useAssociatedOption = true;
                    $index = 0;
                }
        
                foreach ($customOptionValues as $customOptionId=>$customOptionValue){
                    
                    if ($loadImage && $useAssociatedOption){
                        
                        $sliced = array_slice($associatedOptionValues, $index, 1);
                        $imageOptionValue = reset($sliced);
                        $index++;
                    } else {
                        $imageOptionValue = $customOptionValue;
                    }
                    
                    $values[$customOptionId] = array(
                            'id' => $customOptionId,
                            'label' =>$customOptionValue->getTitle(),
                            'image' => ($loadImage) ? Mage::helper('aydus_customconfigurable')->getOptionImage($imageOptionValue) : null,
                    );
                }
        
                $this->_options[$code]['id'] = $customOption->getId();
                $this->_options[$code]['label'] = $customOption->getTitle();
                $this->_options[$code]['type'] = $customOption->getType();
                $this->_options[$code]['required'] = $customOption->getIsRequire();
                $this->_options[$code]['object'] = $customOption;
                $this->_options[$code]['values'] = $values;
        
            }
        
        } else {
            
            Mage::log('CustomConfigurable configurable does not have any custom options', null, 'aydus_customconfigurable.log');
        }
        
        return $this;
    }
    
    /**
     * Store Id needed for getting inventory
     */
    public function getStoreId()
    {
        if (!$this->hasData('store_id') || !$this->getData('store_id')){
            
            if (Mage::app()->getStore()->isAdmin() || !Mage::app()->getStore()->getId()){
                
                //get store id of consumer
                if (is_object(Mage::helper('aydus_restension'))){
                    
                    $storeId = Mage::helper('aydus_restension')->getConsumerStoreId($this->_consumer);
                    
                } else {
                    
                    $storeId = Mage::app()->getWebsite()->getDefaultStore()->getId();
                }
                
            } else {
                
                $storeId = Mage::app()->getStore()->getId();
                
            }
            
            $this->setData('store_id', $storeId);
        }
        
        return $this->getData('store_id');
    }
        
    /**
     * Get custom configurable product
     * 
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if (!$this->_product){
            
            $this->_product = Mage::getModel('catalog/product')->setStoreId($this->getStoreId());
            $id = $this->_product->getResource()->getIdBySku($this->_sku);
            $this->_product->load($id);
            
            if (!$this->_product->getId()){
            
                Mage::log('CustomConfigurable configurable product could not be loaded', null, 'aydus_customconfigurable.log');
            }
                        
        }
        
        return $this->_product;
    }
    
    /**
     *
     * @param Mage_Catalog_Model_Product $product
     * @return $this
     */
    public function setProduct($product)
    {
        if ($product && $product->getTypeID() == 'configurable'){
    
            if (!$product->getSku()){

                $product->load($product->getId());
            }
            
            $collection = $product->getTypeInstance(true)->getConfigurableAttributeCollection($product);
            
            if ($collection->getSize()>0){
                
                $attribute = $collection->getFirstItem()->getProductAttribute();
                
                $this->_product = $product;
                $this->_sku = $product->getSku();
                $this->_attribute = $attribute;
                $this->_attributeCode = $attribute->getAttributeCode();
                $this->_attributeLabel = $attribute->getFrontendLabel();

            } else {
                
                throw new Exception('Could not load configurable attribute');
            }
            
        } else {

            throw new Exception('Configurable product only');
        }
    
        return $this;
    }
    
    /**
     * 
     * @return Mage_Eav
     */
    public function getAttribute()
    {
        if (!$this->_attribute){
            
            $attribute = Mage::getModel('eav/entity_attribute');
            
            if ($this->_attributeCode || $this->getProduct()){
                 
                if ($this->_attributeCode){
                    
                    $config = Mage::getSingleton('eav/config');
                    $attribute = $config->getAttribute('catalog_product', $this->_attributeCode);
                    
                } else {
                    
                    $collection = $this->getProduct()->getTypeInstance(true)->getConfigurableAttributeCollection();
                    
                    if ($collection->getSize()>0){
                    
                        $attribute = $collection->getFirstItem();
                    
                        $this->_sku = $this->getProduct()->getSku();
                        $this->_attributeCode = $attribute->getAttributeCode();
                        $this->_attributeLabel = $attribute->getFrontendLabel();
                    
                    } else {
                    
                        throw new Exception('Could not load configurable attribute');
                    }                    
                }

                $this->_attribute = $attribute;
                
            } else {
            
                throw new Exception('Could not load configurable attribute');
            
            }
            
        }

        return $this->_attribute;
    }
    
    /**
     *
     * @return eav/entity_attribute_set
     */
    public function getAttributeSet()
    {
        if (!$this->_attributeSet){
            
            $this->_attributeSet = Mage::getModel('eav/entity_attribute_set');
            
            $collection = Mage::getResourceModel('eav/entity_attribute_set_collection');
            $collection->addFieldToFilter('attribute_set_name', self::ATTRIBUTE_SET);
            
            if ($collection->getSize()>0){
                $this->_attributeSet = $collection->getFirstItem();
            }            
        }
            
        return $this->_attributeSet;
    }    
    
    /**
     * 
     * @return string
     */
    public function getAttributeCode()
    {
        if (!$this->_attributeCode){
            
            $attribute = $this->getAttribute();
            $this->_attributeCode = $attribute->getAttributeCode();
        }
        
        return $this->_attributeCode;
    }
    
    /**
     *
     * @return string
     */
    public function getAttributeLabel()
    {
        if (!$this->_attributeLabel){
            
            $attribute = $this->getAttribute();
            $this->_attributeLabel = $attribute->getFrontendLabel();            
        }
    
        return $this->_attributeLabel;
    }    
    
    /**
     * 
     * Get attribute products
     */
    public function getAttributeProducts()
    {
        $products = array();
        $attribute = $this->getAttribute();

        $skipSaleableCheck = Mage::helper('catalog/product')->getSkipSaleableCheck();
        $usedProducts = $this->getProduct()->getTypeInstance(true)->getUsedProducts(array($attribute->getId()), $this->getProduct());
        $values = array();
        
        foreach ($usedProducts as $usedProduct) {
        
            $usedProduct->setStoreId($this->getStoreId())->load($usedProduct->getId());
        
            if ($usedProduct->getIsSalable() || $skipSaleableCheck) 
            {
                $products[] = $usedProduct;
            }
        
        }

        return $products;
    }
    

    /**
     * Get custom configurable options
     * 
     * @return array
     */
    public function getOptions()
    {
        if (!$this->_options){
            $this->loadOptions();
        }
        
        return $this->_options;
    }
    
    /**
     * Option codes for Custom Case model
     */
    public function getOptionCodes()
    {
        $codes = array_keys($this->_options);
        return $codes;
    }
    
    /**
     * Get options that are required
     * 
     * @return associative array of code=>id
     */
    public function getRequiredOptions()
    {
        if (!$this->_requiredOptions){
        
            $requiredOptions = array();
        
            foreach ($this->_options as $code=>$option){
                
                if ($option['required']){
                    
                    $requiredOptions[$code] = $option['id'];
                    
                }
        
            }
        
            $this->_requiredOptions = $requiredOptions;
        }
        
        return $this->_requiredOptions;        
    }
    
    /**
     * Get api options
     *
     * @return array
     */
    public function getApiOptions()
    {
        if (!$this->_apiOptions){
            
            $apiOptions = array();
            
            foreach ($this->_options as $code=>$option){
                
                $apiOptions[$code] = array(
                    'id' => $option['id'],
                    'label' => $option['label'],
                    'type' => $option['type'],
                    'required' => $option['required'],
                    'values' => $option['values'],
                );
                
            }
            
            $this->_apiOptions = $apiOptions;
        }
        
        return $this->_apiOptions;
    }    
    
    /**
     * Upload photo
     * 
     * @param string $code
     * @param string $filename 
     * @param string $data 
     * @param bool $encoded Base 64 encoded
     */
    public function uploadPhoto($code, $filename, $data, $encoded=false)
    {
        $result = array();
        $option = $this->_options[$code]['object'];
        $image = ($encoded) ? base64_decode($data) : $data;
        
        $result = $this->_validatePhoto($image, $option);
        if (!$result['error']){
            
            $result = $this->_uploadPhoto($filename, $image, $option);
        }
                
        return $result;
    }
    
    /**
     * 
     * @param resource $image
     * @param Mage_Catalog_Model_Product_Option $option
     */
    protected function _validatePhoto($image, $option)
    {
        $result = array();
        
        $result = Mage::helper('aydus_customconfigurable')->validateImage($image, $option->getImageSizeX(), $option->getImageSizeY(), array('image/jpeg', 'image/png'));
                
        return $result;
    }    
       
    /**
     *
     * @param string $filename
     * @param resource $image
     * @param Mage_Catalog_Model_Product_Option $option
     */    
    protected function _uploadPhoto($filename, $image, $option)
    {
        $result = array();
        
        $optionFile = $option->groupFactory($option->getType());
        $extension = pathinfo(strtolower($filename), PATHINFO_EXTENSION);
        
        $filename = Mage_Core_Model_File_Uploader::getCorrectFileName($filename);
        $dispersion = Mage_Core_Model_File_Uploader::getDispretionPath($filename);
        
        $quoteDir = $optionFile->getQuoteTargetDir().$dispersion;
        $uploadDir = Mage::helper('aydus_customconfigurable')->getMediaDir().DS.$dispersion;
        
        if (!file_exists($quoteDir))
        {
            mkdir($quoteDir,0775,true);
        }
        
        if (!file_exists($uploadDir))
        {
            mkdir($uploadDir,0775,true);
        }        
        
        $hash = md5($image);
        $filenameHash = $hash . '.' . $extension;
        $quoteFilePath = $quoteDir .DS. $filenameHash;
        
        $size = file_put_contents($quoteFilePath, $image);
        
        $result['error'] = ($size > 0) ? false : true;
        
        if ($result['error']){
            $result['data'] = 'File upload failed';
        } else {
            
            $time = time();
            $uploadFilePath = $uploadDir.DS.$time.'-'.$filename;
            copy($quoteFilePath, $uploadFilePath);            
            
            $result['data'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'aydus'.DS.'customconfigurable'.$dispersion.DS.$time.'-'.$filename;
            
        }

        return $result;
    }
    
    /**
     * Validate posted data for add to cart
     * 
     * @param array $data
     * @return array
     */
    public function validateCartData($data)
    {
        $result = array();
        
        $requiredOptions = $this->getRequiredOptions();
        $missingOptions = array();
        
        foreach ($requiredOptions as $code => $optionId){
        
            if (!$data[$code]){
                $missingOptions[] = $code;
            }
        }
        
        if (count($missingOptions) == 0){
        
            $result['error'] = false;
        
        } else {
        
            $result['error'] = true;
            $result['data'] = 'Missing required options';
            $result['missing_options'] = $missingOptions;
            
        }        
        
        return $result;
    }
    
    protected function _getParams($data)
    {
        $result = array();
        
        $params = array();
        $product = $this->getProduct();
        $params['product'] = $product->getId();
        $params['related_product'] = null;
        
        $attribute = $this->getAttribute();
        
        $attributeOptionId = $data[$this->_attributeCode];
        $params['super_attribute'] = array(
                        $attribute->getId() => $attributeOptionId,
                );
        
        $options = array();
        
        foreach ($data as $code => $optionValue){
            
            if ($code != $this->_attributeCode && in_array($code, $this->getOptionCodes()) && $optionValue){
        
                $optionId = $this->_options[$code]['id'];
                
                if ($this->_options[$code]['type']=='file'){
                    
                    //uploaded file
                    if (is_array($_FILES) && count($_FILES) > 0){
                        
                        $oldKey = key($_FILES);
                        $file = reset($_FILES);
                        unset($_FILES[$oldKey]);
                        $_FILES['options_'.$optionId.'_file'] = $file;
                        $options['options_'.$optionId.'_file_action'] = 'save_new';
                        $params['options_'.$optionId.'_file_action'] = 'save_new';
                        
                    //previously uploaded file
                    } else {

                        $mediaPath = substr($optionValue, strpos($optionValue,'media') );
                        $imagePath = Mage::getBaseDir().DS.$mediaPath;
                        
                        if (file_exists($imagePath)){
                            
                            $optionFile = Mage::getModel('catalog/product_option_type_file');
                            
                            $image = file_get_contents($imagePath);
                            
                            if (!function_exists('getimagesizefromstring')) {
                            
                                $details = Mage::helper('aydus_customconfigurable')->getimagesizefromstring($image);
                            
                            } else {
                            
                                $details = getimagesizefromstring($image);
                            }                            
                            
                            $width = $details[0];
                            $height = $details[1];
                            $type = $details['mime'];
                            
                            $fileHash = md5($image);
                            $filenamePath = substr($mediaPath, strpos($mediaPath,'customconfigurable') + 11 );
                            $explodedPath = explode('/',$filenamePath);
                            $timeFilename = $explodedPath[2];
                            $explodedFilename = explode('-',$timeFilename);
                            $title = $explodedFilename[1];
                            $extension = pathinfo(strtolower($timeFilename), PATHINFO_EXTENSION);
                            $filePath  = $explodedPath[0].DS.$explodedPath[1].DS.$fileHash.'.'.$extension;
                            
                            $quotePath = $optionFile->getQuoteTargetDir(true).DS.$filePath;
                            $orderPath = $optionFile->getOrderTargetDir(true).DS.$filePath;
                            $fullPath = Mage::getBaseDir().DS.$quotePath;
                            $size = strlen($image);
                            $secretKey = substr(md5(file_get_contents($fullPath)), 0, 20);
                            
                            $options[$optionId] = array(
                                    'type' => $type,
                                    'title' => $title,
                                    'quote_path' => $quotePath,
                                    'order_path' => $orderPath,
                                    'fullpath' => $fullPath,
                                    'size' => $size,
                                    'width' => $width,
                                    'height' => $height,
                                    'secret_key' => $secretKey,
                            );                            
                                             
                        } else {
                            
                            $result['error'] = true;
                            $result['data'] = 'Image does not exist';
                            break;
                        }

                    }
        
                } else {
        
                    $options[$optionId] = $optionValue;
        
                }
                
            }
            
        }
        
        if (!$result['error']){
            
            $params['options'] = $options;
            $params['qty'] = (isset($data['qty'])) ? (int)$data['qty'] : 1;
            
            $result['error'] = false;
            $result['data'] = $params;
            
        }

        return $result;
    }
        
    /**
     * Add data to cart
     * 
     * @param array $data
     * @return array
     */
    public function addToCart($data)
    {
        $result = array();
        
        try {
            
            $cart = Mage::getSingleton('checkout/cart');
            
            $result = $this->_getParams($data);
            
            if (!$result['error']){
                
                $params = $result['data'];
                $product = $this->getProduct();
                $cart->addProduct($product, $params);
                $cart->save();
                
                $quoteId = $cart->getQuote()->getId();
                $hash = $this->_saveEntityId($quoteId);
                
                $result['error'] = false;
                $result['data'] = $hash;
            } 
                   
        } catch(Exception $e){
            
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Get quote id
     * 
     * @param string $hash
     * @return array
     */
    public function getQuote($hash)
    {
        $result = array();
        
        try {
        
            $hashModel = Mage::getModel('aydus_customconfigurable/hash')->load($hash);
            
            if ($hashModel->getId()){
                
                $result['error'] = false;
                $result['data'] = $hashModel->getEntityId();
                
            } else {
                $result['error'] = true;
                $result['data'] = 'Quote not found';
            }
             
        } catch(Exception $e){
        
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }
        
        return $result;        
    }
    
    protected function _saveEntityId($entityId)
    {
        $hash = md5(get_class($this).$entityId);
        
        $hashModel = Mage::getModel('aydus_customconfigurable/hash')->load($hash);
        $date = date('Y-m-d H:i:s');
        
        if (!$hashModel->getId()){
            $hashModel->setDateCreated($date);
        }
        
        $hashModel->setHash($hash);
        $hashModel->setEntityId($entityId);
        $hashModel->setDateUpdated($date);
        $hashModel->save();
        
        return $hash;
    }
    
    /**
     * Place order
     * 
     * @param array $data
     * @return array
     */
    public function placeOrder($data)
    {
        $result = array();
        
        try {
        
            //load quote
            $quoteId = (int)$data['quote'];
            $store = Mage::getSingleton('core/store')->load($this->getStoreId());
            $quote = Mage::getModel('sales/quote')->setStore($store)->load($quoteId);
            
            if (!$quote->getIsActive()){
                
                $result['error'] = true;
                $result['data'] = 'Quote is inactive';
                
                return $result;
            }
            
            //set customer email - required
            $email = $data['email'];
            if(!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)){
                
                $result['error'] = true;
                $result['data'] = 'Email is invalid';
                
                return $result;
            }
            $quote->setCustomerEmail($email);
            
            //set addresses
            $quoteAddressCollection = Mage::getModel('sales/quote_address')->getCollection();
            $quoteAddressCollection->addFieldToFilter('quote_id',$quoteId);
            //delete existing addresses - should not have any
            if ($quoteAddressCollection->getSize()>0){
                foreach ($quoteAddressCollection as $address){
                    $address->delete();
                }
            }     
              
            //set billing addresses
            $billingAddressData = $data['billing'];
            $billingAddressData['address_type'] = 'billing';
            $billingRegion = $billingAddressData['region'];
            $billingCountry = $billingAddressData['country'];
            $billingAddressData['country_id'] = $billingCountry;
            $regionModel = Mage::getModel('directory/region');
            if (is_numeric($billingRegion)){
                $regionModel->load($billingRegion);
            } else {
                $regionModel->loadByCode($billingRegion, $billingCountry);
                if (!$regionModel->getId()){
                    $regionModel->loadByName($billingRegion, $billingCountry);
                }
            }
            $billingAddressData['region'] = $regionModel->getCode();
            $billingAddressData['region_id'] = $regionModel->getId();
            if (is_array($billingAddressData['street'])){
                $billingAddressData['street'] = implode("\n", $billingAddressData['street']);
            }       
            $billingAddress = Mage::getModel('sales/quote_address');;
            $billingAddress->setData($billingAddressData);
            $billingAddress->setQuoteId($quoteId);
            $billingAddress->setQuote($quote);
            $billingAddress->save();
            $quote->setBillingAddress($billingAddress);
            
            //set shipping addresses
            $shippingAddressData = $data['shipping'];
            $shippingAddressData['address_type'] = 'shipping';
            $shippingRegion = $shippingAddressData['region'];
            $shippingCountry = $shippingAddressData['country'];
            $shippingAddressData['country_id'] = $shippingCountry;
            $regionModel = Mage::getModel('directory/region');
            if (is_numeric($shippingRegion)){
                $regionModel->load($shippingRegion);
            } else {
                $regionModel->loadByCode($shippingRegion, $shippingCountry);
                if (!$regionModel->getId()){
                    $regionModel->loadByName($shippingRegion, $shippingCountry);
                }
            }            
                        
            $shippingAddressData['region'] = $regionModel->getCode();
            $shippingAddressData['region_id'] = $regionModel->getId();
            if (is_array($shippingAddressData['street'])){
                $shippingAddressData['street'] = implode("\n", $shippingAddressData['street']);
            }
            $shippingAddress = Mage::getModel('sales/quote_address');
            $shippingAddress->setData($shippingAddressData);
            $shippingAddress->setQuoteId($quoteId);
            $shippingAddress->setQuote($quote);
            
            //set shipping method
            $quoteItems = $quote->getAllVisibleItems();
            foreach ($quoteItems as $quoteItem){
                if (!$quoteItem->getParentItem()){
                    $shippingAddress->addItem($quoteItem);
                }
            }            
            $shippingMethod = $data['shipping_method'];
            $shippingAddress->setShippingMethod($shippingMethod);
            $shippingAddress->setCollectShippingRates(true);
            //shipping rates are collected during quote collectTotals;
            //$shippingAddress->collectShippingRates();
            //$shippingAddress->setCollectShippingRates(false);
            $shippingAddress->save();
            $quote->setShippingAddress($shippingAddress);
            
            //set payment method
            $paymentMethod = $data['payment_method'];
            $method = $paymentMethod['method'];
            //delete existing payments - should not have any
            $quotePaymentsCollection = Mage::getModel('sales/quote_payment')->getCollection();
            $quotePaymentsCollection->addFieldToFilter('quote_id',$quoteId);
            if ($quotePaymentsCollection->getSize()>0){
                foreach ($quotePaymentsCollection as $payment){
                    $payment->delete();
                }
            }
            $payment = Mage::getModel('sales/quote_payment');
            $paymentMethodData = array(
                    'quote_id' => $quoteId,
                    'method' => $method,
                    'cc_type' => $paymentMethod['cc_type'],
                    'cc_owner' => $paymentMethod['cc_owner'],
                    'cc_number_enc' => Mage::helper('core')->encrypt($paymentMethod['cc_number']),
                    'cc_last4' => substr($paymentMethod['cc_number'], -4),
                    'cc_cid_enc' => Mage::helper('core')->encrypt($paymentMethod['cc_cid']),
                    'cc_exp_month' => $paymentMethod['cc_exp_month'],
                    'cc_exp_year' => $paymentMethod['cc_exp_year'],                   
            );
            $payment->setData($paymentMethodData);
            $payment->setQuote($quote);
            $payment->save();
            $quote->setPayment($payment);
            
            $quote->reserveOrderId();
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            $quote->getShippingAddress()->setShippingMethod($shippingMethod);
            $quote->save();
            
            //convert the quote to order
            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();

            $order = $service->getOrder();
            $profiles = $service->getRecurringPaymentProfiles();
            
            $convertedAt = date('Y-m-d H:i:s');
            $quote->setIsActive(0)->setConvertedAt($convertedAt)->save();
            
            Mage::dispatchEvent(
            'checkout_submit_all_after',
            array('order' => $order, 'quote' => $quote, 'recurring_profiles' => $profiles)
            );            
                                        
            $result['error'] = false;
            $result['data'] = $order->getIncrementId();
             
        } catch(Exception $e){
        
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }
        
        return $result;        
    }
    
    /**
     * Get order id
     *
     * @param string $incrementId
     * @return array
     */
    public function getOrder($incrementId)
    {
        $result = array();
    
        try {
    
            $order = Mage::getModel('sales/order')->load($incrementId, 'increment_id');
    
            if ($order->getId()){
    
                $result['error'] = false;
                $result['data'] = $order->getStatus();
    
            } else {
                $result['error'] = true;
                $result['data'] = 'Order not found';
            }
             
        } catch(Exception $e){
    
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }
    
        return $result;
    }
        
	
}