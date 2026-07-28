<?php
require_once(DIR_SYSTEM . 'library/common_helper.php');
class ControllerExtensionModuleInventoryModulePayroll extends Controller {

    use CommonHelper;

    private $error = array();

    public function index() {
        
        $this->load->language('extension/module/inventory_module/payroll');
        $this->document->setTitle($this->language->get('heading_title_payroll'));
        $this->load->model('extension/module/inventory_module/payroll');
    
        $user_token = $this->session->data['user_token'];
        $data['user_token'] = $user_token;
        
        $sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'month_year';
        $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';
        
        $url_order = ($order == 'ASC') ? 'DESC' : 'ASC';
        
        $data['sort_month_year'] = $this->url->link('extension/module/inventory_module/payroll', 'user_token=' . $user_token . '&sort=month_year&order=' . $url_order, true);
        $data['sort_net_salary'] = $this->url->link('extension/module/inventory_module/payroll', 'user_token=' . $user_token . '&sort=total_net_salary&order=' . $url_order, true);
        $data['sort_status']     = $this->url->link('extension/module/inventory_module/payroll', 'user_token=' . $user_token . '&sort=status&order=' . $url_order, true);
    
        $data['payrolls'] = array();
        
        $filter_data = array(
            'sort'  => $sort,
            'order' => $order
        );
        
        $payroll_results = $this->model_extension_module_inventory_module_payroll->getPayrolls($filter_data);
        
        $items = [
            'Payroll Manager' => 'extension/module/inventory_module/payroll'
        ];
        
        
        $this->getBreadcrumbs($data, $items);
        $this->getMessages($data);
    
        $data['employee_list'] = $this->url->link('extension/module/inventory_module/payroll/employee', 'user_token=' . $user_token, true);
        $data['generate'] = $this->url->link('extension/module/inventory_module/payroll/generatePayroll', 'user_token=' . $user_token, true);
        $data['export'] = $this->url->link('extension/module/inventory_module/payroll/export', 'user_token=' . $user_token, true);

        $data['column_month_year'] = $this->language->get('column_month_year');
        $data['column_net_salary'] = $this->language->get('column_net_salary');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_action'] = $this->language->get('column_action');
    
     
        
        foreach ($payroll_results as $result) {
            $month_name = date("F", mktime(0, 0, 0, $result['payroll_month'], 10));
            $data['payrolls'][] = array(
                'payroll_id' => $result['payroll_id'],
                'month_year' => $month_name . ' ' . $result['payroll_year'],
                'net_salary' => $this->currency->format($result['total_net_salary'], $this->config->get('config_currency')),
                'status'     => $result['status'], 
                'date_paid'  => ($result['payroll_created_date'] && $result['payroll_created_date'] != '0000-00-00') ? date('d/m/Y', strtotime($result['payroll_created_date'])) : 'N/A',                
                'view'       => $this->url->link('extension/module/inventory_module/payroll/viewPayroll', 'user_token=' . $user_token . '&payroll_id=' . $result['payroll_id'], true)
            );
        }
        
        
       
        
        $data['active_employees'] = array();
        $data['active_employees'] = array();
        $employees = $this->model_extension_module_inventory_module_payroll->getActiveEmployees();
        foreach ($employees as $employee) {
            $data['active_employees'][] = array(
                'emp_id' => $employee['emp_id'],
                'name'   => $employee['name'],
                'salary' => $employee['salary']
            );
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
     
        // Use standard OpenCart breadcrumb and message loaders if available in your common helper
        $data['breadcrumb'] = $this->load->view('common/breadcrumb', $data);
        $data['messages'] = $this->load->view('common/messages', $data);
    
        $this->response->setOutput($this->load->view('extension/module/inventory_module/payroll/payroll', $data));
    }
    
    public function generatePayroll() {
        $this->load->language('extension/module/inventory_module/payroll');
        $this->load->model('extension/module/inventory_module/payroll');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['payroll_data'])) {
            $month = $this->request->post['month'];
            $year  = $this->request->post['year'];
            $month_name = date("F", mktime(0, 0, 0, $month, 10));
            
            $exists = $this->model_extension_module_inventory_module_payroll->checkPayrollExists($month, $year);
        
            if ($exists) {
                $month_name = date("F", mktime(0, 0, 0, $month, 10));
                $this->session->data['error_warning'] = sprintf("Warning: Payroll for %s %s already exists!", $month_name, $year);
                $this->response->redirect($this->url->link('extension/module/inventory_module/payroll', 'user_token=' . $this->session->data['user_token'], true));
                return;
            }
            // Pass the entire post data to the model
            $this->model_extension_module_inventory_module_payroll->generatePayroll($this->request->post);
            
            
            $this->session->data['success'] = sprintf($this->language->get('text_success_generate'), $month_name, $year);
            $this->response->redirect($this->url->link('extension/module/inventory_module/payroll', 'user_token=' . $this->session->data['user_token'], true));
        } else {
            $this->error['warning'] = "No employees were selected for payroll.";
            $this->response->redirect($this->url->link('extension/module/inventory_module/payroll', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function viewPayroll() {
        $this->load->language('extension/module/inventory_module/payroll');
        $this->load->model('extension/module/inventory_module/payroll');
    
        $payroll_id = $this->request->get['payroll_id'] ?? 0;
        $data['user_token'] = $this->session->data['user_token'];
    
        $items = [
            $this->language->get('heading_title_payroll') => 'extension/module/inventory_module/payroll',
            'Payroll Details' => 'extension/module/inventory_module/payroll/viewPayroll&payroll_id=' . $payroll_id
        ];
        
        $this->getBreadcrumbs($data, $items);
        $this->getMessages($data);
    
        $data['cancel'] = $this->url->link('extension/module/inventory_module/payroll', 'user_token=' . $data['user_token'], true);
        $data['results'] = array();
    
        $results = $this->model_extension_module_inventory_module_payroll->getPayrollDetails($payroll_id);
    
        foreach ($results as $result) {
            $data['results'][] = array(
                'payroll_details_id' => $result['payroll_details_id'],
                'name'               => $result['employee_name'],
                'basic'              => $this->currency->format($result['basic'], $this->config->get('config_currency')),
                'bonus'              => $this->currency->format($result['bonus'], $this->config->get('config_currency')),
                'deduction'          => $this->currency->format($result['deduction'], $this->config->get('config_currency')),
                'net_salary'         => $this->currency->format($result['net_salary'], $this->config->get('config_currency')),
                'status'             => $result['status'] == 1 ? 'Paid' : 'Pending',
                'edit_link'          => $this->url->link('extension/module/inventory_module/payroll/edit', 'user_token=' . $data['user_token'] . '&payroll_details_id=' . $result['payroll_details_id'], true),
            );
        }

        $data['column_name'] = $this->language->get('column_name');
        $data['column_basic'] = $this->language->get('column_basic');
        $data['column_net_salary'] = $this->language->get('column_net_salary');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_action'] = $this->language->get('column_action');

        $data['export'] = $this->url->link('extension/module/inventory_module/payroll/export_details', 'user_token=' . $data['user_token'] . '&payroll_id=' . $payroll_id, true);
    
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $data['breadcrumb'] = $this->load->view('common/breadcrumb', $data);
        $data['messages'] = $this->load->view('common/messages', $data);
    
        $this->response->setOutput($this->load->view('extension/module/inventory_module/payroll/payroll_view', $data));
    }
    
    public function edit() {
        $this->load->language('extension/module/inventory_module/payroll');
        $this->load->model('extension/module/inventory_module/payroll');
    
        $payroll_details_id = (int)($this->request->get['payroll_details_id'] ?? 0);
        $info = $this->model_extension_module_inventory_module_payroll->getPayrollDetailWithInfo($payroll_details_id);
    
        if (!$info || $info['status'] == 1) {
            $this->response->redirect($this->url->link('extension/module/inventory_module/payroll', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $items = [
            $this->language->get('text_home')             => 'common/dashboard',
            $this->language->get('heading_title_payroll') => 'extension/module/inventory_module/payroll',
            'Payroll Details'                             => 'extension/module/inventory_module/payroll/viewPayroll&payroll_id=' . $info['payroll_id'],
            'Edit Salary'                                 => 'extension/module/inventory_module/payroll/edit&payroll_details_id=' . $payroll_details_id
        ];
        
        $this->getBreadcrumbs($data, $items);
    
        $data['payroll_details_id'] = $info['payroll_details_id'];
        $data['employee_name']      = $info['employee_name'];
        $data['basic']              = $info['basic'];
        $data['bonus']              = $info['bonus'];
        $data['deduction']          = $info['deduction'];
        $data['payroll_id']         = $info['payroll_id'];
    
        $data['action'] = $this->url->link('extension/module/inventory_module/payroll/edit_salary', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('extension/module/inventory_module/payroll/viewPayroll', 'user_token=' . $this->session->data['user_token'] . '&payroll_id=' . $info['payroll_id'], true);
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['breadcrumb'] = $this->load->view('common/breadcrumb', $data);
        $data['messages'] = $this->load->view('common/messages', $data);
    
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
    
        $this->response->setOutput($this->load->view('extension/module/inventory_module/payroll/payroll_edit', $data));
    }
    
    public function edit_salary() {
        $this->load->model('extension/module/inventory_module/payroll');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['payroll_details_id'])) {
            $id = (int)$this->request->post['payroll_details_id'];
            $bonus = (float)$this->request->post['bonus'];
            $deduction = (float)$this->request->post['deduction'];

            $payroll_info = $this->model_extension_module_inventory_module_payroll->getPayrollDetailWithInfo($id);
            
            if ($payroll_info) {
                $new_net_salary = ($payroll_info['basic'] + $bonus) - $deduction;

                $update_data = array(
                    'bonus'      => $bonus,
                    'deduction'  => $deduction,
                    'net_salary' => $new_net_salary
                );

                $this->model_extension_module_inventory_module_payroll->updateSalary($id, $update_data);
                $this->session->data['success'] = "Success: Salary for " . $payroll_info['employee_name'] . " has been updated!";
                
                $this->response->redirect($this->url->link('extension/module/inventory_module/payroll/viewPayroll', 'user_token=' . $this->session->data['user_token'] . '&payroll_id=' . $payroll_info['payroll_id'], true));
            }
        }
        $this->response->redirect($this->url->link('extension/module/inventory_module/payroll', 'user_token=' . $this->session->data['user_token'], true));
    }
    
    public function pay() {
        $this->load->model('extension/module/inventory_module/payroll');
        $json = array();
    
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['payroll_details_id'])) {
            $id = (int)$this->request->post['payroll_details_id'];
            $check_query = $this->model_extension_module_inventory_module_payroll->checkPaidStatus($id);
            
            if ($check_query->row && $check_query->row['status'] == 1) {
                $json['error'] = "This employee has already been marked as paid.";
            } else {
                $this->model_extension_module_inventory_module_payroll->markPaid($id);
                $json['success'] = "Success: Payment for this employee has been recorded!";
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // --- EMPLOYEE SECTION ---
    
    public function employee() {
        $this->load->language('extension/module/inventory_module/payroll');
        $this->document->setTitle('Manage Employees');
        $this->load->model('extension/module/inventory_module/payroll');
        $this->load->model('tool/image');
        
        $user_token = $this->session->data['user_token'];
        $data['user_token'] = $user_token;
    
        $sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'name';
        $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';
        $url_order = ($order == 'ASC') ? 'DESC' : 'ASC';
    
        $url = '&user_token=' . $user_token;
        
        $data['sort_name']        = $this->url->link('extension/module/inventory_module/payroll/employee', $url . '&sort=name' . '&order=' . $url_order, true);
        $data['sort_designation'] = $this->url->link('extension/module/inventory_module/payroll/employee', $url . '&sort=designation' . '&order=' . $url_order, true);
        $data['sort_email']       = $this->url->link('extension/module/inventory_module/payroll/employee', $url . '&sort=email' . '&order=' . $url_order, true);
        $data['sort_mobile']      = $this->url->link('extension/module/inventory_module/payroll/employee', $url . '&sort=phone' . '&order=' . $url_order, true);
        $data['sort_salary']      = $this->url->link('extension/module/inventory_module/payroll/employee', $url . '&sort=salary' . '&order=' . $url_order, true);
        $data['sort_status']      = $this->url->link('extension/module/inventory_module/payroll/employee', $url . '&sort=status' . '&order=' . $url_order, true);
    
        $data['add'] = $this->url->link('extension/module/inventory_module/payroll/addEmployee', 'user_token=' . $user_token, true);
        $data['delete'] = $this->url->link('extension/module/inventory_module/payroll/deleteEmployee', 'user_token=' . $user_token, true);
        $data['export'] = $this->url->link('extension/module/inventory_module/payroll/export_employees', 'user_token=' . $user_token, true);

        $data['column_name'] = $this->language->get('column_name');
        $data['column_designation'] = $this->language->get('column_designation');
        $data['column_email'] = $this->language->get('column_email');
        $data['column_mobile'] = $this->language->get('column_mobile');
        $data['column_salary'] = $this->language->get('column_salary');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_action'] = $this->language->get('column_action');
    
        $filter_data = array(
            'sort'  => $sort,
            'order' => $order
        );
    
        $data['employees'] = array();
        $results = $this->model_extension_module_inventory_module_payroll->getEmployees($filter_data);
    
        foreach ($results as $result) {
            if (is_file(DIR_IMAGE . $result['image'])) {
                $image = $this->model_tool_image->resize($result['image'], 40, 40);
            } else {
                $image = $this->model_tool_image->resize('no_image.png', 40, 40);
            }
            
            $data['employees'][] = array(
                'emp_id'      => $result['emp_id'],
                'image'       => $image,
                'name'        => $result['name'],
                'designation' => $result['designation'],
                'email'       => $result['email'],
                'phone'       => $result['phone'],
                'salary'      => $this->currency->format($result['salary'], $this->config->get('config_currency')),
                'status'      => $result['status'],
                'edit'        => $this->url->link('extension/module/inventory_module/payroll/editEmployee', 'user_token=' . $user_token . '&emp_id=' . $result['emp_id'], true)
            );
        }
    
        $data['sort'] = $sort;
        $data['order'] = $order;
        
        $items = [
            'Payroll Manager' => 'extension/module/inventory_module/payroll',
            'Employees'       => 'extension/module/inventory_module/payroll/employee'
        ];
        $this->getBreadcrumbs($data, $items);
        $this->getMessages($data);
    
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['breadcrumb'] = $this->load->view('common/breadcrumb', $data);
        $data['messages'] = $this->load->view('common/messages', $data);
    
        $this->response->setOutput($this->load->view('extension/module/inventory_module/payroll/payroll_employee', $data));
    }

    public function addEmployee() {
        $this->load->language('extension/module/inventory_module/payroll');
        $this->load->model('extension/module/inventory_module/payroll');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateEmployee()) {
            $this->model_extension_module_inventory_module_payroll->addEmployee($this->request->post);
            $this->session->data['success'] = $this->language->get('text_employee_added');
            $this->response->redirect($this->url->link('extension/module/inventory_module/payroll/employee', 'user_token=' . $this->session->data['user_token'], true));
        }
        $this->getEmployeeForm();
    }

    public function editEmployee() {
        $this->load->language('extension/module/inventory_module/payroll');
        $this->load->model('extension/module/inventory_module/payroll');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateEmployee()) {
            $this->model_extension_module_inventory_module_payroll->editEmployee($this->request->get['emp_id'], $this->request->post);
            $this->session->data['success'] = $this->language->get('text_employee_updated');
            $this->response->redirect($this->url->link('extension/module/inventory_module/payroll/employee', 'user_token=' . $this->session->data['user_token'], true));
        }
        $this->getEmployeeForm();
    }
    
    public function deleteEmployee() {
        $this->load->language('extension/module/inventory_module/payroll');
        $this->load->model('extension/module/inventory_module/payroll');
    
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $emp_id) {
                $this->model_extension_module_inventory_module_payroll->deleteEmployee($emp_id);
            }
            $this->session->data['success'] = $this->language->get('text_employee_deleted');
        }
        else if (!isset($this->request->post['selected'])) {
            $this->session->data['error_warning'] = "Warning: Please select at least one employee to delete.";
        }
        $this->response->redirect($this->url->link('extension/module/inventory_module/payroll/employee', 'user_token=' . $this->session->data['user_token'], true));
    }
    
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/payroll')) {
            $this->error['warning'] = "Warning: You do not have permission to delete employees!";
        }
        return !$this->error;
    }

