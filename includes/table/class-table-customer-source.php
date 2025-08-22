<?php
if (!defined('ABSPATH')) {
    exit;
}

class AERP_Frontend_Customer_Source_Table extends AERP_Frontend_Table
{
    public function __construct()
    {
        parent::__construct([
            'table_name' => $GLOBALS['wpdb']->prefix . 'aerp_crm_customer_sources',
            'columns' => [
                // 'id' => 'ID',
                'source_key' => 'Mã nguồn',
                'name' => 'Tên nguồn',
                'description' => 'Mô tả',
                'color' => 'Màu sắc',
                'created_at' => 'Ngày tạo',
            ],
            'sortable_columns' => ['id', 'source_key', 'name', 'created_at'],
            'searchable_columns' => ['source_key', 'name'],
            'primary_key' => 'id',
            'per_page' => 10,
            'actions' => ['edit', 'delete'],
            'bulk_actions' => ['delete'],
            'base_url' => home_url('/aerp-crm-customer-sources'),
            'delete_item_callback' => ['AERP_Frontend_Customer_Source_Manager', 'delete_customer_source_by_id'],
            'nonce_action_prefix' => 'delete_customer_source_',
            'message_transient_key' => 'aerp_customer_source_message',
            'hidden_columns_option_key' => 'aerp_crm_customer_source_table_hidden_columns',
            'ajax_action' => 'aerp_crm_filter_customers_source',
            'table_wrapper' => '#aerp-customer-source-table-wrapper',
        ]);
    }

    public function set_filters($filters = [])
    {
        parent::set_filters($filters);
    }

    protected function get_extra_filters()
    {
        $filters = [];
        $params = [];

        if (!empty($this->filters['color'])) {
            $filters[] = 'color = %s';
            $params[] = sanitize_text_field($this->filters['color']);
        }

        return [$filters, $params];
    }

    protected function column_color($item)
    {
        if (!empty($item->color)) {
            return sprintf(
                '<span class="badge" style="background-color: %s; color: white;">%s</span>',
                esc_attr($item->color),
                esc_html($item->color)
            );
        }
        return '<span class="text-muted">--</span>';
    }

}
