<?php


ini_set("memory_limit","2G");



class SPL_API_Reports_211_County_Collection_Lookup extends SPL_API_Reports {

  var $keys;
  var $configAPI;

  var $adhoc = '/mnt/horizon/onestop/adhoc';
  var $upload = '/var/web/---/upload';
  var $subdir = '/county/';
  var $dataload = 'COLLECTION.txt';
  var $lockfile = '.countylock';
  var $userfile;
  var $uploaded;
  var $processed; 

  public function getReportData() {
    //$this->unlinkLockFile(); // clear failed run

    $this->pdo = new PDO($this->config['api']['connect']
                        ,$this->config['api']['web_user']
                        ,$this->config['api']['web_pass']
                        );

		$report = new stdClass();

		$report->params = $this->params;

    $this->userfile = $this->params['files']['collectionlist'];

    // Remove last run
    if ( is_file($this->upload.$this->subdir.$this->dataload) ) {
      unlink($this->upload.$this->subdir.$this->dataload);
    }

    // Ensure one user doesn't clobber another's job.
    if ( is_file($this->upload.$this->subdir.$this->lockfile) ) {
      unlink($this->upload.$this->userfile['tmp_name']);
      $report->error = 'Job in process. Please try again in a few minutes.';
      return $report;
    }

    $this->writeLockFile();

    $this->processUpload();

    if ( $this->uploaded ) {
      $this->processFile();
      if ( $this->processed ) {
          $report->sorted = $this->loadCollectionData();
      } else {
        $report->error = 'File not processed.';
      }
    } else {
      $report->error = 'No file to process.';
    }

    $this->unlinkLockFile();

    return $report;
	}

  protected function loadCollectionData() {

    $params = array();

    $sql = "EXEC spl_load_county_collections";
    $result = $this->getQuery($sql, $params); 

    return $result;
  }


