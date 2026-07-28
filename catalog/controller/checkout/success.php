<?php
class ControllerCheckoutSuccess extends Controller {
	public function index() {
	    
		$this->load->language('checkout/success');

		if (isset($this->session->data['order_id'])) {
		    $this->session->data['success_order_id'] = $this->session->data['order_id'];
			$this->cart->clear();

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['totals']);
		}
	   

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_basket'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_success'),
			'href' => $this->url->link('checkout/success')
		);

		if ($this->customer->isLogged()) {
			$data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('account/download', '', true), $this->url->link('information/contact'));
		} else {
			$data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
		}
//         if (isset($this->session->data['success_order_id'])) {
// 		    $data['text_message'] .= sprintf($this->language->get('text_order_id'), $this->url->link('account/order/info&order_id=' . $this->session->data['success_order_id'], '', true), $this->session->data['success_order_id']);
// 			unset($this->session->data['success_order_id']);
// 		}

        if (isset($this->session->data['success_order_id'])) {

            $order_id = $this->session->data['success_order_id'];
            
            // Load model
            $this->load->model('account/order');
        
            // Get order info
            $order_info = $this->model_account_order->getOrderInfo($order_id);
        
            if ($order_info) {

                // Basic
                $data['order_id'] = $order_id;
                $data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
                $data['date_added'] = date('d/m/Y', strtotime($order_info['date_added']));
                $data['payment_method'] = $order_info['payment_method'];
                $data['shipping_method'] = $order_info['shipping_method'];
        
                // Address (IMPORTANT: same format)
               
                
                
                // Shipping Address
                	// Shipping Address
			if ($order_info['shipping_address_format']) {
				$format = $order_info['shipping_address_format'];
			} else {
				$format = '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' ;
			}

            $shipping_address = $order_info['shipping_firstname'] . ' ' . $order_info['shipping_lastname'] . '<br />';
            
            $find_fields = array('{company}', '{address_1}', '{address_2}', '{city}', '{postcode}', '{zone}', '{zone_code}');
            
            $replace_fields = array(
                'company'   => $order_info['shipping_company'],
                'address_1' => $order_info['shipping_address_1'],
                'address_2' => $order_info['shipping_address_2'],
                'city'      => $order_info['shipping_city'],
                'postcode'  => $order_info['shipping_postcode'],
                'zone'      => $order_info['shipping_zone'],
                'zone_code' => $order_info['shipping_zone_code'],
            );
            
            $address_raw = str_replace($find_fields, $replace_fields, $format);
            
            $address_body = str_replace(array("\r\n", "\r", "\n"), ', ', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), ', ', trim($address_raw)));
            
            $address_body = trim(preg_replace('/,\s*,+/', ', ', $address_body), ', ');
            
            $shipping_address .= $address_body;
            
            if ($order_info['email']) {
                $shipping_address .= '<br />Email: ' . $order_info['email'];
            }
            if ($order_info['telephone']) {
                $shipping_address .= '<br />Phone: ' . $order_info['telephone'];
            }
            if ($order_info['secondary_telephone']) {
                $shipping_address .= ', ' . $order_info['secondary_telephone'];
            }
            
    
            
            $data['shipping_address'] = $shipping_address;
        
                // Products
                $data['products'] = [];
        
                $results = $this->model_account_order->getOrderProducts($order_id);
        
                foreach ($results as $result) {
        
                    $option_data = [];
        
                    $options = $this->model_account_order->getOrderOptions($order_id, $result['order_product_id']);
        
                    foreach ($options as $option) {
                        $option_data[] = [
                            'name'  => $option['name'],
                            'value' => $option['value']
                        ];
                    }
        
                    $data['products'][] = [
                        'name'     => $result['name'],
                        'model'    => $result['model'],
                        'option'   => $option_data,
                        'quantity' => $result['quantity'],
                        'price'    => $this->currency->format($result['price'], $order_info['currency_code']),
                        'total'    => $this->currency->format($result['total'], $order_info['currency_code']),
                        'reorder'  => false,
                        'return'   => ''
                    ];
                }
        
                // Vouchers
                $data['vouchers'] = [];
        
                // Totals
                $data['totals'] = [];
        
                $totals = $this->model_account_order->getOrderTotals($order_id);
        
                foreach ($totals as $total) {
                    $data['totals'][] = [
                        'title' => $total['title'],
                        'text'  => $this->currency->format($total['value'], $order_info['currency_code'])
                    ];
                }
        
                // Comment
                $data['comment'] = nl2br($order_info['comment']);
            }
            
                  
            unset($this->session->data['success_order_id']);
        }

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

        
		$this->response->setOutput($this->load->view('common/success', $data));
	}
}