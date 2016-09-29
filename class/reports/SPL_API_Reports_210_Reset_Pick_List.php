<?php

class SPL_API_Reports_210_Reset_Pick_List extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();

        $meta = $this->params['vals'];

		$report->meta = $meta;

        $params = array();
        if ( !empty($this->params['vals']['branch']) ) {
            if ( 'all' == $this->params['vals']['branch'] ) {
                $sql = "UPDATE 
                        request 
                        SET fill_date = NULL 
                        ,fill_location = NULL
                        ,fill_item# = NULL 
                        WHERE fill_date = datediff( dd, '01 jan 1970', getdate() )
                        AND request_status = 0 
                        ";
            } else {
                $params[':branch'] = $this->params['vals']['branch'];
                $sql = "UPDATE 
                        request 
                        SET fill_date = NULL 
                        ,fill_location = NULL
                        ,fill_item# = NULL 
                        WHERE fill_date = datediff( dd, '01 jan 1970', getdate() )
                        AND request_status = 0 
                        AND fill_location = :branch
                        ";   
            }

            if ( $sql ) {
                $result = $this->getQuery($sql, $params);   
                $report->sorted->reset = true;
            }

        }

        return $report;
	}

}

?>