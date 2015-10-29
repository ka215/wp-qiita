<?php
if ( !class_exists( 'WpQiita' ) ) :
/**
 * Wrapper class of Qiita API v2 for WordPress
 *
 * @version 1.0.0
 * @see -
 * @author ka2
 */
class WpQiita {
  
  var $data = null;
  
  var $token = null;
  
  var $endpoint = 'https://qiita.com/api/';
  
  var $api_version = 'v2';
  
  var $api_request_options = array(
    'method' => 'GET', 
    'timeout' => 10, // sec
    'redirection' => 6, // times
    'httpversion' => '1.1', 
    'user-agent' => null, 
    'blocking' => true, 
    'headers' => array(), 
    'cookies' => array(), 
    'body' => null, 
    'compress' => false, 
    'decompress' => true, 
    'sslverify' => true, 
    'stream' => false, 
    'filename' => null 
  );
  
  var $api_method_definitions = array(
    'access_tokens' => array(
      'allow_methods' => array( 'post', 'delete' ), 
      'allow_paths' => array( ':access_token' ) 
    ), 
    'authenticated_user' => array(
      'allow_methods' => array( 'get' ), 
      'allow_paths' => array( 'items' ) 
    ), 
    'comments' => array(
      'allow_methods' => array( 'get', 'put', 'patch', 'delete' ), 
      'allow_paths' => array( ':comment_id', 'thank' ) 
    ), 
    'expanded_templates' => array(
      'allow_methods' => array( 'post' ), 
      'allow_paths' => array() 
    ), 
    'items' => array(
      'allow_methods' => array( 'get', 'post', 'put', 'patch', 'delete' ), 
      'allow_paths' => array( ':item_id', ':tagging_id', 'comments', 'like', 'stock', 'stockers', 'taggings' ) 
    ), 
    'oauth' => array(
      'allow_methods' => array( 'get' ), 
      'allow_paths' => array( 'authorize' ) 
    ), 
    'projects' => array(
      'allow_methods' => array( 'get', 'post', 'patch', 'delete' ), 
      'allow_paths' => array( ':project_id' ) 
    ), 
    'tags' => array(
      'allow_methods' => array( 'get', 'put', 'delete' ), 
      'allow_paths' => array( ':tag_id', 'following', 'items' ) 
    ), 
    'teams' => array(
      'allow_methods' => array( 'get' ), 
      'allow_paths' => array() 
    ), 
    'templates' => array(
      'allow_methods' => array( 'get', 'post', 'patch', 'delete' ), 
      'allow_paths' => array( ':template_id' ) 
    ), 
    'users' => array(
      'allow_methods' => array( 'get', 'put', 'delete' ), 
      'allow_paths' => array( ':user_id', 'followers', 'following', 'following_tags', 'items', 'stocks' ) 
    ), 
  );
  
  /**
   * Constructor
   *
   * @param array $data
   */
  public function __construct( $data=null ) {
    if (!empty($data) && is_array($data)) {
      $this->set_data( $data );
    } else {
      $this->set_data( array() );
    }
  }
  
  /**
   * Set request data
   *
   * @param array $data
   */
  public function set_data( $data ) {
    $this->data = $data;
  }
  
  /**
   * Get response data
   *
   * @return array $data or null
   */
  public function get_data() {
    return $this->$data;
  }
  
  /**
   * Retrieve access token after oauth authentication
   *
   * @param string $client_id [required]
   * @param array $scope [optional] [ `read_qiita`, `write_qiita`, `read_qiita_team`, `write_qiita_team` ]; default is `read_qiita` only if 
   * @return void
   */
  public function oauth_api( $client_id=null, $scope=array('read_qiita') ) {
    if (empty($client_id) || !is_array($scope)) 
      return;
    
    add_action( 'wp_qiita/after_api_request', array(&$this, 'auth_dialog'), 10, 2);
    
    $result = $this->get_api( array( 'oauth', 'authorize' ), array (
      'client_id' => $client_id, 
      'scope' => implode('+', $scope), 
      'state' => wp_create_nonce( 'wp-qiita/' . site_url() .';oauth' ), 
    ));
    var_dump($result);
  }
  
  public function auth_dialog( $response, $url ) {
    var_dump([ $response, $url ]);
  }
  
  /**
   * `GET` method of API request
   *
   * @since 1.0.0
   *
   * @param array $methods [required]
   * @param array $queries [optional]
   * @param array $body [optional]
   * @return mixed Return response body if request is success; Otherwise return a fail status.
   */
  public function get_api( $methods=array(), $queries=null, $body=null ) {
    if (!$this->validate_request_method('GET', $methods)) 
      return;
    
    return $this->request_api( $this->get_api_url( $methods, $queries ), 'GET', $body );
    
  }
  
  /**
   * `POST` method of API request
   *
   * @since 1.0.0
   *
   * @param array $methods [required]
   * @param array $queries [optional]
   * @param array $body [optional]
   * @return mixed Return response body if request is success; Otherwise return a fail status.
   */
  public function post_api( $methods=array(), $queries=null, $body=null ) {
    if (!$this->validate_request_method('POST', $methods)) 
      return;
    
    return $this->request_api( $this->get_api_url( $method, $queries ), 'POST', $body );
    
  }
  
