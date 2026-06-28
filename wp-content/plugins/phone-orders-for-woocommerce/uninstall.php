<?php

if (defined('WP_UNINSTALL_PLUGIN')) {
    global $wpdb;
    $table_name = "{$wpdb->prefix}phone_orders_log";
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
    $result     = $wpdb->query("DROP TABLE IF EXISTS $table_name;");
}
