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

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Thêm khách hàng mới</h2>
    <div class="user-info">
        Welcome, <?php echo esc_html($current_user->display_name); ?>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-sm btn-outline-danger ms-2">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('aerp_save_customer_action', 'aerp_save_customer_nonce'); ?>
            <div class="mb-3">
                <label for="customer_code" class="form-label">Mã khách hàng</label>
                <input type="text" class="form-control" id="customer_code" name="customer_code">
            </div>
            <div class="mb-3">
                <label for="full_name" class="form-label">Họ và tên</label>
                <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            <div class="mb-3">
                <label for="company_name" class="form-label">Tên công ty</label>
                <input type="text" class="form-control" id="company_name" name="company_name">
            </div>
            <div class="mb-3">
                <label for="tax_code" class="form-label">Mã số thuế</label>
                <input type="text" class="form-control" id="tax_code" name="tax_code">
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Địa chỉ</label>
                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>
            <!-- Phone Numbers Section -->
            <div class="mb-3">
                <label class="form-label">Số điện thoại</label>
                <div id="phone-numbers-container">
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" name="phone_numbers[0][number]" placeholder="Số điện thoại">
                        <div class="input-group-text">
                            <input class="form-check-input border-secondary mt-0" type="checkbox" name="phone_numbers[0][primary]" value="1"> &nbsp; Chính
                        </div>
                        <input type="text" class="form-control" name="phone_numbers[0][note]" placeholder="Ghi chú">
                        <button type="button" class="btn btn-outline-danger remove-phone-field">Xóa</button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary mt-2" id="add-phone-field">Thêm số điện thoại</button>
            </div>

            <!-- Attachments Section -->
            <div class="mb-3">
                <label for="attachments" class="form-label">File đính kèm</label>
                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
            </div>

            <div class="mb-3">
                <label for="customer_type" class="form-label">Loại khách hàng</label>
                <select class="form-select" id="customer_type" name="customer_type">
                    <option value="individual">Cá nhân</option>
                    <option value="company">Công ty</option>
                    <option value="vip">VIP</option>
                    <option value="reseller">Đại lý</option>
                    <option value="partner">Đối tác</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Trạng thái</label>
                <select class="form-select" id="status" name="status">
                    <option value="active">Hoạt động</option>
                    <option value="inactive">Không hoạt động</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="assigned_to" class="form-label">Người phụ trách</label>
                <select class="form-select" id="assigned_to" name="assigned_to">
                    <option value="">-- Chọn nhân viên --</option>
                    <?php
                    $employees = aerp_get_employees_with_location(); // Lấy danh sách nhân viên
                    foreach ($employees as $employee) {
                        $display_name = esc_html($employee->full_name);
                        if (!empty($employee->work_location_name)) {
                            $display_name .= ' - ' . esc_html($employee->work_location_name);
                        }
                        printf(
                            '<option value="%s">%s</option>',
                            esc_attr($employee->user_id), // Giả sử user_id là ID cần lưu
                            $display_name
                        );
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="note" class="form-label">Ghi chú</label>
                <textarea class="form-control" id="note" name="note" rows="3"></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="aerp_save_customer" class="btn btn-primary">Thêm mới</button>
                <a href="<?php echo home_url('/aerp-crm-customers'); ?>" class="btn btn-secondary">Quay lại</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Thêm khách hàng mới';
include(AERP_HRM_PATH . 'frontend/dashboard/layout.php'); 