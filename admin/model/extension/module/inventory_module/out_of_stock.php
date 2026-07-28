<?php
class ModelExtensionModuleInventoryModuleOutOfStock extends Model {

    public function getOutOfStockProducts($data = array()) {
        $sql = "SELECT p.product_id, p.weight, p.unit_weight, p.weight_class_id, p.sku, p.image, p.quantity, p.price, pd.name, p.status, 
            (SELECT cd.name FROM " . DB_PREFIX . "product_to_category p2c 
             LEFT JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id) 
             WHERE p2c.product_id = p.product_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
             LIMIT 1) AS category,
            (SELECT aid.purchase_price FROM " . DB_PREFIX . "inventory_details aid 
             WHERE aid.product_id = p.product_id AND aid.is_merge_lot_quantity_to_main = 1 
             ORDER BY aid.inventory_details_id DESC LIMIT 1) AS purchase_price,
            (SELECT aid.additional_cost FROM " . DB_PREFIX . "inventory_details aid 
             WHERE aid.product_id = p.product_id AND aid.is_merge_lot_quantity_to_main = 1 
             ORDER BY aid.inventory_details_id DESC LIMIT 1) AS additional_cost 
            FROM " . DB_PREFIX . "product p 
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND p.quantity <= 0";

        // Filter by Name if provided
        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        // Filter by SKU if provided
        if (!empty($data['filter_sku'])) {
            $sql .= " AND p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
        }

        $sort_data = array(
            'p.product_id',
            'pd.name',
            'p.sku',
            'p.price',
            'p.status',
            'p.quantity',
            'p.status',
            'p.weight',
            'p.unit_weight',
            'category',
            'purchase_price',
            
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY p.quantity";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) $data['start'] = 0;
            if ($data['limit'] < 1) $data['limit'] = 20;
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalOutOfStockProducts($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product p 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND p.quantity <= 0";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_sku'])) {
            $sql .= " AND p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }
}