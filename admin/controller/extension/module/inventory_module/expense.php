<?php
require_once(DIR_SYSTEM . 'library/common_helper.php');

class ControllerExtensionModuleInventoryModuleExpense extends Controller {
    use CommonHelper;

    public $error = [];
    
    public function index() {
        
      
        $this->load->language('extension/module/inventory_module/expense');
        $this->load->model('extension/module/inventory_module/expense');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['user_token'] = $this->session->data['user_token'];
        
        $filter_title = isset($this->request->get['filter_title']) ? $this->request->get['filter_title'] : '';
        $filter_category_id = isset($this->request->get['filter_category_id']) ? $this->request->get['filter_category_id'] : '';
        $filter_date = isset($this->request->get['filter_date']) ? $this->request->get['filter_date'] : '';
        $filter_date_range = isset($this->request->get['filter_date_range']) ? $this->request->get['filter_date_range'] : '';
        $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '';
        $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '';
    
        $sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'e.expense_date';
        $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';
        
        $filter_data = array(
            'filter_title'       => $filter_title,
            'filter_category_id' => $filter_category_id,
            'filter_date'        => $filter_date,
            'filter_date_range'  => $filter_date_range,
            'filter_date_start'  => $filter_date_start,
            'filter_date_end'    => $filter_date_end,
            'sort'               => $sort,
            'order'              => $order
        );
        
        $data += $filter_data;
        
        $url = '';
        if (isset($this->request->get['filter_title'])) $url .= '&filter_title=' . $this->request->get['filter_title'];
        if (isset($this->request->get['filter_category_id'])) $url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
        if (isset($this->request->get['filter_date'])) $url .= '&filter_date=' . $this->request->get['filter_date'];
        if (isset($this->request->get['filter_date_range'])) $url .= '&filter_date_range=' . $this->request->get['filter_date_range'];
        if (isset($this->request->get['filter_date_start'])) $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        if (isset($this->request->get['filter_date_end'])) $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
      

        $url_order = ($order == 'ASC') ? 'DESC' : 'ASC';
        
        $data['sort_category'] = $this->url->link('extension/module/inventory_module/expense', 'user_token=' . $data['user_token'] . '&sort=category_name' . $url . '&order=' . $url_order, true);
        $data['sort_title']    = $this->url->link('extension/module/inventory_module/expense', 'user_token=' . $data['user_token'] . '&sort=e.title' . $url . '&order=' . $url_order, true);
        $data['sort_amount']   = $this->url->link('extension/module/inventory_module/expense', 'user_token=' . $data['user_token'] . '&sort=e.amount' . $url . '&order=' . $url_order, true);
        $data['sort_date']     = $this->url->link('extension/module/inventory_module/expense', 'user_token=' . $data['user_token'] . '&sort=e.expense_date' . $url . '&order=' . $url_order, true);
                
        $this->getMessages($data);
        $this->getBreadcrumbs($data, [
            'Expenses' => 'extension/module/inventory_module/expense'
        ]);

        $data['add'] = $this->url->link('extension/module/inventory_module/expense/addExpense', 'user_token=' . $data['user_token'], true);
        $data['delete'] = $this->url->link('extension/module/inventory_module/expense/deleteExpense', 'user_token=' . $data['user_token'], true);
        $data['category_list'] = $this->url->link('extension/module/inventory_module/expense/category', 'user_token=' . $data['user_token'], true);
        
        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['expenses'] = array();
        $data['categories'] = $this->model_extension_module_inventory_module_expense->getCategories();
        $results = $this->model_extension_module_inventory_module_expense->getExpenses($filter_data);

        foreach ($results as $result) {
            $data['expenses'][] = array(
                'expense_id'    => $result['expense_id'],
                'category_name' => $result['category_name'], 
                'title'         => $result['title'],
                'amount'        => $this->currency->format($result['amount'], $this->config->get('config_currency')),
                'expense_date'  => date('d M, y', strtotime($result['expense_date'])),
                'edit'          => $this->url->link('extension/module/inventory_module/expense/editExpense', 'user_token=' . $data['user_token'] . '&expense_id=' . $result['expense_id'], true)
            );
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_filter'] = $this->language->get('text_filter');
        $data['text_category_list'] = $this->language->get('text_category_list');
        $data['text_add'] = $this->language->get('text_add');
        $data['text_confirm'] = $this->language->get('text_confirm');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['column_title'] = $this->language->get('column_title');
        $data['column_category'] = $this->language->get('column_category');
        $data['column_amount'] = $this->language->get('column_amount');
        $data['column_date'] = $this->language->get('column_date');
        $data['column_action'] = $this->language->get('column_action');
        $data['entry_title'] = $this->language->get('entry_title');
        $data['entry_category'] = $this->language->get('entry_category');
        $data['entry_date_start'] = $this->language->get('entry_date_start');
        $data['entry_date_end'] = $this->language->get('entry_date_end');
        $data['button_filter'] = $this->language->get('button_filter');
        $data['button_reset'] = $this->language->get('button_reset');
        $data['export'] = $this->url->link('extension/module/inventory_module/expense/export', 'user_token=' . $data['user_token'] . $url, true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $data['messages'] = $this->load->view('common/messages', $data);
        $data['breadcrumb'] = $this->load->view('common/breadcrumb', $data);

        $this->response->setOutput($this->load->view('extension/module/inventory_module/expense/expense', $data));
    }

	public function export() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/expense')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/expense');
		$this->load->model('extension/module/inventory_module/expense');

		$filter_data = array(
			'filter_title'       => $this->request->get['filter_title'] ?? '',
			'filter_category_id' => $this->request->get['filter_category_id'] ?? '',
			'filter_date_start'  => $this->request->get['filter_date_start'] ?? '',
			'filter_date_end'    => $this->request->get['filter_date_end'] ?? '',
			'sort'               => $this->request->get['sort'] ?? 'e.expense_date',
			'order'              => $this->request->get['order'] ?? 'DESC'
		);

		$results = $this->model_extension_module_inventory_module_expense->getExpenses($filter_data);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=expenses.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_date'),
			$this->language->get('column_category'),
			$this->language->get('column_title'),
			$this->language->get('column_amount')
		));

		foreach ($results as $result) {
			fputcsv($output, array(
				date('d M, y', strtotime($result['expense_date'])),
				$result['category_name'],
				$result['title'],
				$this->currency->format($result['amount'], $this->config->get('config_currency'), '', false)
			));
		}

		fclose($output);
		exit();
	}

   // Add Expense
    public function addExpense() {
        $this->load->language('extension/module/inventory_module/expense');
        $this->load->model('extension/module/inventory_module/expense');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_extension_module_inventory_module_expense->addExpense($this->request->post);
            $this->session->data['success'] = "Success: Expense added!";
            $this->response->redirect($this->url->link('extension/module/inventory_module/expense', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    // Edit Expense
    public function editExpense() {
        $this->load->language('extension/module/inventory_module/expense');
        $this->load->model('extension/module/inventory_module/expense');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_extension_module_inventory_module_expense->editExpense($this->request->get['expense_id'], $this->request->post);
            $this->session->data['success'] = "Success: Expense updated!";
            $this->response->redirect($this->url->link('extension/module/inventory_module/expense', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    // Common Form Logic
    protected function getForm() {
        $user_token = $this->session->data['user_token'];
        $data['user_token'] = $user_token;

        $items = ['Expenses' => 'extension/module/inventory_module/expense'];

        if (isset($this->request->get['expense_id'])) {
            $expense_info = $this->model_extension_module_inventory_module_expense->getExpense($this->request->get['expense_id']);
            $items['Edit Expense'] = 'extension/module/inventory_module/expense/editExpense&expense_id=' . $this->request->get['expense_id'];
            $page_title = 'Edit Expense';
            $data['action'] = $this->url->link('extension/module/inventory_module/expense/editExpense', 'user_token=' . $user_token . '&expense_id=' . $this->request->get['expense_id'], true);
        } else {
            $expense_info = array();
            $items['Add Expense'] = 'extension/module/inventory_module/expense/addExpense';
            $page_title = 'Add Expense';
            $data['action'] = $this->url->link('extension/module/inventory_module/expense/addExpense', 'user_token=' . $user_token, true);
        }

        $this->document->setTitle($page_title);
        $data['heading_title'] = $page_title;

        $data['text_form'] = $this->language->get('text_list'); // Using text_list or something appropriate

        $this->getBreadcrumbs($data, $items);
        $this->getMessages($data);

        $data['cancel'] = $this->url->link('extension/module/inventory_module/expense', 'user_token=' . $user_token, true);

        $data['categories'] = $this->model_extension_module_inventory_module_expense->getCategories();

        $data['category_id'] = $this->request->post['category_id'] ?? ($expense_info['category_id'] ?? '');
        $data['title'] = $this->request->post['title'] ?? ($expense_info['title'] ?? '');
        $data['amount'] = $this->request->post['amount'] ?? ($expense_info['amount'] ?? '');
        $data['note'] = $this->request->post['note'] ?? ($expense_info['note'] ?? '');
        $data['expense_date'] = $this->request->post['expense_date'] ?? ($expense_info['expense_date'] ?? date('Y-m-d'));

        $data['error_title'] = $this->error['title'] ?? '';
        $data['error_date'] = $this->error['date'] ?? '';
        $data['error_amount'] = $this->error['amount'] ?? '';
        $data['error_category'] = $this->error['category'] ?? '';

        $data['text_select'] = $this->language->get('text_select');
        $data['button_save'] = $this->language->get('button_save');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $data['breadcrumb'] = $this->load->view('common/breadcrumb', $data);
        $data['messages'] = $this->load->view('common/messages', $data);

        $this->response->setOutput($this->load->view('extension/module/inventory_module/expense/expense_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/expense')) {
            $this->error['warning'] = "Warning: Permission denied!";
        }
        if (empty($this->request->post['title']) || (utf8_strlen($this->request->post['title']) < 3)) {
            $this->error['title'] = "Title must be at least 3 characters!";
        }
    
        if (!is_numeric($this->request->post['amount']) || $this->request->post['amount'] <= 0) {
            $this->error['amount'] = "Please enter a valid amount!";
        }
    
        if (empty($this->request->post['category_id'])) {
            $this->error['category'] = "Please select a category!";
        }
        
        if (empty($this->request->post['expense_date'])) {
            $this->error['date'] = "Please select a date!";
        }
        
        return !$this->error;
    }

    public function deleteExpense() {
        $this->load->model('extension/module/inventory_module/expense');
        if (isset($this->request->post['selected']) && $this->user->hasPermission('modify', 'extension/module/inventory_module/expense')) {
            foreach ($this->request->post['selected'] as $expense_id) {
                $this->model_extension_module_inventory_module_expense->deleteExpense($expense_id);
            }
            $this->session->data['success'] = "Success: Expense(s) deleted!";
        }
        $this->response->redirect($this->url->link('extension/module/inventory_module/expense', 'user_token=' . $this->session->data['user_token'], true));
    }
    
    /* ====================================
        Category  List Page 
    =======================================*/
    
    public function category() {
        $this->load->language('extension/module/inventory_module/expense');
        $this->load->model('extension/module/inventory_module/expense');
        $this->document->setTitle('Expense Categories');
    
        $user_token = $this->session->data['user_token'];
        $data['user_token'] = $user_token;
        
        $sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'name';
        $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';
        
        $url_order = ($order == 'ASC') ? 'DESC' : 'ASC';
    
        $data['sort_name'] = $this->url->link('extension/module/inventory_module/expense/category', 'user_token=' . $user_token . '&sort=name' . '&order=' . $url_order, true);
    
        $filter_data = array(
            'sort'  => $sort,
            'order' => $order
        );
    
        $data['add'] = $this->url->link('extension/module/inventory_module/expense/addCategory', 'user_token=' . $user_token, true);
        $data['delete'] = $this->url->link('extension/module/inventory_module/expense/deleteCategory', 'user_token=' . $user_token, true);
    
        $data['categories'] = $this->model_extension_module_inventory_module_expense->getCategories($filter_data);
        $data['sort'] = $sort;
        $data['order'] = $order;
        
        $this->getMessages($data);
        $this->getBreadcrumbs($data, array(
            'Expense Categories' => 'extension/module/inventory_module/expense/category'
        ));
        
        $data['messages'] = $this->load->view('common/messages', $data);
        $data['breadcrumb'] = $this->load->view('common/breadcrumb', $data);
    
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_add'] = $this->language->get('text_add');
        $data['text_confirm'] = $this->language->get('text_confirm');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['column_name'] = $this->language->get('column_title'); // Using Title as Name
        $data['column_action'] = $this->language->get('column_action');
        $data['export'] = $this->url->link('extension/module/inventory_module/expense/export_category', 'user_token=' . $user_token, true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
    
        $this->response->setOutput($this->load->view('extension/module/inventory_module/expense/expense_category', $data));
    }

	public function export_category() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/expense')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/expense');
		$this->load->model('extension/module/inventory_module/expense');

		$results = $this->model_extension_module_inventory_module_expense->getCategories();

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=expense_categories.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			'Category Name'
		));

		foreach ($results as $result) {
			fputcsv($output, array(
				$result['name']
			));
		}

		fclose($output);
		exit();
	}

    // Category Add
    public function addCategory() {
        $this->load->language('extension/module/inventory_module/expense');
        $this->load->model('extension/module/inventory_module/expense');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateCategoryForm()) {
            $this->model_extension_module_inventory_module_expense->addCategory($this->request->post);
            $this->session->data['success'] = "Success: You have added a new category!";
            $this->response->redirect($this->url->link('extension/module/inventory_module/expense/category', 'user_token=' . $this->session->data['user_token'], true));
        }
        $this->getCategoryForm();
    }
    

    // Category Edit
    public function editCategory() {

        $this->load->language('extension/module/inventory_module/expense');
        $this->load->model('extension/module/inventory_module/expense');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateCategoryForm()) {
            $this->model_extension_module_inventory_module_expense->editCategory($this->request->get['category_id'], $this->request->post);
            $this->session->data['success'] = "Success: Category updated successfully!";
            $this->response->redirect($this->url->link('extension/module/inventory_module/expense/category', 'user_token=' . $this->session->data['user_token'], true));
        }
        $this->getCategoryForm();
    }
    
    public function deleteCategory() {
        $this->load->language('extension/module/inventory_module/expense');
        $this->load->model('extension/module/inventory_module/expense');
    
        if (isset($this->request->post['selected']) && $this->validateCategoryDelete()) {
            foreach ($this->request->post['selected'] as $category_id) {
                $this->model_extension_module_inventory_module_expense->deleteCategory($category_id);
            }
    
            $this->session->data['success'] = "Success: You have deleted categories!";
        }
    
        $this->response->redirect($this->url->link('extension/module/inventory_module/expense/category', 'user_token=' . $this->session->data['user_token'], true));
    }

    // Common Form form all Edit and Add
    protected function getCategoryForm() {
        $this->load->language('extension/module/inventory_module/expense');
        $this->load->model('extension/module/inventory_module/expense');

        $user_token = $this->session->data['user_token'];
        $data['user_token'] = $user_token;
    
    
        $items = [$this->language->get('text_category_list') => 'extension/module/inventory_module/expense/category'];
        
        if (isset($this->request->get['category_id'])) {
            $category_info = $this->model_extension_module_inventory_module_expense->getCategory($this->request->get['category_id']);
            $items[$this->language->get('text_edit_category')] = 'extension/module/inventory_module/expense/editCategory&category_id=' . $this->request->get['category_id'];
            
            $page_title = $this->language->get('text_edit_category');
            $data['action'] = $this->url->link('extension/module/inventory_module/expense/editCategory', 'user_token=' . $user_token . '&category_id=' . $this->request->get['category_id'], true);
        } else {
            $category_info = array();
            $items[$this->language->get('text_add_category')] = 'extension/module/inventory_module/expense/addCategory';
            $page_title = $this->language->get('text_add_category');
            $data['action'] = $this->url->link('extension/module/inventory_module/expense/addCategory', 'user_token=' . $user_token, true);
        }
        
        $this->document->setTitle($page_title);
        $data['heading_title'] = $page_title;

        $data['entry_name'] = $this->language->get('column_category');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $this->getBreadcrumbs($data, $items);
    
        $data['cancel'] = $this->url->link('extension/module/inventory_module/expense/category', 'user_token=' . $user_token, true);
        $data['name'] = $this->request->post['name'] ?? ($category_info['name'] ?? '');
        $data['error_name'] = $this->error['name'] ?? '';
    
        $data['breadcrumb'] = $this->load->view('common/breadcrumb', $data);
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
    
        $this->response->setOutput($this->load->view('extension/module/inventory_module/expense/expense_category_form', $data));
    }
    
    protected function validateCategoryForm() {

        if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/expense')) {
            $this->error['warning'] = "Warning: You do not have permission to modify expenses!";
        }
    
        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = "Category Name must be between 3 and 64 characters!";
        }
    
        return !$this->error;
    }
    
    protected function validateCategoryDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/expense')) {
            $this->error['warning'] = "Warning: You do not have permission to delete!";
        }
        return !$this->error;
    }
    
    
}
