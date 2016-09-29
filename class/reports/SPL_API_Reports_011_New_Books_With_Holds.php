<?php

class SPL_API_Reports_011_New_Books_With_holds extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();
		$report->meta = $meta;

		$params = array();

		$sql = "SELECT
						MAX(bib_control.bib#) AS bib
						--,item.ibarcode AS barcode
						,MAX(dbo.spl_api_extract_subfield_text('a', bib_092.text)) AS call_number
						--,MAX(dbo.spl_api_extract_subfield_text('h', bib_245.text)) AS bib_245h
						,MAX(item.processed) AS title
						,MAX(CONVERT( date, dbo.spl_get_datetime_from_epoch( po_line_item.receive_date) ))AS received_date
						,MAX(item.collection) AS collection
						,MAX(item.itype) AS itype
						--,MAX(collection.descr) AS collection
						--,MAX(item_status.descr) AS item_status
						,(SELECT COUNT(bib#) FROM item as items WHERE MAX(item.bib#) = items.bib# AND item_status IN('n','t')) AS copies
						,(SELECT COUNT(bib#) FROM request WHERE request.bib# = MAX(item.bib#)) AS request_count
						FROM bib_control
						JOIN item_with_title AS item
							ON item.bib# = bib_control.bib#
							AND item.item_status IN ('n', 't')
							AND item.collection NOT IN ('star')
							AND item.collection NOT LIKE '%hd'
							AND item.ibarcode LIKE ('acq%')
						JOIN request
							ON request.bib# = bib_control.bib#
						JOIN item_status
							ON item.item_status = item_status.item_status
						--JOIN collection
							--ON item.collection = collection.collection
						LEFT OUTER JOIN bib AS bib_092
							ON bib_control.bib# = bib_092.bib# 
							AND bib_092.tag='092'
						--LEFT OUTER JOIN bib AS bib_245
							--ON bib_control.bib# = bib_245.bib# 
							--AND bib_245.tag='245'
						LEFT OUTER JOIN po_line_item
							ON item.item# = po_line_item.item#

						GROUP BY
						bib_control.bib#, item.collection, receive_date

						ORDER BY
						receive_date DESC, request_count DESC
            ";

    $result = $this->getQuery($sql, $params);

    $report->sorted = $this->sortReportData($result);

    return $report;
	}

	protected function sortReportData($data) {
		$data = array_map(array($this, 'normalizeReportData'), $data);
		
		$av = array('dvda','dvdanf','dvdhda','dvdhdj','dvdj','dvdjnf','musa','musj','spcd');

		$sorted = array();
		foreach ( $data as $bib ) {
			if ( in_array($bib['itype'], $av) ) {
				//$sorted['av'][] = $bib;
				if ( $bib['request_count'] < 3 ) {
					$sorted['av']['few'][] = $bib;
				} else {
					$sorted['av']['many'][] = $bib;
				}
			} else {
				//$sorted['print'][] = $bib;
				if ( $bib['request_count'] < 3 ) {
					$sorted['print']['few'][] = $bib;
				} else {
					$sorted['print']['many'][] = $bib;
				}
			}
		}
		
		return $sorted;
		return $data;
	}

	protected function normalizeReportData($data) {
		if ( strlen($data['title']) > 40 ) {
			$elide = true;
		}
		$data['title'] = ucfirst( substr(utf8_encode($data['title']), 0, 30) );
		if ( $elide ) {
			$data['title'] .= '&hellip;';
		}
		$data['call_number'] = substr($data['call_number'], 0, 10);
		
		$date = new DateTime($data['received_date']);
		$data['received_date'] = str_ireplace(' ', '&nbsp;', $date->format('M d'));

		return $data;
	}

}

?>