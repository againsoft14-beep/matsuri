<?php
class PathaoLogger {
	private $enabled;
	private $env;
	private $log;

	public function __construct($enabled, $env) {
		$this->enabled = (bool)$enabled;
		$this->env = (string)$env;
		$this->log = new Log('pathao.log');
	}

	/**
	 * @param string $type e.g. api, token, order, webhook, cron
	 * @param string $message
	 * @param array  $context optional keys: order_id, endpoint, plus any JSON detail
	 */
	public function write($type, $message, $context = array()) {
		if (!$this->enabled) {
			return;
		}

		$line = '[' . date('Y-m-d H:i:s') . ']' .
			'[' . strtoupper($this->env) . ']';

		if (isset($context['order_id'])) {
			$line .= ' order_id=' . (int)$context['order_id'];
		}
		if (!empty($context['endpoint'])) {
			$line .= ' ' . (string)$context['endpoint'];
		}
		$line .=
			' [' . strtoupper((string)$type) . '] ' .
			(string)$message;

		$rest = is_array($context) ? $context : array();
		if (isset($rest['order_id'])) {
			unset($rest['order_id']);
		}
		if (isset($rest['endpoint'])) {
			unset($rest['endpoint']);
		}
		if ($rest) {
			$line .= ' ' . json_encode($rest);
		}

		$this->log->write($line);
	}
}
