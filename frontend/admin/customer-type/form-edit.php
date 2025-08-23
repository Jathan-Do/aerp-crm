<?php
if (!defined('ABSPATH')) {
    exit;
}
// Get current user
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

if (!is_user_logged_in()) {
    wp_die(__('You must be logged in to access this page.'));
}

// Danh sách điều kiện, chỉ cần 1 cái đúng là qua
$access_conditions = [
    aerp_user_has_role($user_id, 'admin'),
    aerp_user_has_role($user_id, 'department_lead'),
    aerp_user_has_permission($user_id, 'customer_type_edit'),
];

if (!in_array(true, $access_conditions, true)) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}
$type_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$type = AERP_Frontend_Customer_Type_Manager::get_by_id($type_id);
if (!$type) {
    wp_die(__('Customer type not found.'));
}
ob_start();
?>
<div class="d-flex flex-column-reverse flex-md-row justify-content-between align-items-md-center mb-4">
    <h2>Cập nhật loại khách hàng</h2>
    <div class="user-info text-end">
        Welcome, <?php echo esc_html($current_user->display_name); ?>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-sm btn-outline-danger ms-2">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
<?php
if (function_exists('aerp_render_breadcrumb')) {
    aerp_render_breadcrumb([
        ['label' => 'Trang chủ', 'url' => home_url('/aerp-dashboard'), 'icon' => 'fas fa-home'],
        ['label' => 'Quản lý khách hàng', 'url' => home_url('/aerp-crm-customers')],
        ['label' => 'Quản lý loại khách hàng', 'url' => home_url('/aerp-crm-customer-types')],
        ['label' => 'Cập nhật loại khách hàng']
    ]);
}
?>
<?php $message = get_transient('aerp_customer_type_message');
if ($message) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
        . esc_html($message) .
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    delete_transient('aerp_customer_type_message');
} ?>
<div class="card">
    <div class="card-body">
        <form method="post">
            <?php wp_nonce_field('aerp_save_customer_type_action', 'aerp_save_customer_type_nonce'); ?>
            <input type="hidden" name="type_id" value="<?php echo esc_attr($type_id); ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="type_key" class="form-label">Mã loại</label>
                    <input type="text" class="form-control shadow-sm" id="type_key" name="type_key" value="<?php echo esc_attr($type->type_key); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Tên loại</label>
                    <input type="text" class="form-control shadow-sm" id="name" name="name" value="<?php echo esc_attr($type->name); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="color" class="form-label">Màu sắc</label>
                    <input type="color" class="form-control shadow-sm form-control-color" id="color" name="color" value="<?php echo esc_attr($type->color ?: '#007bff'); ?>">
                    <div class="form-text">Màu sắc để phân biệt nguồn</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea class="form-control shadow-sm" id="description" name="description" rows="3"><?php echo esc_textarea($type->description); ?></textarea>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="aerp_save_customer_type" class="btn btn-primary">Cập nhật</button>
                <a href="<?php echo home_url('/aerp-crm-customer-types'); ?>" class="btn btn-secondary">Quay lại</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Cập nhật loại khách hàng';
include(AERP_HRM_PATH . 'frontend/dashboard/layout.php');
