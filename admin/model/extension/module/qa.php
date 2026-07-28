<?php
class ModelExtensionModuleQA extends Model {
	private static $alerts;
	private $tables = array(
		'qa' => array('qa_id', 'product_id', 'customer_id', 'language_id', 'question_author_name', 'question_author_email', 'question_author_phone', 'question_author_custom', 'answer_author_name', 'question', 'answer', 'status', 'notified', 'date_asked', 'date_answered', 'date_modified'),
		'qa_to_store' => array('qa_id', 'store_id')
	);

	protected static $filteredCount = 0;

	public function __construct($registry) {
		parent::__construct($registry);
	}

	public function upgradeDatabaseStructure($from_version, $alert = array()) {
		$errors = false;
		self::$alerts = array();

		switch ($from_version) {
			case '1.6.0':
				$this->db->query("ALTER TABLE `" . DB_PREFIX . "qa` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");

				$this->db->query("ALTER TABLE `" . DB_PREFIX . "qa`
					CHANGE customer_id customer_id INT(11),
					CHANGE q_author question_author_name VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
					CHANGE q_author_email question_author_email VARCHAR(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
					CHANGE a_author answer_author_name VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
					CHANGE status status TINYINT(1) NOT NULL DEFAULT '0',
					ADD COLUMN `language_id` INT(11) NOT NULL DEFAULT '" . (int)$this->config->get('config_language_id') . "' AFTER `customer_id`,
					ADD COLUMN `store_id` INT(11) NOT NULL DEFAULT '0' AFTER `language_id`,
					ADD COLUMN `question_author_phone` VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' AFTER `question_author_email`,
					ADD COLUMN `question_author_custom` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' AFTER `question_author_phone`,
					ADD COLUMN `notified` TINYINT(1) NOT NULL DEFAULT '0' AFTER `status`,
					ADD COLUMN `date_modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `date_answered`,
					ADD INDEX fk_qa_language (language_id)"
				);

				$this->db->query("UPDATE `" . DB_PREFIX . "qa` q LEFT JOIN `" . DB_PREFIX . "language` l ON (q.lang_code COLLATE utf8_unicode_ci = l.code COLLATE utf8_unicode_ci) SET q.language_id = IFNULL(l.language_id, '" . (int)$this->config->get('config_language_id') . "')");
				$this->db->query("UPDATE `" . DB_PREFIX . "qa` SET date_modified = date_answered");
				$this->db->query("UPDATE `" . DB_PREFIX . "qa` SET notified = '1' WHERE status = '1' AND answer != ''");

				$this->db->query("ALTER TABLE `" . DB_PREFIX . "qa` DROP COLUMN `lang_code`");

				$this->db->query("
					CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "qa_to_store (
						qa_id INT(11) NOT NULL,
						store_id INT(11) NOT NULL,
						PRIMARY KEY (qa_id, store_id),
						INDEX fk_qa2s_store (store_id)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
				);

				// Link all Q&As with all stores
				$query = $this->db->query("INSERT INTO " . DB_PREFIX . "qa_to_store (qa_id, store_id) SELECT qa_id, s.store_id FROM " . DB_PREFIX . "qa JOIN (SELECT store_id FROM " . DB_PREFIX . "store UNION SELECT 0 AS store_id) s");
			case '1.7.0':
			case '1.7.1':
			case '1.7.2':
			case '1.7.3':
			case '1.7.4':
			case '1.7.5':
			case '1.8.0':
			case '1.8.1':
			case '1.8.2':
			case '1.8.3':
			case '1.8.4':
			case '1.8.5':
			case '1.8.6':
			case '1.8.7':
			case '1.8.8':
			case '2.0.0':
			case '2.0.1':
			case '2.0.2':
				$this->db->query("ALTER TABLE `" . DB_PREFIX . "qa`
					MODIFY `date_asked` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					MODIFY `date_answered` DATETIME NULL DEFAULT NULL,
					MODIFY `date_modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
				);
				break;
			default:
				break;
		}

		return !$errors;
	}

	public function applyDatabaseChanges() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "qa (
				qa_id INT(11) NOT NULL AUTO_INCREMENT,
				product_id INT(11) NOT NULL,
				customer_id INT(11),
				language_id INT(11) NOT NULL DEFAULT '" . (int)$this->config->get('config_language_id') . "',
				store_id INT(11) NOT NULL DEFAULT '0',
				question_author_name VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
				question_author_email VARCHAR(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
				question_author_phone VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
				question_author_custom VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
				answer_author_name VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
				question TEXT COLLATE utf8_unicode_ci NOT NULL,
				answer TEXT COLLATE utf8_unicode_ci NOT NULL,
				status TINYINT(1) NOT NULL DEFAULT '0',
				notified TINYINT(1) NOT NULL DEFAULT '0',
				date_asked DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				date_answered DATETIME NULL DEFAULT NULL,
				date_modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (qa_id),
				INDEX fk_qa_product (product_id),
				INDEX fk_qa_language (language_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
		);

		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "qa_to_store (
				qa_id INT(11) NOT NULL,
				store_id INT(11) NOT NULL,
				PRIMARY KEY (qa_id, store_id),
				INDEX fk_qa2s_store (store_id)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
		);
	}

