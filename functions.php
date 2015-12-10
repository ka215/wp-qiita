<?php
/**
 * Including libraries for this plugin
 */
defined( 'WPQT' ) OR wp_die();

$library_dir_name = 'lib';
$library_dir = plugin_dir_path(__FILE__) . $library_dir_name;

$lib_includes = array();

$files = array(
  'core.php', 			// Wrapper class of Qiita API v2
  'utils.php', 			// Class of common utilities
  'shortcodes.php',	// Class of each shortcodes definition
  'main.php', 			// Final class as dispatcher
  'init.php', 				// Instance factory & plugin activater
  'widgets.php',		// Class of each widgets definition
);
foreach ( $files as $file ) {
  $lib_includes[] = $library_dir . '/' . $file;
}
unset( $library_dir_name, $library_dir, $files, $file );

foreach ( $lib_includes as $file ) {
  if ( ! file_exists( $file ) ) {
    trigger_error( sprintf( __('Error locating %s for inclusion', MPQT), $file ), E_USER_ERROR);
  }
  
  require_once $file;
}
unset( $file );
