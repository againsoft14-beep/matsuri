<?php
class ModelExtensionModuleInventoryModuleInventory extends Model {
    
    public function addInventory($data) {
        
        $inventory_date = $data['inventory_year'] . '-' . 
                      str_pad($data['inventory_month'], 2, '0', STR_PAD_LEFT) . '-' . 
                      str_pad($data['inventory_day'], 2, '0', STR_PAD_LEFT);
                      
        $status_map = [
            'pending'  => 0,
            'upcoming' => 1,
            'received' => 2
        ];
        
        $status = $status_map[$data['inventory_status']] ?? 0;
                      
        // $status = ($data['inventory_status'] == 'pending') ? 0 : 1;
                  
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory SET
            inventory_date = '" . $this->db->escape($inventory_date) . "',
            inventory_day = '" . (int)$data['inventory_day'] . "',
            inventory_month = '" . (int)$data['inventory_month'] . "',
            inventory_year = '" . (int)$data['inventory_year'] . "',
            inventory_lotnumber = '" . $this->db->escape($data['inventory_lotnumber']) . "',
            supplier_id = '" . (int)$data['supplier_id'] . "',
            status = '" . (int)$status . "',
            timestamp = NOW()");

        $inventory_id = $this->db->getLastId();

        if (!empty($data['products'])) {
            foreach ($data['products'] as $product) {
                $damage_quantity = isset($product['damage_quantity']) ? (int)$product['damage_quantity'] : 0;
                $current_quantity = (int)$product['quantity'] - $damage_quantity;

                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_details SET
                    inventory_id = '" . (int)$inventory_id . "',
                    product_id = '" . (int)$product['product_id'] . "',
                    quantity = '" . (int)$product['quantity'] . "',
                    damage_quantity = '" . (int)$damage_quantity . "',
                    current_quantity = '" . (int)$current_quantity . "',
                    sale_price = '" . (float)$product['sale_price'] . "',
                    purchase_price = '" . (float)$product['purchase_price'] . "',
                    additional_cost = '" . (float)$product['additional_cost'] . "',
                    total_price = '" . (float)$product['total_price'] . "',
                    remarks = '" . $this->db->escape($product['remarks'] ?? '') . "'
                ");
            }
        }
    
        return $inventory_id;
    }
    
