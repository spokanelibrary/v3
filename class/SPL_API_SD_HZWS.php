<?php
//error_reporting(E_ALL);
ini_set("memory_limit","512M");

require_once('base/SPL.php');


class SPL_API_SD_HZWS extends SPL {

  var $apikey;
  var $method;
  var $params;
  var $config;

  var $endpoint = 'http://hzportal.spokanelibrary.org:8080/hzws/v1/';

  var $cid;
  //var $crt;

  var $token;


  function __construct($config=null, $request=null, $apikey=null) {
    
    if ( is_array($config) ) {
      $this->config = $config;

      $this->cid = $config['api']['hzwsv1']['cid'];
      
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

    //return $this->getTest();

    if ( $this->method[1] ) {
      // This is a stateless api
      if ( $this->params['barcode'] && $this->params['pin'] ) {
        $login = $this->loginUser($this->params['barcode'], $this->params['pin']);
        $this->token = $login->response->sessionToken;
        $this->session = $login->response;
        unset($this->params['barcode']);
        unset($this->params['pin']);
      } elseif ( $this->params['token'] ) {
        $this->token = $this->params['token'];
      }
      
      switch ( $this->method[1] ) {
        case 'test':
          return $this->getTest();
          break;
        default: 
          $hzws = array('error' => 'Method not defined.');
          break;
      }
      // cleanup session
      if ( $this->params['logout'] ) {
        $logout = $this->logoutUser();
      }
    } else {
      $hzws = array('error' => 'No method specififed.');
    } 

    return $hzws;
  }


  protected function getTest() {
    return $this->session;

    return $this->getHZWS('user/patron/address/describe');

    return $this->getHZWS('user/patron/key/'.$this->session->patronKey);
    
    return $this->getHZWS('user/patron/barcode/27413204161149');
    return $this->getHZWS('user/patron/describe');
    return $this->session;
    
    $login = $this->loginUser($this->params['barcode'], $this->params['pin']);
    $logout = $this->logoutUser($login->response->sessionToken);
    return array($login, $logout);

    
    return $this->getHZWS('user/patron/describe');
    return $this->getHZWS('aboutIlsWs');
  }

  protected function loginUser($barcode, $pin) {
    return $this->getHZWS('user/patron/login', array('login'=>$barcode, 'password'=>$pin), null, 'post');
  }

  protected function logoutUser($token=null) {
    $token = isset($token) ? $token : $this->token;
    if ( $token ) {
      return $this->getHZWS('user/patron/logout', null, $token, 'post');
    }
  } 


  private function getHZWS($method, $params=null, $session=null, $http='get') {
    if ( $method ) { 
      // hzws returns invalid json
      //$params['json'] = 'true';

      if ( $params ) {
        $params = json_encode($params);
      }
      
      $headers = array('x-sirs-clientID: ' . $this->cid
                      ,'sd-originating-app-id: spl-webapp'
                      ,'Content-Type: application/json'
                      ,'Accept: application/json'
                      ,'Access-Control-Allow-Origin: *'
                      );

      if ( !isset($session) ) {
        if ( isset($this->token) ) {
          $session = $this->token;
        }
      }
      if ( $session ) {
        $headers[] = 'x-sirs-sessionToken: '. $session;
      }

      $apicall = $this->curlProxyHeaders($this->endpoint.$method, $params, $http, null, $headers);
      
      $response = json_decode($apicall->response);
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
      if ( $params ) {
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
      } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
      }
    } elseif ( 'get' == $method ) {
      if ( is_array($params) ) {
      $url .= '?' . http_build_query($params);
      }
    } elseif ( 'delete' == $method ) {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
      if ( $params ) {
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
      } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
      }
    } elseif ( 'put' == $method ) {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
      if ( $params ) {
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
      } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
      }
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
