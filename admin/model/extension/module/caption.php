<?php

class ModelExtensionModuleCaption extends Model {

    public function install() {
        // Check if 'caption' column exists in 'category_description' table
        $column_exist = $this->db->query(
            "SELECT column_name 
            FROM information_schema.columns 
            WHERE table_schema='" . DB_DATABASE . "' 
            AND table_name='" . DB_PREFIX . "category_description' 
            AND column_name='caption'"
        )->rows;

        // If the column does not exist, add it
        if (empty($column_exist)) {
            $this->db->query("ALTER TABLE " . DB_PREFIX . "category_description ADD caption VARCHAR(200) DEFAULT NULL");
        }

        // Check if 'caption' column exists in 'product_description' table
        $column_exist = $this->db->query(
            "SELECT column_name 
            FROM information_schema.columns 
            WHERE table_schema='" . DB_DATABASE . "' 
            AND table_name='" . DB_PREFIX . "product_description' 
            AND column_name='caption'"
        )->rows;

        // If the column does not exist, add it
        if (empty($column_exist)) {
            $this->db->query("ALTER TABLE " . DB_PREFIX . "product_description ADD caption VARCHAR(255) DEFAULT NULL");
        }
    }
    
    public function uninstall() {
        $sql = "ALTER TABLE " . DB_PREFIX . "category_description DROP COLUMN caption";
        $this->db->query($sql);

        $sql = "ALTER TABLE " . DB_PREFIX . "product_description DROP COLUMN caption";
        $this->db->query($sql);

    }
}
