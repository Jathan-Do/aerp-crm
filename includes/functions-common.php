<?php

// ============================
// COMMON FUNCTIONS FOR CRM MODULE
// ============================

/**
 * Lấy danh sách khách hàng
 */
function aerp_get_customers()
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aerp_crm_customers ORDER BY full_name ASC");
}

/**
 * Lấy thông tin khách hàng theo ID
 */
function aerp_get_customer($customer_id)
{
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aerp_crm_customers WHERE id = %d",
        $customer_id
    ));
}

/**
 * Lấy danh sách tương tác của khách hàng
 */
function aerp_get_customer_logs($customer_id)
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aerp_crm_logs WHERE customer_id = %d ORDER BY created_at DESC",
        $customer_id
    ));
}

/**
 * Lấy danh sách tương tác của khách hàng với giới hạn và offset (dùng cho phân trang)
 */
function aerp_get_customer_logs_paginated($customer_id, $limit = 10, $offset = 0)
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aerp_crm_logs WHERE customer_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $customer_id,
        $limit,
        $offset
    ));
}

/**
 * Lấy danh sách file đính kèm của khách hàng
 */
function aerp_get_customer_attachments($customer_id)
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aerp_crm_attachments WHERE customer_id = %d ORDER BY uploaded_at DESC",
        $customer_id
    ));
}

/**
 * Lấy danh sách số điện thoại của khách hàng
 */
function aerp_get_customer_phones($customer_id)
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aerp_crm_customer_phones WHERE customer_id = %d ORDER BY is_primary DESC",
        $customer_id
    ));
}

/**
 * Lấy số điện thoại chính của khách hàng
 */
function aerp_get_customer_primary_phone($customer_id)
{
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare(
        "SELECT phone_number FROM {$wpdb->prefix}aerp_crm_customer_phones 
        WHERE customer_id = %d AND is_primary = 1 LIMIT 1",
        $customer_id
    ));
}

/**
 * Lấy tên nhân viên phụ trách
 */
function aerp_get_customer_assigned_name($assigned_to)
{
    if (!$assigned_to) return '';
    
    $employee = aerp_get_employee_by_user_id($assigned_to);
    if ($employee) {
        $display_name = $employee->full_name;
        if (!empty($employee->work_location_id)) {
            $work_location_name = aerp_get_work_location_name($employee->work_location_id);
            if (!empty($work_location_name)) {
                $display_name .= ' - ' . $work_location_name;
            }
        }
        return $display_name;
    }
    return '';
}


// Allow plugin modules to apply filters
add_filter('aerp_get_customers', 'aerp_get_customers');
add_filter('aerp_get_customer', 'aerp_get_customer');
add_filter('aerp_get_customer_logs', 'aerp_get_customer_logs');
add_filter('aerp_get_customer_attachments', 'aerp_get_customer_attachments');
add_filter('aerp_get_customer_phones', 'aerp_get_customer_phones');