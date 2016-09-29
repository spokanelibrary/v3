<?php

class SPL_API_Notices_SMS_Reply extends SPL_API_Notices_SMS {

  //function __construct($config=null, $request=null, $apikey=null) {
    //$this->config = $config;
    //$this->request = $request;
    //$this->apikey = $apikey;
  //}

	public function sendTwilioVoiceReply() {
		
    $reply .= '<Response>'.PHP_EOL;
    $reply .= '<Say voice="man">'.PHP_EOL;
    $reply .= 'Hello.'.PHP_EOL;
    $reply .= 'You have reached the Spokane Public Library SMS service.'.PHP_EOL;
    $reply .= 'Please call us at five zero nine, four four four, five three, zero zero.'.PHP_EOL;
    $reply .= 'Or, visit us online at spokanelibrary dot, oh, are gee.'.PHP_EOL;
    $reply .= '</Say>'.PHP_EOL;
    $reply .= '<Pause length="1"/>'.PHP_EOL;
    $reply .= '<Say>'.PHP_EOL;
    $reply .= 'Again. That number is five zero nine, four four four, five three, zero zero.'.PHP_EOL;
    $reply .= '</Say>';
    $reply .= '<Pause length="1"/>'.PHP_EOL;
    $reply .= '<Say>';
    $reply .= 'Thank you, good bye.'.PHP_EOL;
    $reply .= '</Say>';
    $reply .= '<Pause length="2"/>'.PHP_EOL;
    $reply .= '</Response>'.PHP_EOL;

    return $reply;
	}

  public function sendTwilioSMSReply() {
    $this->logSMSRequest();

    if ( $_REQUEST['Body'] && $_REQUEST['From'] ) {
      if ( stristr('hold', trim($_REQUEST['Body']))
        || stristr('holds', trim($_REQUEST['Body'])) ) {
        if ( $_REQUEST['encode'] ) {
          $reply = $this->getHoldMessage(trim(urlencode($_REQUEST['From'])));
        } else {
          $reply = $this->getHoldMessage(trim($_REQUEST['From']));
        }
      }
      if ( stristr('renew', trim($_REQUEST['Body'])) ) {
        if ( $_REQUEST['encode'] ) {
          $reply = $this->getRenewMessage(trim(urlencode($_REQUEST['From'])));
        } else {
          $reply = $this->getRenewMessage(trim($_REQUEST['From']));
        }
      }

    }

    if ( !$reply ) {
      $reply = $this->getDefaultMessage();
    }
    //return $reply;
    return $this->wrapReply($reply);
    
  }

