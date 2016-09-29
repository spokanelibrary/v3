<?php

class SPL_API_Reports_209_Weed_Non_Circ extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();
		$report->vals = $this->params['vals'];
		if ( is_array($report->vals) && isset($report->vals['init']) ) {
			$report->controls = $this->getReportControls();
			$report->controls['locations'] = $this->getReportLocations();
		} else {
			$report->sorted = $this->getReportValues();
			$report->sql = $this->sql;
		}

	    return $report;
	}

	protected function getReportValues() {
	    //return $this->params['vals'];
		$cgroup = $this->params['vals']['spl-weed-cgroup'];
		if ( $cgroup ) {
			//$collections = implode(',', $cgroup);
			//return $collections;
			//$collections = "'fica','ficbeg'";
			
			// yikes
			$collections = '';
			$i = 0;
			foreach ( $cgroup as $collection ) {
				if ( $i > 0 ) {
					$collections .= ' OR ';
				}
				$collections .= "collection = '".$collection."'";
				$i++;
			} 

			if ( !isset($this->params['vals']['spl-weed-non-viable']) ) {
				$nonviable = 'AND NOT ( item_status.available_for_request = 0 AND item_status.available_for_hold = 1 )';
			}

			if ( !isset($this->params['vals']['spl-weed-local']) ) {
				$local = "AND item.bib# NOT IN (SELECT bib# FROM bib WHERE bib.tag = '690' AND bib.cat_link_xref#=1270901)";
			}



			//return $collections;

		    $params = array(':months'=>$this->params['vals']['spl-weed-cutoff']
		    				,':location'=>$this->params['vals']['spl-weed-location']);
		    //return $params;
			$sql = "SELECT
					--COUNT(*) AS total
					--*
					item.bib# AS bib
					,item.ibarcode
					,item.call_reconstructed AS call_number
					,item_status.descr AS item_status
					,dbo.spl_get_datetime_from_epoch(item.last_cko_date) AS last_cko
					,item.n_ckos
					,dbo.spl_get_datetime_from_epoch(item.creation_date) AS create_date
					,dbo.spl_get_bib_title(item.bib#, 1) AS title
					FROM item
					LEFT OUTER JOIN item_status 
						ON item.item_status = item_status.item_status
					WHERE (" .$collections. ")
					AND item.last_cko_date IS NOT NULL
					AND ( DATEDIFF( dd, '01/01/1970', GETDATE() ) - item.last_cko_date ) > ( :months * 30 )
					AND item.location = :location
					".$nonviable."
					".$local."
					ORDER BY item.call_reconstructed ASC, item.last_cko_date DESC
				";
			
			$this->sql = $sql;
			//return $sql;
		    $result = $this->getQuery($sql, $params);
			
			return $this->sortReportData($result);
		}
	}

	protected function sortReportData($data) {
		$data = array_map(array($this, 'normalizeReportData'), $data);
		
		return $data;
	}

	protected function normalizeReportData($data) {
		if ( strlen($data['title']) > 60 ) {
			$elide = true;
		}
		$data['title'] = trim( ucfirst( substr(utf8_encode($data['title']), 0, 50) ) );
		if ( $elide ) {
			$data['title'] .= '&hellip;';
		}

		$data['call_number'] = str_ireplace(' ', '&nbsp;', $data['call_number']);

		$date = new DateTime($data['last_cko']);
		$data['last_cko'] = str_ireplace(' ', '&nbsp;', $date->format('M d, Y'));

		$date = new DateTime($data['create_date']);
		$data['create_date'] = str_ireplace(' ', '&nbsp;', $date->format('M d, Y'));

		return $data;
	}

	// Controls

	protected function getReportControls() {

		$groups = $this->getCollectionGroups();
		
		$codes = array();
		$collections = $this->getCollections();
		if ( is_array($collections) && is_array($groups)  ) {
			foreach ( $collections as $c => $collection ) {
				$coded = false;
				foreach ( $groups as $g => $group ) {
					$search = $g;
					if ( 'fa' == $g ) {
						$search = 'fa-'; 
					}
					if ( stristr($collection['code'], $search) ) {
						$codes[$g][$collection['code']] = $collection;
						$coded = true;
					}
				}
				if ( !$coded ) {
					$codes['other'][$collection['code']] = $collection;
				}
			}
		}

		foreach ( $groups as $g => $group ) {
			$cgroup['code'] = $g;
			$cgroup['label'] = $group;
			$cgroup['collections'] = array_values($codes[$g]);

			$cgroups[] = $cgroup;
		}

		$controls['cgroups'] = $cgroups;
		//$controls['collections'] = $collections;
		//$controls['codes'] = $codes;
		//$controls['groups'] = $groups;

		return $controls;
	}

	

	protected function getCollections() {
		$params = array();
		$sql = "SELECT
				collection AS code
				,descr AS label
				FROM collection
			";
	    $result = $this->getQuery($sql, $params);
	
		return $result;
	}

	protected function getCollectionGroups() {
		
		$groups['fic'] = 'Fiction';
		$groups['nf'] = 'Non-Fiction';
		$groups['dvd'] = 'DVDs';
		$groups['vhs'] = 'VHS';
		$groups['mus'] = 'Music CDs';
		$groups['spc'] = 'Spoken Audio';
		$groups['gen'] = 'Genealogy';
		$groups['graph'] = 'Graphic Lit';
		$groups['mag'] = 'Magazines';
		$groups['ya'] = 'Young Adult';
		$groups['lp'] = 'Large Print';
		$groups['nw'] = 'Northwest Room';
		$groups['ref'] = 'Reference';
		$groups['prof'] = 'Professional Resources';
		$groups['fa'] = 'Fast Add';
		$groups['clos'] = 'Closed Stacks';
		$groups['govdoc'] = 'Government Documents';
		
		$groups['other'] = 'All Other';

		return $groups;
	}

}

?>