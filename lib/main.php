<?php
defined( 'WPQT' ) OR wp_die();

if ( class_exists( 'WpQiitaUtils' ) ) :

final class WpQiitaMain extends WpQiitaUtils {
  
  var $version;
  var $plugin_enabled;
  var $options;
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
    $this->options = get_option($this->domain_name . '-options', array());
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
    // Currently do nothing
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
    add_action( 'widget_init', array( $this, 'wpqt_widget' ) );
    add_action( 'wp_loaded', array( $this, 'wpqt_wp_loaded' ) ); // Fired once WordPress, all plugins, and the theme are fully loaded
    
    if (is_admin()) {
      add_action( 'admin_menu', array( $this, 'wpqt_admin_menu' ) );
      add_action( 'admin_init', array( $this, 'wpqt_admin_init' ) );
      add_action( 'pre_get_posts', array( $this, 'wpqt_pre_get_posts' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'wpqt_enqueue_scripts' ) );
      add_action( 'admin_head', array( $this, 'wpqt_head' ) );
      add_action( 'admin_notices', array( $this, 'wpqt_admin_notices' ) );
      # do_action( 'wp-qiita/get_admin_template', array( $this, 'get_admin_template') ); // Add New Action
      add_action( 'admin_footer', array( $this, 'wpqt_footer' ) );
      add_action( 'admin_print_footer_scripts', array( $this, 'wpqt_print_footer_scripts' ) ); // For modal insertion
      
      // Filters
      add_filter( 'plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );
      add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );
      
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
    
    // Whether this plugin has been loaded
    $this->plugin_enabled = true;
    
  }
  
  public function wpqt_init() {
    // Set ajax action name
    $this->plugin_ajax_action = 'wpqt_ajax_handler';
    
    // Session initialinze
    if (!session_id()) 
      @session_start();
    
    // Start output buffering
    ob_start();
    
    // Set current query strings
    if (is_admin()) {
      wp_parse_str( $_SERVER['QUERY_STRING'], $this->query );
    } else {
      $this->query = $GLOBALS['_REQUEST'];
    }
    
  }
  
  public function wpqt_widget() {
    // Currently do nothing
  }
  
  public function wpqt_wp_loaded() {
    // Currently do nothing
    
  }
  
  public function wpqt_pre_get_posts() {
    // Currently do nothing
  }
  
