<?php

class Custom_Singlepagecheckout_Model_Mysql4_Singlepagecheckout_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('singlepagecheckout/singlepagecheckout');
    }
}