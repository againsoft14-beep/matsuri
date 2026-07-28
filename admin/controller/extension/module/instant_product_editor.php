<?php

class ControllerExtensionModuleInstantProductEditor extends Controller {
	public function install()
	{
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('instant_product_editor');
		$this->model_setting_event->addEvent(
			'instant_product_editor',
			'admin/view/common/header/before',
			'extension/module/instant_product_editor/addScript'
		);
	}

	public function uninstall()
	{
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('instant_product_editor');
	}

	public function addScript($eventRoute, &$data)
	{
		$data['scripts'][] = 'view/javascript/ipe.js';
	}
}