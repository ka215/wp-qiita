<?php
defined( 'WPQT' ) OR wp_die();

if ( class_exists( 'WP_Widget' ) ) :

class WpQiitaWidget extends WP_Widget {
  
  var $fields;
  var $widget_name;
  var $widget_slug;
  var $wpqt;
  
  public function __construct() {
    $this->widget_name = __( 'WP-Qiita Widget', WPQT ); // Labeled
    $widget_options = array(
      'classname' => 'wp_qiita_widget', 
      'description' => __( 'Introduce an articles that is synchronized from Qiita', WPQT ), 
    );
    parent::__construct( $widget_options['classname'], $this->widget_name, $widget_options );
    $this->widget_slug = $widget_options['classname'];
    
    add_action( 'save_post', array( &$this, 'flush_widget_cache' ) );
    add_action( 'delete_post', array( &$this, 'flush_widget_cache' ) );
    add_action( 'switch_theme', array( &$this, 'flush_widget_cache' ) );
    
    // Set Fields
    $this->fields = array(
      'title' => __('Widget Title', WPQT), 
      'display_title' => __( 'Display Widget Label', WPQT ), 
      'display_limit' => __( 'Display Limit', WPQT ), 
      'display_state' => __( 'Display State', WPQT ), 
      'sort_by' => __( 'Sort By', WPQT ), 
      'sort_order' => __( 'Sort Order', WPQT ), 
      'show_stocks' => __( 'Display the number of stock?', WPQT ), 
    );
    
  }
  
  public function widget( $args, $instance ) {
    $cache = wp_cache_get( $this->widget_slug, 'widget' );
    
    if ( ! is_array( $cache ) ) 
      $cache = array();
    
    if ( ! isset( $args['widget_id'] ) ) 
      $args['widget_id'] = null;
    
    if ( isset( $cache[$args['widget_id']] ) ) {
      echo $cache[$args['widget_id']];
      return;
    }
    
    // Retrive Specific Posts
    global $wpqt;
    //$this->wpqt = is_object( $wpqt ) && ! empty( $wpqt ) ? $wpqt : WpQiitaMain::instance();
    $_args = array(
      'posts_per_page' => $instance['display_limit'], 
      'post_type' => $wpqt->domain_name, 
      'post_status' => str_replace( '+', ',', $instance['display_state'] ), 
      'orderby' => 'stocks' === $instance['sort_by'] ? 'date' : $instance['sort_by'], 
      'order' => strtoupper( $instance['sort_order'] ), 
    );
    if ( 'stocks' === $instance['sort_by'] ) {
      // Must WP 4.2 more
      $_args['meta_query'] = array(
        'stocks' => array(
          'key' => 'wpqt_stocks', 
          'type' => 'DECIMAL', 
          'compare' => 'EXISTS',
        )
      );
      $_args['orderby'] = array(
        'stocks' => strtoupper( $instance['sort_order'] ), 
      );
    }
    $_posts = get_posts( $_args );
    if ( ! empty( $_posts ) ) {
      
      
      ob_start();
      extract( $args, EXTR_SKIP );
      
      $title = apply_filters( 'wpqt/widget_title', empty( $instance['title'] ) ? $this->widget_name : $instance['title'], $instance, $args['widget_id'] );
      $display_title = $instance['display_title'];
      
      foreach ( $this->fields as $_name => $_label ) {
        if ( ! isset( $instance[$_name] ) ) 
          $instance[$name] = '';
      }
      
      echo $before_widget;
      if ( $display_title ) 
        echo $before_title, $wpqt->the_custom_icon( array( 'id'=>3 ), '' ), do_shortcode( $display_title ), $after_title;
      
      $render_container_class = apply_filters( 'wpqt/widget_container_class', 'wpqt-container', $instance, $args['widget_id'] );
      $render_list_class = apply_filters( 'wpqt/widget_list_class', 'wpqt-list-'. $instance['sort_by'], $instance, $args['widget_id'] );
      $render_var_class = apply_filters( 'wpqt/widget_var_class', 'count', $instance, $args['widget_id'] ); ?>
<div class="<?php echo $display_content_class; ?>">
  <ul>
  <?php foreach ( $_posts as $_post ) : ?>
    <li>
      <a href="<?php echo get_post_meta( $_post->ID, 'wpqt_origin_url', true ); ?>" target="_blank"><?php echo $_post->post_title; ?></a>
    <?php if ( $instance['show_stocks'] ) : ?><var class="count"><?php echo get_post_meta( $_post->ID, 'wpqt_stocks', true ); ?></var><?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>
</div>
<?php
      echo $after_widget;
      
      $cache[$args['widget_id']] = ob_get_flush();
      wp_cache_set( $this->widget_slug, $cache, 'widget' );
    }
  }
  
  public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance['title'] = strip_tags( trim( $new_instance['title'] ) );
    $instance['display_title'] = trim( $new_instance['display_title'] );
    $instance['display_limit'] = intval( $new_instance['display_limit'] );
    $instance['display_state'] = trim( $new_instance['display_state'] );
    $instance['sort_by'] = trim( $new_instance['sort_by'] );
    $instance['sort_order'] = trim( $new_instance['sort_order'] );
    $instance['show_stocks'] = wp_validate_boolean( trim( $new_instance['show_stocks'] ) );
    
