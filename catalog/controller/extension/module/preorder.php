<?php
class ControllerExtensionModulePreOrder extends Controller {
    public static $init = false;
    private $data = array();
    private $moduleName;
    private $modulePath;
    private $moduleModel;
    private $callModel;

    public function __construct($registry) {
        parent::__construct($registry);

        if (self::$init) {
            $this->initConfig();
            $this->init();
        }
    }

    private function initConfig() {
        $this->config->load('isenselabs/preorder');

        $this->moduleVersion = $this->config->get('preorder_moduleVersion');
        $this->moduleName    = $this->config->get('preorder_moduleName');
        $this->callModel     = $this->config->get('preorder_callModel');
        $this->modulePath    = $this->config->get('preorder_modulePath');
    }

    private function init() {
        /* Module-specific declarations - Begin */
        $this->load->language($this->modulePath);
        $this->load->model($this->modulePath);
        $this->moduleModel = $this->{$this->callModel};

        $this->load->model('setting/store');
    }

    public function index()
    {
        return null;
    }

    /**
     * Controller to monitor product cart button and options change
     */
    public function script() {
        $module_setting = $this->config->get($this->moduleName);

        if (empty($module_setting) || $module_setting['Enabled'] != 'yes') {
            return;
        }

        $this->data[$this->moduleName]           = $module_setting;
        $this->data[$this->moduleName]['status'] = $this->data[$this->moduleName]['Enabled'] == 'yes' ? true : false;

        // Take the store date format
        $date_format_short = $this->language->get('date_format_short');

        if (isset($this->request->get['route']) && $this->request->get['route'] == 'journal2/quickview' && !empty($this->request->get['pid'])) {
            $this->request->get['product_id'] = $this->request->get['pid'];
        }

        if (isset($this->request->get['product_id'])) {
            $this->data['product_id'] = $this->request->get['product_id'];

            if (!empty($module_setting['DateNote'])) {
                $preorder_date_note = $module_setting['DateNote'][$this->config->get('config_language_id')];
                $find    = array("{preorder_date}","{preorder_note}","{preorder_quantity}");
                $replace = array('', '', '');

                $preorder_product = $this->moduleModel->checkPreOrder($this->request->get['product_id']);
                $replace = array($preorder_product['preorder_date'], $preorder_product['preorder_note'], $preorder_product['preorder_quantity']);

                $this->data['preorder_date_note'] = htmlentities(str_replace($find, $replace, $preorder_date_note), ENT_QUOTES, 'UTF-8');
            }
        }

        $this->data['preorder_button'] = $module_setting['ButtonName'][$this->config->get('config_language_id')];
        $this->data['button_cart']     = $this->language->get('button_cart');

        $this->data['popup'] = false;
        if (!empty($this->request->get['popup']) && $this->request->get['popup'] == 'quickview') {
            $this->data['popup'] = true;
        }

        // Theme identifier
        $this->data['theme'] = $this->config->get('config_theme') ? $this->config->get('config_theme') : $this->config->get('config_template');
        $this->data['theme'] = str_replace('theme_', '', $this->data['theme']);
        if ($this->data['theme'] == 'default' && $this->config->get('theme_default_directory') != $this->data['theme']) {
            $this->data['theme'] = $this->config->get('theme_default_directory');
        }

        return $this->load->view($this->modulePath, $this->data);
    }

