import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const WarningNotice = () => {
	return (
		<Notice status="warning" isDismissible={ false }>
			{ __( 'AI revisions will overwrite the current post content.', 'kaiba-ai-review' ) }
		</Notice>
	);
};

export default WarningNotice;
