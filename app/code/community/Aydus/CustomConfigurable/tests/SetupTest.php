<?php

/**
 * Setup test
 *
 * @category    Aydus
 * @package     Aydus_CustomConfigurable
 * @author      Aydus <davidt@aydus.com>
 */

include('bootstrap.php');

class SetupTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        
    }

    public function testSetup() {
    	
        $installed = Mage::getResourceModel('aydus_customconfigurable/setup')->isInstalled();
        
        $this->assertTrue($installed);
    }

}
