<?php
class ControllerExtensionModuleInventoryModuleHistory extends Controller {
    public function index() {
        $this->load->language('extension/module/inventory_module/history');
        $this->document->setTitle($this->language->get('heading_title_history') ?: "Inventory History");
        $this->load->model('extension/module/inventory_module/history');

        $filter_name = $this->request->get['filter_name'] ?? '';
        $filter_sku = $this->request->get['filter_sku'] ?? '';
        $filter_lot = $this->request->get['filter_lot'] ?? '';
        $filter_date_range = $this->request->get['filter_date_range'] ?? 'lifetime';
        $filter_date_start = $this->request->get['filter_date_start'] ?? '';
        $filter_date_end = $this->request->get['filter_date_end'] ?? '';
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

        $url = '';
        if (isset($this->request->get['filter_name'])) { $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8')); }
        if (isset($this->request->get['filter_sku'])) { $url .= '&filter_sku=' . urlencode(html_entity_decode($this->request->get['filter_sku'], ENT_QUOTES, 'UTF-8')); }
        if (isset($this->request->get['filter_lot'])) { $url .= '&filter_lot=' . urlencode(html_entity_decode($this->request->get['filter_lot'], ENT_QUOTES, 'UTF-8')); }
        if (isset($this->request->get['filter_date_range'])) { $url .= '&filter_date_range=' . $this->request->get['filter_date_range']; }
        if (isset($this->request->get['filter_date_start'])) { $url .= '&filter_date_start=' . $this->request->get['filter_date_start']; }
        if (isset($this->request->get['filter_date_end'])) { $url .= '&filter_date_end=' . $this->request->get['filter_date_end']; }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_history') ?: "Inventory History",
            'href' => $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $sort = $this->request->get['sort'] ?? 'i.inventory_details_id';
        $order = $this->request->get['order'] ?? 'DESC';
        $page = $this->request->get['page'] ?? 1;
    
        // URL components for persistent sorting/filtering
        $url = '';
        if (isset($this->request->get['filter_name'])) { $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8')); }
        if (isset($this->request->get['filter_sku'])) { $url .= '&filter_sku=' . urlencode(html_entity_decode($this->request->get['filter_sku'], ENT_QUOTES, 'UTF-8')); }
        if (isset($this->request->get['filter_lot'])) { $url .= '&filter_lot=' . urlencode(html_entity_decode($this->request->get['filter_lot'], ENT_QUOTES, 'UTF-8')); }
        if (isset($this->request->get['filter_date_range'])) { $url .= '&filter_date_range=' . $this->request->get['filter_date_range']; }
    
        $url_order = ($order == 'ASC') ? 'DESC' : 'ASC';
    
        // Header Sort Links
        $data['sort_date'] = $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=i.inventory_date&order=' . $url_order, true);
        $data['sort_lot']  = $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=i.inventory_lotnumber&order=' . $url_order, true);
        $data['sort_name'] = $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=product_name&order=' . $url_order, true);
        $data['sort_sku'] = $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=sku&order=' . $url_order, true);
        $data['sort_current_quantity'] = $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.current_quantity&order=' . $url_order, true);
        $data['sort_purchase_price'] = $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.purchase_price&order=' . $url_order, true);
        $data['sort_additional_cost'] = $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.additional_cost&order=' . $url_order, true);
        $data['sort_sale_price'] = $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.sale_price&order=' . $url_order, true);
        $data['sort_qty']  = $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.quantity&order=' . $url_order, true);
        $data['sort_total']= $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.total_price&order=' . $url_order, true);

        $limit = 20;
        $filter_data = array(
            'filter_name'       => $filter_name,
            'filter_sku'        => $filter_sku,
            'filter_lot'        => $filter_lot,
            'filter_date_range' => $filter_date_range,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'start'             => ($page - 1) * $limit,
            'limit'             => $limit,
            'sort'        => $sort,
            'order'       => $order,
        );

        $history_total = $this->model_extension_module_inventory_module_history->getTotalInventoryHistory($filter_data);
        $results = $this->model_extension_module_inventory_module_history->getInventoryHistory($filter_data);

        $data['histories'] = array();
        foreach ($results as $result) {
            $data['histories'][] = array(
                'inventory_id'    => $result['inventory_id'],
                'date'            => date('d M, Y', strtotime($result['inventory_date'])),
                'lot_number'      => $result['inventory_lotnumber'],
                'product_name'    => $result['product_name'],
                'sku'             => $result['sku'],
                'quantity'        => $result['quantity'],
                'current_quantity' => $result['current_quantity'],
                'sale_price'      => $this->currency->format($result['sale_price'], $this->config->get('config_currency')),
                'purchase_price'  => $this->currency->format($result['purchase_price'], $this->config->get('config_currency')),
                'additional_cost' => $this->currency->format($result['additional_cost'], $this->config->get('config_currency')),
                'total'           => $this->currency->format($result['total_price'], $this->config->get('config_currency'))
            );
        }
        
        $pagination = new Pagination();
        $pagination->total = $history_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('extension/module/inventory_module/history', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=' . $sort . '&order=' . $order . '&page={page}', true);
        
        $data['sort'] = $sort;
        $data['order'] = $order;
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($history_total - $limit)) ? $history_total : ((($page - 1) * $limit) + $limit), $history_total, ceil($history_total / $limit));

        $data['filter_name'] = $filter_name;
        $data['filter_sku'] = $filter_sku;
        $data['filter_lot'] = $filter_lot;
        $data['filter_date_range'] = $filter_date_range;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['heading_title'] = $this->language->get('heading_title_history');
        $data['text_list'] = $this->language->get('text_history_list');
        $data['text_filter'] = $this->language->get('text_filter');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_export'] = $this->language->get('text_export');
        $data['text_clear'] = $this->language->get('text_clear');
        $data['text_lifetime'] = $this->language->get('text_lifetime');
        $data['text_today'] = $this->language->get('text_today');
        $data['text_last_7_days'] = $this->language->get('text_last_7_days');
        $data['text_last_month'] = $this->language->get('text_last_month');
        $data['text_last_3_months'] = $this->language->get('text_last_3_months');
        $data['text_last_year'] = $this->language->get('text_last_year');
        $data['text_custom'] = $this->language->get('text_custom');

        $data['entry_product_name'] = $this->language->get('entry_product_name');
        $data['entry_sku'] = $this->language->get('entry_sku');
        $data['entry_lot_number'] = $this->language->get('entry_lot_number');
        $data['entry_date_range'] = $this->language->get('entry_date_range');
        $data['entry_date_start'] = $this->language->get('entry_date_start');
        $data['entry_date_end'] = $this->language->get('entry_date_end');

        $data['column_date'] = $this->language->get('column_date');
        $data['column_lot_no'] = $this->language->get('column_lot_no');
        $data['column_product'] = $this->language->get('column_product');
        $data['column_sku'] = $this->language->get('column_sku');
        $data['column_qty'] = $this->language->get('column_qty');
        $data['column_sale_price'] = $this->language->get('column_sale_price');
        $data['column_purchase_price'] = $this->language->get('column_purchase_price');
        $data['column_additional'] = $this->language->get('column_additional');
        $data['column_total'] = $this->language->get('column_total');

        $data['export'] = $this->url->link('extension/module/inventory_module/history/export', 'user_token=' . $this->session->data['user_token'] . $url, true);
      
        $this->response->setOutput($this->load->view('extension/module/inventory_module/inventory/inventory_history', $data));
    }

	public function export() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/history')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/history');
		$this->load->model('extension/module/inventory_module/history');