  protected function getRenewMessage($from) {
    // may have more than one borrower associated with a phone number
    $borrowers = $this->getBorrowersFromPhone($from);
    //return print_r($borrowers, true);
    if ( is_array($borrowers) && !empty($borrowers) ) {
      $items = array();
      foreach ( $borrowers as $borrower ) {
        $params = array(':borrower' => $borrower['borrower'], ':cutoff'=>3);
        $sql = "SELECT
                item.bib# AS bib
                ,item.item# AS item
                ,item.borrower# AS borrower
                ,item.ibarcode AS ibarcode
                ,processed AS title
                ,borrower.pin# AS pin
                ,borrower_barcode.bbarcode
                FROM item_with_title AS item
                -- only get items currently out to borrower
                JOIN borrower
                  ON item.borrower# = borrower.borrower#
                JOIN borrower_barcode 
                  ON borrower.borrower# = borrower_barcode.borrower#
                  AND borrower_barcode.ord = 0
                WHERE borrower.borrower# = :borrower --203068
                AND item.item_status IN ('o', 'l', 'w')
                -- only get items due w/in :cutoff days or less (or overdue)
                -- (we don't want items to get renewed early after the first sms renew)
                AND item.due_date < DATEDIFF( dd, '01/01/1970', GETDATE() ) + :cutoff
                -- only get items we have messaged about
                AND item.item# IN (
                  SELECT DISTINCT item FROM Spl_Connect.dbo.spl_api_sms_log_notice
                  WHERE borrower = :borrower
                )
        ";
        $result = $this->getQuery($sql, $params);
        if ( is_array($result) && !empty($result) ) {
          foreach ( $result as $block ) {
            $items[$block['ibarcode']] = $block;
          }
          $renew = $this->renewItems($items);
        }

        //$renew = json_decode($renew);
        if ( is_array($renew) ) {
          $success = array();
          $errors = array();
          foreach ( $renew as $item ) {
            $item->title = $items[$item->ibarcode]['title'];
            $item->title = $this->formatTitle($item->title);
            if ( '500' == $item->renewal->httpcode ) {
              $errors[] = $item;
            } elseif ( '200' == $item->renewal->httpcode) {
              $success[] = $item;
            }
          }

          if ( empty($success) ) {
            $msg .= 'No items could be renewed.'.PHP_EOL;
          } elseif ( !empty($success) ) {
            $msg .= 'The following items were renewed:'.PHP_EOL;
            foreach( $success as $rnw ) {
              //$msg .= $rnw->title.' - '.$rnw->ibarcode.PHP_EOL;
              $msg .= $rnw->title.' Due: '.$rnw->renewal->response->dueDate.PHP_EOL;
            }
          }

          if ( !empty($errors) ) {
            $msg .= 'The following items could not be renewed, and are still due:'.PHP_EOL;
            foreach( $errors as $error ) {
              $msg .= $error->title.' - '.$error->ibarcode.PHP_EOL;
            }
          } 
          
        } else {
          $msg .= 'We could not find any items to renew.'.PHP_EOL;
          //$msg .= print_r($borrower, true);
        }

        $this->logSMSRenew($borrower, $renew, $msg);
      }
      //$msg = $items;
      //$msg = $items;
      //$msg = $renew;
    } else {
      $msg = 'We were unable to find any records associated with this phone number.';
      $msg .= PHP_EOL;
      $msg .= 'Please note: we cannot look you up via SMS until we have sent you at least one SMS notice.';
    }
    return  $msg;
    //return print_r($msg, true);
  }

  protected function renewItems($items) {
    if ( is_array($items) ) {
      foreach ( $items as $item ) {
        $id['itemID'][] = $item['ibarcode'];
      }
      $api = array('apikey' => $this->config['api']['key']);
      $api['params'] = $id; 
      $borrower = current($items);
      $api['params']['barcode'] = $borrower['bbarcode'];
      $api['params']['pin'] = $borrower['pin'];

      //$renew = $id;
      //return $api;
      $renew = $this->curlProxy($this->hzws.'renew', $api);
      $renew = json_decode($renew->response);
    } else {
      $renew = array('error' => 'Unexpected error renewing item(s).');
    }

    return $renew;
  }

  protected function getBorrowersFromPhone($from) {
    $params = array(':sms_to'=>$from);
    $sql = "SELECT
            DISTINCT borrower
            FROM Spl_Connect.dbo.spl_api_sms_log_notice
            WHERE sms_to = :sms_to 
    ";

    $borrowers = $this->getQuery($sql, $params);
    
    return $borrowers;
  }

