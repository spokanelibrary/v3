<?php

class SPL_API_Reports_153_SS_Stats_Sum_Monthly extends SPL_API_Reports {

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();

    //$report = $this->getSampleData();
    
    /*
    $meta = array();
    $dt = new DateTime();
    
    $meta['date']['stamp'] = $dt->format('F, d Y - h:i A');      
    $begin_month = 16832;
    $meta['date']['begin_month'] = $this->getDateFromEpoch(++$begin_month);
    //$meta['date']['begin_year'] = $this->getDateFromEpoch(++16801);
    //$meta['date']['end'] = $this->getDateFromEpoch(16861);

    $report->test = $meta;
    */
    //return $report;
    
    $data = $this->getLegacyData();

    if ( $data ) {
      $report->sorted = $data[0];

      $meta = array();
      $dt = new DateTime();
      $meta['date']['stamp'] = $dt->format('F, d Y - h:i A');      
      $meta['date']['begin_month'] = $this->getDateFromEpoch(++$report->sorted['date_month_begin']);
      $meta['date']['begin_year'] = $this->getDateFromEpoch(++$report->sorted['date_year_begin']);
      $meta['date']['end'] = $this->getDateFromEpoch($report->sorted['date_report_end']);

      $report->meta = $meta;
    }

    return $report;
	}

  
  protected function getLegacyData() {
    $date_month = new DateTime($this->params['vals']['datebegin']);
    $date_month->modify('first day of this month');
    $date = $date_month->format('m/d/Y');

    $params = array(':date'=>$date);
    $sql = "EXECUTE usp_153_support_services_stats_sum_monthly_sg :date";   

    $result = $this->getQuery($sql, $params); 

    return $result;
  }
  

  protected function getSampleData() {
    return json_decode('{"meta":{"date":{"stamp":"March, 02 2016 - 07:45 AM","begin_month":"02-01-2016","begin_year":"01-01-2016"
,"end":"02-29-2016"}},"sorted":{"query_stamp":"Mar  2 2016 07:45:35:887AM","date_month_begin":16833,"date_year_begin"
:16802,"date_report_end":"16861","bib_ebook":"15","bib_ytd_ebook":"59","bib_dlaudio":"97","bib_ytd_dlaudio"
:"151","bib_sub_elec":"112","bib_ytd_sub_elec":"210","bib_total_elec":"1341","bib_ytd_total_elec":"2448"
,"bib_print":"1057","bib_printa":"831","bib_printj":"150","bib_printya":"76","bib_sc":"0","bib_sca":"0"
,"bib_scj":"0","bib_scya":"0","bib_spcd":"31","bib_spcda":"31","bib_spcdj":"0","bib_spcdya":"0","bib_music"
:"81","bib_musica":"81","bib_musicj":"0","bib_musicya":"0","bib_dvd":"60","bib_dvda":"60","bib_dvdj"
:"0","bib_dvdya":"0","bib_sub_print":"1057","bib_sub_printa":"831","bib_sub_printj":"150","bib_sub_printya"
:"76","bib_sub_nonprint":"172","bib_sub_nonprinta":"172","bib_sub_nonprintj":"0","bib_sub_nonprintya"
:"0","bib_total":"1229","bib_totala":"1003","bib_totalj":"150","bib_totalya":"76","bib_ytd_print":"1929"
,"bib_ytd_printa":"1493","bib_ytd_printj":"303","bib_ytd_printya":"133","bib_ytd_sc":"19","bib_ytd_sca"
:"19","bib_ytd_scj":"0","bib_ytd_scya":"0","bib_ytd_spcd":"46","bib_ytd_spcda":"45","bib_ytd_spcdj":"1"
,"bib_ytd_spcdya":"0","bib_ytd_music":"140","bib_ytd_musica":"140","bib_ytd_musicj":"0","bib_ytd_musicya"
:"0","bib_ytd_dvd":"104","bib_ytd_dvda":"98","bib_ytd_dvdj":"6","bib_ytd_dvdya":"0","bib_ytd_sub_print"
:"1948","bib_ytd_sub_printa":"1512","bib_ytd_sub_printj":"303","bib_ytd_sub_printya":"133","bib_ytd_sub_nonprint"
:"290","bib_ytd_sub_nonprinta":"283","bib_ytd_sub_nonprintj":"7","bib_ytd_sub_nonprintya":"0","bib_ytd_total"
:"2238","bib_ytd_totala":"1795","bib_ytd_totalj":"310","bib_ytd_totalya":"133","item_print":"3514","item_printa"
:"2497","item_printj":"750","item_printya":"267","item_sc":"1","item_sca":"0","item_scj":"1","item_scya"
:"0","item_spcd":"41","item_spcda":"40","item_spcdj":"1","item_spcdya":"0","item_music":"157","item_musica"
:"157","item_musicj":"0","item_musicya":"0","item_dvd":"365","item_dvda":"364","item_dvdj":"1","item_dvdya"
:"0","item_sub_print":"3515","item_sub_printa":"2497","item_sub_printj":"751","item_sub_printya":"267"
,"item_sub_nonprint":"563","item_sub_nonprinta":"561","item_sub_nonprintj":"2","item_sub_nonprintya"
:"0","item_total":"4078","item_totala":"3058","item_totalj":"753","item_totalya":"267","item_ytd_print"
:"6165","item_ytd_printa":"4143","item_ytd_printj":"1585","item_ytd_printya":"437","item_ytd_sc":"138"
,"item_ytd_sca":"0","item_ytd_scj":"138","item_ytd_scya":"0","item_ytd_spcd":"65","item_ytd_spcda":"60"
,"item_ytd_spcdj":"5","item_ytd_spcdya":"0","item_ytd_music":"268","item_ytd_musica":"268","item_ytd_musicj"
:"0","item_ytd_musicya":"0","item_ytd_dvd":"653","item_ytd_dvda":"588","item_ytd_dvdj":"65","item_ytd_dvdya"
:"0","item_ytd_sub_print":"6303","item_ytd_sub_printa":"4143","item_ytd_sub_printj":"1723","item_ytd_sub_printya"
:"437","item_ytd_sub_nonprint":"986","item_ytd_sub_nonprinta":"916","item_ytd_sub_nonprintj":"70","item_ytd_sub_nonprintya"
:"0","item_ytd_total":"7289","item_ytd_totala":"5059","item_ytd_totalj":"1793","item_ytd_totalya":"437"
},"summary":[]}');
  }

}

?>