	public function revertDatabaseChanges() {
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "qa_to_store");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "qa");
	}

	public function checkDatabaseStructure($alert = array()) {
		$errors = false;
		self::$alerts = array();

		foreach ($this->tables as $tbl => $fields) {
			$table_exists = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "{$tbl}'");
			if (!$table_exists->num_rows) {
				$errors = true;
				self::$alerts['error']["missing_table_{$tbl}"] = sprintf($this->language->get('error_missing_table'), DB_PREFIX . $tbl);
			} else { // Check for table fields
				foreach($fields as $field) {
					$column_exists = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "{$tbl} LIKE '{$field}'");
					if (!$column_exists->num_rows) {
						$errors = true;
						self::$alerts['error']["missing_column_{$field}"] = sprintf($this->language->get('error_missing_column'), DB_PREFIX . $tbl, $field);
					}
				}
			}
		}

		return !$errors;
	}

	public function getAlerts() {
		return (array)self::$alerts;
	}

	// Q&A
	public function addQuestion($data) {
		$data['store_id'] = ($data['store_id'] == '' && isset($data['question_stores'][0])) ? $data['question_stores'][0] : 0;

		$this->db->query("INSERT INTO " . DB_PREFIX . "qa SET product_id = '" . (int)$data['product_id'] . "', customer_id = " . (($data['customer_id'] != '') ? "'" . (int)$data['customer_id'] . "'" : "NULL" ) . ", language_id = '" . (int)$data['language_id'] . "', store_id = '" . (int)$data['store_id'] . "', question_author_name = '" . $this->db->escape($data['question_author_name']) . "', question_author_email = '" . $this->db->escape($data['question_author_email']) . "', question_author_phone = '" . $this->db->escape($data['question_author_phone']) . "', question_author_custom = '" . $this->db->escape($data['question_author_custom']) . "', answer_author_name = '" . $this->db->escape($data['answer_author_name']) . "', question = '" . $this->db->escape($data['question']) . "', answer = '" . $this->db->escape($data['answer']) . "', status = '" . (int)$data['status'] . "', date_asked = NOW()" . (($this->db->escape($data['answer']) != "") ? ", date_answered = NOW()" : "") . ", date_modified = NOW()");

		$question_id = $this->db->getLastId();

		if (isset($data['question_stores'])) {
			foreach ((array)$data['question_stores'] as $store) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "qa_to_store SET qa_id = '" . (int)$question_id . "', store_id = '" . (int)$store . "'");
			}
		}

		$this->cache->delete('qa');

		return $question_id;
	}

	public function editQuestion($question_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "qa SET product_id = '" . (int)$data['product_id'] . "', customer_id = " . (($data['customer_id'] != '') ? "'" . (int)$data['customer_id'] . "'" : "NULL" ) . ", language_id = '" . (int)$data['language_id'] . "', question_author_name = '" . $this->db->escape($data['question_author_name']) . "', question_author_email = '" . $this->db->escape($data['question_author_email']) . "', question_author_phone = '" . $this->db->escape($data['question_author_phone']) . "', question_author_custom = '" . $this->db->escape($data['question_author_custom']) . "', answer_author_name = '" . $this->db->escape($data['answer_author_name']) . "', question = '" . $this->db->escape($data['question']) . "', answer = '" . $this->db->escape($data['answer']) . "', status = '" . (int)$data['status'] . "'" . (($this->db->escape($data['answer']) != "" && isset($data['update_date_answered']) && (int)$data['update_date_answered']) ? ", date_answered = NOW()" : "") . ", date_modified = NOW() WHERE qa_id = '" . (int)$question_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "qa_to_store WHERE qa_id = '" . (int)$question_id . "'");

		if (isset($data['question_stores'])) {
			foreach ((array)$data['question_stores'] as $store) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "qa_to_store SET qa_id = '" . (int)$question_id . "', store_id = '" . (int)$store . "'");
			}
		}

		$this->cache->delete('qa');
	}

	public function updateNotificationSent($question_id, $notified) {
		$this->db->query("UPDATE " . DB_PREFIX . "qa SET notified = '" . (int)$notified . "' WHERE qa_id = '" . (int)$question_id . "'");
	}

	public function copyQuestion($question_id) {
		$data = $this->getQuestion($question_id);

		$new_question_id = null;

		if (isset($data['qa_id'])) {
			$data['status'] = '0';

			$data = array_merge($data, array('question_stores' => $this->getQuestionStores($question_id)));

			$new_question_id = $this->addQuestion($data);

			$this->cache->delete('qa');
		}
		return $new_question_id;
	}

	public function deleteQuestion($qa_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "qa_to_store WHERE qa_id = '" . (int)$qa_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "qa WHERE qa_id = '" . (int)$qa_id . "'");

		$this->cache->delete('qa');
	}

	public function getQuestion($qa_id) {
		$query = $this->db->query("SELECT q.*, pd.name AS product, pd.product_id, CONCAT_WS(' ', c.firstname, c.lastname) AS customer_name FROM " . DB_PREFIX . "qa q LEFT JOIN " . DB_PREFIX . "product_description pd ON (q.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN " . DB_PREFIX . "customer c ON (q.customer_id = c.customer_id) WHERE q.qa_id = '" . (int)$qa_id . "'");

		return $query->row;
	}

	public function getQuestionStores($qa_id) {
		$stores = array();

		$query = $this->db->query("SELECT store_id FROM " . DB_PREFIX . "qa_to_store WHERE qa_id = '" . (int)$qa_id . "'");

		foreach ($query->rows as $store) {
			$stores[] = $store['store_id'];
		}

		return $stores;
	}

	public function getQuestions($data = array()) {
		$columns = isset($data['columns']) ? $data['columns'] : array();

		$sql = "SELECT SQL_CALC_FOUND_ROWS q.*, pd.name AS product, CONCAT_WS(' ', c.firstname, c.lastname) AS customer_name";

		if (in_array("status", $columns)) {
			$sql .= ", IF(q.status, '" . $this->db->escape($this->language->get('text_enabled')) . "','" .$this->db->escape($this->language->get('text_disabled')) . "') AS status_text";
		}

		if (in_array("language", $columns)) {
			$sql .= ", l.name AS language_name";
		}

		if (in_array("store", $columns)) {
			$sql .= ", GROUP_CONCAT(DISTINCT IF(q2s.store_id = 0, '" . $this->db->escape($this->config->get('config_name')) . "', s.name) SEPARATOR '<br/>') AS stores";
		}

		$sql .= " FROM " . DB_PREFIX . "qa q LEFT JOIN " . DB_PREFIX . "product_description pd ON (q.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN " . DB_PREFIX . "customer c ON (q.customer_id = c.customer_id)";

		if (in_array("language", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "language l ON (q.language_id = l.language_id)";
		}

		if (in_array("store", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "qa_to_store q2s ON (q.qa_id = q2s.qa_id) LEFT JOIN " . DB_PREFIX . "store s ON (s.store_id = q2s.store_id)";
			$sql .= " LEFT JOIN " . DB_PREFIX . "qa_to_store q2s2 ON (q.qa_id = q2s2.qa_id)";
		}

		$filters = array('AND' => array(), 'OR' => array());

		// Global search
		if (!empty($data['search']) && !in_array("store", $columns)) {
			$_filters = array(
				'id'                    => 'q.qa_id',
				'product'               => 'pd.name',
				'status'                => "IF(q.status, '" . $this->db->escape($this->language->get('text_enabled')) . "','" .$this->db->escape($this->language->get('text_disabled')) . "')",
				'date_asked'            => 'q.date_asked',
				'date_answered'         => 'q.date_answered',
				'date_modified'         => 'q.date_modified',
				'question_author_name'  => 'q.question_author_name',
				'question_author_email' => 'q.question_author_email',
				'question_author_phone' => 'q.question_author_phone',
				'question_author_custom'=> 'q.question_author_custom',
				'question'              => 'q.question',
				'answer'                => 'q.answer',
				'answer_author_name'    => 'q.answer_author_name',
			);

			foreach ($_filters as $key => $value) {
				$filters['OR'][] = $value . " LIKE '%" . $this->db->escape($data["search"]) . "%'";
			}
		}

		$int_filters = array(
			'id'                => 'q.qa_id',
			'status'            => 'q.status',
			'language'          => 'q.language_id',
			);

		foreach ($int_filters as $key => $value) {
			if (isset($data["filter"][$key]) && !is_null($data["filter"][$key])) {
				$filters['AND'][] = "$value = '" . (int)$data["filter"][$key] . "'";
			}
		}

		$date_filters = array(
			'date_asked'        => 'q.date_asked',
			'date_answered'     => 'q.date_answered',
			'date_modified'     => 'q.date_modified',
			);

		foreach ($date_filters as $key => $value) {
			if (isset($data["filter"][$key]) && !is_null($data["filter"][$key])) {
				$filters['AND'][] = $this->db->escape($data["filter"][$key]);
			}
		}

		$anywhere_filters = array(
			'product'               => 'pd.name',
			'question_author_name'  => 'q.question_author_name',
			'question_author_email' => 'q.question_author_email',
			'question_author_phone' => 'q.question_author_phone',
			'question_author_custom'=> 'q.question_author_custom',
			'answer_author_name'    => 'q.answer_author_name',
			'question'              => 'q.question',
			'answer'                => 'q.answer',
			);

		foreach ($anywhere_filters as $key => $value) {
			if (!empty($data["filter"][$key])) {
				$filters['AND'][] = "$value LIKE '%" . $this->db->escape($data["filter"][$key]) . "%'";
			}
		}

		if (isset($data['filter']['store'])) {
			if ($data['filter']['store'] == '*') {
				$filters['AND'][] = "q2s2.store_id IS NULL";
			} else {
				$filters['AND'][] = "q2s2.store_id = '" . (int)$data['filter']['store'] . "'";
			}
		}

		if ($filters['AND'] || $filters['OR']) {
			$sql .= " WHERE";

			if ($filters['OR']) {
				$sql .= " (" . implode(" OR ", $filters['OR']) . ")";

				if ($filters['AND']) {
					$sql .= " AND " . implode(" AND ", $filters['AND']);
				}
			} else if ($filters['AND']) {
				$sql .= " " . implode(" AND ", $filters['AND']);
			}
		}

		$sql .= " GROUP BY q.qa_id";

		$filters = array('AND' => array(), 'OR' => array());

		// Global search
		if (!empty($data['search']) && (in_array("store", $columns))) {
			$_filters = array(
				'id'                    => 'q.qa_id',
				'product'               => 'pd.name',
				'status'                => 'status_text',
				'date_asked'            => 'q.date_asked',
				'date_answered'         => 'q.date_answered',
				'date_modified'         => 'q.date_modified',
				'question_author_name'  => 'q.question_author_name',
				'question_author_email' => 'q.question_author_email',
				'question_author_phone' => 'q.question_author_phone',
				'question_author_custom'=> 'q.question_author_custom',
				'question'              => 'q.question',
				'answer'                => 'q.answer',
				'answer_author_name'    => 'q.answer_author_name',
				'store'                 => "GROUP_CONCAT(DISTINCT IF(q2s.store_id = 0, '" . $this->db->escape($this->config->get('config_name')) . "', s.name) SEPARATOR ' ')",
			);

			foreach ($_filters as $key => $value) {
				$filters['OR'][] = $value . " LIKE '%" . $this->db->escape($data["search"]) . "%'";
			}
		}

		if ($filters['AND'] || $filters['OR']) {
			$sql .= " HAVING";

			if ($filters['OR']) {
				$sql .= " (" . implode(" OR ", $filters['OR']) . ")";
			}

			if ($filters['AND']) {
				$sql .= " " . implode(" AND ", $filters['AND']);
			}
		}

		$sort_data = array(
			'pd.name',
			'l.name',
			'question_author_name',
			'answer_author_name',
			'date_asked',
			'date_answered',
			'date_modified',
			'status'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY date_asked";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if ($data['start'] != '' || $data['limit'] != '') {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		$count = $this->db->query("SELECT FOUND_ROWS() AS count");
		$this->filteredCount = ($count->num_rows) ? (int)$count->row['count'] : 0;

		if ($query) {
			return $query->rows;
		} else {
			return array();
		}
	}

	public function getFilteredTotalQuestions() {
		return $this->filteredCount;
	}

	public function getTotalQuestions($data = array()) {
		if (!empty($data['filter_date_asked'])) {
			$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "qa WHERE DATE(date_asked) = DATE('" . $this->db->escape($data['filter_date_asked']) . "')";
		} else {
			$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "qa";
		}

		$query = $this->db->query($sql);

		$count = (int)$query->row['total'];

		return $count;
	}

	public function getTotalQuestionsAwaitingAnswer() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "qa WHERE answer = ''");

		return (int)$query->row['total'];
	}
}
