<?php


require_once('base/SPL_DB.php');

class SPL_API_RPA_Links extends SPL_DB {

  var $apikey;
  var $method;
  var $params;
  var $config;


  function __construct($config=null, $request=null, $apikey=null) {
    
    if ( is_array($config) ) {
      $this->config = $config;

      if ( isset($config['api']['connect']) 
        && isset($config['api']['web_user']) 
        && isset($config['api']['web_pass']) ) { 
        
        parent::__construct( $config['api']['connect']
                            ,$config['api']['web_user']
                            ,$config['api']['web_pass']
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


    $this->setRegistry();


  }

  public function getApiRequest() {
    //return $this->params['url'];
    //return $this->registry;
    //return $this->registry['bsc'];

    $request = null;
    if ( $this->params['url'] && $this->registry[$this->params['url']] ) {
      //return $this->registry;
      $request = $this->registry[$this->params['url']];
    }

    // post-processing for variable URLs
    if ( 'gvrl' == $this->params['url'] && !empty($this->params['isbn']) ) {
      $request['url'] = 'http://go.galegroup.com/ps/eToc.do?userGroupName=splbt_main&prodId=GVRL&inPS=true&action=DO_BROWSE_ETOC&searchType=BasicSearchForm&docId=GALE%7C'.$this->params['isbn'].'&contentSegment='.$this->params['isbn'];
    }

    if ( !empty($request) ) {
      return $request;
    }
  }

  protected function setRegistry() {
    
    // pull local config paths
    $registry = parse_ini_file('/var/web/---/app/api-v3/rpa.ini', true);


    $this->registry = $registry;
  }
  

} // CLASS

?>
