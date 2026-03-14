import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { parse } from '@wordpress/blocks';
import { PanelBody, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import WarningNotice from './WarningNotice';
import PromptInput from './PromptInput';
import ExecuteButton from './ExecuteButton';

const Sidebar = () => {
	const [ prompt, setPrompt ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ isConfigured, setIsConfigured ] = useState( null );

	const postContent = useSelect( ( select ) => {
		return select( 'core/editor' ).getEditedPostContent();
	}, [] );

	const { resetBlocks } = useDispatch( 'core/block-editor' );

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

		try {
			const response = await apiFetch( {
				path: 'ai-review/v1/review',
				method: 'POST',
				data: {
					post_content: postContent,
					prompt,
				},
			} );

			if ( response.success && response.content ) {
				const blocks = parse( response.content );
				resetBlocks( blocks );
				setPrompt( '' );
			}
		} catch ( err ) {
			setError(
				err.message ||
					__( 'エラーが発生しました。', 'ai-review' )
			);
		} finally {
			setIsLoading( false );
		}
	};

	if ( isConfigured === null ) {
		return (
			<PanelBody>
				<p>{ __( '読み込み中...', 'ai-review' ) }</p>
			</PanelBody>
		);
	}

	if ( ! isConfigured ) {
		return (
			<PanelBody>
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'設定画面からLLMの設定を完了してください。管理画面の「設定」→「AI Review」から設定できます。',
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
					disabled={ ! prompt.trim() || isLoading }
					isLoading={ isLoading }
				/>
			</div>
		</PanelBody>
	);
};

export default Sidebar;
