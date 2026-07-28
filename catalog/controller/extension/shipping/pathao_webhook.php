<?php
class ControllerExtensionShippingPathaoWebhook extends Controller {
	public function index() {
		require_once(DIR_SYSTEM . 'library/pathao/logger.php');

		$env = (defined('PATHAO_COURIER_ENV') && strtolower((string)PATHAO_COURIER_ENV) === 'live') ? 'live' : (((int)$this->config->get('shipping_pathao_sandbox')) ? 'sandbox' : 'live');
		$logger = new PathaoLogger((int)$this->config->get('shipping_pathao_logging'), $env);

		if (!(int)$this->config->get('shipping_pathao_status')) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(array('success' => false, 'message' => 'Pathao is disabled')));
			return;
		}

		$raw = (string)file_get_contents('php://input');
		$secret = trim((string)$this->config->get('shipping_pathao_webhook_secret'));

		if ($secret !== '') {
			$valid = false;
			$given = '';
			if (isset($this->request->server['HTTP_X_PATHAO_SIGNATURE'])) {
				$given = (string)$this->request->server['HTTP_X_PATHAO_SIGNATURE'];
			} elseif (isset($this->request->server['HTTP_X_WEBHOOK_SIGNATURE'])) {
				$given = (string)$this->request->server['HTTP_X_WEBHOOK_SIGNATURE'];
			}
			$expected = hash_hmac('sha256', $raw, $secret);
			if ($given !== '' && hash_equals($expected, $given)) {
				$valid = true;
			}
			$key = isset($this->request->get['key']) ? (string)$this->request->get['key'] : '';
			if ($key !== '' && hash_equals($secret, $key)) {
				$valid = true;
			}
			if (!$valid) {
				$logger->write('webhook', 'rejected: invalid or missing secret', array('endpoint' => 'pathao_webhook'));
				$this->response->addHeader('Content-Type: application/json');
				$this->response->addHeader('HTTP/1.1 403 Forbidden');
				$this->response->setOutput(json_encode(array('success' => false, 'message' => 'Forbidden')));
				return;
			}
		} else {
			$logger->write('webhook', 'warning: webhook received without secret configured (set shipping_pathao_webhook_secret in admin)', array('endpoint' => 'pathao_webhook'));
		}

		$payload = json_decode($raw, true);
		if (!is_array($payload)) {
			$payload = array();
		}
		$flat = $this->pathaoFlattenWebhook($payload);

		$consignment_id = $this->pathaoPickString($flat, array('consignment_id', 'consignmentId', 'id'));
		$status = $this->pathaoPickString($flat, array('order_status', 'status', 'orderStatus'));
		$tracking_code = $this->pathaoPickString($flat, array('tracking_code', 'trackingCode', 'tracking'));

		$logger->write('webhook', 'event', array(
			'endpoint' => 'pathao_webhook',
			'consignment_id' => $consignment_id,
			'order_status' => $status,
		));

		if ($consignment_id === '') {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(array('success' => true, 'message' => 'No consignment_id (ignored)')));
			return;
		}

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "pathao_order` (
			`id` INT AUTO_INCREMENT PRIMARY KEY,
			`order_id` INT NOT NULL,
			`consignment_id` VARCHAR(100),
			`tracking_code` VARCHAR(100),
			`status` VARCHAR(50),
			`delivery_fee` DECIMAL(10,2),
			`created_at` DATETIME,
			`updated_at` DATETIME,
			KEY `order_id` (`order_id`),
			KEY `consignment_id` (`consignment_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("UPDATE `" . DB_PREFIX . "pathao_order` SET
			`status` = '" . $this->db->escape($status) . "',
			`tracking_code` = IF(`tracking_code`='', '" . $this->db->escape($tracking_code) . "', `tracking_code`),
			`updated_at` = NOW()
			WHERE `consignment_id` = '" . $this->db->escape($consignment_id) . "'");

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(array('success' => true, 'message' => 'OK')));
	}

	/**
	 * @param array $p
	 * @return array
	 */
	private function pathaoFlattenWebhook($p) {
		if (!is_array($p)) {
			return array();
		}
		if (isset($p['data']) && is_array($p['data']) && (isset($p['data']['consignment_id']) || isset($p['data']['id']) || isset($p['data']['order_status']) || isset($p['data']['status']))) {
			return array_merge($p, $p['data']);
		}
		return $p;
	}

	/**
	 * @param array  $a
	 * @param string[] $keys
	 * @return string
	 */
	private function pathaoPickString($a, $keys) {
		foreach ($keys as $k) {
			if (isset($a[$k]) && $a[$k] !== '') {
				return (string)$a[$k];
			}
		}
		return '';
	}
}