  protected function getHoldMessage($from) {
    $params = array(':sms_to'=>$from);
    $sql = "SELECT
            DISTINCT log.reference
            ,item.processed AS title
            FROM Spl_Connect.dbo.spl_api_sms_log_notice AS log
            JOIN item_with_title AS item
              ON log.item = item.item#
            -- limit to items still on hold
            JOIN burb 
              ON log.item = burb.item#
              AND log.borrower = burb.borrower#
              AND burb.block = 'hnsms'
            WHERE sms_to = :sms_to
            AND notice_type = 'hold'
    ";
    $holds = $this->getQuery($sql, $params);

    if (  is_array($holds) && isset($holds['error']) ) {
      $msg = 'We ran into a problem and are unable to retrieve your holds.';
      return $msg;
    }

    $msg = '';
    if ( is_array($holds) && !empty($holds) ) {
      $msg .= 'Items on hold:'.PHP_EOL.PHP_EOL;
      foreach ( $holds as $hold) {
        $msg .= $this->formatTitle($hold['title']).PHP_EOL;
        
        $params = array(':reference' => $hold['reference']);
        $sql = "SELECT 
                comment
                FROM burb
                WHERE reference# = :reference 
                AND block = 'note'
        ";
        $notes = $this->getQuery($sql, $params);
        if ( is_array($notes) ) {
          foreach ( $notes as $note ) {
            $msg .= $note['comment'].PHP_EOL;
          } 
          $msg .= PHP_EOL;
        }
        
      }
    } else {
      $msg .= 'You do not have any active holds.';
    }

    $this->logSMSHold($from, $holds, $msg);
    
    return $msg;
  }

  protected function getDefaultMessage() {
    $msg = 'Thanks for using the library!'.PHP_EOL;
    $msg .= 'Please give us a call: 509-444-5300'.PHP_EOL;
    $msg .= 'Or visit us online: www.spokanelibrary.org'.PHP_EOL;
    $msg .= PHP_EOL;
    $msg .= 'Reply HOLD to see your holds.'.PHP_EOL;
    $msg .= PHP_EOL;
    $msg .= 'Reply RENEW and we will attemp to renew overdue and near overdue items.';
    
    return $msg;
  }

  protected function formatTitle($title) {
    // json_encode parser chokes on some unicode chars
      $title = utf8_encode($title);
      // stip excess whitespace
      $title = preg_replace( '/\s+/', ' ', $title );
      // shorten title
      $title = substr($title, 0, 50);
      // normalize
      $title = ucfirst($title);

      return $title;
  }

  protected function wrapReply($msg) {
    $reply = '<Response>'.PHP_EOL;
    $reply .= '<Sms>'.PHP_EOL;
    $reply .= $msg.PHP_EOL;
    $reply .= '</Sms>'.PHP_EOL;
    $reply .= '</Response>'.PHP_EOL;

    return $reply;
  }

  protected function logSMSRequest() {
    $log = '/var/web/---/log/twilio-sms.log';
    $dt = new DateTime();
    file_put_contents($log, $dt->format('m-d-Y H:i:s').PHP_EOL, FILE_APPEND);
    file_put_contents($log, print_r($_REQUEST,true).PHP_EOL, FILE_APPEND);
    file_put_contents($log, '----------'.PHP_EOL, FILE_APPEND);
  }

  protected function logSMSRenew($borrower, $renew, $message) {
    $log = '/var/web/---/log/twilio-renew.log';
    $dt = new DateTime();
    file_put_contents($log, $dt->format('m-d-Y H:i:s').PHP_EOL, FILE_APPEND);
    file_put_contents($log, print_r($borrower,true).PHP_EOL, FILE_APPEND);
    file_put_contents($log, print_r($renew,true).PHP_EOL, FILE_APPEND);
    file_put_contents($log, print_r($message,true).PHP_EOL, FILE_APPEND);
    file_put_contents($log, '----------'.PHP_EOL, FILE_APPEND);
  }

  protected function logSMSHold($borrower, $hold, $message) {
    $log = '/var/web/---/log/twilio-hold.log';
    $dt = new DateTime();
    file_put_contents($log, $dt->format('m-d-Y H:i:s').PHP_EOL, FILE_APPEND);
    file_put_contents($log, print_r($borrower,true).PHP_EOL, FILE_APPEND);
    file_put_contents($log, print_r($hold,true).PHP_EOL, FILE_APPEND);
    file_put_contents($log, print_r($message,true).PHP_EOL, FILE_APPEND);
    file_put_contents($log, '----------'.PHP_EOL, FILE_APPEND);
  }

}

?>