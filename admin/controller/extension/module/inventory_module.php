<?php
class ControllerExtensionModuleInventoryModule extends Controller {

    public function index() {

        if (!$this->user->hasPermission('access', 'extension/module/inventory_module')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->document->setTitle('Inventory Dashboard');

        $data['inventory'] = $this->url->link(
            'extension/module/inventory_module/inventory',
            'user_token=' . $this->session->data['user_token'],
            true
        );

        $data['expense'] = $this->url->link(
            'extension/module/inventory_module/expense',
            'user_token=' . $this->session->data['user_token'],
            true
        );

        $data['payroll'] = $this->url->link(
            'extension/module/inventory_module/payroll',
            'user_token=' . $this->session->data['user_token'],
            true
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/inventory_module_dashboard', $data));
    }
}