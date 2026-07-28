<?php
class ModelExtensionModuleInventoryModuleHistory extends Model {


    public function getInventoryHistory($data = array()) {
        $sql = "SELECT id.*, i.inventory_date, i.inventory_lotnumber, pd.name AS product_name, p.sku, cd.name AS category_name 
            FROM " . DB_PREFIX . "inventory_details id 
            LEFT JOIN " . DB_PREFIX . "inventory i ON (id.inventory_id = i.inventory_id) 
            LEFT JOIN " . DB_PREFIX . "product p ON (id.product_id = p.product_id) 
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
            LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) 
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id) 
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            AND (cd.language_id = '" . (int)$this->config->get('config_language_id') . "' OR cd.language_id IS NULL)";

        // 1. Filter by Product Name
        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        // 2. Filter by SKU
        if (!empty($data['filter_sku'])) {
            $sql .= " AND p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
        }

        // 3. Filter by Lot Number
        if (!empty($data['filter_lot'])) {
            $sql .= " AND i.inventory_lotnumber LIKE '%" . $this->db->escape($data['filter_lot']) . "%'";
        }

        // 4. Advanced Date Range Logic
        if (!empty($data['filter_date_range'])) {
            switch ($data['filter_date_range']) {
                case 'today':
                    $sql .= " AND DATE(i.inventory_date) = CURDATE()";
                    break;
                case 'last_7_days':
                    $sql .= " AND i.inventory_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'last_month':
                    $sql .= " AND i.inventory_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                    break;
                case 'last_3_months':
                    $sql .= " AND i.inventory_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
                    break;
                case 'last_year':
                    $sql .= " AND i.inventory_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    break;
                case 'custom':
                    if (!empty($data['filter_date_start'])) {
                        $sql .= " AND DATE(i.inventory_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
                    }
                    if (!empty($data['filter_date_end'])) {
                        $sql .= " AND DATE(i.inventory_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
                    }
                    break;
            }
        }

        // 5. Grouping and Sorting by ID (Latest Entry First)
        $sql .= " GROUP BY id.inventory_details_id";

        // --- Dynamic Sorting Logic ---
        $sort_data = array(
            'i.inventory_date',
            'i.inventory_lotnumber',
            'sku',
            'product_name',
            'id.quantity',
            'id.current_quantity',
            'id.additional_cost',
            'id.purchase_price',
            'id.sale_price',
            'id.total_price'
        );
    
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY id.inventory_details_id"; // Default
        }
    
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
    
        // --- Pagination ---
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) $data['start'] = 0;
            if ($data['limit'] < 1) $data['limit'] = 20;
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
    
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalInventoryHistory($data = array()) {
        $sql = "SELECT COUNT(DISTINCT id.inventory_details_id) AS total 
                FROM " . DB_PREFIX . "inventory_details id 
                LEFT JOIN " . DB_PREFIX . "inventory i ON (id.inventory_id = i.inventory_id) 
                LEFT JOIN " . DB_PREFIX . "product p ON (id.product_id = p.product_id) 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        // Apply same filters to count total accurately
        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        if (!empty($data['filter_sku'])) {
            $sql .= " AND p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
        }
        if (!empty($data['filter_lot'])) {
            $sql .= " AND i.inventory_lotnumber LIKE '%" . $this->db->escape($data['filter_lot']) . "%'";
        }
        
        // Re-apply Date Range Filters for correct count
        if (!empty($data['filter_date_range']) && $data['filter_date_range'] != 'lifetime') {
            if ($data['filter_date_range'] == 'custom') {
                if (!empty($data['filter_date_start'])) { $sql .= " AND DATE(i.inventory_date) >= '" . $this->db->escape($data['filter_date_start']) . "'"; }
                if (!empty($data['filter_date_end'])) { $sql .= " AND DATE(i.inventory_date) <= '" . $this->db->escape($data['filter_date_end']) . "'"; }
            } else {
                // Simplified logic for non-custom ranges in count
                $ranges = [
                    'today' => "CURDATE()",
                    'last_7_days' => "DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
                    'last_month' => "DATE_SUB(CURDATE(), INTERVAL 1 MONTH)",
                    'last_3_months' => "DATE_SUB(CURDATE(), INTERVAL 3 MONTH)",
                    'last_year' => "DATE_SUB(CURDATE(), INTERVAL 1 YEAR)"
                ];
                if (isset($ranges[$data['filter_date_range']])) {
                    $sql .= " AND i.inventory_date >= " . $ranges[$data['filter_date_range']];
                }
            }
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }
    
}