  public function wpqt_enqueue_scripts() {
    // Load this plugin assets
    $assets = array(
      'styles' => array(
        'bootstrap-style-cdn' => array( '//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css', array(), '3.3.5', 'all' ), 
        'wpqt-style' => array( $this->plugin_dir_url . 'assets/styles/wpqt.css', array(), $this->version, 'all' ), 
      ), 
      'scripts' => array(
        'jquery-cdn' => array( '//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js', array(), '1.11.3', true ), 
        'bootstrap-script-cdn' => array( '//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js', array(), '3.3.5', true ), 
        'blockchain' => array( 'https://blockchain.info/Resources/wallet/pay-now-button.js', array('jquery-cdn'), null, true ), 
        'wpqt-script' => array( $this->plugin_dir_url . 'assets/scripts/wpqt.js', array(), $this->version, true ), 
      )
    );
    // Filter the assets to be importing in page (before registration)
    //
    // @since 1.0.0
    $assets = apply_filters( 'wp-qiita/enqueue_assets', $assets, $this->query );
    
    if (is_admin()) {
      if ( array_key_exists('page', $this->query) && 'wp-qiita-options' === $this->query['page'] ) {
        wp_deregister_script('jquery');
      } else {
        unset( $assets['styles']['bootstrap-style-cdn'], $assets['scripts']['jquery'], $assets['scripts']['bootstrap-script-cdn'], $assets['scripts']['blockchain'] );
      }
    }
    
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
    // 
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
    
    global $user_ID;
    get_currentuserinfo();
    
    $verify_nonce_action = implode('/', array(site_url(), $this->domain_name, $user_ID, $this->query['page']));
    
    if (!empty($GLOBALS['_POST'])) {
      
      if (check_admin_referer( $verify_nonce_action )) {
        // Call the worker method of each tab in admin pages
        $method_elements = array('do');
        if (isset($this->query['tab']) && !empty($this->query['tab'])) {
          $method_elements[] = $this->query['tab'];
        } elseif (isset($GLOBALS['_POST']['active_tab']) && !empty($GLOBALS['_POST']['active_tab'])) {
          $method_elements[] = $GLOBALS['_POST']['active_tab'];
        }
        if (isset($GLOBALS['_POST']['action']) && !empty($GLOBALS['_POST']['action'])) {
          $method_elements[] = $GLOBALS['_POST']['action'];
        }
        $worker_method = implode('_', $method_elements);
        if (method_exists($this, $worker_method)) {
          $this->$worker_method();
        } else {
          // Invalid access
          $_message = __('Invalid access this page.', $this->domain_name);
        }
      } else {
        // Invalid access
        $_message = __('Invalid access this page.', $this->domain_name);
      }
      
    } elseif (!empty($GLOBALS['_GET'])) {
      // OAuth redirection only
      if (array_key_exists('code', $GLOBALS['_GET']) && !empty($GLOBALS['_GET']['code']) && array_key_exists('state', $GLOBALS['_GET']) && $_SESSION['activation']['_wpnonce'] === $GLOBALS['_GET']['state']) {
        $this->retrieve_access_token( $GLOBALS['_GET']['code'] );
      } else {
        // OAuth error
        # $_message = __('Had occurred error in the authorization process.', $this->domain_name);
      }
      
    } else {
      // Invalid access
      $_message = __('Invalid access this page.', $this->domain_name);
      
    }
    
    if (!empty($_message)) 
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
    $submit_data = filter_input_array(INPUT_POST, $validations);
    
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
    
    if (!array_key_exists($this->domain_name, $submit_data) || empty($submit_data[$this->domain_name])) 
      $_message = __('Required data are not sent.', $this->domain_name);
    
    if (!array_key_exists('client_id', $submit_data[$this->domain_name]) || empty($submit_data[$this->domain_name]['client_id'])) 
      $_message = __('Client ID is not specified.', $this->domain_name);
    
    if (!array_key_exists('scope', $submit_data[$this->domain_name]) || !is_array($submit_data[$this->domain_name]['scope']) || empty($submit_data[$this->domain_name]['scope'])) 
      $_message = __('Scopes is not specifed', $this->domain_name);
    
    $url = $this->get_api_url(array( 'oauth', 'authorize' ));
    if (method_exists($this, 'oauth_api')) {
      $queries = array(
        'client_id=' . $submit_data[$this->domain_name]['client_id'], 
        'scope=' . implode('+', $submit_data[$this->domain_name]['scope']), 
        'state=' . $submit_data['_wpnonce'], 
      );
      header('Location: ' . $url . '?' . implode('&', $queries));
      
    } else {
      // Note: I don't understand why it is not working if calling method in the inheritance original wrapper class, yet.
      //
      // @since 1.0.0
      $response = $this->oauth_api( $submit_data[$this->domain_name]['client_id'], array('read_qiita') );
    }
    
    if (!empty($_message)) 
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
    
    if (empty($code)) {
      $_message = __('Alternative code to get access token does not exist.', $this->domain_name);
    } else {
      $url = $this->get_api_url(array('access_tokens'));
      $body = array(
        'client_id' => $_SESSION['activation'][$this->domain_name]['client_id'], 
        'client_secret' => $_SESSION['activation'][$this->domain_name]['client_secret'], 
        'code' => $code 
      );
      if (method_exists($this, 'request_api')) {
        $request_args = array(
          'method' => 'POST', 
          'headers' => array(
            'Content-Type' => 'application/json',
          ),
          'body' => wp_json_encode( $body ),
        );
        $response = wp_remote_request( $url, $request_args );
        
        if ($this->validate_response_code($response)) {
          // Success
          $_parse_response = json_decode(wp_remote_retrieve_body( $response ));
          $this->token = $_parse_response->token;
          $_qiita_user_meta = array( 'access_token' => $this->token, 'activated' => true );
          
          update_user_meta($_SESSION['activation']['user_id'], 'wpqt_qiita_authenticated_user', $_qiita_user_meta);
          
          $_message = __('Activation successful. You will work with Qiita.', $this->domain_name);
          $_message_type = $this->message_type['note'];
        } else {
          // Fails
          $_message = sprintf(__('Your request has been response of "%s". Please check again whether there is a miss in the setting options.', $this->domain_name), wp_remote_retrieve_response_message( $response ));
        }
      }
    }
    
    if (!empty($_message)) 
      $this->register_admin_notices( $_message_type, $_message, 1, true );
    
    wp_safe_redirect( admin_url('options-general.php?page=wp-qiita-options') );
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
    
    if (!array_key_exists($this->domain_name, $submit_data) || empty($submit_data[$this->domain_name])) 
      $_message = __('Required data are not sent.', $this->domain_name);
    
    if (!array_key_exists('access_token', $submit_data[$this->domain_name]) || empty($submit_data[$this->domain_name]['access_token'])) 
      $_message = __('Access token is not specified.', $this->domain_name);
    
    
    $this->token = $submit_data[$this->domain_name]['access_token'];
    $url = $this->get_api_url(array('authenticated_user'));
    if (method_exists($this, 'request_api')) {
      $request_args = array(
        'method' => 'GET', 
        'headers' => array(
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->token, 
        ),
      );
      $response = wp_remote_request( $url, $request_args);
    } else {
      // Note: I don't understand why it is not working if calling method in the inheritance original wrapper class, yet.
      //
      // @since 1.0.0
      $response = $this->request_api( $url, 'get', array() );
    }
    # var_dump([wp_remote_retrieve_body( $response ), wp_remote_retrieve_headers( $response ), wp_remote_retrieve_header( $response, 'status' ), wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) ]);
    if ($this->validate_response_code($response)) {
      // Success
      $_parse_response = json_decode(wp_remote_retrieve_body( $response ));
      $_qiita_user_meta = array_merge((array)$_parse_response, array( 'access_token' => $this->token, 'activated' => true ));
      update_user_meta($submit_data['user_id'], 'wpqt_qiita_authenticated_user', $_qiita_user_meta);
      
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
    unset($_SESSION[$submit_data['active_tab']]);
    
    if (update_user_meta($submit_data['user_id'], 'wpqt_qiita_authenticated_user', array())) {
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
    
    $current_user_meta = get_user_meta($user_id, 'wpqt_qiita_authenticated_user', true);
    if (is_array($current_user_meta) && array_key_exists('access_token', $current_user_meta) && !empty($current_user_meta['access_token'])) {
      $this->token = $current_user_meta['access_token'];
      $url = $this->get_api_url(array('authenticated_user'));
      if (method_exists($this, 'request_api')) {
        $request_args = array(
          'method' => 'GET', 
          'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token, 
          ),
        );
        $response = wp_remote_request( $url, $request_args);
      }
      return $this->validate_response_code($response);
    } else {
      unset($_SESSION['activation']);
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
    
    $current_user_meta = get_user_meta($user_id, 'wpqt_qiita_authenticated_user', true);
    if (is_array($current_user_meta) && array_key_exists('access_token', $current_user_meta) && !empty($current_user_meta['access_token'])) {
      $this->token = $current_user_meta['access_token'];
      $url = $this->get_api_url(array('authenticated_user'));
      if (method_exists($this, 'request_api')) {
        $request_args = array(
          'method' => 'GET', 
          'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token, 
          ),
        );
        $response = wp_remote_request( $url, $request_args );
      }
      $_parse_response = json_decode(wp_remote_retrieve_body( $response ));
      $_new_user_meta = array_merge($current_user_meta, (array)$_parse_response);
      update_user_meta($user_id, 'wpqt_qiita_authenticated_user', $_new_user_meta);
      
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
    
    if (empty($page) || intval($page) < 1 || intval($page) > 100) 
      $page = 1;
    if (empty($per_page) || intval($per_page) < 1 || intval($per_page) > 100) 
      $per_page = 20;
    
    global $user_ID;
    get_currentuserinfo();
    
    $current_user_meta = get_user_meta($user_ID, 'wpqt_qiita_authenticated_user', true);
    $this->token = empty($this->token) ? $current_user_meta['access_token'] : $this->token;
    $url = $this->get_api_url( array( 'authenticated_user', 'items' ), array( 'page'=>$page, 'per_page'=>$per_page ));
    if (method_exists($this, 'request_api')) {
      $request_args = array(
        'method' => 'GET', 
        'headers' => array(
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->token, 
        ),
      );
      $response = wp_remote_request( $url, $request_args );
    }
    if ($this->validate_response_code($response)) {
      // Success
      $_parse_response = json_decode(wp_remote_retrieve_body( $response ));
      $_SESSION['items'] = $_parse_response;
    } else {
      // Fails
      $_parse_response = array();
    }
    
    return $_parse_response;
  }
  
  /**
   * Retrieve authenticated user items
   *
   * @since 1.0.0
   *
   * @param string $item_id [required]
   * @return int
   */
  public function get_item_stocks( $item_id=null ) {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    if (empty($item_id)) 
      return 0;
    
    global $user_ID;
    get_currentuserinfo();
    
    $current_user_meta = get_user_meta($user_ID, 'wpqt_qiita_authenticated_user', true);
    $reference_item_stocks = get_user_meta($user_ID, 'wpqt_qiita_item_stocks_cache', true);
    $this->token = empty($this->token) ? $current_user_meta['access_token'] : $this->token;
    if (!empty($reference_item_stocks) && is_array($reference_item_stocks) && array_key_exists($item_id, $reference_item_stocks)) {
      $start_page = floor(intval($reference_item_stocks[$item_id]) / 100);
      $start_page = $start_page > 1 ? $start_page - 1 : 1;
      $stocks = ($start_page - 1) * 100;
    } else {
      $start_page = 1;
      $stocks = 0;
    }
    for ($i=$start_page; $i<=100; $i++) {
      $url = $this->get_api_url( array( 'items', $item_id, 'stockers' ), array( 'page'=>$i, 'per_page'=>100 ));
      if (method_exists($this, 'request_api')) {
        $request_args = array(
          'method' => 'GET', 
          'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token, 
          ),
        );
        $response = wp_remote_request( $url, $request_args );
        if ($this->validate_response_code($response)) {
          // Success
          $_parse_response = json_decode(wp_remote_retrieve_body( $response ));
          if (count($_parse_response) > 0) {
            $stocks += count($_parse_response);
          } else {
            break;
          }
        } else {
          // Fails
          break;
        }
      }
    }
    // Various caching process
    if (array_key_exists('items', $_SESSION) && !empty($_SESSION['items'])) {
      foreach ($_SESSION['items'] as $_cache_item) {
        if ($_cache_item->id === $item_id) {
          $_cache_item->stocks = $stocks;
          break;
        }
      }
    }
    if (empty($reference_item_stocks) || !is_array($reference_item_stocks)) 
      $reference_item_stocks = array();
    
    $reference_item_stocks[$item_id] = $stocks;
    update_user_meta($user_ID, 'wpqt_qiita_item_stocks_cache', $reference_item_stocks);
    
    return $stocks;
  }
  
  /**
   * Tab: items / Action: reload_items
   *
   * @since 1.0.0
   */
  public function do_items_reload_items() {
    $_message_type = $this->message_type['err'];
    $_message = null;
    
    $submit_data = $this->validate_submit_data();
    unset($_SESSION[$submit_data['active_tab']]);
    
    return;
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
    if (!current_user_can('activate_plugins') || $this->plugin_enabled) 
      return;
    
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "activate-plugin_{$plugin}" );
    ob_start();
    
    if ($this->plugin_enabled = $this->check_plugin_env()) {
      
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
    $extensions = [
      'mbstring', 
    ];
    
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
    if (!current_user_can('activate_plugins') || !$this->plugin_enabled) 
      return;
    
    $this->plugin_enabled = false;
    
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
    if ( !current_user_can( 'activate_plagins' ) ) 
      return;
    
    check_admin_referer( 'bulk-plugins' );
    
    if ( $this->plugin_main_file != WP_UNINSTALL_PLUGIN ) 
      return;
    
    $this->plugin_enabled = false;
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
    
    $current_datetime = date('Y-m-d H:i:s', time());
    $message = preg_replace( '/(?:\n|\r|\r\n)/', ' ', trim($message) );
    $log_message = sprintf("[%s] %s\n", $current_datetime, $message);
    
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