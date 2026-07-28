<?php

class ControllerExtensionPaymentBkashAutoPayment extends Controller {

	public function index() {

		 $base_url = $this->config->get('payment_BkashAutoPayment_base_url'); 
		 $app_key = $this->config->get('payment_BkashAutoPayment_app_key'); 
		 $app_secret = $this->config->get('payment_BkashAutoPayment_app_secret'); 
		 $username = $this->config->get('payment_BkashAutoPayment_username'); 
		 $password = $this->config->get('payment_BkashAutoPayment_password'); 

		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		
		$data['tran_id'] = $this->session->data['order_id'];
		$data['total_amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		
	
		$data['process_url'] = $this->url->link('extension/payment/BkashAutoPayment/createPayment', '', 'bkash');
	
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/BkashAutoPayment')) {
			return $this->load->view($this->config->get('config_template') . '/template/extension/payment/BkashAutoPayment', $data);
		} else {
			return $this->load->view('extension/payment/bkashAutoPayment', $data);
		}
	}

	// public function sendrequest()
	// {
		
	// 	$this->load->model('checkout/order');

	// 	$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);// update order status as pending
		
	// 	$data['store_id'] = $this->config->get('payment_SSLCommerce_merchant');
	// 	$data['tran_id'] = $_REQUEST['order'];
	// 	$data['total_amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

	// 	$data['action'] = $this->url->link('extension/payment/BkashAutoPayment/createPayment', '', 'bkash');
		
	// 	///bkash
	// 	$this->response->setOutput($this->load->view('extension/payment/bkash/payment', $data));
		
	// }
	

		public function getToken()
		{
			 $base_url = $this->config->get('payment_BkashAutoPayment_base_url'); 
			 $app_key = $this->config->get('payment_BkashAutoPayment_app_key'); 
			 $app_secret = $this->config->get('payment_BkashAutoPayment_app_secret'); 
			 $username = $this->config->get('payment_BkashAutoPayment_username'); 
			 $password = $this->config->get('payment_BkashAutoPayment_password'); 
			
			$this->session->data['id_token'] = null;
			

			$post_token = array(
				'app_key' => $app_key,
				'app_secret' => $app_secret
			);

	
			$url = ("$base_url/checkout/token/grant");
			
			$post_token = json_encode($post_token);
			
			$header = array(
				'Content-Type:application/json',
				"username:".$username,
				"password:".html_entity_decode($password)
			);
			
		
			$handle = curl_init();
			curl_setopt($handle, CURLOPT_URL, $url);
			curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
			curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($handle, CURLOPT_POSTFIELDS, $post_token);
			curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    		curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
    		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($url, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			$result_data = curl_exec($handle);
			curl_close($handle);
	
			$response = json_decode($result_data, true);
			
			if (array_key_exists('message', $response)) {
				
				$this->response->setOutput(json_encode($response));
			}
	
			$this->session->data['id_token'] = $response['id_token'];

			// return json_encode($response);
			// $this->response->setOutput(json_encode($response));
			return $response['id_token'];
		}

		public function createPayment()
		{

			 $token  = $this->getToken();
			 $base_url = $this->config->get('payment_BkashAutoPayment_base_url'); 
			 $app_key = $this->config->get('payment_BkashAutoPayment_app_key'); 
			 $app_secret = $this->config->get('payment_BkashAutoPayment_app_secret'); 
			 $username = $this->config->get('payment_BkashAutoPayment_username'); 
			 $password = $this->config->get('payment_BkashAutoPayment_password'); 

			 $this->load->model('checkout/order');
			 $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
			
			

			// $token =$this->session->data['id_token'];
			$callbackURL=  $this->url->link('extension/payment/BkashAutoPayment/callback');
			// $callbackURL=  "https://www.google.com/";

			$requestbody = array(
                'mode' => '0011',
                'amount' => $amount,
                'currency' => 'BDT',
                'intent' => 'sale',
                'payerReference' => $order_info['telephone'],
                'merchantInvoiceNumber' => $this->session->data['order_id'],
                'callbackURL' => $callbackURL
            );

			$url = ("$base_url/checkout/create");
			
			$request_data_json = json_encode($requestbody);
			$header = array(
				'Content-Type:application/json',
				"authorization:$token",
				"x-app-key:$app_key"
			);

			$handle = curl_init();
			curl_setopt($handle, CURLOPT_URL, $url);
			curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
			curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($handle, CURLOPT_POSTFIELDS, $request_data_json);
			curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
    		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
			$result_data = curl_exec($handle);
			curl_close($handle);
			
			$this->response->setOutput($result_data);
		}
	
		
		public function executePayment($paymentID)
		{

			 $base_url = $this->config->get('payment_BkashAutoPayment_base_url'); 
			 $app_key = $this->config->get('payment_BkashAutoPayment_app_key'); 
			 $app_secret = $this->config->get('payment_BkashAutoPayment_app_secret'); 
			 $username = $this->config->get('payment_BkashAutoPayment_username'); 
			 $password = $this->config->get('payment_BkashAutoPayment_password'); 

			$token = $this->session->data['id_token'];
	
			// $paymentID = $_POST['paymentID'];
			
			$post_token = array(
				'paymentID' => $paymentID
				 );
			$posttoken = json_encode($post_token);
			
			$url = ("$base_url/checkout/execute/");
			$header = array(
				'Content-Type:application/json',
				"authorization:$token",
				"x-app-key:$app_key"
			);

			$handle = curl_init();
			curl_setopt($handle, CURLOPT_URL, $url);
			curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
			curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($handle, CURLOPT_POSTFIELDS, $posttoken);
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
    		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($url, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			$result_data = curl_exec($handle);
			curl_close($url);
	
			$result_dataValue = json_decode($result_data, true);

			if($result_dataValue['paymentID'] != null &&  $result_dataValue['statusCode'] =="0000"){
				$this->confirm($result_data);
			} else {
				$this->session->data['bkash_payment_error'] = $result_dataValue['statusMessage'];
				$this->response->redirect($this->url->link('checkout/checkout'));
			}
			
			return $result_dataValue['paymentID'];
		}
	
		public function confirm($result_data)
		{
			
			$data = json_decode($result_data, true);
			if ($this->session->data['payment_method']['code'] == 'BkashAutoPayment') {
				$comment = '';
				$comment .= "Transaction ID: " . $data['trxID'] . "\n";
				$comment .= "Payment ID: " . $data['paymentID'] . "\n";
				$comment .= 'Customer Bkash Number: ' . $data['customerMsisdn'] . "\n";
				$comment .= 'Amount: ' . $data['amount'] . "\n";
				$comment .= 'Invoice Number: ' . $data['merchantInvoiceNumber'] . "\n";
				$comment .= 'Payment Time:  ' . $data['paymentExecuteTime'] . "\n";
				
				$this->load->model('checkout/order');

				$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_BkashAutoPayment_order_status_id'), $comment, true);
			}
			// $data['continue'] = $this->url->link('checkout/success');
			// $this->response->setOutput($this->load->view('extension/payment/success', $data));
			return null;
		}
	
	public function callback() 
	{
		if($_GET['status'] == 'success'){
			$this->executePayment($_GET['paymentID']);
			$this->response->redirect($this->url->link('checkout/success'));
		} else if($_GET['status'] == 'failure'){
			$this->session->data['bkash_payment_error'] = "Payment Failed";
			$this->response->redirect($this->url->link('checkout/checkout'));
		} else {
			$this->session->data['bkash_payment_error'] = "Payment Cancel";;
			$this->response->redirect($this->url->link('checkout/checkout'));
		}
	}

}
