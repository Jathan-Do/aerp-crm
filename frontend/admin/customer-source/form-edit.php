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
    aerp_user_has_permission($user_id, 'customer_source_edit'),
];

if (!in_array(true, $access_conditions, true)) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

$edit_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$editing = AERP_Frontend_Customer_Source_Manager::get_by_id($edit_id);
if (!$editing) wp_die(__('Customer source not found.'));

ob_start();
?>

<div class="d-flex flex-column-reverse flex-md-row justify-content-between align-items-md-center mb-4">
    <h2>Cập nhật nguồn khách hàng</h2>
    <div class="user-info text-end">
        Welcome, <?php echo esc_html($current_user->display_name); ?>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-sm btn-outline-danger ms-2">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="post">
            <?php wp_nonce_field('aerp_save_customer_source_action', 'aerp_save_customer_source_nonce'); ?>
            <input type="hidden" name="source_id" value="<?php echo esc_attr($edit_id); ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="source_key" class="form-label">Mã nguồn <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="source_key" name="source_key" value="<?php echo esc_attr($editing->source_key); ?>" required>
                    <div class="form-text">Mã duy nhất để định danh nguồn (ví dụ: fb, zalo, web)</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Tên nguồn <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo esc_attr($editing->name); ?>" required>
                    <div class="form-text">Tên hiển thị của nguồn (ví dụ: Facebook, Zalo, Website)</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="color" class="form-label">Màu sắc</label>
                    <input type="color" class="form-control form-control-color" id="color" name="color" value="<?php echo esc_attr($editing->color ?: '#007bff'); ?>">
                    <div class="form-text">Màu sắc để phân biệt nguồn</div>
                </div>
                <div class="col-12 mb-3">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo esc_textarea($editing->description); ?></textarea>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="aerp_save_customer_source" class="btn btn-primary">Cập nhật</button>
                <a href="<?php echo home_url('/aerp-crm-customer-sources'); ?>" class="btn btn-secondary">Quay lại</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Cập nhật nguồn khách hàng';
include(AERP_HRM_PATH . 'frontend/dashboard/layout.php');
?>
