<?php

class Custom_Singlepagecheckout_Block_Adminhtml_Singlepagecheckout_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('singlepagecheckout_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('singlepagecheckout')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('singlepagecheckout')->__('Item Information'),
          'title'     => Mage::helper('singlepagecheckout')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('singlepagecheckout/adminhtml_singlepagecheckout_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}