"use strict";

jQuery(function($) {
	// adds support for hyphens in form field names
	$.extend(FormSerializer.patterns, {
		validate: /^[a-z][a-z0-9_-]*(?:\[(?:\d*|[a-z0-9_-]+)\])*$/i,
		key:      /[a-z0-9_-]+|(?=\[\])/gi,
		named:    /^[a-z0-9_-]+$/i
	});

	$('.download-sample-util').click(function(e) {
		e.preventDefault();
		var payload = {
			name: 'sample-utility.php',
			type: "text/plain;charset=utf-8",
			data: $('.sample-util-source').html()
		};
		download_payload( payload );
	});

	$(".wp-utilities").accordion({
		header: 'header',
		heightStyle: 'content',
		collapsible: true,
		active: false
	});
	$(".wp-utility .datepicker").datepicker({
		minDate : new Date(),
		dateFormat : 'yy-mm-dd'
	});
	// this really should be a mutation observer, but it's a hack on a hack
	// because css resizes don't !@#$@#$ing fire resize events!
	var $input_forms = $(".wp-utilities .wp-utility-input-form");
	setInterval(function() {
		$input_forms.each(function() {
			var $this = $(this);
			var height = $this.height();
			var oldHeight = $this.data('oldHeight');
			if( height != oldHeight ) {
				$this.data('oldHeight', height);
				$this.closest(".wp-utilities").accordion('refresh');
			}
		});
	}, 50);
	// end nasty interface hack

	var $fileInputs = $(".wp-utility .wp-utility-input-form form").find("input[type='file']");
	$fileInputs.change(function(e) {
		var $this = $(this);
		var name = $this.attr('name');
		var multiple = $this[0].hasAttribute('multiple');
		var textType = /text.*/;
		var content = [];
		$.each($this[0].files, function(i, file) {
			var result = {
				'name'    : file.name,
				'type'    : file.type,
				'contents': ''
			}
			var reader = new FileReader();
			reader.onload = function(e) {
				result.contents = reader.result;
			}
			if (file.type.match(textType)) {
				reader.readAsText(file);
			} else {
				reader.readAsDataURL(file);
			}
			if( multiple ) {
				content.push(result);
			} else {
				content = result;
			}
		});
		$this.data('content', content);
	});
	$('.wp_util_button_run').click(function(e) {
		e.preventDefault();
		if( !$(this).attr( 'disabled' ) ) {
			var $util = $(this).closest('.wp-utility');
			var $form = $util.find('form.wp-util-input');
			var has_empty_required = false;
			$form.find('[required]').each(function() {
				if( this.value === '' ) {
					has_empty_required = true;
					return false;
				}
			});
			if( has_empty_required ) {
				alert("You must populate all required fields before submitting");
				return false;
			}
			var slug = $(this).data('util-slug');
			$(this).attr( 'disabled', 'disabled' );
			$(this).data('original-text', $(this).html());
			$(this).html("Processing...");
			$util.find('.response').html("").slideDown();
			util_ajax_run( $util, slug );
		} else {
			alert('you must wait for the utility to complete, before starting over');
		}
	});
	$('.wp_util_button_schedule').click(function(e) {
		e.preventDefault();
		if( !$(this).attr( 'disabled' ) ) {
			var $util = $(this).closest('.wp-utility');
			var $form = $util.find('form.wp-util-input');
			var has_empty_required = false;
			$form.find('[required]').each(function() {
				if( this.value === '' ) {
					has_empty_required = true;
					return false;
				}
			});
			if( has_empty_required ) {
				alert("You must populate all required fields before submitting");
				return false;
			}
			var slug = $(this).data('util-slug');
			$(this).attr( 'disabled', 'disabled' );
			$(this).data('original-text', $(this).html());
			$(this).html("Processing...");
			util_ajax_schedule( $util, slug );
		} else {
			alert('you must wait for processing to complete.');
		}
	});
	$('.wp_util_button_cancel').click(function(e) {
		e.preventDefault();
		if( !$(this).attr( 'disabled' ) ) {
			var $util = $(this).closest('.wp-utility');
			var slug = $(this).data('util-slug');
			$(this).attr( 'disabled', 'disabled' );
			$(this).data('original-text', $(this).html());
			$(this).html("Processing...");
			util_ajax_cancel( $util, slug );
		} else {
			alert('you must wait for processing to complete.');
		}
	});
	function get_form_files( $form ) {
		var files = {};
		$form.find("input[type='file']").each(function() {
			var $this = $(this);
			var name = $this.attr('name');
			var content = $this.data('content');
			if( name.indexOf('[') === -1 ) {
				files[name] = content;
			} else {
				alert("WARNING: File inputs with bracketed names are not yet supported");
			}
		});
		return files;
	}
	function util_ajax_run( $wrap, slug, state, noform ) {
		if( typeof state === 'undefined' || !state ) {
			state = {};
		}
		var atts  = [];
		var files = [];
		if( typeof noform === 'undefined' || !noform ) {
			var $form = $wrap.find('form.wp-util-input');
			if( $form.length ) {
				atts  = $form.serializeObject();
				files = get_form_files($form);
			}
		}
		var nonce = $wrap.find('.wp_util_nonce').val();
		var data = {
			'action': 'wp_util_script_run',
			'nonce' : nonce,
			'slug'  : slug,
			'state' : state,
			'atts'  : atts,
			'files' : files
		};

		$.post( ajaxurl, data, function(response) {
			if( !response || typeof response.message == 'undefined') {
				var content = response;
				response = {
					'state' : 'complete',
					'message': content
				};
			}
			var t = new Date();
			var $timestamp = $('<div></div>').addClass('wp-util-time').text( mysql_format( t ) );
			var $message   = $('<div></div>').addClass('wp-util-message').html( response.message );
			var $meswrap   = $('<div></div>').addClass('wp-util-row').append( $timestamp ).append( $message );
			if( response.state === 'complete' ) {
				$meswrap.css('color', '#228B22');
			} else if( response.state === 'error' ) {
				$meswrap.css('color', '#B22222');
			}
			var $response  = $wrap.find('.response').append($meswrap);
			$response.scrollTop( $response[0].scrollHeight );
			if( typeof response.payload !== 'undefined' && response.payload ) {
				var payload = response.payload;
				if( typeof payload !== 'object' ) {
					payload = {
						data : payload
					};
				}
				if( typeof payload.type === 'undefined' || !payload.type ) {
					payload.type = "text/plain;charset=utf-8";
				}
				if( typeof payload.name === 'undefined' || !payload.name ) {
					payload.name = "utility-runner-result.txt";
				}
				download_payload( payload );
			}
			if( typeof response.state == 'undefined' ) {
				response.state = 'complete';
			}
			var $button = $wrap.find('.wp_util_button_run');
			if( response.state == 'complete' || response.state == 'error') {
				$button.removeAttr('disabled');
				$button.html($button.data('original-text'));
			} else {
				if( typeof response.noform === 'undefined' || !response.noform ) {
					response.noform = false;
				}
				util_ajax_run( $wrap, slug, response.state, response.noform );
			}
		}, 'json' );
	}
	function util_ajax_schedule( $wrap, slug ) {
		var atts  = [];
		var files = [];
		var cron  = [];
		var $form = $wrap.find('form.wp-util-input');
		if( $form.length ) {
			atts  = $form.serializeObject();
			files = get_form_files($form);
		}
		var $cron = $wrap.find('form.wp_util_schedule');
		if( $form.length ) {
			cron  = $cron.serializeObject();
		}
		var nonce = $wrap.find('.wp_util_nonce').val();
		var data = {
			'action': 'wp_util_script_schedule_cron',
			'nonce' : nonce,
			'slug'  : slug,
			'atts'  : atts,
			'files' : files,
			'cron'  : cron
		};

		$.post( ajaxurl, data, function(response) {
			if( !response || typeof response.message == 'undefined') {
				var content = response;
				response = {
					'state' : 'complete',
					'message': content
				};
			}
			if( response.state === 'complete' ) {
				location.reload();
			} else {
				alert('Error: ' + response.message);
			}
			var $button = $wrap.find('.wp_util_button_schedule');
			$button.removeAttr('disabled');
			$button.html($button.data('original-text'));
		}, 'json' );
	}
	function util_ajax_cancel( $wrap, slug ) {
		var nonce = $wrap.find('.wp_util_nonce').val();
		var data = {
			'action': 'wp_util_script_cancel_cron',
			'nonce' : nonce,
			'slug'  : slug,
		};

		$.post( ajaxurl, data, function(response) {
			if( !response || typeof response.message == 'undefined') {
				var content = response;
				response = {
					'state' : 'complete',
					'message': content
				};
			}
			if( response.state === 'complete' ) {
				location.reload();
			} else {
				alert('Error: ' + response.message);
			}
			var $button = $wrap.find('.wp_util_button_cancel');
			$button.removeAttr('disabled');
			$button.html($button.data('original-text'));
		}, 'json' );
	}
	function mysql_format( d ) {
		var twoDigits = function(d) {
			if(0 <= d && d < 10) return "0" + d.toString();
			if(-10 < d && d < 0) return "-0" + (-1*d).toString();
			return d.toString();
		}
		return d.getUTCFullYear() + "-" + twoDigits(1 + d.getUTCMonth()) + "-" + twoDigits(d.getUTCDate()) + " " + twoDigits(d.getUTCHours()) + ":" + twoDigits(d.getUTCMinutes()) + ":" + twoDigits(d.getUTCSeconds());
	}
	function download_payload( payload ) {
		var file = new File([payload.data], payload.name, {type: payload.type});
		saveAs(file);
	}
});
