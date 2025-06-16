<?php
if (!defined('ABSPATH')) exit;
$current_user = wp_get_current_user();
$management_menu = [
    [
        'icon' => 'fa-users',
        'title' => 'Quản lý khách hàng',
        'desc' => 'Thêm, sửa, xóa và quản lý thông tin khách hàng',
        'url' => home_url('/aerp-crm-customers'),
        'color' => 'primary',
    ],
    [
        'icon' => 'fa-phone',
        'title' => 'Số điện thoại',
        'desc' => 'Quản lý số điện thoại và thông tin liên hệ của khách hàng',
        'url' => home_url('/aerp-crm-phones'),
        'color' => 'success',
    ],
    [
        'icon' => 'fa-history',
        'title' => 'Lịch sử tương tác',
        'desc' => 'Xem và quản lý lịch sử tương tác với khách hàng',
        'url' => home_url('/aerp-crm-logs'),
        'color' => 'info',
    ],
    [
        'icon' => 'fa-paperclip',
        'title' => 'File đính kèm',
        'desc' => 'Quản lý các file và tài liệu đính kèm của khách hàng',
        'url' => home_url('/aerp-crm-attachments'),
        'color' => 'warning',
    ],
    [
        'icon' => 'fa-chart-line',
        'title' => 'Báo cáo',
        'desc' => 'Xem báo cáo và thống kê về khách hàng',
        'url' => home_url('/aerp-crm-reports'),
        'color' => 'danger',
    ],
    [
        'icon' => 'fa-cog',
        'title' => 'Cài đặt',
        'desc' => 'Cấu hình và thiết lập hệ thống CRM',
        'url' => home_url('/aerp-crm-settings'),
        'color' => 'secondary',
    ],
];

ob_start();
?>
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-th-large me-2"></i> Danh mục quản lý</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($management_menu as $item): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card category-card h-100">
                        <div class="card-body text-center d-flex flex-column">
                            <i class="fas <?php echo $item['icon']; ?> category-icon text-<?php echo $item['color']; ?>"></i>
                            <h6 class="text-uppercase mt-2"><?php echo $item['title']; ?></h6>
                            <p class="fs-6 text-muted flex-grow-1"><?php echo $item['desc']; ?></p>
                            <a href="<?php echo esc_url($item['url']); ?>" class="btn btn-sm btn-outline-<?php echo $item['color']; ?>">Quản lý</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Danh mục quản lý CRM';
include(AERP_CRM_PATH . 'frontend/dashboard/layout.php'); 