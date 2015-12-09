<?php
defined( 'WPQT' ) OR wp_die();

if ( class_exists( 'WpQiitaShortcodes' ) ) :

final class WpQiitaMain extends WpQiitaShortcodes {
  
  var $version;
  var $options; // global options for this plugin
  var $current_user;
  var $user_options; // each users local options for this plugin
  var $widgets;
  var $debug_mode;
  var $errors;
  var $message_type;
  var $logger_cache;
  var $query;
  
  /**
   * Instance factory method as entry point of plugin.
   *
   * @since 1.0.0
   */
  public static function instance() {
    
    static $instance = null;
    
    if ( null === $instance ) {
      $instance = new self;
      $instance->plugin_init();
      $instance->inclusion();
      $instance->setup_actions();
    }
    
    return $instance;
  }
  
  /**
   * Define magic methods as follow;
   *
   * @since 1.0.0
   */
  public function __construct() { /* Do nothing here */ }
  
  public function __destruct() { /* Do nothing here */ }
  
  
  /**
   * Definition of the class members
   *
   * @since 1.0.0
   */
  protected function plugin_init() {
    
    // Const members
    $this->domain_name = WPQT;
    $this->version = WPQT_PLUGIN_VERSION;
    $this->db_version = WPQT_DB_VERSION;
    $this->debug_mode = WPQT_DEBUG;
    
    // Paths
    $this->plugin_dir_name = str_replace('/lib', '', dirname( plugin_basename( __FILE__ ) ));
    $this->plugin_dir_path = str_replace('lib/', '', plugin_dir_path( __FILE__ ));
    $this->plugin_dir_url = str_replace('/lib', '', plugin_dir_url( __FILE__ ));
    $this->plugin_main_file = $this->plugin_dir_path . $this->domain_name . '.php';
    
    // Override core members
    $this->data = array();
    $this->api_request_options['user-agent'] = sprintf( 'WordPress/%s; %s; wp-qiita/%s', $GLOBALS['wp_version'], site_url(), $this->version );
    $this->options = get_option( $this->domain_name . '-options', array() );
    $this->message_type = array(
      'note' => $this->domain_name . '-notice', 
      'err' => $this->domain_name . '-error', 
    );
    
  }
  
  /**
   * Include sub classes
   *
   * @since 1.0.0
   */
  protected function inclusion() {
    
    /*
    if ( class_exists( 'WpQiitaWidgets' ) ) {
      $this->widget = new WpQiitaWidgets;
      $this->widget->instance();
    }
    */
    
  }
  
  /**
   * Definition of actions and filters for WordPress
   *
   * @since 1.0.0
   */
  protected function setup_actions() {
    
    register_uninstall_hook( $this->plugin_main_file, array( get_class($this), 'plugin_uninstall' ) );
    register_deactivation_hook( $this->plugin_main_file, array( &$this, 'plugin_deactivation' ) );
    register_activation_hook( $this->plugin_main_file, array( &$this, 'plugin_activate' ) );
    
    add_action( 'after_setup_theme', array( $this, 'wpqt_setup_theme' ) );
    add_action( 'plugins_loaded', array( $this, 'wpqt_plugin_loaded' ) );
    add_action( 'init', array( $this, 'wpqt_init' ) );
    if ( ! post_type_exists( $this->domain_name ) ) {
      // Register a wp-qiita post type.
      // 
      // @link http://codex.wordpress.org/Function_Reference/register_post_type
      add_action( 'init', array( $this, 'create_post_type' ) );
    }
    add_action( 'widgets_init', array( $this, 'wpqt_widgets' ) );
    add_action( 'wp_loaded', array( $this, 'wpqt_wp_loaded' ) ); // Fired once WordPress, all plugins, and the theme are fully loaded
    add_action( 'wpqt/autosync', array( $this, 'wpqt_autosync' ) ); // Add New Action
    
    if ( is_admin() ) {
      add_action( 'admin_menu', array( $this, 'wpqt_admin_menu' ) );
      add_action( 'admin_init', array( $this, 'wpqt_admin_init' ) );
      add_action( 'pre_get_posts', array( $this, 'wpqt_pre_get_posts' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'wpqt_enqueue_scripts' ) );
      add_action( 'admin_head', array( $this, 'wpqt_head' ) );
      add_action( 'admin_notices', array( $this, 'wpqt_admin_notices' ) );
      # do_action( 'wpqt/get_admin_template', array( $this, 'get_admin_template') ); // Add New Action
      add_action( 'admin_footer', array( $this, 'wpqt_footer' ) );
      add_action( 'admin_print_footer_scripts', array( $this, 'wpqt_print_footer_scripts' ) ); // For modal insertion
      
      // Filters
      add_filter( 'plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );
      add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );
      add_filter( 'wpqt/register_post_type', array( $this, 'wpqt_custom_post_type' ) );
      
    } else {
      add_action( 'pre_get_posts', array( $this, 'wpqt_pre_get_posts' ) );
      add_action( 'wp_enqueue_scripts', array( $this, 'wpqt_enqueue_scripts' ) );
      add_action( 'wp_head', array( $this, 'wpqt_head' ) );
      add_action( 'wp_footer', array( $this, 'wpqt_footer' ) );
      add_action( 'wp_print_footer_scripts', array( $this, 'wpqt_print_footer_scripts' ) );
      
      // Filters
      add_filter( 'wp_body_class', array( $this, 'add_body_classes' ) );
      
    }
    
    add_action( 'shutdown', array( $this, 'wpqt_shutdown' ) );
    
  }
  
  /**
   * Some common hooks (frontend and admin screen)
   *
   * @since 1.0.0
   * -------------------------------------------------------------------------
   */
  public function wpqt_setup_theme() {
    // Currently do nothing
  }
  
  public function wpqt_plugin_loaded() {
    // Load languages
    load_plugin_textdomain( $this->domain_name )
    or load_plugin_textdomain( $this->domain_name, false, $this->plugin_dir_name . '/langs' );
    
  }
  
  public function wpqt_init() {
    // Set ajax action name
    $this->plugin_ajax_action = 'wpqt_ajax_handler';
    
    // Shortcodes initialize
    $this->register_shortcodes();
    
    // Set current query strings
    if (is_admin()) {
      wp_parse_str( $_SERVER['QUERY_STRING'], $this->query );
    } else {
      $this->query = $GLOBALS['_REQUEST'];
    }
    
    // Set current user local options
    global $user_ID;
    get_currentuserinfo();
    $this->current_user = $user_ID; // guest is `0`
    $this->user_options = get_user_meta( $user_ID, 'wpqt_qiita_authenticated_user', true ); // guest is `false`
    
    // Session initialize
    if (!session_id()) 
      @session_start();
    
    // Start output buffering
    ob_start();
    
  }
  
  public function wpqt_widgets() {
    
    register_widget( 'WpQiitaWidget' ); // id_base : wp_qiita_widget
    
    $this->widgets = array(
      'wp_qiita_widget', 
    );
  }
  
  public function wpqt_wp_loaded() {
    // Currently do nothing
    
  }
  
  public function wpqt_pre_get_posts() {
    // Currently do nothing
  }
  
