<?php

class SPL_API_Reports_203_OverDrive_Metadata extends SPL_API_Reports {

  var $keys;
  var $configAPI;

  var $upload = '/var/web/---/upload/';
  var $csv = 'overdrive.csv';
  var $err = 'error-log.xls';
  var $mrc = 'overdrive.mrc';

	public function getReportData() {

    require 'File/MARC.php';
    require 'File/MARCXML.php';

    $path = '/var/web/---/php/PHPExcel-1.8/Classes';
    set_include_path(get_include_path() . PATH_SEPARATOR . $path);
    require 'PHPExcel.php';

		$report = new stdClass();

		$report->params = $this->params;

    if ( $this->processUpload() ) {
      $this->configAPI();
      $this->getRecords();

      
      if ( $this->records ) {
        $this->writeRecords($this->upload.$this->mrc);


        $i = 1;
        while ( $record = $this->marcout->next() ) {
          $marc = array('id'=>$i, 'marc'=>trim(utf8_encode($record->__toString())));
          $report->sorted->records[] = $marc;
          $i++;
        }
        
      }
      
      
      if ( $this->empty ) {
        $this->writeErrorLog($this->err);
        $report->sorted->errors = $this->empty;
      }

    }

    return $report;
	}

  protected function configAPI() {
    $this->configAPI = array(
                'oclc'=>array(  'api'=>'http://www.worldcat.org/webservices/catalog/content/', // OCLC webservices api
                                'wskey'=>$this->config['api']['oclc']['wskey'];
                                ),
                'overdrive'=>array(
                                    'url'=>'http://overdrive.spokanelibrary.org/ContentDetails.htm?ID=', // Shouldn't need this
                                    'map'=>array(
                                                'row'=>'Input Row #',
                                                'url'=>'URL',
                                                'isbn'=>'ISBN',
                                                'oclc'=>'OclcControlNumber',
                                                'title'=>'Title'
                                                )
                            )
                );
  }

  protected function processUpload() {
    // Note: SPL_Report uploads are saved to a scratch space
    if ( UPLOAD_ERR_OK == $this->params['files']['metadata']['error'] ) {
      if ( stristr($this->params['files']['metadata']['name'], '.csv') == TRUE ) {
        $upload = rename($this->upload.$this->params['files']['metadata']['tmp_name']
                        ,$this->upload.$this->csv);
        //clearstatcache();
      } else {
        unlink($this->upload.$this->params['files']['metadata']['tmp_name']);
      }
    }
    
    return $upload;
  }

  // Output MARC21 file
  protected function writeRecords($file) {
      $success = false;
      if ( $file && $this->records ) {
          if ( file_exists($file) ) {
              // delete $file
              unlink($file);
          }
          $marc21_file = fopen($file, "wb");
          while ($record = $this->records->next()) {
              fwrite($marc21_file, $record->toRaw());
          }
          fclose($marc21_file);
          $success = true;
      }
      
      return $success;
  }

