<?php

class SPL_API_Reports_204_OneClick_Records extends SPL_API_Reports {

  var $keys;

  var $upload = '/var/web/---/upload/';

  var $input = 'oneclick-input.mrc';
  var $output = 'oneclick.mrc';
  
	public function getReportData() {

    require 'File/MARC.php';
    require 'File/MARCXML.php';

    $path = '/var/web/---/php/PHPExcel-1.8/Classes';
    set_include_path(get_include_path() . PATH_SEPARATOR . $path);
    require 'PHPExcel.php';

		$report = new stdClass();

		$report->params = $this->params;

    if ( $this->processUpload() ) {
      
      $this->getRecords();
      
      if ( $this->records ) {

        $this->writeRecords($this->upload.$this->output);

        $i = 1;
        while ( $record = $this->marcout->next() ) {
          $marc = array('id'=>$i, 'marc'=>trim(utf8_encode($record->__toString())));
          //$marc = array('id'=>$i, 'marc'=>trim($record->toRaw()));
          $report->sorted->records[] = $marc;
          $i++;
        }
        
      }
      
    }

    return $report;
	}


  protected function processUpload() {
    // Note: SPL_Report uploads are saved to a scratch space
    if ( UPLOAD_ERR_OK == $this->params['files']['metadata']['error'] ) {
      if ( stristr($this->params['files']['metadata']['name'], '.mrc') == TRUE ) {
        $upload = rename($this->upload.$this->params['files']['metadata']['tmp_name']
                        ,$this->upload.$this->input);
        //clearstatcache();
      } else {
        unlink($this->upload.$this->params['files']['metadata']['tmp_name']);
      }
    }
    
    return $upload;
  }

  // Output MARC21 file
    public function writeRecords($file) {
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

  protected function getRecords() {
    $this->parseRecordsFromFile($this->upload.$this->input);
    
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

  protected function parseRecordsFromFile($input) {
    // Retrieve MARC records from uploaded file
    $records = new File_MARC($input, File_MARC::SOURCE_FILE);
    
    $ouput = array();
    
    // Iterate through the retrieved records
    while ($marc = $records->next()) {
        
      // 1. Add an 092 of DOWNLOADABLE AUDIO. 
      $marc->appendField(new File_MARC_Data_Field('092', array(
          new File_MARC_Subfield('a', 'DOWNLOADABLE AUDIO'),
          ), null, null
      ));
      
      // 2. If present, delete 655 field: "Audiobooks." 
      $tag_655 = $marc->getFields('655');
      foreach ($tag_655 as $field) { 
          $subfields = $field->getSubfields();
          if ( $subfields ) {
              foreach ($subfields as $subfield) {
                  $content = $subfield->getData();
                  if(stristr($content, 'Audiobooks.') == TRUE) {
                      $field->delete();
                  }
                  // Go ahead and delete in advance of step #4
                  if(stristr($content, 'Downloadable audio books.') == TRUE) {
                      $field->delete();
                  }
              }
          }
      }
      
      // 3. If present, delete 650 field: "Audiobooks." 
      $tag_650 = $marc->getFields('650');
      foreach ($tag_650 as $field) { 
          $subfields = $field->getSubfields();
          if ( $subfields ) {
              foreach ($subfields as $subfield) {
                  $content = $subfield->getData();
                  if(stristr($content, 'Audiobooks.') == TRUE) {
                      $field->delete();
                  }
              }
          }
      }
      
      
      // 4. Make sure that the local subject heading for electronic books looks like this:
      // 655 7 $aElectronic books.$2local 
      
      // 655 subfields
      $subfields = array();
      $subfields[] = new File_MARC_Subfield('a', 'Downloadable audio books.');
      $subfields[] = new File_MARC_Subfield('2', 'local');
      // 655 field
      $marc->appendField(new File_MARC_Data_Field('655', $subfields, null, 7)
      );

      // 5. Remove and BISAC headings from the 650 fields. 

      // Note: Rob is performing step #5 at import time

      // 6. Change 245 h from 'electronic resource' to 'downloadable audiobook'
      // 7. If present, change 245 h from 'sound recording' to 'downloadable audiobook'
      
      // First drop any 245/h that might exist
      /*
      $tag_245 = $marc->getFields('245');
      foreach ($tag_245 as $field) {
          $h = $field->getSubfields('h');
          foreach ($h as $sub) {
              $sub->delete();
          }
      }
      // Next build and add our own 245/h
      $subfields = array();
      $subfields[] = new File_MARC_Subfield('h', 'downloadable audiobook');
      $marc->appendField(new File_MARC_Data_Field('245', $subfields, null, null)
      );
      */
      $tag_245 = $marc->getFields('245');
      foreach ($tag_245 as $field) {
          $h = $field->getSubfields('h');
          foreach ($h as $sub) {
              // should only be 1 'h', but could delete them all and recreate
              //$sub->delete();
              $sub->setData('[downloadable audiobook]');
          }
      }
      
      
      
      // 8. Add an 856, subfield "y" note of "Click here to access this downloadable audio book."
      $tag_856 = $marc->getFields('856');
      $i = 0; // RB is now putting in a second 856 link to cover image, we only want the first 856
      foreach ($tag_856 as $field) {
          if (0 == $i) {
              $u = $field->getSubfields('u');
              foreach ($u as $sub) {
                  $url = $sub; 
              }
              $i++;
          }
          $field->delete();
      }
      
      $subfields = array();
      $subfields[] = $url; // we pulled this from preexisting 856 before nuking it
      $subfields[] = new File_MARC_Subfield('y', 'Click here to access this downloadable audio book.');
      $subfields[] = new File_MARC_Subfield('z', "You will be leaving Spokane Public Library's web site.");
      
      $marc->appendField( new File_MARC_Data_Field('856', $subfields, 4, '0') );
      
      $output[] = $marc;
      
    }
    
    $this->marc = $output;
  }



}

?>