  public function wpqt_enqueue_scripts() {
    $load_wpqt_assets = false;
    if ( is_admin() && array_key_exists('page', $this->query) && 'wp-qiita-options' === $this->query['page'] ) {
      $load_wpqt_assets = true;
    } else
    if ( ! is_admin() ) {
      $load_wpqt_assets = $this->check_whether_loading_assets( $this->widgets, array_keys( $this->shortcodes ) );
    }
    if ( ! $load_wpqt_assets ) 
      return;
    
    // Load this plugin assets
    wp_deregister_script( 'jquery' );
    $assets = array(
      'styles' => array(
        'bootstrap-style-cdn' => array( '//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css', array(), '3.3.5', 'all' ), 
        'wpqt-style' => array( $this->plugin_dir_url . 'assets/styles/wpqt.css', array(), $this->version, 'all' ), 
      ), 
      'scripts' => array(
        'jquery-cdn' => array( '//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js', array(), '1.11.3', false ), 
        'bootstrap-script-cdn' => array( '//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js', array( 'jquery-cdn' ), '3.3.5', true ), 
        'blockchain' => array( 'https://blockchain.info/Resources/wallet/pay-now-button.js', array( 'jquery-cdn' ), null, true ), 
        'wpqt-script' => array( $this->plugin_dir_url . 'assets/scripts/wpqt.js', array( 'jquery-cdn' ), $this->version, true ), 
      )
    );
    if ( ! is_admin() ) {
      unset( $assets['styles']['bootstrap-style-cdn'], $assets['scripts']['bootstrap-script-cdn'], $assets['scripts']['blockchain'] );
      if ( isset( $this->options['load_jquery'] ) && ! $this->options['load_jquery'] ) 
        unset( $assets['scripts']['jquery-cdn'] );
    }
    // Filter the assets to be importing in page (before registration)
    //
    // @since 1.0.0
    $assets = apply_filters( 'wp-qiita/enqueue_assets', $assets, $this->query );
    
    foreach ($assets as $asset_type => $asset_data) {
      if ('styles' === $asset_type) {
        foreach ($asset_data as $asset_name => $asset_values) {
          wp_enqueue_style( $asset_name, $asset_values[0], $asset_values[1], $asset_values[2], $asset_values[3] );
        }
      }
      if ('scripts' === $asset_type) {
        foreach ($asset_data as $asset_name => $asset_values) {
          if (!empty($asset_values)) 
            wp_register_script( $asset_name, $asset_values[0], $asset_values[1], $asset_values[2], $asset_values[3] );
          
          wp_enqueue_script( $asset_name );
        }
      }
    }
    
  }
  
  public function wpqt_head() {
    // Currently do nothing
  }
  
  public function add_body_classes( $classes ) {
    // Currently do nothing
  }
  
  public function wpqt_custom_post_type( $args ) {
/*
    global $user_ID;
    get_currentuserinfo();
    
    $current_user_meta = get_user_meta( $user_ID, 'wpqt_qiita_authenticated_user', true );
    if ( isset( $current_user_meta['show_posttype'] ) ) {
      $args['show_in_menu'] = $current_user_meta['show_posttype'];
*/
    if ( isset( $this->options['show_posttype'] ) ) {
      $args['show_in_menu'] = $this->options['show_posttype'];
    } else
    if ( isset( $this->user_options['show_posttype'] ) ) {
      $args['show_in_menu'] = $this->user_options['show_posttype'];
    } else {
      $args['show_in_menu'] = false;
    }
    return $args;
  }
  
  public function wpqt_footer() {
    // Currently do nothing
  }
  
  public function wpqt_print_footer_scripts() {
    // Insert modal dialog html
    if (is_admin() && array_key_exists('page', $this->query) && 'wp-qiita-options' === $this->query['page']) :
?>
<div class="modal fade" id="wpQiitaModal" tabindex="-1" role="dialog" aria-labelledby="wpQiitaModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="wpQiitaModal"><span class="wpqt-qiita-favicon-color"></span> WP Qiita</h4>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', $this->domain_name); ?></button>
        <button type="button" class="btn btn-primary sr-only"><?php _e('Save changes', $this->domain_name); ?></button>
      </div>
    </div>
  </div>
</div>
<?php
    endif;
    
    
  }
  
  public function wpqt_shutdown() {
    // Finish buffering
    $buffer = ob_get_contents();
    ob_get_clean();
    
    if ($buffer) 
      echo $buffer;
    
  }
  
  /**
   * Some hooks (for admin screen only)
   *
   * @since 1.0.0
   * -------------------------------------------------------------------------
   */
  public function wpqt_admin_menu() {
    
    // Filter the user capabilities that is able to configure options for this plugin
    //
    // @since 1.0.0
    $allow_capability = apply_filters( 'wp-qiita/manage_options_capability', 'manage_options' );
    
    add_options_page(
      __('WP Qiita General Options', $this->domain_name), 
      __('WP Qiita', $this->domain_name), 
      $allow_capability, 
      'wp-qiita-options', 
      array( $this, 'wpqt_admin_page_render' )
    );
    
  }
  
  public function wpqt_admin_init() {
    
    register_setting( 'wp-qiita', $this->domain_name );
    
  }
  
  public function modify_plugin_action_links( $links, $file ) {
    
    if (plugin_basename($this->plugin_main_file) !== $file) 
      return $links;
    
    $prepend_new_links = $append_new_links = array();
    
    $prepend_new_links['settings'] = sprintf(
      '<a href="%s">%s</a>', 
      add_query_arg([ 'page' => 'wp-qiita-options' ], admin_url('options-general.php')), 
      __( 'Settings', $this->domain_name )
    );
    
    if (array_key_exists( 'edit', $links )) {
      $append_new_links['edit'] = $links['edit'];
      unset($links['edit']);
    }
    
    /*
    $append_new_links['edit'] = sprintf(
      '<a href="%s">%s</a>', 
      add_query_arg([ 'file' => plugin_basename($this->plugin_main_file)], admin_url('plugin-editor.php')), 
      __( 'Edit', $this->domain_name )
    );
    */
    
    return array_merge( $prepend_new_links, $links, $append_new_links );
    
  }
  
  public function wpqt_admin_notices() {
    
    if (false !== get_transient( $this->message_type['err'] )) {
      $messages = get_transient( $this->message_type['err'] );
      $classes = 'error';
    } elseif (false !== get_transient( $this->message_type['note'] )) {
      $messages = get_transient( $this->message_type['note'] );
      $classes = 'updated';
    }
    
    if (isset($messages) && !empty($messages)) :
?>
    <div id="messages" class="<?php echo $classes; ?>">
      <ul>
      <?php foreach( $messages as $message ): ?>
        <li><?php echo esc_html($message); ?></li>
      <?php endforeach; ?>
      </ul>
    </div>
<?php
    endif;
    
  }
  
  private function register_admin_notices( $code=null, $message, $expire_seconds=10, $is_init=false ) {
    if (empty($code)) 
      $code = $this->message_type['err'];
    
    if (!$this->errors || $is_init) 
      $this->errors = new WP_Error();
    
    if (is_object($this->errors)) {
      $this->errors->add( $code, $message );
      set_transient( $code, $this->errors->get_error_messages(), $expire_seconds );
    }
    
  }
  
  public function get_admin_template() {
    // Currently do nothing
  }
  
  public function wpqt_admin_page_render() {
    // Rendering plugin options screen
    if (array_key_exists('page', $this->query) && 'wp-qiita-options' === $this->query['page']) {
      
      $template_file_path = sprintf('%s%s.php', $this->plugin_dir_path . 'templates/', $this->query['page']);
      
      if (file_exists($template_file_path)) {
        $this->admin_controller();
        
        require_once $template_file_path;
      }
    }
  }
  
