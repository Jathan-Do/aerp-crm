<?php

/**
 * Plugin Name: AERP CRM – Quản lý khách hàng
 * Description: Module quản lý khách hàng và bán hàng của hệ thống AERP.
 * Version: 1.0.0
 * Author: Truong Thinh Group
 * Text Domain: aerp-crm
 */

if (!defined('ABSPATH')) exit;

// Constants
define('AERP_CRM_PATH', plugin_dir_path(__FILE__));
define('AERP_CRM_URL', plugin_dir_url(__FILE__));
define('AERP_CRM_VERSION', '1.0.0');

// Kiểm tra bản Pro
if (!function_exists('aerp_crm_is_pro')) {
    function aerp_crm_is_pro()
    {
        return function_exists('aerp_is_pro_module') && aerp_is_pro_module('crm');
    }
}

// Khởi tạo plugin
function aerp_crm_init()
{
    // Load func dùng chung
    require_once AERP_CRM_PATH . 'includes/functions-common.php';

    // Table 
    require_once AERP_CRM_PATH . 'includes/table/class-base-table.php';
    require_once AERP_CRM_PATH . 'includes/table/table-customer.php';
    require_once AERP_CRM_PATH . 'includes/table/table-lead.php';

    // Load các class cần thiết manager
    $includes = [
        'class-customer-manager.php',
        'class-lead-manager.php',
        'class-settings-manager.php',
    ];
    foreach ($includes as $file) {
        require_once AERP_CRM_PATH . 'includes/managers/' . $file;
    }

    // Xử lý form và logic
    $managers = [
        'AERP_Customer_Manager',
        'AERP_Lead_Manager',
        'AERP_Settings_Manager',
    ];
    foreach ($managers as $manager) {
        if (method_exists($manager, 'handle_submit')) {
            add_action('admin_init', [$manager, 'handle_submit']);
        }
        if (method_exists($manager, 'handle_form_submit')) {
            add_action('admin_init', [$manager, 'handle_form_submit']);
        }
        if (method_exists($manager, 'handle_delete')) {
            add_action('admin_init', [$manager, 'handle_delete']);
        }
    }

    // Admin menu
    if (is_admin()) {
        add_action('admin_menu', ['AERP_CRM_Settings_Manager', 'register_admin_menu']);
    }

    // Tải asset admin
    add_action('admin_enqueue_scripts', function () {
        $version = time();
        wp_enqueue_style('aerp-crm-backend', AERP_CRM_URL . 'assets/css/backend.css', [], $version);
        wp_enqueue_script('aerp-crm-admin', AERP_CRM_URL . 'assets/js/admin.js', ['jquery'], $version, true);
    }, 1);
}
add_action('plugins_loaded', 'aerp_crm_init');

// Đăng ký database khi kích hoạt
register_activation_hook(__FILE__, function () {
    require_once AERP_CRM_PATH . 'install-schema.php';
    aerp_crm_install_schema();
    flush_rewrite_rules();
});

// Xóa database khi deactivate
register_deactivation_hook(__FILE__, function () {
    // Có thể thêm logic xóa database nếu cần
    flush_rewrite_rules();
}); 