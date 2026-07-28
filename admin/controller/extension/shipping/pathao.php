<?php
class ControllerExtensionShippingPathao extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/shipping/pathao');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		$this->load->model('extension/shipping/pathao');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$post = $this->request->post;
			if (isset($post['shipping_pathao_merchant_password']) && trim((string)$post['shipping_pathao_merchant_password']) === '' && $this->config->get('shipping_pathao_merchant_password') !== null && $this->config->get('shipping_pathao_merchant_password') !== '') {
				$post['shipping_pathao_merchant_password'] = $this->config->get('shipping_pathao_merchant_password');
			}
			if (isset($post['shipping_pathao_client_secret']) && trim((string)$post['shipping_pathao_client_secret']) === '' && $this->config->get('shipping_pathao_client_secret') !== null && $this->config->get('shipping_pathao_client_secret') !== '') {
				$post['shipping_pathao_client_secret'] = $this->config->get('shipping_pathao_client_secret');
			}
			if (isset($post['shipping_pathao_webhook_secret']) && trim((string)$post['shipping_pathao_webhook_secret']) === '' && $this->config->get('shipping_pathao_webhook_secret') !== null && $this->config->get('shipping_pathao_webhook_secret') !== '') {
				$post['shipping_pathao_webhook_secret'] = $this->config->get('shipping_pathao_webhook_secret');
			}
			$this->model_setting_setting->editSetting('shipping_pathao', $post);

			// default store (optional)
			if (isset($this->request->post['shipping_pathao_default_store_id'])) {
				$this->model_extension_shipping_pathao->setDefaultStore((int)$this->request->post['shipping_pathao_default_store_id']);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/pathao', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/shipping/pathao', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

		$keys = array(
			'shipping_pathao_status',
			'shipping_pathao_sandbox',
			'shipping_pathao_logging',
			'shipping_pathao_cron_token',
			'shipping_pathao_webhook_secret',
			'shipping_pathao_client_id',
			'shipping_pathao_client_secret',
			'shipping_pathao_merchant_username',
			'shipping_pathao_merchant_password',
		);

		foreach ($keys as $k) {
			if (isset($this->request->post[$k])) {
				$data[$k] = $this->request->post[$k];
			} else {
				$data[$k] = $this->config->get($k);
			}
		}

		$data['shipping_pathao_default_store_id'] = (int)$this->config->get('shipping_pathao_default_store_id');

		$stores = $this->model_extension_shipping_pathao->getStores();
		$data['pathao_stores'] = $stores;

		$data['test_connection_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/pathao/testConnection', 'user_token=' . $this->session->data['user_token'], true));
		$data['sync_stores_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/pathao/syncStores', 'user_token=' . $this->session->data['user_token'], true));

		$data['pathao_webhook_url'] = '';
		$data['pathao_cron_url'] = '';
		if (defined('HTTP_CATALOG')) {
			$base = rtrim(HTTP_CATALOG, '/');
			$data['pathao_webhook_url'] = $base . '/index.php?route=extension/shipping/pathao_webhook';
			$data['pathao_cron_url'] = $base . '/index.php?route=extension/shipping/pathao_cron';
			$tok = (string)$this->config->get('shipping_pathao_cron_token');
			if ($tok !== '') {
				$data['pathao_cron_url'] .= '&token=' . rawurlencode($tok);
			}
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/pathao', $data));
	}

	public function install() {
		$this->load->language('extension/shipping/pathao');
		if (!$this->user->hasPermission('modify', 'extension/shipping/pathao')) {
			return;
		}
		$this->load->model('extension/shipping/pathao');
		$this->model_extension_shipping_pathao->install();
	}

	public function uninstall() {
		$this->load->language('extension/shipping/pathao');
		if (!$this->user->hasPermission('modify', 'extension/shipping/pathao')) {
			return;
		}
		$this->load->model('extension/shipping/pathao');
		$this->model_extension_shipping_pathao->uninstall();
	}

	private function pathaoEnv() {
		if (defined('PATHAO_COURIER_ENV')) {
			$env = strtolower(trim((string)PATHAO_COURIER_ENV));
			if ($env === 'sandbox' || $env === 'staging') {
				return 'sandbox';
			}
			if ($env === 'live' || $env === 'production') {
				return 'live';
			}
		}
		return ((int)$this->config->get('shipping_pathao_sandbox')) ? 'sandbox' : 'live';
	}

	/**
	 * Use credentials from the current request so Test / Sync work before Save.
	 * Empty password/secret in the form does not override values already stored in settings.
	 */
	private function applyPathaoCredentialsFromRequest() {
		$src = $this->request->post;
		if (!$src) {
			$src = $this->request->get;
		}
		$map = array(
			'shipping_pathao_client_id',
			'shipping_pathao_client_secret',
			'shipping_pathao_merchant_username',
			'shipping_pathao_merchant_password'
		);
		foreach ($map as $k) {
			if (!isset($src[$k])) {
				continue;
			}
			$v = (string)$src[$k];
			if (($k === 'shipping_pathao_client_secret' || $k === 'shipping_pathao_merchant_password') && $v === '') {
				continue;
			}
			$this->config->set($k, $v);
		}
		if (isset($src['shipping_pathao_sandbox'])) {
			$this->config->set('shipping_pathao_sandbox', $src['shipping_pathao_sandbox']);
		}
	}

	private function pathaoLibs() {
		$env = $this->pathaoEnv();
		$logger = new PathaoLogger((int)$this->config->get('shipping_pathao_logging'), $env);
		$token = new PathaoTokenManager($this->db, $this->config, $logger);
		$client = new PathaoClient($token, $logger, $env);
		return array($env, $logger, $token, $client);
	}

	public function testConnection() {
		@ini_set('display_errors', '0');
		@ini_set('log_errors', '0');
		error_reporting(0);
		while (ob_get_level()) { @ob_end_clean(); }

		$json = array('success' => false, 'message' => '');

		if (!$this->user->hasPermission('modify', 'extension/shipping/pathao')) {
			$json['message'] = 'Permission denied';
		} else {
			$this->load->model('extension/shipping/pathao');
			require_once(DIR_SYSTEM . 'library/pathao/logger.php');
			require_once(DIR_SYSTEM . 'library/pathao/token_manager.php');
			require_once(DIR_SYSTEM . 'library/pathao/client.php');

			$this->applyPathaoCredentialsFromRequest();
			list($env, $logger, $token, $client) = $this->pathaoLibs();
			$res = $token->getToken($client->baseUrl());
			if (!empty($res['success'])) {
				$json['success'] = true;
				$json['message'] = 'Connected';
			} else {
				$json['message'] = isset($res['message']) ? $res['message'] : 'Failed';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function syncStores() {
		@ini_set('display_errors', '0');
		error_reporting(0);
		while (ob_get_level()) { @ob_end_clean(); }

		$json = array('success' => false, 'message' => '');

		if (!$this->user->hasPermission('modify', 'extension/shipping/pathao')) {
			$json['message'] = 'Permission denied';
		} else {
			$this->load->model('extension/shipping/pathao');
			require_once(DIR_SYSTEM . 'library/pathao/logger.php');
			require_once(DIR_SYSTEM . 'library/pathao/token_manager.php');
			require_once(DIR_SYSTEM . 'library/pathao/client.php');

			$this->applyPathaoCredentialsFromRequest();
			list($env, $logger, $token, $client) = $this->pathaoLibs();
			$r = $client->get('/stores');
			if (!empty($r['success'])) {
				$data = $r['data'];
				$stores = array();
				if (isset($data['data']) && is_array($data['data'])) $stores = $data['data'];
				if (isset($data['data']['data']) && is_array($data['data']['data'])) $stores = $data['data']['data'];
				if (isset($data['stores']) && is_array($data['stores'])) $stores = $data['stores'];

				$this->model_extension_shipping_pathao->saveStores($stores, (int)$this->config->get('shipping_pathao_default_store_id'));
				$json['success'] = true;
				$json['message'] = 'Stores synced: ' . count($stores);
			} else {
				$json['message'] = $r['message'];
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function stores() {
		$json = array();
		if ($this->user->hasPermission('access', 'sale/order') || $this->user->hasPermission('access', 'extension/shipping/pathao')) {
			$this->load->model('extension/shipping/pathao');
			$json['stores'] = $this->model_extension_shipping_pathao->getStores();
		} else {
			$json['stores'] = array();
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function orderInfo() {
		$order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;
		$row = array();
		if ($order_id && $this->user->hasPermission('access', 'sale/order')) {
			$q = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pathao_order` WHERE order_id = '" . (int)$order_id . "' ORDER BY id DESC LIMIT 1");
			if ($q->num_rows) $row = $q->row;
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(array('data' => $row)));
	}

	/**
	 * Customer order comment for Pathao "Special instruction" prefill in order list modal.
	 */
	public function orderComment() {
		@ini_set('display_errors', '0');
		error_reporting(0);
		while (ob_get_level()) {
			@ob_end_clean();
		}
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');

		$order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;
		$out = array('success' => false, 'comment' => '');

		if ($order_id && $this->pathaoCanUseLocations()) {
			$this->load->model('sale/order');
			$order = $this->model_sale_order->getOrder($order_id);
			if ($order) {
				$out['success'] = true;
				$out['comment'] = isset($order['comment']) ? (string)$order['comment'] : '';
			}
		}
		$this->response->setOutput(json_encode($out));
	}

	/**
	 * JSON for "Send with Pathao" modal autofill (single order).
	 */
	public function getOrderData() {
		@ini_set('display_errors', '0');
		error_reporting(0);
		while (ob_get_level()) {
			@ob_end_clean();
		}
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;
		if (!$order_id || !$this->pathaoCanUseLocations()) {
			$this->response->setOutput(json_encode(array('success' => false, 'message' => 'Invalid request')));
			return;
		}
		$this->load->model('extension/shipping/pathao');
		$out = $this->model_extension_shipping_pathao->getPathaoOrderData($order_id);
		$this->response->setOutput(json_encode($out));
	}

	/**
	 * Create Pathao consignment from order (POST). Lives in this controller so the session
	 * permission check is extension/shipping/pathao (same as the extension), not a separate
	 * pathao_ajax route that is often missing from user groups and returns HTML "permission denied".
	 */
	private function pathaoOrderJsonOut($arr) {
		while (ob_get_level()) { @ob_end_clean(); }
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($arr));
	}

	private function pathaoCanCreateFromOrder() {
		return $this->user->hasPermission('modify', 'sale/order') || $this->user->hasPermission('modify', 'extension/shipping/pathao');
	}

	/**
	 * Convert PHP fatal errors during AJAX into a JSON response so the modal
	 * shows a real message instead of jQuery's generic "Error: error".
	 */
	private function pathaoOrderInstallShutdownGuard() {
		register_shutdown_function(function () {
			$err = error_get_last();
			if (!$err) { return; }
			$fatal = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR);
			if (!in_array((int)$err['type'], $fatal, true)) { return; }
			while (ob_get_level()) { @ob_end_clean(); }
			if (!headers_sent()) {
				header('Content-Type: application/json; charset=utf-8', true, 500);
			}
			$msg = 'PHP error: ' . (string)$err['message'] . ' in ' . basename((string)$err['file']) . ':' . (int)$err['line'];
			echo json_encode(array('success' => false, 'message' => $msg));
		});
	}

	public function createFromOrder() {
		@ini_set('display_errors', '0');
		@ini_set('log_errors', '1');
		error_reporting(0);
		// Live Pathao API can be slow; default PHP max_execution_time (30s) + cURL timeout
		// would otherwise kill the script after the order had already been created at Pathao.
		@set_time_limit(120);
		@ignore_user_abort(true);
		$this->pathaoOrderInstallShutdownGuard();

		try {
			if (!$this->pathaoCanCreateFromOrder()) {
				return $this->pathaoOrderJsonOut(array('success' => false, 'message' => 'Permission denied'));
			}

			$order_id = isset($this->request->post['order_id']) ? (int)$this->request->post['order_id'] : 0;
			if (!$order_id) {
				return $this->pathaoOrderJsonOut(array('success' => false, 'message' => 'Order ID is required'));
			}

			$override = array(
				'merchant_order_id' => isset($this->request->post['merchant_order_id']) ? (string)$this->request->post['merchant_order_id'] : '',
				'store_id' => isset($this->request->post['store_id']) ? (int)$this->request->post['store_id'] : 0,
				'recipient_city' => isset($this->request->post['recipient_city']) ? (int)$this->request->post['recipient_city'] : 0,
				'recipient_zone' => isset($this->request->post['recipient_zone']) ? (int)$this->request->post['recipient_zone'] : 0,
				'recipient_area' => isset($this->request->post['recipient_area']) ? (int)$this->request->post['recipient_area'] : 0,
				'recipient_name' => isset($this->request->post['recipient_name']) ? (string)$this->request->post['recipient_name'] : '',
				'recipient_phone' => isset($this->request->post['recipient_phone']) ? (string)$this->request->post['recipient_phone'] : '',
				'recipient_secondary_phone' => isset($this->request->post['recipient_secondary_phone']) ? (string)$this->request->post['recipient_secondary_phone'] : '',
				'recipient_address' => isset($this->request->post['recipient_address']) ? (string)$this->request->post['recipient_address'] : '',
				'item_type' => isset($this->request->post['item_type']) ? (int)$this->request->post['item_type'] : 2,
				'item_quantity' => isset($this->request->post['item_quantity']) ? (int)$this->request->post['item_quantity'] : 0,
				'item_weight' => isset($this->request->post['item_weight']) ? (float)$this->request->post['item_weight'] : 0.0,
				'item_description' => isset($this->request->post['item_description']) ? (string)$this->request->post['item_description'] : '',
				'amount_to_collect' => isset($this->request->post['amount_to_collect']) ? (int)$this->request->post['amount_to_collect'] : -1,
				'delivery_type' => isset($this->request->post['delivery_type']) ? (int)$this->request->post['delivery_type'] : 48,
				'special_instruction' => isset($this->request->post['special_instruction']) ? (string)$this->request->post['special_instruction'] : '',
			);
			if (isset($override['amount_to_collect']) && (int)$override['amount_to_collect'] < 0) {
				unset($override['amount_to_collect']);
			}

			$this->load->model('extension/shipping/pathao');
			$res = $this->model_extension_shipping_pathao->createOrder($order_id, $override);

			if (!empty($res['success'])) {
				return $this->pathaoOrderJsonOut(array(
					'success' => true,
					'message' => 'Created. Consignment: ' . (isset($res['consignment_id']) ? $res['consignment_id'] : ''),
					'data' => $res
				));
			}

			return $this->pathaoOrderJsonOut(array(
				'success' => false,
				'message' => isset($res['message']) ? $res['message'] : 'Failed',
				'data' => $res
			));
		} catch (\Throwable $e) {
			return $this->pathaoOrderJsonOut(array(
				'success' => false,
				'message' => 'Pathao exception: ' . $e->getMessage(),
			));
		}
	}

	public function bulkCreateFromOrders() {
		@ini_set('display_errors', '0');
		@ini_set('log_errors', '1');
		error_reporting(0);
		@set_time_limit(300);
		@ignore_user_abort(true);
		$this->pathaoOrderInstallShutdownGuard();

		try {
			if (!$this->pathaoCanCreateFromOrder()) {
				return $this->pathaoOrderJsonOut(array('success' => false, 'message' => 'Permission denied'));
			}

			$order_ids = array();
			if (isset($this->request->post['order_ids'])) {
				$order_ids = $this->request->post['order_ids'];
				if (!is_array($order_ids)) { $order_ids = array($order_ids); }
			}

			$order_ids = array_values(array_filter(array_map('intval', $order_ids)));
			if (!$order_ids) {
				return $this->pathaoOrderJsonOut(array('success' => false, 'message' => 'Order ID is required'));
			}

			$override = array(
				'store_id' => isset($this->request->post['store_id']) ? (int)$this->request->post['store_id'] : 0,
				'recipient_city' => isset($this->request->post['recipient_city']) ? (int)$this->request->post['recipient_city'] : 0,
				'recipient_zone' => isset($this->request->post['recipient_zone']) ? (int)$this->request->post['recipient_zone'] : 0,
				'recipient_area' => isset($this->request->post['recipient_area']) ? (int)$this->request->post['recipient_area'] : 0,
				'recipient_name' => isset($this->request->post['recipient_name']) ? (string)$this->request->post['recipient_name'] : '',
				'recipient_phone' => isset($this->request->post['recipient_phone']) ? (string)$this->request->post['recipient_phone'] : '',
				'recipient_secondary_phone' => isset($this->request->post['recipient_secondary_phone']) ? (string)$this->request->post['recipient_secondary_phone'] : '',
				'recipient_address' => isset($this->request->post['recipient_address']) ? (string)$this->request->post['recipient_address'] : '',
				'item_type' => isset($this->request->post['item_type']) ? (int)$this->request->post['item_type'] : 2,
				'item_quantity' => isset($this->request->post['item_quantity']) ? (int)$this->request->post['item_quantity'] : 0,
				'item_weight' => isset($this->request->post['item_weight']) ? (float)$this->request->post['item_weight'] : 0.0,
				'item_description' => isset($this->request->post['item_description']) ? (string)$this->request->post['item_description'] : '',
				'amount_to_collect' => isset($this->request->post['amount_to_collect']) ? (int)$this->request->post['amount_to_collect'] : -1,
				'delivery_type' => isset($this->request->post['delivery_type']) ? (int)$this->request->post['delivery_type'] : 48,
				'special_instruction' => isset($this->request->post['special_instruction']) ? (string)$this->request->post['special_instruction'] : '',
			);
			if (isset($override['amount_to_collect']) && (int)$override['amount_to_collect'] < 0) {
				unset($override['amount_to_collect']);
			}

			$this->load->model('extension/shipping/pathao');

			$created = 0;
			$errors = array();
			foreach ($order_ids as $oid) {
				try {
					$r = $this->model_extension_shipping_pathao->createOrder((int)$oid, $override);
				} catch (\Throwable $e) {
					$r = array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
				}
				if (!empty($r['success'])) {
					$created++;
				} else {
					$errors[] = array('order_id' => $oid, 'message' => isset($r['message']) ? $r['message'] : 'Failed');
				}
			}

			$msg = 'Created: ' . $created . ' (Errors: ' . count($errors) . ')';
			if ($errors) {
				$msg .= ' - Order #' . $errors[0]['order_id'] . ': ' . $errors[0]['message'];
			}

			return $this->pathaoOrderJsonOut(array(
				'success' => ($created > 0),
				'message' => $msg,
				'created' => $created,
				'errors' => $errors
			));
		} catch (\Throwable $e) {
			return $this->pathaoOrderJsonOut(array(
				'success' => false,
				'message' => 'Pathao exception: ' . $e->getMessage(),
			));
		}
	}

	/**
	 * Pathao location lists for order list modal (dropdowns).
	 */
	private function pathaoCanUseLocations() {
		return $this->user->hasPermission('access', 'sale/order') || $this->user->hasPermission('access', 'extension/shipping/pathao');
	}

	private function pathaoParseItemList($parsed) {
		if (!is_array($parsed)) {
			return array();
		}
		if (isset($parsed['data']['data']['data']) && is_array($parsed['data']['data']['data'])) {
			return $parsed['data']['data']['data'];
		}
		if (isset($parsed['data']['data']) && is_array($parsed['data']['data'])) {
			return $parsed['data']['data'];
		}
		if (isset($parsed['data']) && is_array($parsed['data'])) {
			return $parsed['data'];
		}
		return array();
	}

	public function cities() {
		$this->pathaoJsonLocations(function ($client) {
			return $client->get('countries/1/city-list');
		});
	}

	public function zones() {
		$city_id = isset($this->request->get['city_id']) ? (int)$this->request->get['city_id'] : 0;
		if ($city_id <= 0) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(array('success' => false, 'message' => 'City is required', 'data' => array())));
			return;
		}
		$this->pathaoJsonLocations(function ($client) use ($city_id) {
			return $client->get('cities/' . (int)$city_id . '/zone-list');
		});
	}

	public function areas() {
		$zone_id = isset($this->request->get['zone_id']) ? (int)$this->request->get['zone_id'] : 0;
		if ($zone_id <= 0) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(array('success' => false, 'message' => 'Zone is required', 'data' => array())));
			return;
		}
		$this->pathaoJsonLocations(function ($client) use ($zone_id) {
			return $client->get('zones/' . (int)$zone_id . '/area-list');
		});
	}

	private function pathaoJsonLocations($callback) {
		@ini_set('display_errors', '0');
		error_reporting(0);
		while (ob_get_level()) { @ob_end_clean(); }
		$this->response->addHeader('Content-Type: application/json');

		if (!$this->pathaoCanUseLocations()) {
			$this->response->setOutput(json_encode(array('success' => false, 'message' => 'Permission denied', 'data' => array())));
			return;
		}

		require_once(DIR_SYSTEM . 'library/pathao/logger.php');
		require_once(DIR_SYSTEM . 'library/pathao/token_manager.php');
		require_once(DIR_SYSTEM . 'library/pathao/client.php');

		list($env, $logger, $token, $client) = $this->pathaoLibs();
		$res = $callback($client);
		if (empty($res['success'])) {
			$this->response->setOutput(json_encode(array(
				'success' => false,
				'message' => isset($res['message']) ? $res['message'] : 'API error',
				'data' => array()
			)));
			return;
		}
		$list = $this->pathaoParseItemList(isset($res['data']) ? $res['data'] : array());
		$this->response->setOutput(json_encode(array('success' => true, 'message' => '', 'data' => $list)));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/pathao')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
}

