<?php
class ControllerExtensionModuleInventoryModuleOrderHistory extends Controller {
    public function index() {
        $this->load->language('extension/module/inventory_module/order_history');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/inventory_module/order_history');
        
        $sort = $this->request->get['sort'] ?? 'o.order_id';
        $order = $this->request->get['order'] ?? 'DESC';
        $page = $this->request->get['page'] ?? 1;

        $url = '';
        $filters = ['filter_customer', 'filter_product', 'filter_lot_number', 'filter_phone', 'filter_date_start', 'filter_date_end'];
        foreach ($filters as $f) {
            if (isset($this->request->get[$f])) { $url .= '&' . $f . '=' . urlencode(html_entity_decode($this->request->get[$f], ENT_QUOTES, 'UTF-8')); }
        }
    
        $filter_data = [
            'sort'              => $sort,
            'order'             => $order,
            'filter_customer'   => $this->request->get['filter_customer'] ?? '',
            'filter_product'    => $this->request->get['filter_product'] ?? '',
            'filter_lot_number' => $this->request->get['filter_lot_number'] ?? '',
            'filter_phone'      => $this->request->get['filter_phone'] ?? '',
            'filter_date_start' => $this->request->get['filter_date_start'] ?? '',
            'filter_date_end'   => $this->request->get['filter_date_end'] ?? '',
            'start'             => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'             => $this->config->get('config_limit_admin')
        ];
    
        $url_order = ($order == 'ASC') ? 'DESC' : 'ASC';
        $data['sort_order_id']   = $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . '&sort=o.order_id' . '&order=' . $url_order . $url, true);
        $data['sort_date_added'] = $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_added' . '&order=' . $url_order . $url, true);
        $data['sort_customer']   = $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . '&sort=customer' . '&order=' . $url_order . $url, true);
        $data['sort_product']    = $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . '&sort=product_name' . '&order=' . $url_order . $url, true);
        $data['sort_total']      = $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . '&sort=op.total' . '&order=' . $url_order . $url, true);
        $data['sort_phone']      = $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . '&sort=o.telephone' . '&order=' . $url_order . $url, true);
        $data['sort_sku']        = $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sku' . '&order=' . $url_order . $url, true);
        $data['sort_lot_number'] = $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . '&sort=inv.inventory_lotnumber' . '&order=' . $url_order . $url, true);
        $data['sort_quantity']   = $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . '&sort=op.quantity' . '&order=' . $url_order . $url, true);
        $data['sort_status']     = $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . '&order=' . $url_order . $url, true);
            
        $data['sort'] = $sort;
        $data['order'] = $order;

        // Breadcrumbs
        $data['breadcrumbs'] = [
            ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)],
            ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'], true)]
        ];

        $results = $this->model_extension_module_inventory_module_order_history->getOrdersByProduct($filter_data);
        $total = $this->model_extension_module_inventory_module_order_history->getTotalOrdersByProduct($filter_data);

    
      
        foreach ($results as $result) {
            $product_url = HTTP_CATALOG . 'index.php?route=product/product&product_id=' . $result['product_id'];
            $data['orders'][] = [
                'order_id'      => $result['order_id'],
                'order_href'    => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'], true),
                'customer'      => $result['customer'],
                'customer_href' => $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . '&filter_customer=' . urlencode($result['customer']), true),
                'product'       => $result['product_name'],
                'product_href'  => $product_url, 
                'price'         => sprintf("%.2f", $result['price']),
                'total'         => sprintf("%.2f", $result['total']),
                'quantity'      => $result['quantity'],
                'lot_number'    => $result['inventory_lotnumber'],
                'lot_href'      => $this->url->link('extension/module/inventory_module/inventory/view', 'user_token=' . $this->session->data['user_token'] . '&inventory_id=' . urlencode($result['inventory_id']), true),
                'sku'           => $result['sku'],
                'phone'         => $result['telephone'],
                'status'        => $result['status'],
                'date_added'    => date('d M, Y', strtotime($result['date_added']))
            ];
        }
        
        // Ensure filter values are passed back to the view for the input fields
        $data['filter_customer'] = $filter_data['filter_customer'];
        $data['filter_product'] = $filter_data['filter_product'];
        $data['filter_lot_number'] = $filter_data['filter_lot_number'];
        $data['filter_phone'] = $filter_data['filter_phone'];
        $data['filter_date_start'] = $filter_data['filter_date_start'];
        $data['filter_date_end'] = $filter_data['filter_date_end'];

        // Pagination logic
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $this->request->get['page'] ?? 1;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('extension/module/inventory_module/order_history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=' . $sort . '&order=' . $order . '&page={page}', true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['pagination'] = $pagination->render();
        $data['user_token'] = $this->session->data['user_token'];

        $data['column_order_id'] = $this->language->get('column_order_id');
        $data['column_customer'] = $this->language->get('column_customer');
        $data['column_product'] = $this->language->get('column_product');
        $data['column_sku'] = $this->language->get('column_sku');
        $data['column_phone'] = $this->language->get('column_phone');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_price'] = $this->language->get('column_price');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_lot_number'] = $this->language->get('column_lot_number');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['entry_customer'] = $this->language->get('entry_customer');
        $data['entry_product'] = $this->language->get('entry_product');
        $data['entry_lot_number'] = $this->language->get('entry_lot_number');
        $data['entry_phone'] = $this->language->get('entry_phone');
        $data['entry_date_start'] = $this->language->get('entry_date_start');
        $data['entry_date_end'] = $this->language->get('entry_date_end');
        $data['button_filter'] = $this->language->get('button_filter');

        $data['export'] = $this->url->link('extension/module/inventory_module/order_history/export', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $this->response->setOutput($this->load->view('extension/module/inventory_module/order_history', $data));
    }

	public function export() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/order_history')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/order_history');
		$this->load->model('extension/module/inventory_module/order_history');

		$filter_data = array(
			'filter_customer'   => $this->request->get['filter_customer'] ?? '',
			'filter_product'    => $this->request->get['filter_product'] ?? '',
			'filter_lot_number' => $this->request->get['filter_lot_number'] ?? '',
			'filter_phone'      => $this->request->get['filter_phone'] ?? '',
			'filter_date_start' => $this->request->get['filter_date_start'] ?? '',
			'filter_date_end'   => $this->request->get['filter_date_end'] ?? '',
			'sort'              => $this->request->get['sort'] ?? 'o.order_id',
			'order'             => $this->request->get['order'] ?? 'DESC'
		);

		$results = $this->model_extension_module_inventory_module_order_history->getOrdersByProduct($filter_data);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=order_history.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_order_id'),
			$this->language->get('column_date_added'),
			$this->language->get('column_customer'),
			$this->language->get('column_phone'),
			$this->language->get('column_product'),
			$this->language->get('column_sku'),
			$this->language->get('column_lot_number'),
			$this->language->get('column_quantity'),
			$this->language->get('column_price'),
			$this->language->get('column_total'),
			$this->language->get('column_status')
		));

		foreach ($results as $result) {
			fputcsv($output, array(
				$result['order_id'],
				date('d M, Y', strtotime($result['date_added'])),
				$result['customer'],
				$result['telephone'],
				$result['product_name'],
				"\t" . $result['sku'],
				$result['inventory_lotnumber'],
				$result['quantity'],
				$this->currency->format($result['price'], $this->config->get('config_currency'), '', false),
				$this->currency->format($result['total'], $this->config->get('config_currency'), '', false),
				$result['status']
			));
		}

		fclose($output);
		exit();
	}
    
    
    public function autocomplete() {
    $json = [];

    if (isset($this->request->get['filter_name'])) {
        $this->load->model('catalog/product');

        $filter_data = [
            'filter_name' => $this->request->get['filter_name'],
            'start'       => 0,
            'limit'       => 5
        ];

        $results = $this->model_catalog_product->getProducts($filter_data);

        foreach ($results as $result) {
            $json[] = [
                'product_id' => $result['product_id'],
                'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
            ];
        }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}
}