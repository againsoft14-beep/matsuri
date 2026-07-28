<?php
class ControllerExtensionModuleInventoryModuleReport extends Controller {
    public function index() {
        // ল্যাঙ্গুয়েজ এবং মডেল লোড
        $this->load->language('extension/module/inventory_module/inventory');
        $this->document->setTitle("Inventory Report");
        $this->load->model('extension/module/inventory_module/report');

        // ১. ফিল্টার ডাটা হ্যান্ডলিং (আজকের ডেট বা সিলেক্টেড ডেট)
        $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : date('Y-m-01');
        $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : date('Y-m-d');

        $filter_data = array(
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end
        );

        // ভিউতে ডেট এবং টোকেন পাঠানো
        $data['user_token'] = $this->session->data['user_token'];
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        // ব্রেডক্রাম্বস (ওপেনকার্ট স্ট্যান্ডার্ড)
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => "Inventory Report",
            'href' => $this->url->link('extension/module/inventory_module/report', 'user_token=' . $this->session->data['user_token'], true)
        );

        // ২. ইনভেন্টরি স্ট্যাটাস এবং প্রফিট ডাটা
        $total_value_info = $this->model_extension_module_inventory_module_report->getTotalInventoryValue();
        $data['total_inventory_value'] = $this->currency->format($total_value_info['total_cost'], $this->config->get('config_currency'));

        // এখানে ফিল্টার পাস করা হচ্ছে যাতে নির্দিষ্ট সময়ের প্রফিট দেখা যায়
        $stock_stats = $this->model_extension_module_inventory_module_report->getStockStats($filter_data);
        
        $data['total_items']             = $stock_stats['total_items_count']; 
        $data['low_stock_count']         = $stock_stats['low_stock_items'];
        $data['low_stock_items']      = $stock_stats['low_stock_items'];
        $data['total_lots']              = $stock_stats['total_lots_count'];      
        $data['total_unique_items']      = $stock_stats['total_unique_products']; 
        $data['total_current_stock_qty'] = isset($stock_stats['total_current_quantity']) ? $stock_stats['total_current_quantity'] : 0;

        // প্রফিট এবং মার্জিন ক্যালকুলেশন
        $profit_stats = $this->model_extension_module_inventory_module_report->getOverallProfitStats($filter_data);
        
        if ($profit_stats['total_sales'] > 0) {
            $margin = (($profit_stats['total_sales'] - $profit_stats['total_purchase']) / $profit_stats['total_sales']) * 100;
            $data['gross_profit_margin'] = number_format($margin, 2) . '%';
        } else {
            $data['gross_profit_margin'] = '0.00%';
        }
        
        $gross_profit = $profit_stats['total_profit'];
        $data['total_profit'] = $this->currency->format($gross_profit, $this->config->get('config_currency'));

        // ৩. টপ সেলিং প্রোডাক্টস
        $data['top_selling_products'] = array();
        $top_sellers = $this->model_extension_module_inventory_module_report->getTopSellingProducts(5, $filter_data);
        
        foreach ($top_sellers as $product) {
            $data['top_selling_products'][] = array(
                'name'     => $product['name'],
                'sold_qty' => $product['total_sold'],
                'revenue'  => $this->currency->format($product['total_revenue'], $this->config->get('config_currency'))
            );
        }

        // ৪. প্রফিটেবল লটস
        $data['profitable_lots'] = array();
        $top_lots = $this->model_extension_module_inventory_module_report->getTopProfitableLots(5, $filter_data);
        
        foreach ($top_lots as $lot) {
            $data['profitable_lots'][] = array(
                'lot_number' => $lot['inventory_lotnumber'],
                'profit'     => $this->currency->format($lot['total_profit'], $this->config->get('config_currency')),
                'date'       => date($this->language->get('date_format_short'), strtotime($lot['inventory_date']))
            );
        }

        // ৫. এক্সপেন্স সেকশন
        $data['expenses'] = array();
        $expenses = $this->model_extension_module_inventory_module_report->getExpenses($filter_data);

        foreach ($expenses as $expense) {
            $data['expenses'][] = array(
                'category' => $expense['category'],
                'total'    => $this->currency->format($expense['total'], $this->config->get('config_currency'))
            );
        }

        $total_expense = $this->model_extension_module_inventory_module_report->getTotalExpenseAmount($filter_data);
        $data['total_expense'] = $this->currency->format($total_expense, $this->config->get('config_currency'));

        // ৬. ফাইনাল নিট প্রফিট ক্যালকুলেশন (Gross Profit - Total Expense)
        $final_net_profit = $gross_profit - $total_expense;
        $data['final_net_profit'] = $this->currency->format($final_net_profit, $this->config->get('config_currency'));
        
        $data['categories'] = array();
    $results = $this->model_extension_module_inventory_module_report->getExpensesByCategory($filter_data);

    foreach ($results as $result) {
        $data['categories'][] = array(
            'name'  => $result['category_name'] ? $result['category_name'] : 'Uncategorized',
            'count' => $result['total_count'],
            'total' => $this->currency->format($result['total_amount'], $this->config->get('config_currency'))
        );
    }

    $total_raw = $this->model_extension_module_inventory_module_report->getTotalExpense($filter_data);
    $data['grand_total_expense'] = $this->currency->format($total_raw, $this->config->get('config_currency'));


        // কমন কম্পোনেন্ট এবং আউটপুট
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/inventory_module/report', $data));
    }
}