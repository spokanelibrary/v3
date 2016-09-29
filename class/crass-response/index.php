<?php
//error_reporting(E_ALL); 
//ini_set( 'display_errors','1');

/*
Plugin Name: WP Crass Response
Plugin URI: http://www.seangirard.com
Description: Trused timestamp spam prevention using hash_hmac(). 
Author: Sean Girard
Version: 1.1
Author URI: http://seangirard.com

ToDo: Collect Roots-specific variables
*/

require '_crass/Crass.php';

// Function to echo form fields
function wp_crass_response_fields() {
  //if ( !is_user_logged_in() ) {
    echo WP_Crass_Response::$crass->getFormFields();
  //}
}

// Acts as a static initializer
add_action('init', array('WP_Crass_Response', '__init__'));

/*
 *
 * You can overload the Crass_Response class
 * because it is called from Crass via __autoload()
 * 
 * So if you define it here it is never __autoload()ed.
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
      if ( isset($_REQUEST['spl-form']) ) {
        if ( isset($_REQUEST['spl-form']['id']) ) {
          switch ($_REQUEST['spl-form']['id']) :
            case 'renew':
              if ( !empty($_REQUEST['spl-renew']) ) {
                $this->setRequest($_REQUEST['spl-renew']);
              }
              $this->handleRenewal();
              break;
            case 'contact':
              $this->setRequest($_REQUEST['spl-form']);
              $this->handleContact();
              break;
            default:
                break;
          endswitch;
        } else {
        
        }
      }
    }
    
    protected function handleContact() {
      //var_export($this);
    }
    
    protected function handleRenewal() {
      // make db request here and handle ret val
      $result = 'success';
      
      switch( $this->request['stage'] ) :
        case '2':
          if ( 'success' == $result ) {
            $stage = 3;
          } else {
            $stage = 2;
          }
          break;
        case '1':
          if ( 'success' == $result ) {
            $stage = 2;
          } else {
            $stage = 1;
          }
          break;
        default:
          $stage = 1;
          break;
      endswitch;
      
      $this->addParam('stage',$stage);
        
    }
    
}


/*
 *  Note: Late Static Binding was not available prior to 5.3
 *        and we're using it here.
 *
 *        Wordpress seems to like static methods so we wrap
 *        WP calls to Crass_Response in these static classes.
 *
 */

class WP_Crass_Response {

  public static $crass;
  
  static function __init__() {
    require('_crass/index.php');
    self::$crass = $crass;
    
    // Setup WP actions/filter hooks
    if ( !is_user_logged_in() ) {
      add_filter('pre_comment_on_post', array('WP_Crass_Response_Comments', 'wp_crass_comment_filter'));
    }
    
    // Setup form shortcodes
    add_shortcode('crass_form', array('WP_Crass_Response_Form', 'wp_crass_form'));
    
    //self::debug();
  }
  
  static function debug() {
    var_export(self::$crass);
  }

}

class WP_Crass_Response_Form extends WP_Crass_Response {

  function wp_crass_form($atts) {
    $form = null;
    $path = 'templates/forms/form'; // relative to the active theme directory
    $tmpl = '_tmpl/';
    
    if ( is_array($atts) ) {
      ob_start();
      
      get_template_part($path, $atts[0]);
      $form = ob_get_contents();
      
      if ( !$form ) {
        include plugin_dir_path(__FILE__) . $tmpl . $atts[0] . '.php';
        $form = ob_get_contents();
      }
      
      ob_end_clean();
    }
    
    return $form;
  }

}


class WP_Crass_Response_Comments extends WP_Crass_Response {

  function wp_crass_comment_filter($post_id) {
    add_filter('wp_die_handler', array('WP_Crass_Response_Comments', 'wp_crass_comment_die_handler'));
    
    $valid = false;
    if ( self::$crass->response ) {
      if ( 'success' == self::$crass->response->status ) {
        $valid = true;
      }
    }
    
    if ( !$valid ) {
      wp_die( __('Error: '. ucfirst(self::$crass->response->status) . '.') );
    }
  } // wp_crass_comment_filter

  function wp_crass_comment_die_handler() {
    return array('WP_Crass_Response_Comments', 'wp_crass_comment_die');
  } // wp_crass_comment_die_handler

  function wp_crass_comment_die($message, $title='', $args=array()) {
    // Supply a local plugin error template but allow a theme copy
    $err_theme = get_theme_root() . '/' . get_template() . '/' . 'comment-error.php';
    $err_local = plugin_dir_path(__FILE__) . '_tmpl/comment-error.php';
    if ( file_exists($err_theme) ) {
      $errorTemplate = $err_theme;
    } else {
      $errorTemplate = $err_local;
    }
    
    if( !is_admin() && file_exists($errorTemplate) ) {
      $defaults = array( 'response' => 500 );
      $r = wp_parse_args($args, $defaults);
      $have_gettext = function_exists('__');
      if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {
        if ( empty( $title ) ) {
            $error_data = $message->get_error_data();
            if ( is_array( $error_data ) && isset( $error_data['title'] ) )
                $title = $error_data['title'];
        }
        $errors = $message->get_error_messages();
        switch ( count( $errors ) ) :
          case 0 :
            $message = '';
            break;
          case 1 :
            $message = "<p>{$errors[0]}</p>";
            break;
          default :
            $message = "<ul>\n\t\t<li>" . join( "</li>\n\t\t<li>", $errors ) . "</li>\n\t</ul>";
            break;
        endswitch;
      } elseif ( is_string( $message ) ) {
        // Crass Response
        $message = array('text'=>$message, 'link'=>wp_get_referer());
      }
      if ( isset( $r['back_link'] ) && $r['back_link'] ) {
        $back_text = $have_gettext? __('&laquo; Back') : '&laquo; Back';
        $message .= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
      }
      if ( empty($title) )
        $title = $have_gettext ? __('WordPress &rsaquo; Error') : 'WordPress &rsaquo; Error';
        require($errorTemplate);
        die();
    } else {
      _default_wp_die_handler($message, $title, $args);
    }
  
  } // spl_comment_die

}

?>