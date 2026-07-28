<?php
class ControllerExtensionDashboardQA extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/dashboard/qa');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('dashboard_qa', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/dashboard/qa', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/dashboard/qa', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true);

		if (isset($this->request->post['dashboard_qa_width'])) {
			$data['dashboard_qa_width'] = $this->request->post['dashboard_qa_width'];
		} else {
			$data['dashboard_qa_width'] = $this->config->get('dashboard_qa_width');
		}

		$data['columns'] = array();

		for ($i = 3; $i <= 12; $i++) {
			$data['columns'][] = $i;
		}

		if (isset($this->request->post['dashboard_qa_status'])) {
			$data['dashboard_qa_status'] = $this->request->post['dashboard_qa_status'];
		} else {
			$data['dashboard_qa_status'] = $this->config->get('dashboard_qa_status');
		}

		if (isset($this->request->post['dashboard_qa_sort_order'])) {
			$data['dashboard_qa_sort_order'] = $this->request->post['dashboard_qa_sort_order'];
		} else {
			$data['dashboard_qa_sort_order'] = $this->config->get('dashboard_qa_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$template = 'extension/dashboard/qa_form';

		$this->response->setOutput($this->load->view($template, $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/dashboard/qa')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function dashboard() {
		if (!((int)$this->config->get('module_qa_status') && (int)$this->config->get('dashboard_qa_status'))) {
			return;
		}
		$this->load->language('extension/dashboard/qa');

		$data['user_token'] = $this->session->data['user_token'];

		// Total Questions
		$this->load->model('extension/module/qa');

		$today = $this->model_extension_module_qa->getTotalQuestions(array('filter_date_asked' => date('Y-m-d', strtotime('-1 day'))));

		$yesterday = $this->model_extension_module_qa->getTotalQuestions(array('filter_date_asked' => date('Y-m-d', strtotime('-2 day'))));

		$difference = $today - $yesterday;

		if ($difference && $today) {
			$data['percentage'] = round(($difference / $today) * 100);
		} else {
			$data['percentage'] = 0;
		}

		$qa_total = $this->model_extension_module_qa->getTotalQuestions();

		if ($qa_total > 1000000000000) {
			$data['total'] = round($qa_total / 1000000000000, 1) . 'T';
		} elseif ($qa_total > 1000000000) {
			$data['total'] = round($qa_total / 1000000000, 1) . 'B';
		} elseif ($qa_total > 1000000) {
			$data['total'] = round($qa_total / 1000000, 1) . 'M';
		} elseif ($qa_total > 1000) {
			$data['total'] = round($qa_total / 1000, 1) . 'K';
		} else {
			$data['total'] = $qa_total;
		}

		$data['qa'] = $this->url->link('extension/module/qa/view', 'user_token=' . $this->session->data['user_token'], true);

		$template = 'extension/dashboard/qa_info';

		return $this->load->view($template, $data);
	}
}
