<?php
class PathaoClient {
	private $tokenManager;
	private $logger;
	private $env;
	/** @var array Context merged into every log (e.g. order_id) */
	private $logContext = array();

	public function __construct($tokenManager, $logger, $env) {
		$this->tokenManager = $tokenManager;
		$this->logger = $logger;
		$this->env = (string)$env;
	}

	public function setLogContext(array $context) {
		$this->logContext = $context;
	}

	public function clearLogContext() {
		$this->logContext = array();
	}

	public function baseUrl() {
		if ($this->env === 'sandbox') {
			return 'https://courier-api-sandbox.pathao.com/aladdin/api/v1';
		}
		return 'https://api-hermes.pathao.com/aladdin/api/v1';
	}

	public function get($path, $query = array()) {
		return $this->request('GET', $path, $query, null, true);
	}

	public function post($path, $data = array()) {
		return $this->request('POST', $path, array(), $data, true);
	}

	private function logEndpoint($method, $path) {
		$p = ltrim((string)$path, '/');
		return (string)$method . ' ' . $p;
	}

	private function mergeContext($extra) {
		return array_merge($this->logContext, $extra);
	}

	private function request($method, $path, $query = array(), $data = null, $retry = true) {
		$baseUrl = $this->baseUrl();
		$p = ltrim((string)$path, '/');
		$url = rtrim($baseUrl, '/') . '/' . $p;
		$ep = $this->logEndpoint($method, $p);
		if ($query) {
			$qs = http_build_query($query);
			$url .= (strpos($url, '?') === false ? '?' : '&') . $qs;
			$ep .= '?' . $qs;
		}

		$tokenRes = $this->tokenManager->getToken($baseUrl);
		if (empty($tokenRes['success'])) {
			$this->logger->write('api', 'no token', $this->mergeContext(array('endpoint' => $ep, 'message' => isset($tokenRes['message']) ? $tokenRes['message'] : 'Token error')));
			return array('success' => false, 'code' => 0, 'message' => isset($tokenRes['message']) ? $tokenRes['message'] : 'Token error', 'data' => array());
		}

		$access = (string)$tokenRes['access_token'];
		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'Authorization: Bearer ' . $access,
		);

		$logData = $data;
		$this->logger->write('api', 'request', $this->mergeContext(array('endpoint' => $ep, 'data' => $logData)));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		// Keep curl below typical PHP max_execution_time (30s) so the script can still
		// emit a JSON response if Pathao is slow. The admin order endpoints raise
		// max_execution_time to 120s as an extra safety margin.
		curl_setopt($ch, CURLOPT_TIMEOUT, 25);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data !== null ? $data : array()));
		}

		$raw = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$this->logger->write('api', 'response', $this->mergeContext(array('endpoint' => $ep, 'http_code' => $code, 'errno' => $errno, 'raw' => substr((string)$raw, 0, 3000))));

		if ($errno) {
			return array('success' => false, 'code' => 0, 'message' => $error, 'data' => array());
		}

		$parsed = json_decode($raw, true);
		if (!is_array($parsed)) {
			$parsed = array();
		}

		if ($code === 401 && $retry) {
			$this->logger->write('api', '401 retry: invalidate + refresh or login', $this->mergeContext(array('endpoint' => $ep)));
			$this->tokenManager->invalidateAccessToken();
			$second = $this->tokenManager->getToken($baseUrl);
			if (empty($second['success'])) {
				return array('success' => false, 'code' => 401, 'message' => isset($second['message']) ? $second['message'] : 'Re-auth failed', 'data' => $parsed);
			}
			return $this->request($method, $path, $query, $data, false);
		}

		if ($code < 200 || $code >= 300) {
			$msg = '';
			if (isset($parsed['message'])) {
				$msg = (string)$parsed['message'];
			}
			if ($msg === '' && isset($parsed['error'])) {
				$msg = (string)$parsed['error'];
			}
			if ($msg === '') {
				$msg = 'API error (HTTP ' . $code . ')';
			}

			$errors = array();
			if (isset($parsed['errors']) && is_array($parsed['errors'])) {
				$errors = $parsed['errors'];
			}
			if (!$errors && isset($parsed['data']['errors']) && is_array($parsed['data']['errors'])) {
				$errors = $parsed['data']['errors'];
			}
			return array('success' => false, 'code' => $code, 'message' => $msg, 'errors' => $errors, 'data' => $parsed);
		}
		return array('success' => true, 'code' => $code, 'message' => '', 'data' => $parsed);
	}
}
