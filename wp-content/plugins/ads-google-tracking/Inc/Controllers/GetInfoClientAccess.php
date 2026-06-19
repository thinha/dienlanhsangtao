<?php

namespace Inc\Controllers;

use Inc\Models\Database;

class GetInfoClientAccess 
{
  	protected $request = []; 

  	private function get_all_header() 
  	{
  		$all_headers = getallheaders();
  		$referer = null;
  		foreach (getallheaders() as $name => $value) {
		    if ($name == "Referer") {
		    	$referer = $value;
		    	break;
		    }
		}
		return $referer;
  	}

  	private function get_user_agent()
	{
		return (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}//End Agent

	private function get_user_referer()
	{
		return (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : null;
	}//End Referer

  	private  function get_ip()
  	{ 
		// Get server IP address
		$server_ip = (isset($_SERVER['SERVER_ADDR'])) ? $_SERVER['SERVER_ADDR'] : '';

		// If website is hosted behind CloudFlare protection.
		if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return $_SERVER['HTTP_CF_CONNECTING_IP'];
		}

		if (isset($_SERVER['X-Real-IP']) && filter_var($_SERVER['X-Real-IP'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return $_SERVER['X-Real-IP'];
		}

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = trim(current(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));

			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) && $ip != $server_ip) {
				return $ip;
			}
		}

		return $_SERVER['REMOTE_ADDR'];
  	}//End IP

	public function get_host()
	{
		return (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : null;
	}

	public function save_database()
	{
		$initData = new Database();
		$dataIPs = $initData->get_databaseIP();
		$host = $this->get_host();
		$referer = null;

		if( strpos($host,'dienlanhsangtao.') === false ) {
			return;
		}

		if ($this->get_all_header()) {
			$referer = $this->get_all_header();	
		} else {
			$referer = $this->get_user_referer();	
		}

		$this->request['ip'] = $this->get_ip();
		$this->request['agent'] = $this->get_user_agent();
		$this->request['referer'] = $referer;
		
		if( isset($_GET['keyword']) ){
			$this->request['keyword'] = urldecode($_GET['keyword']);
		}else {
			$this->request['keyword'] = '';
		}
		if( isset($_GET['devicemodel']) ){
		$this->request['devicemodel'] = urldecode($_GET['devicemodel']);
		} else {
			$this->request['devicemodel'] = '';
		}
		if ( isset($_GET['device']) ) {
			$this->request['device'] =urldecode($_GET['device']);
		}else {
			$this->request['device'] = '';
		}
		if ( isset($_GET['campaignid']) ) {
			$this->request['campaignid'] =urldecode($_GET['campaignid']);
		}else {
			$this->request['campaignid'] = '';
		}
		if ( isset($_GET['adposition']) ) {
			$this->request['adposition'] =urldecode($_GET['adposition']);
		}else {
			$this->request['adposition'] = '';
		}
		if  ( !in_array( $this->get_ip(),  $dataIPs, true) ){
			$getidNew = $initData -> save_tracking_ip( $this->request );
			if($getidNew !== 0){
				$this->request['ads_tracking_id'] = $getidNew;
				$initData -> save_tracking_info_of_ip( $this->request );
			}
		} else {
			$ip = $this->get_ip();
			$dataLists = $initData->first_ip_info($ip);
			if( count($dataLists) < 1 ) return;
			$getData_getIpId = $dataLists[0];

			$box_keywords = [];
			$box_agents = [];
			$box_devices = [];
			$box_devicemodels = [];			
			$box_adpositions = [];
			$box_campaignids = [];

			foreach($dataLists as $dataList){

				if(!empty($dataList->keyword)){
					$box_keywords[] = $dataList->keyword;
				}
				if(!empty($dataList->agent)){
					$box_agents[] = $dataList->agent;
				}
				if(!empty($dataList->device)){
					$box_devices[] = $dataList->device;
				}
				if(!empty($dataList->devicemodel)){
					$box_devicemodels[] =  $dataList->devicemodel;
				}
				if(!empty($dataList->campaignid)){
					$box_campaignids[] = $dataList->campaignid;
				}
				if(!empty($dataList->adposition)){
					$box_adpositions[] = $dataList->adposition;
				}	
			}
			$checkAds = [];	
			foreach($this->request as $key => $param){
				if( empty($param) || $key == 'ip'){
					continue;	
				}
				switch ($key) {
					case 'agent':
						if( !in_array($param,$box_agents) ){
							array_push($checkAds,'true');	
						}
						break;
					case 'keyword':
							
						if( !in_array($param,$box_keywords) ){
							array_push($checkAds,'true');	
						}

						break;
					case 'device':
					if( !in_array($param,$box_devices) ){
						array_push($checkAds,'true');	
					}
					break;
					case 'devicemodel':
					if( !in_array($param,$box_devicemodels) ){
						array_push($checkAds,'true');	
					}
					break;
					case 'campaignid':
					if( !in_array($param,$box_campaignids) ){
						array_push($checkAds,'true');	
					}
					break;
					case 'adposition':
					if( !in_array($param,$box_adpositions) ){
						array_push($checkAds,'true');	
					}
					break;
				}
			}
			if ( in_array( 'true',  $checkAds) ){
				$this->request['ads_tracking_id'] = $getData_getIpId->ip_id;
				$initData -> save_tracking_info_of_ip( $this->request );
			}
		}
		return;
	}
		
	public function save_ajax_response() 
	{
		$initData = new Database();
		$visitedes =  $initData->get_visit();
		$currentIP = $this->get_ip();
		$data = '';
		$host = $this->get_host();
		
		if( strpos($host,'dienlanhsangtao.') === false ){
			return;
		}
		
		if ( isset($_POST['cookies']) ){
			$cookies = $_POST['cookies'];
			$visit = 1;
			if  (  !empty(  $visitedes ) ){
				foreach ( $visitedes as $val ){
					 if ( $val->ip === $currentIP ) {
						$arr  = maybe_unserialize( $val->count_visit );
						if ( $arr['cookies'] !== $cookies ){
							 $visit = intval ( $arr['visit'] ) + 1;
						} else {
							$visit = intval ( $arr['visit'] );
						}
					}
				}
			}
			$result = ['cookies' =>$cookies,'visit' => $visit ];
			$data = array( 'data' => maybe_serialize( $result ), 'ip' => $currentIP );
			$initData -> save_visit_page($data);
		} else {
			$data = array( 'data' => '', 'ip' => $currentIP );
			$initData -> save_visit_page($data);
		}
		$response = array(
			'success' => 'success',
			'check' =>   $cookies,
			'visit' => $visit,
			'ip'=>$currentIP,
		);
		wp_send_json( $response, 200 );
		wp_die();

	}
}
