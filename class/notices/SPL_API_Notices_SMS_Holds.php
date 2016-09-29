<?php

class SPL_API_Notices_SMS_Holds extends SPL_API_Notices_SMS {

	public function sendHoldNotices() {
		
		// sms hold blocks are created in realtime
		$this->queueHolds();

    return $this->sendHolds();
	}

	protected function sendHolds() {
    $params = array(':block'=>$blocktype);

    $sql = "SELECT TOP ".$this->batch."
            spl_connect.dbo.spl_api_sms_queue_holds.*
            FROM spl_connect.dbo.spl_api_sms_queue_holds
    ";  

    $queue = $this->getQuery($sql, $params);
    //return $queue;
    if ( !empty($queue) ) {
      return array_map(array($this, 'sendHold'), $queue);
    }
  }

  protected function sendHold($queue) {
    $dt = new DateTime();
    $sent = $dt->format('Y-m-d H:i:s');
    $queue['items'] = json_decode($queue['items']);
    //return $queue;
    foreach ( $queue['items'] as $item ) {
      $location = $item->trans_location_name;
      
      $hold = $item;
      $hold->timestamp = $queue['timestamp'];
      $hold->hmac = $queue['hmac'];
      $hold->cutoff = $queue['cutoff'];
      $hold->date_sent = $sent;
      $hold->notice_type = 'hold';
      // log items individually
      $this->logNotice($hold);
    }
    

    $msg = 'Spokane Public Library:'.
    $msg .= PHP_EOL;
    $msg .= 'Your request is ready at '.$location.'.';
    $msg .= PHP_EOL;
    $msg .= 'www.spokanelibrary.org/'.$this->endpoint.'/'.$queue['timestamp'].'/'.$queue['hmac'];
    $msg .= PHP_EOL;
    $msg .= PHP_EOL;
    $msg .= 'Reply HOLD anytime for info.';
    //return $msg;
    $sms = $this->sendSMS($queue['phone'], $msg);
    $log = $this->logNoticeSMS($sms, $queue['hmac']);
    $del = $this->removeHoldsFromQueue($queue);
    //return $log;
  	return $sms;
  }

  protected function removeHoldsFromQueue($queue) {
    $params = array(':hmac'=>$queue['hmac']);
    $sql = "DELETE 
            FROM spl_connect.dbo.spl_api_sms_queue_holds
            WHERE hmac = :hmac
            ";
    $this->getQuery($sql, $params);
  }

  protected function queueHolds() {
    $batch = $this->getHoldNoticeBatch('hnsms');
    //return $batch;
    if ( $batch ) {
      $notices = array_map(array($this, 'getHoldBatchNotices'), $batch);
    }

    if ( $notices ) {
      $queue = $this->queueHoldNotices($notices);
    }

    return $queue;
  }

  protected function queueHoldNotices($notices) {
    return array_map(array($this, 'queueHoldNotice'), $notices);
  }

  protected function queueHoldNotice( $notice ) {
    //return $notice->phone;
    $params = array(
            ':borrower' => $notice->borrower
            ,':timestamp' => $notice->timestamp
            ,':cutoff' => $notice->cutoff
            ,':hmac' => $notice->hmac
            ,':block' => $notice->block
            ,':phone' => $notice->phone
            ,':items' => json_encode($notice->items)
            );

    $sql = "INSERT
            INTO
            spl_connect.dbo.spl_api_sms_queue_holds
            (borrower, timestamp, cutoff, hmac, block, phone, items)
            VALUES(
             :borrower
            ,:timestamp
            ,:cutoff
            ,:hmac
            ,:block
            ,:phone
            ,:items
            )

    ";

    //return $notice;
    //return $params;
    $this->getQuery($sql, $params);
  }

  protected function getHoldBatchNotices($borrower) {
    $notice = new stdClass();

    $dt = new DateTime();
    $notice->timestamp = $dt->getTimestamp();
    $notice->borrower = $borrower['borrower'];
    $notice->block = $borrower['block'];
    
    //devmode
    //$notice->phone = $this->getBorrowerSMSPhone(203068, 'hold');
    $notice->phone = $borrower['phone'];

    $now = new DateTime($dt->format('Y-m-d H:i'));
    $today = new DateTime($now->format('Y-m-d'));
    // minutes from today at 00:00:00 until now
    $minutes = ( $now->getTimestamp() - $today->getTimestamp() ) / 60;
    // minutes from today at 00:00:00 that is safe to send
    $notice->cutoff = $minutes - $this->offset;
    
    $notice->hmac = $this->getBorrowerTimestampHMAC($notice->borrower, $notice->timestamp);

    $items = array();
    
    $process = true; 
    foreach ( $borrower['blocks'] as $b => $block ) {
      if ( $block['time'] > $notice->cutoff ) {
        $process = false;
      }
      $items[] = $block;
    }
    $notice->items = $items;

    if ( $process ) {
      return $notice;
    } else {
      return 'skipping batch';
    }
    
  }  

  protected function getHoldNoticeBatch($blocktype=null) {
    
    if ( $blocktype ) {
      $params = array(':block'=>$blocktype);

      $sql = "SELECT
              burb.block
              ,burb.borrower# AS borrower
              ,burb.reference# AS reference
              ,burb.item#  AS item
              ,burb.date
              ,burb.time
              ,burb.trans_location
              ,location.name AS trans_location_name
              ,(SELECT 
                TOP 1 phone_no 
                FROM 
                borrower_phone
                WHERE 
                borrower# = burb.borrower#
                AND (
                  sms_allow_hold = 1 
                )
              ) AS phone
              FROM burb
              JOIN location
                ON location.location = burb.trans_location
              
              /*
              JOIN borrower_phone
                ON borrower_phone.borrower# = burb.borrower#
                AND borrower_phone.ord = 0
              */

              WHERE block = :block

              -- send each block just once
              AND NOT EXISTS (
                SELECT DISTINCT spl_connect.dbo.spl_api_sms_log_notice.reference 
                FROM spl_connect.dbo.spl_api_sms_log_notice
                WHERE spl_connect.dbo.spl_api_sms_log_notice.reference = burb.reference#
              )
              
              -- limit batch size
              AND burb.borrower# IN (
                SELECT TOP ".$this->batch." borrower#
                FROM (
                  SELECT DISTINCT borrower#
                  FROM burb
                  WHERE block = :block
                  --AND burb.ord = 0
                  -- send each block just once
                  AND NOT EXISTS (
		                SELECT DISTINCT spl_connect.dbo.spl_api_sms_log_notice.reference 
		                FROM spl_connect.dbo.spl_api_sms_log_notice
		                WHERE spl_connect.dbo.spl_api_sms_log_notice.reference = burb.reference#
		              )
                ) AS borrowers
              )
              
      ";  
      
      $blocks = $this->getQuery($sql, $params);
      //return $blocks;
      if ( is_array($blocks) ) {
        $batch = array();
        
        foreach ( $blocks as $b => $block ) {
          $date = new DateTime('1970-01-01');
          $date->modify('+'.$block['date'].' days');
          if ( $block['time'] ) {
            $date->modify('+'.$block['time'].' minutes'); 
          }
          $block['date_formatted'] =  $date->format('Y-m-d H:i:s');
          $batch[$block['borrower']]['block'] = $blocktype; 
          $batch[$block['borrower']]['borrower'] = $block['borrower'];
          $batch[$block['borrower']]['phone'] = $block['phone'];
          $batch[$block['borrower']]['blocks'][] = $block;
        }
        
        return $batch;
      }
    }
  }
	

}

?>