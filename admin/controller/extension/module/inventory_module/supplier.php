<?php
class ControllerExtensionModuleInventoryModuleSupplier extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/inventory_module/supplier');
        $this->document->setTitle($this->language->get('heading_title_suppliers'));
        $this->load->model('extension/module/inventory_module/supplier');
        $this->getList();
    }
    protected function getList() {
        $data['user_token'] = $this->session->data['user_token'];

        // Breadcrumbs
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_suppliers'),
            'href' => $this->url->link('extension/module/inventory_module/supplier', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Actions
        $data['add'] = $this->url->link('extension/module/inventory_module/supplier/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('extension/module/inventory_module/supplier/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('extension/module/inventory_module/supplier/export', 'user_token=' . $this->session->data['user_token'], true);

        $data['heading_title'] = $this->language->get('heading_title_suppliers');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');
        $data['column_name'] = $this->language->get('column_name');
        $data['column_phone'] = $this->language->get('column_phone');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_action'] = $this->language->get('column_action');
        $data['button_add'] = $this->language->get('button_add');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');

        $data['suppliers'] = array();



        $sort = $this->request->get['sort'] ?? 'name';
        $order = $this->request->get['order'] ?? 'ASC';
        $page = $this->request->get['page'] ?? 1;
    
        $url = '';
        if (isset($this->request->get['sort'])) { $url .= '&sort=' . $this->request->get['sort']; }
        if (isset($this->request->get['order'])) { $url .= '&order=' . $this->request->get['order']; }
        if (isset($this->request->get['page'])) { $url .= '&page=' . $this->request->get['page']; }
    
        $url_order = ($order == 'ASC') ? 'DESC' : 'ASC';
        $data['sort_name']   = $this->url->link('extension/module/inventory_module/supplier', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . '&order=' . $url_order, true);
        $data['sort_phone']  = $this->url->link('extension/module/inventory_module/supplier', 'user_token=' . $this->session->data['user_token'] . '&sort=phone' . '&order=' . $url_order, true);
        $data['sort_status'] = $this->url->link('extension/module/inventory_module/supplier', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . '&order=' . $url_order, true);
    
        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );
    
        $results = $this->model_extension_module_inventory_module_supplier->getSuppliers($filter_data);
        
        $data['sort'] = $sort;
        $data['order'] = $order;
        foreach ($results as $result) {
            $data['suppliers'][] = array(
                'supplier_id' => $result['supplier_id'],
                'name'        => $result['name'],
                'phone'       => $result['phone'],
                'address'     => $result['address'],
                'status'      => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'edit'        => $this->url->link('extension/module/inventory_module/supplier/edit', 'user_token=' . $this->session->data['user_token'] . '&supplier_id=' . $result['supplier_id'], true)
            );
        }

        // Error & Success Handling
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        // Selection Persistence
        $data['selected'] = (array)($this->request->post['selected'] ?? array());

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/inventory_module/supplier/supplier_list', $data));
    }

    public function add() {
        $this->load->language('extension/module/inventory_module/supplier');

        $this->document->setTitle($this->language->get('heading_title_suppliers'));

        $this->load->model('extension/module/inventory_module/supplier');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_extension_module_inventory_module_supplier->addSupplier($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/module/inventory_module/supplier', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('extension/module/inventory_module/supplier');

        $this->document->setTitle($this->language->get('heading_title_suppliers'));

        $this->load->model('extension/module/inventory_module/supplier');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_extension_module_inventory_module_supplier->editSupplier($this->request->get['supplier_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/module/inventory_module/supplier', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('extension/module/inventory_module/supplier');

        $this->document->setTitle($this->language->get('heading_title_suppliers'));

        $this->load->model('extension/module/inventory_module/supplier');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $supplier_id) {
                $this->model_extension_module_inventory_module_supplier->deleteSupplier($supplier_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/module/inventory_module/supplier', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getList();
    }


    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['supplier_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        $data['user_token'] = $this->session->data['user_token'];

        // Field Errors
        $errors = array('warning', 'name', 'phone');
        foreach ($errors as $error) {
            $data['error_' . $error] = isset($this->error[$error]) ? $this->error[$error] : '';
        }

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_suppliers'),
            'href' => $this->url->link('extension/module/inventory_module/supplier', 'user_token=' . $data['user_token'], true)
        );

        // Action Buttons
        if (!isset($this->request->get['supplier_id'])) {
            $data['action'] = $this->url->link('extension/module/inventory_module/supplier/add', 'user_token=' . $data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('extension/module/inventory_module/supplier/edit', 'user_token=' . $data['user_token'] . '&supplier_id=' . $this->request->get['supplier_id'], true);
        }
        $data['cancel'] = $this->url->link('extension/module/inventory_module/supplier', 'user_token=' . $data['user_token'], true);

        // Data Loading
        if (isset($this->request->get['supplier_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $supplier_info = $this->model_extension_module_inventory_module_supplier->getSupplier($this->request->get['supplier_id']);
        }

        $fields = array('name', 'phone', 'address', 'status');
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($supplier_info)) {
                $data[$field] = $supplier_info[$field];
            } else {
                $data[$field] = ($field == 'status') ? 1 : '';
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/inventory_module/supplier/supplier_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/supplier')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/supplier')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

	public function export() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/supplier')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/supplier');
		$this->load->model('extension/module/inventory_module/supplier');

		$filter_data = array(
			'sort'  => $this->request->get['sort'] ?? 'name',
			'order' => $this->request->get['order'] ?? 'ASC'
		);

		$results = $this->model_extension_module_inventory_module_supplier->getSuppliers($filter_data);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=suppliers.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_name'),
			$this->language->get('column_phone'),
			$this->language->get('entry_address'),
			$this->language->get('column_status')
		));

		foreach ($results as $result) {
			fputcsv($output, array(
				$result['name'],
				"\t" . $result['phone'],
				$result['address'],
				$result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')
			));
		}

		fclose($output);
		exit();
	}

    public function autocomplete() {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('extension/module/inventory_module/supplier');

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'start'       => 0,
                'limit'       => 10
            );

            $results = $this->model_extension_module_inventory_module_supplier->getSuppliers($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'supplier_id' => $result['supplier_id'],
                    'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}