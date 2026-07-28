<?php
class ControllerExtensionModuleInventoryModuleReturns extends Controller {
    public function index() {
        // $this->load->language('extension/module/inventory_module/inventory');
        $this->load->language('extension/module/inventory_module/return');
        $this->load->model('extension/module/inventory_module/returns');

        $this->document->setTitle('Return Items');

        $user_token = $this->session->data['user_token'];

        // 1. Handle Filters (Matching Order History logic)
        $filter_data = [
            'filter_customer'   => $this->request->get['filter_customer'] ?? '',
            'filter_product'    => $this->request->get['filter_product'] ?? '',
            'filter_order_id'   => $this->request->get['filter_order_id'] ?? '',
            'filter_lot_number' => $this->request->get['filter_lot_number'] ?? '', 
            'filter_phone'      => $this->request->get['filter_phone'] ?? '',      
            'filter_date_start' => $this->request->get['filter_date_start'] ?? '',
            'filter_date_end'   => $this->request->get['filter_date_end'] ?? '',
            'page'              => $this->request->get['page'] ?? 1,
            'start'             => (($this->request->get['page'] ?? 1) - 1) * $this->config->get('config_limit_admin'),
            'limit'             => $this->config->get('config_limit_admin')
        ];

        // 2. Breadcrumbs
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $user_token, true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/inventory_module/returns', 'user_token=' . $user_token, true)
        ];

        // 3. Fetch Data
        $data['returns'] = [];
        $return_total = $this->model_extension_module_inventory_module_returns->getTotalReturnsByInventory($filter_data);
        $results = $this->model_extension_module_inventory_module_returns->getReturnsByInventory($filter_data);
        
       

        foreach ($results as $result) {
            // Product Front-end Link
            $product_url = HTTP_CATALOG . 'index.php?route=product/product&product_id=' . $result['product_id'];



            $data['returns'][] = [
                'return_id'     => $result['return_id'],
                'order_id'      => $result['order_id'],
                // Link to Order Info
                'order_href'    => $this->url->link('sale/order/info', 'user_token=' . $user_token . '&order_id=' . $result['order_id'], true),
                'customer'      => $result['customer'],
                // Link to filter this report by this customer
                'customer_href' => $this->url->link('extension/module/inventory_module/returns', 'user_token=' . $user_token . '&filter_customer=' . urlencode($result['customer']), true),
                'phone'       => $result['telephone'],
                'product'       => $result['product'],
                'product_href'  => $product_url,
                'sku'           => $result['sku'],
                'quantity'      => $result['quantity'],
                'status'        => $result['status'],
                'date_added'    => date('d M, Y', strtotime($result['date_added'])),
                // Link to Edit the Return record
                'view_return'   => $this->url->link('sale/return/edit', 'user_token=' . $user_token . '&return_id=' . $result['return_id'], true)
            ];
        }

        // 4. UI Strings & Tokens
        $data['user_token'] = $user_token;
        $data['heading_title'] = $this->language->get('heading_title');

        $data['column_return_id'] = $this->language->get('column_return_id');
        $data['column_order_id'] = $this->language->get('column_order_id');
        $data['column_customer'] = $this->language->get('column_customer');
        $data['column_product'] = $this->language->get('column_product');
        $data['column_sku'] = $this->language->get('column_sku');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_phone'] = $this->language->get('column_phone');
        $data['column_lot_number'] = $this->language->get('column_lot_number');

        $data['entry_customer'] = $this->language->get('entry_customer');
        $data['entry_product'] = $this->language->get('entry_product');
        $data['entry_order_id'] = $this->language->get('entry_order_id');
        $data['entry_lot_number'] = $this->language->get('entry_lot_number');
        $data['entry_phone'] = $this->language->get('entry_phone');
        $data['entry_date_start'] = $this->language->get('entry_date_start');
        $data['entry_date_end'] = $this->language->get('entry_date_end');

        $data['button_filter'] = $this->language->get('button_filter');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');

        $url = '';
        if (isset($this->request->get['filter_customer'])) $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        if (isset($this->request->get['filter_product'])) $url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
        if (isset($this->request->get['filter_order_id'])) $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
        if (isset($this->request->get['filter_lot_number'])) $url .= '&filter_lot_number=' . urlencode(html_entity_decode($this->request->get['filter_lot_number'], ENT_QUOTES, 'UTF-8'));
        if (isset($this->request->get['filter_phone'])) $url .= '&filter_phone=' . urlencode(html_entity_decode($this->request->get['filter_phone'], ENT_QUOTES, 'UTF-8'));
        if (isset($this->request->get['filter_date_start'])) $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        if (isset($this->request->get['filter_date_end'])) $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];

        $data['export'] = $this->url->link('extension/module/inventory_module/returns/export', 'user_token=' . $user_token . $url, true);

        // Pass filter values back to view
        foreach ($filter_data as $key => $value) {
            $data[$key] = $value;
        }

        // 5. Pagination
        $pagination = new Pagination();
        $pagination->total = $return_total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('extension/module/inventory_module/returns', 'user_token=' . $user_token . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($return_total) ? (($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) > ($return_total - $this->config->get('config_limit_admin'))) ? $return_total : ((($filter_data['page'] - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $return_total, ceil($return_total / $this->config->get('config_limit_admin')));

        // 6. Common Components
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/inventory_module/inventory/inventory_return', $data));
    }

	public function export() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/returns')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/return');
		$this->load->model('extension/module/inventory_module/returns');

		$filter_data = array(
			'filter_customer'   => $this->request->get['filter_customer'] ?? '',
			'filter_product'    => $this->request->get['filter_product'] ?? '',
			'filter_order_id'   => $this->request->get['filter_order_id'] ?? '',
			'filter_lot_number' => $this->request->get['filter_lot_number'] ?? '',
			'filter_phone'      => $this->request->get['filter_phone'] ?? '',
			'filter_date_start' => $this->request->get['filter_date_start'] ?? '',
			'filter_date_end'   => $this->request->get['filter_date_end'] ?? '',
		);

		$results = $this->model_extension_module_inventory_module_returns->getReturnsByInventory($filter_data);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=product_returns.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_return_id'),
			$this->language->get('column_order_id'),
			$this->language->get('column_date_added'),
			$this->language->get('column_customer'),
			$this->language->get('column_phone'),
			$this->language->get('column_product'),
			$this->language->get('column_sku'),
			$this->language->get('column_quantity'),
			$this->language->get('column_status')
		));

		foreach ($results as $result) {
			fputcsv($output, array(
				$result['return_id'],
				$result['order_id'],
				date('d M, Y', strtotime($result['date_added'])),
				$result['customer'],
				$result['telephone'],
				$result['product'],
				"\t" . $result['sku'],
				$result['quantity'],
				$result['status']
			));
		}

		fclose($output);
		exit();
	}

    // public function autocomplete() {
    //     $json = [];
    //     if (isset($this->request->get['filter_name'])) {
    //         $this->load->model('catalog/product');
    //         $filter_data = [
    //             'filter_name' => $this->request->get['filter_name'],
    //             'start'       => 0,
    //             'limit'       => 5
    //         ];
    //         $results = $this->model_catalog_product->getProducts($filter_data);
    //         foreach ($results as $result) {
    //             $json[] = [
    //                 'product_id' => $result['product_id'],
    //                 'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
    //             ];
    //         }
    //     }
    //     $this->response->addHeader('Content-Type: application/json');
    //     $this->response->setOutput(json_encode($json));
    // }
}