  // Output Error Log suitable for processing
  public function writeErrorLog($file) {
      $file = $this->upload.$file;
      //trace($this->error,'No OCLC matchpoint:');
      //trace($this->empty,'No OCLC records found:');        
      $map = $this->config['overdrive']['map'];
      
      $objPHPExcel = new PHPExcel();
      $objPHPExcel->getProperties()->setCreator("SPL-OverDrive-MetaData-Parser")
                                   ->setLastModifiedBy("SPL-OverDrive-MetaData-Parser")
                                   ->setTitle("SPL OverDrive MetaData Errors")
                                   ->setSubject("SPL OverDrive MetaData Errors")
                                   ->setDescription("Records with no matchpoints or associated OCLC records.")
                                   ->setKeywords("Spokane Public Library, OverDrive, OCLC")
                                   ->setCategory("SPL OverDrive Output/Input");

      $objPHPExcel->setActiveSheetIndex(0)
          ->setCellValue('A1', $map['row'])
          ->setCellValue('B1', $map['oclc'])
          ->setCellValue('C1', $map['isbn'])
          ->setCellValue('D1', $map['title'])
          ->setCellValue('E1', $map['url']);
      
      // No matchpoints
      $i = 1;
      if ( isset($this->error) && is_array($this->error) ) {
          foreach ($this->error as $id=>$record) {
              $i++;
              
              $objPHPExcel->getActiveSheet()
                  ->setCellValue('A'.$i, $record['row'])
                  ->setCellValue('B'.$i, $record['oclc'])
                  ->setCellValue('C'.$i, $record['isbn'])
                  ->setCellValue('D'.$i, $record['title'])
                  ->setCellValue('E'.$i, $record['url']);
                  
              $objPHPExcel->getActiveSheet()->getCell('A'.$i)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
              $objPHPExcel->getActiveSheet()->getCell('B'.$i)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
              $objPHPExcel->getActiveSheet()->getCell('C'.$i)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
              $objPHPExcel->getActiveSheet()->getCell('D'.$i)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
              $objPHPExcel->getActiveSheet()->getCell('E'.$i)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
          }
      }
      
      // No matching records
      if ( isset($this->empty) && is_array($this->empty) ) {
          foreach ($this->empty as $id=>$record) {
              $i++;
              
              $objPHPExcel->getActiveSheet()
                  ->setCellValue('A'.$i, $record['row'])
                  ->setCellValue('B'.$i, $record['oclc'])
                  ->setCellValue('C'.$i, $record['isbn'])
                  ->setCellValue('D'.$i, $record['title'])
                  ->setCellValue('E'.$i, $record['url']);
                  
              $objPHPExcel->getActiveSheet()->getCell('A'.$i)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
              $objPHPExcel->getActiveSheet()->getCell('B'.$i)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
              $objPHPExcel->getActiveSheet()->getCell('C'.$i)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
              $objPHPExcel->getActiveSheet()->getCell('D'.$i)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
              $objPHPExcel->getActiveSheet()->getCell('E'.$i)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
          }
      }
      
      $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
      $objPHPExcel->getActiveSheet()->getStyle('B1')->getFont()->setBold(true);
      $objPHPExcel->getActiveSheet()->getStyle('C1')->getFont()->setBold(true);
      $objPHPExcel->getActiveSheet()->getStyle('D1')->getFont()->setBold(true);
      $objPHPExcel->getActiveSheet()->getStyle('E1')->getFont()->setBold(true);
      
      $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
      $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
      $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
      $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
      $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
      
      $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5')
          //->setDelimiter(',')
          //->setEnclosure('')
          //->setLineEnding("\r\n")
          ///->setSheetIndex(0)
          ->save($file);
      
      return true;
  }

  // Get a hand-rolled collection of MARC records
  public function getRecords() {
      $this->parseData();
      $this->parseRecordsFromWebService();
      
      if ( $this->marc ) {
          // Build up a raw string of records
          // I'll bet Dan Scott has some convenience
          // methods to do this, but I can't find 'em.
          $raw = null;
          foreach ($this->marc as $marc) {
              $raw .= $marc->toRaw();
          }
          $this->records = new File_MARC($raw, File_MARC::SOURCE_STRING);
          $this->marcout = new File_MARC($raw, File_MARC::SOURCE_STRING);
      }
  }
  
