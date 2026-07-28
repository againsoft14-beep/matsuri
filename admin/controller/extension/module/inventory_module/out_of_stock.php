<?php
class ControllerExtensionModuleInventoryModuleOutOfStock extends Controller { 

	public function export() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/out_of_stock')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/out_of_stock');

		$this->load->model('extension/module/inventory_module/out_of_stock');

		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_sku'])) {
			$filter_sku = $this->request->get['filter_sku'];
		} else {
			$filter_sku = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'pd.name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		$filter_data = array(
			'filter_name' => $filter_name,
			'filter_sku'  => $filter_sku,
			'sort'        => $sort,
			'order'       => $order
		);

		$results = $this->model_extension_module_inventory_module_out_of_stock->getOutOfStockProducts($filter_data);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=out_of_stock_report.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_name'),
			$this->language->get('text_category'),
			$this->language->get('column_sku'),
			$this->language->get('text_self_weight'),
			$this->language->get('text_weight'),
			$this->language->get('text_unit'),
			$this->language->get('text_purchase_price'),
			$this->language->get('column_price'),
			$this->language->get('column_quantity')
		));

		foreach ($results as $result) {
			fputcsv($output, array(
				$result['name'],
				$result['category'] ? $result['category'] : 'None',
				"\t" . $result['sku'],
				number_format($result['weight'], 2),
				number_format($result['unit_weight'], 2),
				$this->weight->getUnit($result['weight_class_id']),
				$this->currency->format($result['purchase_price'], $this->session->data['currency'], '', false),
				$this->currency->format($result['price'], $this->session->data['currency'], '', false),
				$result['quantity']
			));
		}

		fclose($output);
		exit();
	}

    public function index() {
        $this->load->language('extension/module/inventory_module/out_of_stock'); 
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/inventory_module/out_of_stock'); 
        $this->load->model('tool/image');
    
        $user_token = $this->session->data['user_token'];
        
        // 1. Parameters (Filters, Sort, Order, Page)
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        if (isset($this->request->get['filter_sku'])) {
            $filter_sku = $this->request->get['filter_sku'];
        } else {
            $filter_sku = '';
        }

        $sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'pd.name';
        $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit = $this->config->get('config_limit_admin');

        // 2. Sorting Links and URL Construction
        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_sku'])) {
            $url .= '&filter_sku=' . urlencode(html_entity_decode($this->request->get['filter_sku'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['sort'])) $url .= '&sort=' . $this->request->get['sort'];
        if (isset($this->request->get['order'])) $url .= '&order=' . $this->request->get['order'];
        
        // 3. Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $user_token, true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/inventory_module/out_of_stock', 'user_token=' . $user_token . $url, true)
        );

        // 4. Sorting Links Logic
        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_sku'])) {
            $url .= '&filter_sku=' . urlencode(html_entity_decode($this->request->get['filter_sku'], ENT_QUOTES, 'UTF-8'));
        }

        $url_order = ($order == 'ASC') ? 'DESC' : 'ASC';
        $base_route = 'extension/module/inventory_module/out_of_stock';
        $token_url = 'user_token=' . $user_token;

        $data['sort_name']            = $this->url->link($base_route, $token_url . '&sort=pd.name' . '&order=' . $url_order . $url, true);
        $data['sort_sku']             = $this->url->link($base_route, $token_url . '&sort=p.sku' . '&order=' . $url_order . $url, true);
        $data['sort_quantity']        = $this->url->link($base_route, $token_url . '&sort=p.quantity' . '&order=' . $url_order . $url, true);
        $data['sort_price']           = $this->url->link($base_route, $token_url . '&sort=p.price' . '&order=' . $url_order . $url, true);
        $data['sort_status']           = $this->url->link($base_route, $token_url . '&sort=p.status' . '&order=' . $url_order . $url, true);
        $data['sort_category']        = $this->url->link($base_route, $token_url . '&sort=category' . '&order=' . $url_order . $url, true);
        $data['sort_purchase_price']  = $this->url->link($base_route, $token_url . '&sort=purchase_price' . '&order=' . $url_order . $url, true);
        $data['sort_weight']          = $this->url->link($base_route, $token_url . '&sort=p.weight' . '&order=' . $url_order . $url, true);
        $data['sort_unit_weight']          = $this->url->link($base_route, $token_url . '&sort=p.unit_weight' . '&order=' . $url_order . $url, true);
        $data['sort_sl']              = $this->url->link($base_route, $token_url . '&sort=p.product_id' . '&order=' . $url_order . $url, true);

        // 5. Data Retrieval
        $filter_data = [
            'filter_name' => $filter_name,
            'filter_sku'  => $filter_sku,
            'sort'        => $sort,
            'order'       => $order,
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit
        ];
        
        $results = $this->model_extension_module_inventory_module_out_of_stock->getOutOfStockProducts($filter_data);
        $product_total = $this->model_extension_module_inventory_module_out_of_stock->getTotalOutOfStockProducts($filter_data);

        $data['products'] = array();
        foreach ($results as $result) {
            if (!empty($result['image']) && is_file(DIR_IMAGE . $result['image'])) {
                $image = $this->model_tool_image->resize($result['image'], 40, 40);
            } else {
                $image = $this->model_tool_image->resize('no_image.png', 40, 40);
            }

            $data['products'][] = array(
                'product_id'     => $result['product_id'],
                'image'          => $image,
                'name'           => $result['name'],
                'category'       => $result['category'] ? $result['category'] : 'None',
                'sku'            => $result['sku'],
                'weight'         => number_format($result['weight'], 2),
                'unit_weight'         => number_format($result['unit_weight'], 2),
                'weight_class'   => $this->weight->getUnit($result['weight_class_id']),
                'purchase_price' => $this->currency->format($result['purchase_price'], $this->session->data['currency']),
                'price'          => $this->currency->format($result['price'], $this->session->data['currency']),
                'quantity'       => $result['quantity'],
                'status'         => $result['status'],
                'edit'           => $this->url->link('catalog/product/edit', 'user_token=' . $user_token . '&product_id=' . $result['product_id'], true)
            );
        }

        // 6. Pagination & Meta Data
        $data['user_token'] = $user_token;
        $data['filter_name'] = $filter_name;
        $data['filter_sku'] = $filter_sku;
        $data['sort'] = $sort;
        $data['order'] = $order;
        $data['product_total'] = $product_total;
        $data['pagination_page'] = $page;
        $data['pagination_limit'] = $limit;

        $url_pagination = '&sort=' . $sort . '&order=' . $order;
        $pagination = new Pagination();
        $pagination->total = $product_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link($base_route, 'user_token=' . $user_token . $url_pagination . '&page={page}', true);
        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

        // UI Labels
        $data['heading_title'] = $this->language->get('heading_title');
        $data['column_image'] = $this->language->get('column_image');
        $data['column_name'] = $this->language->get('column_name');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_filter'] = $this->language->get('text_filter');
        $data['entry_name'] = $this->language->get('entry_name');
        $data['entry_sku'] = $this->language->get('entry_sku');
        $data['button_filter'] = $this->language->get('button_filter');
        $data['button_reset'] = $this->language->get('button_reset');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/inventory_module/inventory/out_of_stocks_products', $data));
    }
}