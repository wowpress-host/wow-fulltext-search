jQuery(function($) {
	//
	// settings
	//
	function onSearchEngineChange() {
		var v = $('input[name="wowfts__search_engine"]:checked').val();

		$('#tr_wowfts__wowsearch__api_key').css('display',
			(v == 'wowsearch' ? '' : 'none'));
		$('#tr_wowfts__elasticsearch__host_port').css('display',
			(v == 'elasticsearch' ? '' : 'none'));
		$('#tr_wowfts__elasticsearch__index').css('display',
			(v == 'elasticsearch' ? '' : 'none'));
	}



	onSearchEngineChange();
	$('input[name="wowfts__search_engine"]').click(onSearchEngineChange);



	$('#wowfts__obtain_api_key').click(function(e) {
		e.preventDefault();
		$('#wowfts__obtain_api_key').css('display', 'none');
		$('#wowfts__obtain_api_key_form').css('display', 'inline-block');
	});



	$('#wowfts__obtain_api_key2').click(function(e) {
		e.preventDefault();
		jQuery.post({
			url: 'https://search.wowpress.host/api/signup',
			data: {
				email: $('#wowfts__obtain_api_key_email').val()
			},
			dataType: 'json',
			error: function(e, e2, e3) {
				alert('Failed to obtain API key');
			},
			success: function(data) {
				if (data.error_message) {
					alert(data.error_message);
				} else {
					$('#wowfts__obtain_api_key_form').css('display', 'none');
					$('#wowfts__wowsearch__api_key').val(data.api_key);
				}
			}
		});
	});



	//
	// build index
	//
	var notices_count = 0;
	var ajax_timeout = null;



	var show_if = function(selector, show) {
		$(selector).css('display', (show ? '' : 'none'));
	};



	var state_set = function(mode) {
		if (mode)
			wow_search_state = mode;

		var mode = wow_search_state;

		show_if('#wowfts_status', false);
		show_if('#wow_search_build_start_outer', mode == 'start');
		show_if('#wow_search_build_restart_outer', mode == 'done' || mode == 'failed');
		show_if('#wow_search_build_continue_outer', mode == 'paused' || mode == 'failed');
		show_if('#wow_search_build_process',
			mode == 'working' || mode == 'paused' || mode == 'done' ||
			mode == 'failed');
		show_if('#wow_search_working_now', mode == 'working');
		show_if('#wow_search_working_outer', mode == 'working');
		show_if('#wow_search_done', mode == 'done');
		show_if('#wow_search_failed', mode == 'failed');
	}



	var ajax_timeout_clear = function() {
		if (ajax_timeout != null) {
			clearTimeout(ajax_timeout);
			ajax_timeout = null;
		}
	};



	var step = function(extras) {
		extras.action = 'wow_search_build';
		extras._wpnonce = wow_search_nonce;

		var react_to_result = true;
		var react_to_failure = function(data) {
			if (!react_to_result) {
				return;
			}

			react_to_result = false;

			if (!extras.failed_attempt)
				extras.failed_attempt = 0;

			extras.failed_attempt++;

			if (extras.failed_attempt > 5) {
				step_failed(data);
			} else {
				step({failed_attempt: extras.failed_attempt});
			}
		};


		ajax_timeout_clear();
		ajax_timeout = setTimeout(react_to_failure, 15000);

		jQuery.post({
			url: ajaxurl,
			data: extras,
			dataType: 'json',
			error: react_to_failure,
			success: function(data) {
				if (!react_to_result)
					return;
				if (!data || !data.status)
					return react_to_failure();

				ajax_timeout_clear();

				$('#wow_search_total').html(data.posts_all);
				$('#wow_search_processed').html(data.posts_processed);

				$('#wow_search_errors').html(data.errors_count);
				$('#wow_search_working_now').css('display', '');
				$('#wow_search_now').html(data.last_processed_description);

				notices_add(data.new_notices);

				if (data.status.substr(0, 8) == 'working.') {
					if (wow_search_state == 'working') {
						step({});
					}
				} else if (data.status == 'error.configuration') {
					step_failed({responseText: 'You didn\'t specify correct search server instance details'});
				} else if (data.status == 'done.') {
					step_done();
				} else {
					step_failed({responseText: 'unknown status ' + (data ? data.status : '')});
				}
			}
		});
	};



	var notices_add = function(notices) {
		if (!notices || !notices.length)
			return;
		if (notices_count > 100)
			return;

		for (var n = 0; n < notices.length; n++) {
			var i = notices[n];
			notices_count++;
			var notice = $('<div>');
			if (i.post_id) {
				notice.append($('<a>')
					.prop('href', 'post.php?action=edit&post=' + Number(i.post_id))
					.text(i.post_id));
				notice.append($('<span>').text(': '));
			}
			notice.append($('<span>').text(i.message));

			$('#wow_search_notices').prepend(notice);
		}
		if (notices_count > 10000) {
			$('#wow_search_notices').prepend(
				$( '<div>Too many log entries. ' +
					'Stop logging to avoid browser crash. ' +
					'Consider log to file instead.</div>' ));
		}
	};



	var step_done = function() {
		state_set('done');
	};



	var step_failed = function(error) {
		var statusText = (error.statusText ? error.statusText : '');
		var responseText = (error.responseText ? error.responseText.substr(0, 500) : '' );

		if (statusText == '' && responseText == '') {
			if (error)
				responseText = error;
			else
				responseText = 'no detailed error information returned by the server';
		}

		$('#wow_search_notices').prepend(
			$('<div>').text('Request failed: ' +
				statusText +
				' ' +
				responseText));

		state_set('failed');
	};



	$('.wow_search_build_start').click(function(e) {
		e.preventDefault();

		state_set('working');
		$('html, body').animate({ scrollTop: 0 }, "slow");
		$('#wow_search_total').html('starting...');
		$('#wow_search_processed').html('0');
		$('#wow_search_errors').html('0');
		$('#wow_search_now').html('');
		$('#wow_search_notices').html('');
		notices_count = 0;

		step({
			'wow_search_action': 'start'
		});
	});



	$('#wow_search_build_continue').click(function(e) {
		e.preventDefault();
		state_set('working');

		step({'wmlf_action': 'continue'});
	});



	$('#wow_search_build_stop').click(function(e) {
		e.preventDefault();
		state_set('paused');
	});
});
