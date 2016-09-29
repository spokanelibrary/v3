<?php

class SPL_API_Reports_177_Item_Borrower_History extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();

		$meta = $this->params['vals'];

		$report->meta = $meta;

		
		$params= array(':ibarcode'=>$this->params['vals']['ibarcode']);

    $sql = "SELECT 
         item.item# item_number
        ,title.processed title
        ,borrower.name borrower_name
        ,barcode.bbarcode borrower_barcode
        ,stats.item_status_old stat_old
        ,stats.item_status_new stat_new 
        ,stats.cur_location
        ,stats.user_id
        , CONVERT(char (11),stats.date, 20) status_date
            
            FROM 
            item 

            JOIN
            spl_stats_daily stats
            ON
            item.item# = stats.item#
            
            JOIN
            title
            ON
            title.bib# = item.bib#
            
            LEFT OUTER JOIN borrower
            ON
            stats.borrower# = borrower.borrower#

            LEFT OUTER JOIN borrower_barcode barcode
            ON
            stats.borrower# = barcode.borrower#
            
            WHERE 
            ibarcode = :ibarcode
        ORDER BY stats.date DESC";

		$result = $this->getQuery($sql, $params);	

    $report->sorted->detail = $result;


    return $report;
	}

}

?>