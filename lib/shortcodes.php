<?php
defined( 'WPQT' ) OR wp_die();

if ( class_exists( 'WpQiitaUtils' ) ) :

class WpQiitaShortcodes extends WpQiitaUtils {
  
  var $shortcodes;
  var $shortcode_templates_dir;
  
  public function __construct() { /* Do nothing here */ }
  
  protected function register_shortcodes() {
    
    $this->shortcodes = array(
      'wpqt-icon' => array( 'function' => 'the_custom_icon', 'description' => __('Rendering the custom icons', $this->domain_name), 'since' => '1.0.0' ), 
      'wpqt-permalink' => array( 'function' => 'get_qiita_link', 'description' => __('Rendering the Qiita side permalink of specified post', $this->domain_name), 'since' => '1.0.0' ), 
      'wpqt-post-stocks' => array( 'function' => 'get_post_stocks', 'description' => __('Rendering the number of stock of the specified post', $this->domain_name), 'since' => '1.0.0' ), 
      
    );
    
    foreach ( $this->shortcodes as $shortcode_name => $shortcode_atts ) {
      if ( false === shortcode_exists( $shortcode_name ) ) 
        add_shortcode( $shortcode_name, array( $this, $shortcode_atts['function'] ) );
    }
    
  }
  
  /**
   * Shortcodes definition
   * -------------------------------------------------------------------------
   */
  
  /**
   * Rendering the custom icons
   *
   * @since 1.0.0
   *
   * @param array $attributes [required] Array of attributes in shortcode
   * @param string $content [optional] For default is empty
   * @return string $html_content The formatted content
   */
  public function the_custom_icon() {
    list( $attributes, $content ) = func_get_args();
    extract( shortcode_atts( array(
      'name' => '', 
      'id' => null, 
      'class' => '', 
    ), $attributes ) );
    $shortcode_name = 'wpqt-icon';
    
    $icon_names = array( 'qiita-q', 'qiita-favicon', 'qiita-favicon-color', 'qiita-favicon-reversal', 'qiita-square' );
    $render_tmpl = '<span class="%s%s">%s</span>';
    $base_class = '';
    if ( ! empty( $name ) && in_array( $name, $icon_names ) ) {
      $base_class = 'wpqt-' . $name;
    } else
    if ( ! empty( $id ) && intval( $id ) > 0 && intval( $id ) <= count( $icon_names ) ) {
      $base_class = 'wpqt-' . $icon_names[ intval( $id ) - 1 ];
    }
    if ( ! empty( $base_class ) ) {
      $paths = 'wpqt-qiita-favicon-color' === $base_class ?  '<span class="path1"></span><span class="path2"></span>' : '';
      $render_html = sprintf( $render_tmpl, $base_class, ' ' . esc_attr( $class ), $paths );
    } else {
      $render_html = '';
    }
    
    return $render_html;
  }
  
  /**
   * Rendering the Qiita side permalink of specified post
   *
   * @since 1.0.0
   *
   * @param array $attributes [required] Array of attributes in shortcode
   * @param string $content [optional] For default is empty
   * @return string $html_content The formatted content
   */
  public function get_qiita_link() {
    list( $attributes, $content ) = func_get_args();
    extract( shortcode_atts( array(
      'pid' => null, 
      'iid' => null, 
      'html' => true, 
      'target' => '_self', 
      'class' => '', 
    ), $attributes ) );
    $shortcode_name = 'wpqt-permalink';
    
    $_post = null;
    if ( ! empty( $pid ) && intval( $pid ) > 0 ) {
      $_post = get_post( intval( $pid ) );
    } else
    if ( ! empty( $iid ) ) {
      $_tmp = get_posts( array( 'post_type' => $this->domain_name, 'meta_key' => 'wpqt_item_id', 'meta_value' => $iid ) );
      $_post = $_tmp[0];
    } else
    if ( is_main_query() ) {
      $_post = get_post();
    }
    if ( ! empty( $_post ) ) {
      $_qiita_url = get_post_meta( $_post->ID, 'wpqt_origin_url', true );
      if ( ! empty( $_qiita_url ) ) {
        if ( wp_validate_boolean( $html ) ) {
          $content = empty( $content ) ? $_qiita_url : $content;
          return sprintf( '<a href="%s" target="%s" class="%s">%s</a>', esc_url( $_qiita_url ), esc_attr( $target ), esc_attr( $class ), esc_html( $content ) );
        } else {
          return $_qiita_url;
        }
      }
    }
    
    return '';
  }
  
  /**
   * Rendering the number of stock of the specified post
   *
   * @since 1.0.0
   *
   * @param array $attributes [required] Array of attributes in shortcode
   * @param string $content [optional] For default is empty
   * @return string $html_content The formatted content
   */
  public function get_post_stocks() {
    list( $attributes, $content ) = func_get_args();
    extract( shortcode_atts( array(
      'pid' => null, 
      'iid' => null, 
    ), $attributes ) );
    $shortcode_name = 'wpqt-post-stocks';
    
    $_post = null;
    if ( ! empty( $pid ) && intval( $pid ) > 0 ) {
      $_post = get_post( intval( $pid ) );
    } else
    if ( ! empty( $iid ) ) {
      $_tmp = get_posts( array( 'post_type' => $this->domain_name, 'meta_key' => 'wpqt_item_id', 'meta_value' => $iid ) );
      $_post = $_tmp[0];
    } else
    if ( is_main_query() ) {
      $_post = get_post();
    }
    if ( ! empty( $_post ) ) {
      $_stocks = get_post_meta( $_post->ID, 'wpqt_stocks', true );
      return intval( $_stocks );
    }
    
    return;
  }
  
}

endif; // end of class_exists()