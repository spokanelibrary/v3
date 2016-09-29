<?php

/**
 *
 * Crass_Response
 * 
 * A front controller to handle
 * all Crass form submissions. It
 * auto-runs after form validation.
 *
 * This class is meant to be customized.
 *
 * Use it
 *
 */

class Crass_Response extends Crass_Handler {

  var $request;

  protected function success() {
    $this->controller();
  }

  protected function error() {

  }
  
  protected function setRequest($request) {
    if ( isset($request) ) {
      $this->request = $request;
    }
  }
  
  protected function controller() {

  }

}


?>