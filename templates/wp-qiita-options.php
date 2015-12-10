<?php
$_local_code = defined('WPLANG') ? '-' . WPLANG : '';

if ( $this->check_current_activated( $this->current_user ) ) {
  $_qiita_user_meta = $this->user_options;
  $_is_activated = ! is_array( $_qiita_user_meta ) || ! array_key_exists( 'activated', $_qiita_user_meta ) || ! wp_validate_boolean( $_qiita_user_meta['activated'] ) ? false : true;
} else {
  $_is_activated = false;
}

$tmpl_tabs = array(
  'activation' => __('Activation', $this->domain_name), 
  'profile' => __('Profile', $this->domain_name), 
  'items' => __('Items', $this->domain_name), 
  'comments' => __('Comments', $this->domain_name), 
  'extra' => __('Extra', $this->domain_name), 
);
if ( $_is_activated ) {
  unset( $tmpl_tabs['comments'] ); // Features not yet valid
  $tmpl_current_tab = array_key_exists( 'tab', $this->query ) ? $this->query['tab'] : 'activation';
} else {
  unset( $tmpl_tabs['profile'], $tmpl_tabs['items'], $tmpl_tabs['comments'] );
  $tmpl_current_tab = array_key_exists( 'tab', $this->query ) ? $this->query['tab'] : 'activation';
}

$wpqt_nonce_action = implode( '/', array( site_url(), $this->domain_name, $this->current_user, $this->query['page'] ) );

?>
<div id="wp-qiita-options">
  <header class="plugin-options-header">
    <h2 id="screen-title"><span class="wpqt-qiita-favicon-color"><span class="path1"></span><span class="path2"></span></span> <?php _e('WP Qiita General Options', $this->domain_name); ?></h2>
    <span class="plugin-version label label-info"><?php echo __('Ver.', $this->domain_name); ?> <?php echo $this->version; ?></span>
  </header><!-- /.plugin-options-header -->
  
  <form method="post" action="<?php echo add_query_arg('tab', $tmpl_current_tab); ?>" id="wp-qiita-admin-form">
    <input type="hidden" name="active_tab" value="<?php echo $tmpl_current_tab; ?>">
    <input type="hidden" name="action" value="">
    <input type="hidden" name="user_id" value="<?php echo $this->current_user; ?>">
  <?php wp_nonce_field( $wpqt_nonce_action ); ?>
  </form><!-- /#wp-qiita-admin-form -->
  
  <!-- Nav tabs -->
  <ul class="nav nav-tabs" role="tablist">
  <?php foreach ($tmpl_tabs as $_slug => $_label) : ?>
    <li role="presentation"<?php if ($tmpl_current_tab === $_slug) : ?> class="active"<?php endif; ?>><a href="#<?php echo $_slug; ?>" aria-controls="<?php echo $_slug; ?>" role="tab" data-toggle="tab"><?php echo $_label; ?></a></li>
  <?php endforeach; ?>
  </ul><!-- /.nav-tabs -->
  
  <!-- Tab panes -->
  <div class="tab-content">
    <div class="loader"><?php _e('Now Loading...', $this->domain_name); ?></div>
    <div role="tabpanel" class="tab-pane<?php if ($tmpl_current_tab === 'activation') : ?> active<?php endif; ?><?php if ( ! array_key_exists( 'tab', $this->query ) ) : ?> loaded<?php endif; ?>" id="activation">
<?php
if ( 'activation' === $tmpl_current_tab ) : 
  if ( ! $_is_activated) : 
