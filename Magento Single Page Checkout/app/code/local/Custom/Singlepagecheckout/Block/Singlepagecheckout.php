<?php
class Custom_Singlepagecheckout_Block_Singlepagecheckout extends Custom_Singlepagecheckout_Block_Singlepagecheckout_Abstract
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getSteps()
    {
        $steps = array();
		
       /* if (!$this->isCustomerLoggedIn()) {
            $steps['login'] = $this->getCheckout()->getStepData('login');
        }*/

        $stepCodes = array('billing', 'shipping', 'shipping_method', 'payment', 'review');

        foreach ($stepCodes as $step) {
            $steps[$step] = $this->getCheckout()->getStepData($step);
        }
        return $steps;
    }

    public function getActiveStep()
    {
		return 'billing';
        //return $this->isCustomerLoggedIn() ? 'billing' : 'login';
    }
	public function loginStepExists()
	{
		if(in_array('login',$this->getSteps()))
		{
			return true;
		}
		return false;
	}
	public function getCheckoutTitle()
	{
		return Mage::getStoreConfig('checkout/singlepagecheckout/checkout_title');
	}
	public function getCheckoutDescription()
	{
		return Mage::getStoreConfig('checkout/singlepagecheckout/checkout_description');
	}
	public function canShowOrderComments()
	{
		return Mage::getStoreConfig('checkout/singlepagecheckout/enable_comments');
	}
}