    protected function getEmployeeForm() {
        $this->load->model('tool/image');
        $user_token = $this->session->data['user_token'];
        $data['user_token'] = $user_token;

        $items = [
            $this->language->get('heading_title_payroll') => 'extension/module/inventory_module/payroll',
            $this->language->get('text_employee_list')     => 'extension/module/inventory_module/payroll/employee'
        ];

        if (isset($this->request->get['emp_id'])) {
            $emp_info = $this->model_extension_module_inventory_module_payroll->getEmployee($this->request->get['emp_id']);
            $items[$this->language->get('text_edit_employee')] = 'extension/module/inventory_module/payroll/editEmployee&emp_id=' . $this->request->get['emp_id'];
            $page_title = $this->language->get('text_edit_employee');
            $data['action'] = $this->url->link('extension/module/inventory_module/payroll/editEmployee', 'user_token=' . $user_token . '&emp_id=' . $this->request->get['emp_id'], true);
        } else {
            $emp_info = array();
            $items[$this->language->get('text_add_employee')] = 'extension/module/inventory_module/payroll/addEmployee';
            $page_title = $this->language->get('text_add_employee');
            $data['action'] = $this->url->link('extension/module/inventory_module/payroll/addEmployee', 'user_token=' . $user_token, true);
        }

        $this->document->setTitle($page_title);
        $data['heading_title'] = $page_title;
        $this->getBreadcrumbs($data, $items);
        $this->getMessages($data);

        $data['cancel'] = $this->url->link('extension/module/inventory_module/payroll/employee', 'user_token=' . $user_token, true);

        $data['name']         = $this->request->post['name'] ?? ($emp_info['name'] ?? '');
        $data['email']        = $this->request->post['email'] ?? ($emp_info['email'] ?? '');
        $data['phone']        = $this->request->post['phone'] ?? ($emp_info['phone'] ?? '');
        $data['dob']          = $this->request->post['dob'] ?? ($emp_info['dob'] ?? '');
        $data['gender']       = $this->request->post['gender'] ?? ($emp_info['gender'] ?? 'Male');
        $data['designation']  = $this->request->post['designation'] ?? ($emp_info['designation'] ?? '');
        $data['joining_date'] = $this->request->post['joining_date'] ?? ($emp_info['joining_date'] ?? date('Y-m-d'));
        $data['salary']       = $this->request->post['salary'] ?? ($emp_info['salary'] ?? '');
        $data['status']       = $this->request->post['status'] ?? ($emp_info['status'] ?? 1);
        
        $data['symbol_left'] = $this->currency->getSymbolLeft($this->config->get('config_currency'));
        $data['symbol_right'] = $this->currency->getSymbolRight($this->config->get('config_currency'));
        
        if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
            $data['image'] = $this->request->post['image'];
        } elseif (!empty($emp_info) && is_file(DIR_IMAGE . $emp_info['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($emp_info['image'], 100, 100);
            $data['image'] = $emp_info['image'];
        } else {
            $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
            $data['image'] = '';
        }
        
        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        $data['error_name']   = $this->error['name'] ?? '';
        $data['error_salary'] = $this->error['salary'] ?? '';
        $data['error_warning'] = $this->error['warning'] ?? '';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['breadcrumb'] = $this->load->view('common/breadcrumb', $data);
        $data['messages']   = $this->load->view('common/messages', $data);

        $this->response->setOutput($this->load->view('extension/module/inventory_module/payroll/payroll_employee_form', $data));
    }

	public function export() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/payroll')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/payroll');
		$this->load->model('extension/module/inventory_module/payroll');

