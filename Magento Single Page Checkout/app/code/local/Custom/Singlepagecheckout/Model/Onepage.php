<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * One page checkout processing model
 */
class Custom_Singlepagecheckout_Model_Onepage extends Mage_Checkout_Model_Type_Onepage
{   
    /**
     * Checkout types: Checkout as Guest, Register, Logged In Customer
     */
    const METHOD_GUEST    = 'guest';
    const METHOD_REGISTER = 'register';
    const METHOD_CUSTOMER = 'customer';

    /**
     * Error message of "customer already exists"
     *
     * @var string
     */ 
    private $_customerEmailExistsMessage = '';

    /**
     * @var Mage_Customer_Model_Session
     */
    protected $_customerSession;

    /**
     * @var Mage_Checkout_Model_Session
     */
    protected $_checkoutSession;

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    /**
     * @var Mage_Checkout_Helper_Data
     */
    protected $_helper;
	
	public $_orderCommentId = null;	

    /**
     * Class constructor
     * Set customer already exists message
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('checkout');
        $this->_customerEmailExistsMessage = $this->_helper->__('There is already a customer registered using this email address. Please login using this email address or enter a different email address to register your account.');
        $this->_checkoutSession = Mage::getSingleton('checkout/session');
        $this->_quote = $this->_checkoutSession->getQuote();
        $this->_customerSession = Mage::getSingleton('customer/session');
    }

    /**
     * Get frontend checkout session object
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return $this->_checkoutSession;
    }

    /**
     * Quote object getter
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->_quote;
    }

    /**
     * Get customer session object
     *
     * @return Mage_Customer_Model_Session
     */
    public function getCustomerSession()
    {
        return $this->_customerSession;
    }

    /**
     * Initialize quote state to be valid for one page checkout
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function initCheckout()
    {
        $checkout = $this->getCheckout();
        $customerSession = $this->getCustomerSession();
        if (is_array($checkout->getStepData())) {
            foreach ($checkout->getStepData() as $step=>$data) {
                if (!($step==='login' || $customerSession->isLoggedIn() && $step==='billing')) {
                    $checkout->setStepData($step, 'allow', false);
                }
            }
        }

        /**
         * Reset multishipping flag before any manipulations with quote address
         * addAddress method for quote object related on this flag
         */
        if ($this->getQuote()->getIsMultiShipping()) {
            $this->getQuote()->setIsMultiShipping(false);
            $this->getQuote()->save();
        }

