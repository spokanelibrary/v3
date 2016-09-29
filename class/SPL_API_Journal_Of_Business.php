<?php


require_once('base/SPL_DB.php');

class SPL_API_Journal_Of_Business extends SPL_DB {

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
    return $this->searchJOB();
  }

  protected function searchJOB() {
    $job = null;
    
    if ( !empty($this->params['term']) ) {  
      $term = trim($this->params['term']);
      
      $params = array(':term'=>'% '.$term.' %');
      $sql = "SELECT *
              FROM spl_api_journal_of_business 
              WHERE COMPANY LIKE :term
              OR HEADLINE LIKE :term
              ORDER BY ISSUE ASC
      ";  
      $exact = $this->getQuery($sql, $params);
      
      //if ( empty($exact) ) {
      
        $params = array(':term'=>'"'.$term.'"');
        $sql = "SELECT *
                FROM spl_api_journal_of_business 
                WHERE FREETEXT(COMPANY, :term)
                OR FREETEXT(HEADLINE, :term)
                ORDER BY ISSUE ASC
        ";
        
      //}
      $freetext =  $this->getQuery($sql, $params);

      $result = $exact + $freetext;
      //$result = $freetext;

      $job['count'] = count($result);
      $job['search'] = $result;
    }

    return $job;
  }

} // CLASS

?>
