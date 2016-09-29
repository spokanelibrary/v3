<?php

class SPL_API_Reports_022_Items_With_SS_Notes extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();
		//$report->meta = $meta;

		$params = array();

		
		$sql = "SELECT 
				ibarcode
				,substring( processed, 1, 30 ) title
				,internal_note
				FROM item_with_title 
				WHERE internal_note IS NOT NULL
 				AND internal_note LIKE 'SS %'
				ORDER BY ibarcode
            ";

    	$result = $this->getQuery($sql, $params);

	   	$report->sorted = $result;

    	$report->count = count($result);

	    return $report;
	}


}

?>