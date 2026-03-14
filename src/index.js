import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import Sidebar from './components/Sidebar';

registerPlugin( 'ai-review-plugin', {
	render: () => (
		<PluginSidebar
			name="ai-review-sidebar"
			title={ __( 'AI Review', 'ai-review' ) }
			icon="edit"
		>
			<Sidebar />
		</PluginSidebar>
	),
} );
