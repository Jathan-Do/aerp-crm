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
    aerp_user_has_permission($user_id, 'customer_source_view'),
];

if (!in_array(true, $access_conditions, true)) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

ob_start();
?>
<style>
    .select2-container--default .select2-selection--single {
        border: 1px solid #dee2e6 !important;
        border-radius: 0.375rem !important;
        height: 38px !important;
        min-height: 38px !important;
        padding: 6px 12px !important;
        background: #fff !important;
        font-size: 1rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 24px !important;
        padding-left: 0 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 0.75rem !important;
    }

    /* Phone validation styles */
    .aerp-phone-input.is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    .aerp-phone-input.is-valid {
        border-color: #198754 !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
    }

    .invalid-feedback {
        display: block !important;
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
        margin-bottom: 0.5rem;
    }

    .valid-feedback {
        display: block !important;
        color: #198754;
        font-size: 0.875em;
        margin-top: 0.25rem;
        margin-bottom: 0.5rem;
    }
</style>
<div class="d-flex flex-column-reverse flex-md-row justify-content-between align-items-md-center mb-4">
    <h2>Thêm khách hàng mới</h2>
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
        ['label' => 'Thêm khách hàng mới']
    ]);
}
?>
<div class="card">
    <div class="card-body">
        <form class="aerp-customer-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('aerp_save_customer_action', 'aerp_save_customer_nonce'); ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="full_name" class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="address" class="form-label">Địa chỉ</label>
                            <textarea class="form-control" id="address" name="address" rows="1"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="customer_source_id" class="form-label">Nguồn khách hàng</label>
                            <select class="form-select" id="customer_source_id" name="customer_source_id">
                                <option value="">-- Chọn nguồn --</option>
                                <?php
                                $customer_sources = aerp_get_customer_sources();
                                if ($customer_sources) {
                                    foreach ($customer_sources as $source) {
                                        printf(
                                            '<option value="%s">%s</option>',
                                            esc_attr($source->id),
                                            esc_html($source->name)
                                        );
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="company_name" class="form-label">Tên công ty</label>
                            <input type="text" class="form-control" id="company_name" name="company_name">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="tax_code" class="form-label">Mã số thuế</label>
                            <input type="text" class="form-control" id="tax_code" name="tax_code">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <div id="phone-numbers-container">
                        <div class="phone-input-wrapper">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control aerp-phone-input" name="phone_numbers[0][number]" placeholder="Số điện thoại" required>
                                <div class="input-group-text">
                                    <input class="form-check-input border-secondary mt-0" type="checkbox" name="phone_numbers[0][primary]" value="1"> &nbsp; Chính
                                </div>
                                <input type="text" class="form-control" name="phone_numbers[0][note]" placeholder="Ghi chú">
                                <button type="button" class="btn btn-outline-danger remove-phone-field">Xóa</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary mt-2" id="add-phone-field">Thêm số điện thoại</button>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="attachments" class="form-label">File đính kèm</label>
                    <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="customer_type_id" class="form-label">Loại khách hàng</label>
                    <select class="form-select" id="customer_type_id" name="customer_type_id">
                        <?php
                        $customer_types = aerp_get_customer_types();
                        aerp_safe_select_options($customer_types, '', 'id', 'name', true);
                        ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Trạng thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active">Hoạt động</option>
                        <option value="inactive">Không hoạt động</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="assigned_to" class="form-label">Người phụ trách</label>
                    <select class="form-select employee-select" id="assigned_to" name="assigned_to">
                        <option value="">-- Chọn nhân viên --</option>
                        <?php
                        $employees = aerp_get_employees_with_location();
                        foreach ($employees as $employee) {
                            $display_name = esc_html($employee->full_name);
                            if (!empty($employee->work_location_name)) {
                                $display_name .= ' - ' . esc_html($employee->work_location_name);
                            }
                            printf(
                                '<option value="%s">%s</option>',
                                esc_attr($employee->id),
                                $display_name
                            );
                        }
                        ?>
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label for="note" class="form-label">Ghi chú</label>
                    <textarea class="form-control" id="note" name="note" rows="2"></textarea>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="aerp_save_customer" class="btn btn-primary">Thêm mới</button>
                <a href="<?php echo home_url('/aerp-crm-customers'); ?>" class="btn btn-secondary">Quay lại</a>
            </div>
        </form>
    </div>
</div>
<script>
    // $(".employee-select").select2({
    //     placeholder: "Chọn nhân viên",
    //     allowClear: true,
    //     ajax: {
    //         url: aerp_order_ajax.ajaxurl,
    //         dataType: "json",
    //         delay: 250,
    //         data: function(params) {
    //             return {
    //                 action: "aerp_get_users_by_work_location",
    //                 work_location_id: 0, // Sẽ filter theo branch của user hiện tại trong backend
    //                 q: params.term,
    //             };
    //         },
    //         processResults: function(data) {
    //             return {
    //                 results: data
    //             };
    //         },
    //         cache: true,
    //     },
    //     minimumInputLength: 0,
    // });
</script>
<?php
$content = ob_get_clean();
$title = 'Thêm khách hàng mới';
include(AERP_HRM_PATH . 'frontend/dashboard/layout.php');
