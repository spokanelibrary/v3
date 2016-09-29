<?php

//ini_set("memory_limit","16M");

require_once('config.php');

/*

 *
 *  SPL API
 *  
 
 */

// to invoke
// curl http://app.spokanelibrary.org/v3/ent-carousel

$restful = explode('/', $_REQUEST['endpoint']);

$request = array(
                //'method'=>$restful[0],
                'method'=>$restful,
                'params'=>$_REQUEST['params'],
                'apikey'=>$_REQUEST['apikey']
                );

$api = new SPL_API($config, $request);

class SPL_API {

  var $apikey = false;
  var $config;
  var $request;

  function __construct($config, $request) {

    if ( $request['apikey'] ==  $config['api']['key'] ) {
      $this->apikey = true;
    }
    
    $this->config = $config;
    $this->request = $request;

    $this->init();
  } 


  protected function init() {
    switch ($this->request['method'][0]) {
    	case 'version':
        $call = 'SPL API v3';
        break;

      case 'crass':
        $call = 'crass';
        break;

      case 'server':
        $call = $_SERVER["HTTP_REFERER"];
        break;

      case 'rpa':
        if ( $this->apikey ) {
          require 'class/SPL_API_RPA_Links.php';
          $api = new SPL_API_RPA_Links($this->config, $this->request, $this->apikey);
          $call = $api->getApiRequest();
        } else {
          $call = $this->raiseError('not authorized');
        }
        break;

    	case 'x1-logs':
        require 'class/SPL_API_X1_Logs.php';
        $api = new SPL_API_X1_Logs($this->config, $this->request, $this->apikey);
        if ( $this->apikey ) {
          $call = $api->getApiRequest();
        } else {
          $call = $this->raiseError('not authorized');
        }
        break;

      case 'ent-carousel':
        require 'class/SPL_API_Enterprise_Carousel.php';
        $api = new SPL_API_Enterprise_Carousel($this->config, $this->request, $this->apikey);
        //if ( $this->apikey ) {
          $call = $api->getApiRequest();
        //} else {
          //$call = $this->raiseError('not authorized');
        //}
        break;

      case 'chof-nominate':
        require 'class/SPL_API_CHOF_Nominate.php';
        $api = new SPL_API_CHOF_Nominate($this->config, $this->request, $this->apikey);
        if ( $this->apikey ) {
          $call = $api->getApiRequest();
        } else {
          $call = $this->raiseError('not authorized');
        }
        break;

      case 'spl-reports':
        require 'class/SPL_API_Reports.php';
        $api = new SPL_API_Reports($this->config, $this->request, $this->apikey);
        if ( $this->apikey ) {
          $call = $api->getApiRequest();
        } else {
          $call = $this->raiseError('not authorized');
        }
        break;

      case 'nw-biofiles':
        require 'class/SPL_API_NW_BioFiles.php';
        $api = new SPL_API_NW_BioFiles($this->config, $this->request, $this->apikey);
        //if ( $this->apikey ) {
          $call = $api->getApiRequest();
        //} else {
          //$call = $this->raiseError('not authorized');
        //}
        break;

      case 'journal-business':
        require 'class/SPL_API_Journal_Of_Business.php';
        $api = new SPL_API_Journal_Of_Business($this->config, $this->request, $this->apikey);
        //if ( $this->apikey ) {
          $call = $api->getApiRequest();
        //} else {
          //$call = $this->raiseError('not authorized');
        //}
        break;

      case 'xls-report':
        require 'class/SPL_API_XLS_Report.php';
        $api = new SPL_API_XLS_Report($this->config, $this->request, $this->apikey);
        if ( $this->apikey ) {
          $call = $api->getApiRequest();
        } else {
          $call = $this->raiseError('not authorized');
        }
        break;

      case 'send-notices':
        require 'class/SPL_API_Notices.php';
        $api = new SPL_API_Notices($this->config, $this->request, $this->apikey);
        if ( $this->apikey ) {
          $call = $api->getApiRequest();
        } else {
          $call = $this->raiseError('not authorized');
        }
        break;

      case 'twilio':
        require 'class/SPL_API_Notices_SMS.php';
        $api = new SPL_API_Notices_SMS($this->config, $this->request, $this->apikey);
        //if ( $this->apikey ) {
          $call = $api->getTwilioReply();
        //} else {
          //$call = $this->raiseError('not authorized');
        //}
        break;

      case 'send-pin':
        require 'class/SPL_API_Forgot_Pin.php';
        $api = new SPL_API_Forgot_Pin($this->config, $this->request, $this->apikey);
        //if ( $this->apikey ) {
          $call = $api->getApiRequest();
        //} else {
          //$call = $this->raiseError('not authorized');
        //}
        break;

      case 'pc-res':
        require 'class/SPL_API_PC_Res_Monitor.php';
        $api = new SPL_API_PC_Res_Monitor($this->config, $this->request, $this->apikey);
        if ( $this->apikey ) {
          $call = $api->getApiRequest();
        } else {
          $call = $this->raiseError('not authorized');
        }
        break;

      case 'calendar-feed':
        require 'class/SPL_API_Calendar_Feed.php';
        $api = new SPL_API_Calendar_Feed($this->config, $this->request, $this->apikey);
        //if ( $this->apikey ) {
          $call = $api->getApiRequest();
        //} else {
          //$call = $this->raiseError('not authorized');
        //}
        break;



      case 'sms-notice':
        require 'class/SPL_API_Notices_SMS.php';
        $api = new SPL_API_Notices_SMS($this->config, $this->request, $this->apikey);
        if ( $this->apikey ) {
          $call = $api->getApiRequest();
        } else {
          $call = $this->raiseError('not authorized');
        }
        break;

      case 'hzws':
        require 'class/SPL_API_SD_Web_Services.php';
        $api = new SPL_API_SD_Web_Services($this->config, $this->request, $this->apikey);
        if ( $this->apikey ) {
          $call = $api->getApiRequest();
        } else {
          $call = $this->raiseError('not authorized');
        }
        break;

      case 'hzws-v2':
        require 'class/SPL_API_SD_HZWS.php';
        $api = new SPL_API_SD_HZWS($this->config, $this->request, $this->apikey);
        if ( $this->apikey ) {
          $call = $api->getApiRequest();
        } else {
          $call = $this->raiseError('not authorized');
        }
        break;

      case 'civic-tech':
        require 'class/SPL_API_Civic_Tech.php';
        $api = new SPL_API_Civic_Tech($this->config, $this->request, $this->apikey);
        if ( $this->apikey ) {
          $call = $api->getApiRequest();
        } else {
          $call = $this->raiseError('not authorized');
        }
        break;

      default:
        $call = $this->raiseError('invalid endpoint');
        break;

    }

    //trace($call);
    $this->output($call);
  }

  private function output($obj) {
    //header('Content-Type: text/javascript; charset=utf-8'); 
    if ( $obj ) {
      json_echo($obj);
    } else {
      json_echo($this->raiseError('empty'));
    }
  }
    
  private function raiseError($msg=null) {
    return array('error' => $msg);
  }

  /**
   *
   * Super Simple Curl Proxy  
   *
   */
  public static function curlPostProxy($url, $post=null) {
      // create a new cURL resource
      $ch = curl_init();
   
      // set URL and other appropriate options
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HEADER, false);
      
      // set returntransfer to true to prevent browser echo
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   
      $ua = $_SERVER['HTTP_USER_AGENT']; // optional
      if (isset($ua)) {
          curl_setopt($ch, CURLOPT_USERAGENT, $ua);
      }
   
      // setup for an http post
      curl_setopt($ch, CURLOPT_POST, 1);
      if ( !is_null($post) ) {
        $post = http_build_query($post);
      }
      // 'cause cURL doesn't like multi-dimensional arrays
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    
      // grab URL
      $result = curl_exec($ch);
   
      // close cURL resource, and free up system resources
      curl_close($ch);
      
      return $result;
  }


}

?>