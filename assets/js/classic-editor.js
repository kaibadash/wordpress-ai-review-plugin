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

	function showError( parts ) {
		$error.find( 'p' ).css( {
			'white-space': 'pre-wrap',
			'word-break': 'break-all',
			'font-size': '12px',
		} ).text( parts.join( '\n' ) );
		$error.show();
	}

	function formatErrorFromJSON( json ) {
		var parts = [];
		parts.push( json.message || 'Unknown error' );
		if ( json.code ) {
			parts.push( '[' + json.code + ']' );
		}
		if ( json.detail ) {
			parts.push( json.detail );
		}
		if ( json.data && json.data.detail ) {
			parts.push( json.data.detail );
		}
		if ( json.url || ( json.data && json.data.url ) ) {
			parts.push( 'URL: ' + ( json.url || json.data.url ) );
		}
		if ( json.model || ( json.data && json.data.model ) ) {
			parts.push( 'Model: ' + ( json.model || json.data.model ) );
		}
		return parts;
	}

	function resetUI() {
		$executeBtn.prop( 'disabled', false ).text( aiReviewClassic.i18n.execute );
		$spinner.removeClass( 'is-active' );
	}

	function executeReview() {
		$executeBtn.prop( 'disabled', true ).text( aiReviewClassic.i18n.processing );
		$spinner.addClass( 'is-active' );
		$error.hide();
		$changes.hide();

		fetch( aiReviewClassic.rest_url + 'review-stream', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': aiReviewClassic.nonce,
			},
			body: JSON.stringify( {
				post_title: getTitle(),
				post_content: getContent(),
				prompt: $prompt.val(),
			} ),
		} )
			.then( function( response ) {
				if ( ! response.ok ) {
					return response.json().catch( function() {
						return null;
					} ).then( function( json ) {
						if ( json ) {
							showError( formatErrorFromJSON( json ) );
						} else {
							showError( [
								aiReviewClassic.i18n.error,
								'HTTP ' + response.status + ' ' + response.statusText,
							] );
						}
						throw null; // Signal error handled.
					} );
				}
				return readSSEStream( response );
			} )
			.then( function( result ) {
				if ( ! result ) {
					return;
				}
				if ( result.title ) {
					setTitle( result.title );
				}
				if ( result.body ) {
					setContent( result.body );
				}
				if ( result.changes ) {
					$changesText.text( result.changes );
					$changes.show();
				}
				$prompt.val( '' );
			} )
			.catch( function( err ) {
				if ( err === null ) {
					return; // Already handled.
				}
				if ( err && err.message ) {
					showError( formatErrorFromJSON( err ) );
				} else {
					showError( [ aiReviewClassic.i18n.error, String( err ) ] );
				}
			} )
			.finally( resetUI );
	}

	function readSSEStream( response ) {
		var reader = response.body.getReader();
		var decoder = new TextDecoder();
		var buffer = '';
		var fullContent = '';

		function processChunks() {
			return reader.read().then( function( result ) {
				if ( result.done ) {
					return;
				}

				buffer += decoder.decode( result.value, { stream: true } );
				var lines = buffer.split( '\n' );
				buffer = lines.pop();

				for ( var i = 0; i < lines.length; i++ ) {
					var line = lines[ i ];
					if ( line.indexOf( 'data: ' ) !== 0 ) {
						continue;
					}
					var data = line.slice( 6 );
					if ( data === '[DONE]' ) {
						continue;
					}

					var parsed = JSON.parse( data );

					if ( parsed.error ) {
						throw parsed;
					}

					var delta = parsed.choices && parsed.choices[ 0 ] &&
						parsed.choices[ 0 ].delta && parsed.choices[ 0 ].delta.content;
					if ( delta ) {
						fullContent += delta;
					}
				}

				return processChunks();
			} );
		}

		return processChunks().then( function() {
			if ( ! fullContent ) {
				throw { message: 'No content received from the AI service.', code: 'llm_empty_response' };
			}
			return JSON.parse( fullContent );
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