  /*
  protected function getAddressData() {
    $params = array();

    $sql = "EXEC spl_stats_load_adhoc_data";
    $result = $this->getQuery($sql, $params); 

    if ( 'barcode' == $this->fileInspection['type'] ) {
      $sql = "SELECT
              DISTINCT adhoc.dataload
              ,borrower_barcode.bbarcode
              ,borrower_address.email_address 
              ,borrower_address.email_name
              ,borrower.borrower#
              ,borrower.location
              ,borrower.btype
              ,borrower.n_ckos
              ,CONVERT(date, 
                        horizon.dbo.spl_get_datetime_from_epoch(borrower.last_cko_date)
                      ) AS last_cko_date
              ,CONVERT(date, 
                        horizon.dbo.spl_get_datetime_from_epoch(borrower.last_authentication_date)
                      ) AS last_authentication_date

              
              ,CONVERT(nvarchar(500), ISNULL(borrower_address.address1,'')) + ' ' + CONVERT(nvarchar(500), ISNULL(borrower_address.address2,'')) AS borrower_address
              ,LEFT(city_st.descr, LEN(city_st.descr)-4) AS borrower_address_city
              ,RIGHT(city_st.descr, 2) AS borrower_address_state
              ,SUBSTRING(borrower_address.postal_code,1,5) AS borrower_address_zip


              FROM spl_stats_adhoc_dataload AS adhoc
              
              LEFT OUTER JOIN horizon.dbo.borrower_barcode AS borrower_barcode
                ON borrower_barcode.bbarcode = adhoc.dataload
              LEFT OUTER JOIN horizon.dbo.borrower AS borrower
                ON borrower.borrower# = borrower_barcode.borrower#
              LEFT OUTER JOIN horizon.dbo.borrower_address AS borrower_address
                ON borrower.borrower# = borrower_address.borrower#
                AND borrower_address.ord = 0
              LEFT OUTER JOIN horizon.dbo.city_st as city_st
                ON borrower_address.city_st = city_st.city_st
              ";
      $result = $this->getQuery($sql, $params); 
    } elseif ( 'email' == $this->fileInspection['type'] ) {
      $sql = "SELECT
              DISTINCT adhoc.dataload
              ,borrower_barcode.bbarcode
              ,borrower_address.email_address 
              ,borrower_address.email_name
              ,borrower.borrower#
              ,borrower.location
              ,borrower.btype
              ,borrower.n_ckos
              ,CONVERT(date, 
                        horizon.dbo.spl_get_datetime_from_epoch(borrower.last_cko_date)
                      ) AS last_cko_date
              ,CONVERT(date, 
                        horizon.dbo.spl_get_datetime_from_epoch(borrower.last_authentication_date)
                      ) AS last_authentication_date

              
              ,CONVERT(nvarchar(500), ISNULL(borrower_address.address1,'')) + ' ' + CONVERT(nvarchar(500), ISNULL(borrower_address.address2,'')) AS borrower_address
              ,LEFT(city_st.descr, LEN(city_st.descr)-4) AS borrower_address_city
              ,RIGHT(city_st.descr, 2) AS borrower_address_state
              ,SUBSTRING(borrower_address.postal_code,1,5) AS borrower_address_zip


              FROM spl_stats_adhoc_dataload AS adhoc

              JOIN horizon.dbo.borrower_address AS borrower_address
                ON adhoc.dataload = borrower_address.email_address
                AND borrower_address.ord = 0
              LEFT OUTER JOIN horizon.dbo.borrower AS borrower
                ON borrower.borrower# = borrower_address.borrower#
              LEFT OUTER JOIN horizon.dbo.borrower_barcode AS borrower_barcode
                ON borrower_barcode.borrower# = borrower.borrower#
              LEFT OUTER JOIN horizon.dbo.city_st as city_st
                ON borrower_address.city_st = city_st.city_st
              ";
      $result = $this->getQuery($sql, $params); 
    } else {
      $result = array('error'=>'Unable to match data.');
    }

    if ( is_file($this->adhoc.$this->subdir.$this->dataload) ) {
      unlink($this->adhoc.$this->subdir.$this->dataload);
    }

    if ( is_array($result) && array_key_exists('borrower#', $result[0]) ) {
      return $result;
    } else {
      return false;
    }

  }
  */

  protected function writeLockFile() {
    $fp = fopen($this->upload.$this->subdir.$this->lockfile, 'w');
    fwrite($fp, 'Queue locked for processing.');
    fclose($fp);
  }

  protected function unlinkLockFile() {
    $lockfile = $this->upload.$this->subdir.$this->lockfile;
    if ( is_file($lockfile) ) {
      unlink($lockfile);
    }
  }

  protected function processUpload() {
    // Note: SPL_Report uploads are saved to a scratch space
    if ( UPLOAD_ERR_OK == $this->userfile['error'] ) {
        $upload = rename($this->upload.$this->userfile['tmp_name']
                        ,$this->upload.$this->subdir.$this->userfile['name']);
  
        $this->uploaded = $this->upload.$this->subdir.$this->userfile['name'];
    }
  }

  protected function processFile() {

    if ( !$this->fileInspection['error'] ) {

      // Move column to SQL server
      if ( is_writeable($this->adhoc.$this->subdir) ) {
        if ( file_exists($this->adhoc.$this->subdir.$this->dataload) ) {
          unlink($this->adhoc.$this->subdir.$this->dataload);
        }
        // For some reason rename is throwing an error
        copy($this->upload.$this->subdir.$this->dataload
                            ,$this->adhoc.$this->subdir.$this->dataload);
        unlink($this->upload.$this->subdir.$this->dataload);
      } 

      if ( file_exists($this->adhoc.$this->subdir.$this->dataload) ) {
        $this->processed = true;
      }
    } elseif ( file_exists($this->uploaded) ) {
        // cleanup userfile if we couldn't process (e.g. no useful data)
        unlink($this->uploaded);
    }
  }

  


}

?>