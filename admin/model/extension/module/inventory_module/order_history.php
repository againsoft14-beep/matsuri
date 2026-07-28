<?php
class ModelExtensionModuleInventoryModuleOrderHistory extends Model {
    public function getOrdersByProduct($data = []) {
        $sql = "SELECT op.order_product_id, o.order_id, op.quantity, op.price, op.total, 
            CONCAT(o.firstname, ' ', o.lastname) AS customer, op.name AS product_name, 
            p.sku, p.product_id, o.telephone, os.name AS status, o.date_added,
            inv.inventory_lotnumber, inv.inventory_id
            FROM `" . DB_PREFIX . "order_product` op 
            LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) 
            LEFT JOIN `" . DB_PREFIX . "product` p ON (op.product_id = p.product_id) 
            LEFT JOIN `" . DB_PREFIX . "order_status` os ON (o.order_status_id = os.order_status_id) 
            LEFT JOIN `" . DB_PREFIX . "inventory_details` id ON (op.lot_id = id.inventory_details_id) 
            LEFT JOIN `" . DB_PREFIX . "inventory` inv ON (inv.inventory_id = id.inventory_id) 
            WHERE o.order_status_id > 0 
            AND os.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $sql .= $this->buildFilterSql($data);

        $sort_data = array(
            'o.order_id',
            'o.date_added',
            'customer',
            'o.telephone',         
            'product_name',
            'p.sku',
            'inv.inventory_lotnumber',
            'op.quantity',
            'op.total',
            'status'
        );
    
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY o.order_id"; // ডিফল্ট
        }
    
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
    
        if (isset($data['start']) || isset($data['limit'])) {
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        return $this->db->query($sql)->rows;
    }

    public function getTotalOrdersByProduct($data = []) {
        // Important: All JOINs used in the filter must be present here for accurate counts
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order_product` op 
                LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) 
                LEFT JOIN `" . DB_PREFIX . "product` p ON (op.product_id = p.product_id) 
                LEFT JOIN `" . DB_PREFIX . "inventory_details` id ON (op.lot_id = id.inventory_details_id) 
                LEFT JOIN `" . DB_PREFIX . "inventory` inv ON (inv.inventory_id = id.inventory_id) 
                WHERE o.order_status_id > 0";

        $sql .= $this->buildFilterSql($data);
        
        return $this->db->query($sql)->row['total'];
    }

    private function buildFilterSql($data) {
        $sql = "";

        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_product'])) {
            $sql .= " AND op.name LIKE '%" . $this->db->escape($data['filter_product']) . "%'";
        }

        if (!empty($data['filter_lot_number'])) {
            // Fixed typo: invenory -> inventory
            $sql .= " AND inv.inventory_lotnumber LIKE '%" . $this->db->escape($data['filter_lot_number']) . "%'";
        }

        if (!empty($data['filter_sku'])) {
            $sql .= " AND p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
        }

        if (!empty($data['filter_phone'])) {
            $sql .= " AND o.telephone LIKE '%" . $this->db->escape($data['filter_phone']) . "%'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        return $sql;
    }
}