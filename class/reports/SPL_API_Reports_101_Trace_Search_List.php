<?php

class SPL_API_Reports_101_Trace_Search_List extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		
		$report = new stdClass();
		$report->meta = $this->params['vals'];

		
		$params = array();

		$sql = "SELECT 
				dbo.spl_get_datetime_from_epoch(last_status_update_date) AS last_status_update_date
				,call_reconstructed AS call_number
				,collection
				,location
				,ibarcode AS barcode
				,item_status
				,dbo.spl_get_bib_title(item.bib#, 1) AS title
				,item.bib# AS bib
				FROM item
				WHERE 
				
				(
					item_status = 'trace2'
				)
				OR
				(
					item_status = 'trace' 
					AND last_status_update_date < ( DATEDIFF( dd, '01/01/1970', GETDATE() ) - 30  ) 
				)
				OR
				(
					item_status = 'c' 
					AND last_status_update_date < ( DATEDIFF( dd, '01/01/1970', GETDATE() ) - 300  ) 	
				)
			";
		if ( $this->params['vals']['dmg'] ) {
			$sql .="
					OR
					(
						item_status = 'dmg' 
						AND last_status_update_date < ( DATEDIFF( dd, '01/01/1970', GETDATE() ) - 60  ) 	
					)
				";
		}
		$sql .= "
				ORDER BY 
				collection
				,call_reconstructed
				,item_status
				,location
				,last_status_update_date
				DESC
            ";

    $result = $this->getQuery($sql, $params);

    $report->sorted = $this->sortReportData($result);

    return $report;
	}

	protected function sortReportData($data) {
		$data = array_map(array($this, 'normalizeReportData'), $data);
		
		$locations = $this->getReportLocations();

		//$sorted = array();
		foreach ( $locations as $l => $location ) { 		
			foreach ( $data as $item ) {
				if ( $item['location'] == $location['code'] ) {
					$locations[$l]['items'][] = $item;
				}
			}
		}
		$sorted['locations'] = $locations;
		$sorted['all'] = $data;
		return $sorted;
		return $data;
	}

	protected function normalizeReportData($data) {
		if ( strlen($data['title']) > 40 ) {
			$elide = true;
		}
		$data['title'] = trim( ucfirst( substr(utf8_encode($data['title']), 0, 30) ) );
		if ( $elide ) {
			$data['title'] .= '&hellip;';
		}
		$data['call_number'] = substr($data['call_number'], 0, 10);
		
		$date = new DateTime($data['last_status_update_date']);
		$data['last_status_update_date'] = str_ireplace(' ', '&nbsp;', $date->format('M d'));

		return $data;
	}

}

?>