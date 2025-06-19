<?php
if (!defined('ABSPATH')) {
    exit;
}

class AERP_Frontend_Customer_Table extends AERP_Frontend_Table
{
    public function __construct()
    {
        parent::__construct([
            'table_name' => $GLOBALS['wpdb']->prefix . 'aerp_crm_customers',
            'columns' => [
                'id' => 'ID',
                'customer_code' => 'Mã khách hàng',
                'full_name' => 'Họ và tên',
                'company_name' => 'Tên công ty',
                'tax_code' => 'Mã số thuế',
                'email' => 'Email',
                'customer_type' => 'Loại khách hàng',
                'status' => 'Trạng thái',
                'assigned_to' => 'Nhân viên phụ trách',
                'created_at' => 'Ngày tạo',
            ],
            'sortable_columns' => ['id', 'full_name', 'customer_code', 'created_at'],
            'searchable_columns' => ['full_name', 'customer_code', 'email', 'company_name'],
            'primary_key' => 'id',
            'per_page' => 10,
            'actions' => ['edit', 'delete'],
            'bulk_actions' => ['delete'],
            'base_url' => home_url('/aerp-crm-customers'),
            'delete_item_callback' => ['AERP_Frontend_Customer_Manager', 'delete_customer_by_id'],
            'nonce_action_prefix' => 'delete_customer_',
            'message_transient_key' => 'aerp_customer_message',
            'hidden_columns_option_key' => 'aerp_crm_customer_table_hidden_columns',
        ]);
    }

    /**
     * Hiển thị tên người phụ trách thay vì ID
     */
    protected function column_assigned_to($item)
    {
        $assigned_to_id = $item->assigned_to;
        $employee_name = aerp_get_customer_assigned_name($assigned_to_id); // Hàm đã có để lấy tên nhân viên
        return esc_html($employee_name);
    }

    /**
     * Hiển thị cột full_name với liên kết đến trang chi tiết khách hàng
     */
    protected function column_full_name($item)
    {
        $detail_url = home_url('/aerp-crm-customers/' . $item->id);
        return sprintf('<a class="text-decoration-none" href="%s">%s</a>', esc_url($detail_url), esc_html($item->full_name));
    }

    /**
     * Hiển thị loại khách hàng thân thiện hơn
     */
    protected function column_customer_type($item)
    {
        global $wpdb;
        $type = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aerp_crm_customer_types WHERE id = %d", $item->customer_type_id));
        if ($type) {
            $color = !empty($type->color) ? $type->color : 'secondary';
            return '<span class="badge bg-' . esc_attr($color) . '">' . esc_html($type->name) . '</span>';
        }
        return '<span class="badge bg-secondary">Không xác định</span>';
    }

    /**
     * Hiển thị trạng thái khách hàng thân thiện hơn
     */
    protected function column_status($item)
    {
        $statuses = [
            'active' => '<span class="badge bg-success">Hoạt động</span>',
            'inactive' => '<span class="badge bg-secondary">Không hoạt động</span>',
        ];
        return $statuses[$item->status] ?? esc_html($item->status);
    }
}
