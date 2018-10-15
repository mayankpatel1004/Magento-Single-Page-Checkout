<?php 

class Custom_Singlepagecheckout_Model_Singlepagecheckout extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('singlepagecheckout/singlepagecheckout');
    }
}