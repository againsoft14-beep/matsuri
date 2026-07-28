<?php
class ModelExtensionModuleInventoryModulePayroll extends Model {

    public function install() {
        // Employee Table
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "pay_employees (
            emp_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            dob DATE,
            gender VARCHAR(10),
            designation VARCHAR(100),
            image VARCHAR(255),
            joining_date DATE,
            salary DECIMAL(10,2) NOT NULL,
            status TINYINT(1) DEFAULT '1'
        )");

        // Payroll Master Table
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "pay_payroll (
            payroll_id INT AUTO_INCREMENT PRIMARY KEY,
            payroll_created_date DATE,
            payroll_month INT(11),
            payroll_year INT(11),
            status TINYINT(1) DEFAULT '0' COMMENT '0 = Open, 1 = Close',
            created_by INT(11),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Payroll Details Table
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "pay_payroll_details (
            payroll_details_id INT AUTO_INCREMENT PRIMARY KEY,
            payroll_id INT(11),
            emp_id INT(11) NOT NULL,
            basic DECIMAL(10,2) NOT NULL,
            bonus DECIMAL(10,2) DEFAULT '0.00',
            deduction DECIMAL(10,2) DEFAULT '0.00',
            net_salary DECIMAL(10,2) NOT NULL,
            status TINYINT(1) DEFAULT '0' COMMENT '0 = Pending, 1 = Paid, 2 = Reject',
            date_paid DATE DEFAULT NULL
        )");
    }

    // --- PAYROLL MASTER METHODS ---

    public function getPayrolls($data = array()) {
        $sql = "SELECT p.*, 
                (SELECT SUM(pd.basic) FROM " . DB_PREFIX . "pay_payroll_details pd WHERE pd.payroll_id = p.payroll_id) AS basic, 
                (SELECT SUM(pd.net_salary) FROM " . DB_PREFIX . "pay_payroll_details pd WHERE pd.payroll_id = p.payroll_id) AS total_net_salary 
                FROM " . DB_PREFIX . "pay_payroll p";
    
        $sort_data = array(
            'month_year'       => 'p.payroll_year',
            'total_net_salary' => 'total_net_salary',
            'status'           => 'p.status'
        );
    
        if (isset($data['sort']) && array_key_exists($data['sort'], $sort_data)) {
            if ($data['sort'] == 'month_year') {
                $sql .= " ORDER BY p.payroll_year " . $data['order'] . ", p.payroll_month " . $data['order'];
            } else {
                $sql .= " ORDER BY " . $sort_data[$data['sort']] . " " . $data['order'];
            }
        } else {
            $sql .= " ORDER BY p.payroll_year DESC, p.payroll_month DESC";
        }
    
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    public function closePayrollBatch($payroll_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "pay_payroll SET status = '1' WHERE payroll_id = '" . (int)$payroll_id . "'");
    }

    public function generatePayroll($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "pay_payroll SET 
            payroll_month = '" . (int)$data['month'] . "', 
            payroll_year = '" . (int)$data['year'] . "', 
            status = '0', 
            payroll_created_date = NOW()");
    
        $payroll_id = $this->db->getLastId();
    
        if (isset($data['payroll_data'])) {
            foreach ($data['payroll_data'] as $emp) {
                if (isset($emp['include'])) {
                    $basic     = (float)$emp['basic'];
                    $bonus     = (float)$emp['bonus'];
                    $deduction = (float)$emp['deduction'];
                    $net       = ($basic + $bonus) - $deduction;
    
                    $this->db->query("INSERT INTO " . DB_PREFIX . "pay_payroll_details SET 
                        payroll_id = '" . (int)$payroll_id . "', 
                        emp_id     = '" . (int)$emp['emp_id'] . "', 
                        basic      = '" . $basic . "', 
                        bonus      = '" . $bonus . "', 
                        deduction  = '" . $deduction . "', 
                        net_salary = '" . $net . "', 
                        status     = '0'");
                }
            }
        }
        return $payroll_id;
    }
    
    public function checkPayrollExists($month, $year) {
        $query = $this->db->query("SELECT payroll_id FROM " . DB_PREFIX . "pay_payroll WHERE payroll_month = '" . (int)$month . "' AND payroll_year = '" . (int)$year . "' LIMIT 1");
    
        if ($query->num_rows) {
            return true; 
        }
        
        return false;
    }

    // --- PAYROLL DETAILS METHODS ---

    public function getPayrollDetails($payroll_id) {
        $query = $this->db->query("SELECT pd.*, e.name AS employee_name 
            FROM " . DB_PREFIX . "pay_payroll_details pd 
            LEFT JOIN " . DB_PREFIX . "pay_employees e ON (pd.emp_id = e.emp_id) 
            WHERE pd.payroll_id = '" . (int)$payroll_id . "'");
        return $query->rows;
    }

    public function getPayrollDetailWithInfo($payroll_details_id) {
        $query = $this->db->query("SELECT pd.*, e.name AS employee_name 
            FROM " . DB_PREFIX . "pay_payroll_details pd 
            LEFT JOIN " . DB_PREFIX . "pay_employees e ON (pd.emp_id = e.emp_id) 
            WHERE pd.payroll_details_id = '" . (int)$payroll_details_id . "'");
        return $query->row;
    }

    public function updateSalary($payroll_details_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "pay_payroll_details SET 
            bonus = '" . (float)$data['bonus'] . "', 
            deduction = '" . (float)$data['deduction'] . "', 
            net_salary = '" . (float)$data['net_salary'] . "' 
            WHERE payroll_details_id = '" . (int)$payroll_details_id . "' AND status = '0'");
    }

    public function markPaid($payroll_details_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "pay_payroll_details SET status = '1', date_paid = NOW() WHERE payroll_details_id = '" . (int)$payroll_details_id . "'");
        
        $query = $this->db->query("SELECT payroll_id FROM " . DB_PREFIX . "pay_payroll_details WHERE payroll_details_id = '" . (int)$payroll_details_id . "'");
    
    if ($query->num_rows) {
        $payroll_id = $query->row['payroll_id'];

        $check_pending = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "pay_payroll_details WHERE payroll_id = '" . (int)$payroll_id . "' AND status = '0'");

        if ($check_pending->row['total'] == 0) {
            $this->db->query("UPDATE " . DB_PREFIX . "pay_payroll SET status = '1' WHERE payroll_id = '" . (int)$payroll_id . "'");
        }
    }
        
        // Expense Integration Logic
        $payroll_info = $this->getPayrollDetailWithInfo($payroll_details_id);
        
        if ($payroll_info) {
            $cat_query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "expense_category WHERE name = 'Salary' LIMIT 1");
            $category_id = $cat_query->num_rows ? $cat_query->row['category_id'] : $this->createExpenseCategory('Salary');

            $title = "Salary Paid - " . $payroll_info['employee_name'];
            
            $this->db->query("INSERT INTO " . DB_PREFIX . "expense SET 
                category_id = '" . (int)$category_id . "', 
                title = '" . $this->db->escape($title) . "', 
                amount = '" . (float)$payroll_info['net_salary'] . "', 
                expense_date = NOW(), 
                note = 'Payroll Payment processed via System'");
        }
    }

    private function createExpenseCategory($name) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "expense_category SET name = '" . $this->db->escape($name) . "'");
        return $this->db->getLastId();
    }

    public function checkPaidStatus($payroll_details_id) {
        return $this->db->query("SELECT status FROM " . DB_PREFIX . "pay_payroll_details WHERE payroll_details_id = '" . (int)$payroll_details_id . "'");
    }

    // --- EMPLOYEE METHODS ---

    public function getEmployees($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "pay_employees";
    
        $sort_data = array(
            'name',
            'designation',
            'email',
            'phone',
            'salary',
            'status'
        );
    
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

    public function getActiveEmployees() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "pay_employees WHERE status = '1'");
        return $query->rows;
    }

    public function getEmployee($emp_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "pay_employees WHERE emp_id = '" . (int)$emp_id . "'");
        return $query->row;
    }

    public function addEmployee($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "pay_employees SET 
            name = '" . $this->db->escape($data['name']) . "', 
            email = '" . $this->db->escape($data['email']) . "', 
            phone = '" . $this->db->escape($data['phone']) . "', 
            dob = '" . $this->db->escape($data['dob']) . "', 
            gender = '" . $this->db->escape($data['gender']) . "', 
            designation = '" . $this->db->escape($data['designation']) . "', 
            image = '" . $this->db->escape($data['image']) . "', 
            joining_date = '" . $this->db->escape($data['joining_date']) . "', 
            salary = '" . (float)$data['salary'] . "', 
            status = '" . (int)$data['status'] . "'");
    }

    public function editEmployee($emp_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "pay_employees SET 
            name = '" . $this->db->escape($data['name']) . "', 
            email = '" . $this->db->escape($data['email']) . "', 
            phone = '" . $this->db->escape($data['phone']) . "', 
            dob = '" . $this->db->escape($data['dob']) . "', 
            gender = '" . $this->db->escape($data['gender']) . "', 
            designation = '" . $this->db->escape($data['designation']) . "', 
            image = '" . $this->db->escape($data['image']) . "', 
            joining_date = '" . $this->db->escape($data['joining_date']) . "', 
            salary = '" . (float)$data['salary'] . "', 
            status = '" . (int)$data['status'] . "' 
            WHERE emp_id = '" . (int)$emp_id . "'");
    }

    public function deleteEmployee($id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "pay_employees WHERE emp_id = '" . (int)$id . "'");
    }
}