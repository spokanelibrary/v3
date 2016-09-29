<?php

/*
 *
 * Warning:
 *  If you are editing this file then
 *  you are probably doing it wrong. Really.
 *
 */

##############JUST##STOP##HERE##############

/*
 *
 * crass_callback
 *
 * Auto-run by crass->validate()
 * always returns a Crass_Response object
 * which is then stored in crass->response
 *
 */

function crass_callback($status=null, $params=null) {
    $handler = null;
    if ( $status && $params ) {
        $handler = new Crass_Response($status, $params);

        if ( isset($params['debug']) && true == $params['debug'] ) {
            Crass::_debug($status, $params, __FUNCTION__);
        }
    }
    
    return $handler;
}

/**
 *
 * Crass_Handler
 * 
 * Stub class to handle
 * all Crass form submissions. 
 *
 * This class is extended by Crass_Response,
 * which is meant to be customized. Edit that.
 *
 */

class Crass_Handler {
    
    var $status;
    var $params;
    
    function __construct($status=null, $params=null) {
        if ( isset($status) && isset($params) ) {
            $this->status = $status;
            $this->params = $params;
            
            $this->controller();
        }
    }
    
    protected function controller() {
        switch ($this->status) {
            case 'success':
                $this->success();
                break;
            case 'empty':
                // no tokens supplied
            case 'replay':
                // $_SESSION token missing 
            case 'preempt':
                // form submitted too fast
            case 'expired':
                // form submitted too slow
            case 'honeypot':
                // non-empty honeypot value
            default:
                $this->error();
                break;
        }
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function getParams() {
        return $this->params;
    }
    
    public function addParam($key, $val) {
        if ( $key && $val ) {
            $this->params[$key] = $val;
        }
    }
    
    protected function success() {

    }
    
    protected function error() {
    
    }
    
}

/**
 *
 * Crass
 *
 * a Cryptographic Reaction to Automated SPAM Submission
 *
 * crass |kras|
 *   adjective
 *   lacking sensitivity, refinement, or intelligence 
 *   e.g. the crass assumptions that men make about women.
 *
 * @date: 10.09.12
 * @update: 03.28.12
 * @update: 10.07.11
 * @update: 09.26.11
 *
 * @author: Sean Girard
 * @twitter: seanmgirard
 *
 * @package WordPress
 * @subpackage SPL
 * @since SPL 1.0
 *
 * The basic idea is to force the client to ask permission
 * prior to submitting a form. We first supply the client with a signed
 * timestamp and require that the client returns a properly
 * signed timestamp and also that the timestamp itself
 * fall within an acceptable (and tuneable) time interval.
 *
 * In addition, Crass supports an optional off-screen honeypot 
 * as well as session-based key tracking to help ensure one-time use.
 *
 * This is based on some work I did around 2008, I think.
 * It seems to have been fairly effective in the intervening
 * years, although this is the first time I have tried using
 * the technique in WordPress which may be more of a spamtrap 
 * than Crass can handle.
 *
 * All we are doing is sending a hashed private key 
 * to the client using a UNIX timestamp as a salt.
 * 
 * The client returns those values during a form post.
 * 
 * If the timestamp (salt) plus private key hashes to the
 * supplied hash value (public key) then we can be reasonably 
 * sure the client hasn't spoofed a timestamp. 
 *
 * Next we check that the timestamp fits within a valid time interval
 * and if so we can safely process the form post.
 *
 * Simple.
 *
 * This doesn't do anything to prevent human-initiated spam;
 * it just makes things difficult to automate.
 * 
 * There is no need for, or guarantee of, hash uniqueness here.
 * i.e., we don't care if we get simultaneous form submissions;
 * we just want to check for a valid timestamp.
 * 
 * ToDo: detail honeypot and one-time details.
 * 
 * Using .js to retrieve the salt/hash further complicates automation
 * since most bots don't have a proper .js stack (last I knew).
 * No .js utilities are provided at this point but it is fairly simple
 * to roll your own. 
 *
 * ToDo: detail .js usage.
 *
 * Note: JSON retry responses should always check for an expired
 * but properly signed response, otherwise we will be providing a
 * simple api for bot automation.
 *
 * SHA-256 is the default with support for SHA-512. 
 * Ths modular system could (should) be extended to 
 * provide support for additional hashing algorithms.
 * See http://php.net/manual/en/function.crypt.php
 *
 * A little googling led me to see this is a case
 * of trusted timestamping. Neat.
 * http://en.wikipedia.org/wiki/Trusted_timestamping
 *
 * This guy had a similar idea:
 * http://www.ardamis.com/2011/08/27/a-cache-proof-method-for-reducing-comment-spam/
 * 
 * Also, this guy:
 * http://nedbatchelder.com/text/stopbots.html
 * 
 * Here's a link to a jQuery implementation
 * http://docs.jquery.com/Tutorials:Safer_Contact_Forms_Without_CAPTCHAs
 *
 * So, it ain't a new idea.
 * 
 * -sg 
 * 
 */



class Crass {

