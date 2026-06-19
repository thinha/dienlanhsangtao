<?php
namespace Inc\Controllers;

class ActiveController{
    public static function create_ads_tracking() {
      global $wpdb;
      $table_name = $wpdb->prefix . "ads_google_tracking"; 
      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
        ip_id bigint(20) NOT NULL AUTO_INCREMENT,
        ip varchar(255) NOT NULL,
        count_visit longtext NULL,
        PRIMARY KEY  (ip_id)
      ) $charset_collate;";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );
    }

    public static function create_table_each_ip() {
      global $wpdb;
      $table_name = $wpdb->prefix . "ads_google_tracking_info"; 
      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
        info_id bigint(20) NOT NULL AUTO_INCREMENT,
        ads_tracking_id bigint(20) NOT NULL,
        name varchar(255) NULL,
        agent text NULL,
        referer varchar(255) NULL,
        keyword varchar(255) NULL,
        device varchar(255) NULL,
        devicemodel varchar(255) NULL,
        campaignid varchar(255) NULL,
        adposition varchar(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (info_id)
      ) $charset_collate;";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );
    }

    public static function alter_ads_google_tracking_info() {
      global $wpdb;
      $table_name = $wpdb->prefix . "ads_google_tracking_info"; 
      $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
      WHERE table_name = $table_name AND column_name = 'referer'"  );
      if(empty($row)){
         $wpdb->query("ALTER TABLE $table_name ADD referer varchar(255) NULL AFTER agent");
      }
    }

}