<?php
class ControllerExtensionDashboardPendingOrder extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/dashboard/pending_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('dashboard_pending_order', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/dashboard/pending_order', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/dashboard/pending_order', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true);

        if (isset($this->request->post['dashboard_pending_order_width'])) {
            $data['dashboard_pending_order_width'] = $this->request->post['dashboard_pending_order_width'];
        } else {
            $data['dashboard_pending_order_width'] = $this->config->get('dashboard_pending_order_width');
        }
    
        $data['columns'] = array();
        
        for ($i = 3; $i <= 12; $i++) {
            $data['columns'][] = $i;
        }
                
        if (isset($this->request->post['dashboard_pending_order_status'])) {
            $data['dashboard_pending_order_status'] = $this->request->post['dashboard_pending_order_status'];
        } else {
            $data['dashboard_pending_order_status'] = $this->config->get('dashboard_pending_order_status');
        }

        if (isset($this->request->post['dashboard_pending_order_sort_order'])) {
            $data['dashboard_pending_order_sort_order'] = $this->request->post['dashboard_pending_order_sort_order'];
        } else {
            $data['dashboard_pending_order_sort_order'] = $this->config->get('dashboard_pending_order_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/dashboard/pending_order_form', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/dashboard/pending_order')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
    
    public function dashboard() {
        
        $this->load->language('extension/dashboard/pending_order');

        $data['heading_title'] = $this->language->get('heading_title');
        
        $data['orders'] = [];

        // Load order status from settings
        $pending_status_id = $this->config->get('config_order_status_id');

        $query = $this->db->query("
            SELECT o.order_id, o.firstname, o.lastname, o.total, o.date_added, os.name AS status
            FROM `" . DB_PREFIX . "order` o
            LEFT JOIN `" . DB_PREFIX . "order_status` os ON (o.order_status_id = os.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE o.order_status_id = '" . (int)$pending_status_id . "'
            ORDER BY o.date_added DESC
            LIMIT 5
        ");

        foreach ($query->rows as $row) {
            $data['orders'][] = [
                'order_id' => $row['order_id'],
                'customer' => $row['firstname'] . ' ' . $row['lastname'],
                'total'    => $this->currency->format($row['total'], $this->config->get('config_currency')),
                'status'   => $row['status'],
                'date_added' => date($this->language->get('date_format_short'), strtotime($row['date_added'])),
                'view'     => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $row['order_id'], true)
            ];
        }
        
        return $this->load->view('extension/dashboard/pending_order', $data);
    }
}