    // Minimum time interval (in seconds) between form rendering and form submission
    var $min;
    
    // Maximum time interval (in minutes) between form rendering and from submission
    var $max;
    
    // UNIX timestamp
    var $salt;
    
    // Array of optional configuration parameters
    var $config;
    
    // Hash value returned to client (Public Key)
    var $public;
    
    // Password to be hashed using timestamp as salt (Private Key)
    var $private;
    
    // Function and params called by validate()
    var $callback;
    
    // Response object as defined in Crass_Response
    var $response;
    
    // Supported options include SHA-256 (default), SHA-512, (anything supported by hash_hmac)
    var $algorithm;
    
    // Class constructor
    function __construct($params=null) {

        // Construction status
        $success = false;
        
        $key = $params['key'];
        $config = $params['config'];
        $callback = $params['callback'];
        
        // Without a private key, none of this will work
        if ( !is_null($key) ) { 
            // Generate timestamp salt
            $this->setSalt();
            
            // Default Configuration
            $default = array();
            $default['algorithm']      = 'SHA256'; // hash_hmac crypto
            $default['minTimeout']     = 3;        // seconds
            $default['maxTimeout']     = 120;      // minutes
            $default['useSessions']    = true;     // use session-based one-time keys?
            $default['useHoneypot']    = true;     // check honeypot form field (false to ignore)
            $default['sessionKeys']    = 'crass-response-keys';    // $_SESSION array key to store one-time hashes
            // Overlay user-supplied config
            if ( is_array($config) ) {
                $params = array_merge($default, $config);
            } else {
                $params = $default;
            }
            // Apply configuration parameters
            $this->setConfig($params);
            
            // Apply callback parameters
            $this->setCallback($callback);
            
            // Set timeouts
            $this->setMin();
            $this->setMax();
            
            // Set hash algorithm
            $this->setAlgorithm();
            
            // Init optional session
            $this->startSession();
            
            // Assign user-supplied private key
            $this->setPrivateKey($key);
            
            // Generate (hash) and store public key 
            $this->setPublicKey();
            
            // Check that we actually have a public key value
            if ($this->getPublicKey()) {
                $success = true;
            }
        }
        
        // Let user code know whether this routine seemed to work
        return $success;
        
    }
    
    public function setResponse($response) {
        if ( is_object($response) ) {
            $this->response = $response;
        }
    }
    
    public function setCallback($callback) {
        $this->callback = $callback;    
    }
    
    
    public function getMin() {
        return $this->min;
    }
    
    public function setMin($val=null) {
        if (is_null($val)) {
            $val = $this->getConfig('minTimeout');
        }
        $this->min = $val;
    }
    
    
    
    public function getMax() {
        return $this->max * 60;
    }
    
    public function setMax($val=null) {
        if (is_null($val)) {
            $val = $this->getConfig('maxTimeout');
        }
        $this->max = $val;
    }
    
    
    
    public function getSalt() {
        return $this->salt;
    }
    
