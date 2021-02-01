/* global elementor */
import classnames from 'classnames';
import { Button, Modal, TextControl } from '@wordpress/components';
import { render, unmountComponentAtNode, useState } from '@wordpress/element';

import { exportTemplate } from './data/templates-cloud/index.js';

document.addEventListener( 'DOMContentLoaded', () => {
	const addExportMenuItem = ( groups, element ) => {
		const actions = {
			name: 'ti_tpc_export',
			title: window.tiTpc.exporter.exportLabel,
			callback: () => onClickModal( element ),
		};

		const isSaveExist = groups.find( ( i ) => 'save' === i.name );

		if ( isSaveExist ) {
			isSaveExist.actions.push( actions );
		} else {
			const Export = {
				name: 'ti_tpc_export',
				actions: [ actions ],
			};

			groups.splice( 3, 0, Export );
			groups.join();
		}

		return groups;
	};

	const ExportModal = ( { content } ) => {
		const [ title, setTitle ] = useState( '' );
		const [ isLoading, setLoading ] = useState( false );

		const onClose = () => {
			unmountComponentAtNode( document.getElementById( 'ti-tpc-modal' ) );
		};

		const onSave = async () => {
			setLoading( true );
			await exportTemplate( {
				title,
				type: 'section',
				content: [ content ],
			} );
			setLoading( false );
			onClose();
		};

		return (
			<Modal
				title={ window.tiTpc.exporter.modalLabel }
				onRequestClose={ onClose }
				overlayClassName={ classnames( {
					'is-dark':
						'dark' ===
						elementor.settings.editorPreferences.model.get(
							'ui_theme'
						),
				} ) }
			>
				<TextControl
					label={ window.tiTpc.exporter.textLabel }
					placeholder={ window.tiTpc.exporter.textPlaceholder }
					value={ title }
					onChange={ setTitle }
				/>

				<Button
					isPrimary
					isBusy={ isLoading }
					disabled={ isLoading }
					onClick={ onSave }
				>
					{ window.tiTpc.exporter.buttonLabel }
				</Button>
			</Modal>
		);
	};

	const onClickModal = ( element ) => {
		const content = element.model.toJSON( {
			remove: [ 'default', 'editSettings', 'defaultEditSettings' ],
		} );

		const el = document.createElement( 'div' );
		el.id = 'ti-tpc-modal';
		document.body.appendChild( el );

		render(
			<ExportModal content={ content } />,
			document.getElementById( 'ti-tpc-modal' )
		);
	};

	// We only hook our menu item to Sections as handling importing of separate Column and Widgets can be tricky.
	elementor.hooks.addFilter(
		'elements/section/contextMenuGroups',
		addExportMenuItem
	);
} );
