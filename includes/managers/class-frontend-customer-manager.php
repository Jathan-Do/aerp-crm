<?php
if (!defined('ABSPATH')) {
    exit;
}

class AERP_Frontend_Customer_Manager {
    public static function handle_form_submit() {
        if (!isset($_POST['aerp_save_customer'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['aerp_save_customer_nonce'], 'aerp_save_customer_action')) {
            wp_die('Invalid nonce for customer save.');
        }

        // You might want to add capability check here, e.g., current_user_can('manage_options')

        global $wpdb;
        $table = $wpdb->prefix . 'aerp_crm_customers';

        $id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
        $customer_id = 0; // Initialize customer_id
        $customer_code = sanitize_text_field($_POST['customer_code']);
        $full_name = sanitize_text_field($_POST['full_name']);
        $company_name = sanitize_text_field($_POST['company_name']);
        $tax_code = sanitize_text_field($_POST['tax_code']);
        $address = sanitize_textarea_field($_POST['address']);
        $email = sanitize_email($_POST['email']);
        $customer_type = sanitize_text_field($_POST['customer_type']);
        $status = sanitize_text_field($_POST['status']);
        $assigned_to = absint($_POST['assigned_to']);
        $note = sanitize_textarea_field($_POST['note']);

        $data = [
            'customer_code' => $customer_code,
            'full_name' => $full_name,
            'company_name' => $company_name,
            'tax_code' => $tax_code,
            'address' => $address,
            'email' => $email,
            'customer_type' => $customer_type,
            'status' => $status,
            'assigned_to' => $assigned_to,
            'note' => $note,
        ];

        $format = [
            '%s', // customer_code
            '%s', // full_name
            '%s', // company_name
            '%s', // tax_code
            '%s', // address
            '%s', // email
            '%s', // customer_type
            '%s', // status
            '%d', // assigned_to
            '%s', // note
        ];

        if ($id) {
            // Update existing customer
            $wpdb->update(
                $table,
                $data,
                ['id' => $id],
                $format,
                ['%d']
            );
            $customer_id = $id;
            $msg = 'Đã cập nhật khách hàng!';
        } else {
            // Insert new customer
            $data['created_at'] = (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s');
            $format[] = '%s'; // created_at
            $wpdb->insert($table, $data, $format);
            $customer_id = $wpdb->insert_id; // Get the ID of the newly inserted customer
            $msg = 'Đã thêm khách hàng!';
        }

        if ($customer_id) {
            // Handle Phone Numbers
            if (isset($_POST['phone_numbers']) && is_array($_POST['phone_numbers'])) {

                // Get current phone IDs for this customer
                $existing_phone_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}aerp_crm_customer_phones WHERE customer_id = %d",
                    $customer_id
                ));

                $submitted_phone_ids = [];

                // Reset all existing primary flags for this customer first (before processing new/updated ones)
                $wpdb->update(
                    $wpdb->prefix . 'aerp_crm_customer_phones',
                    ['is_primary' => 0],
                    ['customer_id' => $customer_id],
                    ['%d'],
                    ['%d']
                );

                foreach ($_POST['phone_numbers'] as $phone_data) {
                    $phone_number = sanitize_text_field($phone_data['number'] ?? '');
                    $phone_note = sanitize_textarea_field($phone_data['note'] ?? '');
                    $is_primary = isset($phone_data['primary']) ? 1 : 0;
                    $phone_id = absint($phone_data['id'] ?? 0); // Hidden ID for existing phones

                    if (!empty($phone_number)) {
                        $phone_table = $wpdb->prefix . 'aerp_crm_customer_phones';
                        $phone_insert_data = [
                            'customer_id' => $customer_id,
                            'phone_number' => $phone_number,
                            'is_primary' => $is_primary,
                            'note' => $phone_note,
                        ];
                        $phone_insert_format = ['%d', '%s', '%d', '%s'];

                        if ($phone_id) {
                            // Update existing phone number
                            $wpdb->update(
                                $phone_table,
                                $phone_insert_data,
                                ['id' => $phone_id],
                                $phone_insert_format,
                                ['%d']
                            );
                            $submitted_phone_ids[] = $phone_id; // Add to submitted IDs list
                        } else {
                            // Insert new phone number
                            $phone_insert_data['created_at'] = (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s');
                            $phone_insert_format[] = '%s';
                            $wpdb->insert($phone_table, $phone_insert_data, $phone_insert_format);
                            // For newly inserted items, their ID is not known until after insertion. 
                            // If we needed to track new IDs, we'd use $wpdb->insert_id, but for deletion logic,
                            // we only care about existing ones.
                        }
                    }
                }

                // Delete phones not submitted
                $phones_to_delete = array_diff($existing_phone_ids, $submitted_phone_ids);

                if (!empty($phones_to_delete)) {
                    $ids_placeholder = implode(', ', array_fill(0, count($phones_to_delete), '%d'));
                    
                    // Construct the arguments for wpdb->prepare
                    // The first argument is the query string, followed by the values for placeholders
                    $query_string = "DELETE FROM {$wpdb->prefix}aerp_crm_customer_phones WHERE id IN ({$ids_placeholder}) AND customer_id = %d";
                    
                    // All values that go into the placeholders, including the customer_id at the end
                    $query_values = array_merge($phones_to_delete, [$customer_id]);

                    // Use call_user_func_array to pass the query string and values to prepare
                    // The first argument of call_user_func_array is the callable: [$wpdb, 'prepare']
                    // The second argument is an array containing all arguments for the callable: [query_string, value1, value2, ...]
                    $prepare_arguments = array_merge([$query_string], $query_values);

                    $wpdb->query(call_user_func_array([$wpdb, 'prepare'], $prepare_arguments));
                }

            } else {
                // If no phone numbers are submitted at all, delete all existing ones for this customer
                $wpdb->delete(
                    $wpdb->prefix . 'aerp_crm_customer_phones',
                    ['customer_id' => $customer_id],
                    ['%d']
                );
            }

            // Handle Attachments
            if (!empty($_FILES['attachments']['name'][0])) {
                // Include necessary WordPress files for media handling
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }

                $upload_overrides = [ 'test_form' => false ];
                foreach ($_FILES['attachments']['name'] as $key => $filename) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $filename,
                            'type' => $_FILES['attachments']['type'][$key],
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                            'error' => $_FILES['attachments']['error'][$key],
                            'size' => $_FILES['attachments']['size'][$key],
                        ];

                        $uploaded_file = wp_handle_upload($file, $upload_overrides);

                        if (isset($uploaded_file['file'])) {
                            $attachment_table = $wpdb->prefix . 'aerp_crm_attachments';
                            $attachment_data = [
                                'customer_id' => $customer_id,
                                'file_name' => sanitize_file_name($filename),
                                'file_url' => $uploaded_file['url'],
                                'file_type' => $uploaded_file['type'],
                                'uploaded_by' => get_current_user_id(),
                                'uploaded_at' => (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s'),
                            ];
                            $attachment_format = ['%d', '%s', '%s', '%s', '%d', '%s'];
                            $wpdb->insert($attachment_table, $attachment_data, $attachment_format);
                        } else {
                            error_log('AERP_CRM: File upload failed for ' . $filename . ': ' . ($uploaded_file['error'] ?? 'Unknown error'));
                        }
                    }
                }
            }

            // Handle deletion of existing attachments (if any are explicitly removed by the user, this needs AJAX or a hidden field strategy)
            // For simplicity, for now, we only handle new uploads. The existing attachments deletion is handled via AJAX already.
        }

        set_transient('aerp_customer_message', $msg, 10);
        wp_redirect(home_url('/aerp-crm-customers'));
        exit;
    }

    public static function handle_single_delete() {
        $id = absint($_GET['id'] ?? 0);
        $nonce_action = 'delete_customer_' . $id;

        if ($id && check_admin_referer($nonce_action)) {
            if (self::delete_customer_by_id($id)) {
                $message = 'Đã xóa khách hàng thành công!';
            } else {
                $message = 'Không thể xóa khách hàng.';
            }
            set_transient('aerp_customer_message', $message, 10);
            wp_redirect(home_url('/aerp-crm-customers'));
            exit;
        } else {
            error_log('AERP_CRM: Single delete - Nonce verification failed or ID missing.');
        }
        wp_die('Invalid request or nonce.');
    }

    /**
     * Xóa khách hàng theo ID
     * @param int $id ID của khách hàng cần xóa
     * @return bool True nếu xóa thành công, false nếu thất bại
     */
    public static function delete_customer_by_id($id)
    {
        global $wpdb;
        $deleted = $wpdb->delete($wpdb->prefix . 'aerp_crm_customers', ['id' => absint($id)]);
        return (bool) $deleted;
    }

    public static function get_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aerp_crm_customers';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function handle_delete_attachment_ajax() {
        if (!isset($_POST['attachment_id']) || !isset($_POST['_wpnonce'])) {
            wp_send_json_error('Thiếu tham số.');
        }

        $attachment_id = absint($_POST['attachment_id']);
        $nonce = sanitize_text_field($_POST['_wpnonce']);

        if (!wp_verify_nonce($nonce, 'aerp_delete_attachment_nonce')) {
            wp_send_json_error('Nonce không hợp lệ.');
        }

        // Optional: Add capability check here, e.g., current_user_can('manage_options')

        global $wpdb;
        $table = $wpdb->prefix . 'aerp_crm_attachments';

        // Get file information before deleting the record
        $file_info = $wpdb->get_row($wpdb->prepare("SELECT file_url, file_name FROM $table WHERE id = %d", $attachment_id));

        if (!$file_info) {
            wp_send_json_error('File đính kèm không tồn tại.');
        }

        // Delete record from database
        $deleted = $wpdb->delete($table, ['id' => $attachment_id], ['%d']);

        if ($deleted) {
            // Try to delete the physical file from the server
            $upload_dir = wp_upload_dir();
            $base_url = $upload_dir['baseurl'];
            $base_dir = $upload_dir['basedir'];

            // Reconstruct local path from URL
            // This is a basic approach and might need refinement for complex URL structures
            $relative_path = str_replace($base_url, '', $file_info->file_url);
            $file_path = $base_dir . $relative_path;

            if (file_exists($file_path)) {
                if (unlink($file_path)) {
                    error_log('AERP_CRM: Physical file deleted: ' . $file_path);
                } else {
                    error_log('AERP_CRM: Failed to delete physical file: ' . $file_path);
                }
            } else {
                error_log('AERP_CRM: Physical file not found at path: ' . $file_path);
            }
            wp_send_json_success('Đã xóa file đính kèm thành công.');
        } else {
            wp_send_json_error('Không thể xóa file đính kèm khỏi cơ sở dữ liệu.');
        }
    }

    public static function handle_add_customer_log() {
        if (!isset($_POST['aerp_add_customer_log'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['aerp_add_customer_log_nonce'], 'aerp_add_customer_log_action')) {
            wp_die('Invalid nonce for customer log.');
        }

        // You might want to add capability check here
        if (!is_user_logged_in()) {
            wp_die('Bạn cần đăng nhập để thực hiện thao tác này.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'aerp_crm_logs';

        $customer_id = absint($_POST['customer_id'] ?? 0);
        $interaction_type = sanitize_text_field($_POST['interaction_type'] ?? '');
        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $interacted_by = get_current_user_id();

        if (!$customer_id || empty($interaction_type) || empty($content)) {
            set_transient('aerp_customer_message', 'Vui lòng điền đầy đủ thông tin tương tác.', 10);
            wp_redirect(wp_get_referer() ?: home_url('/aerp-crm-customers'));
            exit;
        }

        $data = [
            'customer_id' => $customer_id,
            'interaction_type' => $interaction_type,
            'content' => $content,
            'interacted_by' => $interacted_by,
            'created_at' => (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s'),
        ];

        $format = [
            '%d', // customer_id
            '%s', // interaction_type
            '%s', // content
            '%d', // interacted_by
            '%s', // created_at
        ];

        $inserted = $wpdb->insert($table, $data, $format);

        if ($inserted) {
            set_transient('aerp_customer_message', 'Đã ghi nhận tương tác mới!', 10);
        } else {
            set_transient('aerp_customer_message', 'Không thể ghi nhận tương tác mới.', 10);
        }

        // Redirect back to the customer detail page
        wp_redirect(home_url('/aerp-crm-customers/' . $customer_id));
        exit;
    }

    public static function delete_customer_log_by_id($id)
    {
        global $wpdb;
        $deleted = $wpdb->delete($wpdb->prefix . 'aerp_crm_logs', ['id' => absint($id)]);
        return (bool) $deleted;
    }
} 