    public function setSalt() {
        // Use UNIX timestamp as a salt
        $this->salt = time();
    }
    
    
    public function setConfig($params) {
        $this->config = $params;    
    }
    
    protected function getConfig($param=null) {
        // return entire array by default
        $config = $this->config;
        if (!is_null($param)) {
            // return specified value
            $config = $this->config[$param];
        }
        
        return $config;
    }
    
    
    
    public function getPublicKey() {
        $key = $this->public;
        // If using sessions, add this key
        // to the allowed list (should always be a list of 1)
        if ( $this->getConfig('useSessions') ) {
            $_SESSION[$this->getConfig('sessionKeys')][$this->getSalt()] = $key;
        }
        return $key;
    }
    
    protected function setPublicKey() {
        // Hash private key with timestamp/salt
        $this->public = $this->getKeyHash();
        
    }
    
    
    
    protected function getPrivateKey() {
        return $this->private;
    }
    
    public function setPrivateKey($key=null) {
        if (!empty($key)) {
            $this->private = $key;
        }
    }
    
    
    
    protected function getAlgorithm() {
        return $this->algorithm;
    }
    
    public function setAlgorithm($val=null) {
        if (is_null($val)) {
            $val = $this->getConfig('algorithm');
        }
        $this->algorithm = $val;
    }
    
    
    
    // If we're going to use session key protection
    // init session data (if not already initialized)
    public function startSession() {
        if (true == $this->getConfig('useSessions')) {
            if ( !isset($_SESSION) ) {
                session_start();
            }
        }
    }
    
    
    
    /**
     * 
     * Validate query/post
     * return status code
     * optionally call supplied user function
     * 
     * 
     */
    public function validate($response=null) {
        $status = false;
        
        $callback = $this->callback['function'];
        $params = array_merge($this->callback['params'], $this->getConfig());
        
        // need tokens to validate against
        if ( is_array($response) ) {
            // check that honeypot is empty (if in use)
            if ( empty($response['spam']) ||  (false == $this->getConfig('useHoneypot')) ) {
                // check that crypto is valid
                $auth = $this->validatePublicKey($response['salt'], $response['hash']);
                switch ($auth) {
                    // valid crypto
                    case 'success':
                        $status = 'success';
                        if ( $this->getConfig('useSessions') ) {
                            // check whether public key is still valid
                            if ( !in_array($response['hash'], $_SESSION[$this->getConfig('sessionKeys')]) ) { 
                                // valid crypto, but key is not available
                                // most likely an attempt to re-use a key
                                $status = 'replay';
                            }
                        }
                        break;
                    // response was too fast
                    case 'preempt':
                        $status = 'preempt';
                        break;
                    // response was too slow
                    case 'expired':
                        $status = 'expired';
                        break;
                    // some unidentified error (really shouldn't hit this branch)
                    // example: if you swap out the algo or something mid session you'll see this
                    default:
                        //$status = false;
                        $status = 'unknown';
                        break;
                }
            } else {
                // caught one in the honeypot
                $status = 'honeypot';
            }
        } else {
            // no valid response tokens
            $status = 'empty';
        }
        
        // expire all one-time keys 
        // (except currently valid key for next response)
        if ( $this->getConfig('useSessions') && isset($_SESSION) ) {
            foreach ($_SESSION[$this->getConfig('sessionKeys')] as $k => $v) { 
                if ( $k != $this->getSalt() ) { 
                    unset($_SESSION[$this->getConfig('sessionKeys')][$k]);
                }
            }
        }
        
        // call user-supplied functions 
        // with user-supplied parameters
        if ( is_callable($callback) ) {
            $response = call_user_func($callback, $status, $params);
            // if callback returns a value, store it
            // generally this should be a crass_response object
            $this->setResponse($response);
        }
        
        // return status code
        return $status;
    }
    
    
    
    /**
     * 
     * Check that supplied salt (timestamp)
     * hashes to same value as public key
     * ensuring that original timestamp (salt)
     * was "signed" with the private key.
     * 
     * If we do have a signed timestamp
     * then we can check to see if we
     * have a valid interval.
     * 
     */
    private function validatePublicKey($salt, $hash) {
        $auth = false;
        
        $sign = $this->getKeyHash($salt);
        $time = $this->validateTimeInterval($salt);
        
        if ( ($sign == $hash) && isset($time) ) {
            $auth = $time;
        }
        
        return $auth;
    }
    
