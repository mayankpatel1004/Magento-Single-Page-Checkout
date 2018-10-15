<?php
class Custom_Singlepagecheckout_Block_Singlepagecheckout_Ordercomments extends Mage_Adminhtml_Block_Sales_Order_View
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    public function getOrderComments()
	{
		$intQuoteId = $this->getOrder()->getQuoteId();
		
		return Mage::getModel('singlepagecheckout/onepage')->fnGetOrderComments($intQuoteId);
	}    
}