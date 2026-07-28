<?php
class ModelExtensionShippingPathao extends Model {
	private function libs() {
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
			if ($env === 'sandbox' || $env === 'staging') return 'sandbox';
			if ($env === 'live' || $env === 'production') return 'live';
		}
		return ((int)$this->config->get('shipping_pathao_sandbox')) ? 'sandbox' : 'live';
	}

	/**
	 * Pathao Courier in this extension is an admin-side fulfillment tool only
	 * (consignment creation, tracking, webhooks) — NOT a customer-facing shipping
	 * method. Returning an empty array here keeps the storefront checkout free of
	 * a "Pathao Courier - 0৳" option, while leaving all admin functionality intact.
	 *
	 * Storefront shipping rates should come from your usual shipping module
	 * (e.g. Inside / Outside Dhaka flat rates).
	 */
	public function getQuote($address) {
		return array();
	}

	/**
	 * special_instruction: admin override (POST/modal) if non-empty, else order customer comment.
	 */
	private function pathaoSpecialInstruction($order, $override) {
		if (isset($override['special_instruction']) && trim((string)$override['special_instruction']) !== '') {
			return (string)$override['special_instruction'];
		}
		$c = '';
		if (isset($order['comment'])) {
			$c = trim((string)$order['comment']);
		}
		return $c;
	}

	/**
	 * Build a single line address for Pathao + matching (include OC city/zone when present).
	 */
	private function pathaoRecipientAddressLine($order) {
		$line = trim((string)($order['shipping_address_1'] ?? '') . ' ' . (string)($order['shipping_address_2'] ?? ''));
		if (!empty($order['shipping_city'])) {
			$line = trim($line . ', ' . (string)$order['shipping_city']);
		}
		if (!empty($order['shipping_zone'])) {
			$line = trim($line . ', ' . (string)$order['shipping_zone']);
		}
		if (!empty($order['shipping_postcode'])) {
			$line = trim($line . ' ' . (string)$order['shipping_postcode']);
		}
		return $line;
	}

	/**
	 * Address line for API + area matching: modal override, else from order.
	 */
	private function pathaoRecipientLineFromOverride($order, $override) {
		if (isset($override['recipient_address']) && trim((string)$override['recipient_address']) !== '') {
			return trim((string)$override['recipient_address']);
		}
		return $this->pathaoRecipientAddressLine($order);
	}

	/**
	 * @param array $products getOrderProducts rows
	 * @return float
	 */
	private function pathaoComputeOrderWeight($products) {
		$sum = 0.0;
		foreach ((array)$products as $p) {
			$q = (int)($p['quantity'] ?? 0);
			if ($q < 1) {
				continue;
			}
			$pid = (int)($p['product_id'] ?? 0);
			$w = 0.5;
			if ($pid > 0) {
				$r = $this->db->query("SELECT `weight` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . $pid . "' LIMIT 1");
				if ($r->num_rows && (float)$r->row['weight'] > 0) {
					$w = (float)$r->row['weight'];
				}
			}
			$sum += $w * $q;
		}
		if ($sum <= 0) {
			$sum = 0.5;
		}
		return (float)$sum;
	}

	/**
	 * Pathao API: item_weight must be between 0.001 and 200 (kg).
	 */
	private function pathaoClampItemWeight($w) {
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

	private function pathaoItemDescriptionString($s, $max) {
		$s = (string)$s;
		$max = (int)$max;
		if ($max < 10) {
			$max = 500;
		}
		if (function_exists('mb_substr') && (function_exists('mb_strlen') && mb_strlen($s, 'UTF-8') > $max)) {
			return (string)mb_substr($s, 0, $max, 'UTF-8');
		}
		if (strlen($s) > $max) {
			return (string)substr($s, 0, $max);
		}
		return $s;
	}

	private function pathaoParseListBody($parsed) {
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

	/**
	 * Resolve store: override → config default → is_default in pathao_store → first row.
	 */
	private function pathaoGetStoreId($override) {
		$store_id = isset($override['store_id']) ? (int)$override['store_id'] : 0;
		if ($store_id < 1) {
			$store_id = (int)$this->config->get('shipping_pathao_default_store_id');
		}
		if ($store_id < 1) {
			$q = $this->db->query("SELECT store_id FROM `" . DB_PREFIX . "pathao_store` WHERE is_default = 1 LIMIT 1");
			if ($q->num_rows) {
				$store_id = (int)$q->row['store_id'];
			}
		}
		if ($store_id < 1) {
			$q2 = $this->db->query("SELECT store_id FROM `" . DB_PREFIX . "pathao_store` ORDER BY store_id ASC LIMIT 1");
			if ($q2->num_rows) {
				$store_id = (int)$q2->row['store_id'];
			}
		}
		return $store_id;
	}

	/**
	 * Best-effort: match shipping address / zone text to Pathao city → zone → area IDs.
	 * If all override IDs are empty, skip API calls (Pathao /orders may use address only).
	 * Fills only missing parts; does not override admin-selected IDs in $override.
	 */
	private function pathaoResolveRecipientIds($order, $override, $client, $logger, $order_id, $address_for_matching = null) {
		$order_id = (int)$order_id;
		$city = isset($override['recipient_city']) ? (int)$override['recipient_city'] : 0;
		$zone = isset($override['recipient_zone']) ? (int)$override['recipient_zone'] : 0;
		$area = isset($override['recipient_area']) ? (int)$override['recipient_area'] : 0;
		if ($city > 0 && $zone > 0 && $area > 0) {
			return array('city' => $city, 'zone' => $zone, 'area' => $area, 'source' => 'form');
		}
		if ($city <= 0 && $zone <= 0 && $area <= 0) {
			$logger->write('order', 'location: address-only (no city/zone/area in form)', array('order_id' => $order_id));
			return array('city' => 0, 'zone' => 0, 'area' => 0, 'source' => 'address_only');
		}

		$line_for_match = ($address_for_matching !== null && (string)$address_for_matching !== '')
			? (string)$address_for_matching
			: $this->pathaoRecipientAddressLine($order);
		$haystack = mb_strtolower($line_for_match, 'UTF-8');
		$zoneText = !empty($order['shipping_zone']) ? mb_strtolower(trim((string)$order['shipping_zone']), 'UTF-8') : '';

		$rc = $client->get('countries/1/city-list');
		if (empty($rc['success'])) {
			$logger->write('order', 'city-list skip', array('order_id' => $order_id, 'message' => isset($rc['message']) ? $rc['message'] : 'fail'));
			return array('city' => $city, 'zone' => $zone, 'area' => $area, 'source' => 'partial');
		}
		$cities = $this->pathaoParseListBody(isset($rc['data']) ? $rc['data'] : array());

		if ($city <= 0 && $cities) {
			// Exact match: OpenCart zone name = Pathao city name
			if ($zoneText !== '') {
				foreach ($cities as $c) {
					if (!is_array($c)) {
						continue;
					}
					$cid = 0;
					$cname = '';
					if (isset($c['city_id'])) {
						$cid = (int)$c['city_id'];
					} elseif (isset($c['id'])) {
						$cid = (int)$c['id'];
					}
					if (isset($c['city_name'])) {
						$cname = (string)$c['city_name'];
					} elseif (isset($c['name'])) {
						$cname = (string)$c['name'];
					}
					if ($cid && $cname !== '' && $zoneText === mb_strtolower($cname, 'UTF-8')) {
						$city = $cid;
						break;
					}
				}
			}
			// Fuzzy: longest city name contained in full address/zone string
			if ($city <= 0 && $haystack !== '') {
				$best = 0;
				$bestlen = 0;
				foreach ($cities as $c) {
					if (!is_array($c)) {
						continue;
					}
					$cid = isset($c['city_id']) ? (int)$c['city_id'] : (isset($c['id']) ? (int)$c['id'] : 0);
					$cname = isset($c['city_name']) ? (string)$c['city_name'] : (isset($c['name']) ? (string)$c['name'] : '');
					$low = $cname !== '' ? mb_strtolower($cname, 'UTF-8') : '';
					if ($cid && $low !== '' && (mb_strpos($haystack, $low) !== false)) {
						$len = mb_strlen($cname, 'UTF-8');
						if ($len > $bestlen) {
							$bestlen = $len;
							$best = $cid;
						}
					}
				}
				if ($best > 0) {
					$city = $best;
				}
			}
		}

		if ($city <= 0) {
			return array('city' => 0, 'zone' => 0, 'area' => 0, 'source' => 'guessed', 'guessed' => false);
		}

		$rz = $client->get('cities/' . (int)$city . '/zone-list');
		if (empty($rz['success'])) {
			return array('city' => $city, 'zone' => $zone, 'area' => $area, 'source' => 'partial');
		}
		$zones = $this->pathaoParseListBody(isset($rz['data']) ? $rz['data'] : array());

		if ($zone <= 0 && $zones) {
			if ($zoneText !== '') {
				foreach ($zones as $z) {
					if (!is_array($z)) {
						continue;
					}
					$zid = isset($z['zone_id']) ? (int)$z['zone_id'] : (isset($z['id']) ? (int)$z['id'] : 0);
					$zname = isset($z['zone_name']) ? (string)$z['zone_name'] : (isset($z['name']) ? (string)$z['name'] : '');
					if ($zid && $zname !== '' && $zoneText === mb_strtolower(trim($zname), 'UTF-8')) {
						$zone = $zid;
						break;
					}
				}
			}
			if ($zone <= 0 && $haystack !== '') {
				$best = 0;
				$bestlen = 0;
				foreach ($zones as $z) {
					if (!is_array($z)) {
						continue;
					}
					$zid = isset($z['zone_id']) ? (int)$z['zone_id'] : (isset($z['id']) ? (int)$z['id'] : 0);
					$zname = isset($z['zone_name']) ? (string)$z['zone_name'] : (isset($z['name']) ? (string)$z['name'] : '');
					$low = $zname !== '' ? mb_strtolower($zname, 'UTF-8') : '';
					if ($zid && $low !== '' && (mb_strpos($haystack, $low) !== false)) {
						$len = mb_strlen($zname, 'UTF-8');
						if ($len > $bestlen) {
							$bestlen = $len;
							$best = $zid;
						}
					}
				}
				if ($best > 0) {
					$zone = $best;
				}
			}
		}

		if ($zone <= 0) {
			$logger->write('order', 'location guess', array('order_id' => $order_id, 'city' => $city, 'zone' => 0, 'area' => 0, 'note' => 'no zone match'));
			return array('city' => $city, 'zone' => 0, 'area' => 0, 'source' => 'guessed', 'guessed' => ($city > 0));
		}

		$ra = $client->get('zones/' . (int)$zone . '/area-list');
		if (empty($ra['success'])) {
			return array('city' => $city, 'zone' => $zone, 'area' => $area, 'source' => 'partial');
		}
		$areas = $this->pathaoParseListBody(isset($ra['data']) ? $ra['data'] : array());

		if ($area <= 0 && $areas && $haystack !== '') {
			$best = 0;
			$bestlen = 0;
			foreach ($areas as $a) {
				if (!is_array($a)) {
					continue;
				}
				$aid = isset($a['area_id']) ? (int)$a['area_id'] : (isset($a['id']) ? (int)$a['id'] : 0);
				$aname = isset($a['area_name']) ? (string)$a['area_name'] : (isset($a['name']) ? (string)$a['name'] : '');
				$low = $aname !== '' ? mb_strtolower($aname, 'UTF-8') : '';
				if ($aid && $low !== '' && (mb_strpos($haystack, $low) !== false)) {
					$len = mb_strlen($aname, 'UTF-8');
					if ($len > $bestlen) {
						$bestlen = $len;
						$best = $aid;
					}
				}
			}
			if ($best > 0) {
				$area = $best;
			}
		}

		$logger->write('order', 'location guess', array('order_id' => $order_id, 'city' => $city, 'zone' => $zone, 'area' => $area));
		return array('city' => $city, 'zone' => $zone, 'area' => $area, 'source' => 'guessed', 'guessed' => true);
	}

	public function createOrder($order_id, $override = array()) {
		list($logger, $token, $client) = $this->libs();

		$order_id = (int)$order_id;
		if (!$order_id) {
			return array('success' => false, 'message' => 'Order ID is required');
		}

		$this->load->model('catalog/product');

		if (is_file(DIR_APPLICATION . 'model/sale/order.php')) {
			$this->load->model('sale/order');
			$order = $this->model_sale_order->getOrder($order_id);
			if (!$order) {
				return array('success' => false, 'message' => 'Order not found');
			}
			$products = $this->model_sale_order->getOrderProducts($order_id);
		} else {
			$this->load->model('checkout/order');
			$order = $this->model_checkout_order->getOrder($order_id);
			if (!$order) {
				return array('success' => false, 'message' => 'Order not found');
			}
			$products = $this->model_checkout_order->getOrderProducts($order_id);
		}

		$desc = array();
		$qty = 0;
		foreach ($products as $p) {
			$qty += (int)$p['quantity'];
			$desc[] = (string)($p['name'] ?? '') . ' x' . (int)$p['quantity'];
		}
		$item_desc_default = implode(', ', array_slice($desc, 0, 20));

		$store_id = $this->pathaoGetStoreId($override);
		if ($store_id < 1) {
			return array('success' => false, 'message' => 'No Pathao store. Sync stores in the Pathao admin settings and pick a default store.');
		}

		$addr_line = $this->pathaoRecipientLineFromOverride($order, $override);
		$loc = $this->pathaoResolveRecipientIds($order, $override, $client, $logger, $order_id, $addr_line);
		$client->setLogContext(array('order_id' => $order_id));

		$recipient_name = trim((string)($order['shipping_firstname'] ?? '') . ' ' . (string)($order['shipping_lastname'] ?? '')) ?: trim((string)($order['firstname'] ?? '') . ' ' . (string)($order['lastname'] ?? ''));
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
		$item_weight = $this->pathaoComputeOrderWeight($products);
		if (isset($override['item_weight']) && is_numeric($override['item_weight'])) {
			$w = (float)$override['item_weight'];
			if ($w > 0) {
				$item_weight = $w;
			}
		}
		$item_weight = $this->pathaoClampItemWeight($item_weight);
		$item_text = $item_desc_default;
		if (isset($override['item_description']) && trim((string)$override['item_description']) !== '') {
			$item_text = trim((string)$override['item_description']);
		}
		$amount_collect = (int)round((float)($order['total'] ?? 0));
		if (isset($override['amount_to_collect']) && (string)$override['amount_to_collect'] !== '' && (int)$override['amount_to_collect'] >= 0) {
			$amount_collect = (int)$override['amount_to_collect'];
		}

		$si = $this->pathaoSpecialInstruction($order, $override);
		if (isset($override['recipient_secondary_phone']) && trim((string)$override['recipient_secondary_phone']) !== '') {
			$sp = trim((string)$override['recipient_secondary_phone']);
			$si = trim("Alt phone: " . $sp . ($si !== '' ? "\n" : '') . $si);
		}

		$merchant_ref = (string)$order_id;
		if (isset($override['merchant_order_id']) && trim((string)$override['merchant_order_id']) !== '') {
			$merchant_ref = trim((string)$override['merchant_order_id']);
		}

		$item_type = (int)(isset($override['item_type']) ? $override['item_type'] : 2);
		if ($item_type === 3) {
			$item_type = 2;
			$item_text = '[Fragile] ' . $item_text;
		}
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
			'item_description' => $this->pathaoItemDescriptionString($item_text, 2000),
			'amount_to_collect' => (int)max(0, $amount_collect),
			'special_instruction' => $si
		);

		$cid = (int)$loc['city'];
		$zid = (int)$loc['zone'];
		$aid = (int)$loc['area'];
		$locationComplete = ($cid > 0 && $zid > 0 && $aid > 0);
		if ($locationComplete) {
			$payload['recipient_city'] = $cid;
			$payload['recipient_zone'] = $zid;
			$payload['recipient_area'] = $aid;
		} else {
			if (isset($loc['source']) && $loc['source'] !== 'address_only' && ($cid > 0 || $zid > 0 || $aid > 0)) {
				$logger->write('order', 'location: incomplete, omitting recipient_city/zone/area (use address for Pathao)', array(
					'order_id' => $order_id,
					'city' => $cid,
					'zone' => $zid,
					'area' => $aid,
					'source' => isset($loc['source']) ? (string)$loc['source'] : '',
				));
			}
		}

		$res = $client->post('/orders', $payload);
		$client->clearLogContext();
		if (empty($res['success'])) {
			$msg = $res['message'];
			if (!empty($res['errors']) && is_array($res['errors'])) {
				reset($res['errors']);
				$firstKey = key($res['errors']);
				if ($firstKey !== null) {
					$firstVal = $res['errors'][$firstKey];
					if (is_array($firstVal) && isset($firstVal[0])) {
						$msg .= ' (' . $firstKey . ': ' . $firstVal[0] . ')';
					}
				}
			}
			return array('success' => false, 'message' => $msg, 'data' => $res);
		}

		$data = $res['data'];
		$consignment_id = '';
		$tracking_code = '';
		$delivery_fee = null;
		$status = '';

		if (isset($data['data']) && is_array($data['data'])) $data = $data['data'];

		if (isset($data['consignment_id'])) $consignment_id = (string)$data['consignment_id'];
		if (isset($data['tracking_code'])) $tracking_code = (string)$data['tracking_code'];
		if (isset($data['delivery_fee'])) $delivery_fee = (float)$data['delivery_fee'];
		if (isset($data['order_status'])) $status = (string)$data['order_status'];
		if (!$status && isset($data['status'])) $status = (string)$data['status'];

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

		$this->db->query("INSERT INTO `" . DB_PREFIX . "pathao_order`
			SET order_id = '" . (int)$order_id . "',
				consignment_id = '" . $this->db->escape($consignment_id) . "',
				tracking_code = '" . $this->db->escape($tracking_code) . "',
				status = '" . $this->db->escape($status) . "',
				delivery_fee = " . ($delivery_fee === null ? "NULL" : "'" . (float)$delivery_fee . "'") . ",
				created_at = NOW(),
				updated_at = NOW()
			ON DUPLICATE KEY UPDATE
				consignment_id = VALUES(consignment_id),
				tracking_code = VALUES(tracking_code),
				status = VALUES(status),
				delivery_fee = VALUES(delivery_fee),
				updated_at = NOW()");

		return array(
			'success' => true,
			'message' => 'Order created',
			'consignment_id' => $consignment_id,
			'tracking_code' => $tracking_code,
			'status' => $status,
			'delivery_fee' => $delivery_fee,
			'data' => $res['data']
		);
	}

	public function cronUpdateStatuses($limit = 25) {
		list($logger, $token, $client) = $this->libs();

		$limit = (int)$limit;
		if ($limit < 1) $limit = 1;
		if ($limit > 200) $limit = 200;

		// Ensure table exists
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

		$q = $this->db->query("SELECT `order_id`, `consignment_id` FROM `" . DB_PREFIX . "pathao_order`
			WHERE `consignment_id` IS NOT NULL AND `consignment_id` != ''
			ORDER BY `updated_at` ASC
			LIMIT " . (int)$limit);

		$updated = 0;
		$errors = 0;
		$rows = $q->rows;

		foreach ($rows as $row) {
			$consignment_id = (string)$row['consignment_id'];
			$oid = (int)($row['order_id'] ?? 0);
			$client->setLogContext(array('order_id' => $oid));
			$r = $client->get('/orders/' . rawurlencode($consignment_id) . '/info');
			$client->clearLogContext();
			if (empty($r['success'])) {
				$errors++;
				$logger->write('cron', 'status fetch failed', array(
					'order_id' => $oid,
					'endpoint' => 'GET orders/' . $consignment_id . '/info',
					'message' => isset($r['message']) ? $r['message'] : 'fail',
				));
				continue;
			}

			$data = $r['data'];
			if (isset($data['data']) && is_array($data['data'])) $data = $data['data'];

			$status = '';
			$tracking_code = '';
			$delivery_fee = null;

			if (isset($data['order_status'])) $status = (string)$data['order_status'];
			if (!$status && isset($data['status'])) $status = (string)$data['status'];
			if (isset($data['tracking_code'])) $tracking_code = (string)$data['tracking_code'];
			if (isset($data['delivery_fee'])) $delivery_fee = (float)$data['delivery_fee'];

			$this->db->query("UPDATE `" . DB_PREFIX . "pathao_order`
				SET status = '" . $this->db->escape($status) . "',
					tracking_code = IF(tracking_code='', '" . $this->db->escape($tracking_code) . "', tracking_code),
					delivery_fee = " . ($delivery_fee === null ? "delivery_fee" : "'" . (float)$delivery_fee . "'") . ",
					updated_at = NOW()
				WHERE consignment_id = '" . $this->db->escape($consignment_id) . "'");

			$updated++;
		}

		return array(
			'success' => true,
			'message' => 'Cron done. Updated: ' . $updated . ', Errors: ' . $errors,
			'checked' => count($rows),
			'updated' => $updated,
			'errors' => $errors
		);
	}
}

