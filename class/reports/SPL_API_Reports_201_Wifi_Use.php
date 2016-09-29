<?php

class SPL_API_Reports_201_WiFi_Use extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();

		$meta = $this->params['vals'];
    $report->meta = $meta;

    $this->pdo = new PDO($this->config['api']['radius']
                        ,$this->config['api']['rd_user']
                        ,$this->config['api']['rd_pass']
                        );

    $this->setBranchName();
    $this->setDateRange();
    
    $report->sorted->range = $this->range;

    // active
    $params= array();
    $report->sorted->detail->active = $this->getRadiusActiveSummaryData($params);
    
    // today
    $params = array(':begin'=>$this->range['today'],
                    ':finish'=>$this->range['tomorrow']);
    $report->sorted->detail->today->unique = $this->getRadiusUniqueSummaryData($params);
    $report->sorted->detail->today->session = $this->getRadiusSessionSummaryData($params);

    // yesterday
    $params = array(':begin'=>$this->range['yesterday'],
                    ':finish'=>$this->range['today']
                  );
    $report->sorted->detail->yesterday->unique = $this->getRadiusUniqueSummaryData($params);
    $report->sorted->detail->yesterday->session = $this->getRadiusSessionSummaryData($params);

    // this month
    $params = array(':begin'=>$this->range['thismonth'],
                    ':finish'=>$this->range['nextmonth']
                  );
    $report->sorted->detail->thismonth->unique = $this->getRadiusUniqueSummaryData($params);
    $report->sorted->detail->thismonth->session = $this->getRadiusSessionSummaryData($params);

    // custom date range
    $params= array(':begin'=>$this->params['vals']['datebegin'],
                   ':finish'=>$this->params['vals']['datefinish']);
    $report->sorted->detail->daterange->unique = $this->getRadiusUniqueSummaryData($params);
    $report->sorted->detail->daterange->session = $this->getRadiusSessionSummaryData($params);



    return $report;
	}

  protected function setBranchName() {
    $this->branch = array(
                        'dt'=>'Downtown',
                        'es'=>'East Side',
                        'hy'=>'Hillyard',
                        'it'=>'Indian Trail',
                        'sh'=>'Shadle',
                        'so'=>'South Hill'
                        );
  }

  protected function setDateRange() {
    $range = array();
        
    $now = new DateTime('now');
    $today = new DateTime('today');
    $tomorrow = new DateTime('tomorrow');
    $yesterday = new DateTime('yesterday');
    $lastmonth = new DateTime('first day of last month 12am');
    $thismonth = new DateTime('first day of this month 12am');
    $nextmonth = new DateTime('first day of next month 12am');

    $range['now'] = $now->format('Y-m-d H:i:s');
    $range['today'] = $today->format('Y-m-d');
    $range['tomorrow'] = $tomorrow->format('Y-m-d');
    $range['yesterday'] = $yesterday->format('Y-m-d');
    $range['lastmonth'] = $lastmonth->format('Y-m-d');
    $range['thismonth'] = $thismonth->format('Y-m-d');
    $range['nextmonth'] = $nextmonth->format('Y-m-d');

    $range['datebegin'] = $this->params['vals']['datebegin'];
    $range['datefinish'] = $this->params['vals']['datefinish'];
    
    $this->range = $range;
  }

  protected function sortRadiusSummary($sort,$range=null) {
        $summary = array();
        if ( is_array($sort) ) {
            $summary['range'] = $range;

            foreach ( $sort as $stat ) {
                
                switch ($stat['username']) {
                    case 'dt':
                    case'spl':
                            $summary['branch']['dt']['non-mobile'] += $stat['count'];
                            $summary['branch']['dt']['count'] += $stat['count'];
                        break;
                    case 'dt_mobile':
                    case'spl_mobile':
                            $summary['branch']['dt']['mobile'] += $stat['count'];
                            $summary['branch']['dt']['count'] += $stat['count'];
                        break;
                    
                    case 'es':
                            $summary['branch']['es']['non-mobile'] += $stat['count'];
                            $summary['branch']['es']['count'] += $stat['count'];
                        break;
                    case 'es_mobile':
                            $summary['branch']['es']['mobile'] += $stat['count'];
                            $summary['branch']['es']['count'] += $stat['count'];
                        break;
                        
                    case 'hy':
                            $summary['branch']['hy']['non-mobile'] += $stat['count'];
                            $summary['branch']['hy']['count'] += $stat['count'];
                        break;
                    case 'hy_mobile':
                            $summary['branch']['hy']['mobile'] += $stat['count'];
                            $summary['branch']['hy']['count'] += $stat['count'];
                        break;
                        
                    case 'it':
                            $summary['branch']['it']['non-mobile'] += $stat['count'];
                            $summary['branch']['it']['count'] += $stat['count'];
                        break;
                    case 'it_mobile':
                            $summary['branch']['it']['mobile'] += $stat['count'];
                            $summary['branch']['it']['count'] += $stat['count'];
                        break;
                        
                    case 'sh':
                            $summary['branch']['sh']['non-mobile'] += $stat['count'];
                            $summary['branch']['sh']['count'] += $stat['count'];
                        break;
                    case 'sh_mobile':
                            $summary['branch']['sh']['mobile'] += $stat['count'];
                            $summary['branch']['sh']['count'] += $stat['count'];
                        break;
                        
                    case 'so':
                            $summary['branch']['so']['non-mobile'] += $stat['count'];
                            $summary['branch']['so']['count'] += $stat['count'];
                        break;
                    case 'so_mobile':
                            $summary['branch']['so']['mobile'] += $stat['count'];
                            $summary['branch']['so']['count'] += $stat['count'];
                        break;
                
                }
                
                if ( stristr($stat['username'], '_mobile') == TRUE ) {
                    $summary['total']['mobile'] += $stat['count'];
                } else {
                    $summary['total']['non-mobile'] += $stat['count'];
                }
                
                $summary['total']['count'] += $stat['count'];
                $summary['total']['label'] = 'All Branches';
            }
            
            foreach ($this->branch as $k=>$v) {
                if (!is_array($summary['branch'][$k])) {
                    $summary['branch'][$k]['non-mobile'] = 0;
                    $summary['branch'][$k]['mobile'] = 0;
                    $summary['branch'][$k]['count'] = 0;
                    $summary['branch'][$k]['label'] = $this->branch[$k];
                } else {
                    $summary['branch'][$k]['non-mobile'] = ($summary['branch'][$k]['non-mobile']) ? : 0; 
                    $summary['branch'][$k]['mobile'] = ($summary['branch'][$k]['mobile']) ? : 0; 
                    $summary['branch'][$k]['count'] = ($summary['branch'][$k]['count']) ? : 0; 
                    $summary['branch'][$k]['label'] = $this->branch[$k];
                }
            }
            
            ksort($summary['branch']);

            $summary['branch'] = array_values($summary['branch']);
            
            if ( !is_array($summary['total']) ) {
                $summary['total']['mobile'] = 0;
                $summary['total']['non-mobile'] = 0;
                $summary['total']['count'] = 0;
            } else {
                foreach ( $summary['total'] as $k=>$v ) {
                    if ( empty($v) ) {
                        $summary['total'][$k] = 0;
                    }
                }
            }
        
        }
        
        return $summary;
    }

  protected function getRadiusActiveSummaryData($params) {
    
    $sql = "SELECT
            username
            ,count
            FROM
            (
                SELECT
                username,
                COUNT(DISTINCT callingstationid) AS count
                FROM radacct
                WHERE 
                        acctstoptime IS NULL
                GROUP BY username
            ) AS total";

    $result = $this->getQuery($sql, $params); 

    return $this->sortRadiusSummary($result, $params);
    return $result;
  }

  protected function getRadiusUniqueSummaryData($params) {
    
    $sql = "SELECT
            username
            ,count
            FROM
            (
                SELECT
                username,
                COUNT(DISTINCT callingstationid) AS count
                FROM radacct
                WHERE 
                        acctstarttime > :begin
                    AND acctstarttime < :finish
                GROUP BY username
            ) AS total";

    $result = $this->getQuery($sql, $params); 
    return $this->sortRadiusSummary($result, $params);
    return $result;      
  }

  protected function getRadiusSessionSummaryData($params) {
        
    $sql = "SELECT 
            username
            ,SUM(count) AS count
            FROM(
            
                SELECT
                username
                ,SUM(count) AS count
                ,date
                FROM
                (
                    
                    SELECT
                    username
                    ,DATE_FORMAT(acctstarttime, '%m/%d/%Y') AS date
                    ,COUNT(DISTINCT callingstationid) AS count
                    FROM radacct
                    WHERE 
                            acctstarttime > :begin
                        AND acctstarttime < :finish
                    GROUP BY date, username
                
                ) AS sorted
                GROUP BY username, count
            
        ) AS total
        GROUP BY username";
  
    $result = $this->getQuery($sql, $params); 

    return $this->sortRadiusSummary($result, $params);
    return $result; 
  }

}

?>