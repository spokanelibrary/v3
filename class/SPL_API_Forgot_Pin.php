<?php


require_once('base/SPL_DB.php');

class SPL_API_Forgot_Pin extends SPL_DB {

  var $apikey;
  var $method;
  var $params;
  var $config;

  var $sender = 'Spokane Public Library <mail@notice.spokanelibrary.org>';
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
    //phpinfo();

  }

  public function getApiRequest() {
    //return $this->params;
    
    return $this->sendPIN();
  }

  protected function sendPIN() {
    
    if ( $this->params && $this->params['barcode'] ) {
      $borrower = $this->getPIN($this->params['barcode']);
      if ( $borrower && $borrower['pin'] ) {
        $email = $this->getEmail($borrower['borrower']);
        if ( !empty($email) ) {
          $this->sendEmail($borrower['pin'], $email);
          if ( 1 < count($email) ) {
            $msg = 'We sent your PIN to the email address we have on file.';
          } else {
            $msg = 'We sent your PIN to the email addresses we have on file.';
          }
        } else {
          $err = 'We cannot find an email address for that barcode';
        }
      } else {
        $err = 'We cannot find a pin for that barcode.';
      }
    } else {
      $err = 'No barcode specified.';
    }

    if ( $err ) {
      return array('error' => $err);
    }

    return array('message' => $msg);
  }

  protected function sendEmail($pin, $email) {
    //return array($pin, $email);

    $subject = 'Your PIN request';
    $message['html'] = 'Here is your pin: '.$pin.PHP_EOL;
    $message['text'] = 'Here is your pin'.$pin.PHP_EOL;
    
    if ( is_array($email) ) {
      foreach ( $email as $address ) {
        $response[] = $this->sendMailgunMessage($this->sender
                                    ,$address['email']
                                    ,$subject
                                    ,$message['html']
                                    ,$message['text']
                                    ,'spl_forgot_pin');
      } 
    } 

    

    return $response;
  }

  protected function getEmail($borrower) {
    //return $borrower;
    $params = array(':borrower' => $borrower);
    $sql = "SELECT
            email_address AS email
            FROM borrower_address
            WHERE borrower# = :borrower
            AND email_address IS NOT NULL
    ";

    $result = $this->getQuery($sql, $params);

    return $result;
  }

  protected function getPIN($barcode) {
    $params = array(':barcode'=>$barcode);
    $sql = "SELECT
            --*
            borrower.borrower# AS borrower
            ,borrower.pin# AS pin
            FROM borrower_barcode
            JOIN borrower
              ON borrower_barcode.borrower# = borrower.borrower# 
            WHERE bbarcode = :barcode
    ";

    $result = $this->getQuery($sql, $params);

    if ( $result[0]['pin'] ) {
      return $result[0];
    } else {
      return false;
    }
  }

  /* Mailgun method */

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
