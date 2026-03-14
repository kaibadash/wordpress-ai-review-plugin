import { TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const PromptInput = ( { value, onChange, disabled } ) => {
	return (
		<TextareaControl
			label={ __( '修正指示', 'ai-review' ) }
			help={ __( 'AIへの修正指示を入力してください', 'ai-review' ) }
			value={ value }
			onChange={ onChange }
			rows={ 4 }
			disabled={ disabled }
			placeholder={ __( '例: もっとカジュアルな文体にして', 'ai-review' ) }
		/>
	);
};

export default PromptInput;
