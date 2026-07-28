<?php
class ControllerExtensionModuleQA extends Controller {
	private $error = array();

	public function question($product_id = null, $page = 1, $ajax = false, $per_page = null) {
		$this->load->helper('qa');
		if (!(int)$this->config->get('module_qa_display_questions') || !in_array($this->config->get('config_store_id'), bdecode($this->config->get('module_qa_as')))) {
			return;
		}
		
		 $product_id = $this->request->get['product_id'];
		
		$data['product_id'] = $product_id;

		$this->load->language('extension/module/qa');
		$this->load->model('extension/module/qa');

		$data['module_qa_display_question_author'] = $this->config->get('module_qa_display_question_author');
		$data['module_qa_display_question_date'] = $this->config->get('module_qa_display_question_date');
		$data['module_qa_display_answer_author'] = $this->config->get('module_qa_display_answer_author');
		$data['module_qa_display_answer_date'] = $this->config->get('module_qa_display_answer_date');

		if (is_array($product_id) && count($product_id) == 3) {
			list($product_id, $page, $ajax) = $product_id;
		} else if (is_array($product_id) && count($product_id) == 4) {
			list($product_id, $page, $ajax, $per_page) = $product_id;
		}

		if ($ajax) {
			if (isset($this->request->get['page'])) {
				$page = $this->request->get['page'];
			} else {
				$page = 1;
			}

			$product_id = $this->request->get['product_id'];
		}

		$data['qas'] = array();
		if (is_null($per_page)) {
			$per_page = (int)$this->config->get('module_qa_items_per_page');
		}

		$results = $this->model_extension_module_qa->getQuestionsByProductId($product_id, ($page - 1) * $per_page, $per_page);

		foreach ($results as $result) {
			$data['qas'][] = array(
				'q_author'   => $result['question_author_name'],
				'a_author'   => $result['answer_author_name'],
				'question'   => html_entity_decode($result['question']),
				'answer'     => html_entity_decode($result['answer']),
				'date_asked' => date($this->language->get('date_format_short'), strtotime($result['date_asked'])),
				'date_answered' => date($this->language->get('date_format_short'), strtotime($result['date_answered']))
			);
		  }

		$qa_total = $this->model_extension_module_qa->getTotalQuestionsByProductId($product_id);

		$limit = ($per_page) ? $per_page : $qa_total;

		$pagination = new Pagination();
		$pagination->total = $qa_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('extension/module/qa/question', 'product_id=' . $product_id . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($qa_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($qa_total - $limit)) ? $qa_total : ((($page - 1) * $limit) + $limit), $qa_total, ($limit > 0) ? ceil($qa_total / $limit) : 0);
        
        //new for FAQ form
        $custom_field_names = $this->config->get('module_qa_form_custom_field_name');
		$data['entry_custom'] = $custom_field_names[$this->config->get('config_language_id')] . ':';

		$data['module_qa_display_questions'] = $this->config->get('module_qa_display_questions');
		$data['module_qa_form_display_name'] = $this->config->get('module_qa_form_display_name');
		$data['module_qa_form_require_name'] = $this->config->get('module_qa_form_require_name');
		$data['module_qa_form_display_email'] = $this->config->get('module_qa_form_display_email');
		$data['module_qa_form_require_email'] = $this->config->get('module_qa_form_require_email');
		$data['module_qa_form_display_phone'] = $this->config->get('module_qa_form_display_phone');
		$data['module_qa_form_require_phone'] = $this->config->get('module_qa_form_require_phone');
		$data['module_qa_form_display_custom_field'] = $this->config->get('module_qa_form_display_custom_field');
		$data['module_qa_form_require_custom_field'] = $this->config->get('module_qa_form_require_custom_field');

		$data['module_qa_form_display_captcha'] = (int)$this->config->get('module_qa_form_display_captcha') && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('qa', (array)$this->config->get('config_captcha_page'));

		if ($data['module_qa_form_display_captcha']) {
			$data['qa_captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
		} else {
			$data['qa_captcha'] = '';
		}
		$data['module_qa_form_require_captcha'] = $this->config->get('module_qa_form_require_captcha');
        //form end 
        
		$template = 'extension/module/qa';
		
		if ($ajax) {
			$this->response->setOutput($this->load->view($template, $data));
		} else {
			return $this->load->view($template, $data);
		}
	}

	public function ask() {
		$this->load->helper('qa');

		if (!in_array($this->config->get('config_store_id'), bdecode($this->config->get('module_qa_as')))) {
			return;
		}

		$this->load->language('extension/module/qa');
		$this->load->model('extension/module/qa');

		$json = array();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateQuestion($this->request->post)) {
			$this->model_extension_module_qa->addQuestion($this->request->get['product_id'], $this->request->post);

			$json['success'] = $this->language->get('text_success_question');
		} else {
			$json['error'] = $this->error['message'];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_enc($json));
	}

	private function validateQuestion($data) {
		$errors = false;

		if ((int)$this->config->get('module_qa_form_display_name') && (int)$this->config->get('module_qa_form_require_name') && (!isset($data['q_name']) || utf8_strlen($data['q_name']) < 1)) {
			$errors = true;
			$this->error['message'] = $this->language->get('error_q_author');
		}

		if ((int)$this->config->get('module_qa_form_display_email') && (int)$this->config->get('module_qa_form_require_email') && (!isset($data['q_email']) || !validate_email(utf8_decode($data['q_email']))) || (!empty($data['q_email']) && !validate_email(utf8_decode($data['q_email'])))) {
			$errors = true;
			$this->error['message'] = $this->language->get('error_q_email');
		}

		if ((int)$this->config->get('module_qa_form_display_phone') && (int)$this->config->get('module_qa_form_require_phone') && (!isset($data['q_phone']) || utf8_strlen($data['q_phone']) < 5)) {
			$errors = true;
			$this->error['message'] = $this->language->get('error_q_phone');
		}

		if ((int)$this->config->get('module_qa_form_display_custom_field') && (int)$this->config->get('module_qa_form_require_custom_field') && (!isset($data['q_custom']) || utf8_strlen($data['q_custom']) < 1)) {
			$errors = true;
			$custom_field_names = $this->config->get('module_qa_form_custom_field_name');
			$this->error['message'] = sprintf($this->language->get('error_q_custom'), $custom_field_names[$this->config->get('config_language_id')]);
		}

		if (!isset($data['q_question']) || (utf8_strlen($data['q_question']) < 15) || (utf8_strlen($data['q_question']) > 1000)) {
			$errors = true;
			$this->error['message'] = $this->language->get('error_q_question');
		}

		if (!$errors && (int)$this->config->get('module_qa_form_display_captcha') && (int)$this->config->get('module_qa_form_require_captcha') && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('qa', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

			if ($captcha) {
				$errors = true;
				$this->error['message'] = $captcha;
			}
		}

		if (!$errors) {
			return true;
		} else {
			return false;
		}
	}
}
