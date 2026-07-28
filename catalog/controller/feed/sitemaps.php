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

class ControllerFeedSitemaps extends Controller
{
	private $folder = 'feed';
	private $extension = 'sitemaps';
	private $fieldbase = '';
	private $baseurl = '';
	private $root = '';
	private $filename = '';
	private $limit = 0;
	
	public function __construct($registry)
	{
		parent::__construct($registry);
		
		if (version_compare(VERSION, '2.3', '>=')) $this->path = 'extension/';
		
		$this->load->language($this->path.$this->folder.'/'.$this->extension);
		
		$this->fieldbase = (version_compare(VERSION, '3.0', '>=') ? $this->folder.'_' : '').$this->extension;		
	}
				
	public function index()
	{
   		if (!$this->config->get($this->fieldbase.'_license')) return false;
   		
		if ($this->config->get($this->fieldbase.'_status')) {
			$this->load->model($this->folder.'/'.$this->extension);
			
			if (!empty($_GET['lang'])) {
				$lang = $_GET['lang'];
				
				$this->load->model('localisation/language');
				$languages = $this->model_localisation_language->getLanguages();

				if (isset($languages[$lang])) {
					$language = $languages[$lang];
					$this->config->set('config_language_id', $language['language_id']);
					$this->session->data['language'] = $lang;
				}
			}

			if ($this->request->server['HTTPS']) {
				$this->baseurl = ($this->config->get('config_ssl') ? $this->config->get('config_ssl') : HTTPS_SERVER);
			} else {
				$this->baseurl = ($this->config->get('config_url') ? $this->config->get('config_url') : HTTP_SERVER);
			}
			
			$this->baseurl = rtrim($this->baseurl, '/');
			
			$this->root = str_replace('/system', '', DIR_SYSTEM);
			$this->filename = 'sitemap'.($this->config->get('config_store_id') ? '-'.$this->config->get('config_store_id') : '').(!empty($lang) ? '-'.$lang : '').'.xml';

			$this->limit = $this->config->get($this->fieldbase.'_limit');
		
			if (!$this->limit || ($this->limit > 50000)) $this->limit = 50000;

			$xml = new DOMDocument('1.0', 'UTF-8');			
			$xml->formatOutput = true;
  			$xml->preserveWhiteSpace = false;

			$xsl = $xml->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="'.$this->baseurl.'/sitemaps/xsl/sitemapindex.xsl"');
			$xml->appendChild($xsl);

			$sitemapindex = $xml->createElement('sitemapindex');
			$sitemapindex->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
			$sitemapindex->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
			$sitemapindex->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd');
			
			$xml->appendChild($sitemapindex);

			if ($this->config->get($this->fieldbase.'_product_sitemap')) {
				$this->processItems($xml, 'product');
			}

			if ($this->config->get($this->fieldbase.'_category_sitemap')) {
				$this->processItems($xml, 'category');
			}
			
			if ($this->config->get($this->fieldbase.'_manufacturer_sitemap')) {
				$this->processItems($xml, 'manufacturer');
			}

			if ($this->config->get($this->fieldbase.'_information_sitemap')) {
				$this->processItems($xml, 'information');
			}

			$xml->save($this->root.$this->filename);

			$this->response->addHeader('Content-Type: application/xml');
			$this->response->setOutput($xml->saveXML());				
		} else {
			echo $this->language->get('error_disabled');
		}
	}

	private function processItems(&$xml, $type)
	{
		$items = $this->{'model_'.$this->folder.'_'.$this->extension}->{'get'.ucfirst($type).'Items'}(array('ids' => $this->config->get($this->fieldbase.'_'.$type.'_items')));
			
		$files = ceil(count($items)/$this->limit);
				
		if ($files > 1) {
			$parts = array_chunk($items, $this->limit);
					
			foreach ($parts as $key => $part) {
				$key ++;
				$this->addSitemap($xml, $type.'-'.$key);
				$this->createSitemap($type.'-'.$key, $part);
			}
		} else {
			$this->addSitemap($xml, $type);
			$this->createSitemap($type, $items);
		}
	}

