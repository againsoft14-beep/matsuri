<?php
class PathaoTokenManager {
	/** @var int Renew before this many seconds to expiry (safety buffer). */
	private $expiryBufferSeconds = 300;

	/** @var int Minimum seconds added to "now" when we treat token as still valid. */
	private $minSkewSeconds = 60;

	private $db;
	private $config;
	private $logger;

	public function __construct($db, $config, $logger) {
		$this->db = $db;
		$this->config = $config;
		$this->logger = $logger;
	}

	public function ensureTable() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "pathao_token` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `is_sandbox` tinyint(1) NOT NULL DEFAULT 0,
		  `access_token` text,
		  `refresh_token` text,
		  `expires_at` datetime DEFAULT NULL,
		  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `env_unique` (`is_sandbox`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	}

	public function getEnvironment() {
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

	public function isSandbox() {
		return $this->getEnvironment() === 'sandbox';
	}

	private function tokenRow() {
		$this->ensureTable();
		$is_sandbox = $this->isSandbox() ? 1 : 0;
		$q = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pathao_token` WHERE is_sandbox = '" . (int)$is_sandbox . "' LIMIT 1");
		return $q->num_rows ? $q->row : array();
	}

	/**
	 * Access token is still usable if it expires after (now + buffer + skew).
	 */
	private function accessTokenIsFresh($expires_at) {
		if (!$expires_at) {
			return false;
		}
		$ts = strtotime($expires_at);
		if (!$ts) {
			return false;
		}
		$cutoff = time() + (int)$this->expiryBufferSeconds + (int)$this->minSkewSeconds;
		return $ts > $cutoff;
	}

	private function creds() {
		$env = $this->getEnvironment();
		$suffix = ($env === 'sandbox') ? '_sandbox' : '_live';

		$client_id = (string)$this->config->get('shipping_pathao_client_id' . $suffix);
		if ($client_id === '') {
			$client_id = (string)$this->config->get('shipping_pathao_client_id');
		}

		$client_secret = (string)$this->config->get('shipping_pathao_client_secret' . $suffix);
		if ($client_secret === '') {
			$client_secret = (string)$this->config->get('shipping_pathao_client_secret');
		}

		$username = (string)$this->config->get('shipping_pathao_merchant_username' . $suffix);
		if ($username === '') {
			$username = (string)$this->config->get('shipping_pathao_merchant_username');
		}

		$password = (string)$this->config->get('shipping_pathao_merchant_password' . $suffix);
		if ($password === '') {
			$password = (string)$this->config->get('shipping_pathao_merchant_password');
		}

		return array($client_id, $client_secret, $username, $password);
	}

	private function saveToken($access, $refresh, $expires_at) {
		$this->ensureTable();
		$is_sandbox = $this->isSandbox() ? 1 : 0;
		$this->db->query("INSERT INTO `" . DB_PREFIX . "pathao_token`
			SET is_sandbox = '" . (int)$is_sandbox . "',
				access_token = '" . $this->db->escape((string)$access) . "',
				refresh_token = '" . $this->db->escape((string)$refresh) . "',
				expires_at = " . ($expires_at ? ("'" . $this->db->escape($expires_at) . "'") : "NULL") . "
			ON DUPLICATE KEY UPDATE
				access_token = VALUES(access_token),
				refresh_token = VALUES(refresh_token),
				expires_at = VALUES(expires_at)");
	}

	private function parseTokenPayload($data) {
		$root = is_array($data) ? $data : array();
		$node = $root;
		if (isset($node['data']) && is_array($node['data'])) {
			$node = $node['data'];
		}
		if (isset($node['data']) && is_array($node['data'])) {
			$node = $node['data'];
		}
		$access = '';
		if (isset($node['access_token'])) {
			$access = (string)$node['access_token'];
		} elseif (isset($root['access_token'])) {
			$access = (string)$root['access_token'];
		}
		$refresh = '';
		if (isset($node['refresh_token'])) {
			$refresh = (string)$node['refresh_token'];
		} elseif (isset($root['refresh_token'])) {
			$refresh = (string)$root['refresh_token'];
		}
		$expires_in = 0;
		if (isset($node['expires_in'])) {
			$expires_in = (int)$node['expires_in'];
		} elseif (isset($root['expires_in'])) {
			$expires_in = (int)$root['expires_in'];
		}
		$expires_at = $expires_in ? date('Y-m-d H:i:s', time() + $expires_in) : null;
		return array('access' => $access, 'refresh' => $refresh, 'expires_at' => $expires_at);
	}

	private function postJson($url, $payload) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
		$raw = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return array('raw' => (string)$raw, 'errno' => $errno, 'error' => $error, 'code' => $code);
	}

	/**
	 * Clear cached access (forces getToken to refresh or login). Used on HTTP 401.
	 */
	public function invalidateAccessToken() {
		$this->ensureTable();
		$is_sandbox = $this->isSandbox() ? 1 : 0;
		$this->db->query("UPDATE `" . DB_PREFIX . "pathao_token` SET
			access_token = '',
			expires_at = NULL
			WHERE is_sandbox = '" . (int)$is_sandbox . "' LIMIT 1");
	}

	/**
	 * @return array{success:bool,message?:string,access_token?:string}
	 */
	public function login($baseUrl) {
		list($client_id, $client_secret, $username, $password) = $this->creds();
		if (!$client_id || !$client_secret || !$username || !$password) {
			return array('success' => false, 'message' => 'Missing credentials', 'access_token' => '');
		}

		$url = rtrim($baseUrl, '/') . '/external/login';
		$payload = array(
			'client_id' => $client_id,
			'client_secret' => $client_secret,
			'username' => $username,
			'password' => $password,
			'grant_type' => 'password',
		);

		$this->logger->write('token', 'login', array('endpoint' => 'POST ' . 'external/login'));

		$res = $this->postJson($url, $payload);
		$this->logger->write('token', 'login response', array('endpoint' => 'POST ' . 'external/login', 'http_code' => $res['code']));

		if (!empty($res['errno'])) {
			return array('success' => false, 'message' => $res['error'], 'access_token' => '');
		}
		$code = (int)$res['code'];
		$raw = $res['raw'];
		if ($code < 200 || $code >= 300) {
			return array('success' => false, 'message' => 'Token failed (HTTP ' . $code . ')', 'access_token' => '');
		}

		$data = json_decode($raw, true);
		$p = $this->parseTokenPayload($data);
		if ($p['access'] === '') {
			return array('success' => false, 'message' => 'Access token missing', 'access_token' => '');
		}
		$this->saveToken($p['access'], $p['refresh'], $p['expires_at']);
		return array('success' => true, 'message' => '', 'access_token' => $p['access']);
	}

	/**
	 * OAuth2 refresh: grant_type=refresh_token
	 * @return array{success:bool,message?:string,access_token?:string}
	 */
	public function doRefresh($baseUrl, $refreshToken) {
		$refreshToken = trim((string)$refreshToken);
		if ($refreshToken === '') {
			return array('success' => false, 'message' => 'No refresh token', 'access_token' => '');
		}
		list($client_id, $client_secret) = $this->creds();
		$client_id = (string)$client_id;
		$client_secret = (string)$client_secret;
		if ($client_id === '' || $client_secret === '') {
			return array('success' => false, 'message' => 'Missing client credentials', 'access_token' => '');
		}
		$url = rtrim($baseUrl, '/') . '/external/login';
		$payload = array(
			'grant_type' => 'refresh_token',
			'client_id' => $client_id,
			'client_secret' => $client_secret,
			'refresh_token' => $refreshToken,
		);
		$this->logger->write('token', 'refresh', array('endpoint' => 'POST ' . 'external/login (grant_type=refresh_token)'));

		$res = $this->postJson($url, $payload);
		$this->logger->write('token', 'refresh response', array('endpoint' => 'POST ' . 'external/login', 'http_code' => $res['code']));

		if (!empty($res['errno'])) {
			return array('success' => false, 'message' => $res['error'], 'access_token' => '');
		}
		$code = (int)$res['code'];
		$raw = $res['raw'];
		if ($code < 200 || $code >= 300) {
			$parsed = json_decode($raw, true);
			$msg = 'Refresh failed (HTTP ' . $code . ')';
			if (is_array($parsed) && !empty($parsed['message'])) {
				$msg = (string)$parsed['message'];
			}
			return array('success' => false, 'message' => $msg, 'access_token' => '');
		}

		$data = json_decode($raw, true);
		$p = $this->parseTokenPayload($data);
		if ($p['access'] === '') {
			return array('success' => false, 'message' => 'Access token missing after refresh', 'access_token' => '');
		}
		$newRefresh = $p['refresh'] !== '' ? $p['refresh'] : $refreshToken;
		$this->saveToken($p['access'], $newRefresh, $p['expires_at']);
		return array('success' => true, 'message' => '', 'access_token' => $p['access']);
	}

	/**
	 * @return array{success:bool,message?:string,access_token?:string}
	 */
	public function getToken($baseUrl) {
		$row = $this->tokenRow();
		if ($row && !empty($row['access_token']) && $this->accessTokenIsFresh(isset($row['expires_at']) ? $row['expires_at'] : '')) {
			return array('success' => true, 'access_token' => (string)$row['access_token']);
		}

		if (!empty($row['refresh_token'])) {
			$r = $this->doRefresh($baseUrl, (string)$row['refresh_token']);
			if (!empty($r['success']) && !empty($r['access_token'])) {
				return $r;
			}
			$this->logger->write('token', 'refresh failed, re-login', array('message' => isset($r['message']) ? (string)$r['message'] : 'unknown'));
		}
		return $this->login($baseUrl);
	}
}
