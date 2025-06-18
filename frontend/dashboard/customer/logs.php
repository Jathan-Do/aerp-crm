<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
// Check if user is logged in and has admin capabilities (adjust as needed for CRM roles)
if (!is_user_logged_in() || !aerp_user_has_role($user_id, 'admin')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

$customer_id = get_query_var('aerp_crm_customer_id');
$customer = null;
if ($customer_id) {
    $customer = AERP_Frontend_Customer_Manager::get_by_id($customer_id);
}

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Lịch sử tương tác của khách hàng: <?php echo esc_html($customer ? $customer->full_name : ''); ?>- <?php echo esc_html($customer ? $customer->customer_code : ''); ?></h2>
    <div class="user-info">
        <span class="me-2">Welcome, <?php echo esc_html($current_user->display_name); ?></span>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if ($customer) : ?>
            <?php
            $message = get_transient('aerp_customer_log_message');
            if ($message) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                ' . esc_html($message) . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
                delete_transient('aerp_customer_log_message'); // Xóa transient sau khi hiển thị
            }
            require_once AERP_CRM_PATH . '../aerp-hrm/frontend/includes/table/class-frontend-table.php';
            $customer_logs_table = new AERP_Frontend_Customer_Logs_Table($customer->id);
            $customer_logs_table->process_bulk_action();
            $customer_logs_table->render();
            ?>

            <div class="card-body d-flex justify-content-between align-items-center mt-4">
                <a href="<?php echo home_url('/aerp-crm-customers/' . $customer->id); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại chi tiết khách hàng
                </a>
                <a href="<?php echo home_url('/aerp-crm-customers'); ?>" class="btn btn-secondary">
                    <i class="fas fa-list me-1"></i> Quay lại danh sách khách hàng
                </a>
            </div>

        <?php else : ?>
            <div class="alert alert-warning" role="alert">
                Không tìm thấy khách hàng.
            </div>
            <a href="<?php echo home_url('/aerp-crm-customers'); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách
            </a>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Lịch sử tương tác khách hàng';
include(AERP_HRM_PATH . 'frontend/dashboard/layout.php');
