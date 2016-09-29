<?php


require_once('base/SPL_DB.php');

class SPL_API_NW_BioFiles extends SPL_DB {

  var $apikey;
  var $method;
  var $params;
  var $config;

  var $mdb;
  var $name;
  var $cache = false;
  var $sqlite = 'sqlite:/var/web/---/cache/biofiles.sqlite';

  function __construct($config=null, $request=null, $apikey=null) {
    
    if ( is_array($config) ) {
      $this->config = $config;

      /*
      if ( isset($config['api']['connect']) 
        && isset($config['api']['web_user']) 
        && isset($config['api']['web_pass']) ) { 
        
        parent::__construct( $config['api']['connect']
                            ,$config['api']['web_user']
                            ,$config['api']['web_pass']
                            );
      }
      */
      
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
    $this->setName();


  }

  public function getApiRequest() {
    //return $this->params;
    
    $this->mdb = $this->getFullMdb();
    $this->cache = $this->cacheFullMdb(); 

    return $this->searchBioFiles();
  }

  protected function searchBioFiles() {
    if ( $this->cache ) {
      
      $db = new PDO($this->sqlite);
      $params = array(':name'=>'%'.$this->name.'%');
      // first look for exact match
      $sql = "SELECT 
              name
              ,file_name
              FROM BioFiles
              WHERE name LIKE :name
      ";
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // iff no results, build a more complex query
      if ( 1 > count($result) ) {
        $terms = $this->splitQueryParams();
        $count = count($terms);
        // multiple terms?
        if ( $count > 1 ) {          
          foreach ( $terms as $i=>$term ) {
            $sql .= "OR name LIKE :$i
            ";
            $params[':'.$i] = '%'.$term.'%';
          }
          $stmt = $db->prepare($sql);
          $stmt->execute($params);
          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
      }
      
    } else {
      // no cache file available
      $result = array('error'=>'No results found');
    }
    
    return $result;
  }

  protected function setName() {
    if ( $this->params['term'] ) {
      $name = $this->params['term'];
    } else {
      $name = '*';
    }

    $this->name = $name;
  }
  
  protected function splitQueryParams() {
    $terms = null;
    if ($this->name) {
      $name = str_ireplace(' ', '+',
                str_ireplace(array('.',',',';'), '', $this->name)
              );
      $terms = explode('+', $name);
    }
    
    return $terms;
  }

  protected function cacheFullMdb() {
    try {
      $db = new PDO($this->sqlite);
      
      $sql = "DROP TABLE BioFiles";
      $db->exec($sql); 
      
      $sql = "CREATE TABLE BioFiles
              (id           INTEGER PRIMARY KEY
              ,prefix       TEXT 
              ,name         TEXT
              ,file_name    TEXT
              ,cataloged   BOOLEAN
              ,see_ref      BOOLEAN
              ,new_file     BOOLEAN
              )
      ";
      $db->exec($sql);   
      
      $sql = "INSERT INTO BioFiles
              (id
              ,prefix
              ,name
              ,file_name
              ,cataloged
              ,see_ref
              ,new_file
              )
              VALUES
              (:id
              ,:prefix
              ,:name
              ,:file_name
              ,:cataloged
              ,:see_ref
              ,:new_file
              )
      ";
      $stmt = $db->prepare($sql);
      try {
        $db->beginTransaction();
        
        foreach ($this->mdb as $record) {
          $vals = array(':id'         => $record['id']
                        ,':prefix'    => $record['prefix']
                        ,':name'      => $record['name']
                        ,':file_name' => $record['file_name']
                        ,':cataloged' => $record['cataloged']
                        ,':see_ref'   => $record['see_ref']
                        ,':new_file'  => $record['new_file']
                        );
          $stmt->execute($vals);
        }
        $db->commit();
      } catch (PDOException $e) {
        $db->rollBack();
        //trace($e);
      }
      // close connection
      unset($db);
      unset($this->mdb);
    } catch(Exception $e) {
      return array('error'=>$e->getMessage());
    }
    
    return true;
  }

  protected function getFullMdb() {
    try{
      $dbh = new PDO("odbc:BioFiles");
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch(PDOException $e){
      return array('error'=>$e->getMessage());
    }

    $sql = 'SELECT
            *
            FROM "Bio Files"
            ';

    $stmt = $dbh->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //foreach($result as $key=>$val) {
      //echo $key.' - '.$val.'<br />';
    //}
    if ( is_array($result) ) { 
      foreach ( $result as $row ) {
        foreach ($row as $k=>$v) { 
          $results[$row['ID']][strtolower(str_replace(' ', '_', $k))] = $v;
        }
      }
    }

    return $results;
  }

} // CLASS

?>
