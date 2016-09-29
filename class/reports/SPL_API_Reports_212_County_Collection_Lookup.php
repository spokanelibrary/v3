<?php

class SPL_API_Reports_090_Courtesy_Notices_Sent extends SPL_API_Reports {

	public function getReportData() {
		
		$report = new stdClass();
		$report->meta->display->{$this->params['vals']['notice']} = true;

		if ( $this->params['vals']['notice'] ) {

			if ( isset($this->params['vals']['barcode']) ) {
				switch ( $this->params['vals']['notice'] ) {
						case 'material':
							$result = $this->getMaterialDueNotices();
							break;
						case 'expiry':
							$result = $this->getCardExpiryNotices();
							break;
					}		
	    } else {
	    	$result = array('error'=>'No barcode supplied.');
	    }
	  } else {
	  	$result = array('error'=>'Notice type not specified.');
	  }

    if ( empty($result) ) {
    	$result = array('error'=>'No results found.');
    }

    $report->sorted = $result;
    
    $report->meta->params = $this->params;

    return $report;
	}

	protected function getMaterialDueNotices() {

		$params = array(':barcode'=>$this->params['vals']['barcode']);

		$sql = "SELECT
						*
						FROM spl_connect_material_due_log
						WHERE barcode = :barcode
						ORDER BY id DESC
            ";

    $result = $this->getQuery($sql, $params);

    return array_map(array($this, 'normalizeMaterialDueNotice'), $result);
	}

	protected function normalizeMaterialDueNotice($notice) {
		$notice['titles'] = json_decode($notice['titles']);
		$notice['response'] = json_decode($notice['response']);
		return $notice;
	}

	protected function getCardExpiryNotices() {
		$params = array(':barcode'=>$this->params['vals']['barcode']);

		$sql = "SELECT
						*
						FROM spl_connect_card_expiry_log
						WHERE barcode = :barcode
						ORDER BY id DESC
            ";

    $result = $this->getQuery($sql, $params);

    return array_map(array($this, 'normalizeCardExpiryNotice'), $result);
	}

	protected function normalizeCardExpiryNotice($notice) {
		$notice['response'] = json_decode($notice['response']);
		return $notice;
	}

}

?>