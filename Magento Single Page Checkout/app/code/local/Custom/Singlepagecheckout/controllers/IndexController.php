<?php
class Custom_Singlepagecheckout_IndexController extends Mage_Checkout_Controller_Action
{  
	
    protected $_sectionUpdateFunctions = array( 
        'payment-method'  => '_getPaymentMethodsHtml',
        'shipping-method' => '_getShippingMethodsHtml',
        'review'          => '_getReviewHtml',
    );
	
	protected $_agreements = null;


    /**
     * @return Mage_Checkout_OnepageController
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->_preDispatchValidateCustomer();
        return $this;
    }  
    

    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function getOnepage()
    {
        return Mage::getSingleton('singlepagecheckout/onepage');
    }

    /**
     * Checkout page
     */
    public function indexAction()
    {
        if (!Mage::helper('checkout')->canOnepageCheckout()) {
            Mage::getSingleton('checkout/session')->addError($this->__('Sorry, Onepage Checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
        $quote = $this->getOnepage()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }
        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure'=>true)));
        $this->getOnepage()->initCheckout();
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));
        $this->renderLayout();
    }

    

    public function failureAction()
    {
        $lastQuoteId = $this->getOnepage()->getCheckout()->getLastQuoteId();
        $lastOrderId = $this->getOnepage()->getCheckout()->getLastOrderId();

        if (!$lastQuoteId || !$lastOrderId) {
            $this->_redirect('checkout/cart');
            return;
        }

        $this->loadLayout();
        $this->renderLayout();
    }


    

    /**
     * Save checkout method
     */
    public function saveMethodAction()
    {
        
        if ($this->getRequest()->isPost()) {
            $method = $this->getRequest()->getPost('method');
            $result = $this->getOnepage()->saveCheckoutMethod($method);
			return $result;
            //$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }
	
	public function reviewAction()
	{
		$this->loadLayout();
        $this->renderLayout();
	}
	
	
	/**
     * Get shipping method step html
     *
     * @return string
     */
    protected function _getShippingMethodsHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('singlepagecheckout_onepage_shippingmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }
	
	
	
	public function savebothaddressshippingmethodAction()
	{
		$resBilling = $this->saveBillingAction();
		if(isset($resBilling['error']))
		{			
			echo $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($resBilling));
			exit;
		}
		$postData = $this->getRequest()->getPost('billing', array());
		if(!$postData['use_for_shipping'])
		{
			$resShipping = $this->saveShippingAction();
			if(isset($resShipping['error']))
			{
				echo $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($resShipping));
				exit;
			}
		}
		
	}
	
	public function saveshippingmethodloadreviewAction()
	{
		$resShippingMethod = $this->saveShippingMethodAction();
		if(!$resShippingMethod) {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request'=>$this->getRequest(), 'quote'=>$this->getOnepage()->getQuote()));
        }
		if(isset($resShippingMethod['error']) && isset($resShippingMethod['message']))
		{			
			$resShippingMethod['error'] = trim($resShippingMethod['message']);
			echo $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($resShippingMethod));
			exit;
		}
		
	}

	
	public function saveaddressshippingmethodAction()
	{
		if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost('billing', array());
            $data = $this->_filterPostData($postData);
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!isset($result['error'])) {
                /* check quote for virtual */
                if (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                    $result['goto_section'] = 'shipping_method';
                    

                    $result['allow_sections'] = array('shipping');
                    $result['duplicateBillingInfo'] = 'true';
                } else {
                    $result['goto_section'] = 'shipping';
                }
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
	}

