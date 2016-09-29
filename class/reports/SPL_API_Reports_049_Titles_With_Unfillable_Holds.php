<?php

class SPL_API_Reports_049_Titles_With_Unfillable_Holds extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();

        //$meta = $this->params['vals'];

		//$report->meta = $meta;

		
		$params= array();

        // BIB-level holds
        $sql = "SELECT 
                request.request# AS request
                ,request.bib# AS bib
                ,request.pickup_location AS location
                ,dbo.spl_get_bib_title(request.bib#, 0) AS title
                ,item.item# AS item
                ,dbo.spl_get_date_string_from_epoch(item.last_update_date) AS last_update
                ,item_status.descr AS item_status
                ,item.collection AS collection
                ,dbo.spl_get_date_string_from_epoch(item.due_date) AS due_date
                , CASE WHEN borrower.btype IN ('sl') 
                    THEN 'Yes'
                    ELSE 'No'
                    END AS is_staff
                FROM
                request
                JOIN borrower
                    ON request.borrower# = borrower.borrower#
                JOIN item
                    ON item.item# = (SELECT MAX(item#) FROM item WHERE item.bib# = request.bib# AND item.item_status IN ('c', 'l', 'm', 'w', 'trace', 'trace2') )
                JOIN item_status
                    ON item.item_status = item_status.item_status
                WHERE request.item# IS NULL
                AND ( 
                    SELECT COUNT(item#) 
                    FROM item 
                    WHERE request.bib#=item.bib# 
                    AND item.item_status NOT IN ('c', 'l', 'm', 'w', 'trace', 'trace2') 
                ) = 0
                ORDER BY title ASC";
                
    	$result = $this->getQuery($sql, $params);	

        $report->sorted->detail->bibs = $result;

        // ITEM-level holds
        $sql = "SELECT
                request.request# AS request
                ,request.bib# AS bib
                ,request.pickup_location AS location
                ,dbo.spl_get_bib_title(request.bib#, 0) AS title
                ,item.item# AS item
                ,item.ibarcode AS ibarcode
                ,dbo.spl_get_date_string_from_epoch(item.last_update_date) AS last_update
                ,item_status.descr AS item_status
                ,item.collection AS collection
                ,dbo.spl_get_date_string_from_epoch(item.due_date) AS due_date
                , CASE WHEN borrower.btype IN ('sl') 
                    THEN 'Yes'
                    ELSE 'No'
                    END AS is_staff
                FROM
                request
                JOIN borrower
                    ON request.borrower# = borrower.borrower#
                JOIN item
                    ON request.item# = item.item#
                    AND item.item_status IN ('c', 'l', 'm', 'w', 'trace', 'trace2')
                JOIN item_status
                    ON item.item_status = item_status.item_status
                WHERE request.item# IS NOT NULL
                ORDER BY title ASC";

        $result = $this->getQuery($sql, $params);   

        $report->sorted->detail->items = $result;

        // ITEM-level holds (no longer in collection)
        /*
        $sql = "SELECT
                *
                FROM
                request
                WHERE 
                request.item# IS NOT NULL
                AND (SELECT COUNT(item#) FROM item WHERE request.item# = item.item#) = 0";

            $result = $this->getQuery($sql, $params);   

        $report->sorted->detail->withdrawn = $result;
        */

        return $report;
	}

}

?>