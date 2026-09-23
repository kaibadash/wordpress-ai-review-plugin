import { TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const PromptInput = ( { value, onChange, disabled } ) => {
	return (
		<TextareaControl
			label={ __( 'Instructions (optional)', 'kaiba-ai-review' ) }
			help={ __( 'Optionally enter instructions for the AI. If left empty, the system prompt will be used as-is.', 'kaiba-ai-review' ) }
			value={ value }
			onChange={ onChange }
			rows={ 4 }
			disabled={ disabled }
			placeholder={ __( 'e.g. Make the tone more casual', 'kaiba-ai-review' ) }
		/>
	);
};

export default PromptInput;
