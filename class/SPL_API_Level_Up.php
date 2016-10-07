<?php


require_once('base/SPL_DB.php');

class SPL_API_Level_UP extends SPL_DB {

  var $apikey;
  var $method;
  var $params;
  var $config;

  //var $mdb;
  var $name;
  //var $cache = false;
  //var $sqlite = 'sqlite:/var/web/---/cache/biofiles.sqlite';

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

  }

  public function getApiRequest() {
    //return $this->params;
    return $this->getPassphrase();
  }

  protected function getPassphrase() {
    
    $sql = "SELECT 
            passphrase 
            FROM 
            spl_connect_level_up_login
      ";  
      $result = $this->getQuery($sql, $params);

      return $result[0];
  }

} // CLASS

?>
