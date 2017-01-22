<?php
/**
 * @package     Joomla - > Site and Administrator payment info
 * @subpackage  com_Jshopping
 * @subpackage 	trangell_Mellat
 * @copyright   trangell team => https://trangell.com
 * @copyright   Copyright (C) 20016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die();

if (!class_exists ('checkHack')) {
    require_once dirname(__FILE__). '/trangell_inputcheck.php';
}


class pm_trangellmellat extends PaymentRoot{
    
    function showPaymentForm($params, $pmconfigs){	
        include(dirname(__FILE__)."/paymentform.php");
    }

	//function call in admin
	function showAdminFormParams($params){
		$array_params = array('transaction_end_status', 'transaction_pending_status', 'transaction_failed_status');
		foreach ($array_params as $key){
			if (!isset($params[$key])) $params[$key] = '';
		} 
		$orders = JSFactory::getModel('orders', 'JshoppingModel'); //admin model
		include(dirname(__FILE__)."/adminparamsform.php");
	}

	function showEndForm($pmconfigs, $order){
		$app	= JFactory::getApplication();
        $uri = JURI::getInstance(); 
        $pm_method = $this->getPmMethod();       
        $liveurlhost = $uri->toString(array("scheme",'host', 'port'));
        $return = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step7&act=return&js_paymentclass=".$pm_method->payment_class).'&orderId='. $order->order_id;		
		$notify_url2 = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step2&act=notify&js_paymentclass=".$pm_method->payment_class."&no_lang=1");	
		//====================================================== 
		if (!isset($MerchantId)) {	
			$app->redirect($notify_url2, '<h2>لطفا تنظیمات درگاه ملت را بررسی کنید</h2>', $msgType='Error'); 
		}
		
		$dateTime = JFactory::getDate();
			
		$fields = array(
			'terminalId' => $pmconfigs['melatterminalId'],
			'userName' => $pmconfigs['melatuser'],
			'userPassword' => $pmconfigs['melatpass'],
			'orderId' => time(),
			'amount' => $this->fixOrderTotal($order),
			'localDate' => $dateTime->format('Ymd'),
			'localTime' => $dateTime->format('His'),
			'additionalData' => '',
			'callBackUrl' => $return,
			'payerId' => 0,
			);
			
		try {
			$soap = new SoapClient('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
			$response = $soap->bpPayRequest($fields);
			
			$response = explode(',', $response->return);
			if ($response[0] != '0') { // if transaction fail
				$msg = $this->getGateMsg($response[0]); 
				saveToLog("payment.log", "Status failed. Order ID ".$order->order_id.". message: ".$msg);
				$app->redirect($notify_url2, '<h2>'.$msg.'</h2>', $msgType='Error'); 
			}
			else { // if success
				$refId = $response[1];
				echo '
					<script>
						var form = document.createElement("form");
						form.setAttribute("method", "POST");
						form.setAttribute("action", "https://bpm.shaparak.ir/pgwchannel/startpay.mellat");
						form.setAttribute("target", "_self");

						var hiddenField = document.createElement("input");
						hiddenField.setAttribute("name", "RefId");
						hiddenField.setAttribute("value", "'.$refId.'");

						form.appendChild(hiddenField);

						document.body.appendChild(form);
						form.submit();
						document.body.removeChild(form);
					</script>'
				;
			}
		}
		catch(\SoapFault $e) {
			$msg= $this->getGateMsg('error');
			saveToLog("payment.log", "Status failed. Order ID ".$order->order_id.". message: ".$msg);
			$app->redirect($notify_url2, '<h2>'.$msg.'</h2>', $msgType='Error'); 
		}

	}
    
		function checkTransaction($pmconfigs, $order, $act){
			$app	= JFactory::getApplication();
			$jinput = $app->input;
			$uri = JURI::getInstance(); 
			$pm_method = $this->getPmMethod();       
			$liveurlhost = $uri->toString(array("scheme",'host', 'port'));
			$cancel_return = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step7&act=cancel&js_paymentclass=".$pm_method->payment_class).'&orderId='. $order->order_id;	
			// $Mobile = $order->phone;
            //==================================================================
		
			$ResCode = $jinput->post->get('ResCode', '1', 'INT'); 
			$SaleOrderId = $jinput->post->get('SaleOrderId', '1', 'INT'); 
			$SaleReferenceId = $jinput->post->get('SaleReferenceId', '1', 'INT'); 
			$RefId = $jinput->post->get('RefId', 'empty', 'STRING'); 
			if (checkHack::strip($RefId) != $RefId )
				$RefId = "illegal";
			$CardNumber = $jinput->post->get('CardHolderPan', 'empty', 'STRING'); 
			if (checkHack::strip($CardNumber) != $CardNumber )
				$CardNumber = "illegal";
			
			if (
				checkHack::checkNum($ResCode) &&
				checkHack::checkNum($SaleOrderId) &&
				checkHack::checkNum($SaleReferenceId) 
			){
				if ($ResCode != '0') {
					$msg= $this->getGateMsg($ResCode); 
					saveToLog("payment.log", "Status Cancelled. Order ID ".$order->order_id.". message: ".$msg );
					$app->redirect($cancel_return, '<h2>'.$msg.'</h2>' , $msgType='Error'); 
				}
				else {
					$fields = array(
					'terminalId' => $pmconfigs['melatterminalId'],
					'userName' => $pmconfigs['melatuser'],
					'userPassword' => $pmconfigs['melatpass'],
					'orderId' => $SaleOrderId, 
					'saleOrderId' =>  $SaleOrderId, 
					'saleReferenceId' => $SaleReferenceId
					);
					try {
						$soap = new SoapClient('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
						$response = $soap->bpVerifyRequest($fields);

						if ($response->return != '0') {
							$msg= $this->getGateMsg($response->return); 
							saveToLog("payment.log", "Status failed. Order ID ".$order->order_id.". message: ".$msg );
							$app->redirect($cancel_return, '<h2>'.$msg.'</h2>', $msgType='Error'); 
						}
						else {	
							$response = $soap->bpSettleRequest($fields);
							if ($response->return == '0' || $response->return == '45') {
								$msg= $this->getGateMsg($response->return); 
								$message = "کد پیگیری".$SaleReferenceId."<br>" ."شماره سفارش ".$order->order_id;
								$app->enqueueMessage($message, 'message');
								saveToLog("payment.log", "Status Complete. Order ID ".$order->order_id.". message: ".$msg . " tracking_code: " . $SaleReferenceId);
								return array(1, "");
							}
							else {
								$msg= $this->getGateMsg($response->return); 
								saveToLog("payment.log", "Status failed. Order ID ".$order->order_id.". message: ".$msg );
								$app->redirect($cancel_return, '<h2>'.$msg.'</h2>', $msgType='Error'); 
							}
						}
					}
					catch(\SoapFault $e)  {
						$msg= $this->getGateMsg('error'); 
						saveToLog("payment.log", "Status failed. Order ID ".$order->order_id.". message: ".$msg );
						$app->redirect($cancel_return, '<h2>'.$msg.'</h2>' , $msgType='Error'); 
					}
				}
			}
			else {
				$msg= $this->getGateMsg('hck2'); 
				saveToLog("payment.log", "Status failed. Order ID ".$order->order_id.". message: ".$msg );
				$app->redirect($cancel_return, '<h2>'.$msg.'</h2>' , $msgType='Error'); 
			}
	}


    function getUrlParams($pmconfigs){
		$app	= JFactory::getApplication();
		$jinput = $app->input;
		$oId = $jinput->get->get('orderId', '0', 'INT');
        $params = array(); 
        $params['order_id'] = $oId;
        $params['hash'] = "";
        $params['checkHash'] = 0;
        $params['checkReturnParams'] = 0;
		return $params;
    }
    
	function fixOrderTotal($order){
        $total = $order->order_total;
        if ($order->currency_code_iso=='HUF'){
            $total = round($total);
        }else{
            $total = number_format($total, 2, '.', '');
        }
    return $total;
    }

    public function getGateMsg ($msgId) {
		switch($msgId){
			case '0': $out =  'تراکنش با موفقیت انجام شد'; break;
			case '11': $out =  'شماره کارت نامعتبر است'; break;
			case '12': $out =  'موجودی کافی نیست'; break;
			case '13': $out =  'رمز نادرست است'; break;
			case '14': $out =  'تعداد دفعات وارد کردن رمز بیش از حد مجاز است'; break;
			case '15': $out =  'کارت نامعتبر است'; break;
			case '16': $out =  'دفعات برداشت وجه بیش از حد مجاز است'; break;
			case '17': $out =  'کاربر از انجام تراکنش منصرف شده است'; break;
			case '18': $out =  'تاریخ انقضای کارت گذشته است'; break;
			case '19': $out =  'مبلغ برداشت وجه بیش از حد مجاز است'; break;
			case '21': $out =  'پذیرنده نامعتبر است'; break;
			case '23': $out =  'خطای امنیتی رخ داده است'; break;
			case '24': $out =  'اطلاعات کاربری پذیرنده نادرست است'; break;
			case '25': $out =  'مبلغ نامتعبر است'; break;
			case '31': $out =  'پاسخ نامتعبر است'; break;
			case '32': $out =  'فرمت اطلاعات وارد شده صحیح نمی باشد'; break;
			case '33': $out =  'حساب نامعتبر است'; break;
			case '34': $out =  'خطای سیستمی'; break;
			case '35': $out =  'تاریخ نامعتبر است'; break;
			case '41': $out =  'شماره درخواست تکراری است'; break;
			case '42': $out =  'تراکنش Sale‌ یافت نشد'; break;
			case '43': $out =  'قبلا درخواست Verify‌ داده شده است'; break;
			case '44': $out =  'درخواست Verify‌ یافت نشد'; break;
			case '45': $out =  'تراکنش Settle‌ شده است'; break;
			case '46': $out =  'تراکنش Settle‌ نشده است'; break;
			case '47': $out =  'تراکنش  Settle یافت نشد'; break;
			case '48': $out =  'تراکنش Reverse شده است'; break;
			case '49': $out =  'تراکنش Refund یافت نشد'; break;
			case '51': $out =  'تراکنش تکراری است'; break;
			case '54': $out =  'تراکنش مرجع موجود نیست'; break;
			case '55': $out =  'تراکنش نامعتبر است'; break;
			case '61': $out =  'خطا در واریز'; break;
			case '111': $out =  'صادر کننده کارت نامعتبر است'; break;
			case '112': $out =  'خطا سوییج صادر کننده کارت'; break;
			case '113': $out =  'پاسخی از صادر کننده کارت دریافت نشد'; break;
			case '114': $out =  'دارنده کارت مجاز به انجام این تراکنش نیست'; break;
			case '412': $out =  'شناسه قبض نادرست است'; break;
			case '413': $out =  'شناسه پرداخت نادرست است'; break;
			case '414': $out =  'سازمان صادر کننده قبض نادرست است'; break;
			case '415': $out =  'زمان جلسه کاری به پایان رسیده است'; break;
			case '416': $out =  'خطا در ثبت اطلاعات'; break;
			case '417': $out =  'شناسه پرداخت کننده نامعتبر است'; break;
			case '418': $out =  'اشکال در تعریف اطلاعات مشتری'; break;
			case '419': $out =  'تعداد دفعات ورود اطلاعات از حد مجاز گذشته است'; break;
			case '421': $out =  'IP‌ نامعتبر است';  break;
			case 'error': $out ='خطا غیر منتظره رخ داده است';break;
			case 'hck2': $out = 'لطفا از کاراکترهای مجاز استفاده کنید';break;
            default: $out ='خطا غیر منتظره رخ داده است';break;
		}
		return $out;
	}
}
