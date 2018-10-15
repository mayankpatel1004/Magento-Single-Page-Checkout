var placeOrderForm = new VarienForm('singlepagecheckout-form');
function fnPlaceOrder()
{	
	if(placeOrderForm.validator.validate())
	{
		$('review-please-wait').show();	
		strOrderSaveUrl = $('hdnBaseUrl').value+'singlepagecheckout/index/save';
		strParam = fnFilterPlaceOrder(Form.serialize(document.frmPlaceOrder));
		var request = new Ajax.Request(
			strOrderSaveUrl,
			{
				method: 'post',
				onSuccess: function(transport){
					$('review-please-wait').hide();		
					 try{
							response = eval('(' + transport.responseText + ')');
						}
						catch (e) {
							response = {};
						}							
						if(response.message)
						{
							alert(response.message);
						}
						else if(response.error_messages)
						{
							alert(response.error_messages);
						}
						else if(response.redirectUrl)
						{
							location.href = response.redirectUrl;
						}
				},
				onFailure: function(error){
					alert('Error:'+error);
				},
				parameters: strParam,
			}
		);
	}
}	
function fnFilterPlaceOrder(formData)
{	
	arrPostData = formData.split('&');
	arrFilteredPostData = new Array();
	for(intK=0;intK<arrPostData.length;intK++)
	{
		strSpecData = decodeURIComponent(arrPostData[intK]);
		arrSpecData = strSpecData.split('=');
		if(arrSpecData[0].match(/payment/i) && arrSpecData[1] != '')
		{
			arrFilteredPostData.push(arrPostData[intK]);
		}
		else if(arrSpecData[0].match(/payment/i) && arrSpecData[1] == '')
		{
			continue;
		}
		else
		{
			arrFilteredPostData.push(arrPostData[intK]);
		}
	}
	strPostReadyData = arrFilteredPostData.join('&');
	return strPostReadyData;
}
function fnToggleShippingOption()
{
	boolChecked = $('chkSameAsBilling').checked;
	if(!boolChecked)
	{
		$('opc-shipping').show();
		$('billing:use_for_shipping').value = 0;
	}
	else
	{
		$('opc-shipping').hide();
		$('billing:use_for_shipping').value = 1;
	}
}
//Save Billing/Shipping and Update Shipping Method.
function fnSaveBillingandLoadShippingMethod()
{
	frmPlaceOrder = document.frmPlaceOrder;
	arrControls = frmPlaceOrder.getElementsByTagName("*");
	strParam = 'billing=1';
	for(intI=0;intI<arrControls.length;intI++)
	{
		if(arrControls[intI].id.search(/billing:/i) != -1)	
		{			
			strParam += '&'+arrControls[intI].name+'='+$(arrControls[intI].id).value;
		}
	}
	if($('chkSameAsBilling') && !$('chkSameAsBilling').checked)
	{
		for(intI=0;intI<arrControls.length;intI++)
		{
			if(arrControls[intI].id.search(/shipping:/i) != -1)	
			{			
				strParam += '&'+arrControls[intI].name+'='+$(arrControls[intI].id).value;
			}
		}	
		strShippingMethodUrl = $('hdnBaseUrl').value+'singlepagecheckout/index/savebothaddressshippingmethod';
	}
	else
	{
		strShippingMethodUrl = $('hdnBaseUrl').value+'singlepagecheckout/index/saveaddressshippingmethod';
	}	
	boolAjax = true;
	if($('chkSameAsBilling') && !$('chkSameAsBilling').checked)
	{
		if($('shipping:telephone') && $('shipping:telephone').value == '')	
		{
			boolAjax = false;	
		}
		else if($('shipping:country_id') && $('shipping:country_id').value == '')	
		{
			boolAjax = false;	
		}
	}
	else
	{
		if($('billing:country_id') && $('billing:country_id').value == '')	
		{
			boolAjax = false;	
		}	
	}	
	
	if(boolAjax)
	{
		$('checkout-shipping-method-load').update('');
		$('shipping-method-please-wait').show();
		var request = new Ajax.Request(
				strShippingMethodUrl,
				{
					method: 'post',
					onSuccess: function(transport){
						 try{
								response = eval('(' + transport.responseText + ')');
							}
							catch (e) {
								response = {};
							}								
							if(response.message)
							{
								$('shipping-method-please-wait').hide();
								alert(response.message);
							}
							else if(response.error_messages)
							{
								$('shipping-method-please-wait').hide();
								alert(response.error_messages);
							}
							else
							{
								fnLoadShippingMethod();
							}
							
					},
					onFailure: function(error){
						alert('Error:'+error);
					},
					parameters: strParam,
				}
			);
	}
}
function fnToggleTerms(intId)
{
	$('agreement-'+intId).toggle();
}
function fnLoadShippingMethod()
{	
	strReviewUrl = $('hdnBaseUrl').value+'singlepagecheckout/index/shippingmethod';
	var request = new Ajax.Request(
			strReviewUrl,
			{
				method: 'post',
				onSuccess: function(transport){
					 try{
							response = transport.responseText;
							$('shipping-method-please-wait').hide();
							$('checkout-shipping-method-load').update(response);
						}
						catch (e) {
							response = '';
						}	
						
				},
				onFailure: function(error){
					alert('Error:'+error);
				},
			}
		);		
}
function fnUpdateCartTotals(id)
{
	
	if($(id).checked)
	{
		strParam = 'shipping_method='+$(id).value;	
		$('checkout-review-load').update('');
		$('review-please-wait1').show();
		strReviewUrl = $('hdnBaseUrl').value+'singlepagecheckout/index/saveshippingmethodloadreview';
		var request = new Ajax.Request(
				strReviewUrl,
				{
					method: 'post',
					onSuccess: function(transport){
						 try{
								response = eval('(' + transport.responseText + ')');
							}
							catch (e) {
								response = {};
							}	
							$('shipping-method-please-wait').hide();
							if(response.message)
							{
								alert(response.message);
							}
							else if(response.error_messages)
							{
								alert(response.error_messages);
							}
							fnUpdateReview();
							
					},
					onFailure: function(error){
						alert('Error:'+error);
					},
					parameters: strParam,
				}
			);
	}
}
function fnUpdateReview()
{
		strReviewUrl = $('hdnBaseUrl').value+'singlepagecheckout/index/review';
		var request = new Ajax.Request(
				strReviewUrl,
				{
					method: 'post',
					onSuccess: function(transport){
						 try{
								response = transport.responseText;
								$('review-please-wait1').hide();
								$('checkout-review-load').update(response);
							}
							catch (e) {
								response = '';
							}	
					},
					onFailure: function(error){
						alert('Error:'+error);
					},
				}
			);
}