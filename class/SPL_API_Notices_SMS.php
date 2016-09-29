<?php
//error_reporting(E_ALL);
ini_set("memory_limit","512M");

require_once('base/SPL_DB.php');

require('/var/web/---/php/Services/Twilio.php');

/*
 * Some queries
 * ToDo: setup a routine to clear out sms_notice table


  --select * from sms_notice
  --select * from burb where block like '%sms%'

  --truncate table spl_api_sms_queue_holds
  --select top 10 * from SPL_Connect.dbo.spl_api_sms_queue_holds

  --exec spl_connect_populate_material_due_queue_sms
  --select * from SPL_Connect.dbo.spl_api_sms_queue_preod

  --exec spl_connect_populate_overdue_queue_sms
  --select * from SPL_Connect.dbo.spl_api_sms_queue_overdue

  --truncate table spl_api_sms_log_notice
  --select * from SPL_Connect.dbo.spl_api_sms_log_notice

*/

class SPL_API_Notices_SMS extends SPL_DB {

  var $apikey;
  var $method;
  var $params;
  var $config;


  var $sid; // Twilio Account SID
  var $token; // Twilio Auth Token
  var $from; // Twilio number 
  var $endpoint = 'notice'; // SPL website landing page

  var $hzws = 'https://app.spokanelibrary.org/v3/hzws/';

  var $offset = 60; // minutes to delay before processing block (holds only)
  var $batch = 10; // number of borrowers to process per batch 

  function __construct($config=null, $request=null, $apikey=null) {
    
    if ( is_array($config) ) {
      $this->config = $config;

      $this->sid = $config['api']['twilio']['sid'];
      $this->token = $config['api']['twilio']['token'];
      $this->from = $config['api']['twilio']['from'];

      if ( isset($config['api']['horizon']) 
        && isset($config['api']['hz_user']) 
        && isset($config['api']['hz_pass']) ) { 
        
        parent::__construct( $config['api']['horizon']
                            ,$config['api']['hz_user']
                            ,$config['api']['hz_pass']
                            );
      }
      
      if ( is_array($request) ) {
        $this->params = $request['params'];
        $this->method = $request['method'];
      }

      $this->apikey = $apikey;
      
    }

    $this->initApi();
  }
  
  private function initApi() {

  }

  public function getTwilioReply() {
    require 'notices/SPL_API_Notices_SMS_Reply.php';
    $sms = new SPL_API_Notices_SMS_Reply($this->config, $this->request, $this->apikey);
    switch ( $this->method[1] ) {
      case 'voice':
        header("content-type: text/xml");
        echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        echo $sms->sendTwilioVoiceReply();
        exit;
        break;
      case 'sms':
        //return $sms->sendTwilioSMSReply();
        header("content-type: text/xml");
        echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        echo $sms->sendTwilioSMSReply();
        exit;
      break;
    }
  }

  public function getApiRequest() {
    //return $this->sendTestMessage();

    if ( !isset($this->method[1]) ) {
      return array('error'=>'no method specified');
    } else {
      switch ( $this->method[1] ) {
        case 'notice-lookup':
            return $this->lookupNotice();
          break;

        case 'add-phone':
            return $this->addPhoneNumber($this->params);
          break;

        case 'send-holds':
          require 'notices/SPL_API_Notices_SMS_Holds.php';
          $sms = new SPL_API_Notices_SMS_Holds($this->config, $this->request, $this->apikey);
          return $sms->sendHoldNotices();
          break;

        case 'send-preod':
          require 'notices/SPL_API_Notices_SMS_Pre_Overdue.php';
          $sms = new SPL_API_Notices_SMS_Pre_Overdue($this->config, $this->request, $this->apikey);
          return $sms->sendPreOverdueNotices();
          break;

        case 'send-overdue':
          require 'notices/SPL_API_Notices_SMS_Overdue.php';
          $sms = new SPL_API_Notices_SMS_Overdue($this->config, $this->request, $this->apikey);
          return $sms->sendOverdueNotices();
          break;

        default:
          return array('error'=>'invalid method specified');
          break;
      }
    }

  }

