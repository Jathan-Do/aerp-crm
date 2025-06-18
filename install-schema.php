<?php

/**
 * Install schema for AERP CRM Module
 */

function aerp_crm_get_table_names()
{
    global $wpdb;
    return [
        $wpdb->prefix . 'aerp_crm_customers',
        $wpdb->prefix . 'aerp_crm_customer_phones',
        $wpdb->prefix . 'aerp_crm_logs',
        $wpdb->prefix . 'aerp_crm_attachments',
    ];
}

function aerp_crm_install_schema()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sqls = [];

    // 1. Khách hàng
    $sqls[] = "CREATE TABLE {$wpdb->prefix}aerp_crm_customers (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        customer_code VARCHAR(50),
        full_name VARCHAR(255),
        company_name VARCHAR(255),
        tax_code VARCHAR(50),
        address TEXT,
        email VARCHAR(255),
        customer_type VARCHAR(50),
        status ENUM('active','inactive') DEFAULT 'active',
        assigned_to BIGINT,
        note TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    // 2. Số điện thoại khách hàng
    $sqls[] = "CREATE TABLE {$wpdb->prefix}aerp_crm_customer_phones (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        customer_id BIGINT,
        phone_number VARCHAR(20),
        is_primary BOOLEAN DEFAULT false,
        note VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    // 3. Tương tác
    $sqls[] = "CREATE TABLE {$wpdb->prefix}aerp_crm_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        customer_id BIGINT,
        interaction_type VARCHAR(50),
        content TEXT,
        interacted_by BIGINT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    // 4. File đính kèm
    $sqls[] = "CREATE TABLE {$wpdb->prefix}aerp_crm_attachments (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        customer_id BIGINT,
        file_name VARCHAR(255),
        file_url TEXT,
        file_type VARCHAR(50),
        uploaded_by BIGINT,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    foreach ($sqls as $sql) {
        dbDelta($sql);
    }
} 

function aerp_crm_insert_sample_data() {
    global $wpdb;

    $customers = [];
    for ($i = 1; $i <= 15; $i++) {
        $wpdb->insert("{$wpdb->prefix}aerp_crm_customers", [
            'customer_code'   => 'CUST' . str_pad($i, 4, '0', STR_PAD_LEFT),
            'full_name'       => "Khách hàng $i",
            'company_name'    => "Công ty $i",
            'tax_code'        => "MST$i",
            'address'         => "Địa chỉ $i",
            'email'           => "khach$i@example.com",
            'customer_type'   => ($i % 2 === 0 ? 'doanh_nghiep' : 'ca_nhan'),
            'status'          => ($i % 3 === 0 ? 'inactive' : 'active'),
            'assigned_to'     => 1,
            'note'            => "Ghi chú khách hàng $i",
        ]);
        $customers[] = $wpdb->insert_id;
    }

    foreach ($customers as $id) {
        // Phones
        for ($j = 1; $j <= 2; $j++) {
            $wpdb->insert("{$wpdb->prefix}aerp_crm_customer_phones", [
                'customer_id'   => $id,
                'phone_number'  => "090$i$j$i$j",
                'is_primary'    => $j === 1,
                'note'          => "Số $j của KH$id",
            ]);
        }

        // Logs
        $wpdb->insert("{$wpdb->prefix}aerp_crm_logs", [
            'customer_id'      => $id,
            'interaction_type' => 'gọi điện',
            'content'          => "Đã gọi tư vấn lần đầu cho KH$id",
            'interacted_by'    => 1,
        ]);
        $wpdb->insert("{$wpdb->prefix}aerp_crm_logs", [
            'customer_id'      => $id,
            'interaction_type' => 'email',
            'content'          => "Gửi báo giá lần 2 cho KH$id",
            'interacted_by'    => 1,
        ]);

        // Attachments
        $wpdb->insert("{$wpdb->prefix}aerp_crm_attachments", [
            'customer_id' => $id,
            'file_name'   => "hopdong_khach_$id.pdf",
            'file_url'    => "https://example.com/uploads/hopdong_khach_$id.pdf",
            'file_type'   => 'pdf',
            'uploaded_by'=> 1,
        ]);
    }
}
