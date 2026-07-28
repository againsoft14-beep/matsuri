<?php
class ControllerExtensionModuleInventoryModuleInventory extends Controller {
    private $error = [];

    public function index() {
        $this->load->language('extension/module/inventory_module/inventory');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/inventory_module/inventory');

        $this->getList();
    }

    protected function getList() {
        $this->load->language('extension/module/inventory_module/inventory');
    
        // 1. Capture Request Parameters
        $filter_date_start = $this->request->get['filter_date_start'] ?? '';
        $filter_date_end   = $this->request->get['filter_date_end'] ?? '';
        $filter_title      = $this->request->get['filter_title'] ?? '';
        $filter_status     = $this->request->get['filter_status'] ?? '';
        $sort              = $this->request->get['sort'] ?? 'i.inventory_date';
        $order             = $this->request->get['order'] ?? 'DESC';
        $page              = $this->request->get['page'] ?? 1;
    
        $limit = $this->config->get('config_limit_admin') ?? 20; 
        // $limit =  2; 
    
        $user_token = $this->session->data['user_token'];
    
        // 2. Build URL String
        $url = '';
    
        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }
    
        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }
    
        if (isset($this->request->get['filter_title'])) {
            $url .= '&filter_title=' . urlencode(html_entity_decode($this->request->get['filter_title'], ENT_QUOTES, 'UTF-8'));
        }
    
        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }
    
        $url_base = $url;
    
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
    
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
    
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
    
        // 3. Breadcrumbs
        $data['breadcrumbs'] = [];
    
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $user_token, true)
        ];
    
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $user_token . $url, true)
        ];
    
        // 4. Action Buttons
        $data['add']    = $this->url->link('extension/module/inventory_module/inventory/add', 'user_token=' . $user_token . $url, true);
        $data['delete'] = $this->url->link('extension/module/inventory_module/inventory/delete', 'user_token=' . $user_token . $url, true);
    
        $new_order = ($order == 'ASC') ? 'DESC' : 'ASC';
    
        $data['sort_date'] = $this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $user_token . '&sort=i.inventory_date&order=' . $new_order . $url_base, true);
        $data['sort_lotnumber'] = $this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $user_token . '&sort=i.inventory_lotnumber&order=' . $new_order . $url_base, true);
        $data['sort_amount'] = $this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $user_token . '&sort=total_amount&order=' . $new_order . $url_base, true);
        $data['sort_products'] = $this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $user_token . '&sort=total_products&order=' . $new_order . $url_base, true);
        $data['sort_status'] = $this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $user_token . '&sort=i.status&order=' . $new_order . $url_base, true);
    
        // 5. Filter Data
        $filter_data = [
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'filter_title'      => $filter_title,
            'filter_status'     => $filter_status,
            'sort'              => $sort,
            'order'             => $order,
            'start'             => ($page - 1) * $limit,
            'limit'             => $limit
        ];
    
        // ðŸ”¥ IMPORTANT: total first (best practice)
        $inventory_total = $this->model_extension_module_inventory_module_inventory->getTotalInventories($filter_data);
    
        $results = $this->model_extension_module_inventory_module_inventory->getInventories($filter_data);
    
        $data['inventories'] = [];
    
        foreach ($results as $result) {
    
            $status_map = [
                0 => ['text' => 'Pending',  'class' => 'warning'],
                1 => ['text' => 'Upcoming', 'class' => 'info'],
                2 => ['text' => 'Received', 'class' => 'success']
            ];
    
            $current_status = $status_map[$result['status']] ?? ['text' => 'Unknown', 'class' => 'default'];
    
            $data['inventories'][] = [
                'inventory_id'        => $result['inventory_id'],
                'inventory_lotnumber' => $result['inventory_lotnumber'],
                'inventory_date'      => date("d M, Y", strtotime($result['inventory_date'])),
                'total_products'      => $result['total_products'],
                'total_price'         => $this->currency->format($result['total_amount'], $this->config->get('config_currency')),
                'status_text'         => $current_status['text'],
                'status_class'        => $current_status['class'],
                'edit'                => $this->url->link('extension/module/inventory_module/inventory/edit', 'user_token=' . $user_token . '&inventory_id=' . $result['inventory_id'] . $url, true),
                'view'                => $this->url->link('extension/module/inventory_module/inventory/view', 'user_token=' . $user_token . '&inventory_id=' . $result['inventory_id'] . $url, true)
            ];
        }
    
        // 6. Pagination
        $pagination = new Pagination();
        $pagination->total = $inventory_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link(
            'extension/module/inventory_module/inventory',
            'user_token=' . $user_token . $url_base . '&page={page}',
            true
        );
    
        $data['pagination'] = $pagination->render();
        
        
    
        // ðŸ”¥ Results text (IMPORTANT)
        $data['results'] = sprintf(
            $this->language->get('text_pagination'),
            ($inventory_total) ? (($page - 1) * $limit) + 1 : 0,
            ((($page - 1) * $limit) > ($inventory_total - $limit)) ? $inventory_total : ((($page - 1) * $limit) + $limit),
            $inventory_total,
            ceil($inventory_total / $limit)
        );
    
        // 7. Template Data
        $data['user_token']        = $user_token;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end']   = $filter_date_end;
        $data['filter_title']      = $filter_title;
        $data['filter_status']     = $filter_status;
        $data['sort']              = $sort;
        $data['order']             = $order;
    
        // Notifications
        $data['success'] = $this->session->data['success'] ?? '';
        unset($this->session->data['success']);
    
        $data['error_warning'] = $this->error['warning'] ?? ($this->session->data['error_warning'] ?? '');
        unset($this->session->data['error_warning']);
        
      
        // Common UI
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $data['column_date'] = $this->language->get('column_date');
        $data['column_lotnumber'] = $this->language->get('column_lotnumber');
        $data['column_products'] = $this->language->get('column_products');
        $data['column_price'] = $this->language->get('column_price');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_action'] = $this->language->get('column_action');
        $data['button_filter'] = $this->language->get('button_filter');
        $data['export'] = $this->url->link('extension/module/inventory_module/inventory/export', 'user_token=' . $user_token . $url, true);
    
        $this->response->setOutput($this->load->view('extension/module/inventory_module/inventory/inventory_list', $data));
    }

	public function export() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/inventory')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/inventory');
		$this->load->model('extension/module/inventory_module/inventory');

		$filter_data = array(
			'filter_date_start' => $this->request->get['filter_date_start'] ?? '',
			'filter_date_end'   => $this->request->get['filter_date_end'] ?? '',
			'filter_title'      => $this->request->get['filter_title'] ?? '',
			'filter_status'     => $this->request->get['filter_status'] ?? '',
			'sort'              => $this->request->get['sort'] ?? 'i.inventory_date',
			'order'             => $this->request->get['order'] ?? 'DESC'
		);

		$results = $this->model_extension_module_inventory_module_inventory->getInventories($filter_data);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=inventory_lots.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_date'),
			$this->language->get('column_lotnumber'),
			$this->language->get('column_products'),
			$this->language->get('column_price'),
			$this->language->get('column_status')
		));

		foreach ($results as $result) {
			$status = 'Pending';
			if ($result['status'] == 1) $status = 'Upcoming';
			if ($result['status'] == 2) $status = 'Received';

			fputcsv($output, array(
				date("d M, Y", strtotime($result['inventory_date'])),
				$result['inventory_lotnumber'],
				$result['total_products'],
				$this->currency->format($result['total_amount'], $this->config->get('config_currency'), '', false),
				$status
			));
		}

		fclose($output);
		exit();
	}

    public function add() {
        $this->load->language('extension/module/inventory_module/inventory');
        $this->document->setTitle($this->language->get('text_add'));
        $this->load->model('extension/module/inventory_module/inventory');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $post = $this->request->post;
            $post['products'] = $this->getPostedProducts();
            $this->model_extension_module_inventory_module_inventory->addInventory($post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $this->session->data['user_token'], true));
        }
        $this->getForm();
    }

    public function edit() {
        $this->load->language('extension/module/inventory_module/inventory');
        $this->document->setTitle($this->language->get('text_edit'));
        $this->load->model('extension/module/inventory_module/inventory');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $post = $this->request->post;
            $post['products'] = $this->getPostedProducts();
            $this->model_extension_module_inventory_module_inventory->editInventory($this->request->get['inventory_id'], $post);
            $this->session->data['success'] = $this->language->get('text_success_edit');
            $this->response->redirect($this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $this->session->data['user_token'], true));
        }
        $this->getForm();
    }

    protected function getPostedProducts() {
        if (!empty($this->request->post['products_json'])) {
            $decoded = json_decode(html_entity_decode($this->request->post['products_json'], ENT_QUOTES, 'UTF-8'), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values($decoded);
            }
        }

        if (!empty($this->request->post['products']) && is_array($this->request->post['products'])) {
            return $this->request->post['products'];
        }

        return [];
    }

    protected function getForm() {
        $data['user_token'] = $this->session->data['user_token'];
        $data['text_form'] = !isset($this->request->get['inventory_id']) ? "Add Lot" : "Edit Lot";

        // Action Links
        if (!isset($this->request->get['inventory_id'])) {
            $data['action'] = $this->url->link('extension/module/inventory_module/inventory/add', 'user_token=' . $data['user_token'], true);
            $inventory_info = [];
        } else {
            $data['action'] = $this->url->link('extension/module/inventory_module/inventory/edit', 'user_token=' . $data['user_token'] . '&inventory_id=' . $this->request->get['inventory_id'], true);
            $inventory_info = $this->model_extension_module_inventory_module_inventory->getInventory($this->request->get['inventory_id']);
        }
        

        $data['cancel'] = $this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $data['user_token'], true);

        // --- Data Mapping Logic (Post > DB > Default) ---
        $data['inventory_lotnumber'] = $this->request->post['inventory_lotnumber'] ?? $inventory_info['inventory_lotnumber'] ?? '';
        $data['inventory_date'] = $this->request->post['inventory_date'] ?? $inventory_info['inventory_date'] ?? date('Y-m-d');

        if (isset($this->request->post['supplier_id'])) {
            $data['supplier_id'] = $this->request->post['supplier_id'];
            $data['supplier_name'] = $this->request->post['supplier_name'] ?? '';
        } elseif (!empty($inventory_info['supplier_id'])) {
            $data['supplier_id'] = $inventory_info['supplier_id'];
            $data['supplier_name'] = $inventory_info['supplier_name'] ?? '';
        } else {
            // Default value (create mode)
            $default_supplier_id = 1;
            $data['supplier_id'] = $default_supplier_id;
            // ensure model loaded
            $this->load->model('extension/module/inventory_module/supplier');
            $default_supplier = $this->model_extension_module_inventory_module_supplier->getSupplier($default_supplier_id);
            if ($default_supplier && !empty($default_supplier['name'])) {
                $data['supplier_name'] = $default_supplier['name'];
            } else {
                $data['supplier_name'] = 'Default Supplier';
            }
        }
        
        $data['inventory_day'] = $this->request->post['inventory_day'] ?? $inventory_info['inventory_day'] ?? date('d');
        $data['inventory_month'] = $this->request->post['inventory_month'] ?? $inventory_info['inventory_month'] ?? date('m');
        $data['inventory_year'] = $this->request->post['inventory_year'] ?? $inventory_info['inventory_year'] ?? date('Y');
        
        $statuses = [
            0 => 'pending',
            1 => 'upcoming',
            2 => 'received'
        ];
        
        $status_text = $statuses[$inventory_info['status']] ?? 'pending';
        $data['inventory_status'] = $this->request->post['inventory_status'] ?? $status_text;
        
        // --- Product Loading Logic ---
        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        
        // Category filter নিন
        $category_id = isset($this->request->get['category_id']) ? (int)$this->request->get['category_id'] : 0;
        
        $posted_products = $this->getPostedProducts();

        $products = !empty($posted_products) ? $posted_products : (
            isset($this->request->get['inventory_id']) 
                ? $this->model_extension_module_inventory_module_inventory->getInventoryProducts(
                    $this->request->get['inventory_id'], 
                    $category_id
                  ) 
                : []
        );
        
        // Categories data pass করুন view-এ
        $data['categories'] = $this->model_extension_module_inventory_module_inventory->getCategories();
        $data['category_id'] = $category_id;
        $data['inventory_id'] = $this->request->get['inventory_id'];
        
        //$products = $this->request->post['products'] ?? (isset($this->request->get['inventory_id']) ? $this->model_extension_module_inventory_module_inventory->getInventoryProducts($this->request->get['inventory_id']) : []);
     
        
        $data['products'] = [];
        foreach ($products as $product) {
            $product_info = $this->model_catalog_product->getProduct($product['product_id']);
            $categories = $this->model_extension_module_inventory_module_inventory->getProductCategories($product['product_id']);
            if ($product_info) {
                $data['products'][] = [
                    'product_id'      => $product['product_id'],
                    'name'            => $product_info['name'],
                    'categories'      => $categories,
                    'image'           => $this->model_tool_image->resize($product_info['image'] ?: 'no_image.png', 40, 40),
                    'quantity'        => $product['quantity'],
                    'damage_quantity' => $product['damage_quantity'] ?? 0,
                    'purchase_price'  => $product['purchase_price'],
                    'sale_price'      => $product['sale_price'],
                    'additional_cost' => $product['additional_cost'],
                    'total_price'     => $product['total_price'],
                    'remarks'         => $product['remarks'] ?? '',
                    'is_merge_lot_quantity_to_main' => $product['is_merge_lot_quantity_to_main'],
                ];
            }
        }

        // --- Error Display Logic ---
        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_lotnumber'] = $this->error['lotnumber'] ?? '';
        $data['error_supplier'] = $this->error['supplier'] ?? '';
        $data['error_products'] = $this->error['products'] ?? [];

        $data['column_damage_quantity'] = $this->language->get('column_damage_quantity');
        $data['entry_damage_quantity'] = $this->language->get('entry_damage_quantity');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/inventory_module/inventory/inventory_form', $data));
    }

    protected function validateForm() {
        // 1. Permission Check
        if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/inventory')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
    
        // 2. Lot Number Validation (Length & Uniqueness)
        $lot_number = $this->request->post['inventory_lotnumber'] ?? '';
        $inventory_id = $this->request->get['inventory_id'] ?? 0;
    
        if ((utf8_strlen($lot_number) < 1) || (utf8_strlen($lot_number) > 64)) {
            $this->error['lotnumber'] = $this->language->get('error_lotnumber');
        } else {
            $this->load->model('extension/module/inventory_module/inventory');
            if ($this->model_extension_module_inventory_module_inventory->getTotalInventoriesByLotNumber($lot_number, $inventory_id)) {
                $this->error['lotnumber'] = $this->language->get('error_lot_exists');
            }
        }
    
        // 3. Supplier Validation
        if (empty($this->request->post['supplier_id'])) {
            $this->error['supplier'] = $this->language->get('error_supplier');
        }
    
        // 4. Products Array Validation
        $products = $this->getPostedProducts();
    
        // if (empty($products) || !is_array($products)) {
        //     $this->error['warning'] = $this->language->get('error_product');
        // } else {
        //     foreach ($products as $key => $product) {
        //         // Price & Quantity Logic
        //         $purchase_price  = $product['purchase_price'] ?? '';
        //         $sale_price      = $product['sale_price'] ?? '';
        //         $qty             = (int)($product['quantity'] ?? 0);
        //         $damage_qty      = (int)($product['damage_quantity'] ?? 0);
        //         $additional_cost = $product['additional_cost'] ?? 0;
    
        //         if ($purchase_price === '' || (float)$purchase_price < 0) {
        //             $this->error['products'][$key]['purchase_price'] = $this->language->get('error_purchase_price');
        //         }
    
        //         if ($sale_price === '' || (float)$sale_price < 0) {
        //             $this->error['products'][$key]['sale_price'] = $this->language->get('error_sale_price');
        //         }
    
        //         if ((float)$additional_cost < 0) {
        //             $this->error['products'][$key]['additional_cost'] = $this->language->get('error_additional_cost');
        //         }
    
        //         if ((int)$qty <= 0) {
        //             $this->error['products'][$key]['quantity'] = $this->language->get('error_quantity');
        //         }

        //         if ($damage_qty < 0 || $damage_qty > $qty) {
        //             $this->error['products'][$key]['damage_quantity'] = $this->language->get('error_damage_quantity');
        //         }
        //     }
        // }
    
        // Return warning if any product-level error exists but global warning isn't set
        if (!isset($this->error['warning']) && !empty($this->error['products'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }
    
        return !$this->error;
    }

	public function export_details() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/inventory')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/inventory');
		$this->load->model('extension/module/inventory_module/inventory');

		if (isset($this->request->get['inventory_id'])) {
			$inventory_id = (int)$this->request->get['inventory_id'];
		} else {
			$inventory_id = 0;
		}

		$inventory_info = $this->model_extension_module_inventory_module_inventory->getInventory($inventory_id);

		if ($inventory_info) {
			$results = $this->model_extension_module_inventory_module_inventory->getInventoryDetails($inventory_id);

			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=lot_details_' . $inventory_info['inventory_lotnumber'] . '.csv');

			$output = fopen('php://output', 'w');

			fputcsv($output, array(
				$this->language->get('column_name'),
				$this->language->get('column_quantity'),
				$this->language->get('column_damage_quantity'),
				$this->language->get('column_current_quantity'),
				$this->language->get('column_purchase_price'),
				$this->language->get('column_additional_cost'),
				$this->language->get('column_total_cost'),
				$this->language->get('column_sale_price'),
				$this->language->get('column_total_profit'),
				$this->language->get('column_remarks')
			));

			foreach ($results as $result) {
				fputcsv($output, array(
					$result['product_name'],
					$result['quantity'],
					$result['damage_quantity'],
					$result['current_quantity'],
					$this->currency->format($result['purchase_price'], $this->config->get('config_currency'), '', false),
					$this->currency->format($result['additional_cost'], $this->config->get('config_currency'), '', false),
					$this->currency->format($result['purchase_price'] + $result['additional_cost'], $this->config->get('config_currency'), '', false),
					$this->currency->format($result['sale_price'], $this->config->get('config_currency'), '', false),
					$this->currency->format($result['total_profit'], $this->config->get('config_currency'), '', false),
					$result['remarks']
				));
			}

			fclose($output);
			exit();
		} else {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

    public function delete() {
        $this->load->language('extension/module/inventory_module/inventory');
        $this->load->model('extension/module/inventory_module/inventory');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $inventory_id) {
                $this->model_extension_module_inventory_module_inventory->deleteInventory($inventory_id);
            }
            $this->session->data['success'] = "Inventory deleted successfully!";
        } else {
            $this->session->data['error_warning'] = "Please select records to delete!";
        }
        $this->response->redirect($this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $this->session->data['user_token'], true));
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/inventory')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
    
    public function view() {
        $this->load->language('extension/module/inventory_module/inventory');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/inventory_module/inventory');
        
        if (isset($this->request->get['inventory_id'])) {
            $inventory_id = (int)$this->request->get['inventory_id'];
        } else {
            $inventory_id = 0;
        }

        // Breadcrumbs
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $inventory_id = (int)($this->request->get['inventory_id'] ?? 0);
        $sort = $this->request->get['sort'] ?? 'product_name';
        $order = $this->request->get['order'] ?? 'ASC';
        
        $url = '&inventory_id=' . $inventory_id;
        $new_order = ($order == 'ASC') ? 'DESC' : 'ASC';
        
        
        $data['sort_name']     = $this->url->link('extension/module/inventory_module/inventory/view', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=product_name&order=' . $new_order, true);
        $data['sort_qty']      = $this->url->link('extension/module/inventory_module/inventory/view', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.quantity&order=' . $new_order, true);
        $data['sort_current']  = $this->url->link('extension/module/inventory_module/inventory/view', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.current_quantity&order=' . $new_order, true);
        $data['sort_sale']     = $this->url->link('extension/module/inventory_module/inventory/view', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=id.sale_price&order=' . $new_order, true);
        $data['sort_profit']   = $this->url->link('extension/module/inventory_module/inventory/view', 'user_token=' . $this->session->data['user_token'] . $url . '&sort=total_profit&order=' . $new_order, true);
    
        $data['sort'] = $sort;
        $data['order'] = $order;

        $inventory_info = $this->model_extension_module_inventory_module_inventory->getInventory($inventory_id);

        if ($inventory_info) {
            $data['heading_title'] = $this->language->get('heading_title');

            $data['column_name'] = $this->language->get('column_name');
            $data['column_quantity'] = $this->language->get('column_quantity');
            $data['column_damage_quantity'] = $this->language->get('column_damage_quantity');
            $data['column_current_quantity'] = $this->language->get('column_current_quantity');
            $data['column_purchase_price'] = $this->language->get('column_purchase_price');
            $data['column_additional_cost'] = $this->language->get('column_additional_cost');
            $data['column_total_cost'] = $this->language->get('column_total_cost');
            $data['column_sale_price'] = $this->language->get('column_sale_price');
            $data['column_total_profit'] = $this->language->get('column_total_profit');
            $data['column_remarks'] = $this->language->get('column_remarks');
            $data['column_action'] = $this->language->get('column_action');

            $data['export_details'] = $this->url->link('extension/module/inventory_module/inventory/export_details', 'user_token=' . $this->session->data['user_token'] . '&inventory_id=' . $inventory_id, true);

            $data['user_token'] = $this->session->data['user_token'];
            $data['back'] = $this->url->link('extension/module/inventory_module/inventory', 'user_token=' . $this->session->data['user_token'], true);
            $data['lot_number'] = $inventory_info['inventory_lotnumber'];
            $data['date'] = date($this->language->get('date_format_short'), strtotime($inventory_info['inventory_date']));
            
            $status_text  = 'Pending';
            $status_class = 'warning';
            if ($inventory_info['status'] == 1) {
                $status_text  = 'Upcoming';
                $status_class = 'info';
            } elseif ($inventory_info['status'] == 2) {
                $status_text  = 'Received';
                $status_class = 'success';
            }
        
            $data['status_text'] = $status_text;
            $data['status_class'] = $status_class;
        
            $data['products'] = array();
            $filter_data = array('sort' => $sort, 'order' => $order);
            $results = $this->model_extension_module_inventory_module_inventory->getInventoryDetails($inventory_id, $filter_data);
          
            $total_qty = 0;
            $total_damage_qty = 0;
            $total_current_qty = 0;
            $total_purchase = 0;
            $total_additional = 0;
            $total_price = 0;
            $total_sale = 0;
            $total_profit = 0;
            
            $total_sold_qty = 0;
            $total_pending_qty = 0;
            $total_accepted_qty = 0;
            $total_in_process_qty = 0;
            $total_picked_up_qty = 0;
            $total_returned_qty = 0;
            $total_hold_qty = 0;
            $total_cancel_qty = 0;
        
            foreach ($results as $result) {
                $total_qty += $result['quantity'];
                $total_damage_qty += $result['damage_quantity'];
                $total_current_qty += $result['current_quantity'];
                $total_purchase += $result['purchase_price'];
                $total_additional += $result['additional_cost'];
                $total_price += $result['total_price'];
                $total_sale += $result['sale_price'];
                $total_profit += $result['total_profit'];
        
                $total_pending_qty += $result['total_pending_qty'];
                $total_accepted_qty += $result['total_accepted_qty'];
                $total_in_process_qty += $result['total_in_process_qty'];
                $total_picked_up_qty += $result['total_picked_up_qty'];
                $total_sold_qty += $result['total_sold_qty'];
                $total_returned_qty += $result['total_returned_qty'];
                $total_hold_qty += $result['total_hold_by_customer_qty'];
                $total_cancel_qty += $result['total_cancel_qty'];
                
                $categories = $this->model_extension_module_inventory_module_inventory->getProductCategories($result['product_id']);
        
                $data['products'][] = array(
                    'product_id'        => $result['product_id'],
                    'name'              => $result['product_name'], 
                    'categories'              => $categories, 
                    'quantity'          => $result['quantity'],
                    'damage_quantity'   => $result['damage_quantity'],
                    'current_quantity'  => $result['current_quantity'],
                    'purchase_price'    => $this->currency->format($result['purchase_price'], $this->config->get('config_currency')),
                    'additional_cost'   => $this->currency->format($result['additional_cost'], $this->config->get('config_currency')),
                    'total_cost_price'  => $this->currency->format($result['purchase_price'] + $result['additional_cost'], $this->config->get('config_currency')),
                    'total_price'       => $this->currency->format($result['total_price'], $this->config->get('config_currency')),
                    'sale_price'        => $this->currency->format($result['sale_price'], $this->config->get('config_currency')),
        
                    'total_pending_qty'     => $result['total_pending_qty'],
                    'total_accepted_qty'    => $result['total_accepted_qty'],
                    'total_in_process_qty'  => $result['total_in_process_qty'],
                    'total_picked_up_qty'   => $result['total_picked_up_qty'],
                    'total_sold_qty'        => $result['total_sold_qty'],
                    'total_returned_qty'    => $result['total_returned_qty'],
                    'total_canceled_qty'    => $result['total_cancel_qty'],
                    'total_hold_qty'        => $result['total_hold_by_customer_qty'],
                    
                    'total_profit'      => $this->currency->format($result['total_profit'], $this->config->get('config_currency')),
                    'remarks'           => $result['remarks'],
                    'orders_view'       => $this->url->link('extension/module/inventory_module/inventory/order_list_by_lot', 'user_token=' . $this->session->data['user_token'] . '&lot_id=' . $result['inventory_details_id'], true),
                );
            }
            
            $data['total_qty'] = $total_qty;
            $data['total_damage_qty'] = $total_damage_qty;
            $data['total_current_qty'] = $total_current_qty;
            $data['total_pending_qty'] = $total_pending_qty;
            $data['total_accepted_qty'] = $total_accepted_qty;
            $data['total_in_process_qty'] = $total_in_process_qty;
            $data['total_picked_up_qty'] = $total_picked_up_qty;
            $data['total_sold_qty'] = $total_sold_qty;
            $data['total_returned_qty'] = $total_returned_qty;
            $data['total_hold_qty'] = $total_hold_qty;
            $data['total_cancel_qty'] = $total_cancel_qty;
            
            $data['total_sale'] = $this->currency->format($total_sale, $this->config->get('config_currency'));
            $data['total_purchase'] = $this->currency->format($total_purchase, $this->config->get('config_currency'));
            $data['total_additional'] = $this->currency->format($total_additional, $this->config->get('config_currency'));
            $data['total_price'] = $this->currency->format($total_price, $this->config->get('config_currency'));
            $data['total_profit'] = $this->currency->format($total_profit, $this->config->get('config_currency'));
        
            if (isset($this->session->data['success'])) {
                $data['success'] = $this->session->data['success'];
                unset($this->session->data['success']);
            } else {
                $data['success'] = '';
            }
        
            $data['edit'] = $this->url->link('extension/module/inventory_module/inventory/edit', 'user_token=' . $this->session->data['user_token'] . '&inventory_id=' . (int)$inventory_id, true);
            
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            
            $this->response->setOutput($this->load->view('extension/module/inventory_module/inventory/inventory_view', $data));
        
        } else {
            return new Action('error/not_found');
        }
    }
    
    public function order_list_by_lot() {
    $this->load->language('sale/order');
    $this->load->model('extension/module/inventory_module/inventory');

    if (isset($this->request->get['lot_id'])) {
        $lot_id = (int)$this->request->get['lot_id'];
    } else {
        $lot_id = 0;
    }
    
    $this->document->setTitle("Orders for Lot #" . $lot_id);

    $data['orders'] = array();

    $results = $this->model_extension_module_inventory_module_inventory->getOrdersByLotId($lot_id);


    foreach ($results as $result) {
    $data['orders'][] = array(
        'order_id'             => $result['order_id'],
        'customer'             => $result['customer'],
        'telephone'            => $result['telephone'],
        'product_name'         => $result['product_name'],
        'sku'                  => $result['sku'],
        'model'                => $result['model'],
        'sold_qty'             => $result['sold_qty'],
        'product_total_price'  => $result['product_total_price'],
        'purchase_price'       => $result['purchase_price'],
        'additional_cost'      => $result['additional_cost'], 
        'lot_sale_price'       => $result['lot_sale_price'],
        'status'               => $result['status'],
        'date_added'           => date($this->language->get('date_format_short') . ' H:i', strtotime($result['date_added'])),
        'view'                 => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'], true)
    );
}

    $data['user_token'] = $this->session->data['user_token'];
    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/inventory_module/inventory/lot_orders', $data));
}
}