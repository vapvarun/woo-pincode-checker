/**
 * Editor view: a live server render of the checker. No build step.
 */
( function ( blocks, element, blockEditor, serverSideRender ) {
	'use strict';

	var el = element.createElement;

	blocks.registerBlockType( 'wbpc/pincode-checker', {
		edit: function ( props ) {
			return el(
				'div',
				blockEditor.useBlockProps(),
				el( serverSideRender, { block: 'wbpc/pincode-checker', attributes: props.attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
}( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.serverSideRender ) );
