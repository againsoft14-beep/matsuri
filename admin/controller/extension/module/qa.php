<?php
defined('EXTENSION_NAME')			|| define('EXTENSION_NAME',            'Product Questions &amp; Answers');
defined('EXTENSION_VERSION')		|| define('EXTENSION_VERSION',         '2.0.7');
defined('EXTENSION_ID')				|| define('EXTENSION_ID',              '1328');
defined('EXTENSION_COMPATIBILITY')	|| define('EXTENSION_COMPATIBILITY',   'OpenCart 3.0.0.x, 3.0.1.x, 3.0.2.x and 3.0.3.x');
defined('EXTENSION_STORE_URL')		|| define('EXTENSION_STORE_URL',       'https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=' . EXTENSION_ID);
defined('EXTENSION_PURCHASE_URL')	|| define('EXTENSION_PURCHASE_URL',    'https://www.opencart.com/index.php?route=marketplace/purchase&extension_id=' . EXTENSION_ID);
defined('EXTENSION_RATE_URL')		|| define('EXTENSION_RATE_URL',        'https://www.opencart.com/index.php?route=account/rating/add&extension_id=' . EXTENSION_ID);
defined('EXTENSION_SUPPORT_EMAIL')	|| define('EXTENSION_SUPPORT_EMAIL',   'support@opencart.ee');
defined('EXTENSION_SUPPORT_LINK')	|| define('EXTENSION_SUPPORT_LINK',    'https://www.opencart.com/index.php?route=support/seller&extension_id=' . EXTENSION_ID);
defined('EXTENSION_SUPPORT_FORUM')	|| define('EXTENSION_SUPPORT_FORUM',   'https://forum.opencart.com/viewtopic.php?f=123&t=25969');
defined('OTHER_EXTENSIONS')			|| define('OTHER_EXTENSIONS',          'https://www.opencart.com/index.php?route=marketplace/extension&filter_member=bull5-i');

class ControllerExtensionModuleQA extends Controller {
	private $error = array();
	protected $alert = array(
		'error'     => array(),
		'warning'   => array(),
		'success'   => array(),
		'info'      => array()
	);

	private static $config_defaults = array(
		// (General)
		'module_qa_installed'                      => 1,
		'module_qa_installed_version'              => EXTENSION_VERSION,
		'module_qa_status'                         => 0,
		// 'module_qa_dashboard_widget'               => 0,
		'module_qa_new_question_notification'      => 1,
		'module_qa_question_reply_notification'    => 1,
		'module_qa_notification_emails'            => array(),
		'module_qa_notification_from'              => 0,
		'module_qa_remove_sql_changes'             => 1,
		'module_qa_services'                       => "W10=",
		'module_qa_as'                             => "WyIwIl0=",
		// Question form (Form)
		'module_qa_form_display_name'              => 1,
		'module_qa_form_require_name'              => 1,
		'module_qa_form_display_email'             => 1,
		'module_qa_form_require_email'             => 0,
		'module_qa_form_display_phone'             => 0,
		'module_qa_form_require_phone'             => 0,
		'module_qa_form_display_custom_field'      => 0,
		'module_qa_form_require_custom_field'      => 0,
		'module_qa_form_custom_field_name'         => array(),
		'module_qa_form_display_captcha'           => 1,
		'module_qa_form_require_captcha'           => 1,
		// Questions & answers (Q&A)
		'module_qa_display_questions'              => 1,
		'module_qa_display_all_languages'          => 1,
		'module_qa_new_question_status'            => 0,
		'module_qa_items_per_page'                 => 5,
		'module_qa_preload'                        => 0,
		'module_qa_display_question_author'        => 1,
		'module_qa_display_question_date'          => 1,
		'module_qa_display_answer_author'          => 0,
		'module_qa_display_answer_date'            => 0,
	);

	private static $event_hooks = array(
		'admin_module_qa_language_add'    => array('trigger' => 'admin/model/localisation/language/addLanguage/after',    'action' => 'extension/module/qa/language_add_hook'),
	);

	private $columns = array(
		'selector'              => array('display' => 1, 'index' =>  0, 'align' => 'text-center', 'sort' => '',                         'class'=>          '', ),
		'id'                    => array('display' => 0, 'index' =>  1, 'align' =>   'text-left', 'sort' => 'q.qa_id',                  'class'=>          '', ),
		'product'               => array('display' => 1, 'index' =>  5, 'align' =>   'text-left', 'sort' => 'pd.name',                  'class'=>          '', ),
		'question_author_name'  => array('display' => 1, 'index' => 10, 'align' =>   'text-left', 'sort' => 'question_author_name',     'class'=>'visible-sm visible-md visible-lg', ),
		'question_author_email' => array('display' => 0, 'index' => 10, 'align' =>   'text-left', 'sort' => 'question_author_name',     'class'=>'visible-sm visible-md visible-lg', ),
		'question_author_phone' => array('display' => 0, 'index' => 10, 'align' =>   'text-left', 'sort' => 'question_author_name',     'class'=>'visible-sm visible-md visible-lg', ),
		'question_author_custom'=> array('display' => 0, 'index' => 10, 'align' =>   'text-left', 'sort' => 'question_author_name',     'class'=>'visible-sm visible-md visible-lg', ),
		'question'              => array('display' => 1, 'index' => 15, 'align' =>   'text-left', 'sort' => '',                         'class'=>           '', ),
		'answer'                => array('display' => 1, 'index' => 20, 'align' =>   'text-left', 'sort' => '',                         'class'=>'visible-md visible-lg', ),
		'answer_author_name'    => array('display' => 1, 'index' => 25, 'align' =>   'text-left', 'sort' => 'answer_author_name',       'class'=>'visible-xl', ),
		'language'              => array('display' => 0, 'index' => 30, 'align' =>   'text-left', 'sort' => 'l.name',                   'class'=>          '', ),
		'date_asked'            => array('display' => 1, 'index' => 35, 'align' =>   'text-left', 'sort' => 'date_asked',               'class'=>'visible-lg', ),
		'date_answered'         => array('display' => 1, 'index' => 40, 'align' =>   'text-left', 'sort' => 'date_answered',            'class'=>'visible-xl', ),
		'date_modified'         => array('display' => 0, 'index' => 45, 'align' =>   'text-left', 'sort' => 'date_modified',            'class'=>          '', ),
		'store'                 => array('display' => 0, 'index' => 50, 'align' =>   'text-left', 'sort' => '',                         'class'=>'visible-md visible-lg', ),
		'status'                => array('display' => 1, 'index' => 55, 'align' => 'text-center', 'sort' => 'status',                   'class'=>          '', ),
		'action'                => array('display' => 1, 'index' => 60, 'align' =>  'text-right', 'sort' => '',                         'class'=>          '', ),
	);

	public function __construct($registry) {
		parent::__construct($registry);
		$this->config->load('qa');
	}

