<?php
class ControllerExtensionModuleInventoryModuleUpdate extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/inventory_module/update');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/product');

        $this->getList();
    }

    protected function getList() {
        // 1. Initialize Filters and Parameters
        $filter_name     = $this->request->get['filter_name'] ?? '';
        $filter_sku      = $this->request->get['filter_sku'] ?? '';

        $sort  = $this->request->get['sort'] ?? 'pd.name';
        $order = $this->request->get['order'] ?? 'ASC';
        $page  = (int)($this->request->get['page'] ?? 1);

        // 2. Build URL String for Links
        $url = '';
        if (isset($this->request->get['filter_name']))     $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        if (isset($this->request->get['filter_sku']))      $url .= '&filter_sku=' . urlencode(html_entity_decode($this->request->get['filter_sku'], ENT_QUOTES, 'UTF-8'));
        if (isset($this->request->get['sort']))            $url .= '&sort=' . $this->request->get['sort'];
        if (isset($this->request->get['order']))           $url .= '&order=' . $this->request->get['order'];
        if (isset($this->request->get['page']))            $url .= '&page=' . $this->request->get['page'];

        // 3. Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/inventory_module/update', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['heading_title'] = $this->language->get('heading_title');

        // 4. Data Retrieval
        $data['products'] = array();
        $filter_data = array(
            'filter_name'     => $filter_name,
            'filter_sku'      => $filter_sku,
            'sort'            => $sort,
            'order'           => $order,
            'start'           => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'           => $this->config->get('config_limit_admin')
        );

        $this->load->model('tool/image');
        $this->load->model('catalog/category');

        $product_total = $this->model_catalog_product->getTotalProducts($filter_data);
        $results = $this->model_catalog_product->getProducts($filter_data);

        foreach ($results as $result) {
            // Image handling
            $image = (is_file(DIR_IMAGE . $result['image'])) 
                ? $this->model_tool_image->resize($result['image'], 40, 40) 
                : $this->model_tool_image->resize('no_image.png', 40, 40);

            // Fetch Lots (Ensure this method exists in your model)
            $product_lots_array = array();
            $lots_query_results = $this->model_catalog_product->getProductLots($result['product_id']);
    
            
            foreach ($lots_query_results as $lot) {
                $product_lots_array[] = array(
                    'lot_id'                        => $lot['lot_id'],
                    'lot_number'                    => $lot['lot_number'],
                    'current_quantity'              => $lot['current_quantity'],
                    'is_merge_lot_quantity_to_main' => $lot['is_merge_lot_quantity_to_main'],
                    'sale_price'                    => $this->currency->format($lot['sale_price'], $this->config->get('config_currency')),
                    'view'                          => 'index.php?route=extension/module/inventory_module/inventory/edit&user_token='. $this->session->data['user_token'].'&inventory_id=' .$lot['inventory_id'],
                );
            }
            

            $data['products'][] = array(
                'product_id' => $result['product_id'],
                'image'      => $image,
                'name'       => $result['name'],
                'sku'        => $result['sku'],
                'price'      => $this->currency->format($result['price'], $this->config->get('config_currency')),
                'quantity'   => $result['quantity'],
                'lots'       => $product_lots_array, 
                'status'     => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
            );
        }

        // 5. Interface Strings & Actions
        $data['user_token'] = $this->session->data['user_token'];
        $data['error_warning'] = $this->error['warning'] ?? '';
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        // 6. Sorting URLs
       $url_base = '';
if (isset($this->request->get['filter_name'])) $url_base .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
if (isset($this->request->get['filter_sku']))  $url_base .= '&filter_sku=' . urlencode(html_entity_decode($this->request->get['filter_sku'], ENT_QUOTES, 'UTF-8'));

$new_order = ($order == 'ASC') ? 'DESC' : 'ASC';

// Generate URLs for each sortable column
$data['sort_name']     = $this->url->link('extension/module/inventory_module/update', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name&order=' . $new_order . $url_base, true);
$data['sort_sku']      = $this->url->link('extension/module/inventory_module/update', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sku&order=' . $new_order . $url_base, true);
$data['sort_price']    = $this->url->link('extension/module/inventory_module/update', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price&order=' . $new_order . $url_base, true);
$data['sort_quantity'] = $this->url->link('extension/module/inventory_module/update', 'user_token=' . $this->session->data['user_token'] . '&sort=p.quantity&order=' . $new_order . $url_base, true);

// 7. Pagination (Ensure sort and order are included in page links)
$pagination = new Pagination();
$pagination->total = $product_total;
$pagination->page  = $page;
$pagination->limit = $this->config->get('config_limit_admin');
$pagination->url   = $this->url->link('extension/module/inventory_module/update', 'user_token=' . $this->session->data['user_token'] . $url_base . '&sort=' . $sort . '&order=' . $order . '&page={page}', true);
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

        // 8. View Data
        $data['filter_name']     = $filter_name;
        $data['filter_sku']      = $filter_sku;
        $data['sort']            = $sort;
        $data['order']           = $order;
        
        $data['filter_action'] = $this->url->link('extension/module/inventory_module/update', 'user_token=' . $this->session->data['user_token'], true);

		$data['column_image'] = $this->language->get('column_image');
		$data['column_name'] = $this->language->get('column_name');
		$data['column_sku'] = $this->language->get('column_sku');
		$data['column_price'] = $this->language->get('column_price');
		$data['column_quantity'] = $this->language->get('column_quantity');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_action'] = $this->language->get('column_action');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_reset'] = $this->language->get('button_reset');
		$data['export'] = $this->url->link('extension/module/inventory_module/update/export', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/inventory_module/inventory/inventory_update', $data));
    }

	public function export() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/update')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('catalog/product');
		$this->load->language('extension/module/inventory_module/all_products');
		$this->load->model('catalog/product');

		$filter_data = array(
			'filter_name' => $this->request->get['filter_name'] ?? '',
			'filter_sku'  => $this->request->get['filter_sku'] ?? '',
			'sort'        => $this->request->get['sort'] ?? 'pd.name',
			'order'       => $this->request->get['order'] ?? 'ASC'
		);

		$results = $this->model_catalog_product->getProducts($filter_data);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=inventory_update.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_name'),
			$this->language->get('column_sku'),
			$this->language->get('column_quantity'),
			$this->language->get('column_price')
		));

		foreach ($results as $result) {
			fputcsv($output, array(
				$result['name'],
				"\t" . $result['sku'],
				$result['quantity'],
				$this->currency->format($result['price'], $this->config->get('config_currency'), '', false)
			));
		}

		fclose($output);
		exit();
	}

    public function mergeLotStock() {
        $json = array();

        // Security check: ensure user is logged in via token
        if (!isset($this->session->data['user_token']) || !isset($this->request->get['user_token']) || $this->session->data['user_token'] != $this->request->get['user_token']) {
             $json['error'] = 'Warning: Session expired, please reload.';
        } elseif ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['lot_id'])) {
            $this->load->model('extension/module/inventory_module/update');
            
            $product_id = (int)$this->request->post['product_id'];
            $new_quantity = (int)$this->request->post['quantity'];
            $this->load->model('catalog/product'); 

            $merge_data = array(
                'lot_id'     => (int)$this->request->post['lot_id'],
                'product_id' => $product_id,
                'quantity'   => $new_quantity,
                'sale_price'   => (int)$this->request->post['sale_price'],
            );
            $product_info = $this->model_catalog_product->getProduct($product_id);

            $result = $this->model_extension_module_inventory_module_update->mergeLotToProduct($merge_data);

            if ($result) {
                
                if ($product_info) {
                    if ($product_info['stock_status_id'] == 5 && $product_info['quantity'] <= 0) {
                        if ($new_quantity > 0) {
                            $this->db->query("UPDATE " . DB_PREFIX . "product SET stock_status_id = '7' WHERE product_id = '" . (int)$product_id . "'");
                        }
                    }
                }
                $json['success'] = true;
                $json['message'] = 'Success: Stock merged and price updated!';
            } else {
                $json['error'] = 'Error: Lot data not found or database error!';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}