		$filter_data = array(
			'sort'  => $this->request->get['sort'] ?? 'month_year',
			'order' => $this->request->get['order'] ?? 'DESC'
		);

		$results = $this->model_extension_module_inventory_module_payroll->getPayrolls($filter_data);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=payroll_history.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_month_year'),
			$this->language->get('column_net_salary'),
			$this->language->get('column_status'),
			$this->language->get('column_date_added')
		));

		foreach ($results as $result) {
			$month_name = date("F", mktime(0, 0, 0, $result['payroll_month'], 10));
			fputcsv($output, array(
				$month_name . ' ' . $result['payroll_year'],
				$this->currency->format($result['total_net_salary'], $this->config->get('config_currency'), '', false),
				$result['status'],
				($result['payroll_created_date'] && $result['payroll_created_date'] != '0000-00-00') ? date('d/m/Y', strtotime($result['payroll_created_date'])) : 'N/A'
			));
		}

		fclose($output);
		exit();
	}

	public function export_details() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/payroll')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/payroll');
		$this->load->model('extension/module/inventory_module/payroll');

		$payroll_id = $this->request->get['payroll_id'] ?? 0;

		$results = $this->model_extension_module_inventory_module_payroll->getPayrollDetails($payroll_id);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=payroll_details.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_name'),
			$this->language->get('column_basic'),
			'Bonus',
			'Deduction',
			$this->language->get('column_net_salary'),
			$this->language->get('column_status')
		));

		foreach ($results as $result) {
			fputcsv($output, array(
				$result['employee_name'],
				$this->currency->format($result['basic'], $this->config->get('config_currency'), '', false),
				$this->currency->format($result['bonus'], $this->config->get('config_currency'), '', false),
				$this->currency->format($result['deduction'], $this->config->get('config_currency'), '', false),
				$this->currency->format($result['net_salary'], $this->config->get('config_currency'), '', false),
				$result['status'] == 1 ? 'Paid' : 'Pending'
			));
		}

		fclose($output);
		exit();
	}

	public function export_employees() {
		if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/payroll')) {
			$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->language('extension/module/inventory_module/payroll');
		$this->load->model('extension/module/inventory_module/payroll');

		$filter_data = array(
			'sort'  => $this->request->get['sort'] ?? 'name',
			'order' => $this->request->get['order'] ?? 'ASC'
		);

		$results = $this->model_extension_module_inventory_module_payroll->getEmployees($filter_data);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=employee_list.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array(
			$this->language->get('column_name'),
			$this->language->get('column_designation'),
			$this->language->get('column_email'),
			$this->language->get('column_mobile'),
			$this->language->get('column_salary'),
			$this->language->get('column_status')
		));

		foreach ($results as $result) {
			fputcsv($output, array(
				$result['name'],
				$result['designation'],
				$result['email'],
				$result['phone'],
				$this->currency->format($result['salary'], $this->config->get('config_currency'), '', false),
				$result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')
			));
		}

		fclose($output);
		exit();
	}

    protected function validateEmployee() {
        if (!$this->user->hasPermission('modify', 'extension/module/inventory_module/payroll')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 100)) {
            $this->error['name'] = $this->language->get('error_name');
        }
        if (!filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL) && !empty($this->request->post['email'])) {
            $this->error['warning'] = "Invalid Email Format!";
        }
        if ((float)$this->request->post['salary'] <= 0) {
            $this->error['salary'] = $this->language->get('error_salary');
        }
        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }
        return !$this->error;
    }

}