<?php

// Base SPL utility methods
abstract class SPL {
    
    /*
    var $trigger;
    protected   $dateformat = 'Y-m-d H:i';
    
    
    public function output($obj) {
      //header('Content-Type: text/javascript; charset=utf-8'); 
      if ( $obj ) {
        json_echo($obj);
      } else {
        json_echo($this->raiseError('empty'));
      }
    }
    */
    protected function raiseError($msg=null) {
      return array('error' => $msg);
    }
    

    // most of the stuff below here is obsolete
    
    /*
     *
     * STRING METHODS
     *
     */
    
    /*
    public function getCurrency($str) {
        return money_format('%i', $str);
    } 
    
    protected function encodeString($str) {
        $encode = mb_convert_encoding($str, 'HTML-ENTITIES');
        return $encode;
    }
    */

    /*
     *
     * DATE & TIME METHODS
     *
     */
    
    
    // Get formatted date from string (defaults to now)
    public function getDateFormat($date=null, $format=null) {
		$dt = new DateTime($date);
    
    if ( !$format ) {
      $format = $this->dateformat;
    }

		return $dt->format($format);
	}
    
    /*
    // Get formatted date from days since UNIX epoch
    protected function getEpochDate($days) {
        $dt = new DateTime('01/01/1970 + '.$days.' days');
        
        return $dt->format($this->dateformat);
    }

    // Support older versions of php without date diff methods
    // ToDo: nuke this and rewrite when production server upgraded
    protected function getIntervalHours($begin, $finish) {
        $interval = null;
        $divisor = 60*60;
        if ($begin && $finish) {
            $interval = round(abs(strtotime($begin) - strtotime($finish)) / $divisor,2);
        }
        
        return $interval;
    }
    */

    /*
     *
     * Determine whether an expiry date is prior to now
     * 
     * Returns true: borrower expired
     * Returns false: borrower not expired
     *
     */
    /*
    protected function isBorrowerExpired($expiry=null, $format='m/d/Y') {
        $expired = true;
        if ( $expiry ) {
            $now = new DateTime();
            $then = new DateTime($expiry);
            
            $diff = strtotime($now->format($format)) - strtotime($then->format($format));
            
        
            if ( $diff <= 0 ) {
                $expired = false;
            }
        }
        return $expired;
    }
    */
    
}

?>