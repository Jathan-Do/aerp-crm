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
function aerp_get_customers_select2($q = '') {
    global $wpdb;

    $current_user_id = get_current_user_id();

    $where_clauses = [];
    $params = [];

    // Search by name, code, or phone number like table extra search
    if ($q !== '') {
        $q_like = '%' . $wpdb->esc_like($q) . '%';
        $where_clauses[] = '(c.full_name LIKE %s OR c.customer_code LIKE %s OR c.id IN (SELECT customer_id FROM ' . $wpdb->prefix . 'aerp_crm_customer_phones WHERE phone_number LIKE %s))';
        $params[] = $q_like;
        $params[] = $q_like;
        $params[] = $q_like;
    }

    // Permission filtering mirrors AERP_Frontend_Customer_Table::get_extra_filters
    $restricted_where = '';
    $restricted_params = [];

    // If not admin or no explicit assigned_to filter, apply branch/ownership restrictions
    $is_admin = function_exists('aerp_user_has_role') && aerp_user_has_role($current_user_id, 'admin');
    if (!$is_admin) {
        $current_user_employee = $wpdb->get_row($wpdb->prepare(
            "SELECT id, work_location_id FROM {$wpdb->prefix}aerp_hrm_employees WHERE user_id = %d",
            $current_user_id
        ));

        if ($current_user_employee) {
            $can_view_branch = (function_exists('aerp_user_has_permission') && aerp_user_has_permission($current_user_id, 'customer_view_full'));

            if ($can_view_branch && !empty($current_user_employee->work_location_id)) {
                $branch_employee_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}aerp_hrm_employees WHERE work_location_id = %d",
                    $current_user_employee->work_location_id
                ));
                if (!empty($branch_employee_ids)) {
                    $placeholders = implode(',', array_fill(0, count($branch_employee_ids), '%d'));
                    $restricted_where = "(c.assigned_to IN ($placeholders) OR c.assigned_to IS NULL OR c.assigned_to = 0)";
                    $restricted_params = array_merge($restricted_params, array_map('intval', $branch_employee_ids));
                } else {
                    $restricted_where = "(c.assigned_to IS NULL OR c.assigned_to = 0)";
                }
            } else {
                $restricted_where = "(c.assigned_to = %d OR c.created_by = %d OR c.assigned_to IS NULL OR c.assigned_to = 0)";
                $restricted_params[] = (int)$current_user_employee->id;
                $restricted_params[] = (int)$current_user_employee->id;
            }
        }
    }

    if (!empty($restricted_where)) {
        $where_clauses[] = $restricted_where;
        $params = array_merge($params, $restricted_params);
    }

    $where_sql = 'WHERE 1=1';
    if (!empty($where_clauses)) {
        $where_sql .= ' AND ' . implode(' AND ', $where_clauses);
    }

    $sql = "SELECT c.* FROM {$wpdb->prefix}aerp_crm_customers c {$where_sql} ORDER BY c.full_name ASC";

    if (!empty($params)) {
        $sql = $wpdb->prepare($sql, $params);
    }

    return $wpdb->get_results($sql);
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
        "SELECT * FROM {$wpdb->prefix}aerp_crm_logs WHERE customer_id = %d ORDER BY interaction_type DESC",
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

    $employee = aerp_get_employee_by_id($assigned_to);
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

/**
 * Lấy danh sách loại khách hàng
 */
function aerp_get_customer_types()
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aerp_crm_customer_types ORDER BY name ASC");
}

/**
 * Lấy loại khách hàng theo ID
 */
function aerp_get_customer_type($type_id)
{
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aerp_crm_customer_types WHERE id = %d", $type_id));
}

/**
 * Lấy danh sách nhân viên phụ trách (dùng cho filter)
 */
function aerp_get_assigned_employees()
{
    global $wpdb;
    // Lấy các user_id đã từng được gán phụ trách khách hàng
    $user_ids = $wpdb->get_col("SELECT DISTINCT assigned_to FROM {$wpdb->prefix}aerp_crm_customers WHERE assigned_to IS NOT NULL AND assigned_to != ''");
    if (empty($user_ids)) return [];
    $employees = [];
    foreach ($user_ids as $uid) {
        $employees[] = (object)[
            'user_id' => $uid,
            'full_name' => aerp_get_customer_assigned_name($uid)
        ];
    }
    return $employees;
}
function aerp_get_customer_sources()
{
    global $wpdb;
    $table = $wpdb->prefix . 'aerp_crm_customer_sources';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
}

/**
 * Get customer source by ID
 */
function aerp_get_customer_source($id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'aerp_crm_customer_sources';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
}

/**
 * Get customer source by key
 */
function aerp_get_customer_source_by_key($key)
{
    global $wpdb;
    $table = $wpdb->prefix . 'aerp_crm_customer_sources';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE source_key = %s", $key));
}
// Allow plugin modules to apply filters
add_filter('aerp_get_customers', 'aerp_get_customers');
add_filter('aerp_get_customer', 'aerp_get_customer');
add_filter('aerp_get_customer_logs', 'aerp_get_customer_logs');
add_filter('aerp_get_customer_attachments', 'aerp_get_customer_attachments');
add_filter('aerp_get_customer_phones', 'aerp_get_customer_phones');


function aerp_get_customer_interaction_types($customer_id)
{
    global $wpdb;
    return $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT interaction_type FROM {$wpdb->prefix}aerp_crm_logs WHERE customer_id = %d ORDER BY interaction_type ASC",
        $customer_id
    ));
}
