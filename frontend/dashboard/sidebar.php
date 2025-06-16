<?php
function aerp_menu_active($slug) {
    return strpos($_SERVER['REQUEST_URI'], $slug) !== false ? 'active' : '';
}
?>
<div class="col-md-3 col-lg-2 dashboard-sidebar p-0">
    <div class="p-3 text-center d-flex flex-wrap align-items-center gap-2 justify-content-center">
        <img src="<?php echo AERP_CRM_URL . 'assets/images/logo.png'; ?>" alt="Logo" class="logo" style="width: 50px; margin-bottom: 10px;">
        <h4>CRM Dashboard</h4>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo aerp_menu_active('aerp-crm-dashboard'); ?>" href="<?php echo home_url('/aerp-crm-dashboard'); ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a class="nav-link <?php echo aerp_menu_active('aerp-crm-categories'); ?>" href="<?php echo home_url('/aerp-crm-categories'); ?>">
            <i class="fas fa-th-large me-2"></i> Danh Mục
        </a>
        <a class="nav-link <?php echo aerp_menu_active('aerp-crm-customers'); ?>" href="<?php echo home_url('/aerp-crm-customers'); ?>">
            <i class="fas fa-users me-2"></i> Khách Hàng
        </a>
        <a class="nav-link <?php echo aerp_menu_active('aerp-crm-logs'); ?>" href="<?php echo home_url('/aerp-crm-logs'); ?>">
            <i class="fas fa-history me-2"></i> Lịch Sử Tương Tác
        </a>
        <a class="nav-link <?php echo aerp_menu_active('aerp-crm-attachments'); ?>" href="<?php echo home_url('/aerp-crm-attachments'); ?>">
            <i class="fas fa-paperclip me-2"></i> File Đính Kèm
        </a>
    </nav>
</div> 