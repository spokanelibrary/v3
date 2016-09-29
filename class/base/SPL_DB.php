<?php

require_once('SPL.php');

// Base SPL DB methods
abstract class SPL_DB extends SPL {
    
    var   $pdo;
    var   $now;
    //protected $dateformat = 'm/d/Y';
    protected   $dateformat = 'Y-m-d H:i';
    protected   $fetchmode = PDO::FETCH_ASSOC;
    
    function __construct($dsn=null,$user=null,$pass=null) {
        //putenv('TDSVER=8.0');
        if ( !is_null($dsn) ) {
            $this->setPdo($dsn,$user,$pass);
        }
        
        $this->now = $this->getDateFormat();
    }
    
    protected function setPdo($dsn=null,$user=null,$pass=null) {
        if ( !is_null($dsn) ) {
            $this->pdo = new PDO($dsn,$user,$pass);
            // Some PDO errors (e.g. execute) will fail silently w/o this attribute set
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE 
                                    ,PDO::ERRMODE_EXCEPTION);
        }
        
        return $this->hasPdo();
    }
    
    // Check for valid db handle
    protected function hasPdo() {
    
        return (isset($this->pdo)) ? true : false;
    }
    
    protected function runQuery($sql, $params=null) {
         try {
            $this->pdo->beginTransaction();
            $sth = $this->pdo->prepare($sql);
            $sth->execute($params);
            
            $id = $this->pdo->lastInsertId();
            
            $res = $this->pdo->commit();
            
            return $id;
            //return $res;

        } catch (PDOException $e) {
            //echo 'Connection failed: ' . $e->getMessage();
            //exit;
            return array('error'=>$e->getMessage());
        }
    }
    
    protected function getQuery($sql, $params=null) {
         try {
            $sth = $this->pdo->prepare($sql);
            $sth->execute($params);
            $res = $sth->fetchAll($this->fetchmode);
            return $res; 

        } catch (PDOException $e) {
            //echo 'Connection failed: ' . $e->getMessage();
            //exit;
            return array('error'=>$e->getMessage());
        }
    }
    
}

?>