  public function admin_controller() {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
/*
    global $user_ID;
    get_currentuserinfo();
    
    $verify_nonce_action = implode('/', array(site_url(), $this->domain_name, $user_ID, $this->query['page']));
*/
    $verify_nonce_action = implode( '/', array( site_url(), $this->domain_name, $this->current_user, $this->query['page'] ) );
    
    if ( ! empty( $GLOBALS['_POST'] ) ) {
      
      if ( check_admin_referer( $verify_nonce_action ) ) {
        // Call the worker method of each tab in admin pages
        $method_elements = array( 'do' );
        if ( isset( $this->query['tab'] ) && ! empty( $this->query['tab'] ) ) {
          $method_elements[] = $this->query['tab'];
        } else
        if ( isset( $GLOBALS['_POST']['active_tab'] ) && ! empty( $GLOBALS['_POST']['active_tab'] ) ) {
          $method_elements[] = $GLOBALS['_POST']['active_tab'];
        }
        if ( isset( $GLOBALS['_POST']['action'] ) && ! empty( $GLOBALS['_POST']['action'] ) ) {
          $method_elements[] = $GLOBALS['_POST']['action'];
        }
        $worker_method = implode( '_', $method_elements );
        if ( method_exists( $this, $worker_method ) ) {
          $this->$worker_method();
        } else {
          // Invalid access
          $_message = __('Invalid access this page.', $this->domain_name);
        }
      } else {
        // Invalid access
        $_message = __('Invalid access this page.', $this->domain_name);
      }
      
    } else
    if ( ! empty( $GLOBALS['_GET'] ) ) {
      // OAuth redirection only
      if ( array_key_exists( 'code', $GLOBALS['_GET'] ) && ! empty( $GLOBALS['_GET']['code'] ) && array_key_exists( 'state', $GLOBALS['_GET'] ) && $_SESSION['activation']['_wpnonce'] === $GLOBALS['_GET']['state'] ) {
        $this->retrieve_access_token( $GLOBALS['_GET']['code'] );
      } else {
        // OAuth error
        # $_message = __('Had occurred error in the authorization process.', $this->domain_name);
      }
      
    } else {
      // Invalid access
      $_message = __('Invalid access this page.', $this->domain_name);
      
    }
    
    if ( ! empty( $_message ) ) 
      $this->register_admin_notices( $_message_type, $_message, 1, true );
    
    $this->wpqt_admin_notices();
    
  }
  
  
  /**
   * Admin Worker methods
   * -------------------------------------------------------------------------
   */
  /**
   * Common submitted data validation filter
   *
   * @return array $submit_data
   */
  public function validate_submit_data() {
    $validations = array(
      'active_tab' => FILTER_SANITIZE_STRING, 
      'action' => FILTER_SANITIZE_STRING, 
      'user_id' => FILTER_VALIDATE_INT, 
      '_wpnonce'  => FILTER_UNSAFE_RAW, 
      '_wp_http_referer' => FILTER_UNSAFE_RAW, 
      'wp-qiita' => array(
        'filter' => FILTER_SANITIZE_STRING, 
        'flags'  => FILTER_REQUIRE_ARRAY, 
      ),
    );
    $submit_data = filter_input_array( INPUT_POST, $validations );
    
    return $submit_data;
  }
  
  /**
   * Tab: activation / Action: activate_oauth
   *
   * @since 1.0.0
   */
  public function do_activation_activate_oauth() {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    $submit_data = $this->validate_submit_data();
    $_SESSION[$submit_data['active_tab']] = array_map( 'stripslashes_deep', $submit_data );
    
    if ( ! array_key_exists( $this->domain_name, $submit_data ) || empty( $submit_data[$this->domain_name] ) ) 
      $_message = __('Required data are not sent.', $this->domain_name);
    
    if ( ! array_key_exists( 'client_id', $submit_data[$this->domain_name] ) || empty( $submit_data[$this->domain_name]['client_id'] ) ) 
      $_message = __('Client ID is not specified.', $this->domain_name);
    
    if ( ! array_key_exists( 'scope', $submit_data[$this->domain_name] ) || ! is_array( $submit_data[$this->domain_name]['scope'] ) || empty( $submit_data[$this->domain_name]['scope'] ) ) 
      $_message = __('Scopes is not specifed', $this->domain_name);
    
    $url = $this->get_api_url( array( 'oauth', 'authorize' ) );
    if ( method_exists( $this, 'oauth_api' ) ) {
      $queries = array(
        'client_id=' . $submit_data[$this->domain_name]['client_id'], 
        'scope=' . implode( '+', $submit_data[$this->domain_name]['scope'] ), 
        'state=' . $submit_data['_wpnonce'], 
      );
      header( 'Location: ' . $url . '?' . implode( '&', $queries ) );
      
    } else {
      // Note: I don't understand why it is not working if calling method in the inheritance original wrapper class, yet.
      //
      // @since 1.0.0
      $response = $this->oauth_api( $submit_data[$this->domain_name]['client_id'], array( 'read_qiita' ) );
    }
    
    if ( ! empty( $_message ) ) 
      $this->register_admin_notices( $_message_type, $_message, 1, true );
    
    return;
  }
  
  /**
   * OAuth handler after redirection in the authorization process
   *
   * @since 1.0.0
   *
   * @param string $code [required]
   * @return 
   */
  public function retrieve_access_token( $code=null ) {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    if ( empty( $code ) ) {
      $_message = __('Alternative code to get access token does not exist.', $this->domain_name);
    } else {
      $url = $this->get_api_url( array( 'access_tokens' ) );
      $body = array(
        'client_id' => $_SESSION['activation'][$this->domain_name]['client_id'], 
        'client_secret' => $_SESSION['activation'][$this->domain_name]['client_secret'], 
        'code' => $code 
      );
      if ( method_exists( $this, 'request_api' ) ) {
        $request_args = array(
          'method' => 'POST', 
          'headers' => array(
            'Content-Type' => 'application/json',
          ),
          'body' => wp_json_encode( $body ),
        );
        $response = wp_remote_request( $url, $request_args );
        
        if ( $this->validate_response_code( $response ) ) {
          // Success
          $_parse_response = json_decode( wp_remote_retrieve_body( $response ) );
          $this->token = $_parse_response->token;
          $_qiita_user_meta = array( 'access_token' => $this->token, 'activated' => true );
          $_current_user_meta = get_user_meta( $submit_data['user_id'], 'wpqt_qiita_authenticated_user', true );
          if ( ! empty( $_current_user_meta ) ) 
            $_qiita_user_meta = array_merge( $_current_user_meta, $_qiita_user_meta );
          
          update_user_meta( $_SESSION['activation']['user_id'], 'wpqt_qiita_authenticated_user', $_qiita_user_meta );
          
          $_message = __('Activation successful. You will work with Qiita.', $this->domain_name);
          $_message_type = $this->message_type['note'];
        } else {
          // Fails
          $_message = sprintf( __('Your request has been response of "%s". Please check again whether there is a miss in the setting options.', $this->domain_name), wp_remote_retrieve_response_message( $response ) );
        }
      }
    }
    
    if ( ! empty( $_message ) ) 
      $this->register_admin_notices( $_message_type, $_message, 1, true );
    
    wp_safe_redirect( admin_url( 'options-general.php?page=wp-qiita-options' ) );
  }
  
