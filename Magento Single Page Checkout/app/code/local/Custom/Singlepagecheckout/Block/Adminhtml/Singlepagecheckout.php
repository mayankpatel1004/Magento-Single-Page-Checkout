<?php 
class Custom_Singlepagecheckout_Block_Adminhtml_Singlepagecheckout extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_singlepagecheckout';
    $this->_blockGroup = 'singlepagecheckout';
    $this->_headerText = Mage::helper('singlepagecheckout')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('singlepagecheckout')->__('Add Item');
    parent::__construct();
  }
}