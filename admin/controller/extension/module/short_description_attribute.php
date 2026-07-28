<?php
 class ControllerExtensionModuleShortDescriptionAttribute extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('extension/module/short_description_attribute');
        $this->document->setTitle($this->language->get('heading_title1'));
        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('short_description_attribute', $this->request->post);
            
            if(isset($this->request->post['short_description_attribute']['status'])){
                $module_short_description_status = $this->request->post['short_description_attribute']['status'];
            }else{
                $module_short_description_status = "0";
            }
            $store_id = isset($this->request->get['store_id']) ? $this->request->get['store_id'] : 0 ;
            $this->model_setting_setting->editSetting('module_', array('module_short_description_attribute_status' => $module_short_description_status), $store_id);
            
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/module/short_description_attribute', 'user_token=' . $this->session->data['user_token'], 'SSL'));
        }

        $data['heading_title'] = $this->language->get('heading_title1');

        //
        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            $data['short_description_attribute'] = $this->request->post['short_description_attribute'];
        } else {
            $data['short_description_attribute'] = $this->config->get('short_description_attribute');
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title1'),
            'href' => $this->url->link('extension/module/short_description_attribute', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['action'] = $this->url->link('extension/module/short_description_attribute', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', 'SSL');

        $data['user_token'] = $this->session->data['user_token'];



        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/short_description_attribute', $data));
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/short_description_attribute')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    public function uninstall() {
            $this->load->model('extension/module/short_description_attribute');
            $this->load->model('setting/setting');
            $this->model_setting_setting->deleteSetting('short_description_attribute');
           
        
    }

    public function install() {
 
            $this->load->model('extension/module/short_description_attribute');
            $this->model_extension_module_short_description_attribute->install();

            // initial variable
            $initial = array();
            $initial['short_description_attribute'] = array(
                'status' => 0
            );

            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('short_description_attribute', $initial);
        
    }
    

}

?>