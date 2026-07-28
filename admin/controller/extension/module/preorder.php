<?php
class ControllerExtensionModulepreorder extends Controller {
    private $moduleName;
    private $modulePath;
    private $moduleModel;
    private $moduleVersion;
    private $extensionsLink;
    private $callModel;
    private $error = array();
    private $data = array();
    private $eventGroup;

    public function __construct($registry) {
        parent::__construct($registry);
        // Predata
        $this->data['user_token']   = isset($this->session->data['user_token']) ? $this->session->data['user_token'] : '';
        $this->data['route']        = isset($this->request->get["route"]) ? $this->request->get["route"] : '';
        // Config Loader
        $this->config->load('isenselabs/preorder');
        // Module Constants
        $this->moduleName           = $this->config->get('preorder_moduleName');
        $this->callModel            = $this->config->get('preorder_callModel');
        $this->modulePath           = $this->config->get('preorder_modulePath');
        $this->moduleVersion        = $this->config->get('preorder_moduleVersion');
        $this->eventGroup           = $this->config->get('preorder_eventGroup');
        $this->extensionsLink       = $this->url->link($this->config->get('preorder_extensionsLink'), 'user_token=' . $this->data['user_token'].$this->config->get('preorder_extensionsLink_type'), 'SSL');
        // Load Language
        $this->load->language($this->modulePath);
        // Load Model
        $this->load->model($this->modulePath);
        // Model Instance
        $this->moduleModel          = $this->{$this->callModel};
        // Global Variables
        $this->data['moduleName']        = $this->moduleName;
        $this->data['modulePath']        = $this->modulePath;
        $this->data['moduleData_module'] = $this->moduleData_module;
        $this->data['moduleModel']       = $this->moduleModel;
        $this->load->model('catalog/product');
        $this->load->model('setting/store');
        $this->load->model('setting/setting');
        $this->load->model('localisation/language');

        $this->update();
    }

