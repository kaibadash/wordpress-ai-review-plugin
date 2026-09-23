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
					{ __( 'Processing...', 'kaiba-ai-review' ) }
				</>
			) : (
				__( 'Execute', 'kaiba-ai-review' )
			) }
		</Button>
	);
};

export default ExecuteButton;
