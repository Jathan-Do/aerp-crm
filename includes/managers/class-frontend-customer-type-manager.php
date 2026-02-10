<?php
if (!defined('ABSPATH')) {
    exit;
}

class AERP_Frontend_Customer_Type_Manager
{
    public static function get_by_id($id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aerp_crm_customer_types';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    public static function handle_form_submit()
    {
        if (!isset($_POST['aerp_save_customer_type'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['aerp_save_customer_type_nonce'], 'aerp_save_customer_type_action')) {
            wp_die('Invalid nonce for customer type save.');
        }
        global $wpdb;
        $table = $wpdb->prefix . 'aerp_crm_customer_types';
        $id = isset($_POST['type_id']) ? absint($_POST['type_id']) : 0;
        $type_key = sanitize_text_field($_POST['type_key']);
        // Kiểm tra trùng mã loại (type_key)
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE type_key = %s AND id != %d", $type_key, $id));
        if ($exists) {
            set_transient('aerp_customer_type_message', 'Mã loại đã tồn tại, vui lòng chọn mã khác!', 10);
            if ($id) {
                wp_redirect(home_url('/aerp-crm-customer-types?action=edit&id=' . $id));
            } else {
                wp_redirect(home_url('/aerp-crm-customer-types?action=add'));
            }
            exit;
        }
        $data = [
            'type_key' => $type_key,
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'color' => sanitize_hex_color($_POST['color']),
        ];
        if ($id) {
            $wpdb->update($table, $data, ['id' => $id]);
            $msg = 'Đã cập nhật loại khách hàng!';
        } else {
            $data['created_at'] = (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s');
            $wpdb->insert($table, $data);
            $msg = 'Đã thêm loại khách hàng mới!';
        }
        // Xóa cache bảng sau khi thêm/sửa
        aerp_clear_table_cache();
        set_transient('aerp_customer_type_message', $msg, 10);
        wp_redirect(home_url('/aerp-crm-customer-types'));
        exit;
    }
    public static function handle_single_delete()
    {
        $id = absint($_GET['id'] ?? 0);
        $nonce_action = 'delete_customer_type_' . $id;
        if ($id && check_admin_referer($nonce_action)) {
            if (self::delete_customer_type_by_id($id)) {
                $message = 'Đã xóa loại khách hàng thành công!';
            } else {
                $message = 'Không thể xóa loại khách hàng.';
            }
            // Xóa cache bảng sau khi xóa
            aerp_clear_table_cache();
            set_transient('aerp_customer_type_message', $message, 10);
            wp_redirect(home_url('/aerp-crm-customer-types'));
            exit;
        } else {
            error_log('AERP_CRM: Customer type delete - Nonce verification failed or ID missing.');
        }
        wp_die('Invalid request or nonce.');
    }
    public static function delete_customer_type_by_id($id)
    {
        global $wpdb;
        $deleted = $wpdb->delete($wpdb->prefix . 'aerp_crm_customer_types', ['id' => absint($id)]);
        // Xóa cache bảng sau khi xóa
        aerp_clear_table_cache();
        return (bool) $deleted;
    }
}
