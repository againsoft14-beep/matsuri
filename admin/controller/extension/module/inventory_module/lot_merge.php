<?php
class ControllerExtensionModuleInventoryModuleLotMerge extends Controller {
    
   public function index() {
    $this->load->model('extension/module/inventory_module/inventory');
	$this->load->language('extension/module/inventory_module/lot_merge');

    $this->document->setTitle($this->language->get('heading_title'));
    
    // Get current page
    $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
    
    // Filter variables
    $filter_product_id = isset($this->request->get['filter_product_id']) ? $this->request->get['filter_product_id'] : '';
    $filter_product_name = isset($this->request->get['filter_product_name']) ? $this->request->get['filter_product_name'] : '';
    $filter_sku = isset($this->request->get['filter_sku']) ? $this->request->get['filter_sku'] : '';
    $filter_lot_number = isset($this->request->get['filter_lot_number']) ? $this->request->get['filter_lot_number'] : '';
    $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '';
    $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '';

    // If product ID is provided, get product name for display
    if ($filter_product_id) {
        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($filter_product_id);
        if ($product_info) {
            $filter_product_name = $product_info['name'];
        }
    }
    
    // Filter data for template
    $data['filter_product_id'] = $filter_product_id;
    $data['filter_product_name'] = $filter_product_name;
    $data['filter_sku'] = $filter_sku;
    $data['filter_lot_number'] = $filter_lot_number;
    $data['filter_date_start'] = $filter_date_start;
    $data['filter_date_end'] = $filter_date_end;
    
    // Build URL for pagination and filters
    $sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'h.merge_timestamp';
    $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';
    
    // Build URL for pagination and filters (Include Sort/Order in base URL)
    $url = '';
    if (isset($this->request->get['filter_product_name'])) $url .= '&filter_product_name=' . urlencode(html_entity_decode($this->request->get['filter_product_name'], ENT_QUOTES, 'UTF-8'));
    if (isset($this->request->get['filter_sku'])) $url .= '&filter_sku=' . urlencode(html_entity_decode($this->request->get['filter_sku'], ENT_QUOTES, 'UTF-8'));
    if (isset($this->request->get['filter_lot_number'])) $url .= '&filter_lot_number=' . urlencode(html_entity_decode($this->request->get['filter_lot_number'], ENT_QUOTES, 'UTF-8'));
    if (isset($this->request->get['filter_date_start'])) $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
    if (isset($this->request->get['filter_date_end'])) $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
    
    $new_order = ($order == 'ASC') ? 'DESC' : 'ASC';
    
    // Sorting Links
    $data['sort_lot']      = $this->url->link('extension/module/inventory_module/lot_merge', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=i.inventory_lotnumber&order=' . $new_order, true);
    $data['sort_product']  = $this->url->link('extension/module/inventory_module/lot_merge', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=product_name&order=' . $new_order, true);
    $data['sort_sku']      = $this->url->link('extension/module/inventory_module/lot_merge', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=p.sku&order=' . $new_order, true);
    $data['sort_qty']      = $this->url->link('extension/module/inventory_module/lot_merge', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.quantity&order=' . $new_order, true);
    $data['sort_current']  = $this->url->link('extension/module/inventory_module/lot_merge', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.current_quantity&order=' . $new_order, true);
    $data['sort_price']    = $this->url->link('extension/module/inventory_module/lot_merge', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.sale_price&order=' . $new_order, true);
    $data['sort_date']     = $this->url->link('extension/module/inventory_module/lot_merge', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=h.merge_timestamp&order=' . $new_order, true);
    
    // Get histories with filters
    $filter_data = array(
        'filter_product_id'   => $filter_product_id,
        'filter_product_name' => $filter_product_name,
        'filter_sku'          => $filter_sku,
        'filter_lot_number'   => $filter_lot_number,
        'filter_date_start'   => $filter_date_start,
        'filter_date_end'     => $filter_date_end,
        'sort'  => $sort,
        'order' => $order,
        'start' => ($page - 1) * $this->config->get('config_limit_admin'),
        'limit' => $this->config->get('config_limit_admin')
    );
    
    $data['sort'] = $sort;
    $data['order'] = $order;
    
    // Get total and results
    $histories_total = $this->model_extension_module_inventory_module_inventory->getTotalMergeHistory($filter_data);
    $results = $this->model_extension_module_inventory_module_inventory->getMergeHistory($filter_data);
    
    // Format the results
    $data['histories'] = array();
    
    foreach ($results as $result) {
        $data['histories'][] = array(
            'lot_number'        => $result['lot_number'] ?? $result['inventory_lotnumber'] ?? '',
            'product_name'      => $result['product_name'] ?? '',
            'sku'               => $result['sku'] ?? '',
            'quantity'          => $result['quantity'] ?? '',
            'current_quantity'  => $result['current_quantity'] ?? '',
            'price'             => $this->currency->format($result['sale_price'] ?? 0, $this->config->get('config_currency')),
            'date'              => date('d M, Y', strtotime($result['merge_timestamp'] ?? $result['date_merged'] ?? date('Y-m-d')))
        );
    }
    
    // Set breadcrumbs
    $data['breadcrumbs'] = array();
    $data['breadcrumbs'][] = array(
        'text' => $this->language->get('text_home'),
        'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
    );
    $data['breadcrumbs'][] = array(
        'text' => $this->language->get('heading_title'),
        'href' => $this->url->link('extension/module/inventory_module/lot_merge', 'user_token=' . $this->session->data['user_token'] . $url, true)
    );

    $data['heading_title'] = $this->language->get('heading_title');
    $data['text_list'] = $this->language->get('text_list');
    $data['text_no_results'] = $this->language->get('text_no_results');

    $data['column_lot_number'] = $this->language->get('column_lot_number');
    $data['column_product'] = $this->language->get('column_product');
    $data['column_sku'] = $this->language->get('column_sku');
    $data['column_quantity'] = $this->language->get('column_quantity');
    $data['column_current_quantity'] = $this->language->get('column_current_quantity');
    $data['column_price'] = $this->language->get('column_price');
    $data['column_date'] = $this->language->get('column_date');

    $data['entry_product'] = $this->language->get('entry_product');
    $data['entry_sku'] = $this->language->get('entry_sku');
    $data['entry_lot_number'] = $this->language->get('entry_lot_number');
    $data['entry_date_start'] = $this->language->get('entry_date_start');
    $data['entry_date_end'] = $this->language->get('entry_date_end');

    $data['button_filter'] = $this->language->get('button_filter');

    $data['export'] = $this->url->link('extension/module/inventory_module/lot_merge/export', 'user_token=' . $this->session->data['user_token'] . $url, true);

    // Set pagination
    $pagination = new Pagination();
    $pagination->total = $histories_total;
    $pagination->page = $page;
    $pagination->limit = $this->config->get('config_limit_admin');
    $pagination->url = $this->url->link('extension/module/inventory_module/inventory/lot_merge_history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=' . $sort . '&order=' . $order . '&page={page}', true);    
    $data['pagination'] = $pagination->render();
    $data['results'] = sprintf($this->language->get('text_pagination'), ($histories_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($histories_total - $this->config->get('config_limit_admin'))) ? $histories_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $histories_total, ceil($histories_total / $this->config->get('config_limit_admin')));
    
    // Set cancel/back button
    $data['cancel'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true);
    
    // Load common components
    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');
    $data['user_token'] = $this->session->data['user_token'];
    
    // Set output
    $this->response->setOutput($this->load->view('extension/module/inventory_module/inventory/merge_history', $data));
}

	public function export() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/lot_merge')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/lot_merge');
		$this->load->model('extension/module/inventory_module/inventory');

		$filter_data = array(
			'filter_product_name' => $this->request->get['filter_product_name'] ?? '',
			'filter_sku'          => $this->request->get['filter_sku'] ?? '',
			'filter_lot_number'   => $this->request->get['filter_lot_number'] ?? '',
			'filter_date_start'   => $this->request->get['filter_date_start'] ?? '',
			'filter_date_end'     => $this->request->get['filter_date_end'] ?? '',
			'sort'                => $this->request->get['sort'] ?? 'h.merge_timestamp',
			'order'               => $this->request->get['order'] ?? 'DESC'
		);

		$results = $this->model_extension_module_inventory_module_inventory->getMergeHistory($filter_data);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=lot_merge_history.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_lot_number'),
			$this->language->get('column_product'),
			$this->language->get('column_sku'),
			$this->language->get('column_quantity'),
			$this->language->get('column_current_quantity'),
			$this->language->get('column_price'),
			$this->language->get('column_date')
		));

		foreach ($results as $result) {
			fputcsv($output, array(
				$result['lot_number'] ?? $result['inventory_lotnumber'] ?? '',
				$result['product_name'] ?? '',
				"\t" . ($result['sku'] ?? ''),
				$result['quantity'] ?? '',
				$result['current_quantity'] ?? '',
				$this->currency->format($result['sale_price'] ?? 0, $this->config->get('config_currency'), '', false),
				date('d M, Y', strtotime($result['merge_timestamp'] ?? $result['date_merged'] ?? date('Y-m-d')))
			));
		}

		fclose($output);
		exit();
	}
}