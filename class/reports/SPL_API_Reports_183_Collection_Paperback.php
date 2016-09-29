<?php

class SPL_API_Reports_183_Collection_Paperback extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();

		$meta = $this->params['vals'];

		$report->meta = $meta;

    $hz = new DateTime('01/01/1970');
    $dt = new DateTime($this->params['vals']['datebegin']);
    $diff = $hz->diff($dt);
    $epoch = $diff->days; 

    $report->epoch = $epoch;

		
		$params = array(':location'=>$this->params['vals']['branch']
                  ,':date'=>$epoch);

    //$report->sorted = $params;

    
    $sql = "
        SELECT 
         --item.item#
         item.ibarcode          AS barcode
        ,item.bib#              AS bib
        ,title.processed        AS title
        ,author.processed       AS author
        ,bibcall.processed      AS bib_call
        ,location.name          AS location

        ,CONVERT( 
            VARCHAR(10), 
            DATEADD( dd, 
                    item.creation_date, 
                    '01/01/1970' )
            , 101 )             AS creation_date

        ,CONVERT( 
            VARCHAR(10), 
            DATEADD( dd, 
                    item.last_cko_date, 
                    '01/01/1970' )
            , 101 )             AS last_cko_date
            
        ,collection.descr       AS collection
        ,item_status.descr      AS status

        ,item.location          AS location_code
        ,item.item_status       AS status_code
        ,item.collection        AS collection_code

        FROM item

        LEFT OUTER JOIN title
            ON item.bib# = title.bib#
            
        LEFT OUTER JOIN bib AS bib_100
            ON item.bib# = bib_100.bib# AND bib_100.tag = '100'
        LEFT OUTER JOIN author
            ON author.auth# = bib_100.cat_link_xref#
            
        LEFT OUTER JOIN bibcall
            ON item.bib# = bibcall.bib#
            
        LEFT OUTER JOIN location
            ON item.location = location.location
            
        LEFT OUTER JOIN collection 
            ON item.collection = collection.collection

        LEFT OUTER JOIN item_status
            ON item.item_status = item_status.item_status
            
        WHERE 
        item.collection IN ('pbka','pbkj')
        AND item.location = :location
        AND item.last_cko_date <= :date

        ORDER BY 
        bibcall.processed, item.last_cko_date DESC
        ";   

		$result = $this->getQuery($sql, $params);	

    $report->sorted->detail = $result;
    

    return $report;
	}

}

?>