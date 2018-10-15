<?php

class Custom_Singlepagecheckout_Block_Adminhtml_Singlepagecheckout_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'singlepagecheckout';
        $this->_controller = 'adminhtml_singlepagecheckout';
        
        $this->_updateButton('save', 'label', Mage::helper('singlepagecheckout')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('singlepagecheckout')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('singlepagecheckout_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'singlepagecheckout_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'singlepagecheckout_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('singlepagecheckout_data') && Mage::registry('singlepagecheckout_data')->getId() ) {
            return Mage::helper('singlepagecheckout')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('singlepagecheckout_data')->getTitle()));
        } else {
            return Mage::helper('singlepagecheckout')->__('Add Item');
        }
    }
}