import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const WarningNotice = () => {
	return (
		<Notice status="warning" isDismissible={ false }>
			{ __( '⚠ AIによる修正は現在の記事内容を上書きします。', 'ai-review' ) }
		</Notice>
	);
};

export default WarningNotice;
