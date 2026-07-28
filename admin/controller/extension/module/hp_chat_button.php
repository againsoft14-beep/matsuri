<?php

class ControllerExtensionModuleHpChatButton extends Controller {
	private $version 		= '1.4.7.6';
	private $extension_code = 'hpcb';
	private $v_d 			= '';
	private $error 			= [];
	private $extension_type = 'io';
	private $domain 		= '';

	public function index() {
		$this->domain	 = str_replace("www.","",$_SERVER['SERVER_NAME']);
		$this->houseKeeping();
		$this->rightman();

		// if ($this->domain != $this->v_d) {
		// 	$this->storeAuth();
		// } else {
		// 	$this->setting();
		// }

		$this->setting();
	}

	public function install() {
		$sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "order_source`(
						`order_source_id` INT(11) NOT NULL AUTO_INCREMENT,
						`order_id` INT(11) NOT NULL,
						`source` VARCHAR(32) NOT NULL,
						 PRIMARY KEY(`order_source_id`)
					) ENGINE = InnoDB;";

		$this->db->query($sql);
		$this->houseKeeping();
	}

	public function setting() {
		$data['version'] = $this->version;
		$data['extension_code'] = $this->extension_code;
		$data['extension_type'] = $this->extension_type;
		//Load language
		$this->load->language('extension/module/hp_chat_button');

		//Load model
		$this->load->model('setting/setting');
		$this->load->model('localisation/order_status');
		$this->load->model('localisation/language');

		// Set Title
		$this->document->setTitle($this->language->get('heading_title2'));
		$data['heading_title'] = $this->language->get('heading_title2');

		//Load additional CSS/JS
		$this->document->addScript('view/javascript/bootstrap/js/bootstrap-checkbox.min.js');
		$this->document->addScript('view/javascript/selectpicker/js/bootstrap-select.min.js');
		$this->document->addScript('view/assets/summernote/summernote.js');
		$this->document->addStyle('view/assets/summernote/summernote.css');
		$this->document->addStyle('view/javascript/selectpicker/css/bootstrap-select.min.css');
		$this->document->addStyle('view/assets/flag/css/flag-icon.min.css');

		//Breadcumbs
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/hp_chat_button', 'user_token=' . $this->session->data['user_token'], true),
		];

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		}
		//POST Handling
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$post = $this->request->post;

			$data = [];
			foreach ($post as $key => $value) {
				$data['module_hp_chat_button_' . $key] = $value;
			}

			$this->session->data['success'] = $this->language->get('text_success');
			$this->model_setting_setting->editSetting('module_hp_chat_button', $data);
			$this->response->redirect($this->url->link('extension/module/hp_chat_button', 'user_token=' . $this->session->data['user_token'], true));
		}

		//Error
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['tawkto'])) {
			$data['error_tawkto'] = $this->error['tawkto'];
		} else {
			$data['error_tawkto'] = '';
		}
		if (isset($this->error['fb'])) {
			$data['error_fb'] = $this->error['fb'];
		} else {
			$data['error_fb'] = '';
		}
		//Data

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} else if ($this->config->get('module_hp_chat_button_status')) {
			$data['status'] = $this->config->get('module_hp_chat_button_status');
		} else {
			$data['status'] = 0;
		}

		if (isset($this->request->post['mobile_compatibility'])) {
			$data['mobile_compatibility'] = $this->request->post['mobile_compability'];
		} else if ($this->config->get('module_hp_chat_button_mobile_compatibility')) {
			$data['mobile_compatibility'] = $this->config->get('module_hp_chat_button_mobile_compatibility');
		} else {
			$data['mobile_compatibility'] = 0;
		}

		if (isset($this->request->post['wa_show_homepage'])) {
			$data['wa_show_homepage'] = $this->request->post['wa_show_homepage'];
		} else if ($this->config->get('module_hp_chat_button_wa_show_homepage')) {
			$data['wa_show_homepage'] = $this->config->get('module_hp_chat_button_wa_show_homepage');
		} else {
			$data['wa_show_homepage'] = 0;
		}

		if (isset($this->request->post['button_placement'])) {
			$data['button_placement'] = $this->request->post['button_placement'];
		} else if ($this->config->get('module_hp_chat_button_button_placement')) {
			$data['button_placement'] = $this->config->get('module_hp_chat_button_button_placement');
		} else {
			$data['button_placement'] = '#product';
		}

		if (isset($this->request->post['type'])) {
			$data['type'] = $this->request->post['type'];
		} else if ($this->config->get('module_hp_chat_button_type')) {
			$data['type'] = $this->config->get('module_hp_chat_button_type');
		} else {
			$data['type'] = "wa";
		}

		if (isset($this->request->post['fb_page_id'])) {
			$data['fb_page_id'] = $this->request->post['fb_page_id'];
		} else if ($this->config->get('module_hp_chat_button_fb_page_id')) {
			$data['fb_page_id'] = $this->config->get('module_hp_chat_button_fb_page_id');
		} else {
			$data['fb_page_id'] = "";
		}

		if (isset($this->request->post['tawkto_property_id'])) {
			$data['tawkto_property_id'] = $this->request->post['tawkto_property_id'];
		} else if ($this->config->get('module_hp_chat_button_tawkto_property_id')) {
			$data['tawkto_property_id'] = $this->config->get('module_hp_chat_button_tawkto_property_id');
		} else {
			$data['tawkto_property_id'] = "";
		}

		if (isset($this->request->post['desktop'])) {
			$data['desktop'] = $this->request->post['desktop'];
		} else if ($this->config->get('module_hp_chat_button_desktop')) {
			$data['desktop'] = $this->config->get('module_hp_chat_button_desktop');
		} else {
			$data['desktop'] = 0;
		}

		if (isset($this->request->post['color'])) {
			$data['color'] = $this->request->post['color'];
		} else if ($this->config->get('module_hp_chat_button_color')) {
			$data['color'] = $this->config->get('module_hp_chat_button_color');
		} else {
			$data['color'] = '#2db541';
		}

		if (isset($this->request->post['bottom_margin'])) {
			$data['bottom_margin_desktop'] = $this->request->post['bottom_margin_desktop'];
		} else if ($this->config->get('module_hp_chat_button_bottom_margin_desktop')) {
			$data['bottom_margin_desktop'] = $this->config->get('module_hp_chat_button_bottom_margin_desktop');
		} else {
			$data['bottom_margin_desktop'] = 30;
		}

		if (isset($this->request->post['right_margin_desktop'])) {
			$data['right_margin_desktop'] = $this->request->post['right_margin_desktop'];
		} else if ($this->config->get('module_hp_chat_button_right_margin_desktop')) {
			$data['right_margin_desktop'] = $this->config->get('module_hp_chat_button_right_margin_desktop');
		} else {
			$data['right_margin_desktop'] = 30;
		}

		if (isset($this->request->post['bottom_margin_mobile'])) {
			$data['bottom_margin_mobile'] = $this->request->post['bottom_margin_mobile'];
		} else if ($this->config->get('module_hp_chat_button_bottom_margin_mobile')) {
			$data['bottom_margin_mobile'] = $this->config->get('module_hp_chat_button_bottom_margin_mobile');
		} else {
			$data['bottom_margin_mobile'] = 30;
		}

		if (isset($this->request->post['right_margin_mobile'])) {
			$data['right_margin_mobile'] = $this->request->post['right_margin_mobile'];
		} else if ($this->config->get('module_hp_chat_button_right_margin_mobile')) {
			$data['right_margin_mobile'] = $this->config->get('module_hp_chat_button_right_margin_mobile');
		} else {
			$data['right_margin_mobile'] = 30;
		}

		if (isset($this->request->post['mobile'])) {
			$data['mobile'] = $this->request->post['mobile'];
		} else if ($this->config->get('module_hp_chat_button_mobile')) {
			$data['mobile'] = $this->config->get('module_hp_chat_button_mobile');
		} else {
			$data['mobile'] = 0;
		}

		if (isset($this->request->post['whatsapp'])) {
			$whatsapps = $this->request->post['whatsapp'];
		} else if ($this->config->get('module_hp_chat_button_whatsapp')) {
			$whatsapps = $this->config->get('module_hp_chat_button_whatsapp');
		} else {
			$whatsapps = [];
		}

		if (isset($this->request->post['hide_prechattext'])) {
			$data['hide_prechattext'] = $this->request->post['hide_prechattext'];
		} else if ($this->config->get('module_hp_chat_button_hide_prechattext')) {
			$data['hide_prechattext'] = $this->config->get('module_hp_chat_button_hide_prechattext');
		} else {
			$data['hide_prechattext'] = 0;
		}

		$this->load->model('tool/image');

		$data['whatsapps'] = [];
		foreach ($whatsapps as $wa) {
			if (isset($wa['profile_picture']) && is_file(DIR_IMAGE . $wa['profile_picture'])) {
				$wa['logo_placeholder'] = $this->model_tool_image->resize($wa['profile_picture'], 100, 100);
			} else {
				$wa['logo_placeholder'] = $this->model_tool_image->resize("catalog/profile/default_avatar.png", 100, 100);
			}
			$data['whatsapps'][] = $wa;
		}


		$data['wa_logo_placeholder'] = $this->model_tool_image->resize("catalog/profile/default_avatar.png", 100, 100);
		$data['wa_logo_default'] = "catalog/profile/default_avatar.png";

		$data['languages'] = $this->model_localisation_language->getLanguages();

		foreach ($data['languages'] as $language) {

			if (isset($this->request->post['message_text_' . $language['language_id']])) {
				$data['message_text_' . $language['language_id']] = $this->request->post['message_text_' . $language['language_id']];
			} else if ($this->config->get('module_hp_chat_button_message_text_' . $language['language_id'])) {
				$data['message_text_' . $language['language_id']] = $this->config->get('module_hp_chat_button_message_text_' . $language['language_id']);
			} else {
				$data['message_text_' . $language['language_id']] = $this->language->get('placeholder_message_text');
			}

			if (isset($this->request->post['greeting_' . $language['language_id']])) {
				$data['greeting_' . $language['language_id']] = $this->request->post['greeting_' . $language['language_id']];
			} else if ($this->config->get('module_hp_chat_button_greeting_' . $language['language_id'])) {
				$data['greeting_' . $language['language_id']] = $this->config->get('module_hp_chat_button_greeting_' . $language['language_id']);
			} else {
				$data['greeting_' . $language['language_id']] = $this->language->get('placeholder_greeting');
			}

			if (isset($this->request->post['call_to_action_' . $language['language_id']])) {
				$data['call_to_action_' . $language['language_id']] = $this->request->post['call_to_action_' . $language['language_id']];
			} else if ($this->config->get('module_hp_chat_button_call_to_action_' . $language['language_id'])) {
				$data['call_to_action_' . $language['language_id']] = $this->config->get('module_hp_chat_button_call_to_action_' . $language['language_id']);
			} else {
				$data['call_to_action_' . $language['language_id']] = $this->language->get('placeholder_call_to_action');
			}

			if (isset($this->request->post['heading_title_' . $language['language_id']])) {
				$data['heading_title_' . $language['language_id']] = $this->request->post['heading_title_' . $language['language_id']];
			} else if ($this->config->get('module_hp_chat_button_heading_title_' . $language['language_id'])) {
				$data['heading_title_' . $language['language_id']] = $this->config->get('module_hp_chat_button_heading_title_' . $language['language_id']);
			} else {
				$data['heading_title_' . $language['language_id']] = $this->language->get('placeholder_heading_title');
			}

			if (isset($this->request->post['description_' . $language['language_id']])) {
				$data['description_' . $language['language_id']] = $this->request->post['description_' . $language['language_id']];
			} else if ($this->config->get('module_hp_chat_button_description_' . $language['language_id'])) {
				$data['description_' . $language['language_id']] = $this->config->get('module_hp_chat_button_description_' . $language['language_id']);
			} else {
				$data['description_' . $language['language_id']] = $this->language->get('placeholder_description');
			}


			if (isset($this->request->post['chat_reply_' . $language['language_id']])) {
				$data['chat_reply_' . $language['language_id']] = $this->request->post['chat_reply_' . $language['language_id']];
			} else if ($this->config->get('module_hp_chat_button_chat_reply_' . $language['language_id'])) {
				$data['chat_reply_' . $language['language_id']] = $this->config->get('module_hp_chat_button_chat_reply_' . $language['language_id']);
			} else {
				$data['chat_reply_' . $language['language_id']] = $this->language->get('placeholder_chat_reply');
			}
		}

		//Links
		// $data['uninstall'] = $this->url->link('extension/module/module_hp_chat_button/uninstallTable', 'user_token=' . $this->session->data['user_token'], true);

		//Load template
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$data['user_token'] = $this->session->data['user_token'];

		$this->response->setOutput($this->load->view('extension/module/hp_chat_button', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/hp_chat_button')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}


		if ($this->request->post['type'] == 'tw') {
			if ($this->request->post['tawkto_property_id'] < 1) {

				$this->error['tawkto'] = $this->language->get('error_tawkto');
			}
		}

		if ($this->request->post['type'] == 'fb') {
			if ($this->request->post['fb_page_id'] < 1) {

				$this->error['fb'] = $this->language->get('error_fb');
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}


		if (!$this->error) {
			return true;
		} else {
			return false;
		}

	}

	private function rightman() {
		$this->load->model('extension/module/system_startup');
		if($this->internetAccess()) {
			if (isset($this->model_extension_module_system_startup->checkLicenseKey)) {
				$license = $this->model_extension_module_system_startup->checkLicenseKey($this->extension_code);

				if ($license) {
					if (isset($this->model_extension_module_system_startup->licensewalker)) {
						$url = $this->model_extension_module_system_startup->licensewalker($license['license_key'],$this->extension_code,$this->domain);
						$data = $url;
						$domain = isset($data['domain']) ? $data['domain'] : '';
						$this->v_d = $domain;
					}
				}
			}
		} else {
			$this->error['warning'] = $this->language->get('error_no_internet_access');
		}
	}
 private function houseKeeping() {
		$file    = 'https://api.hpwebdesign.io/validate.zip';
		$newfile = DIR_APPLICATION . 'validate.zip';

		if (!file_exists(DIR_APPLICATION . 'controller/common/hp_validate.php') || !file_exists(DIR_APPLICATION . 'model/extension/module/system_startup.php') || !file_exists(DIR_APPLICATION . 'view/template/extension/module/validation.twig')) {

			$file = $this->curl_get_file_contents($file);

			if (file_put_contents($newfile, $file)) {
				$zip = new ZipArchive();
				$res = $zip->open($newfile);
				if ($res === true) {
					$zip->extractTo(DIR_APPLICATION);
					$zip->close();
					unlink($newfile);
				}
			}
		}

		$this->load->model('extension/module/system_startup');

		if (!isset($this->model_extension_module_system_startup->checkLicenseKey) || !isset($this->model_extension_module_system_startup->licensewalker)) {

			$file = $this->curl_get_file_contents($file);

			if (file_put_contents($newfile, $file)) {
				$zip = new ZipArchive();
				$res = $zip->open($newfile);
				if ($res === true) {
					$zip->extractTo(DIR_APPLICATION);
					$zip->close();
					unlink($newfile);
				}
			}
		}

		if (!file_exists(DIR_SYSTEM . 'system.ocmod.xml')) {
			$str = $this->curl_get_file_contents('https://api.hpwebdesign.io/system.ocmod.txt');

			file_put_contents(dirname(getcwd()) . '/system/system.ocmod.xml', $str);
		}

		$sql = "CREATE TABLE IF NOT EXISTS `hpwd_license`(
						`hpwd_license_id` INT(11) NOT NULL AUTO_INCREMENT,
						`license_key` VARCHAR(64) NOT NULL,
						`code` VARCHAR(32) NOT NULL,
						`support_expiry` date DEFAULT NULL,
						 PRIMARY KEY(`hpwd_license_id`)
					) ENGINE = InnoDB;";

		$this->db->query($sql);
	}


	public function flushdata() {
		$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE `key` LIKE '%module_hp_chat_button_status%'");
	}

	private function internetAccess()
	{
		return true;
	}

	public function curlcheck()
	{
		return in_array('curl', get_loaded_extensions()) ? true : false;
	}

	public function storeAuth() {
		$data['curl_status'] = $this->curlcheck();
		$data['extension_code'] = $this->extension_code;
		$data['user_token']     = $this->session->data['user_token'];
		$data['extension_type'] = $this->extension_type;
		$this->flushdata();

		$this->load->language('extension/module/hp_chat_button');

		$this->document->setTitle($this->language->get('text_validation'));

		$data['text_curl'] = $this->language->get('text_curl');
		$data['text_disabled_curl'] = $this->language->get('text_disabled_curl');

		$data['text_validation'] = $this->language->get('text_validation');
		$data['text_validate_store'] = $this->language->get('text_validate_store');
		$data['text_information_provide'] = $this->language->get('text_information_provide');
		$data['domain_name'] = $this->language->get('text_validate_store');
		$data['domain_name'] = $this->domain;

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => false,
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title2'),
			'href' => $this->url->link('extension/module/hp_chat_button', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => false,
		];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/validation', $data));
	}

	private function curl_get_file_contents($URL) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $URL);
		$contents = curl_exec($c);
		curl_close($c);

		if ($contents) return $contents;
		else return FALSE;
	}

}
