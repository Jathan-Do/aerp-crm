<?php
if (!defined('ABSPATH')) {
    exit;
}

class AERP_Frontend_Customer_Logs_Table extends AERP_Frontend_Table
{
    private $customer_id;

    public function __construct($customer_id)
    {
        parent::__construct([
            'table_name' => $GLOBALS['wpdb']->prefix . 'aerp_crm_logs',
            'columns' => [
                'id' => 'ID',
                'interaction_type' => 'Loại tương tác',
                'content' => 'Nội dung',
                'interacted_by' => 'Nhân viên thực hiện',
                'created_at' => 'Thời gian',
            ],
            'sortable_columns' => ['id', 'interaction_type', 'created_at'],
            'searchable_columns' => ['interaction_type', 'content'],
            'primary_key' => 'id',
            'per_page' => 10,
            'actions' => [], // Không có hành động chỉnh sửa/xóa trực tiếp trên logs table
            'bulk_actions' => ['delete'],
            'base_url' => home_url('/aerp-crm-customers/' . $customer_id . '?action=logs'), // Base URL cho phân trang/tìm kiếm
            'delete_item_callback' => ['AERP_Frontend_Customer_Manager', 'delete_customer_log_by_id'],
            'message_transient_key' => 'aerp_customer_log_message',
            'hidden_columns_option_key' => 'aerp_crm_customer_logs_table_hidden_columns',
        ]);
        $this->customer_id = $customer_id;
    }

    /**
     * Prepare items for the table.
     */
    public function get_items()
    {
        global $wpdb;

        // Build where clause for search and customer_id
        $where = ['customer_id = %d'];
        $params = [$this->customer_id];

        $search_query = '';
        if (isset($_REQUEST['s']) && !empty($_REQUEST['s'])) {
            $search = $wpdb->esc_like(sanitize_text_field($_REQUEST['s']));
            $search_terms = explode(' ', $search);
            $search_clauses = [];
            foreach ($search_terms as $term) {
                $search_clauses[] = "(interaction_type LIKE '%%{$term}%%') OR (content LIKE '%%{$term}%%')";
            }
            $where[] = '(' . implode(' OR ', $search_clauses) . ')';
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $order_by = sanitize_sql_orderby($_REQUEST['orderby'] ?? 'created_at');
        $order = sanitize_sql_orderby($_REQUEST['order'] ?? 'DESC');

        // Get total items
        $total_query = "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}";
        $this->total_items = $wpdb->get_var($wpdb->prepare($total_query, ...$params));

        // Get items with pagination
        $offset = ($this->current_page - 1) * $this->per_page;

        // Validate sort column
        if (!in_array($this->sort_column, $this->sortable_columns)) {
            $this->sort_column = 'created_at'; // Default for logs
        }

        $query = "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$this->sort_column} {$this->sort_order} LIMIT %d OFFSET %d";
        $params[] = $this->per_page;
        $params[] = $offset;

        $this->items = $wpdb->get_results($wpdb->prepare($query, ...$params));

        return $this->items;
    }

    /**
     * Hiển thị tên người thực hiện thay vì ID
     */
    protected function column_interacted_by($item)
    {
        return esc_html(get_the_author_meta('display_name', $item->interacted_by));
    }

    /**
     * Render content column with line breaks
     */
    protected function column_content($item)
    {
        return nl2br(esc_html($item->content));
    }
} 