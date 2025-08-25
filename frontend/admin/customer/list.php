<?php
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
    aerp_user_has_permission($user_id, 'customer_view'),
    aerp_user_has_permission($user_id, 'customer_view_full'),
];

if (!in_array(true, $access_conditions, true)) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Process bulk actions
$table = new AERP_Frontend_Customer_Table(); // We will create this class next
$table->process_bulk_action();

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
        box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 24px !important;
        padding-left: 0 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 0.75rem !important;
    }
</style>
<div class="d-flex flex-column-reverse flex-md-row justify-content-between align-items-md-center mb-4">
    <h2>Quản lý khách hàng</h2>
    <div class="user-info text-end">
        Welcome, <?php echo esc_html($current_user->display_name); ?>
        <a href="<?php echo wp_logout_url(site_url('/aerp-dang-nhap')); ?>" class="btn btn-sm btn-outline-danger ms-2">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
<?php
if (function_exists('aerp_render_breadcrumb')) {
    aerp_render_breadcrumb([
        ['label' => 'Trang chủ', 'url' => home_url('/aerp-dashboard'), 'icon' => 'fas fa-home'],
        ['label' => 'Quản lý khách hàng']
    ]);
}
?>
<div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <h5 class="mb-0">Danh sách khách hàng</h5>
        <div class="d-flex gap-2 flex-column flex-md-row">
            <a href="<?php echo esc_url(home_url('/aerp-crm-customer-types')); ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm mới loại khách hàng
            </a>
            <a href="<?php echo esc_url(home_url('/aerp-crm-customer-sources')); ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm mới nguồn khách hàng
            </a>
            <a href="<?php echo esc_url(home_url('/aerp-crm-customers/?action=add')); ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm mới khách hàng
            </a>
        </div>
    </div>
    <div class="card-body">

        <!-- Filter Form -->
        <form id="aerp-customer-filter-form" class="row g-2 mb-3 aerp-table-ajax-form" data-table-wrapper="#aerp-customer-table-wrapper" data-ajax-action="aerp_crm_filter_customers">
            <!-- <div class="col-12 col-md-2 mb-2">
                <label for="filter-customer-type" class="form-label mb-1">Loại khách hàng</label>
                <select id="filter-customer-type" name="customer_type_id" class="form-select shadow-sm">
                    <?php
                    $types = aerp_get_customer_types();
                    aerp_safe_select_options($types, '', 'id', 'name', true);
                    ?>
                </select>
            </div> -->
            <div class="col-12 col-md-2 mb-2">
                <label for="filter-customer-source" class="form-label mb-1">Nguồn khách hàng</label>
                <select id="filter-customer-source" name="customer_source_id" class="form-select shadow-sm">
                    <?php
                    $customer_sources = aerp_get_customer_sources();
                    aerp_safe_select_options($customer_sources, '', 'id', 'name', true);
                    ?>
                </select>
            </div>
            <div class="col-12 col-md-2 mb-2">
                <label for="filter-status" class="form-label mb-1">Trạng thái</label>
                <select id="filter-status" name="status" class="form-select shadow-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Hoạt động</option>
                    <option value="inactive">Không hoạt động</option>
                </select>
            </div>
            <div class="col-12 col-md-2 mb-2">
                <label for="filter-assigned-to" class="form-label mb-1">Nhân viên phụ trách</label>
                <select id="filter-assigned-to" name="assigned_to" class="form-select shadow-sm <?php echo aerp_user_has_role($user_id, 'admin') ? 'employee-select-all' : 'employee-select'; ?>">
                </select>
            </div>
            <div class="col-12 col-md-1 d-flex align-items-end mb-2">
                <button type="submit" class="btn btn-primary w-100">Lọc</button>
            </div>
        </form>
        <?php // Display messages if any (using Transients API)
        $message = get_transient('aerp_customer_message');
        if ($message) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    ' . esc_html($message) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
            delete_transient('aerp_customer_message'); // Xóa transient sau khi hiển thị
        }
        ?>
        <div id="aerp-customer-table-wrapper">
            <?php $table->render(); ?>
        </div>
    </div>
</div>

<!-- <script>
    $(".employee-select").select2({
        placeholder: "Chọn nhân viên",
        allowClear: true,
        ajax: {
            url: aerp_order_ajax.ajaxurl,
            dataType: "json",
            delay: 250,
            data: function(params) {
                return {
                    action: "aerp_get_users_by_work_location",
                    work_location_id: 0, // Sẽ filter theo branch của user hiện tại trong backend
                    q: params.term,
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true,
        },
        minimumInputLength: 0,
    });
</script> -->
<?php
$content = ob_get_clean();
$title = 'Quản lý khách hàng';
include(AERP_HRM_PATH . 'frontend/dashboard/layout.php');
