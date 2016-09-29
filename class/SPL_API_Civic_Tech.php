<?php

ini_set("memory_limit","2048M");

require_once('base/SPL_DB.php');

class SPL_API_Civic_Tech extends SPL_DB {

  var $apikey;
  var $method;
  var $params;
  var $config;

  var $stored = '/var/web/---/civic-tech';

  function __construct($config=null, $request=null, $apikey=null) {
    
    if ( is_array($config) ) {
      $this->config = $config;

      if ( isset($config['api']['horizon']) 
        && isset($config['api']['web_user']) 
        && isset($config['api']['web_pass']) ) { 
        
        parent::__construct( $config['api']['horizon']
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
    // setup for PHPExcel library
    //$path = '/var/web/---/php/PHPExcel-1.8/Classes';
    //set_include_path(get_include_path() . PATH_SEPARATOR . $path);
    //include 'PHPExcel.php';
  }

  public function getApiRequest() {
    
    //return $this->params;

    /* Config */

    $ct['user'] = 'spokanepub';

    $tmp = $this->stored;
    $cwd = getcwd();

    /*
    $dt = new DateTime();
    $ym = $dt->format('Ym');
    $ymd = $dt->format('Ymd');
    */
    //$begin = new DateTime('2012-01-01');
    //$finish = new DateTime('2012-02-01');

    $begin = new DateTime('first day of last month');
    $finish = new DateTime('first day of this month');

    //$begin = new DateTime('2014-04-01');
    //$finish = new DateTime('2014-05-01');

    $ym = $begin->format('Ym');
    $ymd = $finish->format('Ymd');


    $zip = new ZipArchive();
    $archive = $ct['user'] . '_ahcmlp_' . $ym . '.zip'; 



    $checkouts = $this->getCheckouts($begin->format('Y-m-d'), $finish->format('Y-m-d'));
    //$filename = $tmp . '/' . $ym . '/' . $ct['user'] . '_firstcheckoutdatafile_' . $ym .'.csv';
    $filename = $tmp . '/' . $ym . '/' . $ct['user'] . '_checkouts_' . $ym .'.csv';
    //$filename = $tmp . '/' . '20140506' . '/' . $ct['user'] . '__checkouts__' . $ym .'.csv';
    $this->queryToFile($checkouts, $filename);

    $patrons = $this->getPatrons($begin->format('Y-m-d'), $finish->format('Y-m-d'));
    //$filename = $tmp . '/' . $ym . '/' . $ct['user'] . '_firstpatrondatafile_' . $ymd .'.csv';
    $filename = $tmp . '/' . $ym . '/' . $ct['user'] . '_patron_additions_' . $ymd .'.csv';
    $this->queryToFile($patrons, $filename);

    $cTypes = $this->getCollectionTypes();
    $filename = $tmp . '/' . $ym . '/' . $ct['user'] . '_collectiontypes_' . $ymd .'.csv';
    $this->queryToFile($cTypes, $filename);

    $lTypes = $this->getLocationTypes();
    $filename = $tmp . '/' . $ym . '/' . $ct['user'] . '_locationtypes_' . $ymd .'.csv';
    $this->queryToFile($lTypes, $filename);

    $mTypes = $this->getMaterialTypes();
    $filename = $tmp . '/' . $ym . '/' . $ct['user'] . '_materialtypes_' . $ymd .'.csv';
    $this->queryToFile($mTypes, $filename);

    $pTypes = $this->getPatronTypes();
    $filename = $tmp . '/' . $ym . '/' . $ct['user'] . '_patrontypes_' . $ymd .'.csv';
    $this->queryToFile($pTypes, $filename);

    /* Create Archive */
    chdir($tmp.'/'.$ym);
    if (!$zip->open($archive, ZIPARCHIVE::OVERWRITE)) {
      exit('Failed to create archive');
    }
    $zip->addGlob('*.csv');
    //if (!$zip->status == ZIPARCHIVE::ER_OK) {
       //echo "Failed to write files to zip\n";
    //}
    $zip->close();
    foreach (glob("*.csv") as $filename) {
       unlink($filename);
    }
    chdir($cwd);

    return $archive;
  }

  public function getCheckouts($begin, $finish) {
    $sql = "EXEC spl_civic_tech_get_checkouts :date_begin, :date_finish";
    $params = array(':date_begin'=>$begin, ':date_finish'=>$finish);            
    $result = $this->getQuery($sql, $params);
    
    return $result;
  }

  public function getPatrons($begin, $finish) {
    $sql = "EXEC spl_civic_tech_get_patrons :date_begin, :date_finish";
    $params = array(':date_begin'=>$begin, ':date_finish'=>$finish);            
    $result = $this->getQuery($sql, $params);
    
    return $result;
  }

  public function getLocationTypes() {
    $sql = "EXEC spl_civic_tech_get_location_types";
    $params = array();            
    $result = $this->getQuery($sql, $params);
    
    return $result;
  }

  public function getCollectionTypes() {
    $sql = "EXEC spl_civic_tech_get_collection_types";
    $params = array();            
    $result = $this->getQuery($sql, $params);
    
    return $result;
  }

  public function getMaterialTypes() {
    $sql = "EXEC spl_civic_tech_get_material_types";
    $params = array();            
    $result = $this->getQuery($sql, $params);
    
    return $result;
  }

  public function getPatronTypes() {
    $sql = "EXEC spl_civic_tech_get_patron_types";
    $params = array();            
    $result = $this->getQuery($sql, $params);
    
    return $result;
  }
  
  public function queryToFile($result, $filename) {
    $dirname = dirname($filename);
    if (!is_dir($dirname)) {
        mkdir($dirname, 0775, true);
    }
    $fp = fopen($filename, 'w');
    $headers = array();
    foreach ($result[0] as $k => $v) {
        $headers[] = $k;
    }
    fputcsv($fp, $headers);
    foreach ($result as $field) {
        fputcsv($fp, $field);
    }
    fclose($fp);
  }


} // CLASS

?>
