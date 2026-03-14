import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ExecuteButton = ( { onClick, disabled, isLoading } ) => {
	return (
		<Button
			variant="primary"
			onClick={ onClick }
			disabled={ disabled }
			isBusy={ isLoading }
		>
			{ isLoading ? (
				<>
					<Spinner />
					{ __( '処理中...', 'ai-review' ) }
				</>
			) : (
				__( '実行', 'ai-review' )
			) }
		</Button>
	);
};

export default ExecuteButton;
