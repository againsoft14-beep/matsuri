<?php
class ModelExtensionModulePreOrder extends Model {
    private $module = array();

    public function __construct($registry) {
        parent::__construct($registry);

        $this->module['setting'] = $this->config->get('preorder');

        $this->module['setting']['status']         = !empty($this->module['setting']) && $this->module['setting']['Enabled'] == 'yes' ? true : false;
        $this->module['setting']['stock_statuses'] = $this->db->query("SELECT * FROM " . DB_PREFIX . "stock_status")->rows;
    }

    /**
     * Responsible to state if a product is pre_order or not
     *
     * - Prioritize options quantity when product quantity >= 1; rule:
     *   - Is preorder when product main quantity <= 0, regardless options.
     *   - Is preorder when product main quantity >=1, one of the options (radio, checkbox, select) qty <= 0
     * - Is preorder when cart quantity > available quantity (see above)
     *
     * @param  number $product_id
     * @param  array  $options                  Used to bulk check, list must have key: type, quantity
     * @param  number $product_option_value_id  Used to check individual option
     * @param  array  $param                    Parameter: quantity
     */
    public function checkPreOrder($product_id, $options = array(), $product_option_value_id = null, $param = array()) {
        // standarize return
        $data = array(
            'preorder_available_on'     => $this->language->get('preorder_available_on'),
            'preorder_note'             => !empty($this->module['setting']['ButtonName'][$this->config->get('config_language_id')]) ? $this->module['setting']['ButtonName'][$this->config->get('config_language_id')] : 'Pre-Order',
            'preorder_quantity'         => 99,
            'preorder_date'             => '0000-00-00',
            'preorder_product'          => false
        );

        if (!$this->module['setting']['status']) {
            return $data;
        }

        $this->load->model('catalog/product');

        $productinfo       = $this->model_catalog_product->getProduct((int)$product_id);
        $preorderinfo      = $this->GetPreorderedProduct($product_id);
        $cart_quantity     = !empty($param['quantity']) ? $param['quantity'] : $productinfo['quantity'];

        if (empty($productinfo)) {
            return $data;
        }

        if (!empty($preorderinfo)) {
            $data['preorder_note']     = $preorderinfo['preorder_note'] ? $preorderinfo['preorder_note'] : $data['preorder_note'];
            $data['preorder_quantity'] = isset($preorderinfo['preorder_quantity']) ? $preorderinfo['preorder_quantity'] : $data['preorder_quantity'];
            $data['preorder_date']     = $preorderinfo['preorder_date'] != '0000-00-00' ?
                                            date_format(date_create($preorderinfo['preorder_date']), $this->language->get('date_format_short'))
                                            : '';
        }

        // === Check product stock-status within preorder out-of-stock
        $enabled_stock_status = array();
        foreach ($this->module['setting']['stock_statuses'] as $stock_status) {
            if (isset($this->module['setting'][$stock_status['stock_status_id']])) {
                $enabled_stock_status[] = $stock_status['stock_status_id'];
            }
        }

        // Break early
        if (!in_array($productinfo['stock_status_id'], $enabled_stock_status)) {
            return $data;
        }

        // === Check product main quantity
        $negative_quantity = false;
        if ($productinfo['quantity'] <= 0 || ($cart_quantity > $productinfo['quantity'])) {
            $negative_quantity = true;
        }

        // === Check product option quantity (single option)
        if ($product_option_value_id) {
            $product_option = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` WHERE product_option_value_id = " . (int)$product_option_value_id)->row;

            if ($product_option && $product_option['quantity'] <= 0) {
                $negative_quantity = true;
            }
        }

        if ($negative_quantity) {
            $data['preorder_product'] = true;
        }

        // === Options centralized checker (mostly in product page)
        if ($productinfo['quantity'] >= 1 && !empty($options)) {
            $options_qty_preorder = array();

            // Check options qty regardles product main quantity
            foreach ($options as $option) {
                if (in_array($option['type'], array('radio', 'checkbox', 'select'))) {
                    // Also check ordered quantity
                    $options_qty_preorder[] = (int)$option['quantity'] <= 0 || $option['quantity'] < $cart_quantity ? true : false;
                }
            }

            // Validate before override
            if ($options_qty_preorder) {
                if (in_array(true, $options_qty_preorder)) {
                    $data['preorder_product'] = true;
                } else {
                    // When options qty > cart qty
                    $data['preorder_product'] = false;
                }
            }
        }

        // Final checker preorder-quantity
        if ($cart_quantity > $data['preorder_quantity']) {
            $data['preorder_product'] = false;
        }

        return $data;
    }

    public function sendPreOrderEmail($order_id, $pre_order) {
        $preorder = $this->module['setting'];

        if ($pre_order == true && $preorder['status'] && $preorder['Notifications'] == 'yes' || $preorder['CustomerNotifications'] == 'yes') {
            $this->load->model('checkout/order');
            $this->load->model('tool/image');

            $order             = $this->model_checkout_order->getOrder($order_id);
            $preorder_products = $this->GetPreorderedProducts($order_id, $this->config->get('config_store_id'));
            $OrderLanguage     = $this->model_localisation_language->getLanguage($order['language_id']);
            $LangVars          = $this->loadLanguage($OrderLanguage['directory'].'/extension/module', 'preorder');
            $store['url']      = $this->config->get('config_url');
            $products_html     = '';

            if (!empty($preorder_products)) {
                $products_html .=  '<div style="margin-bottom:20px">';

                for ($i=0; $i<sizeof($preorder_products); $i++) {
                    if (($i+1) == sizeof($preorder_products)) {
                        $products_html .= ' </br> ';
                    } elseif (($i+1) < sizeof($preorder_products) && ($i>0)) {
                        $products_html .= '<span style="color:transparent">,</span> ';
                    }

                    $available_on = '';
                    if (!empty($preorder_products[$i]['preorder_date']) && $preorder_products[$i]['preorder_date'] != '0000-00-00') {
                        $available_on = $LangVars['preorder_available_on'] . date_format(date_create($preorder_products[$i]['preorder_date']), $this->language->get('date_format_short'));
                    }

                    if (isset($preorder['ImageWidth']) && isset($preorder['ImageHeight'])) {
                        $image[$i] = $this->model_tool_image->resize($preorder_products[$i]['image'], $preorder['ImageWidth'], $preorder['ImageHeight']);
                    } else {
                        $image[$i] = $this->model_tool_image->resize($preorder_products[$i]['image'], 200, 200);
                    }

                    $products_html .=  '<div style="display:inline-block;text-align:center;margin:10px">';
                    $products_html .=  '<div>
                                            <a href="' . $store['url'].'index.php?route=product/product&amp;product_id=' . $preorder_products[$i]['product_id'] . '">' . '
                                                <img src="' . str_replace(' ', '%20', $image[$i]) . '" />
                                                <div style="font-size:16px;margin-top:5px;">' . $preorder_products[$i]['name'] . '</div>
                                            </a>
                                        </div>';
                    if ($available_on) {
                        $products_html .=  '<div style="margin-top:5px">' . $available_on . '</div>';
                    }
                    $products_html .=  '<div style="text-align:left;margin-top:5px;padding-top:5px;border-top:1px solid #ddd;">';
                    $products_html .=  '    <div style="padding:0 15px;">';

                    $products_html .=  $LangVars['preorder_quantity'] . $preorder_products[$i]['quantity'];
                    if ($preorder_products[$i]['options']) {
                        $products_html .=  '<br>' . $LangVars['preorder_options'];
                        foreach ($preorder_products[$i]['options'] as $option) {
                            $products_html .=  '<br>&nbsp;&nbsp;&nbsp;- ' . $option['name'] . ': ' . $option['value'];
                        }
                    }

                    $products_html .=  '    </div>';
                    $products_html .=  '</div>';
                    $products_html .=  '</div>';
                }

                $products_html .=  '</div>';
            }

            $messageToCustomer = html_entity_decode($preorder['EmailText'][$order['language_code']], ENT_QUOTES, 'UTF-8');
            $wordTemplates = array("{firstname}", "{lastname}", "{products}");
            $words   = array($order['firstname'], $order['lastname'], $products_html);

            $messageToCustomer = str_replace($wordTemplates, $words, $messageToCustomer);

            $mailToUser = new Mail($this->config->get('config_mail_engine'));
            $mailToUser->protocol = $this->config->get('config_mail_protocol');
            $mailToUser->parameter = $this->config->get('config_mail_parameter');
            $mailToUser->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mailToUser->smtp_username = $this->config->get('config_mail_smtp_username');
            $mailToUser->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mailToUser->smtp_port = $this->config->get('config_mail_smtp_port');
            $mailToUser->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

            $mailToUser->setFrom($this->config->get('config_email'));
            $mailToUser->setSender($order['store_name']);
            $mailToUser->setHtml($messageToCustomer);

            if ($preorder['CustomerNotifications'] == 'yes') {
                $mailToUser->setTo($order['email']);
                $mailToUser->setSubject(html_entity_decode($preorder['EmailSubject'][$order['language_code']], ENT_QUOTES, 'UTF-8'));
                $mailToUser->send();
            }

            // Admin notification
            if ($preorder['Notifications'] == 'yes') {
                $messageToAdmin = html_entity_decode($preorder['AdminEmailText'][$order['language_code']], ENT_QUOTES, 'UTF-8');
                $wordTemplates  = array("{store_owner}", "{customer_name}", "{products}");
                $words          = array($this->config->get('config_owner'), $order['firstname'] . ' ' . $order['lastname'], $products_html);

                $messageToAdmin = str_replace($wordTemplates, $words, $messageToAdmin);

                $mailToUser->setTo($this->config->get('config_email'));
                $mailToUser->setSubject(html_entity_decode($preorder['AdminEmailSubject'][$order['language_code']], ENT_QUOTES, 'UTF-8'));
                $mailToUser->setHtml($messageToAdmin);
                $mailToUser->send();
            }
        }
    }

    public function GetPreorderedProduct($product_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "preorder_product` WHERE product_id = ".(int)$product_id." AND language_id='".(int)$this->config->get('config_language_id')."'");

        if (isset($query->num_rows) && $query->num_rows > 0) {
            return $query->row;
        }
    }

    public function GetPreorderedProducts($order_id, $store_id = 0) {
        $query = $this->db->query("SELECT po.*, pd.name, pp.preorder_date, p.image, op.quantity FROM `" . DB_PREFIX . "preorder` as po
            LEFT JOIN `" . DB_PREFIX . "product_description` as pd ON (po.product_id = pd.product_id AND po.language_id=pd.language_id)
            LEFT JOIN `" . DB_PREFIX . "preorder_product` as pp ON (po.product_id = pp.product_id AND po.language_id = pp.language_id)
            LEFT JOIN `" . DB_PREFIX . "product` as p ON (po.product_id = p.product_id)
            LEFT JOIN `" . DB_PREFIX . "order_product` as op ON (po.order_product_id = op.order_product_id)
            WHERE po.order_id = ".(int)$order_id."
                AND po.language_id = " . (int)$this->config->get('config_language_id') . "
                AND store_id=".(int)$store_id);

        $products = array();
        for ($i=0; $i < $query->num_rows; $i++) {
            $products[$i] = $query->rows[$i];
            $products[$i]['options'] = $this->db->query("SELECT name, value FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$query->rows[$i]['order_id'] . "' AND order_product_id = '" . (int)$query->rows[$i]['order_product_id'] . "'")->rows;
        }

        return $products;
    }

    public function loadLanguage($directory, $filename) {
        $default = 'en-gb/extension/module';
        $data = array();

        $file = DIR_LANGUAGE . $directory . '/' . $filename . '.php';
        if (file_exists($file)) {
            $_ = array();

            require($file);
            $data = array_merge($data, $_);
            return $data;
        }

        $file = DIR_LANGUAGE . $default . '/' . $filename . '.php';
        if (file_exists($file)) {
            $_ = array();
            require($file);
            $data = array_merge($data, $_);
            return $data;
        } else {
            trigger_error('Error: Could not load language ' . $filename . '!');
        }
    }
}
