<?php
class ModelExtensionShippingPathao extends Model {
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "pathao_order` (
			`id` INT AUTO_INCREMENT PRIMARY KEY,
			`order_id` INT NOT NULL,
			`consignment_id` VARCHAR(100),
			`tracking_code` VARCHAR(100),
			`status` VARCHAR(50),
			`delivery_fee` DECIMAL(10,2),
			`created_at` DATETIME,
			`updated_at` DATETIME,
			KEY `order_id` (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "pathao_store` (
			`store_id` INT PRIMARY KEY,
			`store_name` VARCHAR(255),
			`is_default` TINYINT(1)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "pathao_token` (
			`id` INT AUTO_INCREMENT PRIMARY KEY,
			`is_sandbox` TINYINT(1),
			`access_token` TEXT,
			`refresh_token` TEXT,
			`expires_at` DATETIME,
			`updated_at` DATETIME
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "pathao_order`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "pathao_store`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "pathao_token`");
	}

	public function getStores() {
		$q = $this->db->query("SELECT store_id, store_name, is_default FROM `" . DB_PREFIX . "pathao_store` ORDER BY is_default DESC, store_name ASC");
		return $q->rows;
	}

	/** Same as getStores(); name kept for order list (backup) compatibility. */
	public function getStoresFromDb() {
		return $this->getStores();
	}

	public function getDefaultStoreId() {
		$q = $this->db->query("SELECT store_id FROM `" . DB_PREFIX . "pathao_store` WHERE is_default = 1 LIMIT 1");
		return $q->num_rows ? (int)$q->row['store_id'] : 0;
	}

