<?php

class SPL_API_Reports_208_Borrowers_With_Many_Line_Items extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		
		$report = new stdClass();
		$report->meta = $this->params['vals'];

		
		$params = array(':line_items'=>'50', ':blocked'=>'50', ':debt_collect'=>'75', ':cutoff'=>'365');

		$sql = "SELECT
				DISTINCT burb.borrower# AS borrower
				,borrower_barcode.bbarcode AS bbarcode
				,UPPER(borrower.name) AS bname
				,borrower.location AS location
				,location.name AS lname
				,COUNT(burb.block) AS blocks
				,SUM(burb.amount) * .01 AS amount
				,dbo.spl_get_datetime_from_epoch(borrower.last_authentication_date) AS last_auth
				,dbo.spl_get_datetime_from_epoch(borrower.last_cko_date) AS last_cko
				,CASE WHEN SUM(burb.amount) * .01 >= :blocked 
					THEN 1
					ELSE NULL
					END AS blocked
				,CASE WHEN SUM(burb.amount) * .01 >= :debt_collect 
					THEN 1
					ELSE NULL
					END AS debt_collect
				,borrower.last_cko_date
				FROM burb
				LEFT OUTER JOIN borrower_barcode
					ON borrower_barcode.borrower# = burb.borrower#
					AND borrower_barcode.ord = 0
				LEFT OUTER JOIN borrower
					ON borrower.borrower# = burb.borrower#
				LEFT OUTER JOIN location
					ON borrower.location = location.location
				WHERE burb.amount > 0
				AND ( borrower.last_authentication_date > DATEDIFF( dd, '01/01/1970', GETDATE() ) - :cutoff
					OR borrower.last_cko_date > DATEDIFF( dd, '01/01/1970', GETDATE() ) - :cutoff )
				GROUP BY 
				burb.borrower#
				,borrower_barcode.bbarcode
				,borrower.name
				,borrower.location
				,borrower.last_authentication_date
				,borrower.last_cko_date
				,location.name
				HAVING
				COUNT(burb.block) > :line_items
				--AND SUM(burb.amount) * .01 < :blocked
				ORDER BY borrower.location ASC, amount, blocked, debt_collect, borrower.last_cko_date ASC

			";
		

    $result = $this->getQuery($sql, $params);
    //$report->sorted = $result;
    $report->sorted = $this->sortReportData($result);

    return $report;
	}

	protected function sortReportData($data) {
		$data = array_map(array($this, 'normalizeReportData'), $data);
		
		$locations = $this->getReportLocations();

		//$sorted = array();
		foreach ( $locations as $l => $location ) { 		
			foreach ( $data as $borrower ) {
				if ( strtolower($borrower['location']) == strtolower($location['code']) ) {
					$locations[$l]['borrowers'][] = $borrower;
				}
			}
		}
		$sorted['locations'] = $locations;
		$sorted['all'] = $data;
		return $sorted;
		return $data;
	}

	protected function normalizeReportData($data) {

		$date = new DateTime($data['last_cko']);
		$data['last_cko'] = str_ireplace(' ', '&nbsp;', $date->format('M d, Y'));

		$date = new DateTime($data['last_auth']);
		$data['last_auth'] = str_ireplace(' ', '&nbsp;', $date->format('M d, Y'));

		$data['location'] = strtoupper($data['location']);

		return $data;
	}

}

?>