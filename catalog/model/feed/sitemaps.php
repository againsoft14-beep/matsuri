<?php
/*------------------------------------------------------------------------
# Smart Sitemap
# ------------------------------------------------------------------------
# The Krotek
# Copyright (C) 2011-2021 The Krotek. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: https://thekrotek.com
# Support: support@thekrotek.com
-------------------------------------------------------------------------*/

class ModelFeedSitemaps extends Model
{
	public function getProductItems($data)
	{
		$query = $this->db->query("SELECT p.product_id, p.image, p.date_modified, pd.name, pd.description FROM ".DB_PREFIX."product AS p LEFT JOIN ".DB_PREFIX."product_description AS pd ON pd.product_id = p.product_id LEFT JOIN ".DB_PREFIX."product_to_store AS p2s ON p2s.product_id = p.product_id LEFT JOIN ".DB_PREFIX."product_to_category AS p2c ON p2c.product_id = p.product_id WHERE pd.language_id = '".(int)$this->config->get('config_language_id')."' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '".(int)$this->config->get('config_store_id')."'".(!empty($data['category']) ? " AND p2c.category_id = '".(int)$data['category']."'" : "").(!empty($data['manufacturer']) ? " AND p.manufacturer_id = '".(int)$data['manufacturer']."'" : "").(!empty($data['ids']) ? " AND p.product_id IN (".implode(', ', $data['ids']).")" : "")." GROUP BY p.product_id ORDER BY LCASE(pd.name) ASC");

		return $query->rows;
	}

	public function getProductImages($id)
	{
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."product_image WHERE product_id = '".(int)$id."' ORDER BY sort_order ASC");

		return $query->rows;
	}
	
	public function getCategoryItems($data)
	{
		$query = $this->db->query("SELECT c.category_id, c.image, c.date_modified, cd.name, cd.description FROM ".DB_PREFIX."category AS c LEFT JOIN ".DB_PREFIX."category_description AS cd ON cd.category_id = c.category_id LEFT JOIN ".DB_PREFIX."category_to_store AS c2s ON c2s.category_id = c.category_id WHERE c.parent_id = '".(!empty($data['parent']) ? (int)$data['parent'] : '0')."' AND cd.language_id = '".(int)$this->config->get('config_language_id')."' AND c2s.store_id = '".(int)$this->config->get('config_store_id')."' AND c.status = '1'".(!empty($data['ids']) ? " AND c.category_id IN (".implode(', ', $data['ids']).")" : "")." GROUP BY c.category_id ORDER BY LCASE(cd.name) ASC");

		return $query->rows;
	}

	public function getManufacturerItems($data)
	{
		$query = $this->db->query("SHOW TABLES LIKE '".DB_PREFIX."manufacturer_description'");
		
		if ($query->num_rows) {
			$query = $this->db->query("SELECT m.manufacturer_id, m.image, md.name, md.description FROM ".DB_PREFIX."manufacturer AS m LEFT JOIN ".DB_PREFIX."manufacturer_description AS md ON md.manufacturer_id = m.manufacturer_id LEFT JOIN ".DB_PREFIX."manufacturer_to_store AS m2s ON (m2s.manufacturer_id = m.manufacturer_id) WHERE md.language_id = '".(int)$this->config->get('config_language_id')."' AND m2s.store_id = '".(int)$this->config->get('config_store_id')."'".(!empty($data['ids']) ? " AND m.manufacturer_id IN (".implode(', ', $data['ids']).")" : "")." GROUP BY m.manufacturer_id ORDER BY LCASE(md.name) ASC");	
		} else {
			$query = $this->db->query("SELECT m.manufacturer_id, m.name, m.image FROM ".DB_PREFIX."manufacturer AS m LEFT JOIN ".DB_PREFIX."manufacturer_to_store AS m2s ON m2s.manufacturer_id = m.manufacturer_id WHERE m2s.store_id = '".(int)$this->config->get('config_store_id')."'".(!empty($data['ids']) ? " AND m.manufacturer_id IN (".implode(', ', $data['ids']).")" : "")." GROUP BY m.manufacturer_id ORDER BY LCASE(m.name) ASC");
		}

		return $query->rows;
	}

	public function getInformationItems($data) 
	{
		$query = $this->db->query("SELECT i.information_id, id.title FROM ".DB_PREFIX."information AS i LEFT JOIN ".DB_PREFIX."information_description AS id ON id.information_id = i.information_id LEFT JOIN ".DB_PREFIX."information_to_store AS i2s ON i2s.information_id = i.information_id WHERE id.language_id = '".(int)$this->config->get('config_language_id')."' AND i2s.store_id = '".(int)$this->config->get('config_store_id')."' AND i.status = '1'".(!empty($data['ids']) ? " AND i.information_id IN (".implode(', ', $data['ids']).")" : "")." GROUP BY i.information_id ORDER BY LCASE(id.title) ASC");

		return $query->rows;
	}	
}

?>