    public function checkQuantityPO() {
        $product_id = isset($this->request->post['product_id']) ? $this->request->post['product_id'] : 0;
        $quantity   = isset($this->request->post['quantity']) ? (int)$this->request->post['quantity'] : 1;
        $options    = isset($this->request->post['option']) ? array_filter($this->request->post['option']) : array();
        $json       = array(
            'PO'        => false,   // legacy, avoid using it
            'status'    => false,   // true if product or one of the options is preorder
            'products'  => array(), // product_id list
            'error'     => array()
        );

        $this->load->model('catalog/product');
        $this->load->model('extension/module/preorder');
        $this->load->language('checkout/cart');

        // Check a product, with or without options
        if (!is_array($product_id) || $options) {
            $check_options = $this->checkPOProduct((int)$product_id, $options);
            $json['error'] = $check_options['error'];

            if (!$json['error']) {
                $preorder       = $this->model_extension_module_preorder->checkPreOrder((int)$product_id, $check_options['options'], null, array('quantity' => $quantity));
                $json['status'] = $json['PO'] = $preorder['preorder_product'];
            }

        // Check multiple products (in module, category, manufacturer etc)
        } else {
            $product_id = array_filter($product_id);
            foreach ($product_id as $pr_id) {
                $preorder     = $this->model_extension_module_preorder->checkPreOrder($pr_id);
                if ($preorder['preorder_product']) {
                    $json['products'][] = $pr_id;
                }
            }

            $json['PO'] = $json['products'];
            if ($json['products']) {
                $json['status'] = true;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Check a product
     * Used by: checkQuantityPO(), onAfterCheckoutCartAdd()
     *
     * @return [type] [description]
     */
    protected function checkPOProduct($product_id, $options) {
        $data = array(
            'error'     => array(),
            'options'   => array()
        );

        if ($options) {
            $product_options = $this->model_catalog_product->getProductOptions($product_id);

            // Flattened for validate
            $post_options = array();
            foreach ($options as $value) {
                if (!is_array($value)) {
                    $post_options[] = $value;
                } else {
                    foreach ($value as $val) {
                        $post_options[] = $val;
                    }
                }
            }

            foreach ($product_options as $product_option) {
                if ($product_option['required'] && empty($options[$product_option['product_option_id']])) {
                    $data['error'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
                }

                if (!$data['error']) {
                    foreach ($product_option['product_option_value'] as $pr_option) {
                        if (in_array($pr_option['product_option_value_id'], $post_options)) {
                            $data['options'][] = array(
                                'product_option_id'       => $product_option['product_option_id'],
                                'product_option_value_id' => $pr_option['product_option_value_id'],
                                'type'                    => $product_option['type'],
                                'required'                => $product_option['required'],
                                'quantity'                => $pr_option['quantity'],
                            );
                        }
                    }
                }

            }
        }

        return $data;
    }

    // === Events

    /**
     * Extend cart library to mark if product is preorder
     */
    public function replaceCart($eventRoute, $args) {
        $this->initConfig();
        $this->event->unregister("*/before", $this->modulePath . "/replaceCart");
        self::$init = true;

        $this->load->library("vendor/isenselabs/preorder/preordercart");
        $this->registry->set("cart", $this->preordercart);
    }

    /**
     * Load module script() at footer, make it available to all pages
     *
     * Trigger: catalog/controller/common/footer/after
     * Trigger: catalog/controller/journal2/quickview/after
     */
    public function scriptLoader($eventRoute, &$data, &$output) {
        if (!$output && $eventRoute == 'journal2/quickview') {
            $output = $this->response->getOutput();
        }

        $output = str_replace('</body>', $this->load->controller($this->modulePath . '/script') . '</body>', $output);

		/* iCustomFooter Compatibility */
		$output = str_replace('<div id="icustomfooter-custom"', $this->load->controller($this->modulePath . '/script') . '<div id="icustomfooter-custom"', $output);

        if ($eventRoute == 'journal2/quickview') {
            $this->response->setOutput($output);
        }
    }

    /**
     * Add stock_status_id to returned product info
     *
     * Trigger: catalog/model/catalog/product/getProduct/after
     */
    public function onAfterGetProduct($eventRoute, &$args, &$product_info) {
        $product_id = (int)$args[0];

        if ($product_id && !empty($product_info['product_id'])) {
            $product_stock_status = $this->db->query("SELECT stock_status_id FROM " . DB_PREFIX . "product WHERE product_id=" . (int)$product_id);

            if ($product_stock_status->num_rows) {
                $product_info["stock_status_id"] = $product_stock_status->row["stock_status_id"];
            }
        }
    }

    /**
     * Manipulate option to show at product page
     *
     * Trigger: catalog/model/catalog/product/getProductOptions/after
     */
    public function onAfterGetProductOptions($eventRoute, &$args, &$options) {
        $product_id = $args[0];
        $preorder = $this->moduleModel->checkPreOrder($product_id);

        foreach ($options as &$option) {
            $product_option_value_data = array();

            foreach ($option['product_option_value'] as &$option_value) {
                if ($option_value['subtract'] && ($preorder['preorder_product'] || $option_value['quantity'] <= 0)) {
                    $option_value['subtract'] = 0;
                }
            }
        }
    }

    /**
     * Trigger: catalog/controller/checkout/cart/add/after
     */
    public function onAfterCheckoutCartAdd($eventRoute, &$data, &$output) {
        $product_id = isset($this->request->post['product_id']) ? (int)$this->request->post['product_id'] : 0;
        $quantity   = isset($this->request->post['quantity']) ? (int)$this->request->post['quantity'] : 1;
        $options    = isset($this->request->post['option']) ? array_filter($this->request->post['option']) : array();
        $json       = json_decode($this->response->getOutput(), true);

        if (empty($json['error']['option'])) {
            $this->load->model($this->modulePath);

            $check_options = $this->checkPOProduct((int)$product_id, $options);
            $preorder      = $this->model_extension_module_preorder->checkPreOrder((int)$product_id, $check_options['options'], null, array('quantity' => $quantity));

            if ($preorder['preorder_product']) {
                $this->load->model('catalog/product');
                $this->load->language($this->modulePath);

                $product_info = $this->model_catalog_product->getProduct($product_id);

                $json['success'] = sprintf($this->language->get('preorder_Success'), $this->url->link('product/product', 'product_id=' . $product_id), $product_info['name'], $this->url->link('checkout/cart'));

                // Journal3 add cart notification
                if (!empty($json['notification']['message'])) {
                    $json['notification']['message'] = $json['success'];
                }
            }
        }

        $this->response->setOutput(json_encode($json));
    }

    /**
     * Trigger: catalog/model/checkout/order/addOrder/after
     */
    public function onAfterAddOrder($eventRoute, &$args, &$order_id) {
        $data = $args[0];

        if (isset($data['products'])) {
            $language_id = isset($data['language_id']) ? $data['language_id'] : $this->config->get('config_language_id');

            $this->load->model("checkout/order");
            $order_products = $this->model_checkout_order->getOrderProducts($order_id);

            foreach ($order_products as $product) {
                $product["option"] = $this->model_checkout_order->getOrderOptions($order_id, $product["order_product_id"]);
                $this->insertPreorderProduct($order_id, $data['store_id'], $language_id, $product);
            }
        }
    }

    /**
     * Trigger: catalog/model/checkout/order/editOrder/after
     */
    public function onAfterEditOrder($eventRoute, &$args) {
        $order_id = $args[0];
        $data = $args[1];
        $this->db->query("DELETE FROM " . DB_PREFIX . "preorder WHERE order_id = '" . (int)$order_id . "'");

        if (isset($data['products'])) {
            $language_id = isset($data['language_id']) ? $data['language_id'] : $this->config->get('config_language_id');

            $this->load->model("checkout/order");
            $order_products = $this->model_checkout_order->getOrderProducts($order_id);

            foreach ($order_products as $product) {
                $product["option"] = $this->model_checkout_order->getOrderOptions($order_id, $product["order_product_id"]);
                $this->insertPreorderProduct($order_id, $data['store_id'], $language_id, $product);
            }
        }
    }

    /**
     * Trigger: catalog/model/checkout/order/getOrder/after
     */
    public function onAfterGetOrder($eventRoute, &$args, &$order_info) {
        $order_id = $args[0];
        $preorder_query = $this->db->query("SELECT preorder_id FROM `" . DB_PREFIX . "preorder` WHERE order_id = '" . (int)$order_id . "'");

        $order_info["preorder"] = false;
        if ($preorder_query->num_rows > 0) {
            $order_info["preorder"] = true;
        }
    }

    /**
     * Trigger: catalog/model/checkout/order/addOrderHistory/before
     */
    public function onBeforeAddOrderHistory($eventRoute, &$args) {
        $order_id = $args[0];
        $order_status_id = isset($args[1]) ? $args[1] : 0;

        // Make sure it's default or complete order status
        if ($order_status_id == $this->config->get('config_order_status_id') || in_array($order_status_id, (array)$this->config->get('config_complete_status'))) {
            $preorderSetting = $this->config->get('preorder');

            // Check preorder status
            if (!empty($preorderSetting) && $preorderSetting['Enabled'] == 'yes' && !empty($preorderSetting['order_status'])) {
                $preorder_query = $this->db->query("SELECT preorder_id FROM `" . DB_PREFIX . "preorder` WHERE order_id = '" . (int)$order_id . "'");

                // If order contain preorder product, change the status
                if ($preorder_query->num_rows > 0) {
                    if ($order_status_id == $this->config->get('config_order_status_id')) {
                        $args[1] = $preorderSetting['order_status'];
                    }
                    if (!empty($preorderSetting['order_status_complete']) && in_array($order_status_id, (array)$this->config->get('config_complete_status'))) {
                        $args[1] = $preorderSetting['order_status_complete'];
                    }
                }
            }
        }
    }

    /**
     * Trigger: catalog/model/checkout/order/addOrderHistory/after
     */
    public function onAfterAddOrderHistory($eventRoute, &$args) {
        $this->load->model('checkout/order');

        $order_id = $args[0];

        // Stock subtraction
        $order_products = $this->model_checkout_order->getOrderProducts($order_id);
        foreach ($order_products as $order_product) {
            $this->db->query("UPDATE " . DB_PREFIX . "preorder_product SET preorder_quantity = (preorder_quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "'");
        }
    }

    /**
     * Trigger: catalog/model/journal3/order/save/after
     */
    public function onAfterJournal3OrderSave($eventRoute, &$args) {
        $data        = $args[1];
        $order_id    = !empty($args[0]) ? $args[0] : 0;
        $store_id    = isset($data['store_id']) ? $data['store_id'] : $this->config->get('config_store_id');
        $language_id = isset($data['language_id']) ? $data['language_id'] : $this->config->get('config_language_id');

        if (isset($data['products'])) {
            $this->load->model('checkout/order');

            if ($order_id) { // onAfterEditOrder()
                $this->db->query("DELETE FROM " . DB_PREFIX . "preorder WHERE order_id = '" . (int)$order_id . "'");

                $order_products = $this->model_checkout_order->getOrderProducts($order_id);

                foreach ($order_products as $product) {
                    $product["option"] = $this->model_checkout_order->getOrderOptions($order_id, $product["order_product_id"]);
                    $this->insertPreorderProduct($order_id, $store_id, $language_id, $product);
                }

            } elseif (!empty($this->session->data['order_id'])) { // onAfterAddOrder()
                $order_id = $this->session->data['order_id'];
                $order_products = $this->model_checkout_order->getOrderProducts($order_id);

                foreach ($order_products as $product) {
                    $product['option'] = $this->model_checkout_order->getOrderOptions($order_id, $product["order_product_id"]);
                    $this->insertPreorderProduct($order_id, $store_id, $language_id, $product);
                }
            }
        }
    }

    /**
     * Trigger: catalog/controller/checkout/success/before
     */
    public function onBeforeCheckoutSuccess($eventRoute, &$data) {
        if (isset($this->session->data['order_id'])) {
            $this->load->model('extension/module/preorder');
            $this->load->model('checkout/order');

            $PO = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            if (!empty($PO['preorder']) && $PO['order_status_id'] != 0) {
                $this->model_extension_module_preorder->sendPreOrderEmail($PO['order_id'], $PO['preorder']);
            }
        }
    }

    // === Helpers

    private function insertPreorderProduct($order_id, $store_id, $language_id, $product) {
        $this->load->model($this->modulePath);
        $preorder = $this->model_extension_module_preorder->checkPreOrder($product['product_id']);
        $is_preorder = $preorder["preorder_product"];

        if (!$is_preorder && !empty($product['option'])) {
            $param = array(
                'quantity' => isset($product['quantity']) ? $product['quantity'] : 0
            );

            foreach ($product['option'] as $option) {
                if (isset($option['product_option_value_id'])) {
                    $preorder_opt = $this->model_extension_module_preorder->checkPreOrder($product['product_id'], array(), $option['product_option_value_id']);

                    if ($preorder_opt['preorder_product']) {
                        $is_preorder = true;
                        break;
                    }
                }
            }
        }

        if ($is_preorder) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "preorder SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id']  . "', order_product_id = '" . (int)$product["order_product_id"] . "', store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', date_created = NOW()");
        }
    }

    private function getStore($store_id) {
        if ($store_id && $store_id != 0) {
            $store = $this->model_setting_store->getStore($store_id);
        } else {
            $store['store_id'] = 0;
            $store['name'] = $this->config->get('config_name');
            $store['url'] = $this->getCatalogURL();
        }
        return $store;
    }

    private function getCatalogURL() {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTP_SERVER;
        } else {
            $storeURL = HTTPS_SERVER;
        }
        return $storeURL;
    }
}
