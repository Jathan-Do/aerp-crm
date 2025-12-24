<?php
if (!defined('ABSPATH')) {
    exit;
}

class AERP_Frontend_Customer_Table extends AERP_Frontend_Table
{
    protected $filters = [];

    public function __construct()
    {
        parent::__construct([
            'table_name' => $GLOBALS['wpdb']->prefix . 'aerp_crm_customers',
            'columns' => [
                // 'id' => 'ID',
                'customer_code' => 'Mã khách hàng',
                'full_name' => 'Họ và tên',
                'address' => 'Địa chỉ',
                'phones' => 'Số điện thoại',
                'company_name' => 'Tên công ty',
                'customer_type_id' => 'Loại khách hàng',
                'customer_source_id' => 'Nguồn khách hàng',
                'status' => 'Trạng thái',
                'assigned_to' => 'Nhân viên phụ trách',
                'created_by' => 'Người tạo',
                'created_at' => 'Ngày tạo',
                'note' => 'Ghi chú',
                'action' => 'Thao tác',
            ],
            'sortable_columns' => ['id','customer_code', 'full_name', 'status', 'customer_type_id', 'created_at'],
            'searchable_columns' => ['customer_code', 'full_name', 'company_name'],
            'primary_key' => 'id',
            'per_page' => 10,
            'actions' => [],
            'bulk_actions' => ['delete'],
            'base_url' => home_url('/aerp-crm-customers'),
            'delete_item_callback' => ['AERP_Frontend_Customer_Manager', 'delete_customer_by_id'],
            'nonce_action_prefix' => 'delete_customer_',
            'message_transient_key' => 'aerp_customer_message',
            'hidden_columns_option_key' => 'aerp_crm_customer_table_hidden_columns',
            'ajax_action' => 'aerp_crm_filter_customers',
            'table_wrapper' => '#aerp-customer-table-wrapper',
        ]);
    }

    public function set_filters($filters = [])
    {
        parent::set_filters($filters); // Gọi cha để xử lý đầy đủ orderby, order, paged, search_term
    }

    /**
     * Điều kiện tìm kiếm số điện thoại liên bảng
     */
    protected function get_extra_search_conditions($search_term)
    {
        global $wpdb;
        return [
            ["id IN (SELECT customer_id FROM {$wpdb->prefix}aerp_crm_customer_phones WHERE phone_number LIKE %s)"],
            ['%' . $wpdb->esc_like($search_term) . '%']
        ];
    }

