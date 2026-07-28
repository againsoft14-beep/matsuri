<?php
class ModelExtensionModuleInventoryModuleGift extends Model {
    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "gift_product (
            gift_product_id INT(11) NOT NULL AUTO_INCREMENT,
            gifted_name VARCHAR(255) NOT NULL,
            gift_date DATE NOT NULL,
            PRIMARY KEY (gift_product_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "gift_product_item (
            gift_product_item_id INT(11) NOT NULL AUTO_INCREMENT,
            gift_product_id INT(11) NOT NULL,
            product_id INT(11) NOT NULL,
            inventory_details_id INT(11) NOT NULL,
            quantity INT(11) NOT NULL,
            purchase_price DECIMAL(15,4) NOT NULL DEFAULT '0.0000',
            additional_cost DECIMAL(15,4) NOT NULL DEFAULT '0.0000',
            PRIMARY KEY (gift_product_item_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
    }

    public function addGift($data) {
   
        $this->db->query("INSERT INTO " . DB_PREFIX . "gift_product SET 
            gifted_name = '" . $this->db->escape($data['gifted_name']) . "',
            gift_date = '" . $this->db->escape($data['gift_date']) . "'
        ");

        $gift_product_id = $this->db->getLastId();

        // Resolve Expense Category "Gift Item"
        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "expense_category WHERE name = 'Gift Item' LIMIT 1");
        if ($query->num_rows) {
            $category_id = $query->row['category_id'];
        } else {
            $this->db->query("INSERT INTO " . DB_PREFIX . "expense_category SET name = 'Gift Item'");
            $category_id = $this->db->getLastId();
        }
        

        if (!empty($data['products'])) {
            foreach ($data['products'] as $product) {
                // Get lot info to confirm prices and lot existence
                $lot_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "inventory_details WHERE inventory_details_id = '" . (int)$product['inventory_details_id'] . "'");
                
                if ($lot_query->num_rows) {
                    $lot_info = $lot_query->row;
                    $purchase_price = $lot_info['purchase_price'];
                    $additional_cost = $lot_info['additional_cost'];
                    
                    $this->db->query("INSERT INTO " . DB_PREFIX . "gift_product_item SET 
                        gift_product_id = '" . (int)$gift_product_id . "',
                        product_id = '" . (int)$product['product_id'] . "',
                        inventory_details_id = '" . (int)$product['inventory_details_id'] . "',
                        quantity = '" . (int)$product['quantity'] . "',
                        purchase_price = '" . (float)$purchase_price . "',
                        additional_cost = '" . (float)$additional_cost . "'
                    ");

                    // DECREASE stock in product table
                    $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$product['quantity'] . ") WHERE product_id = '" . (int)$product['product_id'] . "'");

                    // DECREASE current_quantity in inventory_details (lot table)
                    $this->db->query("UPDATE " . DB_PREFIX . "inventory_details SET current_quantity = (current_quantity - " . (int)$product['quantity'] . ") WHERE inventory_details_id = '" . (int)$product['inventory_details_id'] . "'");

                    $total_expense = ((float)$purchase_price + (float)$additional_cost) * (int)$product['quantity'];

                    // Log Expense
                    $this->db->query("INSERT INTO " . DB_PREFIX . "expense SET 
                        category_id = '" . (int)$category_id . "',
                        title = '" . $this->db->escape('Product Gift: ' . $data['gifted_name'] . ' (Product ID: ' . $product['product_id'] . ')') . "',
                        amount = '" . (float)$total_expense . "',
                        expense_date = '" . $this->db->escape($data['gift_date']) . "',
                        note = '" . $this->db->escape('Auto-logged from Product Gift feature (Decrement)') . "'
                    ");
                }
            }
        }
        
        return $gift_product_id;
    }

    public function getGifts($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "gift_product WHERE 1=1";

        if (!empty($data['filter_gifted_name'])) {
            $sql .= " AND gifted_name LIKE '%" . $this->db->escape($data['filter_gifted_name']) . "%'";
        }

        $sql .= " ORDER BY gift_date DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) $data['start'] = 0;
            if ($data['limit'] < 1) $data['limit'] = 20;
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalGifts($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "gift_product WHERE 1=1";

        if (!empty($data['filter_gifted_name'])) {
            $sql .= " AND gifted_name LIKE '%" . $this->db->escape($data['filter_gifted_name']) . "%'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }
    
    public function getGiftItems($gift_product_id) {
        $query = $this->db->query("SELECT gpi.*, pd.name AS product_name, i.inventory_lotnumber FROM " . DB_PREFIX . "gift_product_item gpi LEFT JOIN " . DB_PREFIX . "product_description pd ON (gpi.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "inventory_details id ON (gpi.inventory_details_id = id.inventory_details_id) LEFT JOIN " . DB_PREFIX . "inventory i ON (id.inventory_id = i.inventory_id) WHERE gpi.gift_product_id = '" . (int)$gift_product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->rows;
    }

    public function getProductLots($product_id) {
        $query = $this->db->query("SELECT id.inventory_details_id, i.inventory_lotnumber, id.current_quantity, id.purchase_price, id.additional_cost FROM " . DB_PREFIX . "inventory_details id LEFT JOIN " . DB_PREFIX . "inventory i ON (id.inventory_id = i.inventory_id) WHERE id.product_id = '" . (int)$product_id . "' AND id.current_quantity > 0 AND i.status = 2 ORDER BY i.inventory_date DESC");
        return $query->rows;
    }
}