?>
      <p class="describe">
        <?php _e('At the here is able to do Qiita and WordPress activation (cooperation). In the activation has two kinds of methods.', $this->domain_name); ?>
        <?php _e('One is how to issue an access token by registering the site as an application to Qiita side. Another way is to register the access token that was issued at Qiita side to this site side.', $this->domain_name); ?>
        <?php _e('Even in conjunction with either method, the difference in the use of plugin does not occur. Please choose your favorite way.', $this->domain_name); ?>
      </p>
      
      <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="false">
        <div class="panel panel-default">
          <div class="panel-heading" role="tab" id="oauthActivateHeader">
            <h4 class="panel-title">
              <a role="button" data-toggle="collapse" data-parent="#accordion" href="#oauthActivate" aria-expanded="false" aria-controls="oauthActivate">
                <?php _e('Register as application by using OAuth authentication', $this->domain_name); ?>
              </a>
            </h4>
          </div><!-- /.panel-heading -->
          <div id="oauthActivate" class="panel-collapse collapse" role="tabpanel" aria-labelledby="oauthActivateHeader">
            <div class="panel-body">
              
              <div class="row">
                <div class="col-sm-6 col-md-6">
                  <div class="thumbnail">
                    <img src="<?php echo $this->plugin_dir_url; ?>/assets/images/qiita-app-register-1.png">
                    <div class="caption">
                      <h5>1. <?php _e('You will register the application on Qiita.', $this->domain_name); ?></h5>
                      <p><?php printf( __('"Redirect URL" Please specify the URL (%s) of the management screen of this WP Qiita.', $this->domain_name), '<code>'. $this->get_current_url() .'</code>'  ); ?></p>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6 col-md-6">
                  <div class="thumbnail">
                    <img src="<?php echo $this->plugin_dir_url; ?>/assets/images/qiita-app-register-2.png">
                    <div class="caption">
                      <h5>2. <?php _e('Client ID and Client Secret is issued.', $this->domain_name); ?></h5>
                      <p><?php _e('You can confirm from "application in registration" on setting screen of Qiita.', $this->domain_name); ?></p>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-sm-12 col-md-12">
                  <div class="thumbnail">
                    <div class="caption">
                      <h5>3. <?php _e('Enter the issued Client ID and Client Secret.', $this->domain_name); ?></h5>
                    </div>
                    
              <div class="form-horizontal">
                <div class="form-group">
                  <label for="wpqt-client_id" class="col-sm-3 control-label"><?php _e('Client ID', $this->domain_name); ?></label>
                  <div class="col-sm-4">
                    <input type="text" class="form-control" id="wpqt-client_id" name="<?php echo esc_attr($this->domain_name); ?>[client_id]" placeholder="<?php _e('Client ID', $this->domain_name); ?>" value="">
                  </div>
                </div>
                <div class="form-group">
                  <label for="wpqt-client_secret" class="col-sm-3 control-label"><?php _e('Client Secret', $this->domain_name); ?></label>
                  <div class="col-sm-4">
                    <input type="password" class="form-control" id="wpqt-client_secret" name="<?php echo esc_attr($this->domain_name); ?>[client_secret]" placeholder="<?php _e('Client Secret', $this->domain_name); ?>" value="">
                  </div>
                </div>
                <div class="form-group">
                  <label for="wpqt-scope-1" class="col-sm-3 control-label"><?php _e('Scope', $this->domain_name); ?></label>
                  <div class="col-sm-9">
                    <div class="checkbox">
                      <label class="checkbox-inline">
                        <input type="checkbox" id="wpqt-scope-1" name="<?php echo esc_attr($this->domain_name); ?>[scope][]" value="read_qiita"> <?php _e('Read Qiita', $this->domain_name); ?>
                      </label>
                      <label class="checkbox-inline">
                        <input type="checkbox" id="wpqt-scope-2" name="<?php echo esc_attr($this->domain_name); ?>[scope][]" value="write_qiita"> <?php _e('Write Qiita', $this->domain_name); ?>
                      </label>
                      <label class="checkbox-inline">
                        <input type="checkbox" id="wpqt-scope-3" name="<?php echo esc_attr($this->domain_name); ?>[scope][]" value="read_qiita_team"> <?php _e('Read Qiita Team', $this->domain_name); ?>
                      </label>
                      <label class="checkbox-inline">
                        <input type="checkbox" id="wpqt-scope-4" name="<?php echo esc_attr($this->domain_name); ?>[scope][]" value="write_qiita_team"> <?php _e('Write Qiita Team', $this->domain_name); ?>
                      </label>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <div class="col-sm-offset-3 col-sm-10">
                    <button type="button" class="btn btn-primary btn-lg" data-button-action="activate_oauth"><?php _e('Activate', $this->domain_name); ?></button>
                  </div>
                </div>
              </div><!-- /.form-horizontal -->
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-sm-6 col-md-6">
                  <div class="thumbnail">
                    <img src="<?php echo $this->plugin_dir_url; ?>/assets/images/qiita-app-register-3.png">
                    <div class="caption">
                      <h5>4. <?php _e('Redirect to authenticate to Qiita', $this->domain_name); ?></h5>
                      <p><?php _e('If you are not logged in Qiita You must be logged in.', $this->domain_name); ?></p>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6 col-md-6">
                  <div class="thumbnail">
                    <img src="<?php echo $this->plugin_dir_url; ?>/assets/images/qiita-app-register-4.png">
                    <div class="caption">
                      <h5>5. <?php _e('WP Qiita is registered in the "application in use".', $this->domain_name); ?></h5>
                      <p><?php _e('Activation is complete.', $this->domain_name); ?></p>
                    </div>
                  </div>
                </div>
              </div>
              
            </div><!-- /.panel-body -->
          </div><!-- /.panel-collapse -->
        </div><!-- /.panel -->
        <div class="panel panel-default">
          <div class="panel-heading" role="tab" id="tokenActivateHeader">
            <h4 class="panel-title">
              <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#tokenActivate" aria-expanded="false" aria-controls="tokenActivate">
                <?php _e('Register the access token you created', $this->domain_name); ?>
              </a>
            </h4>
          </div><!-- /.panel-heading -->
          <div id="tokenActivate" class="panel-collapse collapse" role="tabpanel" aria-labelledby="tokenActivateHeader">
            <div class="panel-body">
              
              <div class="row">
                <div class="col-sm-6 col-md-6">
                  <div class="thumbnail">
                    <img src="<?php echo $this->plugin_dir_url; ?>/assets/images/qiita-access-token-2.png">
                    <div class="caption">
                      <h5>1. <?php _e('Issue an access token in Qiita.', $this->domain_name); ?></h5>
                      <p><?php _e('You can issue it from "issuing a new token" on  setting screen of Qiita.', $this->domain_name); ?></p>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6 col-md-6">
                  <div class="thumbnail">
                    <img src="<?php echo $this->plugin_dir_url; ?>/assets/images/qiita-access-token-3.png">
                    <div class="caption">
                      <h5>2. <?php _e('Refrain from the issued access token.', $this->domain_name); ?></h5>
                      <p><?php _e('Access token can not be re-displayed.', $this->domain_name); ?></p>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-sm-12 col-md-12">
                  <div class="thumbnail">
                    <div class="caption">
                      <h5>3. <?php _e('Enter the issued access token.', $this->domain_name); ?></h5>
                    </div>
                    
              <div class="form-horizontal">
                <div class="form-group">
                  <label for="wpqt-access_token" class="col-sm-2 control-label"><?php _e('Access Token', $this->domain_name); ?></label>
                  <div class="col-sm-4">
                    <input type="text" class="form-control" id="wpqt-access_token" name="<?php echo esc_attr($this->domain_name); ?>[access_token]" placeholder="<?php _e('Access Token', $this->domain_name); ?>" value="">
                  </div>
                </div>
                <div class="form-group">
                  <div class="col-sm-offset-2 col-sm-10">
                    <button type="button" class="btn btn-primary btn-lg" data-button-action="activate_token"><?php _e('Activate', $this->domain_name); ?></button>
                  </div>
                </div>
              </div><!-- /.form-horizontal -->

                  </div>
                </div>
              </div>
              
            </div><!-- /.panel-body -->
          </div><!-- /.panel-collapse -->
        </div><!-- /.panel -->
      </div><!-- /#accordion-->
<?php else : 