    /**
     * save checkout billing address
     */
    public function saveBillingAction()
    {
       
        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost('billing', array());
            $data = $this->_filterPostData($postData);
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
			try
			{
            	$result = $this->getOnepage()->saveBilling($data, $customerAddressId);
			}
			catch(Exception $e)
			{
				$result['error'] = true;
				$result['error_messages'] = $e->getMessage(); 
			}
			return $result;            
        }
    }

    /**
     * Shipping address save action
     */
    public function saveShippingAction()
    {
        
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping', array());
            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
			try
			{
				
            	$result = $this->getOnepage()->saveShipping($data, $customerAddressId);
			}
			catch(Exception $e)
			{
				$result['error'] = true;
				$result['error_messages'] = $e->getMessage(); 
			}
			return $result;
           
        }
    }

    /**
     * Shipping method save action
     */
    public function saveShippingMethodAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping_method', '');
			try
			{
            	$result = $this->getOnepage()->saveShippingMethod($data);
			}
			catch(Exception $e)
			{
				$result['error'] = true;
				$result['error_messages'] = $e->getMessage(); 
			}			
            return $result;
        }
    }

    /**
     * Save payment ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function savePaymentAction()
    {        
        try {           

            // set payment to quote
            $result = array();
            $data = $this->getRequest()->getPost('payment', array());
			
            $result = $this->getOnepage()->savePayment($data);

            // get section and redirect data
            $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
        } catch (Mage_Payment_Exception $e) {
           
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = true;			
			$result['error_messages'] = $this->__('Unable to set Payment Method.');
        }
		return $result;
        //$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
	
	public function getRequiredAgreementIds()
    {
		$objAgreements = null;
		$arrAgreements = array();
        $objAgreements = Mage::getModel('checkout/agreement')->getCollection()
                    ->addStoreFilter(Mage::app()->getStore()->getId())
                    ->addFieldToFilter('is_active', 1);
		if($objAgreements)
		{
			foreach($objAgreements as $objSpecAgreement)
			{	
				$arrAgreements[] = $objSpecAgreement->getAgreementId();
			}
		}
        return $arrAgreements;
    }
	
	
	
	
    /**
     * Create order action
     */
    public function saveOrderAction()
    {       

        $result = array();
        try {
            if (Mage::getStoreConfig('checkout/singlepagecheckout/enable_terms') && $requiredAgreements = $this->getRequiredAgreementIds()) {
                $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
				
                if ($diff = array_diff($requiredAgreements, $postedAgreements)) {
                    $result['success'] = false;
                    $result['error'] = true;
                    $result['error_messages'] = $this->__('Please agree to all Terms and Conditions before placing the order.');
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }
            if ($data = $this->getRequest()->getPost('payment', false)) {
                $this->getOnepage()->getQuote()->getPayment()->importData($data);
            }
            $this->getOnepage()->saveOrder();
			
			//Save Order Comments if exists.
			if($strOrderComments = $this->getRequest()->getPost('taOrderComments'))
			{
				$this->getOnepage()->fnSaveOrderComments($strOrderComments);
			}

            $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
            $result['success'] = true;
            $result['error']   = false;
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $result['success'] = false;
            $result['error'] = true;
            $result['error_messages'] = $e->getMessage();

            if ($gotoSection = $this->getOnepage()->getCheckout()->getGotoSection()) {
                $result['goto_section'] = $gotoSection;
                $this->getOnepage()->getCheckout()->setGotoSection(null);
            }

            $this->getOnepage()->getQuote()->save();
        } catch (Exception $e) {
			
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $result['success']  = false;
            $result['error']    = true;
            $result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
            $this->getOnepage()->getQuote()->save();
        }
		
		if(isset($result['error']) && trim($result['error']) != '')
		{			
			return $result;
		}
        /**
         * when there is redirect to third party, we don't want to save order yet.
         * we will save the order in return action.
         */
        if (!isset($redirectUrl)) {
			$result['redirectUrl'] = Mage::getBaseUrl().'checkout/onepage/success';
        }
		else
		{
			$result['redirectUrl'] = $redirectUrl;
		}

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }    
	
	public function shippingMethodAction()
    {
        
        $this->loadLayout();
        $this->renderLayout();
    }

	
	public function saveAction()
	{	
		if(!Mage::helper('customer')->isLoggedIn())
		{
			$resCheckoutMethod = $this->saveMethodAction();
			if(isset($resCheckoutMethod['error']))
			{
				echo $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($resCheckoutMethod));
				exit;
			}
		}		
		$resBilling = $this->saveBillingAction();
		if(isset($resBilling['error']))
		{			
			echo $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($resBilling));
			exit;
		}
		$postData = $this->getRequest()->getPost('billing', array());
		if(!$postData['use_for_shipping'])
		{
			$resShipping = $this->saveShippingAction();
			if(isset($resShipping['error']))
			{
				echo $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($resShipping));
				exit;
			}
		}

		$resShippingMethod = $this->saveShippingMethodAction();
		if(!$resShippingMethod) {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request'=>$this->getRequest(), 'quote'=>$this->getOnepage()->getQuote()));
        }
		if(isset($resShippingMethod['error']) && isset($resShippingMethod['message']))
		{			
			$resShippingMethod['error'] = trim($resShippingMethod['message']);
			echo $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($resShippingMethod));
			exit;
		}
		
		$resPayment = $this->savePaymentAction();
		if(isset($resPayment['error']) && $resPayment['error'])
		{
			echo $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($resPayment));
			exit;
		}

		$resOrder = $this->saveOrderAction();		
		if(isset($resOrder['error']) && $resOrder['error'])
		{
			echo $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($resOrder));
			exit;
		}	
			
	}   
	/**
     * Filtering posted data. Converting localized data if needed
     *
     * @param array
     * @return array
     */
    protected function _filterPostData($data)
    {
        $data = $this->_filterDates($data, array('dob'));
        return $data;
    }
}