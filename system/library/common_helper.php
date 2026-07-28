<?php
trait CommonHelper {
    protected function getMessages(&$data) {
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        
        if (isset($this->session->data['error_warning'])) {
            $data['error_warning'] = $this->session->data['error_warning'];
            unset($this->session->data['error_warning']); 
        } elseif (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
    }

    protected function getBreadcrumbs(&$data, $items = array()) {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => 'Home',
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        foreach ($items as $text => $route) {
            $data['breadcrumbs'][] = array(
                'text' => $text,
                'href' => $this->url->link($route, 'user_token=' . $this->session->data['user_token'], true)
            );
        }
    }
}