	public function setDefaultStore($store_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "pathao_store` SET is_default = 0");
		$this->db->query("UPDATE `" . DB_PREFIX . "pathao_store` SET is_default = 1 WHERE store_id = '" . (int)$store_id . "'");
	}

	public function saveStores($stores, $default_store_id = 0) {
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "pathao_store`");
		foreach ((array)$stores as $s) {
			$id = isset($s['store_id']) ? (int)$s['store_id'] : (isset($s['id']) ? (int)$s['id'] : 0);
			$name = isset($s['store_name']) ? (string)$s['store_name'] : (isset($s['name']) ? (string)$s['name'] : '');
			if (!$id) {
				continue;
			}
			$is_default = ($default_store_id && $id == (int)$default_store_id) ? 1 : 0;
			$this->db->query("INSERT INTO `" . DB_PREFIX . "pathao_store` SET store_id='" . (int)$id . "', store_name='" . $this->db->escape($name) . "', is_default='" . (int)$is_default . "'");
		}
	}

	/**
	 * Build data for the admin "Send with Pathao" modal (single order) from order / products / totals.
	 *
	 * @param int $order_id
	 * @return array{success:bool,message?:string}&array
	 */
	public function getPathaoOrderData($order_id) {
		$order_id = (int)$order_id;
		if ($order_id < 1) {
			return array('success' => false, 'message' => 'Invalid order');
		}

		$this->load->model('sale/order');
		$order = $this->model_sale_order->getOrder($order_id);
		if (!$order) {
			return array('success' => false, 'message' => 'Order not found');
		}

		$products = $this->model_sale_order->getOrderProducts($order_id);
		$totals = $this->model_sale_order->getOrderTotals($order_id);
		$order_total = 0.0;
		foreach ((array)$totals as $t) {
			if (isset($t['code']) && (string)$t['code'] === 'total' && isset($t['value'])) {
				$order_total = (float)$t['value'];
			}
		}
		if ($order_total <= 0) {
			$order_total = (float)($order['total'] ?? 0);
		}
		$amount_collect = (int)round($order_total);

		$store_id = (int)$this->config->get('shipping_pathao_default_store_id');
		$q = $this->db->query("SELECT `store_id` FROM `" . DB_PREFIX . "pathao_store` WHERE `is_default` = 1 LIMIT 1");
		if ($q->num_rows) {
			$store_id = (int)$q->row['store_id'];
		}
		if ($store_id < 1) {
			$q2 = $this->db->query("SELECT `store_id` FROM `" . DB_PREFIX . "pathao_store` ORDER BY `store_id` ASC LIMIT 1");
			if ($q2->num_rows) {
				$store_id = (int)$q2->row['store_id'];
			}
		}

		$recipient_name = trim((string)($order['shipping_firstname'] ?? '') . ' ' . (string)($order['shipping_lastname'] ?? ''));
		if ($recipient_name === '') {
			$recipient_name = trim((string)($order['firstname'] ?? '') . ' ' . (string)($order['lastname'] ?? ''));
		}
		$phone = (string)($order['telephone'] ?? '');

		$addr = trim((string)($order['shipping_address_1'] ?? '') . ' ' . (string)($order['shipping_address_2'] ?? ''));
		$addr = trim($addr);
		if (!empty($order['shipping_city'])) {
			$addr = trim($addr . ', ' . (string)$order['shipping_city']);
		}
		if (!empty($order['shipping_zone'])) {
			$addr = trim($addr . ', ' . (string)$order['shipping_zone']);
		}
		if (!empty($order['shipping_postcode'])) {
			$addr = trim($addr . ' ' . (string)$order['shipping_postcode']);
		}

		$qty = 0;
		$weight = 0.0;
		$desc = array();
		$ccode = (string)($order['currency_code'] ?? '');

		foreach ((array)$products as $p) {
			$pq = (int)($p['quantity'] ?? 0);
			$qty += $pq;
			$w = (float)$this->pathaoProductWeightById((int)($p['product_id'] ?? 0));
			if ($w <= 0) {
				$w = 0.5;
			}
			$weight += $w * $pq;
			$pr = isset($p['price']) ? (float)$p['price'] : 0.0;
			$desc[] = (string)($p['name'] ?? '') . ' x' . $pq . ' @' . $this->pathaoFormatPrice($pr, $ccode);
		}
		if ($weight <= 0) {
			$weight = 0.5;
		}
		$weight = $this->pathaoClampItemWeightForApi($weight);
		$item_desc = $desc ? implode(', ', $desc) : 'Order';
		$comment = isset($order['comment']) ? (string)$order['comment'] : '';
		//$sec_phone = $this->pathaoExtractCustomFieldPhone($order);
		$sec_phone = (string)($order['secondary_telephone'] ?? '');

		$data = array(
			'success' => true,
			'store_id' => $store_id,
			'order_id' => $order_id,
			'merchant_order_id' => (string)$order_id,
			'item_type' => 2,
			'recipient_name' => $recipient_name,
			'recipient_phone' => $phone,
			'recipient_secondary_phone' => $sec_phone,
			'recipient_address' => $addr,
			'item_quantity' => (int)$qty,
			'item_weight' => (float)$weight,
			'amount_to_collect' => (int)max(0, $amount_collect),
			'item_description' => $item_desc,
			'special_instruction' => $comment,
			'delivery_type' => 48,
		);
		return $data;
	}

	/**
	 * Match Pathao API: 0.001 – 200 kg.
	 */
	private function pathaoClampItemWeightForApi($w) {
		$w = (float)$w;
		if ($w <= 0) {
			$w = 0.5;
		}
		if ($w < 0.001) {
			$w = 0.001;
		}
		if ($w > 200) {
			$w = 200.0;
		}
		return (float)round($w, 3);
	}

	/**
	 * @param int $product_id
	 * @return float
	 */
	private function pathaoProductWeightById($product_id) {
		if ($product_id < 1) {
			return 0.0;
		}
		$q = $this->db->query("SELECT `weight` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$product_id . "' LIMIT 1");
		if ($q->num_rows) {
			return (float)$q->row['weight'];
		}
		return 0.0;
	}

	/**
	 * @param float  $v
	 * @param string $ccode
	 * @return string
	 */
	private function pathaoFormatPrice($v, $ccode) {
		$v = (float)$v;
		$out = number_format($v, 2, '.', ',');
		return ($ccode !== '' ? $ccode . ' ' : '') . $out;
	}

	/**
	 * @param array $order
	 * @return string
	 */
	private function pathaoExtractCustomFieldPhone($order) {
		$acc = $this->pathaoStringFromCustomField($order, 'custom_field', 'shipping_custom_field', 'payment_custom_field');
		if (preg_match('/[+\d][\d\s\-\(\)]{7,}/u', (string)$acc, $m)) {
			return trim($m[0]);
		}
		return '';
	}

	private function pathaoStringFromCustomField($order) {
		$parts = array();
		$args = array_slice(func_get_args(), 1);
		foreach ($args as $key) {
			if (empty($order[$key]) || !is_array($order[$key])) {
				continue;
			}
			foreach ($order[$key] as $k => $v) {
				if (is_string($v) && $v !== '') {
					$parts[] = trim($v);
				} elseif (is_scalar($v)) {
					$parts[] = trim((string)$v);
				}
			}
		}
		return $parts ? implode(' ', $parts) : '';
	}

	/**
	 * Pathao API: item_weight 0.001 – 200 kg.
	 */
	private function pathaoCreateClampWeight($w) {
		$w = (float)$w;
		if ($w <= 0) { $w = 0.5; }
		if ($w < 0.001) { $w = 0.001; }
		if ($w > 200) { $w = 200.0; }
		return (float)round($w, 3);
	}

	private function pathaoCreateItemDescriptionString($s, $max) {
		$s = (string)$s;
		$max = (int)$max;
		if ($max < 10) { $max = 500; }
		if (function_exists('mb_substr') && function_exists('mb_strlen') && mb_strlen($s, 'UTF-8') > $max) {
			return (string)mb_substr($s, 0, $max, 'UTF-8');
		}
		if (strlen($s) > $max) {
			return (string)substr($s, 0, $max);
		}
		return $s;
	}

	private function pathaoCreateParseListBody($parsed) {
		if (!is_array($parsed)) { return array(); }
		if (isset($parsed['data']['data']['data']) && is_array($parsed['data']['data']['data'])) { return $parsed['data']['data']['data']; }
		if (isset($parsed['data']['data']) && is_array($parsed['data']['data'])) { return $parsed['data']['data']; }
		if (isset($parsed['data']) && is_array($parsed['data'])) { return $parsed['data']; }
		return array();
	}

	private function pathaoCreateAddressLine($order, $override) {
		if (isset($override['recipient_address']) && trim((string)$override['recipient_address']) !== '') {
			return trim((string)$override['recipient_address']);
		}
		$line = trim((string)($order['shipping_address_1'] ?? '') . ' ' . (string)($order['shipping_address_2'] ?? ''));
		if (!empty($order['shipping_city']))     { $line = trim($line . ', ' . (string)$order['shipping_city']); }
		if (!empty($order['shipping_zone']))     { $line = trim($line . ', ' . (string)$order['shipping_zone']); }
		if (!empty($order['shipping_postcode'])) { $line = trim($line . ' ' . (string)$order['shipping_postcode']); }
		return $line;
	}

	private function pathaoCreateGetStoreId($override) {
		$store_id = isset($override['store_id']) ? (int)$override['store_id'] : 0;
		if ($store_id < 1) { $store_id = (int)$this->config->get('shipping_pathao_default_store_id'); }
		if ($store_id < 1) {
			$q = $this->db->query("SELECT store_id FROM `" . DB_PREFIX . "pathao_store` WHERE is_default = 1 LIMIT 1");
			if ($q->num_rows) { $store_id = (int)$q->row['store_id']; }
		}
		if ($store_id < 1) {
			$q2 = $this->db->query("SELECT store_id FROM `" . DB_PREFIX . "pathao_store` ORDER BY store_id ASC LIMIT 1");
			if ($q2->num_rows) { $store_id = (int)$q2->row['store_id']; }
		}
		return $store_id;
	}

	private function pathaoCreateComputeWeight($products) {
		$sum = 0.0;
		foreach ((array)$products as $p) {
			$q = (int)($p['quantity'] ?? 0);
			if ($q < 1) { continue; }
			$pid = (int)($p['product_id'] ?? 0);
			$w = 0.5;
			if ($pid > 0) {
				$r = $this->db->query("SELECT `weight` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . $pid . "' LIMIT 1");
				if ($r->num_rows && (float)$r->row['weight'] > 0) { $w = (float)$r->row['weight']; }
			}
			$sum += $w * $q;
		}
		if ($sum <= 0) { $sum = 0.5; }
		return (float)$sum;
	}

	private function pathaoCreateResolveLocation($order, $override, $client, $logger, $order_id, $address_for_matching = null) {
		$order_id = (int)$order_id;
		$city = isset($override['recipient_city']) ? (int)$override['recipient_city'] : 0;
		$zone = isset($override['recipient_zone']) ? (int)$override['recipient_zone'] : 0;
		$area = isset($override['recipient_area']) ? (int)$override['recipient_area'] : 0;
		if ($city > 0 && $zone > 0 && $area > 0) {
			return array('city' => $city, 'zone' => $zone, 'area' => $area, 'source' => 'form');
		}
		if ($city <= 0 && $zone <= 0 && $area <= 0) {
			return array('city' => 0, 'zone' => 0, 'area' => 0, 'source' => 'address_only');
		}

		$line_for_match = ($address_for_matching !== null && (string)$address_for_matching !== '')
			? (string)$address_for_matching
			: '';
		if ($line_for_match === '') {
			$line_for_match = trim((string)($order['shipping_address_1'] ?? '') . ' ' . (string)($order['shipping_address_2'] ?? ''));
			if (!empty($order['shipping_city']))     { $line_for_match = trim($line_for_match . ', ' . (string)$order['shipping_city']); }
			if (!empty($order['shipping_zone']))     { $line_for_match = trim($line_for_match . ', ' . (string)$order['shipping_zone']); }
			if (!empty($order['shipping_postcode'])) { $line_for_match = trim($line_for_match . ' ' . (string)$order['shipping_postcode']); }
		}
		$haystack = mb_strtolower($line_for_match, 'UTF-8');
		$zoneText = !empty($order['shipping_zone']) ? mb_strtolower(trim((string)$order['shipping_zone']), 'UTF-8') : '';

		if ($city <= 0) {
			$rc = $client->get('countries/1/city-list');
			if (empty($rc['success'])) {
				return array('city' => $city, 'zone' => $zone, 'area' => $area, 'source' => 'partial');
			}
			$cities = $this->pathaoCreateParseListBody(isset($rc['data']) ? $rc['data'] : array());
			if ($zoneText !== '') {
				foreach ($cities as $c) {
					if (!is_array($c)) { continue; }
					$cid = isset($c['city_id']) ? (int)$c['city_id'] : (isset($c['id']) ? (int)$c['id'] : 0);
					$cname = isset($c['city_name']) ? (string)$c['city_name'] : (isset($c['name']) ? (string)$c['name'] : '');
					if ($cid && $cname !== '' && $zoneText === mb_strtolower($cname, 'UTF-8')) { $city = $cid; break; }
				}
			}
			if ($city <= 0 && $haystack !== '') {
				$best = 0; $bestlen = 0;
				foreach ($cities as $c) {
					if (!is_array($c)) { continue; }
					$cid = isset($c['city_id']) ? (int)$c['city_id'] : (isset($c['id']) ? (int)$c['id'] : 0);
					$cname = isset($c['city_name']) ? (string)$c['city_name'] : (isset($c['name']) ? (string)$c['name'] : '');
					$low = $cname !== '' ? mb_strtolower($cname, 'UTF-8') : '';
					if ($cid && $low !== '' && mb_strpos($haystack, $low) !== false) {
						$len = mb_strlen($cname, 'UTF-8');
						if ($len > $bestlen) { $bestlen = $len; $best = $cid; }
					}
				}
				if ($best > 0) { $city = $best; }
			}
		}

		if ($city <= 0) {
			return array('city' => 0, 'zone' => 0, 'area' => 0, 'source' => 'guessed', 'guessed' => false);
		}

		if ($zone <= 0) {
			$rz = $client->get('cities/' . (int)$city . '/zone-list');
			if (empty($rz['success'])) {
				return array('city' => $city, 'zone' => $zone, 'area' => $area, 'source' => 'partial');
			}
			$zones = $this->pathaoCreateParseListBody(isset($rz['data']) ? $rz['data'] : array());
			if ($zoneText !== '') {
				foreach ($zones as $z) {
					if (!is_array($z)) { continue; }
					$zid = isset($z['zone_id']) ? (int)$z['zone_id'] : (isset($z['id']) ? (int)$z['id'] : 0);
					$zname = isset($z['zone_name']) ? (string)$z['zone_name'] : (isset($z['name']) ? (string)$z['name'] : '');
					if ($zid && $zname !== '' && $zoneText === mb_strtolower(trim($zname), 'UTF-8')) { $zone = $zid; break; }
				}
			}
			if ($zone <= 0 && $haystack !== '') {
				$best = 0; $bestlen = 0;
				foreach ($zones as $z) {
					if (!is_array($z)) { continue; }
					$zid = isset($z['zone_id']) ? (int)$z['zone_id'] : (isset($z['id']) ? (int)$z['id'] : 0);
					$zname = isset($z['zone_name']) ? (string)$z['zone_name'] : (isset($z['name']) ? (string)$z['name'] : '');
					$low = $zname !== '' ? mb_strtolower($zname, 'UTF-8') : '';
					if ($zid && $low !== '' && mb_strpos($haystack, $low) !== false) {
						$len = mb_strlen($zname, 'UTF-8');
						if ($len > $bestlen) { $bestlen = $len; $best = $zid; }
					}
				}
				if ($best > 0) { $zone = $best; }
			}
		}

		if ($zone <= 0) {
			return array('city' => $city, 'zone' => 0, 'area' => 0, 'source' => 'guessed', 'guessed' => true);
		}

		if ($area <= 0) {
			$ra = $client->get('zones/' . (int)$zone . '/area-list');
			if (empty($ra['success'])) {
				return array('city' => $city, 'zone' => $zone, 'area' => $area, 'source' => 'partial');
			}
			$areas = $this->pathaoCreateParseListBody(isset($ra['data']) ? $ra['data'] : array());
			if ($haystack !== '') {
				$best = 0; $bestlen = 0;
				foreach ($areas as $a) {
					if (!is_array($a)) { continue; }
					$aid = isset($a['area_id']) ? (int)$a['area_id'] : (isset($a['id']) ? (int)$a['id'] : 0);
					$aname = isset($a['area_name']) ? (string)$a['area_name'] : (isset($a['name']) ? (string)$a['name'] : '');
					$low = $aname !== '' ? mb_strtolower($aname, 'UTF-8') : '';
					if ($aid && $low !== '' && mb_strpos($haystack, $low) !== false) {
						$len = mb_strlen($aname, 'UTF-8');
						if ($len > $bestlen) { $bestlen = $len; $best = $aid; }
					}
				}
				if ($best > 0) { $area = $best; }
			}
		}

		return array('city' => $city, 'zone' => $zone, 'area' => $area, 'source' => 'guessed', 'guessed' => true);
	}

	private function pathaoCreateLibs() {
		require_once(DIR_SYSTEM . 'library/pathao/logger.php');
		require_once(DIR_SYSTEM . 'library/pathao/token_manager.php');
		require_once(DIR_SYSTEM . 'library/pathao/client.php');

		$env = $this->getEnvironment();
		$logger = new PathaoLogger((int)$this->config->get('shipping_pathao_logging'), $env);
		$token = new PathaoTokenManager($this->db, $this->config, $logger);
		$client = new PathaoClient($token, $logger, $env);
		return array($logger, $token, $client);
	}

	public function getEnvironment() {
		if (defined('PATHAO_COURIER_ENV')) {
			$env = strtolower(trim((string)PATHAO_COURIER_ENV));
			if ($env === 'sandbox' || $env === 'staging') { return 'sandbox'; }
			if ($env === 'live' || $env === 'production') { return 'live'; }
		}
		return ((int)$this->config->get('shipping_pathao_sandbox')) ? 'sandbox' : 'live';
	}

	/**
	 * Create Pathao consignment from an OpenCart order.
	 * Inlined (no eval/delegate) so a PHP error always returns a JSON-friendly array
	 * instead of crashing the script and producing the dreaded "Error: error" in the modal.
	 *
	 * @param int   $order_id
	 * @param array $override
	 * @return array
	 */
	public function createOrder($order_id, $override = array()) {
		try {
			list($logger, $token, $client) = $this->pathaoCreateLibs();
		} catch (\Throwable $e) {
			return array('success' => false, 'message' => 'Pathao init failed: ' . $e->getMessage());
		}

		$order_id = (int)$order_id;
		if (!$order_id) {
			return array('success' => false, 'message' => 'Order ID is required');
		}

		$this->load->model('sale/order');
		$order = $this->model_sale_order->getOrder($order_id);
		if (!$order) {
			return array('success' => false, 'message' => 'Order not found');
		}
		$products = $this->model_sale_order->getOrderProducts($order_id);

		$desc = array();
		$qty = 0;
		foreach ((array)$products as $p) {
			$qty += (int)$p['quantity'];
			$desc[] = (string)($p['name'] ?? '') . ' x' . (int)$p['quantity'];
		}
		$item_desc_default = implode(', ', array_slice($desc, 0, 20));

		$store_id = $this->pathaoCreateGetStoreId($override);
		if ($store_id < 1) {
			return array('success' => false, 'message' => 'No Pathao store. Sync stores in the Pathao admin settings and pick a default store.');
		}

		$addr_line = $this->pathaoCreateAddressLine($order, $override);

		try {
			$loc = $this->pathaoCreateResolveLocation($order, $override, $client, $logger, $order_id, $addr_line);
		} catch (\Throwable $e) {
			$loc = array('city' => 0, 'zone' => 0, 'area' => 0, 'source' => 'address_only');
		}
		$client->setLogContext(array('order_id' => $order_id));

		$recipient_name = trim((string)($order['shipping_firstname'] ?? '') . ' ' . (string)($order['shipping_lastname'] ?? ''));
		if ($recipient_name === '') {
			$recipient_name = trim((string)($order['firstname'] ?? '') . ' ' . (string)($order['lastname'] ?? ''));
		}
		if (isset($override['recipient_name']) && trim((string)$override['recipient_name']) !== '') {
			$recipient_name = trim((string)$override['recipient_name']);
		}
		$recipient_phone = (string)($order['telephone'] ?? '');
		if (isset($override['recipient_phone']) && trim((string)$override['recipient_phone']) !== '') {
			$recipient_phone = trim((string)$override['recipient_phone']);
		}

		$item_qty = (int)$qty;
		if (isset($override['item_quantity']) && (int)$override['item_quantity'] > 0) {
			$item_qty = (int)$override['item_quantity'];
		}
		$item_weight = $this->pathaoCreateComputeWeight($products);
		if (isset($override['item_weight']) && is_numeric($override['item_weight'])) {
			$w = (float)$override['item_weight'];
			if ($w > 0) { $item_weight = $w; }
		}
		$item_weight = $this->pathaoCreateClampWeight($item_weight);

		$item_text = $item_desc_default;
		if (isset($override['item_description']) && trim((string)$override['item_description']) !== '') {
			$item_text = trim((string)$override['item_description']);
		}

		$amount_collect = (int)round((float)($order['total'] ?? 0));
		if (isset($override['amount_to_collect']) && (string)$override['amount_to_collect'] !== '' && (int)$override['amount_to_collect'] >= 0) {
			$amount_collect = (int)$override['amount_to_collect'];
		}

		$si = '';
		if (isset($override['special_instruction']) && trim((string)$override['special_instruction']) !== '') {
			$si = (string)$override['special_instruction'];
		} elseif (isset($order['comment'])) {
			$si = trim((string)$order['comment']);
		}
		if (isset($override['recipient_secondary_phone']) && trim((string)$override['recipient_secondary_phone']) !== '') {
			$sp = trim((string)$override['recipient_secondary_phone']);
			$si = trim("Alt phone: " . $sp . ($si !== '' ? "\n" : '') . $si);
		}

		$merchant_ref = (string)$order_id;
		if (isset($override['merchant_order_id']) && trim((string)$override['merchant_order_id']) !== '') {
			$merchant_ref = trim((string)$override['merchant_order_id']);
		}

		$item_type = (int)(isset($override['item_type']) ? $override['item_type'] : 2);
		if ($item_type === 3) { $item_type = 2; $item_text = '[Fragile] ' . $item_text; }
		$item_type = (int)max(1, min(2, $item_type));

		$payload = array(
			'store_id' => $store_id,
			'merchant_order_id' => $merchant_ref,
			'recipient_name' => $recipient_name,
			'recipient_phone' => $recipient_phone,
			'recipient_address' => $addr_line,
			'delivery_type' => (int)(isset($override['delivery_type']) ? $override['delivery_type'] : 48),
			'item_type' => $item_type,
			'item_quantity' => $item_qty,
			'item_weight' => (float)$item_weight,
			'item_description' => $this->pathaoCreateItemDescriptionString($item_text, 2000),
			'amount_to_collect' => (int)max(0, $amount_collect),
			'special_instruction' => $si,
		);

		$cid = (int)$loc['city'];
		$zid = (int)$loc['zone'];
		$aid = (int)$loc['area'];
		if ($cid > 0 && $zid > 0 && $aid > 0) {
			$payload['recipient_city'] = $cid;
			$payload['recipient_zone'] = $zid;
			$payload['recipient_area'] = $aid;
		}

		try {
			$res = $client->post('/orders', $payload);
		} catch (\Throwable $e) {
			$client->clearLogContext();
			return array('success' => false, 'message' => 'Pathao API call failed: ' . $e->getMessage());
		}
		$client->clearLogContext();

		if (empty($res['success'])) {
			$msg = isset($res['message']) ? (string)$res['message'] : 'Failed';
			$errs = (!empty($res['errors']) && is_array($res['errors'])) ? $res['errors'] : array();

			// Idempotency: if the previous attempt timed out after Pathao had already created
			// the consignment, retrying produces a "merchant_order_id already taken" 422.
			// Treat that as success so the user is not stuck — save a local marker and ack.
			$flat = '';
			foreach ($errs as $k => $v) {
				$flat .= ' ' . (string)$k . ':';
				if (is_array($v)) {
					foreach ($v as $vv) { $flat .= ' ' . (string)$vv; }
				} else {
					$flat .= ' ' . (string)$v;
				}
			}
			$flatLow = strtolower($flat . ' ' . $msg);
			$alreadyExists = (strpos($flatLow, 'merchant_order_id') !== false || strpos($flatLow, 'merchant order id') !== false)
				&& (strpos($flatLow, 'already') !== false || strpos($flatLow, 'taken') !== false || strpos($flatLow, 'exist') !== false);

			if ($alreadyExists) {
				$this->pathaoCreateEnsureOrderTable();
				$existing = $this->db->query("SELECT consignment_id FROM `" . DB_PREFIX . "pathao_order` WHERE order_id = '" . (int)$order_id . "' ORDER BY id DESC LIMIT 1");
				$haveLocal = ($existing->num_rows && !empty($existing->row['consignment_id']));
				if (!$haveLocal) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "pathao_order`
						SET order_id = '" . (int)$order_id . "',
							consignment_id = '',
							tracking_code = '',
							status = 'sent',
							delivery_fee = NULL,
							created_at = NOW(),
							updated_at = NOW()");
				}
				return array(
					'success' => true,
					'message' => 'Already sent to Pathao earlier (the previous attempt timed out before saving locally). Check Pathao panel for the consignment.',
					'consignment_id' => $haveLocal ? (string)$existing->row['consignment_id'] : '',
					'tracking_code' => '',
					'status' => 'sent',
					'delivery_fee' => null,
					'data' => $res,
					'idempotent' => true,
				);
			}

			if ($errs) {
				reset($errs);
				$firstKey = key($errs);
				if ($firstKey !== null) {
					$firstVal = $errs[$firstKey];
					if (is_array($firstVal) && isset($firstVal[0])) {
						$msg .= ' (' . $firstKey . ': ' . $firstVal[0] . ')';
					} elseif (is_string($firstVal)) {
						$msg .= ' (' . $firstKey . ': ' . $firstVal . ')';
					}
				}
			}
			return array('success' => false, 'message' => $msg, 'data' => $res);
		}

		$data = isset($res['data']) ? $res['data'] : array();
		$consignment_id = '';
		$tracking_code = '';
		$delivery_fee = null;
		$status = '';
		if (isset($data['data']) && is_array($data['data'])) { $data = $data['data']; }
		if (isset($data['consignment_id'])) { $consignment_id = (string)$data['consignment_id']; }
		if (isset($data['tracking_code']))  { $tracking_code  = (string)$data['tracking_code']; }
		if (isset($data['delivery_fee']))   { $delivery_fee   = (float)$data['delivery_fee']; }
		if (isset($data['order_status']))   { $status         = (string)$data['order_status']; }
		if (!$status && isset($data['status'])) { $status = (string)$data['status']; }

		$this->pathaoCreateEnsureOrderTable();

		try {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "pathao_order`
				SET order_id = '" . (int)$order_id . "',
					consignment_id = '" . $this->db->escape($consignment_id) . "',
					tracking_code = '" . $this->db->escape($tracking_code) . "',
					status = '" . $this->db->escape($status) . "',
					delivery_fee = " . ($delivery_fee === null ? "NULL" : "'" . (float)$delivery_fee . "'") . ",
					created_at = NOW(),
					updated_at = NOW()");
		} catch (\Throwable $e) {
			// Pathao already has the consignment — surface success even if local DB save fails.
		}

		return array(
			'success' => true,
			'message' => 'Order created',
			'consignment_id' => $consignment_id,
			'tracking_code' => $tracking_code,
			'status' => $status,
			'delivery_fee' => $delivery_fee,
			'data' => isset($res['data']) ? $res['data'] : array(),
		);
	}

	private function pathaoCreateEnsureOrderTable() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "pathao_order` (
			`id` INT AUTO_INCREMENT PRIMARY KEY,
			`order_id` INT NOT NULL,
			`consignment_id` VARCHAR(100),
			`tracking_code` VARCHAR(100),
			`status` VARCHAR(50),
			`delivery_fee` DECIMAL(10,2),
			`created_at` DATETIME,
			`updated_at` DATETIME,
			KEY `order_id` (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	}
}


