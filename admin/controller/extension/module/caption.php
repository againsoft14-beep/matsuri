<?php
 class ControllerExtensionModuleCaption extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('extension/module/caption');
        $this->install();
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $module_caption_status = $this->request->post['module_caption_status'];
            
            $store_id = isset($this->request->get['store_id']) ? $this->request->get['store_id'] : 0 ;
            $this->model_setting_setting->editSetting('module_', array('module_caption_status' => $module_caption_status), $store_id);
            
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/module/caption', 'user_token=' . $this->session->data['user_token'], 'SSL'));
        }

        $data['heading_title'] = $this->language->get('heading_title');


        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['text_form'] = $this->language->get('text_form');

        $data['text_status'] = $this->language->get('text_status');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['tab_support'] = $this->language->get('tab_support');
        $data['tab_setting'] = $this->language->get('tab_setting');

        $data['entry_setting'] = $this->language->get('entry_setting');
        $data['entry_value'] = $this->language->get('entry_value');

        
        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            $data['module_caption_status'] = $this->request->post['module_caption_status'];
        } else {
            $data['module_caption_status'] = $this->config->get('module_caption_status');
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
            'href' => $this->url->link('extension/module/short_description', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['action'] = $this->url->link('extension/module/caption', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', 'SSL');

        $data['user_token'] = $this->session->data['user_token'];



        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/caption', $data));
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/caption')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    public function uninstall() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_caption_status');

        $this->load->model('extension/module/caption');
        $this->model_extension_module_caption->uninstall();
    }

    public function install() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_caption_status', 1);

        $this->load->model('extension/module/caption');
        $this->model_extension_module_caption->install();
        
    }
    

}

?>