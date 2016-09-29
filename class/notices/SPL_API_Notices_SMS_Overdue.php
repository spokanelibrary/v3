<?php

class SPL_API_Notices_SMS_Overdue extends SPL_API_Notices_SMS {

	public function sendOverdueNotices() {

    return $this->sendOverdueNoticeBatch();    
    
  }

  protected function sendOverdueNoticeBatch() {
    $borrowers = $this->getOverdueNoticeBatchBorrowers();
    //return $borrowers;
    $batch = array_map(array($this, 'getOverdueNoticeBatchBorrower'), $borrowers);
    //return $batch;

    $notices = array_map(array($this, 'sendOverdueNoticeBatchBorrower'), $batch);

    return $notices;
  }

  protected function sendOverdueNoticeBatchBorrower($notice) {
    //devmode
    //$notice->phone = $this->getBorrowerSMSPhone(203068, 'overdue');
    $notice->phone = $this->getBorrowerSMSPhone($notice->borrower, 'overdue');
    //return $notice;
    if ( !empty($notice->items) ) {
      $dt = new DateTime();
      $sent = $dt->format('Y-m-d H:i:s');
      
      if ( is_array($notice->items) ) {
        foreach ( $notice->items as $item ) {
          $location = $item->trans_location_name;
          //return $item;
          $overdue = new stdClass();
          $overdue->borrower = $item['borrower'];
          $overdue->item = $item['item'];
          $overdue->trans_location = $item['location'];
          $overdue->reference = $item['reference'];
          $overdue->block = null;
          $overdue->block_date = null;
          $overdue->block_time = null;
          $overdue->block_date_formatted = null;
          $overdue->timestamp = $notice->timestamp;
          $overdue->hmac = $notice->hmac;
          $overdue->date_sent = $sent;
          $overdue->notice_type = 'overdue';
          //return $overdue;
          if ( $notice->phone ) {
            // log items individually
            $log = $this->logNotice($overdue);
            //return $log;
          }
          $del = $this->removeOverdueFromQueue($overdue);
          //return $del;
        }
      }

      $msg = '';
      $msg .= 'Spokane Public Library:'.PHP_EOL;
      $msg .= 'Material overdue.'.PHP_EOL;
      $msg .= 'www.spokanelibrary.org/'.$this->endpoint.'/'.$notice->timestamp.'/'.$notice->hmac.PHP_EOL;
      $msg .= PHP_EOL;
      $msg .= 'You can reply RENEW anytime.';
      //return $notice;
      //return $msg;
      
      $sms = null;
      if ( $notice->phone ) {
        $notice->phone = $this->getBorrowerSMSPhone(203068, 'overdue');
        //$sms = $notice->phone;
        $sms = $this->sendSMS($notice->phone, $msg);  
        $log = $this->logNoticeSMS($sms, $notice->hmac);
      } else {
          $sms = 'Not an sms subsciber.';
      }

      return $sms;
    } else {
      $sms = 'No items found.';
        //$sms = $notice;
        $del = $this->removeBorrowerFromOverdueQueue($notice);
    }

    return $sms; 
    
  }

  protected function removeOverdueFromQueue($overdue) {
    //return $overdue;
    $params = array(':item' => $overdue->item);
    //return $params;
    $sql = "DELETE
            FROM SPL_Connect.dbo.spl_api_sms_queue_overdue
            WHERE item = :item
    ";

    return $this->getQuery($sql, $params);
  }

  protected function removeBorrowerFromOverdueQueue($notice) {
    //return $notice;
    $params = array(':borrower' => $notice->borrower);
    //return $params;
    $sql = "DELETE
            FROM SPL_Connect.dbo.spl_api_sms_queue_overdue
            WHERE borrower = :borrower
    ";

    return $this->getQuery($sql, $params);
  }

  protected function getOverdueNoticeBatchBorrower($borrower) {
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
            ,queue.reference
            ,item.location
            FROM SPL_Connect.dbo.spl_api_sms_queue_overdue AS queue
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

  protected function getOverdueNoticeBatchBorrowers() {
    $params = array();
    $sql = "SELECT TOP ".$this->batch." borrower
            FROM (
              SELECT DISTINCT borrower 
              FROM SPL_Connect.dbo.spl_api_sms_queue_overdue
            ) queue
    ";
    $borrowers = $this->getQuery($sql, $params);

    return $borrowers;    
  }
	

}

?>