// Set defaults
extract( array(
  '_load_jquery' => isset( $_qiita_user_meta['load_jquery'] ) ? wp_validate_boolean( $_qiita_user_meta['load_jquery'] ) : true, 
  '_show_posttype' => isset( $_qiita_user_meta['show_posttype'] ) ? wp_validate_boolean( $_qiita_user_meta['show_posttype'] ) : false, 
  '_autosync' => isset( $_qiita_user_meta['autosync'] ) ? wp_validate_boolean( $_qiita_user_meta['autosync'] ) : false, 
  '_autosync_interval' => isset( $_qiita_user_meta['autosync_interval'] ) && intval( $_qiita_user_meta['autosync_interval'] ) > 0 ? intval( $_qiita_user_meta['autosync_interval'] ) : '', 
  '_autosync_status' => __('Undefined', $this->domain_name), 
  '_autopost' => isset( $_qiita_user_meta['autopost'] ) ? wp_validate_boolean( $_qiita_user_meta['autopost'] ) : false, 
  '_remove_post' => isset( $_qiita_user_meta['remove_post'] ) ? wp_validate_boolean( $_qiita_user_meta['remove_post'] ) : false, 
  '_deactivate_qiita' => isset( $_qiita_user_meta['deactivate_qiita'] ) ? wp_validate_boolean( $_qiita_user_meta['deactivate_qiita'] ) : false, 
) );
foreach ( $this->options as $_key => $_val ) {
  $_{$_key} = $_val;
}
if ( isset( $this->options['autosync_datetime'] ) && ! empty( $this->options['autosync_datetime'] ) ) {
  $_timezone = get_option( 'timezone_string' );
  date_default_timezone_set( $_timezone );
  $_next_autosync = date_i18n( 'Y-m-d H:i', $this->options['autosync_datetime'], false );
  $_autosync_status = sprintf( __('Next autosync will be executed at %s.', $this->domain_name), '<time>'. $_next_autosync .'</time>' );
}
?>
      <h3 class="text-success"><?php _e('Currently, already Activated.', $this->domain_name); ?></h3>
      
      <p class="describe"><?php _e('Do you want to inactivate and stop cooperation with Qiita?', $this->domain_name); ?></p>
      
      <div class="form-horizontal">
        <input type="hidden" id="wpqt-inactivate_flag" name="<?php echo esc_attr($this->domain_name); ?>[inactivate_flag]" value="true">
        <div class="form-group">
          <div class="col-sm-offset-2 col-sm-10">
            <button type="button" class="btn btn-default btn-lg" data-button-action="inactivate"><?php _e('Inactivate', $this->domain_name); ?></button>
          </div>
        </div>
      </div>
      
      <div class="clearfix"></div>
      <div class="activated-options">
        <h4 class="text-info"><span class="dashicons dashicons-admin-settings"></span> <?php _e('Advanced cooperation options', $this->domain_name); ?></h4>
        
        <p class="describe"><?php _e('In this options, you can carry out the advanced settings about connection with the Qiita.', $this->domain_name); ?></p>
        
        <div class="form-horizontal">
          <input type="hidden" id="wpqt-advanced_setting" name="<?php echo esc_attr($this->domain_name); ?>[advanced_setting]" value="true">
          <div class="form-group">
            <label for="wpqtLoadJquery" class="col-sm-2 control-label"><?php _e('For Loading jQuery', $this->domain_name); ?></label>
            <div class="col-sm-10">
              <div class="checkbox">
                <label>
                  <input type="checkbox" id="wpqtLoadJquery" name="<?php echo esc_attr($this->domain_name); ?>[load_jquery]" <?php checked( $_load_jquery, true ); ?>> <?php _e('Checked if performing jQuery loading to the frontend by this plugin (it is loaded via CDN when enabled).', $this->domain_name); ?>
                </label>
              </div>
              <p class="help-block"><?php _e('Please load on yourself separately it (for example is in the theme) if you want to use jQuery when has disabled.', $this->domain_name); ?></p>
            </div>
          </div><!-- /.form-group:#wpqtLoadJquery -->
          <div class="form-group">
            <label for="wpqtShowPosttype" class="col-sm-2 control-label"><?php _e('Show Post Type', $this->domain_name); ?></label>
            <div class="col-sm-10">
              <div class="checkbox">
                <label>
                  <input type="checkbox" id="wpqtShowPosttype" name="<?php echo esc_attr($this->domain_name); ?>[show_posttype]" <?php checked( $_show_posttype, true ); ?>> <?php _e('Checked If you want to show the post type of synchronized Qiita articles in admin menu.', $this->domain_name); ?>
                </label>
              </div>
            </div>
          </div><!-- /.form-group:#wpqtAutosync -->
          <div class="form-group">
            <label for="wpqtAutosync" class="col-sm-2 control-label"><?php _e('Autosync', $this->domain_name); ?></label>
            <div class="col-sm-10">
              <div class="checkbox">
                <label>
                  <input type="checkbox" id="wpqtAutosync" name="<?php echo esc_attr($this->domain_name); ?>[autosync]" <?php checked( $_autosync, true ); ?>> <?php _e('Checked if you want to enable automatic synchronization with Qiita.', $this->domain_name); ?>
                </label>
              </div>
            </div>
          </div><!-- /.form-group:#wpqtAutosync -->
          <div class="form-group">
            <label for="wpqtAutosyncInterval" class="col-sm-2 control-label"><?php _e('Autosync Interval', $this->domain_name); ?></label>
            <div class="col-sm-2">
              <input type="text" class="form-control" id="wpqtAutosyncInterval" aria-describedby="helpAutosyncInterval" name="<?php echo esc_attr($this->domain_name); ?>[autosync_interval]" placeholder="14400 (=4hour)" value="<?php echo $_autosync_interval; ?>">
            </div>
            <div class="col-sm-offset-2 col-sm-10">
              <span id="helpAutosyncInterval" class="help-block"><?php _e('There is a possibility when it will be not able to synchronize by the connection restriction of  the Qiita if you set too short interval. (Every 4 hours is recommended)', $this->domain_name); ?></span>
            </div>
          </div><!-- /.form-group:#wpqtAutosyncInterval -->
        <?php if ( $_autosync ) : ?>
          <div class="form-group">
            <label for="wpqtAutosyncStatus" class="col-sm-2 control-label"><?php _e('Autosync Status', $this->domain_name); ?></label>
            <div class="col-sm-10" style="padding-top: 7px;">
              <?php echo $_autosync_status; ?>
            </div>
          </div><!-- /.form-group:#wpqtAutosyncStatus -->
        <?php endif; ?>
          <div class="form-group">
            <label for="wpqtAutoPost" class="col-sm-2 control-label" disabled="disabled"><?php _e('Auto Post', $this->domain_name); ?></label>
            <div class="col-sm-10">
              <div class="checkbox">
                <label>
                  <input type="checkbox" id="wpqtAutoPost" name="<?php echo esc_attr($this->domain_name); ?>[autopost]" disabled="disabled" <?php checked( $_autopost, true ); ?>> <?php _e('Checked if you want to post automatically by specific schedule to Qiita.', $this->domain_name); ?> <span class="text-muted"><?php _e('In Preparation', $this->domain_name); ?></span>
                </label>
              </div>
            </div>
          </div><!-- /.form-group:#wpqtAutosync -->
          <div class="form-group">
            <label for="wpqtDeactivate" class="col-sm-2 control-label"><?php _e('Deactivate Options', $this->domain_name); ?></label>
            <div class="col-sm-10">
              <div class="checkbox">
                <label>
                  <input type="checkbox" id="wpqtDeactivate" name="<?php echo esc_attr($this->domain_name); ?>[remove_post]" <?php checked( $_remove_post, true ); ?>> <?php _e('When you have deactivated connection with the Qiita, to remove all posts that is synchronized.', $this->domain_name); ?>
                </label>
              </div>
            </div>
          </div><!-- /.form-group:#wpqtDeactivate -->
          <div class="form-group">
            <label for="wpqtUDeactivatePlugin" class="col-sm-2 control-label"><?php _e('Deactivate Plugin', $this->domain_name); ?></label>
            <div class="col-sm-10">
              <div class="checkbox">
                <label>
                  <input type="checkbox" id="wpqtDeactivatePlugin" name="<?php echo esc_attr($this->domain_name); ?>[deactivate_qiita]" <?php checked( $_deactivate_qiita, true ); ?>> <?php _e('Force deactivate connection with Qiita when you will disable this plugin.', $this->domain_name); ?>
                </label>
              </div>
            </div>
          </div><!-- /.form-group:#wpqtDeactivate -->
          
          <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
              <button type="button" class="btn btn-primary" data-button-action="advanced_setting"><?php _e('Save changes', $this->domain_name); ?></button>
            </div>
          </div><!-- /.form-group:#wpqtAutosync -->
        </div><!-- /.form-horizontal -->
      </div><!-- /.activated-options -->
      