		$filter_data = array(
			'filter_name'       => $this->request->get['filter_name'] ?? '',
			'filter_sku'        => $this->request->get['filter_sku'] ?? '',
			'filter_lot'        => $this->request->get['filter_lot'] ?? '',
			'filter_date_range' => $this->request->get['filter_date_range'] ?? 'lifetime',
			'filter_date_start' => $this->request->get['filter_date_start'] ?? '',
			'filter_date_end'   => $this->request->get['filter_date_end'] ?? '',
			'sort'              => $this->request->get['sort'] ?? 'i.inventory_details_id',
			'order'             => $this->request->get['order'] ?? 'DESC'
		);

		$results = $this->model_extension_module_inventory_module_history->getInventoryHistory($filter_data);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=inventory_history.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_date'),
			$this->language->get('column_lot_no'),
			$this->language->get('column_product'),
			$this->language->get('column_sku'),
			$this->language->get('column_qty'),
			$this->language->get('column_purchase_price'),
			$this->language->get('column_additional'),
			$this->language->get('column_sale_price'),
			$this->language->get('column_total')
		));

		foreach ($results as $result) {
			fputcsv($output, array(
				date('d M, Y', strtotime($result['inventory_date'])),
				$result['inventory_lotnumber'],
				$result['product_name'],
				"\t" . $result['sku'],
				$result['quantity'],
				$this->currency->format($result['purchase_price'], $this->config->get('config_currency'), '', false),
				$this->currency->format($result['additional_cost'], $this->config->get('config_currency'), '', false),
				$this->currency->format($result['sale_price'], $this->config->get('config_currency'), '', false),
				$this->currency->format($result['total_price'], $this->config->get('config_currency'), '', false)
			));
		}

		fclose($output);
		exit();
	}
}