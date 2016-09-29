<?php

class SPL_API_Reports_202_CHOF_Nominate extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();

		$meta = $this->params['vals'];
    $report->meta = $meta;
    
    $this->pdo = new PDO($this->config['api']['connect']
                        ,$this->config['api']['web_user']
                        ,$this->config['api']['web_pass']
                        );

    $params = array(':id'=>$this->params['vals']['chof']);
    $sql = "SELECT
            *
            FROM spl_foundation_chof_nominate
            WHERE id = :id
            ";

    $result = $this->getQuery($sql, $params); 

    $report->sorted->detail = $this->formatNomination($result[0]);
    
    return $report;
	}

  protected function formatNomination($nominate) {
    $nominate['submitter_essay'] = utf8_encode($nominate['submitter_essay']);
    $nominate['submitter_essay'] = nl2br($nominate['submitter_essay']);
    $nominate['submitter_essay'] = str_replace('â??', "'", $nominate['submitter_essay']);
    //$nominate['submitter_essay'] = utf8_encode($nominate['submitter_essay']);
    //$nominate['submitter_essay'] = iconv("UTF-8", "ISO-8859-1", $nominate['submitter_essay']);

    $nominate['nominee_category'] = str_replace('|', '<br>', $nominate['nominee_category']);
    

    return $nominate;
  }

}

?>