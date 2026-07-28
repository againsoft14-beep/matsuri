<?php
// Heading
$_['heading_title']                 = 'PreOrder';

// Text
$_['text_module']                   = 'Modules';
$_['text_module_settings']          = 'Module settings:';
$_['text_module_settings_help']     = 'Enable or disable Pre-Order.';
$_['text_success_modify']           = 'Success: You have modified module Pre-Order!';
$_['text_success_activation']       = 'ACTIVATED: You have successfully activated Pre-Order!';
$_['text_enabled']                  = 'Enabled';
$_['text_disabled']                 = 'Disabled';
$_['text_button_name']              = 'Pre-Order Button Name:';
$_['text_order_id']                 = 'Order ID';
$_['text_pre_order']                = 'Pre-Order';
$_['text_pre_order_note']           = 'Pre-Order Note';
$_['text_pre_order_note_help']      = 'The text to be displayed next to the pre-ordered products in the cart. Use this codes:<br /><br />{date_available} - Product Date Available<br />';
$_['text_date_note']                = 'Add Note on the PreOrder Product Page';
$_['text_date_note_help']           = "Add custom note on the PreOrder Product Page. If left blank, the note won't be displayed. </br> Special codes: </br> <strong>{preorder_date}</strong> - will show the preorder date </br><strong>{preorder_note}</strong> - will show the preorder note </br><strong>{preorder_quantity}</strong> - show remaining preorder quantity";
$_['text_date_note_example']        = "This Product is a Pre-Order and will be shipped on: {preorder_date} !";
$_['text_admin_notification']       = 'Admin Notification by Email:';
$_['text_admin_notification_help']  = 'You will receive an email when someone pre-orders a product.';
$_['text_customer_notification']    = 'Customer Notification by Email:';
$_['text_customer_notification_help'] = 'The customer will receive a separate notification by email with the PreOrder products.';
$_['text_admin_email']              = 'Admin Notification Email Text:';
$_['text_admin_email_help']         = 'Use this codes:<br /><br />
                                        {store_owner} - Store owner name<br />
                                        {customer_name} - Customer name<br />
                                        {products} - Pre-Ordered products<br />';
$_['text_admin_email_subject']      = 'Admin Pre-Order Notification';
$_['text_admin_email_body']         = ' <p align="center"><b>Hello, {store_owner}!</b></p>
                                        <p align="center">The customer {customer_name} pre-ordered the following products:</p>
                                        <div style="text-align:center;">{products}</div>
                                        <p align="center"><strong>Please, be aware that the products will be shipped once they become available.';
$_['text_email']                    = 'Email Text:';
$_['text_email_help']               = 'Use this codes:<br /><br />
                                        {firstname} - Customer First Name<br />
                                        {lastname} - Customer Last Name<br />
                                        {products} - Pre-Ordered Products<br />';
$_['text_email_subject']            = 'Email Subject:';
$_['text_email_body']               = ' <p align="center"><b>Hello, {firstname} {lastname}!</b></p>
                                        <p align="center">We are happy to notify you that you have pre-ordered the following products:</p>
                                        <div style="text-align:center;">{products}</div>
                                        <p align="center"><strong>Please, be aware that your products will be shipped once they become available.';
$_['text_custom_css']               = 'Custom CSS:';
$_['text_custom_colors']            = 'PreOrder Note Custom Colors:';
$_['text_custom_colors_help']       = 'Customize the note design on the PreOrder product page by adding colors for the text, background and border of your choice.';
$_['text_text']                     = 'Text';
$_['text_background']               = 'Background';
$_['text_border']                   = 'Border';
$_['text_customer_email']           = 'Customer E-mail';
$_['text_customer_name']            = 'Customer Name';
$_['text_product']                  = 'Product';
$_['text_date']                     = 'Date';
$_['text_language']                 = 'Language';
$_['text_actions']                  = 'Actions';
$_['text_remove']                   = 'Remove';
$_['text_remove_all']               = 'Remove all';
$_['text_module_status']            = 'Enable for the following Out-of-Stock statuses';
$_['text_module_status_help']       = 'Enable the module for products with the following Out-of-Stock statuses';
$_['text_notified_customers']       = 'Notified Customers';
$_['text_notified_customers_help']  = 'Notified Customers are users who have received an email once their order status has been changed to "Completed". <br><br>In order to notify your customers you need to go to <code>Sales > Orders > View > Order History > tick Notify Customer checkbox</code> then click <b>Add History</b>.';

$_['text_image_size']               = 'Image Size';
$_['text_image_size_help']          = 'Define size of the product image in the PreOrder email';
$_['text_width']                    = 'Width';
$_['text_height']                   = 'Height';

$_['error_allow_checkout']          = 'The Out-of-Stock Products Checkout is currently disabled. In order to use the module you need to enable it from System >> Settings >> Option >> Stock Checkout => Yes ';

//Others
$_['notifywhenavailable_enabled']   = 'Please, note that you have also enabled <strong>NotifyWhenAvailable</strong>. The PreOrder module is enabled only for products with the selected in the PreOrder Contol Panel tab Out-of-Stock statuses. The NotifyWhenAvailable module is enabled for all of the rest Out-of-Stock products.';
$_['pre_order_note']                = 'Available on {date_available}';

$_['text_valid_license']            = 'VALID LICENSE';
$_['text_manage']                   = 'manage';
$_['text_registered_domains']       = 'Registered domains';
