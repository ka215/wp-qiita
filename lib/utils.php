<?php
defined( 'WPQT' ) OR wp_die();

if ( class_exists( 'WpQiita' ) ) :

class WpQiitaUtils extends WpQiita {
  
  /**
   * Define magic methods as follow;
   *
   * @since 1.0.0
   */
  public function __construct() { /* Do nothing here */ }
  
  public function __destruct() { /* Do nothing here */ }
  
  /**
   *
   * @since 1.0.0
   *
   * @param string $datetime [optional]
   * @param string $dateformatstring [optional]
   * @param string $timezone [optional]
   * @return string 
   */
  public function wpqt_date_format( $datetime=null, $dateformatstring=null, $timezone=null ) {
    if (empty($datetime)) 
      return date(get_option('date_format') .' '. get_option('time_format'), time());
    
    $dateformatstring = empty($dateformatstring) ? get_option('links_updated_date_format') : $dateformatstring;
    $timezone = empty($timezone) ? get_option('timezone_string') : $timezone;
    
    try {
      $_timezone = new DateTimeZone($timezone);
      $_datetime = new DateTime($datetime);
      $_datetime->setTimezone($_timezone);
      
      return $_datetime->format($dateformatstring);
    } catch (Exception $e) {
      return $e;
    }
  }
  
  /**
   * Retrieve for posts with a specified pair of post meta from all posts
   *
   * @since 1.0.0
   *
   * @param string $post_meta_key [required]
   * @param string $post_meta_value [required]
   * @return mixed Return post ID if it found post, and otherwise return false.
   */
  public function retrieve_by_postmeta( $post_meta_key=null, $post_meta_value=null ) {
    if (empty($post_meta_key) || empty($post_meta_value)) 
      return false;
    
    $narrow_key = get_post_types( array('public'=>true, '_builtin'=>false), 'names', 'and' );
    if (is_array($narrow_key)) 
      array_unshift($narrow_key, 'post', 'page');
    
    $args = array(
      'post_type' => $narrow_key,
      'meta_key' => $post_meta_key,
      'meta_value' => $post_meta_value,
      'posts_per_page' => -1
    );
    $matched_posts = get_posts($args);
    
    if (empty($matched_posts)) {
      return false;
    } else {
      $post_ids = array();
      foreach ($matched_posts as $_post) {
        $post_ids[] = $_post->ID;
      }
      return $post_ids;
    }
  }
  
  
}

endif; // end of class_exists()