<?php
class ModelExtensionModuleInventoryModuleAllProducts extends Model {
    
    public function getAllInventoryProducts($data = array()) {
        $sql = "SELECT p.product_id, p.weight, p.unit_weight, p.weight_class_id, p.sku, p.image, p.quantity, p.price, pd.name, p.status, 
                cd.name AS category, 
                aid.purchase_price, 
                aid.additional_cost 
                FROM " . DB_PREFIX . "product p 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
                -- Optimized Category Fetching
                LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) 
                LEFT JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "') 
                -- Optimized Inventory Details Fetching
                LEFT JOIN " . DB_PREFIX . "inventory_details aid ON (p.product_id = aid.product_id AND aid.is_merge_lot_quantity_to_main = 1) 
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        // Apply Filters
        $sql .= $this->buildFilterString($data);

        // Group by product ID to prevent duplicates if a product is in multiple categories
        $sql .= " GROUP BY p.product_id";

        // Sorting Logic
        $sort_data = array(
            'pd.name',
            'p.sku',
            'p.price',
            'p.quantity',
            'p.status',
            'p.weight',
            'p.unit_weight',
            'category',
            'purchase_price',
            'additional_cost',
        );

        $sort = (isset($data['sort']) && in_array($data['sort'], $sort_data)) ? $data['sort'] : 'pd.name';
        $order = (isset($data['order']) && ($data['order'] == 'DESC')) ? 'DESC' : 'ASC';
        
        $sql .= " ORDER BY " . $sort . " " . $order;

        // Pagination
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) $data['start'] = 0;
            if ($data['limit'] < 1) $data['limit'] = 20;
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalAllInventoryProducts($data = array()) {
        $sql = "SELECT COUNT(DISTINCT p.product_id) AS total 
                FROM " . DB_PREFIX . "product p 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $sql .= $this->buildFilterString($data);

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * Private helper to ensure filter logic is identical for both methods
     */
    private function buildFilterString($data) {
        $implode = array();

        // Fix: Name search (Middle/Inside)
        if (!empty($data['filter_name'])) {
            $implode[] = "pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        // Fix: SKU search (Middle/Inside)
        if (!empty($data['filter_sku'])) {
            $implode[] = "p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
        }

        // Add: Quantity filter
        if (isset($data['filter_quantity']) && $data['filter_quantity'] !== null && $data['filter_quantity'] !== '') {
            $implode[] = "p.quantity = '" . (int)$data['filter_quantity'] . "'";
        }

        return $implode ? " AND " . implode(" AND ", $implode) : "";
    }
}