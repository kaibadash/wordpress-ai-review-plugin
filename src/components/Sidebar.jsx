import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { parse } from '@wordpress/blocks';
import { PanelBody, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import WarningNotice from './WarningNotice';
import PromptInput from './PromptInput';
import ExecuteButton from './ExecuteButton';
import ChangesNotice from './ChangesNotice';

/**
 * Parse an SSE stream from the LLM proxy and return the accumulated JSON result.
 *
 * @param {Response} response Fetch Response with SSE body.
 * @return {Promise<Object>} Parsed JSON object with title, body, changes.
 */
async function parseSSEStream( response ) {
	const reader = response.body.getReader();
	const decoder = new TextDecoder();
	let buffer = '';
	let fullContent = '';

	while ( true ) {
		const { done, value } = await reader.read();
		if ( done ) {
			break;
		}

		buffer += decoder.decode( value, { stream: true } );
		const lines = buffer.split( '\n' );
		buffer = lines.pop();

		for ( const line of lines ) {
			if ( ! line.startsWith( 'data: ' ) ) {
				continue;
			}
			const data = line.slice( 6 );
			if ( data === '[DONE]' ) {
				continue;
			}

			const parsed = JSON.parse( data );

			// LLM API error forwarded by PHP.
			if ( parsed.error ) {
				throw parsed;
			}

			const delta = parsed.choices?.[ 0 ]?.delta?.content;
			if ( delta ) {
				fullContent += delta;
			}
		}
	}

	if ( ! fullContent ) {
		throw { code: 'llm_empty_response', message: 'No content received from the AI service.' };
	}

	const result = JSON.parse( fullContent );
	if ( ! result.title || ! result.body || ! result.changes ) {
		throw { code: 'llm_invalid_format', message: 'Unexpected response format.', data: { detail: fullContent } };
	}

	return result;
}

/**
 * Build a detailed error string from various error shapes.
 *
 * @param {*} err - Error from apiFetch (object with code/message/data), Response, string, or Error.
 * @return {string}
 */
function formatError( err ) {
	// apiFetch parsed JSON error (WP_Error response)
	if ( err && typeof err === 'object' && ( err.code || err.data ) ) {
		const parts = [ err.message || 'Unknown error' ];
		if ( err.code ) {
			parts.push( '[' + err.code + ']' );
		}
		if ( err.data?.detail ) {
			parts.push( err.data.detail );
		}
		if ( err.data?.url ) {
			parts.push( 'URL: ' + err.data.url );
		}
		if ( err.data?.model ) {
			parts.push( 'Model: ' + err.data.model );
		}
		if ( err.data?.status ) {
			parts.push( 'HTTP ' + err.data.status );
		}
		return parts.join( '\n' );
	}

	// Standard Error object (network failure, timeout, non-JSON response, etc.)
	if ( err instanceof Error ) {
		return err.message + ( err.stack ? '\n' + err.stack : '' );
	}

	// String or other primitive
	if ( typeof err === 'string' ) {
		return err;
	}

	// Fallback: dump as JSON
	try {
		return JSON.stringify( err, null, 2 );
	} catch {
		return String( err );
	}
}

const Sidebar = () => {
	const [ prompt, setPrompt ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ isConfigured, setIsConfigured ] = useState( null );
	const [ changes, setChanges ] = useState( '' );

	const postTitle = useSelect( ( select ) => {
		return select( 'core/editor' ).getEditedPostAttribute( 'title' );
	}, [] );

	const postContent = useSelect( ( select ) => {
		return select( 'core/editor' ).getEditedPostContent();
	}, [] );

	const { resetBlocks } = useDispatch( 'core/block-editor' );
	const { editPost } = useDispatch( 'core/editor' );

	useEffect( () => {
		apiFetch( {
			path: 'ai-review/v1/settings-status',
		} )
			.then( ( response ) => {
				setIsConfigured( response.configured );
			} )
			.catch( () => {
				setIsConfigured( false );
			} );
	}, [] );

	const handleExecute = async () => {
		setIsLoading( true );
		setError( null );
		setChanges( '' );

		try {
			const response = await fetch(
				aiReviewData.rest_url + 'review-stream',
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': aiReviewData.nonce,
					},
					body: JSON.stringify( {
						post_title: postTitle,
						post_content: postContent,
						prompt,
					} ),
				}
			);

			if ( ! response.ok ) {
				const errBody = await response.json().catch( () => null );
				throw errBody || new Error( 'HTTP ' + response.status );
			}

			const result = await parseSSEStream( response );

			if ( result.title ) {
				editPost( { title: result.title } );
			}
			if ( result.body ) {
				const blocks = parse( result.body );
				resetBlocks( blocks );
			}
			if ( result.changes ) {
				setChanges( result.changes );
			}
			setPrompt( '' );
		} catch ( err ) {
			setError( formatError( err ) );
		} finally {
			setIsLoading( false );
		}
	};

	if ( isConfigured === null ) {
		return (
			<PanelBody>
				<p>{ __( 'Loading...', 'ai-review' ) }</p>
			</PanelBody>
		);
	}

	if ( ! isConfigured ) {
		return (
			<PanelBody>
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Please complete the LLM settings. Go to Settings → AI Review.',
						'ai-review'
					) }
				</Notice>
			</PanelBody>
		);
	}

	return (
		<PanelBody>
			<WarningNotice />

			<div style={ { marginTop: '12px' } }>
				<PromptInput
					value={ prompt }
					onChange={ setPrompt }
					disabled={ isLoading }
				/>
			</div>

			{ error && (
				<Notice
					status="error"
					isDismissible={ true }
					onDismiss={ () => setError( null ) }
				>
					<pre style={ { whiteSpace: 'pre-wrap', wordBreak: 'break-all', margin: 0, fontSize: '12px' } }>
						{ error }
					</pre>
				</Notice>
			) }

			<div style={ { marginTop: '12px' } }>
				<ExecuteButton
					onClick={ handleExecute }
					disabled={ isLoading }
					isLoading={ isLoading }
				/>
			</div>

			{ changes && (
				<div style={ { marginTop: '12px' } }>
					<ChangesNotice
						changes={ changes }
						onDismiss={ () => setChanges( '' ) }
					/>
				</div>
			) }
		</PanelBody>
	);
};

export default Sidebar;
