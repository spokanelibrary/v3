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
		return 'joy';
		$this->pdo = new PDO($this->config['api']['connect']
                        ,$this->config['api']['web_user']
                        ,$this->config['api']['web_pass']
                        );

		//$params = array(':lastname'=>'%'.$this->params['vals']['lastname'].'%');
		$params = array();
		$sql = "SELECT
						*
						FROM spl_county_collections
						WHERE name LIKE ".'%'.$this->params['vals']['lastname'].'%'."
            ";

    	$result = $this->getQuery($sql, $params);
	}

    
}

?>