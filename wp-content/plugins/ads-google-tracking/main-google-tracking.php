<?php
/**
 * Plugin Name: Ads Google Tracking
 * Plugin URI: 
 * Description: 
 * Version: 1.0
 * Author: NGUYEN HUYNH ANH THI
 * Author URI: 
 * 
 */
defined ('ABSPATH') or die( 'Oh, Get Back');

if( file_exists( dirname(__FILE__) ) . '/vendor/autoload.php' ){
  require_once dirname(__FILE__) . '/vendor/autoload.php';
}

use Inc\Controllers\GetInfoClientAccess;
use Inc\Controllers\ActiveController;
use Inc\Controllers\AdminControlController;

// Initialize Plugin 

$adsGoogleTracking = new AdsGoogleTracking();

//Add  Hook
add_action( 'init',array($adsGoogleTracking,'get_info_client_access'),1);
add_action( 'wp_enqueue_scripts', array( $adsGoogleTracking, 'ads_script_footer') );
add_action('wp_ajax_tracking',array( $adsGoogleTracking, 'ajax_response') );
add_action( 'wp_ajax_nopriv_tracking', array( $adsGoogleTracking, 'ajax_response') );

 

register_activation_hook(__FILE__, array($adsGoogleTracking, 'activate'));
register_deactivation_hook(__FILE__, array($adsGoogleTracking, 'deactivate'));

class AdsGoogleTracking
    {
      /**
       * Initialize 
       */
      public function __construct()
      {
        add_action( 'admin_menu', array($this,'add_admin_menu') );
        add_action( 'admin_enqueue_scripts', array($this,'add_style_admin') );

      }

      public function add_admin_menu(){
        add_menu_page('Ads Google Tracking','Ads Google Tracking','manage_options','ads-google-tracking',array($this,'admin_page'), 'dashicons-admin-ip2location', 30);
      }

      public function add_style_admin(){
        wp_enqueue_style('ads_google_style', plugins_url( '/assets/css/admin.css', __FILE__ ), [], wp_get_theme()->get( 'Version' ), null);
      }

      public function add_style(){
        wp_enqueue_style('ads_google_bootstrap', esc_url_raw('https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css'), [], null);

      }

      public function add_script(){
        wp_enqueue_script('ads_google_js_bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js',[], null, true);
      }

      public function admin_page(){
        if (!is_admin()) {
          return;
        }
         $this->add_script();
         $this->add_style(); 
         $adminInit =  new AdminControlController();
         //Initial
         $adminInit->csv_export();
         $adminInit->truncate2TableAdsTracking();
         //Data Tracking
         $dataTrackings = $adminInit->list_view();

        $html = '
        <div class="ads-google-tracking">
        <div class="btn-controls">
          <form method="post">
            <div class="btn-controls_group">
              <button class="btn-blue" type="submit" name="exportcsv">Export CSV</button>
              <button class="btn-danger" type="submit" name="detroyall">Delete All</button>
            </div>
          <form>
        </div>
        <div class="table-master">
          <table class="table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Referer</th>
                <th>Agent</th>
                <th>Keyword</th>
                <th>Devide</th>
                <th>Devidemodel</th>
                <th>Campaignid</th>
                <th>Adposition</th>
                <th>Date</th>
              </tr>
            </thead> 
            <tbody>';
            foreach ( $dataTrackings as $ip => $vals ){
              $count_visit = 0;
              if ($vals['count_visit']) {
              $count_visit = $vals['count_visit'] === 'Not Detected Or Robot'?'Not Detected Or Robot':maybe_unserialize( $vals['count_visit'] )['visit'];
              }
              $html .= '<tr>';
              $html .= '<th colspan="9">
                        <span>IP: '.$vals['ip'].'</span>
                        <span> - Visited: '.$count_visit.'</span>
                        </th>';
              $html .= '</tr>';
              foreach($vals['info'] as $val){
                $html .=  '<tr>';
                $html .= '<td class="d-none"></td>';
                $html .= '<td data-title="Name">'. $val->name .'</td>';
                $html .= '<td data-title="Referer">'. $val->referer .'</td>';
                $html .= '<td data-title="Agent">'. $val->agent .'</td>';
                $html .= '<td data-title="Keyword">'.$val->keyword.'</td>';
                $html .= '<td data-title="Device">'.$val->device.'</td>';
                $html .= '<td data-title="Device Model">'.$val->devicemodel.'</td>';
                $html .= '<td data-title="Campaign ID">'.$val->campaignid.'</td>';
                $html .= '<td data-title="Ads Position">'.$val->adposition.'</td>';
                // $html .= '<td>'.maybe_unserialize( $val->count_visit)['visit'].'</td>';
                $date = new DateTime($val->created_at, new DateTimeZone('Asia/Ho_Chi_Minh'));
                $myTimeZone = $date->format('Y-m-d H:i:sP');
                $html .= '<td data-title="Date">'. $myTimeZone.'</td>';
                $html .= '</tr>';
              }
            }  
              $html .= '</tbody>
          </table>
        </div>
        </div>';
        print $html;
      }

      /**
       * Activate the plugin
       */
      public function get_info_client_access(){
        if ( !is_admin() ) {     
          $getClient  =  new GetInfoClientAccess();
          $getClient->save_database();
        }
      }

      public function ads_script_footer($hook){
        
        wp_enqueue_script('ads_google_js_momentjs', plugins_url('/assets/js/momentjs.js', __FILE__),array(), null, true);
        wp_enqueue_script( 'ajax-script', plugins_url( '/assets/js/script-ads.js', __FILE__ ),array(),null,true );
        $localize = array( 
          'ajax_url' => admin_url( 'admin-ajax.php' ) 
        );
        // wp_enqueue_script( 'jquery-script', plugins_url( '/assets/js/jquery-v351.js', __FILE__ ), array('jquery') );
       
        wp_localize_script( 'ajax-script', 'ajax_object',$localize );
      }

      public function ajax_response(){
        $getClient = new GetInfoClientAccess();
        $getClient -> save_ajax_response();
      }
      /**
       * Activate the plugin
       */
      public static function activate()
      {
        $getClient  =  new GetInfoClientAccess();
          $host = $getClient->get_host();
          if( strpos($host,'dienlanhsangtao') === false ){
              var_dump('AUTHOR:NGUYEN HUYNH ANH THI - SĐT: 0943980279 - ERROR: No License');
              die;
          }
        ActiveController::create_ads_tracking();
        ActiveController::create_table_each_ip();
        ActiveController::alter_ads_google_tracking_info();
      }
      /**
       * Deactivate the plugin
       */  
      public static function deactivate()
      {
        
      }
}
