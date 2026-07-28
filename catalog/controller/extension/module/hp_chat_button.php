<?php

class ControllerExtensionModuleHpChatButton extends Controller
{
	public function index()
	{
		$this->load->language('extension/module/hp_chat_button');
		$this->load->library('mobiledetect');

		$data['hpcb_status'] = $this->config->get('module_hp_chat_button_status');
		$data['mobile_compatibility'] = $this->config->get('module_hp_chat_button_mobile_compatibility');

		if ($this->config->get('module_hp_chat_button_status')) {
//			$this->document->addStyle('catalog/view/javascript/whatsapp-chat-support.css');

			$utm_source = isset($this->session->data['utm_source']) ? $this->session->data['utm_source'] : '';

			$whatsapps = $this->config->get('module_hp_chat_button_whatsapp');

			$language_id = $this->config->get('config_language_id');

			$data['whatsapp_text'] = html_entity_decode($this->config->get('module_hp_chat_button_message_text_' . $language_id));

			$find = [
				'{utm_source}'
			];

			$replace = [
				'utm_source' => $utm_source
			];

			$data['whatsapp_text'] = rawurlencode(str_replace($find, $replace, $data['whatsapp_text']));

			$this->load->library('mobiledetect');
			$data['is_mobile'] = $this->mobiledetect->isMobileDevice();
			$data['color_scheme_button'] = $this->config->get('module_hp_chat_button_color');
			$data['whatsapps'] = [];

			$this->load->model('tool/image');
			if ($whatsapps) {
				foreach ($whatsapps as $whatsapp) {
					if (isset($whatsapp['profile_picture']) && is_file(DIR_IMAGE . $whatsapp['profile_picture'])) {
						$whatsapp['picture_wa'] = $this->model_tool_image->resize($whatsapp['profile_picture'], 45, 45);
					} else {
						$whatsapp['picture_wa'] = $this->model_tool_image->resize($this->config->get('config_logo'), 45, 45);
					}

					$time_now = strtotime(date('H:i:s'));
					$online_time_from = strtotime($whatsapp['online_time_from'] . ':00:00');
					if ($whatsapp['online_time_from'] <= $whatsapp['online_time_to']) {
						$online_time_to = strtotime($whatsapp['online_time_to'] . ':00:00');
					} else {
						$online_time_from = strtotime("-1 day", strtotime($whatsapp['online_time_from'] . ':00:00'));
						$online_time_to = strtotime("+1 day", strtotime($whatsapp['online_time_to'] . ':00:00'));
					}
					$away_time_from = strtotime($whatsapp['away_time_from'] . ':00:00');
					if ($whatsapp['away_time_from'] <= $whatsapp['away_time_to']) {
						$away_time_to = strtotime($whatsapp['away_time_to'] . ':00:00');
					} else {
						$away_time_from = strtotime("-1 day", strtotime($whatsapp['away_time_from'] . ':00:00'));
						$away_time_to = strtotime("+1 day", strtotime($whatsapp['away_time_to'] . ':00:00'));
					}

					if ($time_now >= $online_time_from && $time_now <= $online_time_to) {
						$whatsapp['online_status'] = 1;
					} else if ($time_now >= $away_time_from && $time_now <= $away_time_to) {
						$whatsapp['online_status'] = 2;
					} else {
						$whatsapp['online_status'] = 3;
					}

					$data['whatsapps'][] = $whatsapp;
				}
				usort($data['whatsapps'], function ($a, $b) {
					return ($a['sort_order'] < $b['sort_order']) ? -1 : 1;
				});
			}
		}


		// RTL status
		$rtl_languages = ['ar', 'hb'];
		$language_used = $this->session->data['language'];

		if (in_array($language_used, $rtl_languages)) {
			$data['rtl_status'] = 1;
		} else {
			$data['rtl_status'] = 0;
		}

		$data['call_to_action'] = html_entity_decode($this->config->get('module_hp_chat_button_call_to_action_' .  $language_id));
		
		$this->response->setOutput($this->load->view('extension/module/hp_chat_button', $data));
	}
}
