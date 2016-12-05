<?php

ini_set("memory_limit","1G");
ini_set('max_execution_time', 240);

require_once('base/SPL_DB.php');

class SPL_API_X1_Logs extends SPL_DB {

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

    DEFINE('CSV', 'circHistory.csv');
    DEFINE('MASK', 0775);
    DEFINE('DATA', '/data');
    DEFINE('MOUNT', '/mnt');
    DEFINE('PARSED', '/mnt/horizon/onestop/parsed');
    DEFINE('CURRENT', '/mnt/horizon/onestop/current');
    DEFINE('BACKUPS', '/mnt/horizon/onestop/backups');

    $this->setRegistry();

    $this->log = new stdClass();

  }

  public function getApiRequest() {
    if ( !is_array($this->registry) ) {
      return $this->raiseError('registry not defined');
    }

    // TODO: log elapsed time
    // TODO: truncate loggin table at init
    //        - bubble issues up to reports

    $now = new DateTime();

    if ( !is_dir(PARSED) ) {
      mkdir(PARSED, MASK);
    } 
    if ( !is_dir(CURRENT) ) {
      mkdir(CURRENT, MASK);
    } 
    if ( !is_dir(BACKUPS) ) {
      mkdir(BACKUPS, MASK);
    }
    
    
    // Copy data from x1 systems
    // TODO: need a check that we have fresh data
    //rename(CURRENT, BACKUPS.'/'.'MY-BACKUP');
    //echo 'copying data';
    foreach ( $this->registry as $x1 ) {
      if ( file_exists(MOUNT.'/'.$x1->host.DATA.'/'.CSV) ) {
        if ( !is_dir(CURRENT.'/'.$x1->host) ) {
          mkdir(CURRENT.'/'.$x1->host);
        }
        copy(MOUNT.'/'.$x1->host.DATA.'/'.CSV, CURRENT.'/'.$x1->host.'/'.CSV);
      }      
    }
    // DO NOT TOUCH X1 SYSTEMS ANYMORE (!MOUNT)
     
    
    // Backup the raw data
    //echo 'archiving data';
    $zip = new ZipArchive();
    if ($zip->open(BACKUPS.'/x1-circ-'.$now->format('Y-m-d').'-ts-'.$now->format('H-i-s').'.zip', ZipArchive::CREATE)!==TRUE) {
      return $this->raiseError('unable to create backup');
    }
    foreach ( $this->registry as $x1 ) {   
      if ( file_exists(CURRENT.'/'.$x1->host.'/'.CSV) ) {
        $zip->addFile(CURRENT.'/'.$x1->host.'/'.CSV, $x1->host.'-'.$now->format('Y-m-d').'.csv');
      }
    }
    $zip->close();
    
  
    
    // Parse the raw data
    //echo 'parsing data';
    foreach ( $this->registry as $x1 ) {   
      if ( file_exists(CURRENT.'/'.$x1->host.'/'.CSV) ) {
        if ( ($handle = fopen(CURRENT.'/'.$x1->host.'/'.CSV, "r")) !== FALSE ) {
          $row = 0;
          $parsed = array();
          while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
            // override selfcheck location
            if ( 0 != $row ) {
              $data[7] = $x1->host;
              $data[4] = trim(substr($data[4], 0,20)); // trim barcode
            }
            // add a placeholder for identity column
            array_unshift($data, '');
            $parsed[$row] = $data;
            $row++;
          }
          fclose($handle);
          
          // write parsed file
          unset($parsed[0]);
          if ( !is_dir(PARSED.'/'.$x1->host) ) {
            mkdir(PARSED.'/'.$x1->host);
          }
          if ( ($handle = fopen(PARSED.'/'.$x1->host.'/'.CSV, "w")) !== FALSE ) {
            foreach ($parsed as $fields) {
              fputcsv($handle, $fields);
            }
            fclose($handle);
          }
        }
      }
    }
    
    // Load data in DB
    //echo 'loading data';
    //--GRANT ADMINISTER BULK OPERATIONS TO [web]
    $sql = "TRUNCATE TABLE spl_stats_x1_history";
    $params = array();
    $result = $this->getQuery($sql, $params);
    foreach ( $this->registry as $x1 ) { 
      if ( file_exists(PARSED.'/'.$x1->host.'/'.CSV) ) {
        $sql = "EXEC spl_stats_get_x1_history :hostname";
        $params = array(':hostname'=>$x1->host);
        $result = $this->getQuery($sql, $params);
        //print_r($result);
      }
    }


    return $this->registry;
  }

  protected function setRegistry() {
    $this->registry = array();

    $x1 = new stdClass();

    $x1->location = 'dt';
    
    $x1->host = 'dt-x1ct-01';
    $this->registry[] = clone $x1;
    
    $x1->host = 'dt-x1ct-02';
    $this->registry[] = clone $x1;

    $x1->host = 'dt-x1ct-03';
    $this->registry[] = clone $x1;
    
    $x1->location = 'es';

    $x1->host = 'es-x1ct-01';
    $this->registry[] = clone $x1;

    $x1->host = 'es-x1ct-02';
    $this->registry[] = clone $x1;

    $x1->location = 'hy';

    $x1->host = 'hy-x1ct-01';
    $this->registry[] = clone $x1;

    $x1->host = 'hy-x1ct-02';
    $this->registry[] = clone $x1;

    $x1->location = 'it';

    $x1->host = 'it-x1ct-01';
    $this->registry[] = clone $x1;

    $x1->host = 'it-x1ct-02';
    $this->registry[] = clone $x1;

    $x1->location = 'sh';

    $x1->host = 'sh-x1ct-01';
    $this->registry[] = clone $x1;

    $x1->host = 'sh-x1ct-02';
    $this->registry[] = clone $x1;

    $x1->host = 'sh-x1ct-03';
    $this->registry[] = clone $x1;

    $x1->location = 'so';

    $x1->host = 'so-x1ct-01';
    $this->registry[] = clone $x1;

    $x1->host = 'so-x1ct-02';
    $this->registry[] = clone $x1;

    $x1->host = 'so-x1ct-03';
    $this->registry[] = clone $x1;

    




  }
  

} // CLASS

?>