<?php endif; ?>
  <div class="panel panel-default donate-info">
    <div class="panel-heading"><span class="glyphicon glyphicon-heart" style="color: #f33;"></span> <?php esc_html_e( 'About WP Qiita', $this->domain_name ); ?></div>
    <div class="panel-body">
      <?php _e( 'If you become to like this plugin or if it helps your business, donations to the author are greatly appreciated.', $this->domain_name ); ?></p>
      <div class="clearfix"></div>
      <ul class="list-inline donate-links">
      <?php if (in_array($_local_code, [ 'ja',  ])) : ?>
        <li class="donate-paypal"><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
          <input type="hidden" name="cmd" value="_donations">
          <input type="hidden" name="business" value="2YZY4HWYSWEWG">
          <input type="hidden" name="lc" value="en_US">
          <input type="hidden" name="currency_code" value="USD">
          <input type="hidden" name="item_name" value="Donate to WP Qiita">
          <button type="submit" name="submit" alt="PayPal - <?php esc_html_e( 'The safer, easier way to pay online!', $this->domain_name ); ?>" class="btn btn-primary"><i class="fa fa-paypal"></i> Donate Paypal</button>
          <img alt="" border="0" src="https://www.paypalobjects.com/ja_JP/i/scr/pixel.gif" width="1" height="1">
        </form></li>
      <?php endif; ?>
        <li class="donate-blockchain"><div style="font-size:16px;margin:0 auto;width:300px" class="blockchain-btn" data-address="1821oc4XvWrfiwfVcNCAKEC8gppcrab4Re" data-shared="false">
          <div class="blockchain stage-begin">
            <img src="https://blockchain.info/Resources/buttons/donate_64.png"/>
          </div>
          <div class="blockchain stage-loading" style="text-align:center">
            <img src="https://blockchain.info/Resources/loading-large.gif"/>
          </div>
          <div class="blockchain stage-ready">
            <p align="center"><?php _e('Please Donate To Bitcoin Address:', $this->domain_name);?> <b>[[address]]</b></p>
            <p align="center" class="qr-code"></p>
          </div>
          <div class="blockchain stage-paid">
            Donation of <b>[[value]] BTC</b> Received. Thank You.
          </div>
          <div class="blockchain stage-error">
            <font color="red">[[error]]</font>
          </div>
        </div></li>
      </ul>
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
  
  <div class="panel panel-default other-note">
    <div class="panel-heading"><i class="fa fa-check-circle-o"></i> <?php _e( 'WP Qiita License Agreement', $this->domain_name ); ?></div>
    <div class="panel-body">
      <p>Copyright (c) 2015, ka2 ( <a href="https://ka2.org/" target="_blank">https://ka2.org</a> )</p>
      <p>This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License, version 2, as published by the Free Software Foundation.</p>
      <p>This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.</p>
      <p>You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA</p>
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
<?php endif; ?>
    </div><!-- /.tab-pane#activation -->
    <div role="tabpanel" class="tab-pane<?php if ($tmpl_current_tab === 'profile') : ?> active<?php endif; ?>" id="profile">
<?php if ('profile' === $tmpl_current_tab) : ?>
      <h4 class="heading-tab"><?php _e('Authenticated Qiita user&#039;s profile', $this->domain_name); ?></h4>
      
<?php
if ( ! array_key_exists( 'id', $_qiita_user_meta ) ) {
  $_qiita_user_meta = $this->retrieve_authenticated_user_profile( $this->current_user );
}
$_name_elements = explode( ' ', $_qiita_user_meta['name'] );
$first_name = isset( $_name_elements[0] ) && ! empty( $_name_elements[0] ) ? $_name_elements[0] : '';
$last_name = isset( $_name_elements[1] ) && ! empty( $_name_elements[1] ) ? $_name_elements[1] : '';
$_upload_limit = size_format( $_qiita_user_meta['image_monthly_upload_limit'], 0 );
$_upload_remaining = size_format( $_qiita_user_meta['image_monthly_upload_remaining'], 2 );
$_team_only = $_qiita_user_meta['team_only'] ? 'TRUE' : 'FALSE';
if ( isset( $_qiita_user_meta['contribution'] ) || ! empty( $_qiita_user_meta['contribution'] ) ) {
  $_is_contribution = true;
  $_contribution = $_qiita_user_meta['contribution'];
} else {
  $_is_contribution = false;
  $_contribution =  __('Unacquired', $this->domain_name);
}
?>
      <div class="row">
        <div class="col-xs-6 col-sm-4 col-md-4">
          <div class="col-sm-offset-2 col-sm-8">
            <a href="javascript:;" class="thumbnail">
              <img src="<?php echo esc_attr($_qiita_user_meta['profile_image_url']); ?>">
            </a>
            <input type="hidden" name="user[id]" id="user_id" value="<?php echo esc_attr($_qiita_user_meta['id']); ?>">
            <input type="hidden" name="user[permanent_id]" id="user_permanent_id" value="<?php echo esc_attr($_qiita_user_meta['permanent_id']); ?>">
          </div>
          <div class="form-horizontal">
            <div class="form-group">
              <label for="user_followees" class="col-sm-6 control-label"><?php _e('Followees', $this->domain_name); ?></label>
              <div class="col-sm-6">
                <input class="form-control" type="text" value="<?php echo esc_attr($_qiita_user_meta['followees_count']); ?>" name="user[followees_count]" id="user_followees" readonly>
              </div>
            </div>
            <div class="form-group">
              <label for="user_followers" class="col-sm-6 control-label"><?php _e('Followers', $this->domain_name); ?></label>
              <div class="col-sm-6">
                <input class="form-control" type="text" value="<?php echo esc_attr($_qiita_user_meta['followers_count']); ?>" name="user[followers_count]" id="user_followers" readonly>
              </div>
            </div>
            <div class="form-group">
              <label for="user_items_count" class="col-sm-6 control-label"><?php _e('Items Count', $this->domain_name); ?></label>
              <div class="col-sm-6">
                <input class="form-control" type="text" value="<?php echo esc_attr($_qiita_user_meta['items_count']); ?>" name="user[items_count]" id="user_items_count" readonly>
              </div>
            </div>
            <div class="form-group">
              <label for="user_github_login_name" class="col-sm-6 control-label"><?php _e('GitHub Login Name', $this->domain_name); ?></label>
              <div class="col-sm-6">
                <input class="form-control" type="text" value="<?php echo esc_attr($_qiita_user_meta['github_login_name']); ?>" name="user[github_login_name]" id="user_github_login_name" readonly>
              </div>
            </div>
            <div class="form-group">
              <label for="user_twitter_screen_name" class="col-sm-6 control-label"><?php _e('Twitter Screen Name', $this->domain_name); ?></label>
              <div class="col-sm-6">
                <input class="form-control" type="text" value="<?php echo esc_attr($_qiita_user_meta['twitter_screen_name']); ?>" name="user[twitter_screen_name]" id="user_twitter_screen_name" readonly>
              </div>
            </div>
<?php /*
            <div class="form-group">
              <label for="user_image_monthly_upload_limit" class="col-sm-6 control-label"><?php _e('Image Monthly Upload Limit', $this->domain_name); ?></label>
              <div class="col-sm-6">
                <input class="form-control" type="text" value="<?php echo esc_attr($_upload_limit); ?>" name="user[image_monthly_upload_limit]" id="user_image_monthly_upload_limit" readonly>
              </div>
            </div>
*/ ?>
              <div class="form-group">
              <label for="user_image_monthly_upload_remaining" class="col-sm-6 control-label"><?php _e('Image Monthly Upload Remaining', $this->domain_name); ?></label>
              <div class="col-sm-6">
                <input class="form-control" type="text" value="<?php echo esc_attr($_upload_remaining); ?>" name="user[image_monthly_upload_remaining]" id="user_image_monthly_upload_remaining" readonly>
              </div>
            </div>
            <div class="form-group">
              <label for="user_team_only" class="col-sm-6 control-label"><?php _e('Qiita: Team Only', $this->domain_name); ?></label>
              <div class="col-sm-6">
                <input class="form-control" type="text" value="<?php echo esc_attr($_team_only); ?>" name="user[team_only]" id="user_team_only" readonly>
              </div>
            </div>
            <div class="form-group">
              <label for="user_contribution" class="col-sm-6 control-label<?php if ( ! $_is_contribution ) : ?> text-muted<?php endif; ?>"><?php _e('Contribution', $this->domain_name); ?></label>
              <div class="col-sm-6">
                <input class="form-control" type="text" value="<?php echo esc_attr($_contribution); ?>" name="user[contribution]" id="user_contribution" readonly<?php if ( ! $_is_contribution ) : ?> disabled<?php endif; ?>>
              </div>
            </div>
          </div><!-- /.form-horizontal -->
        </div>
        <div class="col-xs-12 col-sm-8 col-md-8">
      
      <div class="form-group">
        <label for="user_name"><?php _e('Name', $this->domain_name); ?></label>
        <div class="row">
          <div class="col-sm-6">
            <input maximum="32" placeholder="First Name" class="form-control" type="text" value="<?php echo esc_attr($first_name); ?>" name="user[first_name]" id="user_first_name" readonly>
          </div>
          <div class="col-sm-6">
            <input maximum="32" placeholder="Last Name" class="form-control" type="text" value="<?php echo esc_attr($last_name); ?>" name="user[last_name]" id="user_last_name" readonly>
          </div>
        </div>
      </div>
      <div class="form-group ">
        <label for="user_website_url"><?php _e('Website URL', $this->domain_name); ?></label>
        <div class="row">
          <div class="col-sm-12">
            <input placeholder="URL" type="url" class="form-control" value="<?php echo esc_attr($_qiita_user_meta['website_url']); ?>" name="user[website_url]" id="user_website_url" readonly>
          </div>
        </div>
      </div>
      <div class="form-group ">
        <label for="user_organization"><?php _e('Organization', $this->domain_name); ?></label>
        <div class="row">
          <div class="col-sm-12">
            <input placeholder="Your Organization" class="form-control" type="text" value="<?php echo esc_attr($_qiita_user_meta['organization']); ?>" name="user[organization]" id="user_organization" readonly>
          </div>
        </div>
      </div>
      <div class="form-group ">
        <label for="user_location"><?php _e('Location', $this->domain_name); ?></label>
        <div class="row">
          <div class="col-sm-12">
            <input placeholder="Your Location" class="form-control" type="text" value="<?php echo esc_attr($_qiita_user_meta['location']); ?>" name="user[location]" id="user_location" readonly>
          </div>
        </div>
      </div>
      <div class="form-group ">
        <label for="user_description"><?php _e('Description', $this->domain_name); ?></label>
        <div class="row">
          <div class="col-sm-12">
            <textarea rows="4" maxlength="200" class="form-control" name="user[description]" id="user_description" readonly><?php echo esc_textarea($_qiita_user_meta['description']); ?></textarea>
          </div>
          <div class="col-sm-12" style="margin-top: 0.5em;">
            <button type="button" id="sync-description" class="btn btn-default"  data-button-action="sync_description"><?php _e('Sync of Description to WordPress users', $this->domain_name); ?></button>
          </div>
        </div>
      </div>
      <div class="form-group profileSettings_ignoreRailsError ">
        <label for="user_facebook_id"><?php _e('Facebook ID', $this->domain_name); ?></label>
        <div class="row">
          <div class="col-sm-12">
            <div class="input-group">
              <span class="input-group-addon">https://www.facebook.com/</span>
              <input class="form-control" type="text" value="<?php echo esc_attr($_qiita_user_meta['facebook_id']); ?>" name="user[facebook_id]" id="user_facebook_id" readonly>
            </div>
          </div>
        </div>
      </div>
      <div class="form-group profileSettings_ignoreRailsError ">
        <label for="user_linkedin_id"><?php _e('LinkedIn ID', $this->domain_name); ?></label>
        <div class="row">
          <div class="col-sm-12">
            <div class="input-group">
              <span class="input-group-addon">https://www.linkedin.com/in/</span>
              <input class="form-control" type="text" value="<?php echo esc_attr($_qiita_user_meta['linkedin_id']); ?>" name="user[linkedin_id]" id="user_linkedin_id" readonly>
            </div>
          </div>
        </div>
      </div>
<?php /*
      <div class="form-group profileSettings_ignoreRailsError ">
        <label for="user_google_plus_id"><?php _e('Google+ ID', $this->domain_name); ?></label>
        <div class="row">
          <div class="col-sm-12">
            <div class="input-group">
              <span class="input-group-addon">https://plus.google.com/</span>
              <input class="form-control" type="text" value="" name="user[google_plus_id]" id="user_google_plus_id" readonly>
              <span class="input-group-addon">/post</span>
            </div>
          </div>
        </div>
      </div>
*/ ?>
      
      </div><!-- /.row -->
      <div class="form-group">
        <div class="col-sm-offset-1 col-sm-11">
      <?php if ( ! $_is_contribution ) : ?>
        <p class="help-block"><span class="text-warning"><?php _e('In order to get the number of contribution of Qiita user you need to synchronize the Qiita article.', $this->domain_name); ?></span></p>
      <?php endif; ?>
        <button type="button" id="reacquire" class="btn btn-primary" data-button-action="reacquire_profile"><?php _e('Reacquire Qiita User&#039;s Profile', $this->domain_name); ?></button>
        </div>
      </div>
<?php endif; ?>
    </div><!-- /.tab-pane#profile -->
    <div role="tabpanel" class="tab-pane<?php if ($tmpl_current_tab === 'items') : ?> active<?php endif; ?>" id="items">
<?php if ('items' === $tmpl_current_tab) : ?>
      <h4 class="heading-tab"><?php _e('Articles management for authenticated Qiita user', $this->domain_name); ?></h4>
      
<?php
$current_page = array_key_exists('cp', $this->query) && !empty($this->query['cp']) && intval($this->query['cp']) > 0 ? intval($this->query['cp']) : 1;
$per_page = array_key_exists('pp', $this->query) && !empty($this->query['pp']) && intval($this->query['pp']) > 0 ? intval($this->query['pp']) : 20;
$start_index = ($current_page - 1) * $per_page;
$_items = get_posts( array( 'posts_per_page' => $per_page, 'offset' => $start_index, 'post_type' => $this->domain_name, 'author' => $this->current_user, 'post_status' => 'publish,private,draft' ) );
$_indices = array(
  'index' => '#', 
  'title' => __('Title', $this->domain_name), 
  'stocks' => __('Stocks', $this->domain_name), 
  'excerpt' => __('Excerpt', $this->domain_name), 
  'tags' => __('Tags', $this->domain_name), 
  'coediting' => __('Co Editing', $this->domain_name), 
  'private' => __('Private', $this->domain_name), 
  'created' => __('Created at', $this->domain_name), 
  'updated' => __('Updated at', $this->domain_name), 
  'operate' => __('Operations', $this->domain_name), 
);
if ($_qiita_user_meta['team_only']) {
  unset($_indices['excerpt'], $_indices['private']);
} else {
  unset($_indices['excerpt'], $_indices['coediting']);
}
?>
  <?php if ( ! post_type_exists( $this->domain_name ) || empty( $_items ) ) : ?>
    <div class="center-block">
      <p class="text-info"><?php _e('Not yet synchronized articles of the Qiita into WordPress. Are you sure you want to synchronize the Qiita articles?', $this->domain_name); ?></p>
      <p class="text-info"><?php _e('Also you should be noted that the synchronized articles from Qiita will be incorporated into WordPress as a custom post type "wp-qiita".', $this->domain_name); ?></p>
      <p class="text-info"><?php _e('If you have a lot of articles at the Qiita, please note that the synchronization maybe take long time.', $this->domain_name); ?></p>
      <button class="btn btn-primary btn-lg" type="button" id="initial-sync" data-button-action="initial_sync"><?php _e('Synchronize the Qiita articles', $this->domain_name); ?></button>
    </div>
  <?php else : ?>
    <div class="panel panel-default">
      <div class="panel-body form-inline">
        <div class="form-group pull-right">
          <label for="change-perpage-number"><?php _e('Display Item Per Page', $this->domain_name); ?>: </label>
          <input type="number" name="<?php echo esc_attr($this->domain_name); ?>[change_perpage]" class="form-control" id="change-perpage-number" min="1" max="100" value="<?php echo $per_page; ?>" data-show-pages="<?php echo $per_page; ?>">
          <button class="btn btn-default" type="button" id="resync-all-items" data-button-action="resync_all"><span class="dashicons dashicons-update"></span> <?php _e('Resync All', $this->domain_name); ?></button>
        </div>
      </div>
      
      <table class="table table-bordered table-striped table-hover" id="qiita-items">
        <thead>
          <tr>
          <?php foreach ($_indices as $_index_slug => $_index_label) : ?>
            <th class="item-<?php echo $_index_slug; ?>"><?php echo $_index_label; ?></th>
          <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($_items as $_i => $_item) : ?>
          <?php
  $_item_id = get_post_meta( $_item->ID, 'wpqt_item_id', true );
  $_item_tags = array();
  foreach ( wp_get_post_tags( $_item->ID ) as $_tag ) {
    $_item_tags[] = $_tag->name;
  }
  $_stocks = get_post_meta( $_item->ID, 'wpqt_stocks', true );
  $_stocks = empty($_stocks) ? $this->get_item_stocks( $_item->id ) : $_stocks;
  $_excerpt = wp_trim_words( str_replace('　', '', $_item->body), 32, '...' );
  $_switching_item = $_qiita_user_meta['team_only'] ? 'coediting' : 'private';
  // $_switching_item_value = $_item->$_switching_item ? __('True', $this->domain_name) : __('False', $this->domain_name);
  $_switching_item_value = $_item->$_switching_item ? 'marker text-success' : 'no-alt text-muted';
?>
          <tr id="post-<?php echo esc_attr($_item->ID); ?>">
            <td class="item-index"><?php echo $start_index + $_i + 1; ?></td>
            <td class="item-title"><a href="<?php echo esc_url($_item->guid); ?>" target="_blank"><span class="wpqt-qiita-square"></span> <?php echo $_item->post_title; ?></a></td>
            <td class="item-stocks"><?php echo $_stocks; ?></td>
            <?php if (array_key_exists('excerpt', $_indices)) : ?><td class="item-excerpt"><?php echo $_excerpt; ?></td><?php endif; ?>
            <td class="item-tags"><?php echo implode(', ', $_item_tags); ?></td>
            <td class="item-<?php echo $_switching_item; ?>"><span class="dashicons dashicons-<?php echo $_switching_item_value; ?>"></span></td>
            <td class="item-created"><?php echo $this->wpqt_date_format( $_item->post_date ); ?></td>
            <td class="item-updated"><?php echo $this->wpqt_date_format( $_item->post_modified ); ?></td>
            <td class="item-sync">
              <button class="btn btn-default btn-sm" type="button" data-button-action="resync_item" data-post-id="<?php echo esc_attr( $_item->ID ); ?>" data-item-id="<?php echo esc_attr( $_item_id ); ?>"><span class="dashicons dashicons-update"></span></button>
              <button class="btn btn-default btn-sm" type="button" data-button-action="remove_item" data-post-id="<?php echo esc_attr( $_item->ID ); ?>" data-item-id="<?php echo esc_attr( $_item_id ); ?>"><span class="dashicons dashicons-trash"></span></button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
          <?php foreach ($_indices as $_index_slug => $_index_label) : ?>
            <th class="item-<?php echo $_index_slug; ?>"><?php echo $_index_label; ?></th>
          <?php endforeach; ?>
          </tr>
        </tfoot>
      </table>
      
      <div class="panel-footer form-inline">
<?php
$total_items = $_qiita_user_meta['items_count'];
$max_page = ceil($total_items / $per_page);
?>
      <?php if ($max_page > 1) : ?>
        <ul class="pagination" data-current-page="<?php echo $current_page; ?>">
          <li<?php if ($current_page === 1) : ?> class="disabled"<?php endif; ?>><a href="#" data-to-page="<?php echo $current_page > 1 ? $current_page - 1 : 1; ?>"<?php if ($current_page === 1) : ?> disabled="disabled"<?php endif; ?>><span class="dashicons dashicons-arrow-left-alt2"></span></a></li>
        <?php for ($_num=1; $_num<=$max_page; $_num++) : ?>
          <li<?php if ($current_page === $_num) : ?> class="active"<?php endif; ?>><a href="#" data-to-page="<?php echo $_num; ?>"><?php echo $_num; ?></a></li>
        <?php endfor; ?>
          <li<?php if ($current_page === $max_page) : ?> class="disabled"<?php endif; ?>><a href="#" data-to-page="<?php echo $current_page < $max_page ? $current_page + 1 : $max_page; ?>"<?php if ($current_page === $max_page) : ?> disabled="disabled"<?php endif; ?>><span class="dashicons dashicons-arrow-right-alt2"></span></a></li>
        </ul>
      <?php endif; ?>
        <div class="pull-right">
          <!-- <button type="button" class="btn btn-default" id="change-perpage" data-button-action="change_perpage"><?php _e('Change Items', $this->domain_name); ?></button> -->
        </div>
      </div>
    </div><!-- /.panel -->
  <?php endif; ?>
<?php endif; ?>
    </div><!-- /.tab-pane#items -->
    <div role="tabpanel" class="tab-pane<?php if ($tmpl_current_tab === 'comments') : ?> active<?php endif; ?>" id="comments">
<?php if ('comments' === $tmpl_current_tab) : ?>
<?php endif; ?>
    </div><!-- /.tab-pane#comments -->
    <div role="tabpanel" class="tab-pane<?php if ($tmpl_current_tab === 'extra') : ?> active<?php endif; ?>" id="extra">
<?php if ('extra' === $tmpl_current_tab) : ?>
      <h4 class="heading-tab"><?php _e('The below features will have been available in this plugin.', $this->domain_name); ?></h4>
      
<?php
$icon_fonts = array(
  'qiita-q' 					=> array( 'desc' => __('Q mark logo of Qiita', $this->domain_name), 'code' => '<span class="wpqt-qiita-q"></span>', 'content' => '\e900' ), 
  'qiita-favicon' 			=> array( 'desc' => __('Logo of the favicon of Qiita', $this->domain_name), 'code' => '<span class="wpqt-qiita-favicon"></span>', 'content' => '\e901' ), 
  'qiita-favicon-color' 		=> array( 'desc' => __('Logo of the favicon of Qiita (multiple color)', $this->domain_name), 'code' => '<span class="wpqt-qiita-favicon-color"><span class="path1"></span><span class="path2"></span></span>', 'content' => '\e902\e903' ), 
  'qiita-favicon-reversal' 	=> array( 'desc' => __('Logo of the favicon of Qiita (reversal)', $this->domain_name), 'code' => '<span class="wpqt-qiita-favicon-reversal"></span>', 'content' => '\e904' ), 
  'qiita-square' 			=> array( 'desc' => __('Logo of Qiita in the rounded square', $this->domain_name), 'code' => '<span class="wpqt-qiita-square"></span>', 'content' => '\e905' ), 
);
$shortcode_examples = array(
  'wpqt-icon' 				=> '<code>[wpqt-icon name="qiita-favicon"]</code>,<wbr><code>[wpqt-icon id="3"]</code>', 
  'wpqt-permalink' 	=> '<code>[wpqt-permalink pid="911" target="_blank"]Link to Qiita[/wpqt-permalink]</code>,<wbr><code>[wpqt-permalink iid="f9834dca40bb3d7e9c8b" html="false"]</code>', 
  'wpqt-post-stocks' 	=> '<code>[wpqt-post-stocks pid="911"]</code>,<wbr><code>[wpqt-post-stocks iid="f9834dca40bb3d7e9c8b"]</code>', 
);
?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h5><?php _e('Various Icon Fonts', $this->domain_name); ?></h5>
        </div>
        <div class="panel-body">
          <?php _e('In a state in which this plugin is enabled, you can use the following icon fonts in your site.', $this->domain_name); ?>
        </div>
        <table class="table" id="icon-fonts">
          <thead>
            <tr>
              <th class="icon-view"><?php _e('View', $this->domain_name); ?></th>
              <th class="icon-describe"><?php _e('Describe', $this->domain_name); ?></th>
              <th class="icon-code"><?php _e('Code', $this->domain_name); ?></th>
              <th class="icon-context"><?php _e('Context', $this->domain_name); ?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($icon_fonts as $_icon => $_attr) : ?>
            <tr>
              <td class="icon-view"><?php echo $_attr['code']; ?></td>
              <td class="icon-describe"><?php echo $_attr['desc']; ?></td>
              <td class="icon-code"><pre><code><?php echo esc_html($_attr['code']); ?></code></pre></td>
              <td class="icon-context"><pre><code><?php echo $_attr['content']; ?></code></pre></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      
      <div class="panel panel-default">
        <div class="panel-heading">
          <h5><?php _e('Various Shortcodes', $this->domain_name); ?></h5>
        </div>
        <div class="panel-body">
          <?php _e('After you have cooperation with Qiita via this plugin, following shortcodes will be available.', $this->domain_name); ?>
          <table class="table" id="shortcodes">
            <tbody>
            <?php foreach ( $this->shortcodes as $_shortcode => $_atts ) : ?>
              <tr id="<?php echo $_shortcode; ?>">
                <td><pre><code>[<?php echo $_shortcode; ?>]</code></pre></td>
                <td><?php echo $_atts['description']; ?><div class="example"><?php echo $shortcode_examples[$_shortcode]; ?></div></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      
      <div class="panel panel-default">
        <div class="panel-heading">
          <h5><?php _e('Various Widgets', $this->domain_name); ?></h5>
        </div>
        <div class="panel-body">
          <?php _e('After you have cooperation with Qiita via this plugin, following widgets will be available.', $this->domain_name); ?>
          <ul class="list-inline" id="wpqt-widget-1">
            <li><figure id="wpqt-widget-1-view">
              <img src="<?php echo $this->plugin_dir_url; ?>/assets/images/wpqt-widget-view.png">
              <figcaption><?php _e('WP Qiita Widget View', $this->domain_name); ?></figcaption>
            </figure></li>
            <li><figure id="wpqt-widget-1-setting">
              <img src="<?php echo $this->plugin_dir_url; ?>/assets/images/wpqt-widget-setting.png">
              <figcaption><?php _e('WP Qiita Widget Settings', $this->domain_name); ?></figcaption>
            </figure></li>
          </ul>
        </div>
      </div>
      
<?php endif; ?>
    </div><!-- /.tab-pane#comments -->
  </div><!-- /.tab-content -->
  
</div><!-- /#wp-qiita-options -->