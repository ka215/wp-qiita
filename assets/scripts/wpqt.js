$(document).ready(function(){
  
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
  
  // Display modal on loaded
  if ($('#wpQiitaModal').size() === 1 && $('#messages').size() === 1) {
    $('#wpQiitaModal').find('.modal-body').html($('#messages').html()).end().modal('show');
  }
  
  // Display modal on whenever called
  var displayModal = function( content ){
    if ($('#wpQiitaModal').size() === 1) {
      $('#wpQiitaModal').find('.modal-body').html(content).end().modal('show');
    }
  };
  
  $('.nav-tabs>li>a[data-toggle=tab]').on('click', function(e){
    var parse_url = location.href.split('?');
    var redirect_to = parse_url[0] + '?';
    redirect_to += 'page=' + $.QueryString.page + '&tab=' + $(this).attr('aria-controls');
    location.href = redirect_to;
  });
  
  $('#wp-qiita-options').find('button').on('click', function(e){
    var is_submit = false;
    var form = $('#wp-qiita-admin-form');
    form.children('[name=action]').val($(this).data().buttonAction);
    switch($(this).data().buttonAction) {
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
      case 'reload_items': 
        var per_page = $('#change-perpage-number').val();
        var parse_url = location.href.split('?');
        var redirect_to = parse_url[0] + '?';
        redirect_to += 'page=' + $.QueryString.page;
        redirect_to += typeof $.QueryString.tab !== 'undefined' ? '&tab=' + $.QueryString.tab : '';
        redirect_to += '&pp=' + per_page;
        form.attr('action', redirect_to);
        is_submit = true;
        
        break;
      default:
        
        break;
    }
    
    if (is_submit) {
      form.submit();
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
  
});