    /**
     * Điều kiện filter đặc thù cho bảng khách hàng
     */
    protected function get_extra_filters()
    {
        global $wpdb;

        $filters = [];
        $params = [];

        // 1) Các bộ lọc được chọn từ form
        if (!empty($this->filters['customer_type_id'])) {
            $filters[] = "customer_type_id = %s";
            $params[] = $this->filters['customer_type_id'];
        }
        if (!empty($this->filters['customer_source_id'])) {
            $filters[] = "customer_source_id = %s";
            $params[] = $this->filters['customer_source_id'];
        }
        if (!empty($this->filters['status'])) {
            $filters[] = "status = %s";
            $params[] = $this->filters['status'];
        }
        if (!empty($this->filters['assigned_to'])) {
            // Khi người dùng chọn cụ thể nhân viên phụ trách, chỉ áp dụng điều kiện này
            $filters[] = "assigned_to = %d";
            $params[] = (int)$this->filters['assigned_to'];
        } else {
            // Nếu là admin thì được xem hết
            $current_user_id = get_current_user_id();
            if (function_exists('aerp_user_has_role') && aerp_user_has_role($current_user_id, 'admin')) {
                // Không thêm filter nào, admin xem tất cả
            } else {
                // 2) Mặc định giới hạn theo chi nhánh của user hiện tại
                //    - Nếu có quyền xem full (customer_view_full|crm_view_full|order_view_full) => xem toàn chi nhánh
                //    - Ngược lại chỉ xem khách hàng do chính nhân viên (gắn với user hiện tại) phụ trách
                //    - Luôn bao gồm khách hàng CHƯA được gán nhân viên (assigned_to IS NULL hoặc 0)
                $current_user_employee = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, work_location_id FROM {$wpdb->prefix}aerp_hrm_employees WHERE user_id = %d",
                    $current_user_id
                ));

                if ($current_user_employee) {
                    $can_view_branch = (
                        function_exists('aerp_user_has_permission') && (
                            aerp_user_has_permission($current_user_id, 'customer_view_full')
                        )
                    );

                    if ($can_view_branch && !empty($current_user_employee->work_location_id)) {
                        // Lấy toàn bộ nhân viên trong chi nhánh
                        $branch_employee_ids = $wpdb->get_col($wpdb->prepare(
                            "SELECT id FROM {$wpdb->prefix}aerp_hrm_employees WHERE work_location_id = %d",
                            $current_user_employee->work_location_id
                        ));
                        if (!empty($branch_employee_ids)) {
                            $placeholders = implode(',', array_fill(0, count($branch_employee_ids), '%d'));
                            // Bao gồm cả khách hàng chưa được gán (NULL hoặc 0)
                            $filters[] = "(assigned_to IN ($placeholders) OR assigned_to IS NULL OR assigned_to = 0)";
                            $params = array_merge($params, $branch_employee_ids);
                        } else {
                            // Không có nhân viên nào trong chi nhánh => chỉ hiển thị khách hàng chưa gán
                            $filters[] = "(assigned_to IS NULL OR assigned_to = 0)";
                        }
                    } else {
                        // Chỉ xem khách hàng do chính nhân viên hiện tại phụ trách + chưa gán
                        $filters[] = "(assigned_to = %d OR created_by = %d OR assigned_to IS NULL OR assigned_to = 0)";
                        $params[] = (int)$current_user_employee->id;
                        $params[] = (int)$current_user_employee->id;
                    }
                }
            }
        }

        return [$filters, $params];
    }

    /**
     * Hiển thị tên người phụ trách thay vì ID
     */
    protected function column_assigned_to($item)
    {
        $assigned_to_id = $item->assigned_to;
        $employee_name = aerp_get_customer_assigned_name($assigned_to_id); // Hàm đã có để lấy tên nhân viên
        if (empty($employee_name)) {
            return '<span class="badge bg-secondary">Không xác định</span>';
        }
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
    protected function column_customer_type_id($item)
    {
        global $wpdb;
        // $type = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aerp_crm_customer_types WHERE id = %d", $item->customer_type_id));
        $type = aerp_get_customer_type($item->customer_type_id); // Sử dụng hàm đã có để lấy loại khách hàng
        if ($type) {
            $color = !empty($type->color) ? $type->color : 'secondary';
            return sprintf(
                '<span class="badge" style="background-color: %s; color: white;">%s</span>',
                esc_attr($color),
                esc_html($type->name)
            );
        }
        return '<span class="badge bg-secondary">Không xác định</span>';
    }

    /**
     * Hiển thị nguồn khách hàng thân thiện hơn
     */
    protected function column_customer_source_id($item)
    {
        $source = aerp_get_customer_source($item->customer_source_id);
        if ($source) {
            $color = !empty($source->color) ? $source->color : 'secondary';
            return sprintf(
                '<span class="badge" style="background-color: %s; color: white;">%s</span>',
                esc_attr($color),
                esc_html($source->name)
            );
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

    /**
     * Hiển thị người tạo (employee full name)
     */
    protected function column_created_by($item)
    {
        if (empty($item->created_by)) {
            return '<span class="text-muted">--</span>';
        }
        // created_by lưu ID nhân sự => dùng helper để lấy tên nhân sự
        $employee_name = function_exists('aerp_get_customer_assigned_name')
            ? aerp_get_customer_assigned_name((int)$item->created_by)
            : '';
        if (empty($employee_name)) {
            return '<span class="text-muted">--</span>';
        }
        return esc_html($employee_name);
    }

    /**
     * Hiển thị cột số điện thoại nâng cấp UX/UI
     */
    protected function column_phones($item)
    {
        $phones = aerp_get_customer_phones($item->id);
        if (!$phones) return '<span class="text-muted">--</span>';
        $out = [];
        foreach ($phones as $phone) {
            $str = '<a href="tel:' . esc_attr($phone->phone_number) . '">' . esc_html($phone->phone_number) . '</a>';
            $str .= ' <a href="#" class="copy-phone ms-1" data-phone="' . esc_attr($phone->phone_number) . '" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Copy"><i class="fas fa-clipboard"></i></a>';
            if ($phone->is_primary) $str .= ' <span class="badge bg-success">Chính</span>';
            $out[] = $str;
        }
        return implode('<br>', $out);
    }

    /**
     * Hiển thị cột action với kiểm tra đơn hàng
     */
    protected function column_action($item)
    {
        global $wpdb;
        $customer_id = intval($item->id);

        // Kiểm tra xem khách hàng có đơn hàng không
        $order_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aerp_order_orders WHERE customer_id = %d",
            $customer_id
        ));

        $has_orders = $order_count > 0;

        $edit_url = add_query_arg(['action' => 'edit', 'id' => $customer_id], $this->base_url);
        $delete_url = wp_nonce_url(add_query_arg(['action' => 'delete', 'id' => $customer_id], $this->base_url), $this->nonce_action_prefix . $customer_id);

        $edit_btn = sprintf(
            '<a data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Chỉnh sửa" href="%s" class="btn btn-sm btn-success mb-2 mb-md-0"><i class="fas fa-edit"></i></a>',
            esc_url($edit_url)
        );

        if ($has_orders) {
            // Nếu có đơn hàng, disable nút xóa
            $delete_btn = sprintf(
                '<button class="btn btn-sm btn-danger disabled" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Không thể xóa khách hàng đang có đơn hàng"><i class="fas fa-trash"></i></button>'
            );
        } else {
            // Nếu không có đơn hàng, cho phép xóa
            $delete_btn = sprintf(
                '<a data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Xóa" href="%s" class="btn btn-sm btn-danger" onclick="return confirm(\'Bạn có chắc muốn xóa khách hàng này?\')"><i class="fas fa-trash"></i></a>',
                esc_url($delete_url)
            );
        }

        return $edit_btn . ' ' . $delete_btn;
    }
}
