<?php
if (!defined('ABSPATH')) {
    exit;
}

class AERP_Frontend_Customer_Source_Manager
{
    public static function get_by_id($id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aerp_crm_customer_sources';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function handle_form_submit()
    {
        if (!isset($_POST['aerp_save_customer_source'])) return;
        if (!wp_verify_nonce($_POST['aerp_save_customer_source_nonce'], 'aerp_save_customer_source_action')) {
            wp_die('Invalid nonce for customer source save.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'aerp_crm_customer_sources';
        $id = isset($_POST['source_id']) ? absint($_POST['source_id']) : 0;

        $data = [
            'source_key' => sanitize_text_field($_POST['source_key']),
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'color' => sanitize_hex_color($_POST['color']),
        ];

        if ($id) {
            // Update
            $wpdb->update($table, $data, ['id' => $id], ['%s', '%s', '%s', '%s'], ['%d']);
            $msg = 'Đã cập nhật nguồn khách hàng!';
        } else {
            // Insert
            $wpdb->insert($table, $data, ['%s', '%s', '%s', '%s']);
            $msg = 'Đã thêm nguồn khách hàng!';
        }

        aerp_clear_table_cache();
        set_transient('aerp_customer_source_message', $msg, 10);
        wp_redirect(home_url('/aerp-crm-customer-sources'));
        exit;
    }

    public static function handle_single_delete()
    {
        $id = absint($_GET['id'] ?? 0);
        $nonce_action = 'delete_customer_source_' . $id;
        if ($id && check_admin_referer($nonce_action)) {
            if (self::delete_customer_source_by_id($id)) {
                $message = 'Đã xóa nguồn khách hàng thành công!';
            } else {
                $message = 'Không thể xóa nguồn khách hàng.';
            }
            aerp_clear_table_cache();
            set_transient('aerp_customer_source_message', $message, 10);
            wp_redirect(home_url('/aerp-crm-customer-sources'));
            exit;
        }
        wp_die('Invalid request or nonce.');
    }

    public static function delete_customer_source_by_id($id)
    {
        global $wpdb;
        // Check if source is being used by any customers
        $customer_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aerp_crm_customers WHERE customer_source_id = %d",
            $id
        ));
        
        if ($customer_count > 0) {
            return false; // Cannot delete if in use
        }
        
        $deleted = $wpdb->delete($wpdb->prefix . 'aerp_crm_customer_sources', ['id' => $id]);
        aerp_clear_table_cache();
        return (bool)$deleted;
    }
}
