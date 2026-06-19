<?php
if( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	global $wpdb;
	$table_name = "{$wpdb->prefix}phone_orders_log";
	$result = $wpdb->query( "DROP TABLE IF EXISTS $table_name;" );
}
