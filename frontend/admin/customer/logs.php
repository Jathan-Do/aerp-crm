<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$employee = aerp_get_employee_by_user_id($user_id);
$user_fullname = $employee ? $employee->full_name : '';

// Check if user is logged in and has admin capabilities
if (!is_user_logged_in() || !aerp_user_has_role($user_id, 'admin')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}
$customer_id = get_query_var('aerp_crm_customer_id');
$customer = null;
if ($customer_id) {
    $customer = AERP_Frontend_Customer_Manager::get_by_id($customer_id);
}
$customer_logs_table = new AERP_Frontend_Customer_Logs_Table($customer_id);
$customer_logs_table->process_bulk_action();
ob_start();
?>
<div class="d-flex flex-column-reverse flex-md-row justify-content-between align-items-md-center mb-5">
    <h2 class="mb-0">Lịch sử tương tác của khách hàng: <?php echo esc_html($customer ? $customer->full_name : ''); ?> - <?php echo esc_html($customer ? $customer->customer_code : ''); ?></h2>
    <div class="user-info text-end">
        <span class="me-2">Hi, <?php echo esc_html($user_fullname); ?></span>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>
    </div>
</div>
<?php
if (function_exists('aerp_render_breadcrumb')) {
    aerp_render_breadcrumb([
        ['label' => 'Trang chủ', 'url' => home_url('/aerp-dashboard'), 'icon' => 'fas fa-home'],
        ['label' => 'Quản lý khách hàng', 'url' => home_url('/aerp-crm-customers')],
        ['label' => 'Thông tin chi tiết khách hàng', 'url' => home_url('/aerp-crm-customers/' . $customer_id)],
        ['label' => 'Lịch sử tương tác của khách hàng']
    ]);
}
?>
<div class="card shadow-sm">
    <div class="card-body">
        <!-- Filter Form -->
        <form id="aerp-customer-log-filter-form" class="row g-2 mb-3 aerp-table-ajax-form" data-table-wrapper="#aerp-customer-logs-table-wrapper" data-ajax-action="aerp_crm_filter_customer_logs">
            <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer_id); ?>">
            <div class="col-12 col-md-2 mb-2">
                <label for="filter-interaction-type" class="form-label mb-1">Loại tương tác</label>
                <select id="filter-interaction-type" name="interaction_type" class="form-select shadow-sm">
                    <?php
                    $types = aerp_get_customer_interaction_types($customer->id);
                    aerp_safe_select_options($types, '', 'interaction_type', 'interaction_type', true);
                    ?>
                </select>
            </div>
            <div class="col-12 col-md-1 d-flex align-items-end mb-2">
                <button type="submit" class="btn btn-primary w-100">Lọc</button>
            </div>
        </form>
        <?php
        $message = get_transient('aerp_customer_log_message');
        if ($message) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                ' . esc_html($message) . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
            delete_transient('aerp_customer_log_message'); // Xóa transient sau khi hiển thị
        }
        ?>
        <div id="aerp-customer-logs-table-wrapper">
            <?php $customer_logs_table->render(); ?>
        </div>

        <div class="d-flex justify-content-start align-items-center mt-4 gap-2">
            <a href="<?php echo home_url('/aerp-crm-customers/' . $customer->id); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay lại chi tiết
            </a>
            <a href="<?php echo home_url('/aerp-crm-customers'); ?>" class="btn btn-secondary">
                <i class="fas fa-list me-1"></i> Quay lại danh sách
            </a>
        </div>

    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Lịch sử tương tác khách hàng';
include(AERP_HRM_PATH . 'frontend/dashboard/layout.php');
