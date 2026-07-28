<?php
class ModelExtensionModulePreOrder extends Model {
    public function __construct($register) {
        if (!defined('IMODULE_ROOT')) define('IMODULE_ROOT', substr(DIR_APPLICATION, 0, strrpos(DIR_APPLICATION, '/', -2)) . '/');
        if (!defined('IMODULE_SERVER_NAME')) define('IMODULE_SERVER_NAME', substr((defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER), 7, strlen((defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER)) - 8));
        parent::__construct($register);
    }

    public function getProductPreorder($product_id) {
        $product_preorder_data = array();

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "preorder_product` WHERE product_id = '" . (int)$product_id . "'");

        foreach ($query->rows as $result) {
            $product_preorder_data[$result['language_id']] = array(
                'preorder_note'      => $result['preorder_note'],
                'preorder_date'      => $result['preorder_date'] != '0000-00-00' ? $result['preorder_date'] : '',
                'preorder_quantity'  => $result['preorder_quantity']
            );
        }

        return $product_preorder_data;
    }

    public function viewcustomers($page=1, $limit=8, $store_id=0) {
        if ($page) {
            $start = ($page - 1) * $limit;
        }
        $query =  $this->db->query("SELECT super.*, o.*, l.name as language, product.name as product_name FROM `" . DB_PREFIX . "preorder` super
            LEFT JOIN `" . DB_PREFIX . "product_description` product on (super.product_id = product.product_id AND super.language_id = product.language_id)
            LEFT JOIN `" . DB_PREFIX . "language` l on super.language_id = l.language_id
            LEFT JOIN `" . DB_PREFIX . "order` o on super.order_id = o.order_id
            WHERE super.store_id = " . (int)$store_id . "
                AND o.order_status_id > 0
                AND o.order_status_id NOT IN ('" . implode("','", $this->config->get("config_complete_status")) . "')
            ORDER BY `date_created` DESC
            LIMIT ".$start.", ".$limit);

        return $query->rows;
    }


    public function viewnotifiedcustomers($page=1, $limit=8, $store_id=0) {
        if ($page) {
            $start = ($page - 1) * $limit;
        }
        $query =  $this->db->query("SELECT super.*, o.*, l.name as language, product.name as product_name FROM `" . DB_PREFIX . "preorder` super
            LEFT JOIN `" . DB_PREFIX . "product_description` product on (super.product_id = product.product_id AND super.language_id = product.language_id)
            LEFT JOIN `" . DB_PREFIX . "language` l on super.language_id = l.language_id
            LEFT JOIN `" . DB_PREFIX . "order_history` oh on super.order_id = oh.order_id
            LEFT JOIN `" . DB_PREFIX . "order` o on super.order_id = o.order_id
            WHERE oh.notify=1
                AND super.language_id = " . (int)$this->config->get('config_language_id') . "
                AND super.store_id = " . (int)$store_id . "
                AND o.order_status_id > 0
            GROUP BY super.order_product_id
            ORDER BY `date_created` DESC
            LIMIT ".$start.", ".$limit);

        return $query->rows;
    }

    public function getProductOptions($product_id)
    {
        $query = $this->db->query("SELECT product_id, name, max(order_id) AS order_id FROM " . DB_PREFIX . "order_product
            WHERE product_id = '" . (int)$product_id . "'
            GROUP BY `product_id`");

        if ($query->row) {
            $query->row['options'] = $this->getProductOptionsByOrderId($query->row['order_id'], $product_id);
        }

        return $query->row;
    }

    public function getProductOptionsByOrderId($order_id, $product_id, $order_product_id = 0)
    {
        $sql = "SELECT oo.* FROM " . DB_PREFIX . "order_option oo
            LEFT JOIN `" . DB_PREFIX . "order_product` op on op.order_product_id = oo.order_product_id
            WHERE oo.order_id = '" . (int)$order_id . "'
                AND op.product_id = '" . (int)$product_id . "'";

        if ($order_product_id) {
            $sql .= " AND oo.order_product_id = '" . (int)$order_product_id . "'";
        }

        return $this->db->query($sql)->rows;
    }

    public function getTotalCustomers($store_id=0) {
        $query = $this->db->query("SELECT COUNT(*) as `count`  FROM `" . DB_PREFIX . "preorder` p
            LEFT JOIN `" . DB_PREFIX . "order` o on p.order_id = o.order_id
            WHERE p.language_id = " . (int)$this->config->get('config_language_id') . "
                AND o.order_status_id > 0
				AND o.order_status_id NOT IN ('" . implode("','", $this->config->get("config_complete_status")) . "')
                AND p.store_id=".$store_id);

        return $query->row['count'];
    }

    public function getTotalNotifiedCustomers($store_id=0) {
        $query = $this->db->query("SELECT COUNT(*) as `count`  FROM `" . DB_PREFIX . "preorder` p
            LEFT JOIN `" . DB_PREFIX . "order` o on p.order_id = o.order_id
            LEFT JOIN `" . DB_PREFIX . "order_history` oh on p.order_id = oh.order_id
            WHERE oh.notify=1
                AND p.language_id= " . (int)$this->config->get('config_language_id') . "
                AND o.order_status_id > 0
                AND p.store_id=".$store_id);
        return $query->row['count'];
    }


    public function getStatistics($store_id=0) {
        $run_query = $this->db->query("SELECT *, pd.name FROM `".DB_PREFIX."preorder` p
            LEFT JOIN `" . DB_PREFIX . "order` o on p.order_id = o.order_id
            LEFT JOIN `" . DB_PREFIX . "order_history` oh on p.order_id = oh.order_id
            LEFT JOIN `" . DB_PREFIX . "product_description` pd on (p.product_id = pd.product_id)
            WHERE p.store_id = ". $store_id ."
                AND pd.language_id =  " . (int)$this->config->get('config_language_id') . "
                AND o.order_status_id > 0");
        return $run_query;
    }

    public function checkPreorderOrder($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "preorder` po
        LEFT JOIN `" . DB_PREFIX . "order` o on po.order_id = o.order_id
        WHERE o.order_status_id > 0
            AND o.order_status_id NOT IN ('" . implode("','", $this->config->get("config_complete_status")) . "')
            AND po.order_id='".(int)$order_id."'");

        if ($query->num_rows > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    public function checkPreorderProduct($order_product_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "preorder` WHERE order_product_id='" . $order_product_id . "'");

        if ($query->num_rows > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    // ================================

    public function install() {
        $query = $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "preorder`
            (`preorder_id` INT(11) NOT NULL AUTO_INCREMENT,
             `product_id` INT(11) NULL DEFAULT '0',
             `order_product_id` INT(11) NULL DEFAULT '0',
             `order_id` INT(11) NOT NULL DEFAULT '0',
             `date_created` DATETIME  NOT NULL DEFAULT '0000-00-00 00:00:00',
             `store_id` int(11) DEFAULT NULL,
             `language_id` INT(11) NOT NULL DEFAULT '".$this->config->get('config_language_id')."',
             PRIMARY KEY (`preorder_id`));");

        // PreOrder setting in admin product page
        $query = $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "preorder_product`
            (`preorder_product_id` INT(11) NOT NULL AUTO_INCREMENT,
             `product_id` INT(11) NULL DEFAULT '0',
             `preorder_note` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
             `preorder_date` DATE  NOT NULL DEFAULT '0000-00-00' ,
             `preorder_quantity` INT(4) NOT NULL DEFAULT '99',
             `language_id` INT(11) NOT NULL DEFAULT '".$this->config->get('config_language_id')."',
             PRIMARY KEY (`preorder_product_id`));");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "preorder`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "preorder_product`");
        $this->db->query("DELETE FROM " . DB_PREFIX . "layout_module WHERE code = 'preorder'");
    }

    public function update() {
        // An update for preorder-quantity
        $query = $this->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "preorder_product' AND COLUMN_NAME = 'preorder_quantity';");
        if (empty($query->row)) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "preorder_product` ADD COLUMN `preorder_quantity` INT(4) NOT NULL DEFAULT '99' AFTER `preorder_date`;");
        }
    }
}
