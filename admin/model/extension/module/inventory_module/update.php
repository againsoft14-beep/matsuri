<?php
class ModelExtensionModuleInventoryModuleUpdate extends Model {

    public function mergeLotToProduct($data) {
        $product_query = $this->db->query("SELECT quantity, stock_status_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$data['product_id'] . "'");
        
        
        $lot_id = (int)$data['lot_id'];
        $product_id = (int)$data['product_id'];
        $quantity = (int)$data['quantity'];
        

        $query = $this->db->query("SELECT sale_price FROM " . DB_PREFIX . "inventory_details WHERE inventory_details_id = '" . $lot_id . "'");
        
        if ($query->num_rows && $product_query->num_rows) {
            $new_price = (float)$query->row['sale_price'];
            
            $current_product_qty = (int)$product_query->row['quantity'];
            $status_update_sql = "";
            if ($current_product_qty <= 0) {
                $in_stock_status_id = (int)$this->config->get('config_stock_status_id');
                if ($in_stock_status_id) {
                    $status_update_sql = ", stock_status_id = '" . $in_stock_status_id . "'";
                }
            }
            
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
    
    // public function getProductLots($product_id) {
    //     // Explicitly select from ac_inventory_details and join ac_inventory
    //     // We filter for current_quantity > 0 to avoid showing empty lots
    //     $sql = "SELECT 
    //             id.inventory_details_id as lot_id, 
    //             i.inventory_lotnumber as lot_number, 
    //             id.current_quantity, 
    //             id.is_merge_lot_quantity_to_main,
    //             id.sale_price 
    //         FROM " . DB_PREFIX . "inventory_details id 
    //         INNER JOIN " . DB_PREFIX . "inventory i ON (id.inventory_id = i.inventory_id) 
    //         WHERE id.product_id = '" . (int)$product_id . "' 
    //         AND id.current_quantity > 0 
    //         AND i.status = '2'";
    
    //     $query = $this->db->query($sql);
    
    //     return $query->rows;
    // }
}
