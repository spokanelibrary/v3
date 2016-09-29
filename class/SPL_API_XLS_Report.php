<?php

ini_set("memory_limit","512M");

require_once('base/SPL_DB.php');

class SPL_API_XLS_Report extends SPL_DB {

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
    // setup for PHPExcel library
    $path = '/var/web/---/php/PHPExcel-1.8/Classes';
    set_include_path(get_include_path() . PATH_SEPARATOR . $path);
    include 'PHPExcel.php';
  }

  public function getApiRequest() {
    
    if ( isset($this->method[1]) ) {
      switch( $this->method[1] ) {
        case 'balance-sheet':
          return $this->getBalanceSheet();
          break;
        case 'accounts-receivable':
          return $this->getAR();
          break;
      } 
    }

  }

  protected function getAR() {
    
    $date = new DateTime();
    $today = $date->format('Y-m-d');

    $data = $this->getARData();

    if ( !isset($data) ) {
      return;
    } else {
      //return $today;
      $objPHPExcel = new PHPExcel();

      // Workbook metadata
      $objPHPExcel->getProperties()->setCreator('Sean Girard');
      $objPHPExcel->getProperties()->setLastModifiedBy('SPL Auto Report');
      $objPHPExcel->getProperties()->setTitle('Current A/R');
      $objPHPExcel->getProperties()->setSubject('Current A/R: '.$today);
      $objPHPExcel->getProperties()->setDescription('Auto-generated Morning A/R for '.$today.'.');

      // Real-time movement
      $objPHPExcel->setActiveSheetIndex(0);
      $objPHPExcel->getActiveSheet()->setTitle('AR '.$today);
      
      $row = 1;
      /*
      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $today);
      $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
      $row++;
      $row++;
      */
      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, ' ');
      $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
      
      $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, 'Customers');
      $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getFont()->setBold(true);
      
      $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, 'Total');
      $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getFont()->setBold(true);
      
      $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, 'Lost');
      $objPHPExcel->getActiveSheet()->getStyle('D'.$row)->getFont()->setBold(true);
      
      $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, 'Fines');
      $objPHPExcel->getActiveSheet()->getStyle('E'.$row)->getFont()->setBold(true);

      $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, 'Other');
      $objPHPExcel->getActiveSheet()->getStyle('F'.$row)->getFont()->setBold(true);
      
      $row++;

      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, 'All Accounts Receivable');
      $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
      $row++;

      $i = 0;
      while ( $i < 8 ) {
        $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $data[$i]['label']);
        $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $data[$i]['quant']);
        $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $data[$i]['total']);
        $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $data[$i]['lost']);
        $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $data[$i]['fines']);
        $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, $data[$i]['other']);

        $row++;
        $i++;
      }

      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, 'Less than 30 days');
      $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
      $row++;

      while ( $i < 16 ) {
        $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $data[$i]['label']);
        $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $data[$i]['quant']);
        $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $data[$i]['total']);
        $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $data[$i]['lost']);
        $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $data[$i]['fines']);
        $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, $data[$i]['other']);

        $row++;
        $i++;
      }

      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, '30 – 60 days');
      $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
      $row++;

      while ( $i < 24 ) {
        $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $data[$i]['label']);
        $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $data[$i]['quant']);
        $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $data[$i]['total']);
        $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $data[$i]['lost']);
        $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $data[$i]['fines']);
        $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, $data[$i]['other']);

        $row++;
        $i++;
      }

      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, '60 – 90 days');
      $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
      $row++;

      while ( $i < 32 ) {
        $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $data[$i]['label']);
        $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $data[$i]['quant']);
        $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $data[$i]['total']);
        $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $data[$i]['lost']);
        $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $data[$i]['fines']);
        $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, $data[$i]['other']);

        $row++;
        $i++;
      }

      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, 'Over 90 days');
      $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
      $row++;

      while ( $i < 40 ) {
        $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $data[$i]['label']);
        $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $data[$i]['quant']);
        $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $data[$i]['total']);
        $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $data[$i]['lost']);
        $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $data[$i]['fines']);
        $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, $data[$i]['other']);

        $row++;
        $i++;
      }


      /*
       *  OUTPUT
       */

      // Reset to first sheet
      $objPHPExcel->setActiveSheetIndex(0);
      $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
      // Seems to prevent "do you want to save changes" dialog (on close when no changes made)
      $objWriter->setPreCalculateFormulas(true);

      //$yesterday = 'test-file';

      $filename = 'spl-accounts-receivable_'.$today.'.xlsx';
      $temppath = '/var/web/---/xls/';
      $objWriter->save($temppath.$filename);

      $permpath = '/mnt/all-staff/Business-Office/Reports/Accounts-Receivable/';
      if ( is_dir($permpath)
        && is_writable($permpath) ) {
        $objWriter->save($permpath.$filename);
        unlink($temppath.$filename);
      }


    }

  }

  protected function getARData() {
    
    $dt = new DateTime();
    $ut = new DateTime('1970-01-01');
    $epoch =  $ut->diff($dt);

    $params = array(':epoch'=>$epoch->days);

    $sql = "SELECT * FROM spl_reports_055_log WHERE epoch = :epoch ORDER BY id ASC";

    $result = $this->getQuery($sql, $params); 

    return array_map(array($this, 'sortARData'), $result);;
  }

  protected function sortARData($row) {
    $row['quant'] = number_format($row['quant']);
    foreach ( $row as $k => $v ) {  
      if ( in_array($k, array('total', 'lost', 'fines', 'other')) ) {
        $row[$k] = number_format($row[$k], 2);
      }
    }

    return $row;
  }

  protected function getBalanceSheet() {
    $date = new DateTime();
    $today = $date->format('Y-m-d');
    $date->sub(new DateInterval('P1D'));
    $yesterday = $date->format('Y-m-d');

    $data = $this->getBalanceSheetData($yesterday);

    if ( !isset($data->sorted) ) {
      return;
    }

    // http://www.clock.co.uk/blog/phpexcel-cheatsheet

    $objPHPExcel = new PHPExcel();

    // Workbook metadata
    $objPHPExcel->getProperties()->setCreator('Sean Girard');
    $objPHPExcel->getProperties()->setLastModifiedBy('SPL Auto Report');
    $objPHPExcel->getProperties()->setTitle('A/R Balance Sheet');
    $objPHPExcel->getProperties()->setSubject('A/R Balance Sheet: '.$yesterday);
    $objPHPExcel->getProperties()->setDescription('Auto-generated A/R Balance snapshot for '.$yesterday.'.');

    // Real-time movement
    $objPHPExcel->setActiveSheetIndex(0);
    $objPHPExcel->getActiveSheet()->setTitle('Real-time movement');
    $row = 1;
    $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Real-time movement: transaction is in date range (burb trigger)');
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('A2', 'Reporting period: '.$data->params->date->begin.' - '.$data->params->date->finish);
    $objPHPExcel->getActiveSheet()->getStyle('A2')->getFont()->setBold(true);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('A3', 'Auto generated: '.$today);
    $objPHPExcel->getActiveSheet()->getStyle('A3')->getFont()->setBold(true);
    $row++;

    foreach ( $data->sorted->splburblog as $b=>$burb ) {
      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $burb->type_label);
      $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $burb->label);
      $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $burb->total);
      $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
      $row++;
    }
    
    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, 'Total movement');
    $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
    $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getFont()->setBold(true);

    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, '=SUM(C4:C'.($row-1).')');
    $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
    $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getFont()->setBold(true);

    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);

    $objPHPExcel->getActiveSheet()->freezePane('A4');    
    
    // Collected/Waived/Adjusted
    $objPHPExcel->createSheet();
    $objPHPExcel->setActiveSheetIndex(1);
    $objPHPExcel->getActiveSheet()->setTitle('Collected~Waived~Adjusted');
    $row = 1;
    $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Collected/Waived/Adjusted: from payment logs (pay/wave/adj is in date range)');
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('A2', 'Reporting period: '.$data->params->date->begin.' - '.$data->params->date->finish);
    $objPHPExcel->getActiveSheet()->getStyle('A2')->getFont()->setBold(true);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('A3', 'Auto generated: '.$today);
    $objPHPExcel->getActiveSheet()->getStyle('A3')->getFont()->setBold(true);
    $row++;
    foreach ( $data->sorted->paylog->amounts as $a => $amount ) {
      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $amount->label);
      $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
      $row++;
      $amount_row = $row;
      foreach ( $amount->blocks as $b => $block ) {
        $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $block->block_type);
        $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $block->block_label);
        $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $block->total);
        $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        $row++;
      }
      //$objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, 'Row: '.$amount_row);

      $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $amount->label.' subtotal:');
      $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
      $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getFont()->setBold(true);

      $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, '=SUM(C'.$amount_row.':C'.($row-1).')');
      $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
      $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getFont()->setBold(true);

      $row++;
    }

    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);

    $objPHPExcel->getActiveSheet()->freezePane('A4'); 

    // Total A/R
    $objPHPExcel->createSheet();
    $objPHPExcel->setActiveSheetIndex(2);
    $objPHPExcel->getActiveSheet()->setTitle('Total AR');
    $row = 1;
    $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Total A/R: from burb (current, no date range)');
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('A2', 'Reporting period: '.$data->params->date->begin.' - '.$data->params->date->finish);
    $objPHPExcel->getActiveSheet()->getStyle('A2')->getFont()->setBold(true);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('A3', 'Auto generated: '.$today);
    $objPHPExcel->getActiveSheet()->getStyle('A3')->getFont()->setBold(true);
    $row++;
    foreach ( $data->sorted->burblog->amounts as $a => $amount ) {
      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $amount->label);
      $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
      $row++;
      $amount_row = $row;
      foreach ( $amount->blocks as $b => $block ) {
        $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $block->block_type);
        $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $block->block_label);
        $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $block->total);
        $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        $row++;
      }
      //$objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, 'Row: '.$amount_row);

      $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $amount->label.' subtotal:');
      $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
      $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getFont()->setBold(true);

      $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, '=SUM(C'.$amount_row.':C'.($row-1).')');
      $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
      $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getFont()->setBold(true);

      $row++;
    }

    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);

    $objPHPExcel->getActiveSheet()->freezePane('A4'); 

    // Credited
    $objPHPExcel->createSheet();
    $objPHPExcel->setActiveSheetIndex(3);
    $objPHPExcel->getActiveSheet()->setTitle('Credited');
    $row = 1;
    $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Credited: from stats_summary (transactions not available)');
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, 'Note: credits were issued in this date range, not credited against fees levied in this date range.');
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, 'Reporting period: '.$data->params->date->begin.' - '.$data->params->date->finish);
    $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, 'Auto generated: '.$today);
    $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
    $row++;

    $credited = $data->sorted->credited;
    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, 'Day Range');
    $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $credited->day_range->int);
    $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, 'Month Range');
    $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $credited->month_range->int);
    $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, 'Year Range');
    $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $credited->year_range->int);
    $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
    $row++;


    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);

    $objPHPExcel->getActiveSheet()->freezePane('A5'); 

    // A/R Balance
    $objPHPExcel->createSheet();
    $objPHPExcel->setActiveSheetIndex(4);
    $objPHPExcel->getActiveSheet()->setTitle('AR Balance');
    $row = 1;
    $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'A/R Balance: from old "intra" report');
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, 'Last Updated: '.$credited = $data->sorted->arintra->date);
    $row++;
    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, '');
    $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, 'Count');
    $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, 'Total');
    $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, 'Lost');
    $objPHPExcel->getActiveSheet()->getStyle('D'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, 'Fines');
    $objPHPExcel->getActiveSheet()->getStyle('E'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, 'Other');
    $objPHPExcel->getActiveSheet()->getStyle('F'.$row)->getFont()->setBold(true);
    $row++;
    foreach ( $data->sorted->arintra->under_90 as $amount ) {
      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $amount->label);
      $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
      $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $amount->count->int);
      $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0');
      $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $amount->total->int);
      $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
      $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $amount->lost->int);
      $objPHPExcel->getActiveSheet()->getStyle('D'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
      $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $amount->fines->int);
      $objPHPExcel->getActiveSheet()->getStyle('E'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
      $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, $amount->other->int);
      $objPHPExcel->getActiveSheet()->getStyle('F'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
      $row++;
    }
    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, '');
    $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, '');
    $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, 'Total 90+');
    $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, 'Lost 90+');
    $objPHPExcel->getActiveSheet()->getStyle('D'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, 'Fines 90+');
    $objPHPExcel->getActiveSheet()->getStyle('E'.$row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, 'Other 90+');
    $objPHPExcel->getActiveSheet()->getStyle('F'.$row)->getFont()->setBold(true);
    $row++;
    foreach ( $data->sorted->arintra->over_90 as $amount ) {
      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $amount->label);
      $objPHPExcel->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
      $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $amount->count->int);
      $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0');
      $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $amount->total->int);
      $objPHPExcel->getActiveSheet()->getStyle('C'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
      $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $amount->lost->int);
      $objPHPExcel->getActiveSheet()->getStyle('D'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
      $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $amount->fines->int);
      $objPHPExcel->getActiveSheet()->getStyle('E'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
      $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, $amount->other->int);
      $objPHPExcel->getActiveSheet()->getStyle('F'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
      $row++;
    }

    $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);

    $objPHPExcel->getActiveSheet()->freezePane('A3'); 

    /*
     *  OUTPUT
     */

    // Reset to first sheet
    $objPHPExcel->setActiveSheetIndex(0);
    $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
    // Seems to prevent "do you want to save changes" dialog (on close when no changes made)
    $objWriter->setPreCalculateFormulas(true);

    //$yesterday = 'test-file';

    $filename = 'spl-balance-sheet_'.$yesterday.'.xlsx';
    $temppath = '/var/web/---/xls/';
    $objWriter->save($temppath.$filename);

    $permpath = '/mnt/all-staff/Business-Office/Reports/Balance-Sheet/';
    if ( is_dir($permpath)
      && is_writable($permpath) ) {
      $objWriter->save($permpath.$filename);
      unlink($temppath.$filename);
    }



    return $data;
  }

  protected function getBalanceSheetData($date) {
    
    $api = 'http://api.spokanelibrary.org/dash/balance-sheet&params[date][begin]='.$date.'&params[date][finish]='.$date.'&apikey='.$this->config['api']['key'];
    $data = json_decode(SPL_API::curlPostProxy($api));

    return $data;
  }

  

} // CLASS

?>
