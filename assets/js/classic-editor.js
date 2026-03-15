( function( $ ) {
	'use strict';

	var $executeBtn, $spinner, $prompt, $error, $changes, $changesText, $form, $notConfigured;

	function getContent() {
		if ( typeof tinyMCE !== 'undefined' && tinyMCE.get( 'content' ) && ! tinyMCE.get( 'content' ).isHidden() ) {
			return tinyMCE.get( 'content' ).getContent();
		}
		return $( '#content' ).val() || '';
	}

	function setContent( content ) {
		if ( typeof tinyMCE !== 'undefined' && tinyMCE.get( 'content' ) && ! tinyMCE.get( 'content' ).isHidden() ) {
			tinyMCE.get( 'content' ).setContent( content );
		}
		$( '#content' ).val( content );
	}

	function getTitle() {
		return $( '#title' ).val() || '';
	}

	function setTitle( title ) {
		$( '#title' ).val( title );
	}

	function checkSettings() {
		$.ajax( {
			url: aiReviewClassic.rest_url + 'settings-status',
			method: 'GET',
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', aiReviewClassic.nonce );
			},
			success: function( response ) {
				if ( ! response.configured ) {
					$form.hide();
					$notConfigured.show();
				}
			},
			error: function() {
				$form.hide();
				$notConfigured.show();
			},
		} );
	}

	function executeReview() {
		$executeBtn.prop( 'disabled', true ).text( aiReviewClassic.i18n.processing );
		$spinner.addClass( 'is-active' );
		$error.hide();
		$changes.hide();

		$.ajax( {
			url: aiReviewClassic.rest_url + 'review',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify( {
				post_title: getTitle(),
				post_content: getContent(),
				prompt: $prompt.val(),
			} ),
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', aiReviewClassic.nonce );
			},
			success: function( response ) {
				if ( response.success ) {
					if ( response.title ) {
						setTitle( response.title );
					}
					if ( response.content ) {
						setContent( response.content );
					}
					if ( response.changes ) {
						$changesText.text( response.changes );
						$changes.show();
					}
					$prompt.val( '' );
				}
			},
			error: function( xhr ) {
				var msg = aiReviewClassic.i18n.error;
				if ( xhr.responseJSON && xhr.responseJSON.message ) {
					msg = xhr.responseJSON.message;
				}
				$error.find( 'p' ).text( msg );
				$error.show();
			},
			complete: function() {
				$executeBtn.prop( 'disabled', false ).text( aiReviewClassic.i18n.execute );
				$spinner.removeClass( 'is-active' );
			},
		} );
	}

	$( document ).ready( function() {
		$executeBtn    = $( '#ai-review-execute' );
		$spinner       = $( '#ai-review-spinner' );
		$prompt        = $( '#ai-review-prompt' );
		$error         = $( '#ai-review-error' );
		$changes       = $( '#ai-review-changes' );
		$changesText   = $( '#ai-review-changes-text' );
		$form          = $( '#ai-review-form' );
		$notConfigured = $( '#ai-review-not-configured' );

		if ( ! $executeBtn.length ) {
			return;
		}

		checkSettings();
		$executeBtn.on( 'click', executeReview );
	} );
} )( jQuery );
