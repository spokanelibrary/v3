<?php
//error_reporting(E_ALL);
ini_set("memory_limit","512M");

require_once('base/SPL_DB.php');


class SPL_API_SD_Web_Services extends SPL_DB {

  var $apikey;
  var $method;
  var $params;
  var $config;

  var $endpoint = 'http://hzportal.spokanelibrary.org/hzws/rest/standard/';

  var $cid;
  var $crt;

  var $token;


  function __construct($config=null, $request=null, $apikey=null) {
    
    if ( is_array($config) ) {
      $this->config = $config;

      $this->cid = $config['api']['hzws']['cid'];
      $this->crt = $config['api']['hzws']['crt'];

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

    //return $this->method;
    //return $this->params;

    if ( $this->method[1] ) {
      // This is a stateless api
      if ( $this->params['barcode'] && $this->params['pin'] ) {
        $login = $this->loginUser($this->params['barcode'], $this->params['pin']);
        $this->token = $login->response->sessionToken;
      }
      unset($this->params['barcode']);
      unset($this->params['pin']);


      switch ( $this->method[1] ) {
        case 'renew':
            //$params = array('itemID'=>'37413315426371');
            //$params = array('itemID'=>'37413315035355');
            //$params = array('itemID'=>'37413309357715');
            //$params = array('itemID'=>'37413312068085');


            //$hzws = $this->params;

            $hzws = $this->renewMyCheckouts($this->params);
          break;

        case 'account-lookup':
          $hzws = $this->lookupMyAccountInfo();
          break;

        case 'sms-add':
          $hzws = $this->createMySMSEntry();
          break;

        default: 
          $hzws = array('error' => 'Method not defined.');
          break;
      }
      
      $logout = $this->logoutUser();

    } else {
      $hzws = array('error' => 'No method specififed.');
    } 

    return $hzws;
  }

  protected function lookupMyAccountInfo() {
    return $this->getHZWS('lookupMyAccountInfo');
  }

  protected function createMySMSEntry() {
    //return $this->getHZWS('lookupCountryCodes');
    $params = array('includeAddressInfo'=>'true');
    //return $this->getHZWS('lookupMyAccountInfo', $params);
    //return $this->getHZWS('emailMyPin', array('login'=>'27413204161149', 'profile'=>'dt'));
    //return $this->getHZWS('isSMSEnabled');
    //return $this->token;
    $params = array('SMSPhoneNumber'=>array(
      'userID'=>''
      ,'ord'=>'3'
      ,'phoneNumber'=>''
      ,'type'=>'zsms'
      ,'countryCode'=>'US'
      ,'smsPreOverdue'=>'true'
      ,'smsOverdue'=>'true'
      ,'smsHolds'=>'true'
      ,'smsGeneral'=>'true'
    ));
    return $this->getHZWS('createMySMSEntry', $params);
  }

  protected function renewMyCheckouts($params) {
    if ( is_array($params['itemID']) ) {
      $renew = array();
      foreach ( $params['itemID'] as $item ) {
        $id = array('itemID'=>$item);
        $result = new stdClass();
        $result->ibarcode = $item;
        $result->renewal = $this->renewMyCheckout($id);
        $renew[] = $result;
      }
    } elseif ( isset($params['itemID']) ) {
      $renew = $this->renewMyCheckout($params);
    }

    return $renew;
  }

  protected function renewMyCheckout($params) {
    return $this->getHZWS('renewMyCheckout', $params);
  }

  protected function loginUser($login, $password, $profile=null) {
    $params = array('login'=>$login, 'password'=>$password);
    if ( $profile ) {
      $params['profile'] = $profile;
    }

    return $this->getHZWS('loginUser', $params);
  }

  protected function logoutUser($token=null) {
    if ( $token || $this->token ) { 
      return $this->getHZWS('logoutUser');
    }
  } 


  private function getHZWS($method, $params=null, $session=null) {
    if ( $method ) { 
      // hzws returns invalid json
      //$params['json'] = 'true';
      
      $headers = array('x-sirs-clientID: ' . $this->cid
                      ,'x-sirs-secret: ' . $this->crt
                      //,'Content-Type: application/xml'
                      //,'Accept: application/xml'
                      //,'Content-Type: application/json'
                      //,'Accept: application/json'
                      );

      if ( !isset($session) ) {
        if ( isset($this->token) ) {
          $session = $this->token;
        }
      }
      if ( $session ) {
        $headers[] = 'x-sirs-sessionToken: '. $session;
      }

      $apicall = $this->curlProxyHeaders($this->endpoint.$method, $params, 'post', null, $headers);
      
      libxml_use_internal_errors(true);
      $response = simplexml_load_string($apicall->response);
      if ( $response ) {
        $apicall->response = $response; 
      }

      return $apicall;
    }
  }



  ##################################


  /**
   *
   * Curl Proxy w/ custom headers  
   *
   */


  function curlProxyHeaders($url, $params=null, $method='post', $auth=null, $headers=null) {
    $result = new stdClass();
    $result->response = false;

    if ( empty($params) ) {
      $params = array();
    }

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

    if ( $headers ) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
