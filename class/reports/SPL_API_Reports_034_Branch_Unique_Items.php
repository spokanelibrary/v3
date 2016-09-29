<?php

class SPL_API_Reports_034_Branch_Unique_Items extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();
		$report->meta = $meta;

		$params = array(':branch' => $this->params['vals']['branch']);

		$sql = "SELECT
						--*
						COUNT(item.bib#)			AS bib_count
						,collection.descr		AS collection

						FROM item 
						JOIN collection
							ON item.collection = collection.collection
						WHERE item.bib# IN (
							SELECT 
							DISTINCT item.bib#
							FROM item	
							WHERE
							item.location = :branch
							AND 
							item.collection NOT IN (  'afram', 'forn', 'fornj', 'pbka', 'pbkj' )
							AND
							item.bib# NOT IN (SELECT DISTINCT bib# 
												FROM item 
												WHERE 
												item.bib# = bib# 
												AND location != :branch
												)
							)
							AND 
							item.item_status NOT IN ( 'l', 'w', 'r', 'n' )
						GROUP BY collection.descr
						ORDER BY collection
						
					";
		$result = $this->getQuery($sql, $params);

		$report->meta->totals = $result;

		$sql = "SELECT
						--*
						item.bib#			AS bib
						,SUBSTRING(
						item.call_reconstructed 
						,0 
						,20
						)										AS call
						,item.ibarcode			AS barcode

						,title.processed		AS title

						,item.collection		AS icoll
						,collection.descr		AS collection

						,item.itype					AS itype
						,itype.descr				AS type

						,item.item_status		AS istatus
						,item_status.descr	AS status

						FROM item 
						JOIN title
							ON item.bib# = title.bib#
						JOIN item_status
							ON item.item_status = item_status.item_status
						JOIN itype
							ON item.itype = itype.itype
						JOIN collection
							ON item.collection = collection.collection
						WHERE item.bib# IN (
							SELECT 
							DISTINCT item.bib#
							FROM item	
							WHERE
							item.location = :branch
							AND 
							item.collection NOT IN (  'afram', 'forn', 'fornj', 'pbka', 'pbkj' )
							AND
							item.bib# NOT IN (SELECT DISTINCT bib# 
												FROM item 
												WHERE 
												item.bib# = bib# 
												AND location != :branch
												)
							)
							AND 
							item.item_status NOT IN ( 'l', 'w', 'r', 'n' )
						ORDER BY collection, call
            ";

    $result = $this->getQuery($sql, $params);

    $report->sorted = $result;

    return $report;
	}

}

?>