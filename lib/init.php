<?php
defined( 'WPQT' ) OR wp_die();

/**
 * Instance factory for WP Qiita plugin
 *
 * @since v1.0.0
 *
 * @param boolean $set_global [optional] Default is true
 * @return void
 */
function wpqt_factory( $set_global=true ) {
  
  if ( wp_validate_boolean($set_global) ) {
    
    global $wpqt;
    $wpqt = WpQiitaMain::instance();
    
  } else {
    
    return WpQiitaMain::instance();
    
  }
  
}