	private function addSitemap(&$xml, $type)
	{
		$sitemapindex = $xml->getElementsByTagName('sitemapindex')->item(0);
		
		$sitemap = $xml->createElement('sitemap');
		$sitemapindex->appendChild($sitemap);
				
		$loc = $xml->createElement('loc', $this->baseurl.'/sitemaps/'.$type.'-'.$this->filename);
		$sitemap->appendChild($loc);
				
		$lastmod = $xml->createElement('lastmod', date(DateTime::RFC3339, time()));
		$sitemap->appendChild($lastmod);
	}
	
	private function createSitemap($type, $items)
	{	
		$xml = new DOMDocument('1.0', 'UTF-8');
		$xml->formatOutput = true;
  		$xml->preserveWhiteSpace = false;

		$xsl = $xml->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="'.$this->baseurl.'/sitemaps/xsl/sitemap.xsl"');
		$xml->appendChild($xsl);

		$urlset = $xml->createElement('urlset');
		$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		
		if ($this->config->get($this->fieldbase.'_image_sitemap')) {
			$urlset->setAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
		}
		
		$xml->appendChild($urlset);
		
		$name = explode('-', $type);
		
		$this->{'get'.reset($name).'Sitemap'}($xml, $items);

		$xml->save($this->root.'sitemaps/'.$type.'-'.$this->filename);
	}
	
	private function createNode(&$xml, $item)
	{
		$urlset = $xml->getElementsByTagName('urlset')->item(0);
		
		$url = $xml->createElement('url');
		$urlset->appendChild($url);
		
		$loc = $xml->createElement('loc', $item['link']);
		$url->appendChild($loc);
		
		$changefreq = $xml->createElement('changefreq', $this->config->get($this->fieldbase.'_'.$item['type'].'_frequency'));
		$url->appendChild($changefreq);
		
		$priority = $xml->createElement('priority', $this->config->get($this->fieldbase.'_'.$item['type'].'_priority'));
		$url->appendChild($priority);
		
		if (!empty($item['date_modified'])) {
			$lastmod = $xml->createElement('lastmod', date(DateTime::RFC3339, strtotime($item['date_modified'])));
			$url->appendChild($lastmod);
		}
				
		if ($this->config->get($this->fieldbase.'_image_sitemap') && !empty($item['images'])) {
			$this->load->model('tool/image');
			
			foreach ($item['images'] as $row) {
				if ($row['image']) {
					$file = pathinfo($row['image'], PATHINFO_BASENAME);
				
					$image = $xml->createElement('image:image');
					$url->appendChild($image);

					if ($this->config->get($this->fieldbase.'_image_resize')) {
						$location = $this->model_tool_image->resize($row['image'], $this->config->get($this->fieldbase.'_image_height'), $this->config->get($this->fieldbase.'_image_width'));
					} else {
						$location = $this->baseurl.'/image/'.$row['image'];
					}

					$imageloc = $xml->createElement('image:loc', $location);
					$image->appendChild($imageloc);
					
					if ($this->config->get($this->fieldbase.'_image_title') && !empty($item['name'])) {
						$imagetitle = $xml->createElement('image:title', htmlspecialchars($item['name'], ENT_XML1, 'UTF-8'));
						$image->appendChild($imagetitle);
					}
				
					if ($this->config->get($this->fieldbase.'_image_caption') && !empty($item['description'])) {
						$limit = $this->config->get($this->fieldbase.'_image_caption_limit') - mb_strlen($file);
						$caption = trim(strip_tags(html_entity_decode($item['description'], ENT_COMPAT, 'UTF-8')));
						
						if ($caption) {
							$caption = mb_substr($caption, 0, $limit)." (".$file.")";
							$imagecaption = $xml->createElement('image:caption');
							$image->appendChild($imagecaption);
							$imagecaption->appendChild($xml->createCDATASection(htmlentities($caption, ENT_XML1, 'UTF-8')));
						}
					}
				}
			}
		}
	}