  protected function addPhoneNumber($request, $type='zsms') {
    if ( $request['borrower'] && $request['phone'] ) {
      
      //return array($request, $type);
      
      $params = array(':borrower'=>$request['borrower']);
      $sql = "SELECT 
              *
              FROM borrower_phone
              WHERE borrower# = :borrower 
      ";  

      $phones = $this->getQuery($sql, $params);

      if ( is_array($phones) ) {
        foreach ( $phones as $phone ) {
          if ( $type == $phone['phone_type'] ) {
            // update existing SMS number
            $params = array(':borrower'=>$request['borrower']
                          , ':ord'=>$phone['ord']
                          , ':phone_no'=>$request['phone']);
            $sql = "UPDATE borrower_phone
                    SET phone_no = :phone_no
                      ,sms_allow_pre_od = 1
                      ,sms_allow_od = 1
                      ,sms_allow_hold = 1
                      ,sms_allow_general = 1
                    WHERE borrower# = :borrower 
                    AND ord = :ord
            "; 
            
            $this->getQuery($sql, $params);

            return array('message'=>'Your SMS number has been changed to: '.$request['phone'].'.');
          } else {
            //return 'No current SMS phones';
            $params = array(':borrower'=>$request['borrower']);
            $sql = "SELECT 
                    MAX(ord) AS ord
                    FROM borrower_phone
                    WHERE borrower# = :borrower 
            ";  
            $result = $this->getQuery($sql, $params);
            $ord = $result[0]['ord']+1;
          }
        }
      } else {
        //return 'No current phone numbers';
        $ord = 0;
      }

      // insert new SMS number
      
      $params = array(':borrower'=>$request['borrower']
                          , ':ord'=>$ord
                          , ':phone_no'=>$request['phone']
                          , ':phone_type'=>$type);
      
      
      $sql = "INSERT INTO borrower_phone (
              borrower#
              ,phone_no
              ,ord
              ,phone_type
              ,sms_allow_pre_od
              ,sms_allow_od
              ,sms_allow_hold
              ,sms_allow_general
            )
            VALUES (
              :borrower
              ,:phone_no
              ,:ord
              ,:phone_type
              ,1
              ,1
              ,1
              ,1  
            )
      ";
      
      $this->getQuery($sql, $params);
      
      return array('message'=>'We have added your SMS number: '.$request['phone'].'.');
      return $phones;
    } else {
      return array('error' => 'No phone number provided.');
    }
  }

  protected function lookupNotice() {
    if ( $this->params[0] && $this->params[1] ) {
      $timestamp = $this->params[0];
      $hmac = $this->params[1];
      //return $hmac;
      $params = array(':hmac'=>$hmac);
      
      $sql = "SELECT 
            notice_log.*
            ,item.processed AS title
            ,item.ibarcode
            ,item.item_status
            ,borrower.pin# AS pin
            ,borrower_barcode.bbarcode
            ,dbo.spl_get_datetime_from_epoch(item.due_date) AS due_date
            FROM spl_connect.dbo.spl_api_sms_log_notice AS notice_log
            JOIN borrower
             ON notice_log.borrower = borrower.borrower#
            JOIN borrower_barcode 
                  ON borrower.borrower# = borrower_barcode.borrower#
                  AND borrower_barcode.ord = 0
            JOIN item_with_title AS item
              ON notice_log.item = item.item#
            -- only select currently valid blocks
            --JOIN burb
              --ON spl_connect.dbo.spl_api_sms_log_notice.reference = burb.reference#
              --AND burb.block = 'hnsms'
            WHERE hmac = :hmac 
            AND notice_log.borrower = item.borrower#
      ";  

      $batch = $this->getQuery($sql, $params);

      if ( is_array($batch) && !empty($batch) ) {
        $borrower = $batch[0]['borrower'];
        if ( $hmac == $this->getBorrowerTimestampHMAC($borrower, $timestamp) ) {
          foreach ( $batch as $b => $burb ) {
            $params = array(':reference' => $burb['reference']);
            $sql = "SELECT 
            comment
            FROM burb
            WHERE reference# = :reference 
            AND block = 'note'
            ";
            $batch[$b]['notes'] = $this->getQuery($sql, $params); 
          }

          $batch = array_map(array($this, 'formatNotice'), $batch);

          return $batch;
        
        } else {
          return array('error'=>'Invalid notice');
        }
      } else {
        return array('error'=>'Expired notice');
      }

      
    }
  }

  protected function formatNotice($notice) {
    if ( $notice['due_date'] ) {
      $dt = new DateTime($notice['due_date']);
      $notice['due_date_formatted'] = $dt->format('D, M j');
      $notice['title'] = utf8_encode($notice['title']);
    }
    
    return $notice;
  }

  protected function getBorrowerSMSPhone($borrower, $allow=null) {

    $params = array(':borrower'=>$borrower);
    $sql = "SELECT 
                TOP 1 phone_no
                FROM 
                borrower_phone
                WHERE 
                borrower# = :borrower
                AND 
          ";

    switch ( $allow ) {
      case 'holds':
        $sql .= 'sms_allow_hold = 1';
        break;
      case 'preod':
        $sql .= 'sms_allow_pre_od = 1';
        break;
      case 'overdue':
        $sql .= 'sms_allow_overdue = 1';
        break;
      default:
        $sql .= 'sms_allow_general = 1';
    }

    $result = $this->getQuery($sql, $params);
    
    return $result[0]['phone_no'];
  }

  protected function logNoticeSMS($sms, $hmac) {
    //return $hmac;
    if ( $sms->sid && $hmac ) {
      $params = array(':sms_id'=>$sms->sid
                    , ':sms_to'=>$sms->to
                    , ':hmac'=>$hmac
                    );

      $sql = "UPDATE
              spl_connect.dbo.spl_api_sms_log_notice
              SET 
              sms_id = :sms_id
              ,sms_to = :sms_to
              WHERE
              hmac = :hmac
      ";

      return $this->getQuery($sql, $params);
    }
  }

  protected function logNotice($hold) {
    
    $params = array(
            ':borrower' => $hold->borrower
            ,':timestamp' => $hold->timestamp
            ,':hmac' => $hold->hmac
            ,':block' => $hold->block
            ,':date_sent' => $hold->date_sent
            ,':item' => $hold->item
            ,':reference' => $hold->reference
            ,':block_date' => $hold->date
            ,':block_time' => $hold->time
            ,':trans_location' => $hold->trans_location
            ,':block_date_formatted' => $hold->date_formatted
            ,':notice_type' => $hold->notice_type
            );
    //return $params;
    $sql = "INSERT
            INTO
            spl_connect.dbo.spl_api_sms_log_notice
            (borrower
            ,timestamp
            ,hmac
            ,block
            ,date_sent
            ,item
            ,reference
            ,block_date
            ,block_time
            ,trans_location
            ,block_date_formatted
            ,notice_type
            )
            VALUES
            (:borrower
            ,:timestamp
            ,:hmac
            ,:block
            ,:date_sent
            ,:item
            ,:reference
            ,:block_date
            ,:block_time
            ,:trans_location
            ,:block_date_formatted
            ,:notice_type
            )

    ";

    //return $notice;
    //return $params;
    return $this->getQuery($sql, $params);
  }
  
  ##################################

  protected function getBorrowerTimestampHMAC($borrower, $timestamp) {
    $algo = 'ripemd128';
    $encode = base64_encode($timestamp.$borrower);

    $hmac = hash_hmac($algo, $encode, $this->apikey);

    return $hmac;
  }

  protected function sendSMS($phone, $msg) {
    try {
      $client = new Services_Twilio($this->sid, $this->token);

      $message = $client->account->messages->sendMessage(
        $this->from, // From a valid Twilio number
        $phone, // Text this number
        $msg
      );

      return json_decode($message); 
    } catch(exception $e) {
      return $message;
    }
  }

  ##################################


  /**
   *
   * More advanced Curl Proxy  
   *
   */
  function curlJSON($url, $params, $method='post', $auth=null) {
    //return $this->curlProxy($url, $params, $method, $auth);
    //return json_decode($this->curlProxy($url, $params, $method, $auth));
    $proxy = $this->curlProxy($url, $params, $method, $auth);
    $curl = json_decode($proxy->response);
    $curl->httpcode = $proxy->httpcode;
    return $curl; 
  }

  function curlProxy($url, $params, $method='post', $auth=null) {
    $result = new stdClass();
    $result->response = false;

    // create a new cURL resource
    $ch = curl_init();
    
    if ( 'post' == $method ) {
      // setup for an http post
      curl_setopt($ch, CURLOPT_POST, 1);
      // 'cause cURL doesn't like multi-dimensional arrays
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    } elseif ( 'get' == $method ) {
      if ( is_array($params) ) {
      $url .= '?' . http_build_query($params);
      }
    } elseif ( 'delete' == $method ) {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    } elseif ( 'put' == $method ) {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }


    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_URL, $url);
    //curl_setopt($ch, CURLOPT_HEADER, false);

    // follow redirects
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    // set auth params
    if ( is_array($auth) ) {
      //curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);  
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); // CURLAUTH_ANY
      curl_setopt($ch, CURLOPT_USERPWD, $auth['user'] . ':' . $auth['pass']);
    }

    // set returntransfer to true to prevent browser echo
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
    $ua = $_SERVER['HTTP_USER_AGENT']; // optional
    if (isset($ua)) {
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    }
 
    // grab URL
    $result->response = curl_exec($ch);

    // grab http response code
    $result->httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
     
    // close cURL resource, and free up system resources
    curl_close($ch);

    return $result;
  }

} // CLASS

?>
