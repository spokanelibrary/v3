<?php

class SPL_API_Reports_147_Non_Res_Tieout extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();
		$report->meta = $meta;

		$params = array();

		$sql = "SELECT
							item.bib# AS bib
							,item.ibarcode AS barcode
							--,item.call_reconstructed
							,dbo.spl_api_extract_subfield_text('a', bib_092.text) AS call_number
							,dbo.spl_api_extract_subfield_text('h', bib_245.text) AS bib_245h
							,item.processed AS title
							,CONVERT( date, dbo.spl_get_datetime_from_epoch( po_line_item.receive_date) )AS received_date
							,collection.descr AS collection
							,item_status.descr AS item_status
							,(SELECT COUNT(bib#) FROM request WHERE request.bib# = item.bib#) AS request_count
							FROM
							item_with_title as item
							JOIN request
								ON item.bib# = request.bib#
							JOIN item_status
								ON item.item_status = item_status.item_status
							JOIN collection
								ON item.collection = collection.collection
							JOIN bib AS bib_092
								ON item.bib# = bib_092.bib# AND  bib_092.tag='092'
							JOIN bib AS bib_245
							ON item.bib# = bib_245.bib# AND  bib_245.tag='245'
							LEFT OUTER JOIN po_line_item
								ON item.item# = po_line_item.item#
							WHERE
							item.item_status IN ('n', 't')
							AND item.collection NOT IN ('star')
							ORDER BY 
							request_count DESC, item.bib# DESC, received_date DESC, collection, item_status
            ";

    $result = $this->getQuery($sql, $params);

    $sorted = array();
    if ( is_array($result) ) {
    	foreach ( $result as $i=>$item ) {
    		$sorted[$item['bib']]['call_number'] = $item['call_number'];
    		$sorted[$item['bib']]['title'] = ucfirst($item['title']);
    		$sorted[$item['bib']]['bib_245h'] = $item['bib_245h'];
    		$sorted[$item['bib']]['received_date'] = $item['received_date'];
    		$sorted[$item['bib']]['collection'] = $item['collection'];
    		$sorted[$item['bib']]['request_count'] = $item['request_count'];
    		$sorted[$item['bib']]['items'][] = $item; 
    	}
    }
    // trim number of barcodes
    foreach( $sorted as $b=>$bib ) {
    	$sorted[$b]['items'] = array_slice($bib['items'], 0, 5);
    } 
    $sorted = array_values($sorted);

    $report->sorted = $sorted;

    return $report;
	}

}

?>