    /*
     *
     * Check that salt/timestamp falls within
     * acceptable time time interval
     * Return reason for failure
     *
     */
    private function validateTimeInterval($salt) {
        $auth = false;
        
        $now = time();
        $interval = $now - $salt;
        
        if ( ($interval >= $this->getMin()) && ($interval <= $this->getMax()) ) {
            $auth = 'success';
        } elseif ($interval < $this->getMin()) {
            $auth = 'preempt';
        } elseif ($interval > $this->getMax()) {
            $auth = 'expired';
        }
        
        return $auth;
    }
    
    /*
     *
     * This here is the actual crypto.
     * It's pretty simple.
     *
     */
    private function getKeyHash($salt=null) {
        // accept optional salt param so we can return
        // a hash to match against supplied public key
        if ( empty($salt) ) {
            $salt = $this->getSalt();
        }
        
        $hash = hash_hmac($this->algorithm, $salt, $this->private);
        
        /*
        // Note: this was the original implementation. 
        // hash_hmac() is better.
        switch ($this->algorithm) {
            case 'SHA-512':
                $trigger = '$6$rounds=5000$';
                break;
            case 'SHA-256':
                default:
                $trigger = '$5$rounds=5000$';
                break;
        }
        //$hash = crypt($this->private, $trigger.$salt.'$');
        */
        
        return $hash;
    }
    
    // These form fields are required!
    // Call this method and insert them into the form.
    public function getFormFields() {
        $form = null;
        $salt = $this->getSalt();
        $hash = $this->getPublicKey();
        $honeypot = $this->getConfig('useHoneypot');

$fields = <<<EOD

        <div class="crass-response">
            <!-- Timestamp -->
            <input 
                id="crass-response-salt" 
                name="crass-response[salt]" 
                type="hidden" 
                readonly="readonly"
                value="{$salt}" />
            <!-- Public Key (signed timestamp) -->
            <input 
                id="crass-response-hash" 
                name="crass-response[hash]" 
                type="hidden" 
                readonly="readonly"
                value="{$hash}" />
        </div>
            
EOD;

$honey = <<<EOD
        
        <!-- Hide the honeypot. Lifted from .visuallyhidden in style.css of h5bp.com -->
        <div 
            style="
                    border: 0; 
                    clip: rect(0 0 0 0); 
                    height: 1px; margin: -1px; 
                    overflow: hidden; 
                    padding: 0; 
                    position: absolute; 
                    width: 1px;"
        > 
            <label for="crass-response-spam">
            Attention screen readers: This is SPAM protection. Please do not fill out this field. If you do we will not be able to process the form.
            </label>
            <input 
                id="crass-response-spam" 
                name="crass-response[spam]" 
                type="text" 
                value="" />
        </div>

EOD;

        $form = (true === $honeypot) ? $fields . $honey : $fields;

        return $form;
    
    }

    // just printout some basic info if debugging is enabled
    public function _debug($status, $params, $callback) {
        echo '<div class="alert alert-info">';
        echo '<a class="close" data-dismiss="alert">×</a>';
        echo '<h4 class="alert-heading">Crass Debug Info:</h4>';
        echo '<pre>';
        echo 'Executing: ' . $callback;
        echo '<br />';
        echo 'Status Code: ' . $status;
        echo '<br />';
        echo 'Parameters: ';
        var_export($params);
        echo '<br />';
        echo 'Session Keys: ';
        if ( isset($_SESSION[$params['sessionKeys']]) ) {
            var_export($_SESSION[$params['sessionKeys']]);
        }
        echo '<br />';
        echo 'Request Vars: ';
        if ( isset($_REQUEST) ) {
            var_export($_REQUEST);
        }
        echo '</pre>';
        echo '</div>';
    }

}


?>