  // Download and massage MARCXML from OCLC 
  protected function parseRecordsFromWebService() {
      if ( $this->meta ) {
          $i = 1;
          foreach ($this->meta as $record) {
              $url = null;
              // For whatever reason a dot '.' in oclc# throws an error
              if ( $record['oclc'] && !strpos($record['oclc'], '.') ) {
                  $url = $this->configAPI['oclc']['api'] . $record['oclc'];
              } elseif ( $record['isbn'] ) {
                  $url = $this->configAPI['oclc']['api'] . 'isbn/' . $record['isbn'];
              }
              
              
              if ( $url && ( $i<=$this->params['vals']['records'] || 'all' ==$this->params['vals']['records'] ) ) {
                  $request = array('wskey'=>$this->configAPI['oclc']['wskey']);
                  $oclc_api_data = curl_post_proxy($url, $request);

                  if ( $oclc_api_data ) {
                      $result = new File_MARCXML($oclc_api_data, File_MARC::SOURCE_STRING);
                      $marc = $result->next();
                      if ( $marc ) { 
                          
                          // 1. Change the 245 h subfield (gmd) to 'ebook'. 
                          $tag_245 = $marc->getFields('245');
                          
                          foreach ($tag_245 as $field) {
                              $h = $field->getSubfields('h');
                              foreach ($h as $sub) {
                                  // should only be 1 'h', but could delete them all and recreate
                                  $sub->delete();
                                  //$sub->setData('[ebook]');
                              }
                              $sub_245_h = new File_MARC_Subfield('h', '[ebook]');
                              //$field->appendSubfield($sub_245_h);
                              // make sure 245h comes after 245a
                              $sub_245_a = $field->getSubfields('a');
                              // Note: this depends on an old version of File_MARC .0.7.1
                              // Actually, it doesn't work, it was just masking the error
                              //$field->insertSubfield($sub_245_h, $sub_245_a[0]);
                              $sub_245_a_new = new File_MARC_Subfield('a', $sub_245_a[0]->getData());
                              $sub_245_a[0]->delete();
                              $tag_245[0]->prependSubfield($sub_245_h); 
                              $tag_245[0]->prependSubfield($sub_245_a_new);
                          }
                          

                          // 2. Add an 092 of EBOOK. 
                          $marc->appendField(new File_MARC_Data_Field('092', array(
                              new File_MARC_Subfield('a', 'EBOOK'),
                              ), null, null
                          ));
                          
                          // 3. Delete the MARC 999 tag, if present. 
                          $tag_999 = $marc->getFields('999');
                          foreach ($tag_999 as $field) {
                              $field->delete();
                          }
                          
                          // 4. Make sure that the local subject heading for electronic books looks like this:
                          // 655 7 $aElectronic books.$2local 
                          $tag_655 = $marc->getFields('655');
                          foreach ($tag_655 as $field) { 
                              $subfields = $field->getSubfields();
                              if ( $subfields ) {
                                  foreach ($subfields as $subfield) {
                                      $content = $subfield->getData();
                                      if(stristr($content, 'electronic books') == TRUE) {
                                          $subfield->delete();
                                      }
                                      if(stristr($content, 'audiobook') == TRUE) {
                                          $subfield->delete();
                                      }
                                      if(stristr($content, 'audiobooks') == TRUE) {
                                          $subfield->delete();
                                      }
                                      if(stristr($content, 'local') == TRUE) {
                                          $subfield->delete();
                                      }
                                  }
                              }
                          }
                          // 655 subfields
                          $subfields = array();
                          $subfields[] = new File_MARC_Subfield('a', 'Electronic books.');
                          $subfields[] = new File_MARC_Subfield('2', 'local');
                          // 655 field
                          $marc->appendField(new File_MARC_Data_Field('655', $subfields, null, 7)
                          );
                          
                          // 5. Delete print 020 tags.
                          $tag_020 = $marc->getFields('020');
                          foreach ($tag_020 as $field) {
                              //$field->delete();
                              $subfields = $field->getSubfields();
                              if ( $subfields ) {
                                  foreach ($subfields as $subfield) {
                                      $content = $subfield->getData();
                                      if(stristr($content, 'electronic bk.') === FALSE) {
                                          $subfield->delete();
                                      }
                                  }
                              }
                          }
                          
                          // 6. Confirm the MARC 856 tag(s) has a first indicator of 4 and second indicator of blank. 
                          // 7. Delete the MARC 856 subfield 3 tag, if present.
                          // 8. Overlay the current 856 subfield z so it reads "An electronic book accessible through the World Wide Web. Click on the hyperlink for information and access to this electronic book." Don't forget the ending period and the quotation marks are of course not necessary.  
                          
                          // Delete all existing 856
                          $tag_856 = $marc->getFields('856');
                          foreach ($tag_856 as $field) {
                              $field->delete();
                          }
                          
                          // OverDrive 856 subfields
                          $subfields = array();
                          $subfields[] = new File_MARC_Subfield('u', $record['url']);
                          $subfields[] = new File_MARC_Subfield('y', 'Click here for information and access to this electronic book');
                          //$subfields[] = new File_MARC_Subfield('z', 'An electronic book accessible through the World Wide Web.');
                          $subfields[] = new File_MARC_Subfield('z', "You will be leaving Spokane Public Library's web site.");
                          // OverDrive 856 field
                          $marc->appendField(new File_MARC_Data_Field('856', $subfields, 4, null)
                          );
                          /*
                          // Add OverDrive 856
                          $marc->appendField(new File_MARC_Data_Field('856', array(
                              new File_MARC_Subfield('u', $record['url']),
                              ), 4, null
                          ));
                          $marc->appendField(new File_MARC_Data_Field('856', array(
                              new File_MARC_Subfield('y', 'Click here for information and access to this electronic book'),
                              ), null, null
                          ));
                          $marc->appendField(new File_MARC_Data_Field('856', array(
                              new File_MARC_Subfield('z', 'An electronic book accessible through the World Wide Web.'),
                              ), null, null
                          ));
                          */
                          
                          // 9. If the library owns a print version of the same title/edition check that the subject headings on the e-book MARC record match the subject headings on print version. Edit, add, or subtract subject headings as necessary to provide agreement.
                          // Note: We're skipping this for now. -sg
                          
                          // 10. If the title is a Cliffs Notes title, follow the detailed instructions contained in Cataloging - Cliffs Notes. If the library owns a print version of the same Cliffs Notes title/edition check that the subject headings on the e-book MARC record match the subject headings on print version. Edit, add, or subtract subject headings as necessary to provide agreement. 
                          // Note: We're skipping this for now. -sg
                          
                          // 11. Make sure that the 008 Form = 's'. 
                          // ToDo: !!!!!THIS!!!!!!
                          // Note: I don't think PEAR::File_Marc 
                          // has enough granularity to reliably patch in a control value
                          /*
                          //$ctrl_field = new File_MARC_Control_Field('001', '01234567890');
                          $tag_008 = $marc->getFields('008');
                          foreach ($tag_008 as $field) {
                              $val = $field->getData();
                              $arr = explode(' ', $val);
                              trace($arr);
                          }
                          */
                          
                          
                          // 12. Remember to merge the on order bibliographic record, which in most cases will be a very brief record, but will have the necessary item(s) already attached. 
                          // Note: This is not necessary for these records. -sg
                          
                          
                          // push marc record on to collection stack
                          $this->marc[] = $marc;
                      } else {
                          // no record in OCLC
                          // ToDo: check isbn here if we looked up oclc# above?
                          // Note: that may require additional logic in sorting
                          $this->empty[] = $record;
                      }
                  } else {
                      // no response from OCLC
                      $this->empty[] = $record;
                  }
              }
              

              $i++;
          }
      }
      
  }
  
