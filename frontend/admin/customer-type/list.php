<?php
$current_user = wp_get_current_user();
$table = new AERP_Frontend_Customer_Type_Table();
$table->process_bulk_action();
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Quản lý loại khách hàng</h2>
    <div class="user-info">
        Welcome, <?php echo esc_html($current_user->display_name); ?>
        <a href="<?php echo wp_logout_url(site_url('/aerp-dang-nhap')); ?>" class="btn btn-sm btn-outline-danger ms-2">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Danh sách loại khách hàng</h5>
        <a href="<?php echo esc_url(home_url('/aerp-crm-customer-types/?action=add')); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Thêm mới
        </a>
    </div>
    <div class="card-body">
        <?php $message = get_transient('aerp_customer_type_message');
        if ($message) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
                . esc_html($message) .
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            delete_transient('aerp_customer_type_message');
        }
        $table->render(); ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Quản lý loại khách hàng';
include(AERP_HRM_PATH . 'frontend/dashboard/layout.php'); 