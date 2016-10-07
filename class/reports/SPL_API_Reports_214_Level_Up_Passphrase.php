<?php

class SPL_API_Reports_214_Level_Up_Passphrase extends SPL_API_Reports {

	// Make sure class name matches file name!
	
	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();
		//$report->meta = $meta;

		$params = array();

		
		$sql = "SELECT 
				passphrase
				FROM
				SPL_Connect.dbo.spl_connect_level_up_login
            ";

    	$result = $this->getQuery($sql, $params);

	   	$report->sorted = $result;

    	$report->count = count($result);

	    return $report;
	}


}

?>