<?php
//error_reporting(E_ALL);
ini_set("memory_limit","512M");

require_once('base/SPL_DB.php');

class SPL_API_Notices extends SPL_DB {

  var $apikey;
  var $method;
  var $params;
  var $config;

  var $devmode = false;
  var $devaddr = 'sgirard@spokanelibrary.org';

  var $batch = 10; // number of borrowers to process 
  var $offset = 3; // days in the future (needs to match the overnight proc!)

  //var $sender = 'Spokane Public Library <notice@news.spokanelibrary.org>';
  //var $domain = 'news.spokanelibrary.org';
  var $sender = 'Spokane Public Library <notice@notice.spokanelibrary.org>';
  var $domain = 'notice.spokanelibrary.org';
  var $mg_api = 'https://api.mailgun.net/v3/';

  function __construct($config=null, $request=null, $apikey=null) {
    
    if ( is_array($config) ) {
      $this->config = $config;

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

  public function getApiRequest() {
    //return $this->sendTestMessage();

    if ( !isset($this->method[1]) ) {
      return array('error'=>'no method specified');
    } else {
      switch ( $this->method[1] ) {
        case 'material-due':
          return $this->notifyBorrowersWithMaterialsDue();
          break;
        case 'card-expiry':
          return $this->notifyBorrowersWithExpiringCards();
          break;
        default:
          return array('error'=>'invalid method specified');
          break;
      }
    }

  }

  /**
   *
   * Card Expiry methods  
   *
   */

  protected function populateBorrowersWithExpiringCards() {
    //exec spl_connect_populate_card_expiry_queue 7, 30
    /*
    TRUNCATE TABLE spl_connect_card_expiry_queue

    INSERT INTO
    spl_connect_card_expiry_queue
    SELECT DISTINCT 
    borrower#
    FROM borrower
    WHERE expiration_date = DATEDIFF( dd, '01/01/1970', GETDATE() ) + 7

    INSERT INTO
    spl_connect_card_expiry_queue
    SELECT DISTINCT 
    borrower#
    FROM borrower
    WHERE expiration_date = DATEDIFF( dd, '01/01/1970', GETDATE() ) + 30

    --SELECT * FROM spl_connect_card_expiry_queue
    */

  }

  protected function notifyBorrowersWithExpiringCards() {
    $params = array();
    // paramaterized queries don't support TOP n :(
    $sql = "SELECT 
            TOP ".$this->batch." queue.borrower AS borrower
            ,bbarcode AS barcode
            ,addr.email_address AS email
            ,CONVERT(DATE, dbo.spl_get_datetime_from_epoch(borrower.expiration_date)) AS expiration_date
            ,( SELECT SUM(burb.amount) FROM burb WHERE burb.borrower# = queue.borrower ) * .01 AS owed
            ,CASE WHEN addr.email_name IS NOT NULL
              THEN addr.email_name
              ELSE dbo.spl_get_first_from_fullname(borrower.name_reconstructed)
              END AS name
            ,borrower.name_reconstructed AS full_name 
            FROM spl_connect_card_expiry_queue AS queue
            JOIN borrower
              ON borrower.borrower# = queue.borrower
            JOIN borrower_barcode AS barcode
              ON barcode.borrower# = queue.borrower
            LEFT OUTER JOIN borrower_address AS addr
              ON addr.borrower# = queue.borrower
              AND addr.ord = 0
            ORDER BY queue.borrower
          ";
    $result = $this->getQuery($sql, $params);
    
    //return $result;
    $borrowers = array_map(array($this, 'notifyBorrowerWithExpiringCard'), $result);
    return $borrowers;
  }

  protected function notifyBorrowerWithExpiringCard($borrower) {
    $expiry = new DateTime($borrower['expiration_date']);
    $borrower['expiry'] = $expiry->format('l F jS, Y');

    $borrower['response'] = $this->sendBorrowerCardExpirationMessage($borrower);

    $borrower['method'] = $this->method;

    if ( isset($borrower['response']) && !isset($borrower['response']->error) ) { 
      $this->logBorrowerWithExpiringCardMessage($borrower);
    }
    
    $this->removeBorrowerFromCardExpiryQueue($borrower);

    return $borrower;
  }

  protected function sendBorrowerCardExpirationMessage($borrower) {
    $response = new stdClass();

    if ( empty($borrower['email']) ) {
      $response->error = 'no email address';
    }

    if ( !stristr($borrower['email'], '@') ) {
      $response->error = 'invalid email address';
    }

    if ( isset($response->error) ) {
      return $response;
    }

    $hello = 'Hi '.ucwords(strtolower($borrower['name'])).':';
    
    $intro = 'This is a courtesy reminder that your library card has expired or is about to expire.

Your library card is set to expire on '.$borrower['expiry'].'.

You may renew your card online at www.spokanelibrary.org/renew/.

You may also renew your card over the phone by calling our circulation desk at 509-444-5333 or in person at any Spokane Public Library branch.
'.PHP_EOL;

    /*
    if ( $borrower['owed'] > 0 ) {
      $owed = 'Please note that any fines or fees owing must be paid in full before your library card can be renewed.

You currently owe $'.$borrower['owed'].'.

You can pay fines and fees at any Spokane Public Library branch or online at www.spokanelibrary.org/pay/.      
      '.PHP_EOL;
    }
    */

    $outro = 'This is a courtesy reminder for:';

    $expiry = 'Your library card expires on '.$borrower['expiry'].'.'.PHP_EOL.PHP_EOL;


    $text = null;
    $text .= $hello;
    $text .= PHP_EOL.PHP_EOL;
    $text .= $intro;
    $text .= '--------------------'.PHP_EOL.PHP_EOL;
    if ( !empty($owed) ) {
      $text .= $owed;
      $text .= '--------------------'.PHP_EOL.PHP_EOL;
    }
    $text .= $outro.PHP_EOL.PHP_EOL;
    $text .= $borrower['full_name'].PHP_EOL.PHP_EOL;
    $text .= $borrower['barcode'].PHP_EOL.PHP_EOL;
    $text .= $borrower['email'].PHP_EOL.PHP_EOL;
    //$text .= '--------------------'.PHP_EOL.PHP_EOL;
    $text .= $expiry;
    $text .= PHP_EOL.PHP_EOL;

    $intro = $this->getHyperlink($intro, 'www.spokanelibrary.org/renew/');
    $intro = $this->getHyperlink($intro, 'www.spokanelibrary.org/pay/');

    $html = null;
    $html .= '<div style="font-family:sans-serif;">';
    $html .= '<h3>'.$hello.'</h3>';
    $html .= nl2br($intro);
    $html .= '<hr>';
    if ( !empty($owed) ) {
      $html .= '<br>';
      $html .= nl2br($owed);
      $html .= '<hr>';
    }
    $html .= '<h4>'.$outro.'</h4>';
    $html .= '<p>'.$borrower['full_name'].'</p>';
    $html .= '<p>'.$borrower['barcode'].'</p>';
    $html .= '<p>'.$borrower['email'].'</p>';
    //$html .= '<hr>';
    $html .= '<h4>'.$expiry.'</h4>';
    $html .= '<br><br>';
    $html .= '</div>';

    //$html = null;

    if ( $this->devmode ) { 
      $address = $this->devaddr;
    } else {
      $address = $borrower['email'];
    }

    $subject = 'Your library card is about to expire';
    //$message = $this->getMaterialsDueMessage();
    $message = array('html'=>$html, 'text'=>$text);

    


    $response = $this->sendMailgunMessage($this->sender
                                    ,$address
                                    ,$subject
                                    ,$message['html']
                                    ,$message['text']
                                    ,'spl_card_expiry');
    
    return $response;
    //return array('html'=>$html, 'text'=>$text);
  }

  protected function logBorrowerWithExpiringCardMessage($borrower) {
        $params = array(':borrower'=>$borrower['borrower']
                  , ':barcode'=>$borrower['barcode']
                  , ':email'=>$borrower['email']
                  , ':expiration_date'=>$borrower['expiration_date']
                  , ':expiry'=>$borrower['expiry']
                  , ':owed'=>$borrower['owed']
                  , ':name'=>$borrower['name']
                  , ':full_name'=>$borrower['full_name']
                  , ':response'=>json_encode($borrower['response'])
                  );
    
    $sql = "INSERT INTO
            spl_connect_card_expiry_log
            (borrower, barcode, email, expiration_date, expiry, owed, name, full_name, response)
            VALUES
            (:borrower, :barcode, :email, :expiration_date, :expiry, :owed, :name, :full_name, :response)
          ";

    $result = $this->getQuery($sql, $params);
  }

  protected function removeBorrowerFromCardExpiryQueue($borrower) {
    $params = array(':borrower'=>$borrower['borrower']);
    $sql = "DELETE
            FROM spl_connect_card_expiry_queue
            WHERE borrower = :borrower
          ";

    $result = $this->getQuery($sql, $params);
  }

  /**
   *
   * Materials Due methods  
   *
   */

  protected function populateBorrowersWithMaterialsDue() {
    //exec spl_connect_populate_material_due_queue 3
    /*
    TRUNCATE TABLE spl_connect_material_due_queue

    INSERT INTO spl_connect_material_due_queue
    SELECT
    DISTINCT borrower#
    FROM item
    WHERE
    item.item_status = 'o'
    AND item.due_date = DATEDIFF( dd, '01/01/1970', GETDATE() ) + 4 --sg orig = 3 

    --SELECT * FROM spl_connect_material_due_queue
    */
  }

  protected function notifyBorrowersWithMaterialsDue() {
    $params = array(':offset'=>$this->offset);
    // paramaterized queries don't support TOP n :(
    $sql = "SELECT 
            TOP ".$this->batch." queue.borrower AS borrower
            ,bbarcode AS barcode
            ,addr.email_address AS email
            ,DATEADD(day, CONVERT(int, :offset), GETDATE()) AS due
            ,CASE WHEN addr.email_name IS NOT NULL
              THEN addr.email_name
              ELSE dbo.spl_get_first_from_fullname(borrower.name_reconstructed)
              END AS name
            ,borrower.name_reconstructed AS full_name 
            FROM spl_connect_material_due_queue AS queue
            JOIN borrower
              ON borrower.borrower# = queue.borrower
            JOIN borrower_barcode AS barcode
              ON barcode.borrower# = queue.borrower
            LEFT OUTER JOIN borrower_address AS addr
              ON addr.borrower# = queue.borrower
              AND addr.ord = 0
            ORDER BY queue.borrower
          ";
    $result = $this->getQuery($sql, $params);
    
    //return $result;
    $borrowers = array_map(array($this, 'notifyBorrowerWithMaterialsDue'), $result);
    return $borrowers;
  }

  protected function notifyBorrowerWithMaterialsDue($borrower) {
    
    $params = array(':borrower'=>$borrower['borrower'], ':offset'=>$this->offset);

    $sql = "SELECT 
            item.item# AS item
            ,item.ibarcode AS barcode
            ,CONVERT(DATE, dbo.spl_get_datetime_from_epoch(item.due_date)) AS due
            ,CASE WHEN bib.text IS NOT NULL
              THEN dbo.spl_api_extract_subfield_text('a', bib.text)
                  +' '+dbo.spl_api_extract_subfield_text('c', bib.text)
                  +' '+dbo.spl_api_extract_subfield_text('p', bib.text)
                  +' '+dbo.spl_api_extract_subfield_text('n', bib.text)
                  +' '+dbo.spl_api_extract_subfield_text('h', bib.text)
                  +' '+dbo.spl_api_extract_subfield_text('b', bib.text)
              ELSE item.processed
              END AS title
            FROM 
            item_with_title item
            LEFT OUTER JOIN bib
              ON item.bib# = bib.bib#
              AND bib.tag = '245'
            WHERE
            item.borrower# = :borrower
            AND item.item_status = 'o' 
            AND item.due_date = DATEDIFF( dd, '01/01/1970', GETDATE() ) + :offset --sg orig = 3
          ";


    $result = $this->getQuery($sql, $params);
    //$titles = $result;
    $titles = array_map(array($this, 'formatMaterialsDue'), $result);

    $due = new DateTime($borrower['due']);
    $borrower['due'] = $due->format('l F jS, Y');

    $borrower['titles'] = $titles;

    $borrower['response'] = $this->sendBorrowerMaterialsDueMessage($borrower);

    $borrower['method'] = $this->method;

    if ( isset($borrower['response']) && !isset($borrower['response']->error) ) { 
      $this->logBorrowerWithMaterialsDueMessage($borrower);
    }
    
    $this->removeBorrowerFromMaterialsDueQueue($borrower);

    return $borrower;
  }

  protected function formatMaterialsDue($title) {

    if ( is_array($title) ) { 
      $due = new DateTime($title['due']);
      $title['due'] = $due->format('l F jS, Y');
      
      // json_encode parser chokes on some unicode chars
      $title['title'] = utf8_encode($title['title']);
      // stip excess whitespace
      $title['title'] = preg_replace( '/\s+/', ' ', $title['title'] );
      // shorten title
      $title['title'] = substr($title['title'], 0, 100);
      // normalize
      $title['title'] = ucfirst($title['title']);
    }

    return $title;
  }
  
  protected function sendBorrowerMaterialsDueMessage($borrower) {

    $response = new stdClass();

    if ( empty($borrower['email']) ) {
      $response->error = 'no email address';
    }

    if ( !stristr($borrower['email'], '@') ) {
      $response->error = 'invalid email address';
    }

    if ( in_array($borrower['email']
          , array('outreach_notices@spokanelibrary.org'
                , 'illnotices@spokanelibrary.org'
            )
          ) 
        ) {
      $response->error = 'excluded email address';
    }
 
    if ( !is_array($borrower['titles']) || empty($borrower['titles']) ) {
      $response->error = 'no overdue titles';
    }

    if ( isset($response->error) ) {
      return $response;
    }

    $hello = 'Hi '.ucwords(strtolower($borrower['name'])).':';

    $intro = 'This is a courtesy reminder that you have library materials due soon. 

If you have already returned these items, please disregard this notice.

If you want to renew these items, you can do so online at our website www.spokanelibrary.org or phone our telecirc service at 444-5443.

Your library card number and PIN are required for both renewal methods.

Materials that have been requested by someone else or have already been renewed three times will not be eligible for renewal.
    '.PHP_EOL;

    $outro = 'This is a courtesy reminder for:';

    $duedate = 'Materials Due '.$borrower['due'].':'.PHP_EOL.PHP_EOL;

    $items = null;
    foreach ( $borrower['titles'] as $title ) {
      $items .= $title['barcode'].' - ';
      $items .= rtrim($title['title'], '/');
      $items .= PHP_EOL.PHP_EOL;
    }

    $text = null;
    $text .= $hello;
    $text .= PHP_EOL.PHP_EOL;
    $text .= $intro;
    $text .= '--------------------'.PHP_EOL.PHP_EOL;
    $text .= $duedate;
    $text .= $items;
    $text .= '--------------------'.PHP_EOL.PHP_EOL;
    $text .= $outro.PHP_EOL.PHP_EOL;
    $text .= $borrower['full_name'].PHP_EOL.PHP_EOL;
    $text .= $borrower['barcode'].PHP_EOL.PHP_EOL;
    $text .= $borrower['email'].PHP_EOL.PHP_EOL;
    $text .= PHP_EOL.PHP_EOL;

    //$intro = str_ireplace('www.spokanelibrary.org', '<a href="http://www.spokanelibrary.org">www.spokanelibrary.org</a>', $intro);
    $intro = $this->getHyperlink($intro, 'www.spokanelibrary.org');

    $html = null;
    $html .= '<div style="font-family:sans-serif;">';
    $html .= '<h3>'.$hello.'</h3>';
    $html .= nl2br($intro);
    $html .= '<hr>';
    $html .= '<h4>'.$duedate.'</h4>';
    $html .= nl2br($items);
    $html .= '<hr>';
    $html .= '<h4>'.$outro.'</h4>';
    $html .= '<p>'.$borrower['full_name'].'</p>';
    $html .= '<p>'.$borrower['barcode'].'</p>';
    $html .= '<p>'.$borrower['email'].'</p>';
    $html .= '<br><br>';
    $html .= '</div>';

    //$html = null;

    if ( $this->devmode ) { 
      $address = $this->devaddr;
    } else {
      $address = $borrower['email'];
    }

    $subject = 'Materials Due Soon';
    //$message = $this->getMaterialsDueMessage();
    $message = array('html'=>$html, 'text'=>$text);

    


    $response = $this->sendMailgunMessage($this->sender
                                    ,$address
                                    ,$subject
                                    ,$message['html']
                                    ,$message['text']
                                    ,'spl_material_due');

    return $response;
    //return array('html'=>$html, 'text'=>$text);
  }

  protected function logBorrowerWithMaterialsDueMessage($borrower) {
    $params = array(':borrower'=>$borrower['borrower']
                  , ':barcode'=>$borrower['barcode']
                  , ':email'=>$borrower['email']
                  , ':due'=>$borrower['due']
                  , ':name'=>$borrower['name']
                  , ':full_name'=>$borrower['full_name']
                  , ':titles'=>json_encode($borrower['titles'])
                  , ':response'=>json_encode($borrower['response'])
                  );
    
    $sql = "INSERT INTO
            spl_connect_material_due_log
            (borrower, barcode, email, due, name, full_name, titles, response)
            VALUES
            (:borrower, :barcode, :email, :due, :name, :full_name, :titles, :response)
          ";

    $result = $this->getQuery($sql, $params);
  }

  protected function removeBorrowerFromMaterialsDueQueue($borrower) {
    $params = array(':borrower'=>$borrower['borrower']);
    $sql = "DELETE
            FROM spl_connect_material_due_queue
            WHERE borrower = :borrower
          ";

    $result = $this->getQuery($sql, $params);
  }

  /**
   *
   * Shared methods are marked private 
   *
   */

  private function getHyperlink($str, $link) {
    if ( $str && $link ) {
      $str = str_ireplace($link, '<a href="'.$link.'">'.$link.'</a>', $str);
    }

    return $str;
  }

  private function sendMailgunMessage($from, $to, $subject, $html=null, $text=null, $campaign=null) {
    $api = $this->getMailgunApi().$this->getMailgunDomain().'/'.'messages';
    $auth = $this->getMailgunPrivateAuth();
    $params = array('from'=>$from
                  , 'to'=>$to
                  , 'subject'=>$subject
                  , 'o:tag'=>$subject
                  , 'o:tracking-clicks'=>'htmlonly'
                  , 'o:tracking-opens'=>'yes'
                  //, 'o:tracking' => false
                  //, 'v:my-data' => '{"my_message_id":123}'
                    );
    if ( !empty($html) ) {
      $params['html'] = $html;
    }
    if ( !empty($text) ) {
      $params['text'] = $text;
    }
    if ( !empty($campaign) ) {
      $params['o:campaign'] = $campaign;
    }
    
    return $this->curlJSON($api, $params, 'post', $auth);
  }

  private function getMailgunAddressValidation($address=null) {
    $api = $this->getMailgunApi().'address/validate';
    $auth = $this->getMailgunPublicAuth();
    $params = array('address'=>$address);

    return $this->curlJSON($api, $params, 'get', $auth);
  }

  private function getMailgunApi() {
    return $this->mg_api;
  } 

  private function getMailgunDomain() {
    return $this->domain;
  }

  private function getMailgunPublicAuth() {
    $auth = array('user'=>'api'
                , 'pass'=>$this->config['mailgun']['pubkey']
                );
    return $auth;
  }

  private function getMailgunPrivateAuth() {
    $auth = array('user'=>'api'
                , 'pass'=>$this->config['mailgun']['private']
                );
    return $auth;
  }

  /*
  private function sendTestMessage() {
    $address = 'sgirard@spokanelibrary.org';

    $subject = 'My Message';
    $message = $this->getTestMessage();

    return $this->sendMailgunMessage($this->sender
                                    ,$address
                                    ,$subject
                                    ,$message['html']
                                    ,$message['text']);
    //return $this->getMailgunAddressValidation($address);
  }

  private function getTestMessage() {
    $html = 'My message in html';

    $text = 'My message in text';

    return array('html'=>$html, 'text'=>$text);
  }
  */

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
