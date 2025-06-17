<?php
// Get current user
$current_user = wp_get_current_user();

// Process bulk actions
$table = new AERP_Frontend_Customer_Table(); // We will create this class next
$table->process_bulk_action();

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Quản lý khách hàng</h2>
    <div class="user-info">
        Welcome, <?php echo esc_html($current_user->display_name); ?>
        <a href="<?php echo wp_logout_url(site_url('/aerp-dang-nhap')); ?>" class="btn btn-sm btn-outline-danger ms-2">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Danh sách khách hàng</h5>
        <a href="<?php echo esc_url(home_url('/aerp-crm-customers/?action=add')); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Thêm mới
        </a>
    </div>
    <div class="card-body">
        <?php // Display messages if any (using Transients API)
        $message = get_transient('aerp_customer_message');
        if ($message) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    ' . esc_html($message) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
            delete_transient('aerp_customer_message'); // Xóa transient sau khi hiển thị
        }
        $table->render(); ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Quản lý khách hàng';
include(AERP_HRM_PATH . 'frontend/dashboard/layout.php'); 