  /**
   * Tab: activation / Action: activate_token
   *
   * @since 1.0.0
   */
  public function do_activation_activate_token() {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    $submit_data = $this->validate_submit_data();
    $_SESSION[$submit_data['active_tab']] = array_map( 'stripslashes_deep', $submit_data );
    
    if ( ! array_key_exists( $this->domain_name, $submit_data ) || empty( $submit_data[$this->domain_name] ) ) 
      $_message = __('Required data are not sent.', $this->domain_name);
    
    if ( ! array_key_exists( 'access_token', $submit_data[$this->domain_name] ) || empty( $submit_data[$this->domain_name]['access_token'] ) ) 
      $_message = __('Access token is not specified.', $this->domain_name);
    
    
    $this->token = $submit_data[$this->domain_name]['access_token'];
    $url = $this->get_api_url( array( 'authenticated_user' ) );
    if ( method_exists( $this, 'request_api' ) ) {
      $request_args = array(
        'method' => 'GET', 
        'headers' => array(
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->token, 
        ),
      );
      $response = wp_remote_request( $url, $request_args );
    } else {
      // Note: I don't understand why it is not working if calling method in the inheritance original wrapper class, yet.
      //
      // @since 1.0.0
      $response = $this->request_api( $url, 'get', array() );
    }
    if ( $this->validate_response_code( $response ) ) {
      // Success
      $_parse_response = json_decode( wp_remote_retrieve_body( $response ) );
      $_qiita_user_meta = array_merge( (array) $_parse_response, array( 'access_token' => $this->token, 'activated' => true ) );
      $_current_user_meta = get_user_meta( $submit_data['user_id'], 'wpqt_qiita_authenticated_user', true );
      if ( ! empty( $_current_user_meta ) ) 
        $_qiita_user_meta = array_merge( $_current_user_meta, $_qiita_user_meta );
      update_user_meta( $submit_data['user_id'], 'wpqt_qiita_authenticated_user', $_qiita_user_meta );
      
      $_message = __('Activation successful. You will work with Qiita.', $this->domain_name);
      $_message_type = $this->message_type['note'];
    } else {
      // Fails
      $_message = sprintf(__('Your request has been response of "%s". Please check again whether there is a miss in the access token.', $this->domain_name), wp_remote_retrieve_response_message( $response ));
    }
    
    if (!empty($_message)) 
      $this->register_admin_notices( $_message_type, $_message, 1, true );
    
    return;
  }
  
  /**
   * Tab: activation / Action: inactivate
   *
   * @since 1.0.0
   */
  public function do_activation_inactivate() {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    $submit_data = $this->validate_submit_data();
    unset( $_SESSION[$submit_data['active_tab']] );
    
    $_update_user_meta = get_user_meta( $submit_data['user_id'], 'wpqt_qiita_authenticated_user', true );
    foreach ( $_update_user_meta as $_key => $_val ) {
      if ( in_array( $_key, array( 'activated', 'load_jquery', 'show_posttype', 'autosync', 'autosync_interval', 'remove_post', 'deactivate_qiita' ) ) ) {
        if ( 'activated' === $_key ) 
          $_update_user_meta[$_key] = false;
        if ( 'remove_post' === $_key && $_val ) 
          $this->remove_posts_in_post_type( $this->domain_name, $submit_data['user_id'] );
      } else {
        unset( $_update_user_meta[$_key] );
      }
    }
    if ( update_user_meta( $submit_data['user_id'], 'wpqt_qiita_authenticated_user', $_update_user_meta ) ) {
      // Success
      $_message = __('Inactivation successful.', $this->domain_name);
      $_message_type = $this->message_type['note'];
    } else {
      // Fails
      $_message = __('Inactivation fails.', $this->domain_name);
    }
    
    if (!empty($_message)) 
      $this->register_admin_notices( $_message_type, $_message, 1, true );
    
    return;
  }
  
  /**
   * Tab: activation / Action: advanced_setting
   *
   * @since 1.0.0
   */
  public function do_activation_advanced_setting() {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    $submit_data = $this->validate_submit_data();
    $adv_options = $submit_data[$this->domain_name];
    foreach ( $adv_options as $_key => $_val ) {
      // for boolean var
      if ( in_array( $_key, array( 'load_jquery', 'show_posttype', 'autosync', 'autopost', 'remove_post', 'deactivate_qiita' ) ) ) 
        $adv_options[$_key] = wp_validate_boolean( $_val );
    }
    if ( intval( $adv_options['autosync_interval'] ) < 1 ) {
      $adv_options['autosync_interval'] = 14400; // 60 * 60 * 4 = 4hour
    }
    
    $current_user_meta = get_user_meta( $submit_data['user_id'], 'wpqt_qiita_authenticated_user', true );
    if ( is_array( $current_user_meta ) ) {
      if ( $adv_options['autosync'] ) {
        if ( isset( $current_user_meta['autosync_interval'] ) && intval( $adv_options['autosync_interval'] ) !== intval( $current_user_meta['autosync_interval'] ) ) {
          // Update of autosync schedule
          $adv_options['autosync_hash'] = $this->set_autosync_schedule( $submit_data['user_id'], intval( $adv_options['autosync_interval'] ) );
          $adv_options['autosync_datetime'] = wp_next_scheduled( 'wpqt/autosync', array( $submit_data['user_id'], $adv_options['autosync_hash'] ) );
        }
      } else {
        // Stop of autosync
        $this->stop_autosync_schedule();
      }
      $_new_user_meta = array_merge( $current_user_meta, $adv_options );
      
      if ( update_option( $this->domain_name . '-options', $adv_options ) ) {
        update_user_meta( $submit_data['user_id'], 'wpqt_qiita_authenticated_user', $_new_user_meta );
        // Success
        $_message = __('Configuration has been changed successfully.', $this->domain_name);
        $_message_type = $this->message_type['note'];
      } else {
        // Fails
        $_message = __('Configuration changes did not take place.', $this->domain_name);
      }
      $this->options = $adv_options;
      $this->user_options = $_new_user_meta;
    }
    
    if (!empty($_message)) 
      $this->register_admin_notices( $_message_type, $_message, 1, true );
    
    return;
  }
  
  
  /**
   * Tab: profile / Action: sync_description
   *
   * @since 1.0.0
   */
  public function do_profile_sync_description() {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    $submit_data = $this->validate_submit_data();
    
    $prev_description = get_user_meta( $submit_data['user_id'], 'description', true );
    $new_description = $submit_data[$this->domain_name]['description'];
    if ( update_user_meta( $submit_data['user_id'], 'description', $new_description, $prev_description ) ) {
      // Success
      $_message = __('The profile of Qiita user it was synchronized to WordPress user.', $this->domain_name);
      $_message_type = $this->message_type['note'];
    } else {
      // Fails
      $_message = __('Synchronization of the profile was not done.', $this->domain_name);
    }
    
    if ( ! empty($_message)) 
      $this->register_admin_notices( $_message_type, $_message, 1, true );
    
    return;
  }
  
  /**
   * Tab: profile / Action: reacquire_profile
   *
   * @since 1.0.0
   */
  public function do_profile_reacquire_profile() {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    $submit_data = $this->validate_submit_data();
    
    $this->retrieve_authenticated_user_profile( $submit_data['user_id'] );
    
    if ( count_user_posts( $submit_data['user_id'], $this->domain_name, false) > 0 ) {
/*
      $_contribution = $this->get_contribution( $submit_data['user_id'] );
      $current_user_meta = get_user_meta( $submit_data['user_id'], 'wpqt_qiita_authenticated_user', true );
      $_new_user_meta = array_merge( $current_user_meta, array( 'contribution' => $_contribution ) );
      update_user_meta( $submit_data['user_id'], 'wpqt_qiita_authenticated_user', $_new_user_meta );
*/
      $this->update_user_contribution( $submit_data['user_id'] );
    }
    
    return;
  }
  
