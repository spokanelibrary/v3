<?php
//error_reporting(E_ALL); 
//ini_set( 'display_errors','1');

function __autoloader($class) {
    include $class . '.php';
}
spl_autoload_register('__autoloader');

function crass_init() {
    // Private Key. Please change me! 
    // This is required.
    $init['key'] = getenv('SPL_CRASS');
    
    // Crass will attempt to call $callback from validate()
    // passing the validate() status code
    // as well as user-defined parameters (params) + config
    $init['callback']['function']   = 'crass_callback';
    // Add any additinal parameters here
    $init['callback']['params']     = array();

    // Configuration - This is optional (will override defaults).
    $init['config']['debug']        = false;
    $init['config']['algorithm']    = 'SHA256';   // crypto arg passed to hash_hmac
    $init['config']['minTimeout']   = 5;          // seconds
    $init['config']['maxTimeout']   = 60;         // minutes
    $init['config']['useSessions']  = true;       // use session-based one-time keys?
    $init['config']['useHoneypot']  = true;       // check honeypot form field (false to ignore)
    $init['config']['sessionKeys']  = 'crass-response-keys';    // $_SESSION array key to store one-time hashes

    return $init;
}

// Init. Crass Response
$crass = new Crass(crass_init());

// Validate and handle form submission, if present
if ( $crass && !empty($_REQUEST['crass-response']) ) {
    $crass->validate($_REQUEST['crass-response']);
}

?>