  // Collapse data into meta/error
  public function parseData() {
      $this->parseCSV();
      
      if ( is_array($this->data) ) {
          foreach ($this->data as $row) {
              if ( isset($this->meta[$row['id']]) ) {
                  // duplicate, but prioritize oclc control number
                  if ( empty($this->meta[$row['id']]['oclc']) ) {
                      if ( !empty($row['oclc']) ) {
                          $this->meta[$row['id']] = $row;
                      }
                  }
              } elseif ( !empty($row['isbn']) ) {
                  $this->meta[$row['id']] = $row;
              } elseif( !empty($row['oclc']) ) {
                  $this->meta[$row['id']] = $row;
              } else {
                  $this->error[$row['id']] = $row;
              }
          }
      }
  }
  
  // Convert CSV file into data structure
  public function parseCSV() {
      if ( $this->csv && $this->configAPI['overdrive']['map'] ) {
          $row = 1;
          if ( ($handle = fopen($this->upload.$this->csv, "r")) !== FALSE ) {
              while ( ($data = fgetcsv($handle, 0, ",")) !== FALSE ) {
                  // Get column numbers from column headings (in case they change)
                  if ( 1 == $row) {
                      foreach($data as $k=>$v) {
                          if ( $this->configAPI['overdrive']['map']['url'] == $v ) {
                              $this->keys['url'] = $k;
                          }
                          if ( $this->configAPI['overdrive']['map']['isbn'] == $v ) {
                              $this->keys['isbn'] = $k;
                          }
                          if ( $this->configAPI['overdrive']['map']['oclc'] == $v ) {
                              $this->keys['oclc'] = $k;
                          }
                          if ( $this->configAPI['overdrive']['map']['title'] == $v ) {
                              $this->keys['title'] = $k;
                          }
                      }
                  } else {
                      // Parse out essential data from csv file
                      $meta = array();
                      
                      $meta['row'] = $row;
                      $meta['id'] = $this->getOverDriveId($data[$this->keys['url']]);
                      $meta['url'] = $data[$this->keys['url']];
                      $meta['isbn'] = $data[$this->keys['isbn']] ? : null;
                      $meta['oclc'] = $data[$this->keys['oclc']] ? : null;
                      $meta['title'] = $data[$this->keys['title']]; 
                      
                      $this->data[] = $meta;
                      
                  }
                  $row++;
              }
              fclose($handle);
          }
      }
  }
  
  // Returns unique identifier from OverDrive URL
  // So we can collapse multiple 'copies'/formats
  // into single bib records.
  public function getOverDriveId($url) {
      return str_replace($this->configAPI['overdrive']['url'],null,$url);
  }


}

?>