	public function index() {
		$this->load->helper('qa');
		$this->load->language('extension/module/qa');
		$this->load->model('extension/module/qa');

		$this->document->setTitle($this->language->get('extension_name'));

		$this->load->model('setting/setting');

		$ajax_request = isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && !$ajax_request && $this->validateForm($this->request->post)) {
			$original_settings = $this->model_setting_setting->getSetting('module_qa');

			foreach (self::$config_defaults as $setting => $default) {
				$value = $this->config->get($setting);
				if ($value === null) {
					$original_settings[$setting] = $default;
				}
			}

			$settings = array_merge($original_settings, $this->request->post);
			$settings['module_qa_installed_version'] = $original_settings['module_qa_installed_version'];

			$this->model_setting_setting->editSetting('module_qa', $settings);

			$this->session->data['success'] = $this->language->get('text_success_update');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		} else if ($this->request->server['REQUEST_METHOD'] == 'POST' && $ajax_request) {
			$response = array();

			if ($this->validateForm($this->request->post)) {
				$original_settings = $this->model_setting_setting->getSetting('module_qa');

				foreach (self::$config_defaults as $setting => $default) {
					$value = $this->config->get($setting);
					if ($value === null) {
						$original_settings[$setting] = $default;
					}
				}

				if ((int)$original_settings['module_qa_status'] != (int)$this->request->post['module_qa_status']) {
					$response['reload'] = true;
					$this->session->data['success'] = $this->language->get('text_success_update');
				}

				$settings = array_merge($original_settings, $this->request->post);
				$settings['module_qa_installed_version'] = $original_settings['module_qa_installed_version'];

				$this->model_setting_setting->editSetting('module_qa', $settings);

				$this->alert['success']['updated'] = $this->language->get('text_success_update');
			} else {
				if (!$this->checkVersion()) {
					$response['reload'] = true;
				}
			}

			$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_enc($response, JSON_UNESCAPED_SLASHES));
			return;
		}

		$data['heading_title'] = $this->language->get('extension_name');
		$data['text_other_extensions'] = sprintf($this->language->get('text_other_extensions'), OTHER_EXTENSIONS);

		$data['ext_name'] = EXTENSION_NAME;
		$data['ext_version'] = EXTENSION_VERSION;
		$data['ext_id'] = EXTENSION_ID;
		$data['ext_compatibility'] = EXTENSION_COMPATIBILITY;
		$data['ext_store_url'] = EXTENSION_STORE_URL;
		$data['ext_rate_url'] = EXTENSION_RATE_URL;
		$data['ext_purchase_url'] = EXTENSION_PURCHASE_URL;
		$data['ext_support_email'] = EXTENSION_SUPPORT_EMAIL;
		$data['ext_support_link'] = EXTENSION_SUPPORT_LINK;
		$data['ext_support_forum'] = EXTENSION_SUPPORT_FORUM;
		$data['other_extensions_url'] = OTHER_EXTENSIONS;
		$data['oc_version'] = VERSION;
		$data['php_version'] = phpversion();
		$data['installed_extensions'] = (array)$this->config->get('qa_extensions');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'active'    => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_extension'),
			'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
			'active'    => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('extension_name'),
			'href'      => $this->url->link('extension/module/qa', 'user_token=' . $this->session->data['user_token'], true),
			'active'    => true
		);

		$data['save'] = $this->url->link('extension/module/qa', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		$data['upgrade'] = $this->url->link('extension/module/qa/upgrade', 'user_token=' . $this->session->data['user_token'], true);
		$data['extension_installer'] = $this->url->link('marketplace/installer', 'user_token=' . $this->session->data['user_token'], true);
		$data['modifications'] = $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'], true);
		$data['events'] = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'], true);
		$data['services'] = html_entity_decode($this->url->link('extension/module/qa/services', 'user_token=' . $this->session->data['user_token'], true));

		if (!$this->checkPrerequisites()) {
			$this->showErrorPage($data);
			return;
		}

		$db_structure_ok = $this->checkVersion() && $this->model_extension_module_qa->checkDatabaseStructure($this->alert);

		$this->checkVersion(true);

		$this->alert = array_merge($this->alert, $this->model_extension_module_qa->getAlerts());

		$data['update_pending'] = !$this->checkVersion();

		if (!$data['update_pending']) {
			$this->updateEventHooks();
		}

		$data['ssl'] = (
				(int)$this->config->get('config_secure') ||
				isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ||
				!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ||
				!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'
			) ? 's' : '';

		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();
		foreach ($languages as $key => $value) {
			unset($languages[$key]['image']);
		}

		$data['multilingual'] = count($languages) > 1;
		$data['languages'] = array_remap_key_to_id('language_id', $languages);
		$data['default_language'] = $this->config->get('config_language_id');

		$data['installed_version'] = $this->installedVersion();

		# Loop through all settings for the post/config values
		foreach (array_keys(self::$config_defaults) as $setting) {
			if (isset($this->request->post[$setting])) {
				$data[$setting] = $this->request->post[$setting];
			} else {
				$data[$setting] = $this->config->get($setting);
				if ($data[$setting] === null) {
					if (!isset($this->alert['warning']['unsaved']) && $this->checkVersion())  {
						$this->alert['warning']['unsaved'] = $this->language->get('error_unsaved_settings');
					}
					if (isset(self::$config_defaults[$setting])) {
						$data[$setting] = self::$config_defaults[$setting];
					}
				}
			}
		}

		$this->load->model('setting/store');

		$stores = $this->model_setting_store->getStores();

		$data['stores'] = array();

		$data['stores'][0] = array(
			'name' => $this->config->get('config_name'),
			'url'  => HTTP_CATALOG
		);

		foreach ($stores as $store) {
			$data['stores'][$store['store_id']] = array(
				'name' => $store['name'],
				'url'  => $store['url']
			);
		}

		$this->load->model('setting/extension');
		$installed_dashboards = $this->model_setting_extension->getInstalled('dashboard');
		if (in_array('qa', $installed_dashboards)) {
			$data['dashboard_widget'] = array(
				'class'=> 'btn-default btn-nav-link',
				'icon' => 'fa-cog',
				'name' => $this->language->get('button_configure'),
				'loading' => $this->language->get('text_opening'),
				'link' => $this->url->link('extension/dashboard/qa', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['dashboard_widget'] = array(
				'class'=> 'btn-success btn-install',
				'icon' => 'fa-magic',
				'name' => $this->language->get('button_install'),
				'loading' => $this->language->get('text_installing'),
				'link' => $this->url->link('extension/module/qa/install_dashboard', 'user_token=' . $this->session->data['user_token'], true)
			);
		}

		if (isset($this->session->data['error'])) {
			$this->error = $this->session->data['error'];

			unset($this->session->data['error']);
		}

		if (isset($this->error['warning'])) {
			$this->alert['warning']['warning'] = $this->error['warning'];
		}

		if (isset($this->error['error'])) {
			$this->alert['error']['error'] = $this->error['error'];
		}

		if (isset($this->session->data['success'])) {
			$this->alert['success']['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		}

		$this->document->addStyle('view/stylesheet/qa/custom.min.css?v=' . EXTENSION_VERSION);

		$this->document->addScript('view/javascript/qa/custom.min.js?v=' . EXTENSION_VERSION);

		$data['errors'] = $this->error;

		$data['user_token'] = $this->session->data['user_token'];

		$data['alerts'] = $this->alert;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$template = 'extension/module/qa';

		$this->response->setOutput($this->load->view($template, $data));
	}

	public function install_dashboard() {
		$this->load->helper('qa');
		$this->load->language('extension/module/qa');

		$ajax_request = isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

		$response = array();

		if ($this->validateDashboardInstall()) {
			$this->load->model('setting/extension');

			$this->model_setting_extension->install('dashboard', 'qa');

			$this->load->model('user/user_group');

			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/dashboard/qa');
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/dashboard/qa');

			// Call install method if it exsits
			$this->load->controller('extension/dashboard/qa/install');

			$this->alert['success']['install'] = $this->language->get('text_success_install_dashboard');
			$response['url'] = html_entity_decode($this->url->link('extension/module/qa', 'user_token=' . $this->session->data['user_token'], true));
		}

		$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

		if (!$ajax_request) {
			$this->session->data['errors'] = $this->error;
			$this->session->data['alerts'] = $this->alert;
			$this->response->redirect($this->url->link('extension/module/qa', 'user_token=' . $this->session->data['user_token'], true));
		} else {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_enc($response, JSON_UNESCAPED_SLASHES));
		}
	}

	public function install() {
		$this->load->helper('qa');
		$this->load->language('extension/module/qa');
		$this->load->model('extension/module/qa');

		$this->model_extension_module_qa->applyDatabaseChanges();

		$this->registerEventHooks();

		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();
		$languages = array_remap_key_to_id('language_id', $languages);
		foreach ($languages as $language_id => $language) {
			self::$config_defaults['module_qa_notification_emails'][$language_id] = $this->config->get('config_email');
		}

		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('module_qa', self::$config_defaults);
	}

	public function uninstall() {
		$this->load->helper('qa');
		$this->load->language('extension/module/qa');
		$this->load->model('extension/module/qa');

		$this->removeEventHooks();

		if ($this->config->get('module_qa_remove_sql_changes')) {
			$this->model_extension_module_qa->revertDatabaseChanges();
		}

		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_qa');
	}

	public function upgrade() {
		$this->load->helper('qa');
		$this->load->language('extension/module/qa');
		$this->load->model('extension/module/qa');

		$ajax_request = isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateUpgrade()) {
			$this->load->model('setting/setting');

			if ($this->model_extension_module_qa->upgradeDatabaseStructure($this->installedVersion(), $this->alert)) {
				$settings = array();

				// Go over all settings, add new values and remove old ones
				foreach (self::$config_defaults as $setting => $default) {
					$value = $this->config->get($setting);
					if ($value === null) {
						$settings[$setting] = $default;
					} else {
						$settings[$setting] = $value;
					}
				}

				if (!is_array($settings['module_qa_notification_emails'])) {
					$email = $settings['module_qa_notification_emails'];
					$settings['module_qa_notification_emails'] = array();

					$this->load->model('localisation/language');

					$languages = $this->model_localisation_language->getLanguages();
					foreach ($languages as $key => $value) {
						unset($languages[$key]['image']);
					}
					$languages = array_remap_key_to_id('language_id', $languages);

					foreach ($languages as $language_id => $language) {
						$settings['module_qa_notification_emails'][$language_id] = $email ? $email : $this->config->get('config_email');
					}
				}

				$settings['module_qa_installed_version'] = EXTENSION_VERSION;

				$this->model_setting_setting->editSetting('module_qa', $settings);

				$this->session->data['success'] = sprintf($this->language->get('text_success_upgrade'), EXTENSION_VERSION);
				$this->alert['success']['upgrade'] = sprintf($this->language->get('text_success_upgrade'), EXTENSION_VERSION);

				$response['success'] = true;
				$response['reload'] = true;
			} else {
				$this->alert = array_merge($this->alert, $this->model_extension_module_qa->getAlerts());
				$this->alert['error']['database_upgrade'] = $this->language->get('error_upgrade_database');
			}
		}

		$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

		if (!$ajax_request) {
			$this->session->data['errors'] = $this->error;
			$this->session->data['alerts'] = $this->alert;
			$this->response->redirect($this->url->link('extension/module/qa', 'user_token=' . $this->session->data['user_token'], true));
		} else {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_enc($response, JSON_UNESCAPED_SLASHES));
			return;
		}
	}

	public function services() {
		$this->load->helper('qa');
		$this->load->language('extension/module/qa');
		$this->load->model('extension/module/qa');

		$services = base64_decode($this->config->get('module_qa_services'));
		$response = json_decode($services, true);
		$force = isset($this->request->get['force']) && (int)$this->request->get['force'];

		if ($response && isset($response['expires']) && $response['expires'] >= strtotime("now") && !$force) {
			$response['cached'] = true;
		} else {
			$url = "https://www.opencart.ee/services/?eid=" . EXTENSION_ID . "&info=true&general=true&currency=" . $this->config->get('config_currency');
			$hostname = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '' ;

			if (function_exists('curl_init')) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_USERAGENT, base64_encode("curl " . EXTENSION_ID));
				curl_setopt($ch, CURLOPT_REFERER, $hostname);
				$json = curl_exec($ch);
			} else {
				$json = false;
			}

			if ($json !== false) {
				$this->load->model('setting/setting');
				$settings = $this->model_setting_setting->getSetting('module_qa');
				$settings['module_qa_services'] = base64_encode($json);
				$this->model_setting_setting->editSetting('module_qa', $settings);
				$response = json_decode($json, true);
			} else {
				$response = array();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_enc($response, JSON_UNESCAPED_SLASHES));
	}

	// QA page
	public function view() {
		$this->getList();
	}

	public function delete() {
		$this->action('delete');
	}

	public function copy() {
		$this->action('copy');
	}

	public function add() {
		$this->action('add');
	}

	public function edit() {
		$this->action('edit');
	}

	public function autocomplete() {
		$this->load->helper('qa');
		if ($this->request->server['REQUEST_METHOD'] == 'GET' && isset($this->request->get['type'])) {
			$resp = array();
			switch ($this->request->get['type']) {
				case 'product':
					$this->load->model('catalog/product');

					$results = array();

					if (isset($this->request->get['query'])) {
						$data = array(
							'filter_name'   => $this->request->get['query'],
							'sort'          => 'pd.name',
							'start'         => 0,
							'limit'         => 20,
						);

						$results = $this->model_catalog_product->getProducts($data);
					}

					foreach ($results as $result) {
						$result['name'] = html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8');
						$resp[] = array(
							'value'     => $result['name'],
							'tokens'    => explode(' ', $result['name']),
							'id'        => $result['product_id'],
							'model'     => $result['model']
						);
					}
					break;
				case 'customer':
					$this->load->model('customer/customer');

					$results = array();

					if (isset($this->request->get['query'])) {
						$data = array(
							'filter_name'   => $this->request->get['query'],
							'sort'          => 'name',
							'start'         => 0,
							'limit'         => 20,
						);

						$results = $this->model_customer_customer->getCustomers($data);
					}

					foreach ($results as $result) {
						$result['name'] = html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8');
						$resp[] = array(
							'value'     => $result['name'],
							'tokens'    => explode(' ', $result['name']),
							'id'        => $result['customer_id'],
							'email'     => $result['email'],
							'phone'     => $result['telephone']
						);
					}
					break;
				default:
					break;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_enc($resp, JSON_UNESCAPED_SLASHES));
	}

	// Event hooks
	public function language_add_hook($route='', $data=array(), $output=null) {
		$language_id = (int)$output;

		if ($language_id && $this->config->get('module_qa_installed')) {
			$this->load->model('setting/setting');

			$qa_settings = $this->model_setting_setting->getSetting('module_qa');

			if (isset($qa_settings['module_qa_form_custom_field_name'][$this->config->get('config_language_id')])) {
				$qa_settings['module_qa_form_custom_field_name'][$language_id] = $qa_settings['module_qa_form_custom_field_name'][$this->config->get('config_language_id')];
			} else {
				$qa_settings['module_qa_form_custom_field_name'][$language_id] = '';
			}

			if (isset($qa_settings['module_qa_notification_emails'][$this->config->get('config_language_id')])) {
				$qa_settings['module_qa_notification_emails'][$language_id] = $qa_settings['module_qa_notification_emails'][$this->config->get('config_language_id')];
			} else {
				$qa_settings['module_qa_notification_emails'][$language_id] = $this->config->get('config_email');
			}

			$this->model_setting_setting->editSetting('module_qa', $qa_settings);
		}
	}

	// Protected methods
	protected function notify($question_id, $data) {
		if (isset($data['notify_customer']) && (int)$data['notify_customer'] && $data['question'] != "" && $data['answer'] != "" && $data['question_author_email'] != "") {
			$l_query = $this->db->query("SELECT language_id, code FROM " . DB_PREFIX . "language WHERE language_id = '" . $data['language_id'] . "'");
			$language = new Language($l_query->row['code']);
			$language->load($l_query->row['code']);
			$language->load('extension/module/qa_question_reply');

			// Get product info
			$p_query = $this->db->query("SELECT p.model AS model, pd.name AS name FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$data['product_id'] . "' AND pd.language_id = '" . $l_query->row['language_id'] . "'");

			$config = new Config();

			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$data['store_id'] . "'");

			foreach ($query->rows as $setting) {
				if (!$setting['serialized']) {
					$config->set($setting['key'], $setting['value']);
				} else {
					$config->set($setting['key'], json_decode($setting['value'], true));
				}
			}

			if ((int)$data['store_id'] == 0) {
				$config->set('config_url', HTTP_CATALOG);
				$config->set('config_ssl', HTTPS_CATALOG);
			}

			$url = new Url($config->get('config_url'), $config->get('config_ssl'));
			$product_link = $url->link('product/product', 'product_id=' . $data['product_id']);

			if ($this->config->get('config_seo_url')) {
				require_once(DIR_CATALOG . 'controller/startup/seo_url.php');
				$seo_url = new ControllerStartupSeoUrl($this->registry);
				$product_link = $seo_url->rewrite($product_link);
			}

			$subject = sprintf($language->get('text_subject'), $config->get('config_name'));

			// HTML Mail
			$html_data['title'] = sprintf($language->get('text_subject'), html_entity_decode($config->get('config_name'), ENT_QUOTES, 'UTF-8'));

			$html_data['text_answered'] = sprintf($language->get('text_answered'), $product_link, $p_query->row['name']);
			$html_data['text_view'] = ((int)$data['status'] && (int)$this->config->get('module_qa_display_questions')) ? sprintf($language->get('text_view'), $product_link, $product_link) : '';
			$html_data['text_question_detail'] = $language->get('text_question_detail');
			$html_data['text_answer'] = $language->get('text_answer');
			$html_data['text_asked'] = sprintf($language->get('text_asked'), date($language->get('date_format_short'), strtotime($data['date_asked'])));
			$html_data['text_powered_by'] = $language->get('text_powered_by');
			$html_data['text_closing'] = $language->get('text_closing');
			$html_data['text_sender'] = sprintf($language->get('text_sender'), $config->get('config_name'));

			$html_data['store_name'] = $config->get('config_name');
			$html_data['store_url'] = $config->get('config_secure') ? $config->get('config_ssl') : $config->get('config_url');;
			$html_data['logo'] = ($config->get('config_secure') ? $config->get('config_ssl') : $config->get('config_url')) . 'image/' . $config->get('config_logo');
			$html_data['question'] = str_replace(array("\r\n", "\r", "\n"), '<br />', html_entity_decode($data['question'], ENT_QUOTES, 'UTF-8'));
			$html_data['answer'] = str_replace(array("\r\n", "\r", "\n"), '<br />', html_entity_decode($data['answer'], ENT_QUOTES, 'UTF-8'));

			// Text Mail
			$text  = sprintf(strip_tags($language->get('text_answered')), $p_query->row['name']) . "\n";
			if ((int)$data['status']) {
				$text .= sprintf(strip_tags($language->get('text_view')), $product_link) . "\n\n";
			}
			$text .= sprintf($language->get('text_asked'), date($language->get('date_format_short'), strtotime($data['date_asked']))) . "\n";
			$text .= strip_tags(html_entity_decode($data['question'], ENT_QUOTES, 'UTF-8')) . "\n\n";
			$text .= $language->get('text_answer') . "\n" . strip_tags(html_entity_decode($data['answer'], ENT_QUOTES, 'UTF-8')) . "\n\n";
			$text .= $language->get('text_closing') . "\n";
			$text .= sprintf($language->get('text_sender'), $config->get('config_name')) . "\n";

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
			$mail->setFrom($config->get('config_email'));
			$mail->setSender(html_entity_decode($config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));

			$template = 'extension/module/qa_question_reply';

			$mail->setHtml($this->load->view($template, $html_data));
			$mail->setText(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));

			$mail->setTo($data['question_author_email']);
			$mail->send();

			$this->model_extension_module_qa->updateNotificationSent($question_id, 1);
		}
	}

	protected function getList() {
		$this->load->helper('qa');
		$this->load->language('extension/module/qa');
		$this->load->model('extension/module/qa');

		if (isset($this->session->data['errors'])) {
			$this->error = array_merge($this->error, (array)$this->session->data['errors']);

			unset($this->session->data['errors']);
		}

		if (isset($this->session->data['alerts'])) {
			$this->alert = array_merge($this->alert, (array)$this->session->data['alerts']);

			unset($this->session->data['alerts']);
		}

		$this->document->addStyle('view/stylesheet/qa/custom.min.css?v=' . EXTENSION_VERSION);

		$this->document->addScript('view/javascript/qa/custom.min.js?v=' . EXTENSION_VERSION);

		$this->document->setTitle($this->language->get('heading_title_qa'));

		$data['heading_title'] = $this->language->get('heading_title_qa');

		$url = $this->urlParams();

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'active'    => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title_qa'),
			'href'      => $this->url->link('extension/module/qa/view', 'user_token=' . $this->session->data['user_token'], true),
			'active'    => true
		);

		$data['add'] = $this->url->link('extension/module/qa/add', $url, true);
		$data['copy'] = $this->url->link('extension/module/qa/copy', $url, true);
		$data['delete'] = $this->url->link('extension/module/qa/delete', $url, true);
		$data['filter'] = html_entity_decode($this->url->link('extension/module/qa/view', 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8');
		$data['autocomplete'] = html_entity_decode($this->url->link('extension/module/qa/autocomplete', 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8');
		$data['product'] = $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=', true);
		$data['customer'] = $this->url->link('customer/customer', 'user_token=' . $this->session->data['user_token'] . '&filter_name=', true);

		$this->load->model('setting/store');

		$multistore = $this->model_setting_store->getTotalStores();
		$data['multistore'] = $multistore;

		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();
		foreach ($languages as $key => $value) {
			unset($languages[$key]['image']);
		}
		$data['languages'] = $languages;
		$data['multilingual'] = count($languages) > 1;

		$columns = $this->columns;
		$filters = array();

		foreach ($columns as $column => $attr) {
			$columns[$column]['name'] = $this->language->get('column_' . $column);

			if (isset($this->request->get['filter_' . $column])) {
				$filters[$column] = $this->request->get['filter_' . $column];
			}

			if ($column == 'store' && !$multistore) {
				unset($columns[$column]);
			}
		}

		uasort($columns, 'column_sort');

		if ($multistore) {
			$columns['store']['display'] = 1;
		}

		$columns = array_filter($columns, 'column_display');

		$displayed_columns = array_keys($columns);

		$data['columns'] = $columns;
		$data['typeahead'] = array();

		foreach (array('product') as $column) {
			if (in_array($column, $displayed_columns)) {
				$url = $this->urlParams(0, 0, 0, 0, 0);
				$data['typeahead'][$column] = array(
					'remote' => html_entity_decode($this->url->link('extension/module/qa/autocomplete', 'type=' . $column . '&query=%QUERY' . $url, true))
				);
			}
		}

		if (in_array('store', $displayed_columns)) {
			$stores = $this->cache->get('store.all');

			if ($stores === false) {
				$_stores = $this->model_setting_store->getStores(array());

				$stores = array(
					'0' => array(
						'store_id'  => '0',
						'name'      => $this->config->get('config_name'),
						'url'       => HTTP_CATALOG
					)
				);

				foreach ($_stores as $store) {
					$stores[$store['store_id']] = array(
						'store_id'  => $store['store_id'],
						'name'      => $store['name'],
						'url'       => $store['url']
					);
				}

				$this->cache->set('store.all', $stores);
			}

			$data['stores'] = $stores;
		}

		if (isset($this->request->get['search'])) {
			$search = html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8');
		} else {
			$search = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'date_asked';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['questions'] = array();

		$filter_data = array(
			'columns'   => $displayed_columns,
			'search'    => $search,
			'filter'    => $filters,
			'sort'      => $sort,
			'order'     => $order,
			'start'     => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'     => $this->config->get('config_limit_admin')
		);

		$results = $this->model_extension_module_qa->getQuestions($filter_data);

		$filtered_total = $this->model_extension_module_qa->getFilteredTotalQuestions();
		$total = $this->model_extension_module_qa->getTotalQuestions();

		foreach ($results as $result) {
			$action = array();

			$action[] = array(
				'name'      => 'edit',
				'title'     => $this->language->get('text_edit'),
				'text'      => $this->language->get('text_edit_short'),
				'class'     => "btn-primary",
				'icon'      => 'pencil',
				'url'       => $this->url->link('extension/module/qa/edit', 'qa_id=' . $result['qa_id'] . $this->urlParams(), true)
			);

			$question = array(
				'qa_id'                 => $result['qa_id'],
				'question_author_email' => $result['question_author_email'],
				'question_author_phone' => $result['question_author_phone'],
				'question_author_custom'=> $result['question_author_custom'],
				'customer_id'           => $result['customer_id'],
				'language_id'           => $result['language_id'],
				'selected'              => isset($this->request->post['selected']) && in_array($result['qa_id'], $this->request->post['selected']),
			);

			foreach ($displayed_columns as $column) {
				switch ($column) {
					case 'action':
						$value = $action;
						break;
					case 'product':
						$value = $result['product'];
						$question['product_id'] = $result['product_id'];
						break;
					case 'date_asked':
					case 'date_answered':
						if ($result[$column] != null) {
							$date = new DateTime($result[$column]);
							$value = $date->format('Y-m-d');
						} else {
							$value = '';
						}
						break;
					case 'date_modified':
						if ($result[$column] != null) {
							$date = new DateTime($result[$column]);
							$value = $date->format('Y-m-d H:i:s');
						} else {
							$value = '';
						}
						break;
					case 'store':
						$value = $result['stores'];
						break;
					case 'language':
						$value = $result['language_name'];
						break;
					case 'question':
					case 'answer':
						$value = (utf8_strlen(strip_tags(html_entity_decode($result[$column], ENT_QUOTES, 'UTF-8'))) > 50) ? mb_substr(strip_tags(html_entity_decode($result[$column], ENT_QUOTES, 'UTF-8')), 0, 50) . ' ...' : strip_tags(html_entity_decode($result[$column], ENT_QUOTES, 'UTF-8'));
						$question[$column . '_full'] = str_replace('"', '&quot;', html_entity_decode($result[$column], ENT_QUOTES, 'UTF-8'));
						break;
					case 'question_author_name':
						$value = $result[$column];
						$details = '';
						if ($result['question_author_email']) {
							$details .= "<tr><td>" . $this->language->get('entry_email') . "</td><td>" . htmlentities($result['question_author_email']) . "</td></tr>";
						}
						if ($result['question_author_phone']) {
							$details .= "<tr><td>" . $this->language->get('entry_phone') . "</td><td>" . htmlentities($result['question_author_phone']) . "</td></tr>";
						}
						if ($result['question_author_custom']) {
							$field_names = $this->config->get('module_qa_form_custom_field_name');
							$field_name = isset($field_names[$this->config->get('config_language_id')]) ? $field_names[$this->config->get('config_language_id')] : $this->language->get('entry_custom');
							$details .= "<tr><td>" . $field_name  . "</td><td>" . htmlentities($result['question_author_custom']) . "</td></tr>";
						}
						if ($details) {
							$details = "<table class='table-condensed details'>" . $details . '</table>';
						}
						$question['author_details'] = $details;
						$question['customer_name'] = $result['customer_name'];
						break;
					case 'status':
						$value = $result['status_text'];
						$question['status_class'] = (int)$result['status'] ? 'success' : 'danger';
						break;
					default:
						$value = isset($result[$column]) ? $result[$column] : '';
						break;
				}

				$question[$column] = $value;
			}

			$data['questions'][] = $question;
		}

		if (isset($this->error['warning'])) {
			$this->alert['warning']['warning'] = $this->error['warning'];
		}

		if (isset($this->error['error'])) {
			$this->alert['error']['error'] = $this->error['error'];
		}

		if (isset($this->session->data['success'])) {
			$this->alert['success']['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['alerts'] = $this->alert;

		$data['sorts'] = array();

		foreach ($columns as $column => $attr) {
			if ($attr['sort']) {
				$data['sorts'][$column] = $this->url->link('extension/module/qa/view', $this->urlParams(1, 1, $attr['sort'], $order == 'ASC' ? 'DESC' : 'ASC', '1'), true);
			} else {
				$data['sorts'][$column] = null;
			}
		}

		$limit = (int)$this->config->get('config_limit_admin');

		$pagination = new Pagination();
		$pagination->total = $filtered_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('extension/module/qa/view', $this->urlParams(1, 1, 1, 1, '{page}'), true);
		$pagination->style = 'qa pagination';

		$data['pagination'] = $pagination->render_custom();

		$results_find = array(
			'{start}',
			'{end}',
			'{total}',
			'{pages}'
		);

		$results_replace = array(
			($filtered_total) ? (($page - 1) * $limit) + 1 : 0,
			((($page - 1) * $limit) > ($filtered_total - $limit)) ? $filtered_total : ((($page - 1) * $limit) + $limit),
			$filtered_total,
			$limit ? ceil($filtered_total / $limit) : 1
		);

		$data['results'] = str_replace($results_find, $results_replace, ($total != $filtered_total) ? $this->language->get('text_pagination') . ' ' . sprintf($this->language->get('text_filtered_from'), $total) : $this->language->get('text_pagination'));

		$data['search'] = $search;
		$data['filters'] = $filters;
		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['page'] = $page;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$template = 'extension/module/qa_list';

		$this->response->setOutput($this->load->view($template, $data));
	}

	protected function showErrorPage($data = array()) {
		$this->document->addStyle('view/stylesheet/qa/custom.min.css?v=' . EXTENSION_VERSION);

		$data['alerts'] = $this->alert;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$template = 'extension/module/qa_error';

		$this->response->setOutput($this->load->view($template, $data));
	}

	// Private methods
	private function action($action) {
		$this->load->helper('qa');
		$this->load->language('extension/module/qa');
		$this->load->model('extension/module/qa');

		$ajax_request = isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

		$response = array('success' => false);

		switch ($action) {
			case 'delete':
			case 'copy':
				if ($this->request->server['REQUEST_METHOD'] == 'GET' && isset($this->request->get['qa_id'])) {
					$this->request->post['selected'] = array($this->request->get['qa_id']);
				}
				if (isset($this->request->post['selected'])) {
					$successful = array();
					$failed = array();
					foreach ((array)$this->request->post['selected'] as $qa_id) {
						switch ($action) {
							case 'copy':
								if ($this->validateAction($action, $qa_id)) {
									$result = $this->model_extension_module_qa->copyQuestion($qa_id);
									if ($result) {
										$successful[] = $qa_id;
									} else {
										$failed[] = $qa_id;
									}
								}
								break;
							case 'delete':
								if ($this->validateAction($action, $qa_id)) {
									$this->model_extension_module_qa->deleteQuestion($qa_id);
									$successful[] = $qa_id;
								} else {
									$failed[] = $qa_id;
								}
								break;
						}
					}

					if ($ajax_request) {
						if (count($successful)) {
							$response['success'] = true;
							$this->session->data['success'] = sprintf($this->language->get('text_success_' . $action), count($successful));
							$response['msg'] = sprintf($this->language->get('text_success_' . $action), count($successful));

						}
						if ($this->error && count($failed) < 5) {
							$this->alert['warning'] = array_merge($this->alert['warning'], $this->error);
						} else if (count($failed)) {
							$this->alert['warning']['failed'] = sprintf($this->language->get('text_failed_' . $action), count($failed));
						}
					} else {
						if (count($successful)) {
							$this->session->data['success'] = sprintf($this->language->get('text_success_' . $action), count($successful));
						}
						if ($this->error && count($failed) < 5) {
							$this->alert['warning'] = array_merge($this->alert['warning'], $this->error);
						} else if (count($failed)) {
							$this->alert['warning']['failed'] = sprintf($this->language->get('text_failed_' . $action), count($failed));
						}
					}
				} else {
					if ($ajax_request) {
						$response["error"] = true;
					}
				}

				if ($ajax_request) {
					$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_enc($response, JSON_UNESCAPED_SLASHES));
					return;
				} else {
					$this->session->data['errors'] = $this->error;
					$this->session->data['alerts'] = $this->alert;

					$url = $this->urlParams();

					$this->response->redirect($this->url->link('extension/module/qa/view', $url, true));
				}
				break;
			case 'add':
			case 'edit':
				if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateQAForm($this->request->post)) {
					$question_id = isset($this->request->get['qa_id']) ? $this->request->get['qa_id'] : '';
					switch ($action) {
						case 'add':
							$this->request->post['store_id'] = ((!isset($this->request->post['store_id']) || $this->request->post['store_id'] == '') && isset($this->request->post['question_stores'][0])) ? $this->request->post['question_stores'][0] : 0;

							$question_id = $this->model_extension_module_qa->addQuestion($this->request->post);
							break;
						case 'edit':
							if ($question_id) {
								$this->model_extension_module_qa->editQuestion($question_id, $this->request->post);
							}
							break;
					}

					$this->notify($question_id, $this->request->post);

					if ($ajax_request) {
						$response['success'] = true;

						if ($action == 'add') {
							$response['url'] = html_entity_decode($this->url->link('extension/module/qa/edit', 'qa_id=' . $question_id . $this->urlParams(), true));
							$this->session->data['success'] = $this->language->get('text_success_' . $action);
						}

						$response['msg'] = $this->language->get('text_success_' . $action);

						$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

						$this->response->addHeader('Content-Type: application/json');
						$this->response->setOutput(json_enc($response, JSON_UNESCAPED_SLASHES));
						return;
					} else {
						$this->session->data['success'] = $this->language->get('text_success_' . $action);
						$this->session->data['errors'] = $this->error;
						$this->session->data['alerts'] = $this->alert;

						$url = $this->urlParams();

						$this->response->redirect($this->url->link('extension/module/qa/view', $url, true));
					}
				}

				if ($ajax_request) {
					$response = array_merge(array("error" => true), array("errors" => $this->error), array("alerts" => $this->alert));

					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_enc($response, JSON_UNESCAPED_SLASHES));
					return;
				} else {
					$this->getForm();
				}
				break;
		}
	}

	private function getForm() {
		$qa_id = isset($this->request->get['qa_id']) ? $this->request->get['qa_id'] : null;

		if (isset($this->session->data['errors'])) {
			$this->error = array_merge($this->error, (array)$this->session->data['errors']);

			unset($this->session->data['errors']);
		}

		if (isset($this->session->data['alerts'])) {
			$this->alert = array_merge($this->alert, (array)$this->session->data['alerts']);

			unset($this->session->data['alerts']);
		}

		$this->document->addStyle('view/javascript/summernote/summernote.css');
		$this->document->addStyle('view/stylesheet/qa/custom.min.css?v=' . EXTENSION_VERSION);

		$this->document->addScript('view/javascript/summernote/summernote.js');
		$this->document->addScript('view/javascript/qa/custom.min.js?v=' . EXTENSION_VERSION);

		$this->document->setTitle($this->language->get('heading_title_qa'));

		$data['heading_title'] = $this->language->get('heading_title_qa');

		$data['text_form'] = is_null($qa_id) ? $this->language->get('text_add') : $this->language->get('text_edit');

		$url = $this->urlParams();

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'active'    => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title_qa'),
			'href'      => $this->url->link('extension/module/qa/view', $url, true),
			'active'    => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => $qa_id ? $this->language->get('text_edit') : $this->language->get('text_add'),
			'href'      => $this->url->link('extension/module/qa/' . ($qa_id ? 'edit' : 'add'), ($qa_id ? 'qa_id=' . $qa_id : '') . $url, true),
			'active'    => true
		);

		$data['save'] = $this->url->link('extension/module/qa/' . ($qa_id ? 'edit' : 'add'), ($qa_id ? 'qa_id=' . $qa_id : '') . $url, true);
		$data['delete'] = $this->url->link('extension/module/qa/delete', ($qa_id ? 'qa_id=' . $qa_id : '') . $url, true);
		$data['cancel'] = $this->url->link('extension/module/qa/view', $url, true);

		if ($qa_id) {
			$data['qa_id'] = $qa_id;
		} else {
			$data['qa_id'] = "";
		}

		$data['customer_link'] = $this->url->link('customer/customer', 'user_token=' . $this->session->data['user_token'] . '&filter_name=', true);

		$data['typeahead'] = array();

		foreach (array('product', 'customer') as $type) {
			$url = $this->urlParams(0, 0, 0, 0, 0);
			$data['typeahead'][$type] = array(
				'remote' => html_entity_decode($this->url->link('extension/module/qa/autocomplete', 'type=' . $type . '&query=%QUERY' . $url, true))
			);
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();
		foreach ($data['languages'] as $key => $value) {
			unset($data['languages'][$key]['image']);
		}
		$data['languages'] = array_remap_key_to_id('language_id', $data['languages']);

		$stores = $this->cache->get('store.all');

		if ($stores === false) {
			$this->load->model('setting/store');

			$_stores = $this->model_setting_store->getStores(array());

			$stores = array(
				'0' => array(
					'store_id'  => '0',
					'name'      => $this->config->get('config_name'),
					'url'       => HTTP_CATALOG
				)
			);

			foreach ($_stores as $store) {
				$stores[$store['store_id']] = array(
					'store_id'  => $store['store_id'],
					'name'      => $store['name'],
					'url'       => $store['url']
				);
			}

			$this->cache->set('store.all', $stores);
		}

		$data['stores'] = $stores;
		$data['multistore'] = count($stores) > 1;

		if (isset($this->session->data['error'])) {
			$this->error = $this->session->data['error'];

			unset($this->session->data['error']);
		}

		if (isset($this->error['warning'])) {
			$this->alert['warning']['warning'] = $this->error['warning'];
		}

		if (isset($this->error['error'])) {
			$this->alert['error']['error'] = $this->error['error'];
		}

		if (isset($this->session->data['success'])) {
			$this->alert['success']['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		}

		if ($qa_id && $this->request->server['REQUEST_METHOD'] != 'POST') {
			$qa_info = $this->model_extension_module_qa->getQuestion($qa_id);
			if (!$qa_info) {
				$this->response->redirect($this->url->link('extension/module/qa/view', $url, true));
				return;
			}
		}

		$date = new DateTime();

		$form = array(
			'qa_id'                 => '',
			'product'               => '',
			'product_id'            => '',
			'customer_id'           => '',
			'customer_name'         => '',
			'language_id'           => $this->config->get('config_language_id'),
			'store_id'              => '',
			'question_author_name'  => '',
			'question_author_phone' => '',
			'question_author_email' => '',
			'question_author_custom'=> '',
			'question'              => '',
			'answer_author_name'    => '',
			'answer'                => '',
			'status'                => '0',
			'notified'              => '0',
			'date_asked'            => $date->format('Y-m-d H:i:s'),
			'date_answered'         => '',
			'date_modified'         => '',
			'update_date_answered'  => 0,
			'question_stores'       => $qa_id ? $this->model_extension_module_qa->getQuestionStores($qa_id) : array(0),
		);

		foreach ($form as $key => $v) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} else if (isset($qa_info[$key])) {
				$data[$key] = $qa_info[$key];
			} else {
				$data[$key] = $v;
			}

			if (in_array($key, array('date_asked', 'date_answered', 'date_modified'))) {
				if (!empty($data[$key])) {
					$date = new DateTime($data[$key]);
					$formatted = $date->format('Y-m-d');
				} else {
					$formatted = '';
				}
				$data[$key . '_formatted'] = $formatted;
			}
		}

		$data['notify_customer'] = (int)$this->config->get('module_qa_question_reply_notification') && !(int)$data['notified'];

		if (!isset($this->request->post['update_date_answered'])) {
			$data['update_date_answered'] = $data['answer'] == '' || empty($data['date_answered']);
		}

		$data['errors'] = $this->error;

		$data['user_token'] = $this->session->data['user_token'];

		$data['alerts'] = $this->alert;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$template = 'extension/module/qa_form';

		$this->response->setOutput($this->load->view($template, $data));
	}

	private function registerEventHooks() {
		$this->load->model('setting/event');

		if (isset($this->model_setting_event->getEventByCodeTriggerAction) && is_callable($this->model_setting_event->getEventByCodeTriggerAction)) {
			foreach (self::$event_hooks as $code => $hook) {
				$event = $this->model_setting_event->getEventByCodeTriggerAction($code, $hook['trigger'], $hook['action']);

				if (!$event) {
					$this->model_setting_event->addEvent($code, $hook['trigger'], $hook['action']);
				}
			}
		} else {
			$this->alert['warning']['ocmod'] = $this->language->get('error_ocmod_script');
		}
	}

	private function removeEventHooks() {
		$this->load->model('setting/event');

		foreach (self::$event_hooks as $code => $hook) {
			$this->model_setting_event->deleteEventByCode($code);
		}
	}

	private function updateEventHooks() {
		$this->load->model('setting/event');

		if (isset($this->model_setting_event->getEventByCodeTriggerAction) && is_callable($this->model_setting_event->getEventByCodeTriggerAction)) {
			foreach (self::$event_hooks as $code => $hook) {
				$event = $this->model_setting_event->getEventByCodeTriggerAction($code, $hook['trigger'], $hook['action']);

				if (!$event) {
					$this->model_setting_event->addEvent($code, $hook['trigger'], $hook['action']);

					if (empty($this->alert['success']['hooks_updated'])) {
						$this->alert['success']['hooks_updated'] = $this->language->get('text_success_hooks_update');
					}
				}
			}

			// Delete old triggers
			$query = $this->db->query("SELECT `code` FROM " . DB_PREFIX . "event WHERE `code` LIKE 'admin_module_qa_%'");
			$events = array_keys(self::$event_hooks);

			foreach ($query->rows as $row) {
				if (!in_array($row['code'], $events)) {
					$this->model_setting_event->deleteEventByCode($row['code']);

					if (empty($this->alert['success']['hooks_updated'])) {
						$this->alert['success']['hooks_updated'] = $this->language->get('text_success_hooks_update');
					}
				}
			}
		} else {
			$this->alert['warning']['ocmod'] = $this->language->get('error_ocmod_script');
		}
	}

	protected function checkPrerequisites() {
		$errors = false;

		$this->load->language('extension/module/qa', 'qa');

		if (!$this->config->get('qa_ocmod_script_working')) {
			$errors = true;
			$this->alert['error']['ocmod'] = $this->language->get('qa')->get('error_ocmod_script');
		} else if ($this->checkVersion() && $this->installedVersion() != $this->config->get('qa_version')) {
			$this->alert['warning']['ocmod_cache'] = sprintf($this->language->get('qa')->get('error_ocmod_cache'), $this->url->link('marketplace/modification/refresh', 'user_token=' . $this->session->data['user_token'], true));
		}

		return !$errors;
	}

	protected function checkVersion($display_error = false) {
		$errors = false;

		$installed_version = $this->installedVersion();

		if ($installed_version != EXTENSION_VERSION) {
			$errors = true;

			if ($display_error) {
				$this->alert['info']['version'] = sprintf($this->language->get('error_version'), EXTENSION_VERSION);
			}
		}

		return !$errors;
	}

	private function validateDashboardInstall() {
		$errors = false;

		if (!$this->user->hasPermission('modify', 'extension/extension/dashboard')) {
			$errors = true;
			$this->alert['error']['permission'] = $this->language->get('error_dashboard_permission');
		}

		if (!$errors) {
			return true;
		} else {
			return false;
		}
	}

	private function validate() {
		$errors = false;

		if (!$this->user->hasPermission('modify', 'extension/module/qa')) {
			$errors = true;
			$this->alert['error']['permission'] = $this->language->get('error_permission');
		}

		if (!$errors) {
			$result = $this->checkPrerequisites() && $this->checkVersion() && $this->model_extension_module_qa->checkDatabaseStructure($this->alert);
			$this->alert = array_merge($this->alert, $this->model_extension_module_qa->getAlerts());
			return $result;
		} else {
			return false;
		}
	}

	private function validateForm(&$data) {
		$errors = !$this->validate();

		if ((int)$data['module_qa_new_question_notification']) {
			foreach ((array)$data['module_qa_notification_emails'] as $language_id => $value) {
				if (empty($value)) {
					$errors = true;
					$this->error["notification_emails"][$language_id]['email'] = $this->language->get('error_missing_email');
				} else {
					$emails = explode(',', $value);

					foreach ($emails as $email) {
						if (!validate_email($email)) {
							$errors = true;
							$this->error["notification_emails"][$language_id]['email'] = $this->language->get('error_email');
						}
					}
				}
			}
		}

		if ((int)$data['module_qa_form_display_custom_field']) {
			foreach ((array)$data['module_qa_form_custom_field_name'] as $language_id => $value) {
				if (utf8_strlen(trim($value)) == 0) {
					$errors = true;
					$this->error["form_custom_field_name"][$language_id]['name'] = $this->language->get('error_custom_field_name');
				}
			}
		}

		if (!is_numeric($data['module_qa_items_per_page']) || (int)$data['module_qa_items_per_page'] != $data['module_qa_items_per_page'] || (int)$data['module_qa_items_per_page'] < 0) {
			$errors = true;
			$this->error['items_per_page'] = $this->language->get('error_items_per_page');
		}

		if ($errors) {
			$this->alert['warning']['warning'] = $this->language->get('error_warning');
		}

		return !$errors;
	}

	private function validateQAForm(&$data) {
		$errors = !$this->validate();

		if (!isset($data['product_id']) || $data['product_id'] == '') {
			$errors = true;
			$this->error['product'] = $this->language->get('error_product');
		} else {
			$this->load->model('catalog/product');

			$product = $this->model_catalog_product->getProduct($data['product_id']);

			if (!isset($product['product_id'])) {
				$errors = true;
				$data['product_id'] = '';
				$data['product'] = '';
				$this->error['product'] = $this->language->get('error_product');
			}
		}

		if (utf8_strlen($data['question_author_email']) > 0 && !validate_email(utf8_decode($data['question_author_email']))) {
			$errors = true;
			$this->error['question_author_email'] = $this->language->get('error_invalid_email');
		}

		if (utf8_strlen($data['question']) < 1) {
			$errors = true;
			$this->error['question'] = $this->language->get('error_question');
		}

		if ($errors) {
			$errors = true;
			$this->alert['warning']['warning'] = $this->language->get('error_warning');
		}

		return !$errors;
	}

	private function validateUpgrade() {
		$errors = false;

		if (!$this->user->hasPermission('modify', 'extension/module/qa')) {
			$errors = true;
			$this->alert['error']['permission'] = $this->language->get('error_permission');
		}

		return !$errors;
	}

	private function validateAction($action, $data) {
		$errors = !$this->validate();

		switch ($action) {
			case 'delete':
				break;
			default:
				break;
		}

		return !$errors;
	}

	protected function installedVersion() {
		$installed_version = $this->config->get('module_qa_installed_version');
		return $installed_version ? $installed_version : '1.8.9';
	}

	private function urlParams($search = true, $filters = true, $sort = true, $order = true, $page = true) {
		$url = '';

		if ($search) {
			if (is_string($search)) {
				$url .= '&search=' . urlencode($search);
			} else if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}
		}

		if ($filters) {
			foreach($this->columns as $column => $attr) {
				if (isset($this->request->get['filter_' . $column])) {
					$url .= '&filter_' . $column . '=' . urlencode(html_entity_decode($this->request->get['filter_' . $column], ENT_QUOTES, 'UTF-8'));
				}
			}
		}

		if ($sort) {
			if (is_string($sort)) {
				$url .= '&sort=' . $sort;
			} else if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}
		}

		if ($order) {
			if (is_string($order)) {
				$url .= '&order=' . $order;
			} else if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}
		}

		if ($page) {
			if (is_string($page)) {
				$url .= '&page=' . $page;
			} else if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}
		}

		$url .= '&user_token=' . $this->session->data['user_token'];

		return $url;
	}
}
