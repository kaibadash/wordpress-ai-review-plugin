import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import Sidebar from './components/Sidebar';

registerPlugin( 'kaiba-ai-review', {
	render: () => (
		<PluginSidebar
			name="ai-review-sidebar"
			title={ __( 'Kaiba AI Review', 'kaiba-ai-review' ) }
			icon="edit"
		>
			<Sidebar />
		</PluginSidebar>
	),
} );
