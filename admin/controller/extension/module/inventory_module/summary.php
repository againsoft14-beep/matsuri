<?php
class ControllerExtensionModuleInventoryModuleSummary extends Controller {
    
    public function index() {
        $this->load->language('extension/module/inventory_module/inventory');
        $this->document->setTitle('Inventory Summary');

        $this->load->model('extension/module/inventory_module/summary');

        $user_token = $this->session->data['user_token'];
        $data['user_token'] = $user_token;

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => 'Home',
            'href' => $this->url->link('common/dashboard', 'user_token=' . $user_token, true)
        );
        $data['breadcrumbs'][] = array(
            'text' => 'Inventory Summary',
            'href' => $this->url->link('extension/module/inventory_module/summary', 'user_token=' . $user_token, true)
        );

        $summary_info = $this->model_extension_module_inventory_module_summary->getInventorySummary();


        if ($summary_info) {
            $data['total_inventory_value'] = $this->currency->format($summary_info['total_value'], $this->config->get('config_currency'));
            $data['total_qty'] = $summary_info['total_qty'];
            $data['total_lots'] = $summary_info['total_lots'];
            $data['total_suppliers'] = $summary_info['total_suppliers'];
        } else {
            $data['total_inventory_value'] = $this->currency->format(0, $this->config->get('config_currency'));
            $data['total_qty'] = 0;
            $data['total_lots'] = 0;
            $data['total_suppliers'] = 0;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        

        $this->response->setOutput($this->load->view('extension/module/inventory_module/inventory/summary', $data));
    }
}