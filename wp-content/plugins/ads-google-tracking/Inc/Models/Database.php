<?php 

namespace Inc\Models;

class Database {

  protected $request = [];

  public function save_tracking_ip($request) {
    global  $wpdb;
    $lastID = 0;
    $sql  = ' INSERT INTO ' . $wpdb->prefix . 'ads_google_tracking
    ( ip )
    VALUES ( %s )';
    $value = [  
    $request['ip'],
    ];
    $query = $wpdb->prepare( $sql , $value );
    $wpdb->query( $query );
    $wpdb->flush();
    if($wpdb->insert_id){
      $lastID = $wpdb->insert_id;
    }
    return $lastID;
  }

  public function save_tracking_info_of_ip($request){
    global  $wpdb;
    $lastID = $request['ads_tracking_id'];
    $sql  = ' INSERT INTO ' . $wpdb->prefix . 'ads_google_tracking_info
    ( ads_tracking_id, name, agent, referer, keyword, device, devicemodel, campaignid, adposition )
    VALUES ( %d, %s, %s, %s, %s, %s ,%s, %s, %s)';
    $value = [   
      $lastID,
      'google ads',
      $request['agent'], 
      $request['referer'], 
      $request['keyword'],
      $request['device'],
      $request['devicemodel'],
      $request['campaignid'],
      $request['adposition']
    ];
    $query = $wpdb->prepare( $sql , $value );
    $wpdb->query( $query );
    $wpdb->flush();
  }

  public function save_visit_page($request){
   global $wpdb;
   $countVisit = 'Not Detected Or Robot';
   if( strlen($request['data']) > 1 ){
      $countVisit = $request['data'];
   }

   $sql  = ' UPDATE ' . $wpdb->prefix . 'ads_google_tracking
   SET count_visit = %s
   WHERE ip  = %s';
    $value = [  
      $countVisit, $request['ip']
    ];
    $query = $wpdb->prepare( $sql , $value );
    $wpdb->query( $query );
    $wpdb->flush();
  }

  public function get_databaseIP(){
    global  $wpdb;
    $sql = 'SELECT ip 
    FROM ' . $wpdb->prefix . 'ads_google_tracking';
    return $wpdb->get_col( $sql );
  }

  public function get_visit(){
    global  $wpdb;
    $sql = 'SELECT ip,count_visit
    FROM ' . $wpdb->prefix . 'ads_google_tracking';
    return $wpdb->get_results( $sql );
  }

  public function first_ip_info($ip){
    global  $wpdb;
    $sql = 'SELECT *
    FROM ' . $wpdb->prefix . 'ads_google_tracking_info AS f
    INNER JOIN ' . $wpdb->prefix . 'ads_google_tracking AS t
    ON t.ip_id = f.ads_tracking_id
    WHERE t.ip = "'.$ip.'"';
    return $wpdb->get_results( $sql );
  }

  public function get_list(){
    global  $wpdb;
    $sql = 'SELECT *
    FROM ' . $wpdb->prefix . 'ads_google_tracking_info AS f
    INNER JOIN ' . $wpdb->prefix . 'ads_google_tracking AS t
    ON t.ip_id = f.ads_tracking_id';
    return $wpdb->get_results( $sql );
  }

  public function destroyAllDataAdsTracking(){
    global  $wpdb;
    $table_tracking = $wpdb->prefix . "ads_google_tracking"; 
    $table_tracking_info = $wpdb->prefix . "ads_google_tracking_info"; 
    $query = $wpdb->query( "TRUNCATE $table_tracking");
    if($query){
      $query = $wpdb->query( "TRUNCATE $table_tracking_info");
      return true;
    }
    return false; 
  }
 

}
