<?php


require_once('base/SPL_DB.php');

class SPL_API_PC_Res_Monitor extends SPL_DB {

  var $apikey;
  var $method;
  var $params;
  var $config;

  function __construct($config=null, $request=null, $apikey=null) {
    
    if ( is_array($config) ) {
      $this->config = $config;

      if ( isset($config['api']['pcres']) 
        && isset($config['api']['pc_user']) 
        && isset($config['api']['pc_user']) ) { 
        
        parent::__construct( $config['api']['pcres']
                            ,$config['api']['pc_user']
                            ,$config['api']['pc_pass']
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

    switch (strtolower($this->params['branch'])) {
      case 'dt':
        $branch = 'Downtown';
        break;
      case 'es':
        $branch = 'East Side';
        break;
      case 'it':
        $branch = 'Indian Trail';
        break;
      case 'hy':
        $branch = 'Hillyard';
        break;
      case 'sh':
        $branch = 'Shadle';
        break;
      case 'so':
        $branch = 'South Hill';
        break;
      default:
        $branch = null;
        break;
       
    }

    if ( is_null($branch) ) {
      return array('error'=>'Branch not specified.');
    }

    $dt = new DateTime();
    $begin = $dt->format('Y-m-d');
    $dt->add(new DateInterval('P1D'));
    $finish = $dt->format('Y-m-d');

    $params = array(':dt_begin'=>$begin, ':dt_finish'=>$finish, ':branch'=>$branch);
    //return $params;
    $sql = "SELECT 
            * 
            FROM 
            tbluserpcrdetail 
            WHERE 
            pcrStopTime IS NUll
            AND pcrBranch = :branch
            AND pcrDateTime > :dt_begin
            AND pcrDateTime < :dt_finish
            ORDER BY pcrDateTime ASC
    ";  
    
    return $this->getQuery($sql, $params);
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
