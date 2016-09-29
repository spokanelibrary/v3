<?php

class SPL_API_Reports_107_Summer_Reading_Signup extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();

		$date = new DateTime('first day of January ' . date('Y'));
		//$date->sub(new DateInterval('P1Y'));

		$meta['date'] = $date->format('Y-m-d');

		$report->meta = $meta;

		$params= array(':date'=>$meta['date']);

		$sql = "SELECT 
                COUNT(spl_summer_reading_signup.id) as count
                FROM spl_summer_reading_signup
                WHERE date > :date
						
					";
		$result = $this->getQuery($sql, $params);	

		$report->sorted->totals->total = $result[0];

		$sql = "SELECT 
                COUNT(spl_summer_reading_signup.id) as count
                ,spl_summer_reading_signup.branch
                ,location.name
                FROM spl_summer_reading_signup
                LEFT OUTER JOIN location
                	ON location.location = spl_summer_reading_signup.branch
                WHERE date > :date
                AND LTRIM(RTRIM(branch)) != ''
                GROUP BY branch, location.name
                ORDER by branch 
						
					";
		$result = $this->getQuery($sql, $params);		

		$report->sorted->totals->locations = $result;

		$sql = "SELECT 
                *
                FROM spl_summer_reading_signup
                WHERE date > :date
						
					";
		$result = $this->getQuery($sql, $params);


    $report->sorted->detail = $result;

    return $report;
	}

}

?>