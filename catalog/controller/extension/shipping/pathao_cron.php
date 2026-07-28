<?php
class ControllerExtensionShippingPathaoCron extends Controller {
	private function jsonOut($arr) {
		while (ob_get_level()) { @ob_end_clean(); }
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($arr));
	}

	public function index() {
		@ini_set('display_errors', '0');
		error_reporting(0);

		$token = (string)$this->config->get('shipping_pathao_cron_token');
		$given = isset($this->request->get['token']) ? (string)$this->request->get['token'] : '';
		if ($token && !hash_equals($token, $given)) {
			return $this->jsonOut(array('success' => false, 'message' => 'Invalid token'));
		}

		$limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 25;
		if ($limit < 1) $limit = 1;
		if ($limit > 200) $limit = 200;

		$this->load->model('extension/shipping/pathao');
		$res = $this->model_extension_shipping_pathao->cronUpdateStatuses($limit);

		return $this->jsonOut($res);
	}
}

