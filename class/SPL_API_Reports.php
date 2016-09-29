<?php

ini_set("memory_limit","512M");

require_once('base/SPL_DB.php');

class SPL_API_Reports extends SPL_DB {

  var $apikey;
  var $method;
  var $params;
  var $config;

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
    //return $this->params;
    $class = $this->getReportClass();
    if ( is_object($class) ) {
      include 'reports/'.$class->path;
      $report = new $class->name($this->config
                                ,array('params'=>$this->params) 
                                );    
      return $report->getReportData();
    }
  }


  protected function getReportClass() {
    $class = new stdClass();
    
    $files = scandir( __DIR__.'/reports/' );
    foreach ($files as $file) {
      // ignore directories and hidden files
      if(0 !== stripos($file, '.')) {
        //$class->scan = $this->params['id'];
        if ( substr_count(strtolower(str_ireplace('_', '-', $file)), strtolower($this->params['id'])) ) {
          $class->path = $file;
          // trim off file extension
          $class->name = stristr($file, '.', true);
        }
      }
    }
    

    return $class;
  }

  protected function getReportLocations($ou=true) {
    $locations[] = array('code'=>'dt', 'label'=>'Downtown');
    $locations[] = array('code'=>'es', 'label'=>'East Side');
    $locations[] = array('code'=>'it', 'label'=>'Indian Trail');
    $locations[] = array('code'=>'hy', 'label'=>'Hillyard');
    $locations[] = array('code'=>'sh', 'label'=>'Shadle');
    $locations[] = array('code'=>'so', 'label'=>'South Hill');
    if ( $ou ) {
      $locations[] = array('code'=>'ou', 'label'=>'Outreach');
    }

    return $locations;
  }

  // Convert days since epoch to date string
  protected function getDateFromEpoch($days=0, $format='m-d-Y') {
      return date($format, strtotime("+$days days", 0));
  }
  

} // CLASS

?>
