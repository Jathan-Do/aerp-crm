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
    aerp_user_has_permission($user_id, 'customer_source_add'),
];

if (!in_array(true, $access_conditions, true)) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

ob_start();
?>

<div class="d-flex flex-column-reverse flex-md-row justify-content-between align-items-md-center mb-4">
    <h2>Thêm nguồn khách hàng mới</h2>
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
        ['label' => 'Quản lý nguồn khách hàng', 'url' => home_url('/aerp-crm-customer-sources')],
        ['label' => 'Thêm nguồn khách hàng mới']
    ]);
}
?>
<div class="card">
    <div class="card-body">
        <form method="post">
            <?php wp_nonce_field('aerp_save_customer_source_action', 'aerp_save_customer_source_nonce'); ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="source_key" class="form-label">Mã nguồn <span class="text-danger">*</span></label>
                    <input type="text" class="form-control shadow-sm" id="source_key" name="source_key" required>
                    <div class="form-text">Mã duy nhất để định danh nguồn (ví dụ: fb, zalo, web)</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Tên nguồn <span class="text-danger">*</span></label>
                    <input type="text" class="form-control shadow-sm" id="name" name="name" required>
                    <div class="form-text">Tên hiển thị của nguồn (ví dụ: Facebook, Zalo, Website)</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="color" class="form-label">Màu sắc</label>
                    <input type="color" class="form-control shadow-sm form-control-color" id="color" name="color" value="#007bff">
                    <div class="form-text">Màu sắc để phân biệt nguồn</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea class="form-control shadow-sm" id="description" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="aerp_save_customer_source" class="btn btn-primary">Thêm mới</button>
                <a href="<?php echo home_url('/aerp-crm-customer-sources'); ?>" class="btn btn-secondary">Quay lại</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Thêm nguồn khách hàng mới';
include(AERP_HRM_PATH . 'frontend/dashboard/layout.php');
?>