    public function index() {
        $this->document->setTitle($this->language->get('heading_title').' '.$this->version);
        $this->document->addStyle('view/stylesheet/'.$this->moduleName.'.css');
        $this->document->addStyle('view/javascript/'.$this->moduleName.'/colorpicker/css/colorpicker.css');
        $this->document->addScript('view/javascript/'.$this->moduleName.'/colorpicker/js/colorpicker.js');
        if (!isset($this->request->get['store_id'])) {
            $this->request->get['store_id'] = 0;
        }
        $catalogURL = $this->getCatalogURL();
        $store = $this->getCurrentStore($this->request->get['store_id']);

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateForm()) {
            if (!empty($_POST['OaXRyb1BhY2sgLSBDb21'])) {
                $this->request->post['preorder']['LicensedOn'] = $_POST['OaXRyb1BhY2sgLSBDb21'];
            }
            if (!empty($_POST['cHRpbWl6YXRpb24ef4fe'])) {
                $this->request->post['preorder']['License'] = json_decode(base64_decode($_POST['cHRpbWl6YXRpb24ef4fe']),true);
            }

            $store = $this->getCurrentStore($this->request->post['store_id']);

            $this->model_setting_setting->editSetting('preorder', $this->request->post, $this->request->post['store_id']);
            $this->model_setting_setting->editSetting('module_preorder', $this->request->post, $this->request->post['store_id']);

            $this->eventSetup();
            $this->session->data['success'] = $this->language->get('text_success_modify');
            $this->response->redirect($this->url->link($this->data['modulePath'], 'store_id='.$this->request->post['store_id'] . '&user_token=' . $this->session->data['user_token'], 'SSL'));
        }
        $languages = $this->model_localisation_language->getLanguages();
        $this->data['languages'] = $languages;
        foreach ($this->data['languages'] as $key => $value) {
            $this->data['languages'][$key]['flag_url'] = 'language/'.$this->data['languages'][$key]['code'].'/'.$this->data['languages'][$key]['code'].'.png"';
        }
        $firstLanguage = array_shift($languages);
        $this->data['firstLanguageCode'] = $firstLanguage['code'];
        $languageVariables = array(
            'text_default',
            'button_cancel',
            'text_disabled',
            'text_enabled',
            'text_module',
            'text_module_settings',
            'text_module_settings_help',
            'default_notification',
            'text_success_modify',
            'text_success_activation',
            'text_button_name',
            'text_pre_order',
            'text_pre_order_note',
            'text_pre_order_note_help',
            'pre_order_note',
            'text_admin_notification',
            'text_admin_notification_help',
            'text_customer_notification',
            'text_customer_notification_help',
            'text_admin_email',
            'text_admin_email_help',
            'text_admin_email_subject',
            'text_admin_email_body',
            'text_email',
            'text_email_help',
            'text_email_subject',
            'text_email_body',
            'text_custom_css',
            'text_customer_email',
            'text_customer_name',
            'text_product',
            'text_date',
            'text_language',
            'text_actions',
            'text_remove',
            'text_remove_all',
            'text_module_status',
            'text_module_status_help',
            'error_allow_checkout',
            'notifywhenavailable_enabled',
            'text_date_note',
            'text_date_note_help',
            'text_date_note_example',
            'text_custom_colors',
            'text_custom_colors_help',
            'text_text',
            'text_background',
            'text_border',
            'text_image_size',
            'text_image_size_help',
            'text_width',
            'text_height'
        );
        foreach ($languageVariables as $languageVariable) {
            $this->data[$languageVariable] = $this->language->get($languageVariable);
        }
        $this->data['heading_title'] = $this->language->get('heading_title').' '.$this->moduleVersion;
        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }
        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }
        $this->data['breadcrumbs'] = array();
        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => false
        );
        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_module'),
            'href'      => $this->extensionsLink,
            'separator' => ' :: '
        );
        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link($this->data['modulePath'], 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => ' :: '
        );
        $this->data['action'] = $this->url->link($this->data['modulePath'], 'user_token=' . $this->session->data['user_token'], 'SSL');
        $this->data['cancel'] = $this->url->link('extension/module', 'user_token=' . $this->session->data['user_token'], 'SSL');

        if (isset($this->request->post[$this->data['moduleName']])) {
            foreach ($this->request->post[$this->data['moduleName']] as $key => $value) {
                $this->data['data'][$this->data['moduleName']][$key] = $this->request->post[$this->data['moduleName']][$key];
            }
        } else {
            $configValue = $this->config->get($this->data['moduleName']);
            $this->data['data'][$this->data['moduleName']] = $configValue;
        }

        // === Statistics
        $stats  = $this->moduleModel->getStatistics($store['store_id']);
        $this->data['products'] = array();
        $this->data['product_names'] = array();
        if (isset($stats->rows)) {
            foreach ($stats->rows as $row) {
                $this->data["product_names"][$row['product_id']] =  html_entity_decode($row["name"], ENT_COMPAT, "UTF-8");
                $this->data['products'][$row['product_id']][$row['notify']][$row['order_id']] = 1;
            }
        }

        $this->data['product_options'] = array();
        foreach ($this->data['products'] as $product_id => $value) {
            $this->data['product_options'][$product_id] = $this->moduleModel->getProductOptions($product_id);
        }

        $this->load->model('localisation/stock_status');
        $this->data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();
        if ($this->config->get('config_stock_checkout') == 0) {
            $this->data['allow_checkout'] = false;
        } else {
            $this->data['allow_checkout'] = true;
        }

        $this->data['stores']               = array_merge(array(0 => array('store_id' => '0', 'name' => $this->config->get('config_name') . ' (' . $this->data['text_default'].')', 'url' => HTTP_SERVER, 'ssl' => HTTPS_SERVER)), $this->model_setting_store->getStores());
        $this->data['store']                = $store;
        $this->data['data']                 = $this->model_setting_setting->getSetting($this->data['moduleName'], $store['store_id']);
        $this->data['modules']              = $this->model_setting_setting->getSetting('preorder_module', $store['store_id']);
        $this->data['product_info']         = $this->model_catalog_product;
        $this->data['user_token']           = $this->session->data['user_token'];
        $this->data['now']                  = time();

        $this->load->model('localisation/order_status');
        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        if (empty($this->data['data'][$this->data['moduleName']]['order_status'])) {
            $this->data['data'][$this->data['moduleName']]['order_status'] = $this->config->get('config_order_status_id');
        }
        if (empty($this->data['data'][$this->data['moduleName']]['order_status_complete'])) {
            $this->data['data'][$this->data['moduleName']]['order_status_complete'] = $this->config->get('config_complete_status')[0];
        }

        $this->data['header']               = $this->load->controller('common/header');
        $this->data['column_left']          = $this->load->controller('common/column_left');
        $this->data['footer']               = $this->load->controller('common/footer');
        //Check if NotifyWhenAvailable is installed and enabled
        $this->data['notifywhenavailable']  = $this->model_setting_setting->getSetting('notifywhenavailable', $store['store_id']);
        $this->data['licenseMessage']       = empty($this->data['data'][$this->moduleName]['LicensedOn']) ? base64_decode('PGRpdiBjbGFzcz0iYWxlcnQgYWxlcnQtZGFuZ2VyIGZhZGUgaW4iPg0KICAgICAgICA8YnV0dG9uIHR5cGU9ImJ1dHRvbiIgY2xhc3M9ImNsb3NlIiBkYXRhLWRpc21pc3M9ImFsZXJ0IiBhcmlhLWhpZGRlbj0idHJ1ZSI+w5c8L2J1dHRvbj4NCiAgICAgICAgPGg0Pldhcm5pbmchIFVubGljZW5zZWQgdmVyc2lvbiBvZiB0aGUgbW9kdWxlITwvaDQ+DQogICAgICAgIDxwPllvdSBhcmUgcnVubmluZyBhbiB1bmxpY2Vuc2VkIHZlcnNpb24gb2YgdGhpcyBtb2R1bGUhIFlvdSBuZWVkIHRvIGVudGVyIHlvdXIgbGljZW5zZSBjb2RlIHRvIGVuc3VyZSBwcm9wZXIgZnVuY3Rpb25pbmcsIGFjY2VzcyB0byBzdXBwb3J0IGFuZCB1cGRhdGVzLjwvcD48ZGl2IHN0eWxlPSJoZWlnaHQ6NXB4OyI+PC9kaXY+DQogICAgICAgIDxhIGNsYXNzPSJidG4gYnRuLWRhbmdlciIgaHJlZj0iamF2YXNjcmlwdDp2b2lkKDApIiBvbmNsaWNrPSJlbnRlckxpY2Vuc2UoKSI+RW50ZXIgeW91ciBsaWNlbnNlIGNvZGU8L2E+DQogICAgPC9kaXY+') : '';

        $this->data['license_encoded']      = !empty($this->data['data'][$this->moduleName]['LicensedOn']) ? base64_encode(json_encode($this->data['data'][$this->moduleName]['License'])) : '';
        $this->data['license_expire_date']  = !empty($this->data['data'][$this->moduleName]['LicensedOn']) ? date("F j, Y",strtotime($this->data['data'][$this->moduleName]['License']['licenseExpireDate'])) : '';
        $this->data['ticket_open_link']     = 'http://isenselabs.com/tickets/open/' . base64_encode('Support Request') . '/' . base64_encode('239') . '/' . base64_encode($_SERVER['SERVER_NAME']);

		$this->data['tab_viewcustomers_content']   = $this->load->view($this->data['modulePath'] . '/tab_viewcustomers', $this->data);
        $this->data['tab_controlpanel_content']    = $this->load->view($this->data['modulePath'] . '/tab_controlpanel', $this->data);
        $this->data['tab_settings_content'] = $this->load->view($this->data['modulePath'] . '/tab_settings', $this->data);
        $this->data['tab_archive_content']   = $this->load->view($this->data['modulePath'] . '/tab_archive', $this->data);
        $this->data['tab_support_content']   = $this->load->view($this->data['modulePath'] . '/tab_support', $this->data);

        $this->response->setOutput($this->load->view($this->data['modulePath'], $this->data));
    }

    //=== Events

    /**
     * Trigger: admin/view/catalog/product_form/before
     */
    public function addViewData($eventRoute, &$data) {
        $data['language_id'] = $this->config->get('config_language_id');

        $data['data']['preorder'] = array();
        if (isset($this->request->post['preorder'])) {
            $data['data']['preorder'] = $this->request->post['preorder'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['data']['preorder'] = $this->moduleModel->getProductPreorder($this->request->get['product_id']);
        }

        foreach ($data['languages'] as $key => $value) {
            $data['languages'][$key]['flag_url'] = 'language/'.$data['languages'][$key]['code'].'/'.$data['languages'][$key]['code'].'.png"';
        }
    }

    /**
     * Trigger: admin/view/catalog/product_form/after
     */
    public function addCustomProductFields($eventRoute, &$data, &$output) {
        $custom_fields = $this->load->view($this->modulePath . "/events/product_form_custom_fields", $data);
        $output = str_replace('<label class="col-sm-2 control-label" for="input-stock-status">', $custom_fields . '<label class="col-sm-2 control-label" for="input-stock-status">', $output);
    }

    /**
     * Trigger: admin/view/common/footer/after
     */
    public function addProductFormScript($eventRoute, &$data, &$output) {
        if ($this->data['route'] == "catalog/product/add" || $this->data['route'] == "catalog/product/edit") {
            $this->load->model('setting/setting');

            $settings = $this->model_setting_setting->getSetting('preorder', $store_id=0);
            $settings = (isset($settings['preorder'])) ? $settings['preorder'] : array();

            if (isset($settings['Enabled']) && $settings['Enabled']=='yes') {
                $data['enabled_stock_status'] = array();

                $this->load->model('localisation/stock_status');
                $stock_statuses = $this->model_localisation_stock_status->getStockStatuses();
                foreach ($stock_statuses as $stock_status) {
                    if (isset($settings[$stock_status['stock_status_id']])) {
                        $data['enabled_stock_status'][] = $stock_status['stock_status_id'];
                    }
                }
                $data['enabled_stock_status_json'] = json_encode($data['enabled_stock_status']);
            }

            $script = $this->load->view($this->modulePath . "/events/product_form_js", $data);
            $output = $script . $output;
        }
    }

    /**
     * ...
     *
     * Trigger: admin/model/catalog/product/addProduct/after
     */
    public function onBeforeEditProduct($eventRoute, &$args) {
        $product_id = $args[0];
        $data = $args[1];

        $preorder = $this->config->get($this->moduleName);

        if ($this->db->query("SELECT * FROM " . DB_PREFIX . "preorder_product WHERE product_id = '" . (int)$product_id . "'")->num_rows > 0) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "preorder_product WHERE product_id = '" . (int)$product_id . "'");
        }

        if (isset($preorder['Enabled']) && $preorder['Enabled'] == 'yes' && isset($data['preorder_enabled']) && $data['preorder_enabled'] == 'yes') {
            foreach ($data['preorder']['preorder_note'] as $language_id => $preorder_note) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "preorder_product SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', preorder_note = '" . $this->db->escape($preorder_note) . "', preorder_date = '" . $this->db->escape($data['preorder']['preorder_date']) . "', preorder_quantity = '" . (int)$data['preorder']['preorder_quantity'] . "'");
            }
        }
    }

    /**
     * ...
     *
     * Trigger: admin/model/catalog/product/editProduct/before
     */
    public function onAfterAddProduct($eventRoute, &$args, &$product_id) {
        $data = $args[0];

        $preorder = $this->config->get($this->moduleName);

        if (isset($preorder['Enabled']) && $preorder['Enabled'] == 'yes' && isset($data['preorder_enabled']) && $data['preorder_enabled'] == 'yes') {
            foreach ($data['preorder']['preorder_note'] as $language_id => $preorder_note) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "preorder_product SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', preorder_note = '" . $this->db->escape($preorder_note) . "', preorder_date = '" . $this->db->escape($data['preorder']['preorder_date']) . "', preorder_quantity = '" . (int)$data['preorder']['preorder_quantity'] . "'");
            }
        }
    }

    /**
     * ...
     *
     * Trigger: admin/model/sale/order/getOrderProducts/after
     */
    public function onAfterGetOrderProducts($eventRoute, &$args, &$products) {
        $order_id = $args[0];

        $this->load->model("sale/order");

        if (strpos($this->request->get["route"], 'sale/order') === 0) {
            foreach ($products as &$product) {
                $is_preorder = $this->moduleModel->checkPreorderProduct($product['order_product_id']);

                $product["name"] = ($is_preorder ? ' <span class="text-danger"> [PreOrder] </span> ' : '') . $product['name'];
            }
        }
    }

    /**
     * ...
     *
     * Trigger: admin/model/sale/order/getOrders/after
     */
    public function onAfterGetOrders($eventRoute, &$args, &$results) {
        $filter_data = $args[0];
        $this->load->language('sale/order');

        foreach ($results as &$result) {
            $result["order_status"] = ($result['order_status'] ? $result['order_status'] : $this->language->get('text_missing')) . ($this->moduleModel->checkPreorderOrder($result['order_id']) ? ' <span class="text-danger" style="float:right;"> [PreOrder] </span> ' : '');
        }
    }

    // ================================

    private function getCatalogURL() {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_CATALOG;
        } else {
            $storeURL = HTTP_CATALOG;
        }
        return $storeURL;
    }

    private function getServerURL() {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_SERVER;
        } else {
            $storeURL = HTTP_SERVER;
        }
        return $storeURL;
    }

    private function getCurrentStore($store_id) {
        if ($store_id && $store_id != 0) {
            $store = $this->model_setting_store->getStore($store_id);
        } else {
            $store['store_id'] = 0;
            $store['name'] = $this->config->get('config_name');
            $store['url'] = $this->getCatalogURL();
        }
        return $store;
    }

    public function getcustomers() {
        if (!empty($this->request->get['page'])) {
            $page = (int) $this->request->get['page'];
        } else {
            $page = '1';
        }
        if (!empty($this->request->get['store_id'])) {
            $store_id = (int) $this->request->get['store_id'];
        } else {
            $store_id = 0;
        }
        $this->data['heading_title'] = $this->language->get('heading_title').' '.$this->version;
        $this->data['store_id']      = $store_id;
        $this->data['user_token']    = $this->session->data['user_token'];
        $this->data['limit']         = 8;
        $this->data['total']         = $this->moduleModel->getTotalCustomers($this->data['store_id']);
        $this->data['sources']       = $this->moduleModel->viewcustomers($page, $this->data['limit'], $this->data['store_id']);

        foreach ($this->data["sources"] as &$source) {
            $source["order_info_url"] = $this->url->link("sale/order/info", "user_token=" . $this->session->data["user_token"] . "&order_id=" . $source["order_id"], true);
            $source["options"] = $this->moduleModel->getProductOptionsByOrderId($source['order_id'], $source['product_id'], $source['order_product_id']);
        }

        $pagination                  = new Pagination();
        $pagination->total           = $this->data['total'];
        $pagination->page            = $page;
        $pagination->limit           = $this->data['limit'];
        $pagination->url             = $this->url->link($this->data['modulePath'].'/getcustomers','user_token=' . $this->session->data['user_token'].'&page={page}&store_id='.$this->data['store_id'], 'SSL');
        $this->data['pagination']    = $pagination->render();
        $this->data['results']       = sprintf($this->language->get('text_pagination'), ($this->data['total']) ? (($page - 1) * $this->data['limit']) + 1 : 0, ((($page - 1) * $this->data['limit']) > ($this->data['total'] - $this->data['limit'])) ? $this->data['total'] : ((($page - 1) * $this->data['limit']) + $this->data['limit']), $this->data['total'], ceil($this->data['total'] / $this->data['limit']));
        $this->template              = $this->modulePath.'/viewcustomers';

        $this->response->setOutput($this->load->view($this->template, $this->data));
    }

    public function getarchive() {
        if (!empty($this->request->get['page'])) {
            $page = (int) $this->request->get['page'];
        } else {
			$page = 1;
		}
        if (!empty($this->request->get['store_id'])) {
            $store_id = (int) $this->request->get['store_id'];
        } else {
            $store_id = 0;
        }
        $this->data['heading_title'] = $this->language->get('heading_title').' '.$this->version;
        $this->data['store_id']      = $store_id;
        $this->data['user_token']    = $this->session->data['user_token'];
        $this->data['limit']         = 8;
        $this->data['total']         = $this->moduleModel->getTotalNotifiedCustomers($this->data['store_id']);
        $this->data['sources']       = $this->moduleModel->viewnotifiedcustomers($page, $this->data['limit'], $this->data['store_id']);

        foreach ($this->data["sources"] as &$source) {
            $source["order_info_url"] = $this->url->link("sale/order/info", "user_token=" . $this->session->data["user_token"] . "&order_id=" . $source["order_id"], true);
            $source["options"] = $this->moduleModel->getProductOptionsByOrderId($source['order_id'], $source['product_id'], $source['order_product_id']);
        }

        $pagination                  = new Pagination();
        $pagination->total           = $this->data['total'];
        $pagination->page            = $page;
        $pagination->limit           = $this->data['limit'];
        $pagination->url             = $this->url->link($this->data['modulePath'].'/getarchive', 'user_token=' . $this->session->data['user_token'].'&page={page}&store_id='.$this->data['store_id'], 'SSL');
        $this->data['pagination']    = $pagination->render();
        $this->data['results']       = sprintf($this->language->get('text_pagination'), ($this->data['total']) ? (($page - 1) * $this->data['limit']) + 1 : 0, ((($page - 1) * $this->data['limit']) > ($this->data['total'] - $this->data['limit'])) ? $this->data['total'] : ((($page - 1) * $this->data['limit']) + $this->data['limit']), $this->data['total'], ceil($this->data['total'] / $this->data['limit']));
        $this->template              = $this->data['modulePath'].'/archive';

        $this->response->setOutput($this->load->view($this->template, $this->data));
    }

    public function removecustomer() {
        if (isset($_POST['preorder_id'])) {
            $run_query = $this->db->query("DELETE FROM `" . DB_PREFIX . "preorder` WHERE `preorder_id`=".(int)$_POST['preorder_id']);
            if ($run_query) echo "Success!";
        }
    }

    public function removeallcustomers() {
        if (isset($_POST['remove']) && ($_POST['remove']==true)) {
            $run_query = $this->db->query("DELETE FROM `" . DB_PREFIX . "preorder`");
            if ($run_query) echo "Success!";
        }
    }

    public function removeallarchive() {
        $run_query = $this->db->query("DELETE p FROM `" . DB_PREFIX . "preorder` p
            LEFT JOIN `" . DB_PREFIX . "order_history` oh ON oh.order_id = p.order_id
            WHERE oh.notify=1");
        if ($run_query) echo "Success!";
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', $this->data['modulePath'])) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }


    // ================================

    public function install() {
        $this->moduleModel->install();
        $this->eventSetup();
    }

    public function update() {
        if ($this->config->get('preorder')) {
            $this->moduleModel->update();
        }
    }

    public function uninstall() {
        $this->load->model("setting/event");
        $this->model_setting_event->deleteEventByCode($this->eventGroup);

        $this->model_setting_setting->deleteSetting('preorder_module',0);
        $this->model_setting_setting->deleteSetting($this->moduleName, 0);

        $stores = $this->model_setting_store->getStores();
        foreach ($stores as $store) {
            $this->model_setting_setting->deleteSetting($this->moduleName, $store['store_id']);
        }
        $this->moduleModel->uninstall();
    }

    public function eventSetup() {
        $this->load->model("setting/event");
        $this->model_setting_event->deleteEventByCode($this->eventGroup);

        // Admin events
        $this->model_setting_event->addEvent($this->eventGroup, "admin/view/catalog/product_form/before", $this->modulePath . "/addViewData");
        $this->model_setting_event->addEvent($this->eventGroup, "admin/view/catalog/product_form/after", $this->modulePath . "/addCustomProductFields");
        $this->model_setting_event->addEvent($this->eventGroup, "admin/view/common/footer/after", $this->modulePath . "/addProductFormScript");
        $this->model_setting_event->addEvent($this->eventGroup, "admin/model/catalog/product/addProduct/after", $this->modulePath . "/onAfterAddProduct");
        $this->model_setting_event->addEvent($this->eventGroup, "admin/model/catalog/product/editProduct/before", $this->modulePath . "/onBeforeEditProduct");
        $this->model_setting_event->addEvent($this->eventGroup, "admin/model/sale/order/getOrderProducts/after", $this->modulePath . "/onAfterGetOrderProducts");
        $this->model_setting_event->addEvent($this->eventGroup, "admin/model/sale/order/getOrders/after", $this->modulePath . "/onAfterGetOrders");

        // Catalog events
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/*/before", $this->modulePath . "/replaceCart");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/model/catalog/product/getProduct/after", $this->modulePath . "/onAfterGetProduct");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/model/checkout/order/addOrder/after", $this->modulePath . "/onAfterAddOrder");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/model/checkout/order/editOrder/after", $this->modulePath . "/onAfterEditOrder");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/model/checkout/order/getOrder/after", $this->modulePath . "/onAfterGetOrder");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/model/checkout/order/addOrderHistory/before", $this->modulePath . "/onBeforeAddOrderHistory");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/model/checkout/order/addOrderHistory/after", $this->modulePath . "/onAfterAddOrderHistory");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/model/catalog/product/getProductOptions/after", $this->modulePath . "/onAfterGetProductOptions");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/controller/common/footer/after", $this->modulePath . "/scriptLoader");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/controller/checkout/cart/add/after", $this->modulePath . "/onAfterCheckoutCartAdd");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/controller/checkout/success/before", $this->modulePath . "/onBeforeCheckoutSuccess");

        // Compatibility
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/controller/journal2/quickview/after", $this->modulePath . "/scriptLoader");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/model/journal3/order/save/after", $this->modulePath . "/onAfterJournal3OrderSave");
    }
}
