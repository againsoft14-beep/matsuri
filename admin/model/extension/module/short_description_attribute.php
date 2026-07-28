<?php
 class ModelExtensionModuleShortDescriptionAttribute extends Model {

    public function install() {
        $sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_short_description_attribute` (
                    `short_description_id` int(11) NOT NULL auto_increment,
                    `product_id` int(11) NOT NULL,
                    `language_id` int(4) default NULL,
                    `description` text collate utf8_bin  default NULL,
                    PRIMARY KEY  (`short_description_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3";
        $this->db->query($sql);
    }

    public function uninstall() {
        $sql = "DROP TABLE IF EXISTS `" . DB_PREFIX . "product_short_description_attribute`";
        $this->db->query($sql);
    }

    public function insert($product_id, $data) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_short_description_attribute WHERE product_id = '" . (int) $product_id . "'");

        foreach ($data['short_description_attribute'] as $language_id => $product_attribute_description) {
            
            foreach($product_attribute_description['description'] as $short_descritpon){
                if($short_descritpon){
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_short_description_attribute SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', description = '" .  $this->db->escape($short_descritpon) . "'");
                }
            }   
        }
    }
    
    public function getShortDescriptionAttributes($product_id) {
    
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_short_description_attribute WHERE product_id = '" . (int)$product_id . "'");

        return $query->rows;
    }

    public function get_desrc_product_copy($product_id) {
        $query = $this->db->query("SELECT *  FROM " . DB_PREFIX . "product_short_description_attribute WHERE product_id ='" . (int) $product_id . "'");
        $product_short_description_data = array();
        if ($query->rows) {
            foreach ($query->rows as $result) {
                $product_short_description_data[] = array(
                    'product_id' => $result['product_id'],
                    'language_id' => $result['language_id'],
                    'description' => $result['description']
                );
            }
        }
        return $product_short_description_data;
    }
}

?>