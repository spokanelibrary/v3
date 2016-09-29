<?php

ini_set("memory_limit","2G");

$path = '/var/web/---/php/PHPExcel-1.8/Classes';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require 'PHPExcel.php';

class inspectionFilter implements PHPExcel_Reader_IReadFilter {

  public function readCell($column, $row, $worksheetName = '') {
    if ( 1 == $row || 2 == $row ) {
      return true;
    }
    return false;
  }

}

class dataFilter implements PHPExcel_Reader_IReadFilter {
  
  private $_row;
  private $_column;

  public function __construct($params) {
    $this->_row = $params['row'];
    $this->_column = $params['column'];
  }

  public function readCell($column, $row, $worksheetName = '') {
    if ( $row >= $this->_row && $column == $this->_column ) {
      return true;
    }
    return false;
  }

}


class SPL_API_Reports_206_Borrower_Address_By_List extends SPL_API_Reports {

  var $keys;
  var $configAPI;

  var $adhoc = '/mnt/horizon/onestop/adhoc';
  var $upload = '/var/web/---/upload';
  var $subdir = '/address/';
  var $dataload = 'dataload.csv';
  var $lockfile = '.addresslock';
  var $userfile;
  var $uploaded;
  var $processed;
	var $addresses;
  var $saveddata = 'spl-adhoc-addresses.xlsx'; // must match case in /dl/index.php

  public function getReportData() {
    //$this->unlinkLockFile(); // clear failed run

    $this->pdo = new PDO($this->config['api']['connect']
                        ,$this->config['api']['web_user']
                        ,$this->config['api']['web_pass']
                        );

		$report = new stdClass();

		$report->params = $this->params;

    $this->userfile = $this->params['files']['vendorlist'];

    // Remove last run
    if ( is_file($this->upload.$this->subdir.$this->saveddata) ) {
      unlink($this->upload.$this->subdir.$this->saveddata);
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
      $report->sorted->fileInspection = $this->fileInspection;
      if ( $this->processed ) {
        $this->addresses = $this->getAddressData();
        if ( $this->addresses ) {
          //$report->sorted->addressData = $this->addresses;
          $report->sorted->fileInspection['addressMatches'] = count($this->addresses); 
          
          $this->saveAddressData();
        }
      } else {
        $report->error = 'File not processed.';
      }
    } else {
      $report->error = 'No file to process.';
    }

    $this->unlinkLockFile();

    return $report;
	}

  protected function sanitizeAddressData($data) {
    if ( is_array($data) ) {
      foreach ( $data as $k => $v ) {
        $data[$k] = str_ireplace(',', '', $v);
      }
    }
    return $data;
  }

  protected function saveAddressData() {
    
    $addressData = $this->addresses;
    $addressHeaders = array_keys($addressData[0]);
    
    $addressData = array_map(array($this, 'sanitizeAddressData'), $addressData);
    array_unshift($addressData, $addressHeaders);

    $objPHPExcel = new PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);
    $objPHPExcel->getActiveSheet()->fromArray($addressData, NULL, 'A1');
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

    $objWriter->save($this->upload.$this->subdir.$this->saveddata);    
  }

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
    $inputFileName = $this->uploaded;
    //$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);

    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
    $objReader->setReadDataOnly(true);

    // Get first two rows for inspection
    $objReader->setReadFilter( new inspectionFilter() );
    $objPHPExcel = $objReader->load($inputFileName);
    
    // Inspect what and where is the data we need
    $this->fileInspection = $this->inspectFile($objPHPExcel);
    $this->fileInspection['fileType'] = $inputFileType;

    if ( !$this->fileInspection['error'] ) {

      // Load only the data we need
      $objReader->setReadFilter( new dataFilter($this->fileInspection) ); 
      $objPHPExcel = $objReader->load($inputFileName);

      // Extract single column we need
      if ( $this->fileInspection['header'] ) {
        $objPHPExcel->getActiveSheet()->removeRow(1,1);
      }
      $this->fileInspection['highestRow'] = $objPHPExcel->getActiveSheet()->getHighestRow();    
      $dataRange = $this->fileInspection['column'].'1:'.$this->fileInspection['column'].$this->fileInspection['highestRow'];
      $dataValues = $objPHPExcel->getActiveSheet()->rangeToArray($dataRange,NULL,TRUE,TRUE, TRUE);
      $objPHPExcel = new PHPExcel();
      $objPHPExcel->setActiveSheetIndex(0);
      $objPHPExcel->getActiveSheet()->fromArray($dataValues, NULL, 'A1');

      // Save column to a file
      $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
      //$objWriter->setDelimiter(',');
      //$objWriter->setLineEnding("\r\n");
      $objWriter->setEnclosure('');
      $objWriter->save($this->upload.$this->subdir.$this->dataload);

      // Remove original user file
      unlink($inputFileName);

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

  // look for a column with a barcode or email address, prefer a barcode
  protected function inspectFile($objPHPExcel) {
    $inspect = array();

    $objWorksheet = $objPHPExcel->getActiveSheet();
    foreach ($objWorksheet->getRowIterator() as $r => $row) {
      $cellIterator = $row->getCellIterator();
      foreach ($cellIterator as $c => $cell) {
        $v = $cell->getValue();
        $v = trim($v);
        if ( 14 == strlen($v) && is_numeric($v) ) {
          $inspect[$r][$c]['barcode'] = true;
          $inspect[$r][$c]['value'] = $v;
        }
        if ( stristr($v, '@') ) {
          $inspect[$r][$c]['email'] = true;
          $inspect[$r][$c]['value'] = $v; 
        }
        
        //$inspect[$r][$c]['value'] = $v; 
      }
    }

    $file = array();
    if ( is_array($inspect[1]) ) {
      $file['header'] = false;
    } elseif ( is_array($inspect[2]) ) {
      $file['header'] = true;
    } else {
      $file['error'] = true;
    }
    
    if ( !$file['error']  ) {
      if ( $file['header'] ) {
        $r = 2;
      } else {
        $r = 1;
      }

      foreach ( $inspect[$r] as $c => $cell ) {
        if ( $cell['barcode'] ) {
          $file['type'] = 'barcode';
          $file['row'] = $r;
          $file['column'] = $c;
        }
      }

      if ( !$file['type'] ) {
        foreach ( $inspect[$r] as $c => $cell ) {
          if ( $cell['email'] ) {
            $file['type'] = 'email';
            $file['row'] = $r;
            $file['column'] = $c;
          }
        }
      }

    }

    return $file;
  }


}

?>