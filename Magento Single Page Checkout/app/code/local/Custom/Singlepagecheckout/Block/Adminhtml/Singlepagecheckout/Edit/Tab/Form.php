<?php

class Custom_Singlepagecheckout_Block_Adminhtml_Singlepagecheckout_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('singlepagecheckout_form', array('legend'=>Mage::helper('singlepagecheckout')->__('Item information')));
     
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('singlepagecheckout')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('singlepagecheckout')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('singlepagecheckout')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('singlepagecheckout')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('singlepagecheckout')->__('Disabled'),
              ),
          ),
      ));
     
      $fieldset->addField('content', 'editor', array(
          'name'      => 'content',
          'label'     => Mage::helper('singlepagecheckout')->__('Content'),
          'title'     => Mage::helper('singlepagecheckout')->__('Content'),
          'style'     => 'width:700px; height:500px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getSinglepagecheckoutData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getSinglepagecheckoutData());
          Mage::getSingleton('adminhtml/session')->setSinglepagecheckoutData(null);
      } elseif ( Mage::registry('singlepagecheckout_data') ) {
          $form->setValues(Mage::registry('singlepagecheckout_data')->getData());
      }
      return parent::_prepareForm();
  }
}