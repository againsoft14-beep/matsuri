<?php
class ControllerExtensionMeadminsearch extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/me_admin_search');
		
		$data['module_me_admin_search_status'] = $this->config->get('module_me_admin_search_status');
		$data['module_me_admin_search_filter'] = $this->config->get('module_me_admin_search_filter');
		 
		$data['user_token'] = isset($this->session->data['user_token']) ? $this->session->data['user_token'] : '';
		
		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}
		
		if (isset($this->request->get['filter_model'])) {
			$filter_model = $this->request->get['filter_model'];
		} else {
			$filter_model = '';
		}
		
		if (isset($this->request->get['filter_sku'])) {
			$filter_sku = $this->request->get['filter_sku'];
		} else {
			$filter_sku = '';
		}
		
		if (isset($this->request->get['filter_email'])) {
			$filter_email = $this->request->get['filter_email'];
		} else {
			$filter_email = '';
		}
		
		if (isset($this->request->get['filter_order_id'])) {
			$filter_order_id = $this->request->get['filter_order_id'];
		} else {
			$filter_order_id = '';
		}
		
		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = '';
		}
		
		if (isset($this->request->get['filter_customer_telephone'])) {
			$filter_customer_telephone = $this->request->get['filter_customer_telephone'];
		} else {
			$filter_customer_telephone = '';
		}
		
		if (isset($this->request->get['filter_telephone'])) {
			$filter_telephone = $this->request->get['filter_telephone'];
		} else {
			$filter_telephone = '';
		}
		
		if (isset($this->request->get['filter_product'])) {
			$filter_product = $this->request->get['filter_product'];
		} else {
			$filter_product = '';
		}
		
		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = $this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = '';
		}
		
		if (isset($this->request->get['filter_total'])) {
			$filter_total = $this->request->get['filter_total'];
		} else {
			$filter_total = '';
		}
		
		if (isset($this->request->get['filter_from_date'])) {
			$filter_from_date = $this->request->get['filter_from_date'];
		} else {
			$filter_from_date = '';
		}
		
		if (isset($this->request->get['filter_to_date'])) {
			$filter_to_date = $this->request->get['filter_to_date'];
		} else {
			$filter_to_date = '';
		}
		
		$data['filter_pname'] = '';
		$data['filter_pmodel'] = '';
		$data['filter_psku'] = '';
		$data['filter_cname'] = '';
		$data['filter_pmanufacturer'] = '';
		$data['filter_customer_name'] = '';
		$data['filter_customer_email'] = '';
		$data['filter_orderid'] = '';
		$data['filter_orderbycustomer'] = '';
		$data['filter_orderbyproduct'] = '';
		$data['filter_orderstatusid'] = '';
		$data['filter_ototal'] = '';
		$data['filter_from_date'] = '';
		$data['filter_to_date'] = '';
		$data['filter_mcoupon'] = '';
		$data['filter_customer_telephone'] = '';
		$data['filter_orderbycustomertel'] = '';
		if(isset($this->request->get['route'])){
			if($this->request->get['route'] == 'catalog/product'){
				if(!empty($filter_name)){
					$data['filter_pname'] = $filter_name;
				}
				
				if(!empty($filter_model)){
					$data['filter_pmodel'] = $filter_model;
				}
				
				if(!empty($filter_sku)){
					$data['filter_psku'] = $filter_sku;
				}
			}
			
			if($this->request->get['route'] == 'catalog/category'){
				if(!empty($filter_name)){
					$data['filter_cname'] = $filter_name;
				}
			}
			
			if($this->request->get['route'] == 'catalog/manufacturer'){
				if(!empty($filter_name)){
					$data['filter_pmanufacturer'] = $filter_name;
				}
			}
			
			if($this->request->get['route'] == 'catalog/option'){
				if(!empty($filter_name)){
					$data['filter_poption'] = $filter_name;
				}
			}
			
			if($this->request->get['route'] == 'customer/customer'){
				if(!empty($filter_name)){
					$data['filter_customer_name'] = $filter_name;
				}
				
				if(!empty($filter_email)){
					$data['filter_customer_email'] = $filter_email;
				}
				
				if(!empty($filter_telephone)){
					$data['filter_customer_telephone'] = $filter_telephone;
				}
			}
			
			if($this->request->get['route'] == 'sale/order'){
				if(!empty($filter_order_id)){
					$data['filter_orderid'] = $filter_order_id;
				}
				
				if(!empty($filter_customer)){
					$data['filter_orderbycustomer'] = $filter_customer;
				}
				
				if(!empty($filter_telephone)){
					$data['filter_orderbycustomertel'] = $filter_telephone;
				}
				
				if(!empty($filter_product)){
					$data['filter_orderbyproduct'] = $filter_product;
				}
				
				if(!empty($filter_order_status_id)){
					$data['filter_orderstatusid'] = $filter_order_status_id;
				}
				
				if(!empty($filter_total)){
					$data['filter_ototal'] = $filter_total;
				}
				
				if(!empty($filter_from_date)){
					$data['filter_from_date'] = $filter_from_date;
				}
				
				if(!empty($filter_to_date)){
					$data['filter_to_date'] = $filter_to_date;
				}
			}
			
			if($this->request->get['route'] == 'marketing/coupon'){
				if(!empty($filter_name)){
					$data['filter_mcoupon'] = $filter_name;
				}
			}
		}
		
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		return $this->load->view('extension/me_admin_search', $data);
	}
}