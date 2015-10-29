<?php
/**
 * Including libraries for this plugin
 */
defined( 'WPQT' ) OR wp_die();

$library_dir_name = 'lib';
$library_dir = plugin_dir_path(__FILE__) . $library_dir_name;

$lib_includes = array();

$files = array(
  'core.php', 		// Wrapper class of Qiita API v2
  'utils.php', 		// Class of common utilities
  'main.php', 		// Final class as dispatcher
  'init.php', 			// Instance factory & plugin activater
);
foreach ($files as $file) {
  $lib_includes[] = $library_dir . '/' . $file;
}
unset($library_dir_name, $library_dir, $files, $file);

foreach ($lib_includes as $file) {
  if (!file_exists($file)) {
    trigger_error( sprintf(__('Error locating %s for inclusion', MPQT), $file), E_USER_ERROR);
  }
  
  require_once $file;
}
unset($file);

/**
 * Extended as utility functions
 */
function debug_api_request_args( $request_args, $url ){
  var_dump([ $request_args, $url ]);
}
add_filter( 'wp_qiita/api_request_args', 'debug_api_request_args', 10, 2 );
function debug_api_request_response( $response, $url ){
  var_dump([ $response, $url ]);
}
add_filter( 'wp_qiita/api_request_response', 'debug_api_request_response', 10, 2 );

/*
$host = 'https://qiita.com';
$response = wp_remote_request($host . '/api/v2/authenticated_user', [ 'method' => 'GET', 'headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Bearer 6532a7b57f4c16ff9334102ad991f8e9a6ef30bb' ] ]);
var_dump($response);
*/
/*
$test = new WpQiita();
$test->token = '6532a7b57f4c16ff9334102ad991f8e9a6ef30bb';
var_dump( wp_remote_request($test->get_api_url([ 'authenticated_user' ]), [ 'method' => 'get', 'headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Bearer 6532a7b57f4c16ff9334102ad991f8e9a6ef30bb' ]]) );
*/