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
	public function getProductItems($name)
	{
		$query = $this->db->query("SELECT p.product_id AS id, pd.name AS title FROM ".DB_PREFIX."product AS p LEFT JOIN ".DB_PREFIX."product_description AS pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '".(int)$this->config->get('config_language_id')."'".(!empty($name) ? " AND pd.name LIKE '%".$this->db->escape($name)."%'" : "")." GROUP BY p.product_id ORDER BY pd.name ASC LIMIT 0,5");

		return $query->rows;
	}
		
	public function getCategoryItems($name)
	{
		$query = $this->db->query("SELECT c.category_id AS id, cd.name AS title FROM ".DB_PREFIX."category AS c LEFT JOIN ".DB_PREFIX."category_description AS cd ON (cd.category_id = c.category_id) WHERE cd.language_id = '".(int)$this->config->get('config_language_id')."'".(!empty($name) ? " AND cd.name LIKE '%".$this->db->escape($name)."%'" : "")." GROUP BY c.category_id ORDER BY cd.name ASC LIMIT 0,5");

		return $query->rows;
	}
		
	public function getManufacturerItems($name)
	{
		$query = $this->db->query("SHOW TABLES LIKE '".DB_PREFIX."manufacturer_description'");
		
		if ($query->num_rows) {
			$query = $this->db->query("SELECT m.manufacturer_id AS id, md.name AS title FROM ".DB_PREFIX."manufacturer AS m LEFT JOIN ".DB_PREFIX."manufacturer_description AS md ON md.manufacturer_id = m.manufacturer_id WHERE md.language_id = '".(int)$this->config->get('config_language_id')."'".(!empty($name) ? " AND md.name LIKE '%".$this->db->escape($name)."%'" : "")." ORDER BY md.name ASC LIMIT 0,5");
		} else {		
			$query = $this->db->query("SELECT manufacturer_id AS id, name AS title FROM ".DB_PREFIX."manufacturer".(!empty($name) ? " WHERE name LIKE '%".$this->db->escape($name)."%'" : "")." ORDER BY name ASC LIMIT 0,5");
		}

		return $query->rows;
	}
		
	public function getInformationItems($name)
	{
		$query = $this->db->query("SELECT i.information_id AS id, id.title AS title FROM ".DB_PREFIX."information AS i LEFT JOIN ".DB_PREFIX."information_description AS id ON (id.information_id = i.information_id) WHERE id.language_id = '".(int)$this->config->get('config_language_id')."'".(!empty($name) ? " AND id.title LIKE '%".$this->db->escape($name)."%'" : "")." ORDER BY id.title ASC LIMIT 0,5");

		return $query->rows;
	}	
}

?>