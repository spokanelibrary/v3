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
    $this->unlinkLockFile(); // clear failed run

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