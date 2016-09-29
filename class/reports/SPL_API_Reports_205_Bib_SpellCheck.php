<?php

class SPL_API_Reports_205_Bib_SpellCheck extends SPL_API_Reports {


  var $csv = '/var/web/---/csv/spl-misspell.csv';

	public function getReportData() {

    set_time_limit(60*60); 

		$report = new stdClass();

		$report->params = $this->params['vals'];

    if ( $this->params['vals']['chars'] ) {
      $report->chars = $this->getCharSets();
    }

    if ( !empty($this->params['vals']['term']) ) {
      $this->terms = $this->setTerm($this->params['vals']['term']);
      //$report->sorted = $this->getSearchTerm($this->params['vals']['term']);
    } elseif ( !empty($this->params['vals']['list']) ) {
      $this->terms = $this->setTerms($this->params['vals']['list']);
    }

    if ( $this->terms ) {
      $report->terms = $this->terms;
      $sorted = $this->getSearchTerms();
      if ( $sorted ) {
        $report->sorted = $sorted;
      }
    }


    return $report;
	}

  protected function getSearchTerms() {

    foreach ( $this->terms as $term ) {
      
      if ( strpos($term['match'], '*') ) {
        $match = str_replace('*','',$term['match']);
      } else {
        $match = $term['match'].' ';
      }

      $search = $this->getSearchTerm($match);

      if ( $search ) {
        foreach ( $search as $marc ) {
          $result = array();
          $result['note'] = $term['note'];
          $result['match'] = trim($term['match']);
                    
          $result['text'] = str_ireplace(
                              str_replace('*','',$term['match'])
                                          , '<span class="text-danger">~~~~~></span>'.strtoupper($term['match']).'<span class="text-danger"><~~~~~</span>'
                                          , $marc['text']
                                        );
          $result['text'] = utf8_encode($result['text']);
          
          $result['bib'] = $marc['bib#'];
          $result['tag'] = $marc['tag'];
          $results[] = $result;
        }
      }
      
      // try to avoid locking up the db
      $second = 1000000; 
      usleep($second * .25);
    }
    //return $results;
    if ( !empty($results) ) {
      return array('match' => $results
                  ,'count' => count($results));
    }
  }

  protected function getSearchTerm($term) {
    // need to exlude marc subfield indicators
    // but this NOT LIKE exclusion is VERY SLOW
    $subfield = strtolower(substr($term,0,1));
    $stripped = substr($term, 1);
    
    $params = array( ':match'     => '% '.$term.'%'
                    ,':subfield'  => $subfield
                    ,':stripped'  => $stripped.'%'
                    );

    $sql = "EXEC spl_marc_spellcheck :match, :subfield, :stripped";
       
    return $this->getQuery($sql, $params); 
  }

  protected function setTerm($match) {
    $terms = array();
    
    $note = $this->findText('[',']',$match);
    if ( !$note ) {
      $note = null;
    }
    $term['note'] = $note;
    $term['match'] = trim(str_replace('['.$note.']','',$match));
    
    $terms[] = $term;
    
    return $terms;
  }

  protected function setTerms($match) {
    $this->limit = $this->params['vals']['list'];

    $terms = array();
    if (($handle = fopen($this->csv, "r")) !== FALSE) {
      $i = 1;
      while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
        $use = false;
        $term = null;
        $term = array();
        $query = trim($data[0]);
        $first = trim(strtolower(substr($query,0,1)));
        $second = trim(strtolower(substr($query,0,2)));
        //var_export($this->limit);
        
        if ( 'chars' !== $this->limit ) {
          //$limit = $this->limit;
          /*
          if ( 1==$i ) {
            var_export($limit);
          }
          */
          if ( is_string($this->limit) ) {
            $limit = strtolower($this->limit);
          } else {
            $limit = $this->limit;
          }
          //var_export($second);
          if ( $limit == $first ) {
            $use = true;
          }
          if ( $limit == $second ) {
            $use = true;
          }
          
          
        } else {
          
          $alpha = range('a','z');
          $numeric = array('0','1','2','3','4','5','6','7','8','9');
          if ( !in_array($first, $alpha) 
            && !in_array($first, $numeric, true) ) {
            $use = true;
          }
          
        }
        
        if ( $use ) {
          $note = $this->findText('[',']',$query);

          $term['note'] = $note;
          $term['match'] = trim(str_replace('['.$note.']','',$query));
          
          $terms[] = $term;
        }
        
        $i++;
      }
      fclose($handle);
    }
    //var_export($terms);
    return $terms;
  }

  protected function findText($start_limiter, $end_limiter, $haystack) {
    $start_pos = strpos($haystack, $start_limiter);
    if ($start_pos === FALSE) {
      return FALSE;
    }
    $end_pos = strpos($haystack, $end_limiter, $start_pos);
    if ($end_pos === FALSE) {
      return FALSE;
    }

    return substr( $haystack, $start_pos+1, ($end_pos-1)-$start_pos );
  }

  protected function getCharSets() {
    $chars = '';
    $chars .= '<option value="">Select...</option>'.PHP_EOL;
    $chars .= '<option value="chars">$#@!...</option>'.PHP_EOL;
    $alpha = range('A','Z');
    $numeric = range(0,9);
    foreach ( $numeric as $int ) {
      $chars .= '<option value="'.$int.'">'.$int.'</option>'.PHP_EOL;
    }
    foreach ( $alpha as $char ) {
      $chars .= '<option value="'.$char.'">'.$char.'</option>'.PHP_EOL;
    }
    
    $extend = array('A', 'C', 'P', 'S');
    
    foreach ( $extend as $letter ) {
      $chars .= '<optgroup label="'.$letter.' List">'.PHP_EOL;
      foreach ( $alpha as $char ) {
        $chars .= '<option value="'.$letter.$char.'">'.$letter.$char.'</option>'.PHP_EOL;
      }
      $chars .= '</optgroup>';
    }

    return $chars;     
  }


}

?>