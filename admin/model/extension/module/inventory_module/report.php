<?php
class ModelExtensionModuleInventoryModuleReport extends Model {
    
    // বর্তমান ইনভেন্টরি ভ্যালু (এটি সাধারণত ফিল্টার হয় না, লাইভ স্টক দেখায়)
    public function getTotalInventoryValue() {
        $query = $this->db->query("SELECT SUM(current_quantity * purchase_price) AS total_cost FROM " . DB_PREFIX . "inventory_details");
        return $query->row;
    }

    public function getStockStats($data = array()) {
        // ডেট ফিল্টার কন্ডিশন তৈরি
        $date_query = "";
        if (!empty($data['filter_date_start'])) {
            $date_query .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $date_query .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql = "SELECT 
            (SELECT SUM(op.total + op.tax) 
             FROM " . DB_PREFIX . "order_product op 
             INNER JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) 
             WHERE o.order_status_id = '5' " . $date_query . ") AS total_sales,

            (SELECT SUM(op.quantity * (id_inner.purchase_price + id_inner.additional_cost)) 
             FROM " . DB_PREFIX . "order_product op 
             INNER JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) 
             INNER JOIN " . DB_PREFIX . "inventory_details id_inner ON (op.lot_id = id_inner.inventory_details_id) 
             WHERE o.order_status_id = '5' " . $date_query . ") AS total_purchase_cost,

            (SELECT SUM((op.total + op.tax) - (op.quantity * (id_inner.purchase_price + id_inner.additional_cost))) 
             FROM " . DB_PREFIX . "order_product op 
             INNER JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) 
             INNER JOIN " . DB_PREFIX . "inventory_details id_inner ON (op.lot_id = id_inner.inventory_details_id) 
             WHERE o.order_status_id = '5' " . $date_query . ") AS total_profit,

            (SELECT COUNT(*) FROM " . DB_PREFIX . "inventory) AS total_lots_count, 
            (SELECT COUNT(DISTINCT product_id) FROM " . DB_PREFIX . "inventory_details) AS total_unique_products, 
            (SELECT SUM(current_quantity) FROM " . DB_PREFIX . "inventory_details) AS total_current_quantity,
            (SELECT SUM(CASE WHEN current_quantity <= 5 AND current_quantity > 0 THEN 1 ELSE 0 END) FROM " . DB_PREFIX . "inventory_details) AS low_stock_items,
            (SELECT COUNT(*) FROM " . DB_PREFIX . "product WHERE quantity <= 5 AND status = '1') AS low_stock_items,
            (SELECT COUNT(*) FROM " . DB_PREFIX . "inventory_details) AS total_items_count";

        $query = $this->db->query($sql);
        return $query->row;
    }

    public function getOverallProfitStats($data = array()) {
        $sql = "SELECT 
                SUM(op.total + op.tax) AS total_sales, 
                SUM(op.quantity * (id.purchase_price + id.additional_cost)) AS total_purchase,
                SUM((op.total + op.tax) - (op.quantity * (id.purchase_price + id.additional_cost))) AS total_profit 
                FROM " . DB_PREFIX . "order_product op
                INNER JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id)
                INNER JOIN " . DB_PREFIX . "inventory_details id ON (op.lot_id = id.inventory_details_id)
                WHERE o.order_status_id = '5'";

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);
        return ($query->row) ? $query->row : ['total_sales' => 0, 'total_purchase' => 0, 'total_profit' => 0];
    }

    public function getTopSellingProducts($limit = 5, $data = array()) {
        $sql = "SELECT 
                    pd.name AS name, 
                    SUM(op.quantity) AS total_sold, 
                    SUM(op.total + op.tax) AS total_revenue 
                FROM " . DB_PREFIX . "order_product op
                INNER JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (op.product_id = pd.product_id)
                WHERE o.order_status_id = '5' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " GROUP BY op.product_id ORDER BY total_sold DESC LIMIT " . (int)$limit;
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTopProfitableLots($limit = 5, $data = array()) {
        $sql = "SELECT 
                    i.inventory_lotnumber, 
                    i.inventory_date, 
                    SUM((op.total + op.tax) - (op.quantity * (id.purchase_price + id.additional_cost))) AS total_profit 
                FROM " . DB_PREFIX . "order_product op
                INNER JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id)
                INNER JOIN " . DB_PREFIX . "inventory_details id ON (op.lot_id = id.inventory_details_id)
                INNER JOIN " . DB_PREFIX . "inventory i ON (id.inventory_id = i.inventory_id)
                WHERE o.order_status_id = '5'";

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " GROUP BY id.inventory_id ORDER BY total_profit DESC LIMIT " . (int)$limit;
        $query = $this->db->query($sql);
        return $query->rows;
    }

    // --- Expenses Section ---
    public function getExpenses($data = array()) {
        $sql = "SELECT ec.name AS category, SUM(e.amount) AS total 
                FROM " . DB_PREFIX . "expense e 
                LEFT JOIN " . DB_PREFIX . "expense_category ec ON (e.category_id = ec.category_id) 
                WHERE 1=1";

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(e.expense_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(e.expense_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " GROUP BY e.category_id";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalExpenseAmount($data = array()) {
        $sql = "SELECT SUM(amount) AS total FROM " . DB_PREFIX . "expense WHERE 1=1";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(expense_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(expense_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        $query = $this->db->query($sql);
        return $query->row['total'] ? (float)$query->row['total'] : 0;
    }
    
    public function getExpensesByCategory($data = array()) {
    $sql = "SELECT ec.name AS category_name, SUM(e.amount) AS total_amount, COUNT(e.expense_id) AS total_count 
            FROM " . DB_PREFIX . "expense e 
            LEFT JOIN " . DB_PREFIX . "expense_category ec ON (e.category_id = ec.category_id) 
            WHERE 1=1";

    if (!empty($data['filter_date_start'])) {
        $sql .= " AND DATE(e.expense_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
    }

    if (!empty($data['filter_date_end'])) {
        $sql .= " AND DATE(e.expense_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
    }

    $sql .= " GROUP BY e.category_id ORDER BY total_amount DESC";
    
    $query = $this->db->query($sql);
    return $query->rows;
}

public function getTotalExpense($data = array()) {
    $sql = "SELECT SUM(amount) AS total FROM " . DB_PREFIX . "expense WHERE 1=1";
    
    if (!empty($data['filter_date_start'])) {
        $sql .= " AND DATE(expense_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
    }
    if (!empty($data['filter_date_end'])) {
        $sql .= " AND DATE(expense_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
    }
    
    $query = $this->db->query($sql);
    return $query->row['total'] ? $query->row['total'] : 0;
}
}