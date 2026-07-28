<?php
class ModelExtensionModuleInventoryModuleTopSelling extends Model {

public function getTopSellingProducts($data = array()) {
    // 1. We wrap the GROUP BY in a subquery 't'
    $sql = "SELECT t.*, RANK() OVER (ORDER BY t.total_sold DESC) as sales_rank FROM (
                SELECT 
                    op.product_id, 
                    pd.name, 
                    p.sku, 
                    p.image, 
                    SUM(op.quantity) AS total_sold, 
                    p.price 
                FROM " . DB_PREFIX . "order_product op 
                LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id) 
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id) 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
                WHERE o.order_status_id > '0' 
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

    if (!empty($data['filter_date_start'])) {
        $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
    }
    
    if (!empty($data['filter_date_end'])) {
        $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
    }

    $sql .= " GROUP BY op.product_id) t"; // Subquery alias 't'

    // 2. Dynamic Sorting (Applied to the results of the subquery)
    $sort_data = array(
        'pd.name', // You can use t.name if pd.name fails
        'p.sku',   // You can use t.sku if p.sku fails
        'p.price',
        'total_sold'
    );

    // Map sort names to the subquery alias if necessary
    $sort = 'total_sold';
    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
        // Since we are selecting from subquery 't', we use those column names
        if ($data['sort'] == 'pd.name') $sort = 'name';
        elseif ($data['sort'] == 'p.sku') $sort = 'sku';
        elseif ($data['sort'] == 'p.price') $sort = 'price';
        else $sort = $data['sort'];
    }

    $sql .= " ORDER BY " . $sort;

    if (isset($data['order']) && ($data['order'] == 'ASC')) {
        $sql .= " ASC";
    } else {
        $sql .= " DESC";
    }

    if (isset($data['start']) || isset($data['limit'])) {
        if ($data['start'] < 0) $data['start'] = 0;
        if ($data['limit'] < 1) $data['limit'] = 10;
        $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
    }

    $query = $this->db->query($sql);
    return $query->rows;
}        
}