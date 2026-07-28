<?php
 class ModelExtensionModuleShortDescriptionAttribute extends Model {

    public function getShortDescriptionAttributes($product_id) {
    
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_short_description_attribute WHERE product_id = '" . (int)$product_id . "'");

        return $query->rows;
    }

}

?>