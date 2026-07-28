<?php

class ControllerExtensionModuleShortDescriptionAttribute extends Controller {

    private $error = array();

    public function __construct($registry) {
        parent::__construct($registry);
    }

    public function index() {
        if (isset($this->request->get['product_id'])) {
            $product_id = $this->request->get['product_id'];
            $short_description_config = $this->config->get('short_description_attribute'); 
            if(isset($short_description_config['status']) && $short_description_config['status'] == 1 ) {

                $this->load->model('extension/module/short_description_attribute');
                
                $product_attributes_list = $this->model_extension_module_short_description_attribute->getShortDescriptionAttributes($product_id);
                $description = array();
                foreach($product_attributes_list as $row){
                    if($row['description']){
                        $description[] = html_entity_decode($row['description'], ENT_QUOTES, 'UTF-8');
                    }
                }
                $data['product_attributes'] = $description;

                return $this->load->view('extension/module/short_description_attribute', $data);
            }
        }
    }

}
    