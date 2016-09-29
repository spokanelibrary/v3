<?php

//ini_set("memory_limit","512M");

require_once('base/SPL_DB.php');

class SPL_API_CHOF_Nominate extends SPL_DB {

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

  }

  public function getApiRequest() {

    if ( !empty($this->params) ) {
      return $this->logChofNomination();
    }

  }

  protected function logChofNomination() {

    $category = '';
    if ( is_array($this->params['category']) ) {
      foreach ( $this->params['category'] as $c => $cat ) {
        $category .= $cat . ' | ';
      }
    }
    $category = rtrim(trim($category),' | ');

    $epoch = 'Not specified';
    if ( !empty($this->params['epoch']) ) {
      $epoch = $this->params['epoch'];
    }

    $params = array(':ip_address' => $this->params['ip']
                  , ':nominee_name' => $this->params['nominee']
                  , ':nominee_email' => $this->params['nominee-email']
                  , ':nominee_phone' => $this->params['nominee-phone']
                  , ':nominee_epoch' => $epoch
                  , ':nominee_category' => $category
                  , ':submitter_name' => $this->params['name']
                  , ':submitter_email' => $this->params['email']
                  , ':submitter_phone' => $this->params['phone']
                  , ':submitter_essay' => $this->params['essay']

              );

    $sql = "INSERT INTO
            spl_foundation_chof_nominate
            (ip_address
            ,nominee_name
            ,nominee_email
            ,nominee_phone
            ,nominee_epoch
            ,nominee_category
            ,submitter_name
            ,submitter_email
            ,submitter_phone
            ,submitter_essay)
            VALUES
            (:ip_address
            ,:nominee_name
            ,:nominee_email
            ,:nominee_phone
            ,:nominee_epoch
            ,:nominee_category
            ,:submitter_name
            ,:submitter_email
            ,:submitter_phone
            ,:submitter_essay)
          ";

    $result = $this->getQuery($sql, $params);

    return $result;
  }
  

} // CLASS

?>