        /*
        * want to laod the correct customer information by assiging to address
        * instead of just loading from sales/quote_address
        */
        $customer = $customerSession->getCustomer();
        if ($customer) {
            $this->getQuote()->assignCustomer($customer);
        }
        return $this;
    }
    

    /**
     * Save billing address information to quote
     * This method is called by One Page Checkout JS (AJAX) while saving the billing information.
     *
     * @param   array $data
     * @param   int $customerAddressId
     * @return  Mage_Checkout_Model_Type_Onepage
     */
    public function saveBilling($data, $customerAddressId)
    {
        if (empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data'));
        }

        $address = $this->getQuote()->getBillingAddress();
        if (!empty($customerAddressId)) {
            $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
            if ($customerAddress->getId()) {
                if ($customerAddress->getCustomerId() != $this->getQuote()->getCustomerId()) {
                    return array('error' => 1,
                        'message' => $this->_helper->__('Customer Address is not valid.')
                    );
                }
                $address->importCustomerAddress($customerAddress);
            }
        } else {
            unset($data['address_id']);
            $address->addData($data);
            //$address->setId(null);
        }

        if (($validateRes = $this->validateAddress($address))!==true) {
            return array('error' => 1, 'message' => $validateRes);
        }

        if (!$this->getQuote()->getCustomerId() && self::METHOD_REGISTER == $this->getQuote()->getCheckoutMethod()) {
            if ($this->_customerEmailExists($address->getEmail(), Mage::app()->getWebsite()->getId())) {
                return array('error' => 1, 'message' => $this->_customerEmailExistsMessage);
            }
        }

        $address->implodeStreetAddress();
	
        if (!$this->getQuote()->isVirtual()) {
            /**
             * Billing address using otions
             */
            $usingCase = isset($data['use_for_shipping']) ? (int) $data['use_for_shipping'] : 0;
            switch($usingCase) {
                case 0:
                    $shipping = $this->getQuote()->getShippingAddress();
                    $shipping->setSameAsBilling(0);
                    break;
                case 1:
                    $billing = clone $address;
                    $billing->unsAddressId()->unsAddressType();
                    $shipping = $this->getQuote()->getShippingAddress();
                    $shippingMethod = $shipping->getShippingMethod();
                    $shipping->addData($billing->getData())
                        ->setSameAsBilling(1)
                        ->setShippingMethod($shippingMethod)
                        ->setCollectShippingRates(true);
                    $this->getCheckout()->setStepData('shipping', 'complete', true);
                    break;
            }
        }
        if (true !== $result = $this->_processValidateCustomer($address)) {
            return $result;
        }
        $this->getQuote()->collectTotals();
        $this->getQuote()->save();
        $this->getCheckout()
            ->setStepData('billing', 'allow', true)
            ->setStepData('billing', 'complete', true)
            ->setStepData('shipping', 'allow', true);

        return array();
    }

    /**
     * Validate customer data and set some its data for further usage in quote
     * Will return either true or array with error messages
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return true|array
     */
    protected function _processValidateCustomer(Mage_Sales_Model_Quote_Address $address)
    {
        // set customer date of birth for further usage
        $dob = '';
        if ($address->getDob()) {
            $dob = Mage::app()->getLocale()->date($address->getDob(), null, null, false)->toString('yyyy-MM-dd');
            $this->getQuote()->setCustomerDob($dob);
        }

        // set customer tax/vat number for further usage
        if ($address->getTaxvat()) {
            $this->getQuote()->setCustomerTaxvat($address->getTaxvat());
        }

        // set customer gender for further usage
        if ($address->getGender()) {
            $this->getQuote()->setCustomerGender($address->getGender());
        }

        // invoke customer model, if it is registering
        if (self::METHOD_REGISTER == $this->getQuote()->getCheckoutMethod()) {
            // set customer password hash for further usage
            $customer = Mage::getModel('customer/customer');
            $this->getQuote()->setPasswordHash($customer->encryptPassword($address->getCustomerPassword()));

            // validate customer
            foreach (array(
                'firstname'    => 'firstname',
                'lastname'     => 'lastname',
                'email'        => 'email',
                'password'     => 'customer_password',
                'confirmation' => 'confirm_password',
                'taxvat'       => 'taxvat',
                'gender'       => 'gender',
            ) as $key => $dataKey) {
                $customer->setData($key, $address->getData($dataKey));
            }
            if ($dob) {
                $customer->setDob($dob);
            }
            $validationResult = $customer->validate();
            if (true !== $validationResult && is_array($validationResult)) {
                return array(
                    'error'   => -1,
                    'message' => implode(', ', $validationResult)
                );
            }
        } elseif(self::METHOD_GUEST == $this->getQuote()->getCheckoutMethod()) {
            $email = $address->getData('email');
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                return array(
                    'error'   => -1,
                    'message' => $this->_helper->__('Invalid email address "%s"', $email)
                );
            }
        }

        return true;
    }

    /**
     * Save checkout shipping address
     *
     * @param   array $data
     * @param   int $customerAddressId
     * @return  Mage_Checkout_Model_Type_Onepage
     */
    public function saveShipping($data, $customerAddressId)
    {
        if (empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data'));
        }
        $address = $this->getQuote()->getShippingAddress();

        if (!empty($customerAddressId)) {
            $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
            if ($customerAddress->getId()) {
                if ($customerAddress->getCustomerId() != $this->getQuote()->getCustomerId()) {
                    return array('error' => 1,
                        'message' => $this->_helper->__('Customer Address is not valid.')
                    );
                }
                $address->importCustomerAddress($customerAddress);
            }
        } else {
            unset($data['address_id']);
            $address->addData($data);
        }
        $address->implodeStreetAddress();
        $address->setCollectShippingRates(true);

        if (($validateRes = $this->validateAddress($address))!==true) {
            return array('error' => 1, 'message' => $validateRes);
        }

        $this->getQuote()->collectTotals()->save();

        $this->getCheckout()
            ->setStepData('shipping', 'complete', true)
            ->setStepData('shipping_method', 'allow', true);

        return array();
    } 
	/**
     * Validate address attribute values
     *
     * @return bool
     */
    public function validateAddress($address)
    {
        $errors = array();
        $helper = Mage::helper('customer');
        $address->implodeStreetAddress();
        if (!Zend_Validate::is($address->getFirstname(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter first name.');
        }

        if (!Zend_Validate::is($address->getLastname(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter last name.');
        }

        if (!Zend_Validate::is($address->getStreet(1), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter street.');
        }

        if (!Mage::getStoreConfig('checkout/singlepagecheckout/exclude_city') && !Zend_Validate::is($address->getCity(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter city.');
        }

        if (!Mage::getStoreConfig('checkout/singlepagecheckout/exclude_telephone') && !Zend_Validate::is($address->getTelephone(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter telephone.');
        }

        $_havingOptionalZip = Mage::helper('directory')->getCountriesWithOptionalZip();
        if (!Mage::getStoreConfig('checkout/singlepagecheckout/exclude_zip') && !in_array($address->getCountryId(), $_havingOptionalZip) && !Zend_Validate::is($address->getPostcode(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter zip/postal code.');
        }

        if (!Zend_Validate::is($address->getCountryId(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter country.');
        }

        if (!Mage::getStoreConfig('checkout/singlepagecheckout/exclude_region') && $address->getCountryModel()->getRegionCollection()->getSize()
               && !Zend_Validate::is($address->getRegionId(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter state/province.');
        }

        if (empty($errors) || $address->getShouldIgnoreValidation()) {
            return true;
        }
        return $errors;
    }  
	/**
     * Create order based on checkout type. Create customer if necessary.
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function saveOrder()
    {
        $this->validate();
        $isNewCustomer = false;
        switch ($this->getCheckoutMehod()) {
            case self::METHOD_GUEST:
                $this->_prepareGuestQuote();
                break;
            case self::METHOD_REGISTER:
                $this->_prepareNewCustomerQuote();
                $isNewCustomer = true;
                break;
            default:
                $this->_prepareCustomerQuote();
                break;
        }

        //$service = Mage::getModel('sales/service_quote', $this->getQuote());
		$service = Mage::getModel('singlepagecheckout/service_quote', $this->getQuote());
		
        $order = $service->submit();

        if ($isNewCustomer) {
            try {
                $this->_involveNewCustomer();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        Mage::dispatchEvent('checkout_type_onepage_save_order_after', array('order'=>$order, 'quote'=>$this->getQuote()));

        /**
         * a flag to set that there will be redirect to third party after confirmation
         * eg: paypal standard ipn
         */
        $redirectUrl = $this->getQuote()->getPayment()->getOrderPlaceRedirectUrl();
        /**
         * we only want to send to customer about new order when there is no redirect to third party
         */
        if(!$redirectUrl){
            try {
                $order->sendNewOrderEmail();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        $this->getCheckout()->setLastQuoteId($this->getQuote()->getId())
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setRedirectUrl($redirectUrl)
            ->setLastSuccessQuoteId($this->getQuote()->getId());
        return $this;
    }
	/**
	* Add/Update Order comments pertaining to specific order.
	**/
	public function fnSaveOrderComments($strComments)
	{
		$objData = Mage::getSingleton('core/resource')->getConnection('core_write');
		$intQuoteId = $this->getQuote()->getId();
		if(!$this->fnCheckOrderCommentsExists($intQuoteId))
		{
			//Insert Order Comments.			
			$sqlQuery = " INSERT INTO order_comments SET quote_id = '".$intQuoteId."', comments = '".$strComments."', status=1,created_at='".date('Y-m-d')."', updated_at = '".date('Y-m-d')."'";
			$objData->query($sqlQuery);
		}
		else
		{
			//Update Order Comments.
			if($this->_orderCommentId != null)
			{
				$sqlQuery = " UPDATE order_comments SET comments = '".$strComments."' WHERE id = '".$this->_orderCommentId."'";
				$objData->query($sqlQuery);
			}
			else
			{
				$sqlQuery = " INSERT INTO order_comments SET quote_id = '".$intQuoteId."', comments = '".$strComments."', status=1,created_at='".date('Y-m-d')."', updated_at = '".date('Y-m-d')."'";
				$objData->query($sqlQuery);
			}
		}
	}
	
	public function fnCheckOrderCommentsExists($intQuoteId)
	{		
		$objData = Mage::getSingleton('core/resource')->getConnection('core_write');
		$sqlQuery = " SELECT id FROM order_comments WHERE quote_id = '".$intQuoteId."'";
		$resOrderComment = $objData->query($sqlQuery);
		$arrOrderComment = $resOrderComment->fetch(PDO::FETCH_ASSOC);
		if(count($arrOrderComment))
		{
			$this->_orderCommentId = $arrOrderComment['id'];
			return true;
		}
		return false;
	}
	public function fnGetOrderComments($intQuoteId)
	{		
		$objData = Mage::getSingleton('core/resource')->getConnection('core_write');
		$sqlQuery = " SELECT comments FROM order_comments WHERE quote_id = '".$intQuoteId."'";
		$resOrderComment = $objData->query($sqlQuery);
		$arrOrderComment = $resOrderComment->fetch(PDO::FETCH_ASSOC);
		if(isset($arrOrderComment['comments']))
		{
			return nl2br(trim($arrOrderComment['comments']));
		}
		return '';
	}
}