  /**
   * Tab: items / Action: initial_sync
   *
   * @since 1.0.0
   */
  public function do_items_initial_sync() {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    $submit_data = $this->validate_submit_data();
    
    $latest_user_stats = $this->retrieve_authenticated_user_profile( $submit_data['user_id'] );
    $total_items = $latest_user_stats['items_count'];
    if ( $total_items > 0 ) {
      // Get Qiita items
      $_api_calls = ceil( $total_items / 100 );
      $_api_calls = $_api_calls > 100 ? 100 : intval( $_api_calls );
      $_current_page = 1;
      $_per_page = 100;
      
      $_timezone = get_option( 'timezone_string' );
      date_default_timezone_set( $_timezone );
      
      while ($_api_calls > 0) {
        $_items = $this->get_authenticated_user_items( $_current_page, $_per_page );
        foreach ($_items as $_i => $_item) {
          $post_id = $this->wpqt_upsert_post( $submit_data['user_id'], $_item );
          
          $_post_meta = array(
            'item_id' => $_item->id, 
            'markdown_body' => $_item->body, 
            'coediting' => strval( $_item->coediting ), 
            'origin_url' => $_item->url, 
            'stocks' => $this->get_item_stocks( $_item->id, $post_id ), 
          );
          foreach ( $_post_meta as $_key => $_value ) {
            update_post_meta( $post_id, 'wpqt_' . $_key, $_value );
          }
        }
        $_current_page++;
        $_api_calls--;
      }
      
      $this->update_user_contribution( $submit_data['user_id'] );
    } else {
      $_message = __('Authenticated user does not have articles to Qiita.', $this->domain_name);
    }
    
    if ( ! empty($_message)) 
      $this->register_admin_notices( $_message_type, $_message, 1, true );
    
    return;
  }
  
  
  /**
   * Tab: items / Action: resync_item
   *
   * @since 1.0.0
   */
  public function do_items_resync_item() {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    $submit_data = $this->validate_submit_data();
    
    $actual_item_id = get_post_meta( $submit_data[$this->domain_name]['post_id'], 'wpqt_item_id', true );
    if ( $actual_item_id === $submit_data[$this->domain_name]['item_id'] ) {
      $_item = $this->get_single_item( $actual_item_id );
      $post_id = $this->wpqt_upsert_post( $submit_data['user_id'], $_item );
      
      $_post_meta = array(
        'item_id' => $_item->id, 
        'markdown_body' => $_item->body, 
        'coediting' => strval( $_item->coediting ), 
        'origin_url' => $_item->url, 
        'stocks' => $this->get_item_stocks( $_item->id, $post_id ), 
      );
      foreach ( $_post_meta as $_key => $_value ) {
        update_post_meta( $post_id, 'wpqt_' . $_key, $_value );
      }
      $this->update_user_contribution( $submit_data['user_id'] );
    }
    
    return;
  }
  
  /**
   * Tab: items / Action: remove_item
   *
   * @since 1.0.0
   */
  public function do_items_remove_item() {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    $submit_data = $this->validate_submit_data();
    
    $actual_item_id = get_post_meta( $submit_data[$this->domain_name]['post_id'], 'wpqt_item_id', true );
    
    if ( $actual_item_id === $submit_data[$this->domain_name]['item_id'] ) {
      if ( wp_delete_post( $submit_data[$this->domain_name]['post_id'] ) ) {
        // Success
        $_message = __('Removed the post that is synchronized to WordPress.', $this->domain_name);
        $_message_type = $this->message_type['note'];
      } else {
        // Fails
        $_message = __('Failed to delete the synchronized post.', $this->domain_name);
      }
    }
    
    if ( ! empty($_message)) 
      $this->register_admin_notices( $_message_type, $_message, 1, true );
    
    return;
  }
  
  
  
