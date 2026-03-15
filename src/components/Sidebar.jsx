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

const Sidebar = () => {
	const [ prompt, setPrompt ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( '' );
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
		setError( '' );
		setChanges( '' );

		try {
			const response = await apiFetch( {
				path: 'ai-review/v1/review',
				method: 'POST',
				data: {
					post_title: postTitle,
					post_content: postContent,
					prompt,
				},
			} );

			if ( response.success ) {
				if ( response.content ) {
					const blocks = parse( response.content );
					resetBlocks( blocks );
				}
				if ( response.title ) {
					editPost( { title: response.title } );
				}
				if ( response.changes ) {
					setChanges( response.changes );
				}
				setPrompt( '' );
			}
		} catch ( err ) {
			setError(
				err.message ||
					__( 'An error occurred.', 'ai-review' )
			);
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
					onDismiss={ () => setError( '' ) }
				>
					{ error }
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
