<?php
// Heading
$_['heading_title']      = 'Inventory Manager';

// Text
$_['text_success']       = 'Success: You have added a new inventory lot!';
$_['text_success_edit']  = 'Success: You have modified the inventory lot!';
$_['text_success_delete']= 'Success: You have deleted the selected records!';
$_['text_list']          = 'Inventory List';
$_['text_add']           = 'Add Inventory Lot';
$_['text_edit']          = 'Edit Inventory Lot';
$_['text_filter']        = 'Filter';
$_['text_no_results']    = 'No records found!';
$_['text_confirm']       = 'Are you sure?';

// Column
$_['column_date']        = 'Date';
$_['column_lotnumber']   = 'Lot Number';
$_['column_products']    = 'Total Products';
$_['column_damage_quantity'] = 'Damage Qty';
$_['column_price']       = 'Total Price';
$_['column_status']      = 'Status';
$_['column_action']      = 'Action';
$_['column_name']        = 'Product Name';
$_['column_quantity']    = 'Quantity';
$_['column_current_quantity'] = 'Current Qty';
$_['column_purchase_price'] = 'Purchase Price';
$_['column_additional_cost'] = 'Additional Cost';
$_['column_total_cost']  = 'Total Cost';
$_['column_sale_price']  = 'Sale Price';
$_['column_total_profit'] = 'Total Profit';
$_['column_remarks']     = 'Remarks';

// Entry
$_['entry_date_start']   = 'Start Date';
$_['entry_date_end']     = 'End Date';
$_['entry_lotnumber']    = 'Lot Number';
$_['entry_status']       = 'Status';
$_['entry_supplier']     = 'Supplier Name';
$_['entry_product']      = 'Choose Product';
$_['entry_damage_quantity'] = 'Damage Quantity';

// Statuses
$_['text_pending']       = 'Pending';
$_['text_upcoming']      = 'Upcoming';
$_['text_received']      = 'Received';

// Error
$_['error_permission']   = 'Warning: You do not have permission to modify inventory!';
$_['error_lotnumber']    = 'Lot Number must be between 1 and 64 characters!';
$_['error_lot_exists']   = 'Error: Lot number already exists. Please use a unique one!';
$_['error_supplier']     = 'Supplier is required!';
$_['error_product']      = 'Warning: You must add at least one product to the lot!';
$_['error_warning']      = 'Warning: Please check the form carefully for errors!';



// Error
$_['error_permission']       = 'Warning: You do not have permission to modify inventories!';
$_['error_warehouse']        = 'Please select a warehouse!';
$_['error_product_required'] = 'At least one product is required!';
$_['error_quantity']         = 'Quantity must be greater than 0!';
$_['error_purchase_price']   = 'Purchase price must be greater than 0!';
$_['error_sale_price']       = 'Sale price must be greater than 0!';
$_['error_additional_cost']  = 'Additional cost cannot be empty (0 if none)!';
$_['error_damage_quantity']  = 'Damage quantity cannot be negative or more than total quantity!';