  /**
   * Checked whether the current has been activated
   *
   * @since 1.0.0
   *
   * @param int $user_id [required]
   * @return boolean
   */
  public function check_current_activated( $user_id=null ) {
    if (empty($user_id) || intval($user_id) < 1) 
      return false;
    
    $current_user_meta = get_user_meta( $user_id, 'wpqt_qiita_authenticated_user', true );
    if ( is_array( $current_user_meta ) && array_key_exists( 'access_token', $current_user_meta ) && ! empty( $current_user_meta['access_token'] ) ) {
      $this->token = $current_user_meta['access_token'];
      $url = $this->get_api_url( array( 'authenticated_user' ) );
      if ( method_exists( $this, 'request_api' ) ) {
        $request_args = array(
          'method' => 'GET', 
          'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token, 
          ),
        );
        $response = wp_remote_request( $url, $request_args );
      }
      return $this->validate_response_code( $response );
    } else {
      unset( $_SESSION['activation'] );
      return false;
    }
    
  }
  
  /**
   * Retrieve authenticated user profile
   *
   * @since 1.0.0
   *
   * @param int $user_id [required]
   * @return array
   */
  public function retrieve_authenticated_user_profile( $user_id=null ) {
    if (empty($user_id) || intval($user_id) < 1) 
      return false;
    
    $current_user_meta = get_user_meta( $user_id, 'wpqt_qiita_authenticated_user', true );
    if ( is_array( $current_user_meta ) && array_key_exists( 'access_token', $current_user_meta ) && ! empty( $current_user_meta['access_token'] ) ) {
      $this->token = $current_user_meta['access_token'];
      $url = $this->get_api_url( array( 'authenticated_user' ) );
      if ( method_exists( $this, 'request_api' ) ) {
        $request_args = array(
          'method' => 'GET', 
          'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token, 
          ),
        );
        $response = wp_remote_request( $url, $request_args );
      }
      $_parse_response = json_decode( wp_remote_retrieve_body( $response ) );
      $_new_user_meta = array_merge( $current_user_meta, (array)$_parse_response );
      update_user_meta( $user_id, 'wpqt_qiita_authenticated_user', $_new_user_meta );
      
      return $_new_user_meta;
    } else {
      return $current_user_meta;
    }
  }
  
  /**
   * Retrieve authenticated user items
   *
   * @since 1.0.0
   *
   * @param int $page [required] default is 1 (max 100)
   * @param int $per_page [required] default is 20 (max 100)
   * @return array
   */
  public function get_authenticated_user_items( $page=1, $per_page=20 ) {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    if ( empty( $page ) || intval( $page ) < 1 ) {
      $page = 1;
    }
    $page = intval( $page ) > 100 ? 100 : intval( $page );
    if ( empty( $per_page ) || intval( $per_page ) < 1 ) {
      $per_page = 20;
    }
    $per_page = intval( $per_page ) > 100 ? 100 : intval( $per_page );
    
/*
    global $user_ID;
    get_currentuserinfo();
    
    $current_user_meta = get_user_meta( $user_ID, 'wpqt_qiita_authenticated_user', true );
    $this->token = empty( $this->token ) ? $current_user_meta['access_token'] : $this->token;
*/
    $this->token = empty( $this->token ) ? $this->user_options['access_token'] : $this->token;
    $url = $this->get_api_url( array( 'authenticated_user', 'items' ), array( 'page'=>$page, 'per_page'=>$per_page ) );
    if ( method_exists( $this, 'request_api' ) ) {
      $request_args = array(
        'method' => 'GET', 
        'headers' => array(
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->token, 
        ),
      );
      $response = wp_remote_request( $url, $request_args );
    }
    if ( $this->validate_response_code( $response ) ) {
      // Success
      $_parse_response = json_decode( wp_remote_retrieve_body( $response ) );
      $_SESSION['items'] = $_parse_response;
    } else {
      // Fails
      $_parse_response = array();
    }
    
    return $_parse_response;
  }
  
  /**
   * Retrive a item of specific id
   *
   * @since 1.0.0
   *
   * @param string $item_id [required]
   * @return mixed
   */
  public function get_single_item( $item_id=null ) {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    if ( empty( $item_id ) ) 
      return false;
    
/*
    global $user_ID;
    get_currentuserinfo();
    
    $current_user_meta = get_user_meta($user_ID, 'wpqt_qiita_authenticated_user', true);
    $this->token = empty( $this->token ) ? $current_user_meta['access_token'] : $this->token;
*/
    $this->token = empty( $this->token ) ? $this->user_options['access_token'] : $this->token;
    $url = $this->get_api_url( array( 'items', $item_id ) );
    if ( method_exists( $this, 'request_api' ) ) {
      $request_args = array(
        'method' => 'GET', 
        'headers' => array(
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->token, 
        ),
      );
      $response = wp_remote_request( $url, $request_args );
    }
    
    if ( $this->validate_response_code( $response ) ) {
      // Success
      $_parse_response = json_decode( wp_remote_retrieve_body( $response ) );
      $_SESSION['items'] = $_parse_response;
    } else {
      // Fails
      $_parse_response = array();
    }
    
    return $_parse_response;
  }
  
  /**
   * Retrieve authenticated item stocks
   *
   * @since 1.0.0
   *
   * @param string $item_id [required]
   * @param int $post_id [optional] This processing performance is improved when specified the synchronizing post ID.
   * @return int $stocks
   */
  public function get_item_stocks( $item_id=null, $post_id=null ) {
    $_message_type = $this->message_type['err'];
    $_message = null;
    $stocks = 0;
    
    if (empty($item_id)) 
      return $stocks;
    
/*
    global $user_ID;
    get_currentuserinfo();
    
    $current_user_meta = get_user_meta( $user_ID, 'wpqt_qiita_authenticated_user', true );
*/
    if ( empty( $post_id ) || $post_id < 1 ) {
      $_posts = get_posts( array(
        'numberposts' => -1, 
        'post_type' => $this->domain_name, 
        'author' => $this->current_user, 
        'meta_key' => 'wpqt_item_id', 
        'meta_value' => $item_id
      ) );
      if ( ! empty( $_posts ) ) 
        $post_id = $_posts[0]->ID;
    }
    if ( empty( $post_id ) || $post_id < 1 ) 
      return $stocks;
    
    if ( $item_id === get_post_meta( $post_id, 'wpqt_item_id', true ) ) 
      $reference_item_stocks = get_post_meta( $post_id, 'wpqt_stocks', true );
    
    $reference_item_stocks = isset( $reference_item_stocks ) && wp_validate_boolean( $reference_item_stocks ) ? intval($reference_item_stocks) : $stocks;
    //$this->token = empty( $this->token ) ? $current_user_meta['access_token'] : $this->token;
    $this->token = empty( $this->token ) ? $this->user_options['access_token'] : $this->token;
    $start_page = floor( $reference_item_stocks / 100 );
    $start_page = $start_page > 1 ? $start_page - 1 : 1;
    $stocks = ( $start_page - 1 ) * 100;
    
    for ( $i=$start_page; $i<=100; $i++ ) {
      $url = $this->get_api_url( array( 'items', $item_id, 'stockers' ), array( 'page'=>$i, 'per_page'=>100 ) );
      if ( method_exists( $this, 'request_api' ) ) {
        $request_args = array(
          'method' => 'GET', 
          'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token, 
          ),
        );
        $response = wp_remote_request( $url, $request_args );
        if ( $this->validate_response_code( $response ) ) {
          // Success
          $_parse_response = json_decode( wp_remote_retrieve_body( $response ) );
          if ( count( $_parse_response ) > 0 ) {
            $stocks += count( $_parse_response );
          } else {
            break;
          }
        } else {
          // Fails
          break;
        }
      }
    }
    update_post_meta( $post_id, 'wpqt_stocks', $stocks );
    
    return $stocks;
  }
  
  /**
   * Upsert to custom post type `wp-qiita`
   *
   * @since 1.0.0
   *
   * @param int $user_id [required]
   * @param object $qiita_item [required] Object of Qiita articles via `get_authenticated_user_items()`
   * @return mixed Return the post ID if successful in upsert, otherwise is false
   */
  public function wpqt_upsert_post( $user_id=null, $qiita_item=null ) {
    if ( empty( $qiita_item ) || ! is_object( $qiita_item ) ) 
      return false;
    
    $_tags = array();
    if ( ! empty( $qiita_item->tags ) ) {
      foreach ( $qiita_item->tags as $_tagobj ) {
        $_tags[] = $_tagobj->name;
      }
    }
    $_post_atts = array(
      'post_status' => $qiita_item->private ? 'private' : 'publish', 
      'post_type' => $this->domain_name, 
      'post_author' => $user_id, // $submit_data['user_id']
      'post_content' => $qiita_item->rendered_body, 
      'post_title' => $qiita_item->title, 
      'tags_input' => $_tags, 
      'post_name' => $qiita_item->id, 
      'post_date' => date_i18n( 'Y-m-d H:i:s', strtotime( $qiita_item->created_at ), false ),
      'post_date_gmt' => date_i18n( 'Y-m-d H:i:s', strtotime( $qiita_item->created_at ), true ),
      'post_modified' => date_i18n( 'Y-m-d H:i:s', strtotime( $qiita_item->updated_at ), false ),
      'post_modified_gmt' => date_i18n( 'Y-m-d H:i:s', strtotime( $qiita_item->updated_at ), true ),
      'guid' => $qiita_item->url, 
      'post_content_filtered' => $qiita_item->body
    );
    
    if ( count_user_posts( $user_id, $this->domain_name, false ) > 0 ) {
      $_posts = get_posts( array(
        'numberposts' => -1, 
        'post_type' => $this->domain_name, 
        'author' => $user_id, 
        'meta_key' => 'wpqt_item_id', 
        'meta_value' => $qiita_item->id
      ) );
      if ( ! empty( $_posts ) ) {
        $_post_atts['ID'] = $_posts[0]->ID;
      }
    }
    
    $post_id = wp_insert_post( $_post_atts, false );
    
    return $post_id !== 0 ? $post_id : false;
  }
  
  /**
   * Get contribution
   *
   * @since 1.0.0
   *
   * @param int $user_id [required]
   * @return mixed
   */
  public function get_contribution( $user_id=null ){
    if ( empty( $user_id ) || intval( $user_id ) < 1 || count_user_posts( $user_id, $this->domain_name, false) === 0 ) 
      return false;
    
    $_posts = get_posts( array(
      'numberposts' => -1, 
      'post_type' => $this->domain_name, 
      'author' => $user_id
    ) );
    if ( ! empty( $_posts ) ) {
      $contribution = 0;
      foreach ( $_posts as $_post ) {
        $contribution += intval( get_post_meta( $_post->ID, 'wpqt_stocks', true ) );
      }
    }
    
    update_user_meta( $user_id, 'contribution', $contribution );
    
    return $contribution;
  }
  
  /**
   * Update contribution
   *
   * @since 1.0.0
   *
   * @param int $user_id [required]
   * @return void
   */
  public function update_user_contribution( $user_id=null ) {
    if ( empty( $user_id ) || intval( $user_id ) < 1 ) 
      $user_id = $this->current_user;
    
    $this->user_options['contribution'] = $this->get_contribution( $user_id );
    update_user_meta( $user_id, 'wpqt_qiita_authenticated_user', $this->user_options );
    
  }
  
  /**
   * Tab: items / Action: resync_all
   *
   * @since 1.0.0
   */
  public function do_items_resync_all() {
    
    return $this->do_items_initial_sync();
    
  }
  
  /**
   * Create post type for this plugin
   *
   * @since 1.0.0
   */
  public function create_post_type() {
    $post_type = $this->domain_name;
    
    $labels = array(
      'name'               => __( 'Qiita Articles', $this->domain_name ), // post type general name
      'singular_name'      => __( 'Qiita Article', $this->domain_name ), // post type singular name
      'menu_name'          => __( 'Qiita Articles', $this->domain_name ), // admin menu
      'name_admin_bar'     => __( 'Qiita Article', $this->domain_name ), // add new on admin bar
      'add_new'            => __( 'Add New', $this->domain_name ),
      'add_new_item'       => __( 'Add New Article', $this->domain_name ),
      'new_item'           => __( 'New Article', $this->domain_name ),
      'edit_item'          => __( 'Edit Article', $this->domain_name ),
      'view_item'          => __( 'View Article', $this->domain_name ),
      'all_items'          => __( 'All Articles', $this->domain_name ),
      'search_items'       => __( 'Search Articles', $this->domain_name ),
      'parent_item_colon'  => __( 'Parent Articles:', $this->domain_name ),
      'not_found'          => __( 'No Articles found.', $this->domain_name ),
      'not_found_in_trash' => __( 'No Articles found in Trash.', $this->domain_name )
    );
    
    $args = array(
      'labels'             => $labels,
      'description'        => __( 'Articles that are synchronized from Qiita.', $this->domain_name ),
      'public'             => true,
      'publicly_queryable' => true,
      'show_ui'            => true,
      'show_in_menu'       => false,
      'query_var'          => true,
      'rewrite'            => array( 'slug' => $this->domain_name ),
      'capability_type'    => 'post',
      'taxonomies'        => array( 'post_tag' ),
      'has_archive'        => true,
      'hierarchical'       => false,
      'menu_position'      => null,
      'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments' )
    );
    // Filter the registration definitions of custom post type
    //
    // @since 1.0.0
    $args = apply_filters( 'wp-qiita/register_post_type', $args );
    
    register_post_type( $post_type, $args );
  }
  
  /**
   * Remove posts in specific post type
   *
   * @since 1.0.0
   *
   * @param string $post_type [required]
   * @param int $user_id [optional] Note: If not specified post of all users will be subject to.
   * @return void
   */
  public function remove_posts_in_post_type( $post_type=null, $user_id=null ) {
    if ( empty( $post_type ) || ! post_type_exists( $post_type ) ) 
      return;
    
    $_posts_to_be_deleted_args = array(
      'numberposts' => -1, 
      'post_type' => $post_type, 
    );
    if ( ! empty( $user_id ) && intval( $user_id ) > 0 ) {
      $_posts_to_be_deleted_args['author'] = intval( $user_id );
    }
    
    $_posts = get_posts( $_posts_to_be_deleted_args );
    if ( ! empty( $_posts ) ) {
      foreach ( $_posts as $_post ) {
        wp_delete_post( $_post->ID );
      }
    }
    
  }
  
  /**
   * Global Hooks for this plugin
   * -------------------------------------------------------------------------
   */
  /**
   * Fire an action at the time this plugin has activated.
   *
   * since 1.0.0
   */
  public function plugin_activate() {
    if ( ! current_user_can( 'activate_plugins' ) ) 
      return;
    
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "activate-plugin_{$plugin}" );
    ob_start();
    
    if ( $this->check_plugin_env() ) {
      
      // Add rewrite rules
      #$this->prepend_rewrite_rules();
      #flush_rewrite_rules();
      
      $message = sprintf(__('Function called: %s; %s', $this->domain_name), __FUNCTION__, __('WP Qiita plugin has activated.', $this->domain_name));
      $this->logger( $message );
      
    } else {
      deactivate_plugins( $this->plugin_main_file );
    }
  }
  
  /**
   * Operating environment check for this plugin
   *
   * @since 1.0.0
   *
   * @return boolean
   */
  private function check_plugin_env() {
    
    $php_min_version = '5.3';
    $extensions = array(
      'mbstring', 
    );
    
    $php_current_version = phpversion();
    $this->errors = new WP_Error();
    
    if (version_compare( $php_min_version, $php_current_version, '>=' )) 
      $this->errors->add('php_version_error', sprintf(__('Your server is running PHP version %s but this plugin requires at least PHP %s. Please run an upgrade.', $this->domain_name), $php_current_version, $php_min_version));
    
    foreach ($extensions as $extension) {
      if (!extension_loaded($extension)) 
        $this->errors->add('lack_extension_error', sprintf(__('Please install the extension %s to run this plugin.', $this->domain_name), $extension));
    }
    
    $message = $this->errors->get_error_message();
    if (!is_wp_error($this->errors) || empty($message)) {
      return true;
    }
    
    unset( $_GET['activate'] );
    
    $this->logger( $message );
    
    printf( '<div class="error"><p>%s</p><p>%s</p></div>', $message, sprintf(__('The %s has been deactivated.', $this->domain_name), __('WP Qiita', $this->domain_name)) );
    
    return false;
    
  }
  
  /**
   * Fire an action at the time this plugin was deactivation.
   *
   * since 1.0.0
   */
  public function plugin_deactivation() {
    if ( ! current_user_can( 'activate_plugins' ) ) 
      return;
    
/*
    global $user_ID;
    get_currentuserinfo();
    
    $_update_user_meta = get_user_meta( $user_ID, 'wpqt_qiita_authenticated_user', true );
    if ( isset( $_update_user_meta['deactivate_qiita'] ) && $_update_user_meta['deactivate_qiita'] ) {
      foreach ( $_update_user_meta as $_key => $_val ) {
        if ( in_array( $_key, array( 'activated', 'load_jquery', 'show_posttype', 'autosync', 'autosync_interval', 'remove_post', 'deactivate_qiita' ) ) ) {
          if ( 'activated' === $_key ) 
            $_update_user_meta[$_key] = false;
        } else {
          unset( $_update_user_meta[$_key] );
        }
      }
      update_user_meta( $user_ID, 'wpqt_qiita_authenticated_user', $_update_user_meta );
      $this->remove_posts_in_post_type( $this->domain_name, $user_ID );
    }
*/
    if ( isset( $this->options['deactivate_qiita'] ) && $this->options['deactivate_qiita'] ) {
      foreach ( $this->options as $_key => $_val ) {
        if ( in_array( $_key, array( 'activated', 'load_jquery', 'show_posttype', 'autosync', 'autosync_interval', 'remove_post', 'deactivate_qiita' ) ) ) {
          if ( 'activated' === $_key ) {
            $this->options[$_key] = false;
            $this->user_options[$_key] = false;
          }
        } else {
          unset( $this->options[$_key], $this->user_options[$_key] );
        }
      }
      update_option( $this->domain_name . '-options', $this->options );
      update_user_meta( $this->current_user, 'wpqt_qiita_authenticated_user', $this->user_options );
      $this->remove_posts_in_post_type( $this->domain_name, $this->current_user );
    }
    
    $message = sprintf(__('Function called: %s; %s', $this->domain_name), __FUNCTION__, __('WP Qiita plugin has been deactivation.', $this->domain_name));
    $this->logger( $message );
    
    // Delete rewrite rules
    #flush_rewrite_rules();
  }
  
  /**
   * Fire an action before this plugin will be uninstall.
   *
   * since 1.0.0
   */
  public static function plugin_uninstall() {
    if ( ! current_user_can( 'activate_plagins' ) ) 
      return;
    
    check_admin_referer( 'bulk-plugins' );
    
    if ( $this->plugin_main_file !== WP_UNINSTALL_PLUGIN ) 
      return;
    
/*
    global $user_ID;
    get_currentuserinfo();
    delete_user_meta( $user_ID, 'wpqt_qiita_authenticated_user' );
*/
    delete_user_meta( $this->current_user, 'wpqt_qiita_authenticated_user' );
    $this->remove_posts_in_post_type( $this->domain_name );
    
    $message = sprintf(__('Function called: %s; %s', $this->domain_name), __FUNCTION__, __('WP Qiita plugin uninstall now.', $this->domain_name));
    $this->logger( $message );
    
    // Delete rewrite rules
    #flush_rewrite_rules();
  }
  
  /**
   * Add the extended rule for requesting api.
   *
   * @since 1.0.0
   */
  public function prepend_rewrite_rules() {
    
    //add_rewrite_rule( '^wpqt-oauth/([^\?]*)$', 'wp-admin/options-general.php?$matches[1]', 'top' );
    
  }
  
  /**
   * Checking whether to load this plugin assets (at frontend only)
   *
   * @since 1.0.0
   *
   * @param array $widgets [optional] array of widget id_bases that you want to check
   * @param array $shortcodes [optional] array of shortcode name that you want to check
   * @return boolean
   */
  public function check_whether_loading_assets( $widgets=array(), $shortcodes=array() ) {
    if ( is_admin() ) 
      return false;
    
    $_should_load = false;
    if ( ! empty( $widgets ) ) {
      foreach ( $widgets as $_widget ) {
        $_check = is_active_widget( false, false, $_widget, true );
        if ( $_check ) {
          $_should_load = true;
          break;
        }
      }
    }
    if ( ! $_should_load && ! empty( $shortcodes ) ) {
      global $post;
      if ( is_a( $post, 'WP_Post' ) ) {
        foreach ( $shortcodes as $_shortcode ) {
          if ( has_shortcode( $post->post_content, $_shortcode ) ) {
            $_should_load = true;
            break;
          }
        }
      }
    }
    
    return $_should_load;
  }
  
  /**
   * Set autosync schedule
   *
   * @since 1.0.0
   *
   * @param int $user_id [required]
   * @param int $interval [required]
   * @return string $hash
   */
  public function set_autosync_schedule( $user_id=null, $interval=0 ) {
    /*
    if ( ! in_array( $interval, array( 'hourly', 'twicedaily', 'daily' ) ) ) 
      $interval = 'daily';
    */
    $_timezone = get_option( 'timezone_string' );
    date_default_timezone_set( $_timezone );
    
    $_now = time();
    $_hash = md5( $user_id . $_now . wp_rand() );
    //wp_schedule_event( $_now, $interval, 'wpqt/autosync', array( $user_id, $_hash ) );
    wp_schedule_single_event( $_now + $interval, 'wpqt/autosync', array( $user_id, $_hash ) );
    
    return $_hash;
  }
  
  /**
   * Stop all autosync schedule
   *
   * @since 1.0.0
   */
  public function stop_autosync_schedule() {
    
    $users = get_users( array( 'fields' => array( 'ID' ) ) );
    foreach ( $users as $_user ) {
      $last_hash = isset( $this->options['autosync_hash'] ) && ! empty( $this->options['autosync_hash'] ) ? $this->options['autosync_hash'] : null;
      if ( empty( $last_hash ) ) {
        $_user_meta = get_user_meta( intval( $_user->ID ), 'wpqt_qiita_authenticated_user', true );
        if ( $_user_meta && isset( $_user_meta['autosync_hash'] ) ) 
          $last_hash = $_user_meta['autosync_hash'];
      }
      if ( ! empty( $last_hash ) && wp_next_scheduled( 'wpqt/autosync', array( intval( $_user->ID ), $last_hash ) ) ) 
        wp_clear_scheduled_hook( 'wpqt/autosync', array( intval( $_user->ID ), $last_hash ) );
    }
  }
  
  /**
   * Run autosync
   *
   * @since 1.0.0
   *
   * @param int $user_id [required]
   * @param string $hash [required]
   * @return void
   */
  public function wpqt_autosync( $user_id=null, $hash=null ) {
    if ( empty( $user_id ) ) 
      return;
    
    if ( empty( $hash ) ) 
      $hash = $this->options['autosync_hash'];
    
    $_timezone = get_option( 'timezone_string' );
    date_default_timezone_set( $_timezone );
    
    $current_user_meta = get_user_meta( $user_id, 'wpqt_qiita_authenticated_user', true );
    $last_autosync_datetime = $this->options['autosync_datetime'];
    if ( is_array( $current_user_meta ) && array_key_exists( 'access_token', $current_user_meta ) && ! empty( $current_user_meta['access_token'] ) ) {
      // Autosync processes
      
      $latest_user_stats = $this->retrieve_authenticated_user_profile( $user_id );
      $total_items = $latest_user_stats['items_count'];
      if ( $total_items > 0 ) {
        // Get Qiita items
        $_api_calls = ceil( $total_items / 100 );
        $_api_calls = $_api_calls > 100 ? 100 : intval( $_api_calls );
        $_current_page = 1;
        $_per_page = 100;
        
        $_timezone = get_option( 'timezone_string' );
        date_default_timezone_set( $_timezone );
        
        while ($_api_calls > 0) {
          $_items = $this->get_authenticated_user_items( $_current_page, $_per_page );
          foreach ($_items as $_i => $_item) {
            $post_id = $this->wpqt_upsert_post( $user_id, $_item );
            
            $_post_meta = array(
              'item_id' => $_item->id, 
              'markdown_body' => $_item->body, 
              'coediting' => strval( $_item->coediting ), 
              'origin_url' => $_item->url, 
              'stocks' => $this->get_item_stocks( $_item->id, $post_id ), 
            );
            foreach ( $_post_meta as $_key => $_value ) {
              update_post_meta( $post_id, 'wpqt_' . $_key, $_value );
            }
          }
          $_current_page++;
          $_api_calls--;
        }
        $this->update_user_contribution( $user_id );
      }
      if ( $this->debug_mode ) 
        $this->logger( time() . ' Autosync done!', 3 );
      
      if ( wp_next_scheduled( 'wpqt/autosync', array( $user_id, $hash ) ) ) {
        // Remove current schedule
        wp_unschedule_event( $last_autosync_datetime, 'wpqt/autosync', array( $user_id, $hash ) );
      }
      
      // Set new schedule
      $_new_hash = $this->set_autosync_schedule( $user_id, $this->options['autosync_interval'] );
      $_autosync_datetime = wp_next_scheduled( 'wpqt/autosync', array( $user_id, $_new_hash ) );
      $update_autosync = array( 'autosync_datetime' => $_autosync_datetime, 'autosync_hash' => $_new_hash );
      $this->options = array_merge( $this->options, $update_autosync );
      update_option( $this->domain_name . '-options', $this->options );
    }
    
    return;
  }
  
  
  /**
   * Logger for this plugin
   *
   * @since 1.0.0
   *
   * @param string $message
   * @param integer $logging_type 0: php system logger, 1: mail to $distination, 3: overwriting file of $distination (default), 4: to SAPI handler
   * @param string $distination
   * @return mixed Return false if logging fails, or otherwise void
   */
  public function logger( $message='', $logging_type=3, $distination='' ) {
    
    $this->logger_cache = $message;
    
    if (!$this->debug_mode) 
      return;
    
    if (empty($message) || '' === trim($message)) {
      $message = $this->errors->get_error_message();
      if (!is_wp_error($this->errors) || empty($message)) 
        return;
      
      // Filter logging message
      // 
      // @since 1.0.0
      $message = apply_filters( 'wp-qiita/log_message', $message, $this->errors );
    }
    
    if (!in_array(intval($logging_type), [ 0, 1, 3, 4 ])) 
      $logging_type = 3;
    
    $_timezone = get_option( 'timezone_string' );
    date_default_timezone_set( $_timezone );
    
    $current_datetime = date( 'Y-m-d H:i:s', time() );
    $message = preg_replace( '/(?:\n|\r|\r\n)/', ' ', trim( $message ) );
    $log_message = sprintf( "[%s] %s\n", $current_datetime, $message );
    
    if (3 == intval($logging_type)) {
      $this->log_distination_path = empty($message) || '' === trim($distination) ? $this->plugin_dir_path . 'debug.log' : $distination;
      // Filter logging distination path
      //
      // @since 1.0.0
      $this->log_distination_path = apply_filters( 'wp-qiita/log_distination_path', $this->log_distination_path );
    }
    
    if (false === error_log( $log_message, $logging_type, $this->log_distination_path )) {
      $this->errors = new WP_Error();
      $this->errors->add( 'logging_error', __('Failed to logging.', $this->domain_name) );
      return false;
    } else {
      return;
    }
  }
  
  
}

endif; // end of class_exists()