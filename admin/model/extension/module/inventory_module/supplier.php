<?php
class ModelExtensionModuleInventoryModuleSupplier extends Model {
    
    public function addSupplier($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_supplier SET 
            name = '" . $this->db->escape($data['name']) . "', 
            phone = '" . $this->db->escape($data['phone']) . "', 
            address = '" . $this->db->escape($data['address']) . "', 
            status = '" . (isset($data['status']) ? (int)$data['status'] : 1) . "'");

        $supplier_id = $this->db->getLastId();

        return $supplier_id;
    }

    public function editSupplier($supplier_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "inventory_supplier SET 
            name = '" . $this->db->escape($data['name']) . "', 
            phone = '" . $this->db->escape($data['phone']) . "', 
            address = '" . $this->db->escape($data['address']) . "', 
            status = '" . (isset($data['status']) ? (int)$data['status'] : 1) . "'
            WHERE supplier_id = '" . (int)$supplier_id . "'");
    }

    public function deleteSupplier($supplier_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "inventory_supplier WHERE supplier_id = '" . (int)$supplier_id . "'");
    }

    public function getSupplier($supplier_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "inventory_supplier WHERE supplier_id = '" . (int)$supplier_id . "'");

        return $query->row;
    }

    public function getSuppliers($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "inventory_supplier";

        $implode = array();
        if (!empty($data['filter_name'])) {
            $implode[] = "name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $implode[] = "status = '" . (int)$data['filter_status'] . "'";
        }
    
        if ($implode) {
            $sql .= " WHERE " . implode(" AND ", $implode);
        }
    
        $sort_data = array(
            'name',
            'phone',  
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

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalSuppliers() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "inventory_supplier");

        return $query->row['total'];
    }
}