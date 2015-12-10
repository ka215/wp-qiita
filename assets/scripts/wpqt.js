/* ========================================================================
 * DOM-based Routing
 * Based on http://goo.gl/EUTi53 by Paul Irish
 *
 * Only fires on body classes that match. If a body class contains a dash,
 * replace the dash with an underscore when adding it to the object below.
 *
 * .noConflict()
 * The routing is enclosed within an anonymous function so that you can
 * always reference jQuery with $, even when in .noConflict() mode.
 * ======================================================================== */

(function($) {
  
  /**
   * Utility functions
   * Return as an object by parsing the query string of the current URL
   */
  $.QueryString = (function(queries) {
    if ('' === queries) { return {}; }
    var results = {};
    for (var i=0; i<queries.length; ++i) {
      var param = queries[i].split('=');
      if (param.length !== 2) { continue; }
      results[param[0]] = decodeURIComponent(param[1].replace(/\+/g, ' '));
    }
    return results;
  })(window.location.search.substr(1).split('&'));
  
  
  // Use this variable to set up the common and page specific functions. If you
  // rename this variable, you will also need to rename the namespace below.
  var WPQiita = {
    // All pages
    'common': {
      init: function() {
        // JavaScript to be fired on all pages
        
        
      },
      finalize: function() {
        // JavaScript to be fired on all pages, after page specific JS is fired
        
      }
    },
    'wp_admin': {
      init: function() {
        // JavaScript to be fired on admin pages for `WP Qiita`
        
        // Display modal on loaded
        if ($('#wpQiitaModal').size() === 1 && $('#messages').size() === 1) {
          $('#wpQiitaModal').find('.modal-body').html($('#messages').html()).end().modal('show');
          $('#wpQiitaModal').on('hidden.bs.modal', function(e){
            // window.location.href = e.currentTarget.baseURI;
            window.location.reload();
          });
        }
        
        // Display modal on whenever called
        var displayModal = function( content ){
          if ($('#wpQiitaModal').size() === 1) {
            $('#wpQiitaModal').find('.modal-body').html(content).end().modal('show');
          }
        };
        
        $('.nav-tabs>li>a[data-toggle=tab]').on('click', function(e){
          $('.tab-pane.active').removeClass('loaded');
          $('.loader').css({ position: 'fixed', display: 'block' });
          var parse_url = location.href.split('?');
          var redirect_to = parse_url[0] + '?';
          redirect_to += 'page=' + $.QueryString.page + '&tab=' + $(this).attr('aria-controls');
          location.href = redirect_to;
        });
        
        $('#change-perpage-number').on('change blur', function(){
          var form = $('#wp-qiita-admin-form');
          var per_page = Number( $(this).val() );
          var default_views = $(this).data().showPages;
          if ( per_page !== default_views ) {
            var parse_url = location.href.split('?');
            var redirect_to = parse_url[0] + '?';
            redirect_to += 'page=' + $.QueryString.page;
            redirect_to += typeof $.QueryString.tab !== 'undefined' ? '&tab=' + $.QueryString.tab : '';
            redirect_to += '&pp=' + per_page;
            //form.attr('action', redirect_to);
            //form.submit();
            location.href = redirect_to;
          } else {
            return false;
          }
        });
        
        $('#wp-qiita-options').find('button').on('click', function(e){
          var is_submit = false;
          var form = $('#wp-qiita-admin-form');
          var buttonAction = $(this).data().buttonAction;
          form.children('[name=action]').val(buttonAction);
          switch(buttonAction) {
            case 'activate_oauth': 
              if ($('#wpqt-client_id').val() !== '') {
                var clientid_field = $('#wpqt-client_id').clone();
                form.append(clientid_field.attr('type', 'hidden'));
                if ($('#wpqt-client_secret').val() !== '') {
                  var clientsecret_field = $('#wpqt-client_secret').clone();
                  form.append(clientsecret_field.attr('type', 'hidden'));
                  var count_check_scope = 0;
                  $('[id^=wpqt-scope-').each(function(){
                    var scope_field;
                    if ($(this).prop('checked')) {
                      count_check_scope += 1;
                      scope_field = $(this).clone();
                      form.append(scope_field.addClass('sr-only'));
                    }
                  });
                  if (count_check_scope > 0) {
                    is_submit = true;
                  } else {
                    displayModal('The scope has not been checked.');
                  }
                } else {
                  displayModal('Client Secret has not been entered.');
                }
              } else {
                displayModal('Client ID has not been entered.');
              }
              
              break;
            case 'activate_token': 
              if ($('#wpqt-access_token').val() !== '') {
                var token_field = $('#wpqt-access_token').clone();
                form.append(token_field.attr('type', 'hidden'));
                is_submit = true;
              } else {
                displayModal('error!');
              }
              
              break;
            case 'inactivate': 
              if ($('#wpqt-inactivate_flag').val() === 'true') {
                var inactivate_field = $('#wpqt-inactivate_flag').clone();
                form.append(inactivate_field);
                is_submit = true;
              } else {
                displayModal('error!');
              }
              
              break;
            case 'advanced_setting': 
              if ($('#wpqt-advanced_setting').val() === 'true') {
                $('.activated-options').find('[id^=wpqt]').each(function(){
                  if ( 'INPUT' === $(this).context.tagName && $(this).attr('type') !== 'hidden' ) {
                    var elm = $(this).clone();
                    if ( elm.attr('type') === 'checkbox' ) {
                      elm.val( elm.prop('checked') );
                    } else
                    if ( elm.attr('type') === 'radio' ) {
                    }
                    form.append( elm.attr( 'type', 'hidden' ) );
                  }
                });
                is_submit = true;
              } else {
                displayModal('error!');
              }
              break;
            case 'sync_description': 
              var wpqtDescription = $('#user_description').val();
              var wpqtDescriptionName = $('#user_description').attr('name').replace('user', 'wp-qiita');
              form.append( '<input type="hidden" name="'+ wpqtDescriptionName +'" value="'+ wpqtDescription +'">' );
              is_submit = true;
              break;
            case 'reacquire_profile': 
            case 'initial_sync': 
            case 'resync_all': 
              $('.tab-pane.active').removeClass('loaded');
              $('.loader').css({ position: 'fixed', display: 'block' });
              is_submit = true;
              break;
            case 'resync_item': 
            case 'remove_item': 
              form.append( '<input type="hidden" name="wp-qiita[post_id]" value="'+ $(this).data().postId +'">' );
              form.append( '<input type="hidden" name="wp-qiita[item_id]" value="'+ $(this).data().itemId +'">' );
              is_submit = true;
              break;
            default:
              console.log( buttonAction );
              
              break;
          }
          
          if (is_submit) {
            $('.tab-pane.active').removeClass('loaded');
            $('.loader').css({ position: 'fixed', display: 'block' });
            return form.submit();
          } else {
            return false;
          }
          
        });
        
        // Pagenation
        $('.pagination').find('a').on('click', function(e){
          var per_page = $('#change-perpage-number').val();
          var parse_url = location.href.split('?');
          var redirect_to = parse_url[0] + '?';
          redirect_to += 'page=' + $.QueryString.page;
          redirect_to += typeof $.QueryString.tab !== 'undefined' ? '&tab=' + $.QueryString.tab : '';
          redirect_to += '&cp=' + $(this).data('toPage');
          redirect_to += '&pp=' + per_page;
          location.href = redirect_to;
        });
        
      },
      finalize: function() {
        // JavaScript to be fired on admin pages for `WP Qiita`, after page specific JS is fired
        
        if ( $('#' + $.QueryString.tab).hasClass('active') ) {
          $('.loader').css({ position: 'absolute', display: 'none' });
          $('#' + $.QueryString.tab).addClass('loaded');
        }
        if ( $('.tab-pane').hasClass('loaded') ) {
          $('.loader').css({ position: 'absolute', display: 'none' });
        }
        
      }
    },
  };
  
  // The routing fires all common scripts, followed by the page specific scripts.
  // Add additional events for more control over timing e.g. a finalize event
  var UTIL = {
    fire: function(func, funcname, args) {
      var fire;
      var namespace = WPQiita;
      funcname = (funcname === undefined) ? 'init' : funcname;
      fire = func !== '';
      fire = fire && namespace[func];
      fire = fire && typeof namespace[func][funcname] === 'function';

      if (fire) {
        namespace[func][funcname](args);
      }
    },
    loadEvents: function() {
      // Fire common init JS
      UTIL.fire('common');

      // Fire page-specific init JS, and then finalize JS
      $.each(document.body.className.replace(/-/g, '_').split(/\s+/), function(i, classnm) {
        UTIL.fire(classnm);
        UTIL.fire(classnm, 'finalize');
      });

      // Fire common finalize JS
      UTIL.fire('common', 'finalize');
    }
  };

  // Load Events
  $(document).ready(UTIL.loadEvents);

})(jQuery); // Fully reference jQuery after this point.
