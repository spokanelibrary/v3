<?php

class SPL_API_Reports_055_Accounts_Receivable extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();

        $meta = $this->params['vals'];

		$report->meta = $meta;


        $ut = new DateTime('1970-01-01');
        $dt = new DateTime($report->meta['datebegin']);

        $epoch =  $ut->diff($dt);

        $report->meta['epoch'] = $epoch->days;

		
		$params= array();

        if ( empty($report->meta['datebegin']) ) {
            $sql = "EXEC spl_reports_055_get_ar";
        } else {
            $params[':epoch'] = $report->meta['epoch'];
            $sql = "SELECT * FROM spl_reports_055_log WHERE epoch = :epoch ORDER BY id ASC";
        }
        

    	$result = $this->getQuery($sql, $params);	

        $report->sorted->detail =  array_map(array($this, 'normalizeReportData'), $result);

        

        return $report;
	}

    function normalizeReportData($row) {
        $row['quant'] = number_format($row['quant']);
        foreach ( $row as $k => $v ) {  
            if ( in_array($k, array('total', 'lost', 'fines', 'other')) ) {
                $row[$k] = number_format($row[$k], 2);
            }
        }
        
        return $row;
    }

}

?>