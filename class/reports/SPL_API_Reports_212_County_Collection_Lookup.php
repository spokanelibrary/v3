<?php

class SPL_API_Reports_212_County_Collection_Lookup extends SPL_API_Reports {

	public function getReportData() {
		
		$report = new stdClass();

		if ( $this->params['vals']['lastname'] ) {
			$result = $this->lookupBorrowerName();
		} else {
		  	$result = array('error'=>'Name not specified.');
		}

	    if ( empty($result) ) {
	    	//$result = array('error'=>'No results found.');
	    }

	    $report->sorted = $result;

	    return $report;
	}

	protected function lookupBorrowerName() {

		$params = array(':lastname'=>'%'.$this->params['vals']['lastname'].'%');
		
		$sql = "SELECT
				*
				FROM SPL_Connect.dbo.spl_county_collections
				WHERE name LIKE :lastname
            ";

    	$result = $this->getQuery($sql, $params);

    	foreach ( $result as $b => $borrower ) {
    		foreach ( $borrower as $k => $v ) {
    			$result[$b][$k] = str_ireplace('|', '', trim($v));
    			$result[$b]['birthdate'] = 'my birthdate';

    		}
    	}

    	return $result;
	}


    
}

?>