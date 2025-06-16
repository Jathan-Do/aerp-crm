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
 * Lấy danh sách lead
 */
function aerp_get_leads()
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aerp_crm_leads ORDER BY created_at DESC");
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
 * Lấy thông tin lead theo ID
 */
function aerp_get_lead($lead_id)
{
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aerp_crm_leads WHERE id = %d",
        $lead_id
    ));
}

/**
 * Lấy danh sách liên hệ của khách hàng
 */
function aerp_get_customer_contacts($customer_id)
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aerp_crm_contacts WHERE customer_id = %d ORDER BY is_primary DESC",
        $customer_id
    ));
}

/**
 * Lấy danh sách cơ hội của khách hàng
 */
function aerp_get_customer_opportunities($customer_id)
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aerp_crm_opportunities WHERE customer_id = %d ORDER BY created_at DESC",
        $customer_id
    ));
}

/**
 * Lấy danh sách hoạt động của khách hàng
 */
function aerp_get_customer_activities($customer_id)
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aerp_crm_activities WHERE customer_id = %d ORDER BY due_date ASC",
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
    return $employee ? $employee->full_name : '';
}

/**
 * Render select options an toàn
 */
function aerp_safe_select_options($items, $selected = '', $key = 'id', $label = 'name', $show_all_option = false)
{
    if ($show_all_option) {
        echo '<option value="">-- Tất cả --</option>';
    }
    foreach ((array)$items as $item) {
        if (!is_object($item) || !isset($item->$key) || !isset($item->$label)) continue;
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($item->$key),
            selected($selected, $item->$key, false),
            esc_html($item->$label)
        );
    }
}

/**
 * Render thông báo nâng cấp lên Pro
 */
function aerp_render_pro_block($feature_name = 'tính năng này', $module_name = 'AERP')
{
    echo '<div class="aerp-pro-warning" style="border:1px solid #ccd0d4; background:#fff3cd; padding:20px; margin-top:10px;">';
    echo '<h3 style="margin-top:0;">🔒 Tính năng Pro</h3>';
    echo '<p>' . sprintf('Chức năng <strong>%s</strong> chỉ khả dụng khi nâng cấp lên bản <strong>%s</strong>.', esc_html($feature_name), esc_html($module_name)) . '</p>';
    echo '<p><a href="' . esc_url(admin_url('admin.php?page=aerp_license')) . '" class="button button-primary">Nâng cấp ngay</a></p>';
    echo '</div>';
}

// Allow plugin modules to apply filters
add_filter('aerp_get_customers', 'aerp_get_customers');
add_filter('aerp_get_leads', 'aerp_get_leads'); 