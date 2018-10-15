<?php

class Custom_Singlepagecheckout_Model_Mysql4_Singlepagecheckout extends Mage_Core_Model_Mysql4_Abstract
{ 
    public function _construct()
    {    
        // Note that the singlepagecheckout_id refers to the key field in your database table.
        $this->_init('singlepagecheckout/singlepagecheckout', 'singlepagecheckout_id');
    }
}