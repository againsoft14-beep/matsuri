<?php
class ModelExtensionModuleInventoryModuleReturns extends Model {

    public function getReturnsByInventory($data = array()) {
        $sql = "SELECT 
                    r.return_id, 
                    r.order_id, 
                    r.product_id, 
                    r.product, 
                    CONCAT(r.firstname, ' ', r.lastname) AS customer, 
                    r.model, 
                    r.telephone, 
                    p.sku, 
                    r.quantity, 
                    rs.name AS status, 
                    r.date_added 
                FROM `" . DB_PREFIX . "return` r 
                LEFT JOIN `" . DB_PREFIX . "product` p ON (r.product_id = p.product_id) 
                LEFT JOIN `" . DB_PREFIX . "return_status` rs ON (r.return_status_id = rs.return_status_id) 
                WHERE rs.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        // Add Filters
        $sql .= $this->buildFilterSql($data);

        $sql .= " ORDER BY r.date_added DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) $data['start'] = 0;
            if ($data['limit'] < 1) $data['limit'] = 20;
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        return $this->db->query($sql)->rows;
    }

    public function getTotalReturnsByInventory($data = array()) {
        $sql = "SELECT COUNT(*) AS total 
                FROM `" . DB_PREFIX . "return` r 
                LEFT JOIN `" . DB_PREFIX . "return_status` rs ON (r.return_status_id = rs.return_status_id) 
                WHERE rs.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $sql .= $this->buildFilterSql($data);

        return $this->db->query($sql)->row['total'];
    }

    private function buildFilterSql($data) {
        $sql = "";

        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(r.firstname, ' ', r.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_product'])) {
            $sql .= " AND r.product LIKE '%" . $this->db->escape($data['filter_product']) . "%'";
        }

        if (!empty($data['filter_order_id'])) {
            $sql .= " AND r.order_id = '" . (int)$data['filter_order_id'] . "'";
        }
        
        if (!empty($data['filter_phone'])) {
            $sql .= " AND r.telephone LIKE '%" . $this->db->escape($data['filter_phone']) . "%'";
        }
    
        // Added Lot Number Filter (requires joining inventory tables in the main query)
        if (!empty($data['filter_lot_number'])) {
            $sql .= " AND inv.inventory_lotnumber LIKE '" . $this->db->escape($data['filter_lot_number']) . "%'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(r.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(r.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        return $sql;
    }
}