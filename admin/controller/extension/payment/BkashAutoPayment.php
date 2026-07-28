<?php

class ControllerExtensionPaymentBkashAutoPayment extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/BkashAutoPayment');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			
			$this->model_setting_setting->editSetting('payment_BkashAutoPayment', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'bkashAutoPayment'));
		}


 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['error_app_key'])) {
			$data['error_app_key'] = $this->error['error_app_key'];
		} else {
			$data['error_app_key'] = '';
		}
		if (isset($this->error['error_app_secret'])) {
			$data['error_app_secret'] = $this->error['error_app_secret'];
		} else {
			$data['error_app_secret'] = '';
		}
		if (isset($this->error['error_username'])) {
			$data['error_username'] = $this->error['error_username'];
		} else {
			$data['error_username'] = '';
		}
		if (isset($this->error['error_password'])) {
			$data['error_password'] = $this->error['error_password'];
		} else {
			$data['error_password'] = '';
		}
		if (isset($this->error['error_base_url'])) {
			$data['error_base_url'] = $this->error['error_base_url'];
		} else {
			$data['error_base_url'] = '';
		}

  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'bkashAutoPayment'),
      		'separator' => false
   		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'bkashAutoPayment'),
			'separator' => ' :: '
		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/payment/BkashAutoPayment', 'user_token=' . $this->session->data['user_token'], 'bkashAutoPayment'),
      		'separator' => ' :: '
   		);

		$data['action'] = $this->url->link('extension/payment/BkashAutoPayment', 'user_token=' . $this->session->data['user_token'], 'bkashAutoPayment');

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'bkashAutoPayment');
		

		if (isset($this->request->post['payment_BkashAutoPayment_app_key'])) {
			$data['payment_BkashAutoPayment_app_key'] = $this->request->post['payment_BkashAutoPayment_app_key'];
		} else {
			$data['payment_BkashAutoPayment_app_key'] = $this->config->get('payment_BkashAutoPayment_app_key');
		}

		if (isset($this->request->post['payment_BkashAutoPayment_app_secret'])) {
			$data['payment_BkashAutoPayment_app_secret'] = $this->request->post['payment_BkashAutoPayment_app_secret'];
		} else {
			$data['payment_BkashAutoPayment_app_secret'] = $this->config->get('payment_BkashAutoPayment_app_secret');
		}
		if (isset($this->request->post['payment_BkashAutoPayment_username'])) {
			$data['payment_BkashAutoPayment_username'] = $this->request->post['payment_BkashAutoPayment_username'];
		} else {
			$data['payment_BkashAutoPayment_username'] = $this->config->get('payment_BkashAutoPayment_username');
		}
		if (isset($this->request->post['payment_BkashAutoPayment_password'])) {
			$data['payment_BkashAutoPayment_password'] = $this->request->post['payment_BkashAutoPayment_password'];
		} else {
			$data['payment_BkashAutoPayment_password'] = $this->config->get('payment_BkashAutoPayment_password');
		}

		if (isset($this->request->post['payment_BkashAutoPayment_test'])) {
			$data['payment_BkashAutoPayment_test'] = $this->request->post['payment_BkashAutoPayment_test'];
		} else {
			$data['payment_BkashAutoPayment_test'] = $this->config->get('payment_BkashAutoPayment_test');
		}

		if (isset($this->request->post['payment_BkashAutoPayment_order_status_id'])) {
			$data['payment_BkashAutoPayment_order_status_id'] = $this->request->post['payment_BkashAutoPayment_order_status_id'];
		} else {
			$data['payment_BkashAutoPayment_order_status_id'] = $this->config->get('payment_BkashAutoPayment_order_status_id');
		}
        if (isset($this->request->post['payment_BkashAutoPayment_order_fail_id'])) {
			$data['payment_BkashAutoPayment_order_fail_id'] = $this->request->post['payment_BkashAutoPayment_order_fail_id'];
		} else {
			$data['payment_BkashAutoPayment_order_fail_id'] = $this->config->get('payment_BkashAutoPayment_order_fail_id');
		}
		
		if (isset($this->request->post['payment_BkashAutoPayment_order_risk_id'])) {
			$data['payment_BkashAutoPayment_order_risk_id'] = $this->request->post['payment_BkashAutoPayment_order_risk_id'];
		} else {
			$data['payment_BkashAutoPayment_order_risk_id'] = $this->config->get('payment_BkashAutoPayment_order_risk_id');
		}
		if (isset($this->request->post['payment_BkashAutoPayment_base_url'])) {
			$data['payment_BkashAutoPayment_base_url'] = $this->request->post['payment_BkashAutoPayment_base_url'];
		} else {
			$data['payment_BkashAutoPayment_base_url'] = $this->config->get('payment_BkashAutoPayment_base_url');
		}
                
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_BkashAutoPayment_geo_zone_id'])) {
			$data['payment_BkashAutoPayment_geo_zone_id'] = $this->request->post['payment_BkashAutoPayment_geo_zone_id'];
		} else {
			$data['payment_BkashAutoPayment_geo_zone_id'] = $this->config->get('payment_BkashAutoPayment_geo_zone_id');
		}

		
		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_BkashAutoPayment_status'])) {
			$data['payment_BkashAutoPayment_status'] = $this->request->post['payment_BkashAutoPayment_status'];
		} else {
			$data['payment_BkashAutoPayment_status'] = $this->config->get('payment_BkashAutoPayment_status');
		}

		if (isset($this->request->post['payment_BkashAutoPayment_sort_order'])) {
			$data['payment_BkashAutoPayment_sort_order'] = $this->request->post['payment_BkashAutoPayment_sort_order'];
		} else {
			$data['payment_BkashAutoPayment_sort_order'] = $this->config->get('payment_BkashAutoPayment_sort_order');
		}
		
   
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
  
    	
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
  
        
		$this->response->setOutput($this->load->view('extension/payment/BkashAutoPayment', $data));
  
		
	}
	

	private function validate() {
		// if (!$this->user->hasPermission('modify', 'extension/payment/BkashAutoPayment')) {
		// 	$this->error['warning'] = $this->language->get('error_permission');
		// }

		if (!$this->request->post['payment_BkashAutoPayment_app_key']) {
			$this->error['error_app_key'] = $this->language->get('error_app_key');
		}
		if (!$this->request->post['payment_BkashAutoPayment_app_secret']) {
			$this->error['error_app_secret'] = $this->language->get('error_app_secret');
		}
		if (!$this->request->post['payment_BkashAutoPayment_username']) {
			$this->error['error_username'] = $this->language->get('error_username');
		}
		if (!$this->request->post['payment_BkashAutoPayment_password']) {
			$this->error['error_password'] = $this->language->get('error_password');
		}
		if (!$this->request->post['payment_BkashAutoPayment_base_url']) {
			$this->error['error_base_url'] = $this->language->get('error_base_url');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
}
?>