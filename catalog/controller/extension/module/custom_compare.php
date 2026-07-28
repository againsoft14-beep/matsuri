<?php

class ControllerExtensionModuleCustomCompare extends Controller {

    private $error = array();

    public function __construct($registry) {
        parent::__construct($registry);
    }

    public function index() {
        $this->load->model('catalog/product');
        
        $results = $this->model_catalog_product->getProducts();
        
        $this->document->addStyle('catalog/view/javascript/ui/jquery-ui.css');
        $this->document->addScript('catalog/view/javascript/ui/jquery-ui.js');
        
        $data['products'] = array();
        foreach ($results as $result) {
            
            $data['products'][] = array(
                'product_id'  => $result['product_id'],
                'name'        => $result['name']
            );
        }
        return $this->load->view('extension/module/custom_compare', $data);
    }
    
    public function autocomplete() {
    
        $json = array();

        if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_model'])) {
            
            
            $this->load->model('catalog/product');
            
            
            if (isset($this->request->get['filter_name'])) {
                $filter_name = $this->request->get['filter_name'];
            } else {
                $filter_name = '';
            }

            if (isset($this->request->get['filter_model'])) {
                $filter_model = $this->request->get['filter_model'];
            } else {
                $filter_model = '';
            }

            if (isset($this->request->get['limit'])) {
                $limit = (int)$this->request->get['limit'];
            } else {
                $limit = 5;
            }

            $filter_data = array(
                'filter_name'  => $filter_name,
                'filter_model' => $filter_model,
                'start'        => 0,
                'limit'        => $limit
            );

            $results = $this->model_catalog_product->getProducts($filter_data);
            
            

            foreach ($results as $result) {
            
                $json[] = array(
                    'product_id' => $result['product_id'],
                    'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'model'      => $result['model'],
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function custom() {
        
        $this->session->data['compare'] = array();
        
        if (isset($this->request->post['first_product_id'])) {
            $first_product_id = $this->request->post['first_product_id'];
        } else {
            $first_product_id = 0;
        }
        
        if (isset($this->request->post['second_product_id'])) {
            $second_product_id = $this->request->post['second_product_id'];
        } else {
            $second_product_id = 0;
        }
        
        if (isset($first_product_id) && isset($second_product_id)) {
            
            unset($this->session->data['compare']);

            $this->session->data['compare'][] = $first_product_id;
            $this->session->data['compare'][] = $second_product_id;
            
            $this->response->redirect($this->url->link('product/compare'));
        }
    }

}