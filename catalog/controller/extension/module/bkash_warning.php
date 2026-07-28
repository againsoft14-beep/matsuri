<?php

class ControllerExtensionModuleBkashWarning extends Controller {

    private $error = array();

    public function __construct($registry) {
        parent::__construct($registry);
    }

    public function index() {
        if (isset($this->session->data['bkash_payment_error'])) {
            $data['bkash_payment_error'] = $this->session->data['bkash_payment_error'];
            unset($this->session->data['bkash_payment_error']);
        } else {
            $data['bkash_payment_error'] = '';
        }

        return $this->load->view('extension/module/bkash_warning', $data);  
        
    }

}
    