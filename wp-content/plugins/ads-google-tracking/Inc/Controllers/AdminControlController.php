<?php

namespace Inc\Controllers;

use Inc\Models\Database;

class AdminControlController {

	public function truncate2TableAdsTracking(){
		$initData = new Database();
		if( isset($_POST['detroyall']) ){
			$paramDelete = $initData->destroyAllDataAdsTracking();
			if($paramDelete){
				return '<p>Has been successfully removed</p>';
			} else{
				return '<p>Has been unsuccessfully removed</p>';
			}
		}
	}

	public function list_view() {
		$database = new Database();
		$lists = $database->get_list();
		$arrLists = [];
		foreach($lists as $list){
			$arrLists[$list->ip]['info'][] = $list;		
		}
		foreach($lists as $i => $list){
			$arrLists[$list->ip]['ip'] = $list->ip;
			$arrLists[$list->ip]['count_visit'] = $list->count_visit;
		}
		return $arrLists;
	}


	public function csv_export(){
		if(isset($_POST['exportcsv'] )){
			$database = new Database();
			$lists = $database->get_list();
			$results = [];
			foreach($lists as $list){
				$results[$list->ip][] = $list;		
			}
			if (count($results) > 0) {
				$params[] = array('IP','COUNT VISIT','NAME','AGENT','KEYWORD', 'DEVIDE', 'DEVIDEMODEL', 'CAMPAIGNID', 'ADPOSITION', 'DATE');
			}
			foreach($results as $result){
				foreach ($result as $key => $info) {
					$paramsAdsTracking = [
						$info->ip,
						!isset($info->count_visit)?0:maybe_unserialize( $info->count_visit )['visit'],
						$info->name,
						$info->agent,
						$info->keyword,
						$info->device,
						$info->devicemodel,
						$info->campaignid,
						$info->adposition,
						$info->created_at,
					];
					$params[] = $paramsAdsTracking;
				}
			}
			ob_clean();
			header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=tracking_ads_'.time().'.csv');
            $out = fopen('php://output', 'w');
            fputs( $out, "\xEF\xBB\xBF" );
            foreach ($params as $fields) {
                fputcsv($out, $fields);
            }
            fclose($out);
            die();
		}
	}
}