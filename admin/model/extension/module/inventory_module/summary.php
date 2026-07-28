<?php
class ModelExtensionModuleInventoryModuleSummary extends Model {
    
    /**
     * Get inventory summary statistics
     * মোট ইনভেন্টরি এন্ট্রি, বর্তমান স্টক এবং রিসিভড স্ট্যাটাস চেক
     */
    public function getInventorySummary() {
        $sql = "SELECT 
                    COUNT(inventory_id) as total_batches,
                    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as total_received,
                    (SELECT SUM(current_quantity) FROM " . DB_PREFIX . "inventory_details) as total_stock
                FROM " . DB_PREFIX . "inventory";
                
        $query = $this->db->query($sql);
        return $query->row;
    }
    
    /**
     * Get inventory summary by product
     */
    public function getInventorySummaryByProduct($product_id = null) {
        $sql = "SELECT pd.name, p.model, SUM(id.quantity) as initial_qty, SUM(id.current_quantity) as stock 
                FROM " . DB_PREFIX . "inventory_details id 
                LEFT JOIN " . DB_PREFIX . "product p ON (id.product_id = p.product_id) 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        
        if ($product_id) {
            $sql .= " AND id.product_id = '" . (int)$product_id . "'";
        }
        
        $sql .= " GROUP BY id.product_id";
        
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    /**
     * Get inventory summary by lot number
     */
    public function getInventorySummaryByLot($lot_number = null) {
        $sql = "SELECT i.inventory_lotnumber, pd.name, id.quantity, id.current_quantity, id.sale_price 
                FROM " . DB_PREFIX . "inventory_details id 
                JOIN " . DB_PREFIX . "inventory i ON (id.inventory_id = i.inventory_id) 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (id.product_id = pd.product_id) 
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        
        if ($lot_number) {
            $sql .= " AND i.inventory_lotnumber = '" . $this->db->escape($lot_number) . "'";
        }
        
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts($threshold = 10) {
        $sql = "SELECT pd.name, SUM(id.current_quantity) as total_stock 
                FROM " . DB_PREFIX . "inventory_details id 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (id.product_id = pd.product_id) 
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                GROUP BY id.product_id 
                HAVING total_stock <= '" . (int)$threshold . "'";
        
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    /**
     * Get inventory value summary
     * ইনভেন্টরির টোটাল ভ্যালু (Purchase Price এবং Sale Price এর ভিত্তিতে)
     */
    public function getInventoryValueSummary() {
        $sql = "SELECT 
                    SUM(current_quantity * purchase_price) as total_purchase_value, 
                    SUM(current_quantity * sale_price) as total_potential_revenue 
                FROM " . DB_PREFIX . "inventory_details";
        
        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get inventory by supplier
     */
    public function getInventoryBySupplier() {
        $sql = "SELECT s.name as supplier_name, COUNT(i.inventory_id) as total_shipments 
                FROM " . DB_PREFIX . "inventory_supplier s 
                LEFT JOIN " . DB_PREFIX . "inventory i ON (s.supplier_id = i.supplier_id) 
                GROUP BY s.supplier_id";
        
        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Get total inventory count (Detailed)
     */
    public function getTotalInventoryCount() {
        $sql = "SELECT SUM(quantity) as total_in, SUM(current_quantity) as total_available 
                FROM " . DB_PREFIX . "inventory_details";
        
        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * Get inventory turnover rate (Simplified based on merge history)
     * কতটুকু মাল স্টক থেকে আউট (Merge) হয়েছে তার সামারি
     */
    public function getInventoryTurnover($period = 'month') {
        $sql = "SELECT DATE(merge_timestamp) as date, SUM(quantity) as total_out 
                FROM " . DB_PREFIX . "inventory_merge_history 
                WHERE merge_timestamp >= DATE_SUB(NOW(), INTERVAL 1 " . strtoupper($period) . ") 
                GROUP BY DATE(merge_timestamp)";
        
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    /**
     * Get expiring products (Placeholder as expiry_date isn't in your current screenshots)
     * যদি inventory_date কে রেফারেন্স ধরেন
     */
    public function getExpiringProducts($days = 30) {
        // যেহেতু আপনার টেবিলে নির্দিষ্ট expiry_date কলাম নেই, 
        // তাই inventory_date থেকে হিসাব করা হলো (যদি এটি শেলফ লাইফ বুঝায়)
        $sql = "SELECT pd.name, i.inventory_lotnumber, i.inventory_date 
                FROM " . DB_PREFIX . "inventory_details id 
                JOIN " . DB_PREFIX . "inventory i ON (id.inventory_id = i.inventory_id) 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (id.product_id = pd.product_id) 
                WHERE i.inventory_date <= DATE_ADD(CURDATE(), INTERVAL " . (int)$days . " DAY) 
                AND id.current_quantity > 0";
        
        $query = $this->db->query($sql);
        return $query->rows;
    }
}