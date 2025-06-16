<?php
if (!defined('ABSPATH')) exit;

// Get current user
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Get dashboard statistics
global $wpdb;
$total_customers = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aerp_crm_customers");
$total_phones = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aerp_crm_customer_phones");
$total_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aerp_crm_logs");
$total_attachments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aerp_crm_attachments");

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Dashboard Overview</h2>
    <div class="user-info">
        Welcome, <?php echo esc_html($current_user->display_name); ?>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-sm btn-outline-danger ms-2">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="stat-card bg-primary text-white">
            <h3><?php echo $total_customers; ?></h3>
            <p class="mb-0">Tổng Khách Hàng</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-success text-white">
            <h3><?php echo $total_phones; ?></h3>
            <p class="mb-0">Số Điện Thoại</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-info text-white">
            <h3><?php echo $total_logs; ?></h3>
            <p class="mb-0">Tương Tác</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-warning text-white">
            <h3><?php echo $total_attachments; ?></h3>
            <p class="mb-0">File Đính Kèm</p>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Hoạt Động Gần Đây</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php
                    $recent_logs = $wpdb->get_results(
                        "SELECT l.*, c.full_name as customer_name 
                        FROM {$wpdb->prefix}aerp_crm_logs l
                        JOIN {$wpdb->prefix}aerp_crm_customers c ON l.customer_id = c.id
                        ORDER BY l.created_at DESC LIMIT 5"
                    );
                    foreach ($recent_logs as $log): ?>
                        <li class="list-group-item">
                            <i class="fas fa-history text-info me-2"></i>
                            <?php echo esc_html($log->customer_name); ?> - 
                            <?php echo esc_html($log->interaction_type); ?>
                            <small class="text-muted float-end">
                                <?php echo date('d/m/Y H:i', strtotime($log->created_at)); ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thao Tác Nhanh</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo home_url('/aerp-crm-customers?action=new'); ?>" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i> Thêm Khách Hàng Mới
                    </a>
                    <a href="<?php echo home_url('/aerp-crm-logs?action=new'); ?>" class="btn btn-success">
                        <i class="fas fa-plus-circle me-2"></i> Ghi Nhận Tương Tác
                    </a>
                    <a href="<?php echo home_url('/aerp-crm-reports'); ?>" class="btn btn-info">
                        <i class="fas fa-chart-bar me-2"></i> Xem Báo Cáo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'AERP CRM Dashboard';
include(AERP_CRM_PATH . 'frontend/dashboard/layout.php'); 