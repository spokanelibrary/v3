<?php

class SPL_API_Reports_184_Borrowers_Forced_Debt_Collect extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();

		//$meta = $this->params['vals'];

		//$report->meta = $meta;

		
		$params= array();

    $sql = "SELECT 
         --item.item#
         borrower_barcode.bbarcode          AS barcode

        ,CONVERT( 
            VARCHAR(10), 
            DATEADD( dd, 
                    burb.date, 
                    '01/01/1970' )
            , 101 )             AS block_date

        
        FROM burb

        JOIN borrower_barcode
            ON burb.borrower# = borrower_barcode.borrower#
            
        WHERE
            burb.block = 'fdc' --'fdc' --'bc'

        ORDER BY 
        burb.date DESC";

		$result = $this->getQuery($sql, $params);	

    $report->sorted->detail = $result;


    return $report;
	}

}

?>