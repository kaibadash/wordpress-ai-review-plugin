import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ChangesNotice = ( { changes, onDismiss } ) => {
	return (
		<Notice
			status="success"
			isDismissible={ true }
			onDismiss={ onDismiss }
		>
			<strong>{ __( 'Changes', 'ai-review' ) }</strong>
			<p style={ { margin: '4px 0 0', whiteSpace: 'pre-wrap' } }>
				{ changes }
			</p>
		</Notice>
	);
};

export default ChangesNotice;
