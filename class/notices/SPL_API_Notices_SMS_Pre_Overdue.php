<?php

class SPL_API_Notices_SMS_Pre_Overdue extends SPL_API_Notices_SMS {

	public function sendPreOverdueNotices() {

		return $this->sendPreOverdueNoticeBatch();		
		
	}

	protected function sendPreOverdueNoticeBatch() {
		$borrowers = $this->getPreOverdueNoticeBatchBorrowers();

		$batch = array_map(array($this, 'getPreOverdueNoticeBatchBorrower'), $borrowers);
		//return $batch;

		$notices = array_map(array($this, 'sendPreOverdueNoticeBatchBorrower'), $batch);

		return $notices;
	}

	protected function sendPreOverdueNoticeBatchBorrower($notice) {
		// devmode
		//$notice->phone = $this->getBorrowerSMSPhone(203068, 'preod');
		$notice->phone = $this->getBorrowerSMSPhone($notice->borrower, 'preod');
		//return $notice;
		
		if ( !empty($notice->items) ) {
			$dt = new DateTime();
  	  		$sent = $dt->format('Y-m-d H:i:s');
	    
		    if ( is_array($notice->items) ) {
			    foreach ( $notice->items as $item ) {
					$location = $item->trans_location_name;

					$preod = new stdClass();
					$preod->borrower = $item['borrower'];
					$preod->item = $item['item'];
					$preod->trans_location = $item['location'];
					$preod->reference = null;
					$preod->block = null;
					$preod->block_date = null;
					$preod->block_time = null;
					$preod->block_date_formatted = null;
					$preod->timestamp = $notice->timestamp;
					$preod->hmac = $notice->hmac;
					$preod->date_sent = $sent;
					$preod->notice_type = 'preod';
					if ( $notice->phone ) {
						// log items individually
						$log = $this->logNotice($preod);
					}
					//return $log;
					$del = $this->removePreOverdueFromQueue($preod);
			    	//return $del;
			    }
		  	}

			$msg = '';
			$msg .= 'Spokane Public Library:'.PHP_EOL;
			$msg .= 'Material due soon.'.PHP_EOL;
			$msg .= 'www.spokanelibrary.org/'.$this->endpoint.'/'.$notice->timestamp.'/'.$notice->hmac.PHP_EOL;
			$msg .= PHP_EOL;
			$msg .= 'You can reply RENEW anytime.';
			//return $notice;
			//return $msg;

			$sms = null;
			if ( $notice->phone ) {
				//$notice->phone = $this->getBorrowerSMSPhone(203068, 'preod');
				//$sms = $notice->phone;
				$sms = $this->sendSMS($notice->phone, $msg);	
				$log = $this->logNoticeSMS($sms, $notice->hmac);
	    	} else {
	    		$sms = 'Not an sms subsciber.';
	    		//$sms = $notice;
	    	}

			return $sms;
		} else {
			$sms = 'No items found.';
	    	//$sms = $notice;
	    	$del = $this->removeBorrowerFromPreOverdueQueue($notice);
		}

		return $sms; 
	}

	protected function removePreOverdueFromQueue($preod) {
		//return $preod;
		$params = array(':item' => $preod->item);
		//return $params;
		$sql = "DELETE
						FROM SPL_Connect.dbo.spl_api_sms_queue_preod
						WHERE item = :item
		";

		return $this->getQuery($sql, $params);
	}

	protected function removeBorrowerFromPreOverdueQueue($notice) {
		//return $notice;
		$params = array(':borrower' => $notice->borrower);
		//return $params;
		$sql = "DELETE
						FROM SPL_Connect.dbo.spl_api_sms_queue_preod
						WHERE borrower = :borrower
		";

		return $this->getQuery($sql, $params);
	}

	protected function getPreOverdueNoticeBatchBorrower($borrower) {
		$dt = new DateTime();

		$notice = new stdClass();
		$notice->borrower = $borrower['borrower'];
		$notice->datestamp = $dt->format('Y-m-d H:i:s');

		$notice->timestamp = $dt->getTimestamp();
		$notice->hmac = $this->getBorrowerTimestampHMAC($notice->borrower, $notice->timestamp);

		$params = array(':borrower'=>$notice->borrower
									, ':status'=>'o'
									);
		$sql = "SELECT
						queue.item
						,queue.borrower
						,item.location
						FROM SPL_Connect.dbo.spl_api_sms_queue_preod AS queue
						-- only get items still out
						JOIN item
							ON queue.item = item.item#
							AND item.borrower# = :borrower
							AND item.item_status = :status
						WHERE 
						borrower = :borrower
		";
		$notice->items = $this->getQuery($sql, $params);

		return $notice;
	}

	protected function getPreOverdueNoticeBatchBorrowers() {
		$params = array();
		$sql = "SELECT TOP ".$this->batch." borrower
            FROM (
              SELECT DISTINCT borrower 
              FROM SPL_Connect.dbo.spl_api_sms_queue_preod
          	) queue
		";
		$borrowers = $this->getQuery($sql, $params);

		return $borrowers;		
	}

}

?>