    $this->flush_widget_cache();
    $alloptions = wp_cache_get( 'alloptions', 'options' );
    if ( isset( $alloptions[$this->widget_slug] ) ) 
      delete_option( $this->widget_slug );
    
    return $instance;
    
  }
  
  public function flush_widget_cache() {
    // Flush cache
    wp_cache_delete( $this->widget_slug, 'widget' );
    
  }
  
  public function form( $instance ) {
    // Create setting fields in widget menu
    foreach ( $this->fields as $_name => $_label ) {
      $_field_id = isset( $instance[$_name] ) ? $this->get_field_id( $_name ) : '';
      $_field_name = isset( $instance[$_name] ) ? $this->get_field_name( $_name ) : '';
      switch ( $_name ) {
        case 'title': 
        case 'display_title': 
          ${$_name} = isset( $instance[$_name] ) ? esc_attr( $instance[$_name] ) : ''; ?>
<p>
  <label for="<?php echo $_field_id; ?>"><?php echo $_label; ?>:</label>
  <input class="widefat" id="<?php echo $_field_id; ?>" name="<?php echo $_field_name; ?>" type="text" value="<?php echo ${$_name}; ?>">
</p>
<?php
          break;
        case 'display_limit': 
          ${$_name} = isset( $instance[$_name] ) && ! empty( $instance[$_name] ) && intval( $instance[$_name] ) > 0 ? esc_attr( $instance[$_name] ) : 5; ?>
<p>
  <label for="<?php echo $_field_id; ?>"><?php echo $_label; ?>:</label>
  <input id="<?php echo $_field_id; ?>" name="<?php echo $_field_name; ?>" type="number" value="<?php echo ${$_name}; ?>" style="text-align: center; width: 5em;">
</p>
<?php
          break;
        case 'display_state': 
          ${$_name} = isset( $instance[$_name] ) && ! empty( $instance[$_name] ) && in_array( $instance[$_name], array( 'publish', 'publish+private' ) ) ? esc_attr( $instance[$_name] ) : 'publish'; ?>
<p>
  <label for="<?php echo $_field_id; ?>"><?php echo $_label; ?>:</label>
  <select id="<?php echo $_field_id; ?>" name="<?php echo $_field_name; ?>">
    <option value="publish"<?php if ( 'publish' === ${$_name} ) : ?> selected="selected"<?php endif; ?>><?php _e('Publish Only', WPQT ); ?></option>
    <option value="publish+private"<?php if ( 'publish+private' === ${$_name} ) : ?> selected="selected"<?php endif; ?>><?php _e('Publish and Private', WPQT ); ?></option>
  </select>
</p>
<?php
          break;
        case 'sort_by': 
          $candidates = array(
            'date' => __( 'Created Date', WPQT ), 
            'modified' => __( 'Modified Date', WPQT ), 
            'comment_count' => __( 'Comment Count', WPQT ), // Not yet at 1.0.0
            'stocks' => __( 'Stocks', WPQT ), 
            'rand' => __( 'Random', WPQT ), 
          );
          unset( $candidates['comment_count'] );
          ${$_name} = isset( $instance[$_name] ) && ! empty( $instance[$_name] ) && in_array( $instance[$_name], array_keys( $candidates ) ) ? esc_attr( $instance[$_name] ) : 'date'; ?>
<p>
  <label for="<?php echo $_field_id; ?>"><?php echo $_label; ?>:</label>
  <select id="<?php echo $_field_id; ?>" name="<?php echo $_field_name; ?>">
  <?php foreach ( $candidates as $_key => $_label ) : ?>
    <option value="<?php echo $_key; ?>"<?php if ( $_key === ${$_name} ) : ?> selected="selected"<?php endif; ?>><?php echo $_label; ?></option>
  <?php endforeach; ?>
  </select>
</p>
<?php
          break;
        case 'sort_order': 
          ${$_name} = isset( $instance[$_name] ) && ! empty( $instance[$_name] ) && in_array( $instance[$_name], array( 'DESC', 'ASC' ) ) ? esc_attr( $instance[$_name] ) : 'DESC'; ?>
<p>
  <label for="<?php echo $_field_id; ?>"><?php echo $_label; ?>:</label>
  <select id="<?php echo $_field_id; ?>" name="<?php echo $_field_name; ?>">
    <option value="DESC"<?php if ( 'DESC' === ${$_name} ) : ?> selected="selected"<?php endif; ?>><?php _e('DESC', WPQT ); ?></option>
    <option value="ASC"<?php if ( 'ASC' === ${$_name} ) : ?> selected="selected"<?php endif; ?>><?php _e('ASC', WPQT ); ?></option>
  </select>
</p>
<?php
          break;
        case 'show_stocks': 
          ${$_name} = isset( $instance[$_name] ) ? wp_validate_boolean( $instance[$_name] ) : false; ?>
<p>
  <label>
    <input type="checkbox" id="<?php echo $_field_id; ?>" name="<?php echo $_field_name; ?>" value="1" <?php checked( ${$_name}, true ); ?>> <?php echo $_label; ?>
  </label>
</p>
<?php
          break;
        default: 
          break;
      }
    }
  }
  
}

endif; // end of class_exists()