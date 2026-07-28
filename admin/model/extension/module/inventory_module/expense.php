<?php
class ModelExtensionModuleInventoryModuleExpense extends Model {

    // Category
    public function addCategory($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "expense_category SET name = '" . $this->db->escape($data['name']) . "'");
    }

    public function editCategory($category_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "expense_category SET name = '" . $this->db->escape($data['name']) . "' WHERE category_id = '" . (int)$category_id . "'");
    }

    public function deleteCategory($id){
        $this->db->query("DELETE FROM ".DB_PREFIX."expense_category WHERE category_id='".(int)$id."'");
    }
    
    public function getCategory($category_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "expense_category WHERE category_id = '" . (int)$category_id . "'");
        return $query->row;
    }

    public function getCategories($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "expense_category";
    
        $sort_data = array('name');
    
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
        }
    
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
    
        $query = $this->db->query($sql);
        return $query->rows;
    }


    // Expense
    public function addExpense($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "expense SET 
        category_id = '" . (int)$data['category_id'] . "', 
        title = '" . $this->db->escape($data['title']) . "', 
        amount = '" . (float)$data['amount'] . "', 
        expense_date = '" . $this->db->escape($data['expense_date']) . "', 
        note = '" . $this->db->escape($data['note']) . "'");
    }

    public function editExpense($expense_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "expense SET 
            category_id = '" . (int)$data['category_id'] . "', 
            title = '" . $this->db->escape($data['title']) . "', 
            amount = '" . (float)$data['amount'] . "', 
            expense_date = '" . $this->db->escape($data['expense_date']) . "', 
            note = '" . $this->db->escape($data['note']) . "' 
            WHERE expense_id = '" . (int)$expense_id . "'");
    }

    public function deleteExpense($expense_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "expense WHERE expense_id = '" . (int)$expense_id . "'");
    }

    public function getExpenses($data = array()) {
        $sql = "SELECT e.*, ec.name AS category_name FROM " . DB_PREFIX . "expense e 
            LEFT JOIN " . DB_PREFIX . "expense_category ec ON (e.category_id = ec.category_id) 
            WHERE 1=1";

        if (!empty($data['filter_title'])) {
            $sql .= " AND e.title LIKE '%" . $this->db->escape($data['filter_title']) . "%'";
        }
        if (!empty($data['filter_category_id'])) {
            $sql .= " AND e.category_id = '" . (int)$data['filter_category_id'] . "'";
        }
    
        if (!empty($data['filter_date_range'])) {
            switch ($data['filter_date_range']) {
                case 'today':     $sql .= " AND DATE(expense_date) = CURDATE()"; break;
                case 'yesterday': $sql .= " AND DATE(expense_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"; break;
                case 'last_week': $sql .= " AND DATE(expense_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"; break;
                case 'last_month':$sql .= " AND DATE(expense_date) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)"; break;
                case 'last_90':   $sql .= " AND DATE(expense_date) >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)"; break;
                case 'last_year': $sql .= " AND DATE(expense_date) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)"; break;
                case 'custom':
                    if (!empty($data['filter_date_start'])) $sql .= " AND DATE(expense_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
                    if (!empty($data['filter_date_end']))   $sql .= " AND DATE(expense_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
                    break;
            }
        }
        
       $sort_data = array(
            'category_name',
            'e.title',
            'e.amount',
            'e.expense_date'
        );
    
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY e.expense_date";
        }
    
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
    
        // Limit logic...
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) $data['start'] = 0;
            if ($data['limit'] < 1) $data['limit'] = 20;
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getExpense($expense_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "expense WHERE expense_id = '" . (int)$expense_id . "'");
        return $query->row;
    }
    
    public function getTotalExpenses($data = array()) {
        $sql = "SELECT SUM(amount) AS total FROM " . DB_PREFIX . "expense WHERE 1=1";

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND category_id = '" . (int)$data['filter_category_id'] . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'] ? $query->row['total'] : 0;
    }
    
    
    public function addSalaryExpense($emp_name, $amount, $month_year) {
    $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "expense_category WHERE name = 'Salary' LIMIT 1");

    if ($query->num_rows) {
        $category_id = $query->row['category_id'];
    } else {
        $this->db->query("INSERT INTO " . DB_PREFIX . "expense_category SET name = 'Salary'");
        $category_id = $this->db->getLastId();
    }

    $this->db->query("INSERT INTO " . DB_PREFIX . "expense SET 
        category_id = '" . (int)$category_id . "', 
        title = '" . $this->db->escape('Salary Paid: ' . $emp_name . ' (' . $month_year . ')') . "', 
        amount = '" . (float)$amount . "', 
        expense_date = NOW(), 
        note = '" . $this->db->escape('Auto-generated from Payroll Module') . "'");
}
}