  /**
   * `PATCH` method of API request
   *
   * @since 1.0.0
   *
   * @param array $methods [required]
   * @param array $queries [optional]
   * @param array $body [optional]
   * @return mixed Return response body if request is success; Otherwise return a fail status.
   */
  public function patch_api( $methods=array(), $queries=null, $body=null ) {
    if (!$this->validate_request_method('PATCH', $methods)) 
      return;
    
    return $this->request_api( $this->get_api_url( $method, $queries ), 'PATCH', $body );
    
  }
  
  /**
   * `PUT` method of API request
   *
   * @since 1.0.0
   *
   * @param array $methods [required]
   * @param array $queries [optional]
   * @param array $body [optional]
   * @return mixed Return response body if request is success; Otherwise return a fail status.
   */
  public function put_api( $methods=array(), $queries=null, $body=null ) {
    if (!$this->validate_request_method('PUT', $methods)) 
      return;
    
    return $this->request_api( $this->get_api_url( $method, $queries ), 'PUT', $body );
    
  }
  
  /**
   * `DELETE` method of API request
   *
   * @since 1.0.0
   *
   * @param array $methods [required]
   * @param array $queries [optional]
   * @param array $body [optional]
   * @return mixed Return response body if request is success; Otherwise return a fail status.
   */
  public function delete_api( $methods=array(), $queries=null, $body=null ) {
    if (!$this->validate_request_method('DELETE', $methods)) 
      return;
    
    return $this->request_api( $this->get_api_url( $method, $queries ), 'DELETE', $body );
    
  }
  
  /**
   * Verification of API call method by each request method type
   *
   * @param string $method_type [required]
   * @param array $methods [required]
   * @return boolean
   */
  public function validate_request_method( $method_type=null, $methods=array() ) {
    if ( empty($method_type) || !in_array(strtolower($method_type), array( 'get', 'post', 'put', 'patch', 'delete' )) ) 
      return false;
    
    $_result = true;
    foreach ($this->api_method_definitions as $_toplevel_method => $_allows) {
      if (in_array($_toplevel_method, $methods)) {
        // Whether allowed method
        if (!in_array(strtolower($method_type), $_allows['allow_methods'])) {
          $_result = false;
          break;
        }
        /*
        // Whether the method path proper
        $_allowed_paths = array_push($_allows['allow_paths'], $_toplevel_method);
        foreach ($methods as $_method) {
          if (in_array($_method, $_allowed_paths)) {
            // The exact path verification process
          }
        }
        */
      }
    }
    
    return $_result;
  }
  
  /**
   * Request to Qiita API then will get response
   *
   * @since 1.0.0
   *
   * @param string $url [required]
   * @param string $method [required]
   * @param array $body [optional]
   * @return mixed $response Return response body if request is success; Otherwise return a fail status.
   */
  public function request_api( $url=null, $method=null, $body=array() ) {
    $request_args = $this->api_request_options;
    $request_args['method'] = in_array(strtolower($method), array('get', 'post', 'put', 'patch', 'delete')) ? strtoupper($method) : 'GET';
    $request_args['headers'] = array(
      'Content-Type' => 'application/json', 
      'Authorization' => sprintf('Bearer %s', $this->token)
    );
    if (!empty($body)) {
      $request_args['body'] = wp_json_encode( $body );
    } else {
      unset($request_args['body']);
    }
    
    // Filter the request arguments just before API request.
    //
    // @since 1.0.0
    $request_args = apply_filters( 'wp_qiita/api_request_args', $request_args, $url );
    
    $response = wp_remote_request( $url, $request_args );
    
    // Filter the raw response just after API request.
    //
    // @since 1.0.0
    $response = apply_filters( 'wp_qiita/api_request_response', $response, $url );
    
    // Fire action after API request.
    //
    // @since 1.0.0
    do_action( 'wp_qiita/after_api_request', $response, $url );
    
    if ($this->validate_response_code( $response )) {
      return json_decode($response['body']);
    } else {
      return $response['headers']['status'];
    }
  }
  
  /**
   * Get generated Qiita API URL
   *
   * @since 1.0.0
   *
   * @param array $paths [required] Not assoc array that elements of method as API path
   * @param array $queries [optional] Must assoc array
   * @return string $url Qiita API URL
   */
  public function get_api_url( $paths=array(), $queries=array() ) {
    $base_url  = $this->endpoint . $this->api_version . '/' . implode('/', $paths);
    if (!empty($queries)) {
      $query_strings = array();
      foreach ($queries as $_param => $_value) {
        $query_strings[] = sprintf('%s=%s', $_param, $_value);
      }
      $base_url .= '?' . implode('&', $query_strings);
    }
    
    //$url = wp_sanitize_redirect( $base_url );
    $url = $base_url;
    return $url;
  }
  
  /**
   * Validate the response code of API request
   *
   * @since 1.0.0
   *
   * @param array $response [required] Response of API request
   * @return boolean
   */
  public function validate_response_code( $response=array() ) {
    if (!empty($response) && is_array($response)) {
      if ( 200 <= wp_remote_retrieve_response_code( $response ) && 300 > wp_remote_retrieve_response_code( $response ) ) {
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }
  
}
endif; // end of class_exists()