    public function editInventory($inventory_id, $data) {
        $inventory_date = $data['inventory_year'] . '-' . str_pad($data['inventory_month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($data['inventory_day'], 2, '0', STR_PAD_LEFT);
    
        $status_map = [
            'pending'  => 0,
            'upcoming' => 1,
            'received' => 2
        ];
        
        $status = $status_map[$data['inventory_status']] ?? 0;
                          
        // Update the main inventory table
        $this->db->query("UPDATE " . DB_PREFIX . "inventory SET
            inventory_date = '" . $this->db->escape($inventory_date) . "',
            inventory_day = '" . (int)$data['inventory_day'] . "',
            inventory_month = '" . (int)$data['inventory_month'] . "',
            inventory_year = '" . (int)$data['inventory_year'] . "',
            inventory_lotnumber = '" . $this->db->escape($data['inventory_lotnumber']) . "',
            supplier_id = '" . (int)$data['supplier_id'] . "',
            status = '" . (int)$status . "'
            WHERE inventory_id = '" . (int)$inventory_id . "'");
    
        // ✅ DELETE সরিয়ে দিন — শুধু submitted products update করুন
        if (!empty($data['products'])) {
            foreach ($data['products'] as $product) {
                $damage_quantity  = isset($product['damage_quantity']) ? (int)$product['damage_quantity'] : 0;
                $current_quantity = (int)$product['quantity'] - $damage_quantity;
    
                // product টা এই lot-এ আছে কিনা চেক করুন
                $existing = $this->db->query("SELECT inventory_details_id FROM " . DB_PREFIX . "inventory_details 
                    WHERE inventory_id = '" . (int)$inventory_id . "' 
                    AND product_id = '" . (int)$product['product_id'] . "'");
    
                if ($existing->num_rows) {
                    // ✅ আছে — UPDATE করুন
                    $this->db->query("UPDATE " . DB_PREFIX . "inventory_details SET
                        quantity = '" . (int)$product['quantity'] . "',
                        damage_quantity = '" . (int)$damage_quantity . "',
                        current_quantity = '" . (int)$current_quantity . "',
                        is_merge_lot_quantity_to_main = '" . (int)$product['is_merge_lot_quantity_to_main'] . "',
                        sale_price = '" . (float)$product['sale_price'] . "',
                        purchase_price = '" . (float)$product['purchase_price'] . "',
                        additional_cost = '" . (float)$product['additional_cost'] . "',
                        total_price = '" . (float)$product['total_price'] . "',
                        remarks = '" . $this->db->escape($product['remarks'] ?? '') . "'
                        WHERE inventory_id = '" . (int)$inventory_id . "' 
                        AND product_id = '" . (int)$product['product_id'] . "'");
                } else {
                    // ✅ নেই — নতুন INSERT করুন
                    $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_details SET
                        inventory_id = '" . (int)$inventory_id . "',
                        product_id = '" . (int)$product['product_id'] . "',
                        quantity = '" . (int)$product['quantity'] . "',
                        damage_quantity = '" . (int)$damage_quantity . "',
                        current_quantity = '" . (int)$current_quantity . "',
                        is_merge_lot_quantity_to_main = '" . (int)$product['is_merge_lot_quantity_to_main'] . "',
                        sale_price = '" . (float)$product['sale_price'] . "',
                        purchase_price = '" . (float)$product['purchase_price'] . "',
                        additional_cost = '" . (float)$product['additional_cost'] . "',
                        total_price = '" . (float)$product['total_price'] . "',
                        remarks = '" . $this->db->escape($product['remarks'] ?? '') . "'
                    ");
                }
            }
        }
    }
    
    public function getProductCategories($product_id) {
        $query = $this->db->query("SELECT cd.name FROM " . DB_PREFIX . "product_to_category p2c
            LEFT JOIN " . DB_PREFIX . "category_description cd 
                ON (p2c.category_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE p2c.product_id = '" . (int)$product_id . "'");
        
        return array_column($query->rows, 'name');
    }


    public function editInventoryOld($inventory_id, $data) {
        $inventory_date = $data['inventory_year'] . '-' . str_pad($data['inventory_month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($data['inventory_day'], 2, '0', STR_PAD_LEFT);
    
        $status_map = [
            'pending'  => 0,
            'upcoming' => 1,
            'received' => 2
        ];
        
        $status = $status_map[$data['inventory_status']] ?? 0;
                          
        // Update the main inventory table
        $this->db->query("UPDATE " . DB_PREFIX . "inventory SET
            inventory_date = '" . $this->db->escape($inventory_date) . "',
            inventory_day = '" . (int)$data['inventory_day'] . "',
            inventory_month = '" . (int)$data['inventory_month'] . "',
            inventory_year = '" . (int)$data['inventory_year'] . "',
            inventory_lotnumber = '" . $this->db->escape($data['inventory_lotnumber']) . "',
            supplier_id = '" . (int)$data['supplier_id'] . "',
            status = '" . (int)$status . "'
            WHERE inventory_id = '" . (int)$inventory_id . "'");
    
      
    
        // Clear old details
        $this->db->query("DELETE FROM " . DB_PREFIX . "inventory_details
            WHERE inventory_id = '" . (int)$inventory_id . "'");
    
        // Insert new details and update stock
        if (!empty($data['products'])) {
            foreach ($data['products'] as $product) {
                $damage_quantity = isset($product['damage_quantity']) ? (int)$product['damage_quantity'] : 0;
                $current_quantity = (int)$product['quantity'] - $damage_quantity;

                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_details SET
                    inventory_id = '" . (int)$inventory_id . "',
                    product_id = '" . (int)$product['product_id'] . "',
                    quantity = '" . (int)$product['quantity'] . "',
                    damage_quantity = '" . (int)$damage_quantity . "',
                    current_quantity = '" . (int)$current_quantity . "',
                    is_merge_lot_quantity_to_main = '" . (int)$product['is_merge_lot_quantity_to_main'] . "',
                    sale_price = '" . (float)$product['sale_price'] . "',
                    purchase_price = '" . (float)$product['purchase_price'] . "',
                    additional_cost = '" . (float)$product['additional_cost'] . "',
                    total_price = '" . (float)$product['total_price'] . "',
                    remarks = '" . $this->db->escape($product['remarks'] ?? '') . "'
                ");
            }
        }
    }


    public function getInventories($data = array()) {

        $sql = "SELECT i.*, s.name AS supplier_name,
            SUM(id.total_price) AS total_amount,
            COUNT(id.inventory_details_id) AS total_products
            FROM " . DB_PREFIX . "inventory i
            LEFT JOIN " . DB_PREFIX . "inventory_supplier s ON (i.supplier_id = s.supplier_id)
            LEFT JOIN " . DB_PREFIX . "inventory_details id ON (i.inventory_id = id.inventory_id)
            WHERE 1=1";

        // --- Filters ---
        if (!empty($data['filter_title'])) {
            $sql .= " AND i.inventory_lotnumber LIKE '%" . $this->db->escape($data['filter_title']) . "%'";
        }
    
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(i.inventory_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
    
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(i.inventory_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
    
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND i.status = '" . (int)$data['filter_status'] . "'";
        }
    
        // --- Grouping (Crucial for Aggregate Functions) ---
        $sql .= " GROUP BY i.inventory_id";
    
        // --- Sorting ---
        $sort_data = array(
            'i.inventory_date',
            'i.inventory_lotnumber',
            'i.status',
            'total_amount'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY i.inventory_date";
        }
        
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        
        // Pagination
        if (isset($data['start']) || isset($data['limit'])) {
    
            if (!isset($data['start']) || $data['start'] < 0) {
                $data['start'] = 0;
            }
    
            if (!isset($data['limit']) || $data['limit'] < 1) {
                $data['limit'] = 20;
            }
    
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
    
        $query = $this->db->query($sql);
    
        return $query->rows;
    }
    
    public function getTotalInventories($data = []) {
        $sql = "SELECT COUNT(DISTINCT i.inventory_id) AS total FROM " . DB_PREFIX . "inventory i WHERE 1";
    
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(i.inventory_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
    
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(i.inventory_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
    
        if (!empty($data['filter_title'])) {
            $sql .= " AND i.inventory_lotnumber LIKE '%" . $this->db->escape($data['filter_title']) . "%'";
        }
    
        if ($data['filter_status'] !== '') {
            $sql .= " AND i.status = '" . (int)$data['filter_status'] . "'";
        }
    
        $query = $this->db->query($sql);
    
        return $query->row['total'];
    }

    public function getInventory($inventory_id) {

        $query = $this->db->query("SELECT i.*, s.name AS supplier_name
            FROM " . DB_PREFIX . "inventory i
            LEFT JOIN " . DB_PREFIX . "inventory_supplier s
            ON (i.supplier_id = s.supplier_id)
            WHERE i.inventory_id = '" . (int)$inventory_id . "'");

        return $query->row;
    }
    
    public function getInventoryDetails($inventory_id, $data = array()) {
        $sql = "SELECT 
            id.*, 
            pd.name AS product_name, 
            SUM(CASE WHEN o.order_status_id = 1 THEN op.quantity ELSE 0 END) AS total_pending_qty,
            SUM(CASE WHEN o.order_status_id = 15 THEN op.quantity ELSE 0 END) AS total_accepted_qty,
            SUM(CASE WHEN o.order_status_id = 2 THEN op.quantity ELSE 0 END) AS total_in_process_qty,
            SUM(CASE WHEN o.order_status_id = 3 THEN op.quantity ELSE 0 END) AS total_picked_up_qty,
            SUM(CASE WHEN o.order_status_id = 5 THEN op.quantity ELSE 0 END) AS total_sold_qty,
            SUM(CASE WHEN o.order_status_id = 11 THEN op.quantity ELSE 0 END) AS total_returned_qty,
            SUM(CASE WHEN o.order_status_id = 8 THEN op.quantity ELSE 0 END) AS total_hold_by_customer_qty,
            SUM(CASE WHEN o.order_status_id = 7 THEN op.quantity ELSE 0 END) AS total_cancel_qty,
            SUM(CASE 
                WHEN o.order_status_id = 5 
                THEN (op.total - ((id.purchase_price + id.additional_cost) * op.quantity)) 
                ELSE 0 
            END) AS total_profit
        FROM " . DB_PREFIX . "inventory_details id 
        LEFT JOIN " . DB_PREFIX . "product_description pd ON (id.product_id = pd.product_id)
        LEFT JOIN " . DB_PREFIX . "order_product op ON (id.inventory_details_id = op.lot_id)
        LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id)
        WHERE id.inventory_id = '" . (int)$inventory_id . "' 
        AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        GROUP BY id.inventory_details_id";
    
        // --- Sorting Logic ---
        $sort_data = array(
            'product_name',
            'id.quantity',
            'id.current_quantity',
            'id.sale_price',
            'total_profit'
        );
    
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name"; // Default sort
        }
    
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
    
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getInventoryProducts1($inventory_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "inventory_details WHERE inventory_id = '" . (int)$inventory_id . "'");
        return $query->rows;
    }
    
    public function getInventoryProducts($inventory_id, $category_id = 0) {
        $sql = "SELECT id.* FROM " . DB_PREFIX . "inventory_details id";
        
        if ($category_id) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c 
                      ON (id.product_id = p2c.product_id)";
        }
        
        $sql .= " WHERE id.inventory_id = '" . (int)$inventory_id . "'";
        
        if ($category_id) {
            $sql .= " AND p2c.category_id = '" . (int)$category_id . "'";
        }
        
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    // Categories list এর জন্য
    public function getCategories() {
        $query = $this->db->query("SELECT category_id, name FROM " . DB_PREFIX . "category_description 
                                   WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                   ORDER BY name ASC");
        return $query->rows;
    }
    
    public function deleteInventory($inventory_id) {

        $products = $this->getInventoryProducts($inventory_id);

        foreach ($products as $product) {

            $this->db->query("UPDATE " . DB_PREFIX . "product
                SET quantity = (quantity - " . (int)$product['quantity'] . ")
                WHERE product_id = '" . (int)$product['product_id'] . "'");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "inventory_details
            WHERE inventory_id = '" . (int)$inventory_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "inventory
            WHERE inventory_id = '" . (int)$inventory_id . "'");
    }
    
    public function getTotalInventoriesByLotNumber($lot_number, $inventory_id = 0) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "inventory WHERE inventory_lotnumber = '" . $this->db->escape($lot_number) . "'";
        
        if ($inventory_id) {
            $sql .= " AND inventory_id <> '" . (int)$inventory_id . "'";
        }
        $query = $this->db->query($sql);
        return $query->row['total'];
    }
    
    public function getLowStockProducts($limit_qty = 5) {
        $sql = "SELECT p.product_id, pd.name, p.sku, p.quantity, p.image 
                FROM " . DB_PREFIX . "product p 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                AND p.quantity <= '" . (int)$limit_qty . "' 
                AND p.status = '1' 
                ORDER BY p.quantity ASC";
    
        $query = $this->db->query($sql);
    
        return $query->rows;
    }
    

    
    public function getProductLots($product_id) {
        // Join ac_inventory_details with ac_inventory to get the lot number
        // status = 1 ensures we only get 'Confirmed' lots
       $sql = "SELECT id.inventory_details_id as lot_id, i.inventory_lotnumber as lot_number, id.current_quantity, id.is_merge_lot_quantity_to_main 
        FROM " . DB_PREFIX . "inventory_details id 
        LEFT JOIN " . DB_PREFIX . "inventory i ON (id.inventory_id = i.inventory_id) 
        WHERE id.product_id = '" . (int)$product_id . "' 
        AND id.current_quantity > 0 
        AND i.status = '1'";
    
        $query = $this->db->query($sql);
    
        return $query->rows; // Returns all matching lots
    }
    
    public function mergeLotToProduct($data) {
        $lot_id = (int)$data['lot_id'];
        $product_id = (int)$data['product_id'];
        $quantity = (int)$data['quantity'];
    
        $query = $this->db->query("SELECT sale_price FROM " . DB_PREFIX . "inventory_details WHERE inventory_details_id = '" . $lot_id . "'");
        
        if ($query->num_rows) {
            $new_price = (float)$query->row['sale_price'];
            
            $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_merge_history SET 
                inventory_details_id = '" . $lot_id . "', 
                quantity = '" . $quantity . "', 
                sale_price = '" . $new_price . "', 
                merge_timestamp = NOW()");
    
            $this->db->query("UPDATE " . DB_PREFIX . "inventory_details 
                              SET is_merge_lot_quantity_to_main = '1' 
                              WHERE inventory_details_id = '" . $lot_id . "'");
    
            $this->db->query("UPDATE " . DB_PREFIX . "product 
                              SET quantity = (quantity + " . $quantity . "), 
                                  price = '" . $new_price . "' 
                              WHERE product_id = '" . $product_id . "'");
            
            return true;
        }
        
        return false;
    }
    
    public function getMergeHistory($data) {
    
       $sql = "SELECT h.*, i.inventory_lotnumber, id.*, pd.name AS product_name, p.sku
            FROM " . DB_PREFIX . "inventory_merge_history h
            LEFT JOIN " . DB_PREFIX . "inventory_details id ON (h.inventory_details_id = id.inventory_details_id)
            LEFT JOIN " . DB_PREFIX . "inventory i ON (id.inventory_id = i.inventory_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (id.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "product p ON (id.product_id = p.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
                
                    if (!empty($data['filter_product_name']) && empty($data['filter_product_id'])) {
                        $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_product_name']) . "%'";
                    }
                    
                    // Filter by SKU (partial match)
                    if (!empty($data['filter_sku'])) {
                        $sql .= " AND p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
                    }
                    
                    // Filter by lot number (partial match)
                    if (!empty($data['filter_lot_number'])) {
                        $sql .= " AND i.inventory_lotnumber LIKE '%" . $this->db->escape($data['filter_lot_number']) . "%'";
                    }
                    
                    // Filter by start date
                    if (!empty($data['filter_date_start'])) {
                        $sql .= " AND DATE(h.merge_timestamp) >= '" . $this->db->escape($data['filter_date_start']) . "'";
                    }
                    
                    // Filter by end date
                    if (!empty($data['filter_date_end'])) {
                        $sql .= " AND DATE(h.merge_timestamp) <= '" . $this->db->escape($data['filter_date_end']) . "'";
                    }
                 

                 $sort_data = array(
        'i.inventory_lotnumber',
        'product_name',
        'p.sku',
        'id.quantity',
        'id.current_quantity',
        'id.sale_price',
        'h.merge_timestamp'
    );

    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
        $sql .= " ORDER BY " . $data['sort'];
    } else {
        $sql .= " ORDER BY h.merge_timestamp";
    }

    if (isset($data['order']) && ($data['order'] == 'ASC')) {
        $sql .= " ASC";
    } else {
        $sql .= " DESC";
    }

    if (isset($data['start']) || isset($data['limit'])) {
        if ($data['start'] < 0) $data['start'] = 0;
        if ($data['limit'] < 1) $data['limit'] = 20;
        $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
    }

    $query = $this->db->query($sql);
    
        return $query->rows;
    }
    public function getTotalMergeHistory($data = array()) {
    $sql = "SELECT COUNT(*) AS total 
            FROM " . DB_PREFIX . "inventory_merge_history h
            LEFT JOIN " . DB_PREFIX . "inventory_details id ON (h.inventory_details_id = id.inventory_details_id)
            LEFT JOIN " . DB_PREFIX . "inventory i ON (id.inventory_id = i.inventory_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (id.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "product p ON (id.product_id = p.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
    
    // Filter by product ID (exact match)
    if (!empty($data['filter_product_id'])) {
        $sql .= " AND id.product_id = '" . (int)$data['filter_product_id'] . "'";
    }
    
    // Filter by product name (partial match)
    if (!empty($data['filter_product_name']) && empty($data['filter_product_id'])) {
        $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_product_name']) . "%'";
    }
    
    // Filter by SKU (partial match)
    if (!empty($data['filter_sku'])) {
        $sql .= " AND p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
    }
    
    // Filter by lot number (partial match)
    if (!empty($data['filter_lot_number'])) {
        $sql .= " AND i.inventory_lotnumber LIKE '%" . $this->db->escape($data['filter_lot_number']) . "%'";
    }
    
    // Filter by start date
    if (!empty($data['filter_date_start'])) {
        $sql .= " AND DATE(h.merge_timestamp) >= '" . $this->db->escape($data['filter_date_start']) . "'";
    }
    
    // Filter by end date
    if (!empty($data['filter_date_end'])) {
        $sql .= " AND DATE(h.merge_timestamp) <= '" . $this->db->escape($data['filter_date_end']) . "'";
    }
    
    $query = $this->db->query($sql);
    
    return $query->row['total'];
}

    public function getOrdersByLotId($lot_id) {
    $sql = "SELECT 
                o.order_id, 
                CONCAT(o.firstname, ' ', o.lastname) AS customer, 
                o.telephone, 
                op.name AS product_name, 
                op.quantity AS sold_qty, 
                op.total AS product_total_price, 
                o.total AS order_grand_total,    
                os.name AS status, 
                o.date_added,
                p.sku,                          
                id.purchase_price,               
                id.additional_cost,             
                id.sale_price AS lot_sale_price  
            FROM `" . DB_PREFIX . "order_product` op 
            INNER JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) 
            LEFT JOIN `" . DB_PREFIX . "order_status` os ON (o.order_status_id = os.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') 
            LEFT JOIN `" . DB_PREFIX . "product` p ON (op.product_id = p.product_id)
            LEFT JOIN `" . DB_PREFIX . "inventory_details` id ON (op.lot_id = id.inventory_details_id) 
            WHERE op.lot_id = '" . (int)$lot_id . "' 
            ORDER BY o.date_added DESC";
            
            // return $sql;

    $query = $this->db->query($sql);

    return $query->rows;
}

}