	private function getProductSitemap(&$xml, $products)
	{
		foreach ($products as $product) {
			$product['type'] = "product";
			$product['name'] = trim($product['name']);
			$product['description'] = (!empty($product['description']) ? trim($product['description']) : trim($product['name']));
			$product['link'] = $this->url->link('product/product', 'product_id='.$product['product_id']);
			$product['images'] = $this->{'model_'.$this->folder.'_'.$this->extension}->getProductImages($product['product_id']);
			$product['images'][] = array('image' => $product['image']);
					
			$this->createNode($xml, $product);
		}
	}

	private function getCategorySitemap(&$xml, $categories, $path = '')
	{	
		foreach ($categories as $category) {
			$newpath = ($path ? $path.'_' : '').$category['category_id'];
				
			$category['type'] = "category";
			$category['name'] = trim($category['name']);
			$category['description'] = (!empty($category['description']) ? trim($category['description']) : trim($category['name']));
			$category['link'] = $this->url->link('product/category', 'path='.$newpath);
			$category['images'] = array(array('image' => $category['image']));
					
			$this->createNode($xml, $category);

			if ($this->config->get($this->fieldbase.'_category_products')) {
				$products = $this->{'model_'.$this->folder.'_'.$this->extension}->getProductItems(array(
					'ids' => $this->config->get($this->fieldbase.'_product_items'),
					'category' => $category['category_id']));

				foreach ($products as $product) {
					$product['type'] = "category";
					$product['name'] = trim($product['name']);
					$product['description'] = (!empty($product['description']) ? trim($product['description']) : trim($product['name']));
					$product['link'] = $this->url->link('product/product', 'path='.$newpath.'&product_id='.$product['product_id']);
								
					$this->createNode($xml, $product);
				}
			}

			$siblings = $this->{'model_'.$this->folder.'_'.$this->extension}->getCategoryItems(array(
				'ids' => $this->config->get($this->fieldbase.'_category_items'),
				'parent' => $category['category_id']));
			
			$this->getCategorySitemap($xml, $siblings, $newpath);
		}
	}
	
	private function getManufacturerSitemap(&$xml, $manufacturers)
	{		
		foreach ($manufacturers as $manufacturer) {
			$manufacturer['type'] = "manufacturer";
			$manufacturer['name'] = trim($manufacturer['name']);
			$manufacturer['description'] = (!empty($manufacturer['description']) ? trim($manufacturer['description']) : trim($manufacturer['name']));
			$manufacturer['link'] = $this->url->link('product/manufacturer/info', 'manufacturer_id='.$manufacturer['manufacturer_id']);
			$manufacturer['images'] = array(array('image' => $manufacturer['image']));
			
			$this->createNode($xml, $manufacturer);

			if ($this->config->get($this->fieldbase.'_manufacturer_products')) {
				$products = $this->{'model_'.$this->folder.'_'.$this->extension}->getProductItems(array(
					'ids' => $this->config->get($this->fieldbase.'_product_items'),
					'manufacturer' => $manufacturer['manufacturer_id']));
						
				foreach ($products as $product) {
					$product['type'] = "manufacturer";
					$product['name'] = trim($product['name']);
					$product['description'] = (!empty($product['description']) ? trim($product['description']) : trim($product['name']));
					$product['link'] = $this->url->link('product/product', 'manufacturer_id='.$manufacturer['manufacturer_id'].'&product_id='.$product['product_id']);
								
					$this->createNode($xml, $product);
				}
			}
		}
	}
		
	private function getInformationSitemap(&$xml, $informations)
	{
		foreach ($informations as $information) {
			$information['type'] = "information";
			$information['name'] = trim($information['title']);
			$information['description'] = (!empty($information['description']) ? trim($information['description']) : trim($information['name']));
			$information['link'] = $this->url->link('information/information', 'information_id='.$information['information_id']);
					
			$this->createNode($xml, $information);
		}
	}
}

?>