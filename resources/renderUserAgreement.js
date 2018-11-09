/*
 * Copyright (c) 2018 The MITRE Corporation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

( function ( mw, $ ) {
	/**
	 * Gobal function to display a user agreement.
	 */
	window.renderUserAgreement = function ( useragreement ) {
		( {
			/**
			 * Render the user agreement on the page.
			 */
			render: function ( useragreement ) {
				var submitButton = new OO.ui.ButtonInputWidget( {
						id: 'uaAccept',
						label: mw.msg( 'useragreement-dialog-message' ),
						icon: 'check'
					} ),
				useragreementHtml =
					'<div id="uaModal">' +
					JSON.parse( useragreement ).ua +
					'<div id="uaAcceptInput"> \
					</div> \
					</div>';

				$( function () {
					$( 'body' ).html( useragreementHtml );
					$( '#uaAcceptInput' ).append( submitButton.$element );
					submitButton.$input.click( function () {
						var api = new mw.Api();
						api.post( {
							action: 'uaAcceptAgreement',
							token: mw.user.tokens.get( 'editToken' ),
						} ).done( function ( data ) {
							location.reload( true );
						} ).fail( function ( data ) {
							console.error( '[UserAgreement] Failed to accept user agreement for the current user.' );
						} );
					} );
				} );
			},
		} )
		.render( useragreement );
	};
}( mediaWiki, jQuery ) );

$( function () {
	if ( mw.config.exists( 'UserAgreement' ) ) {
		var uaData = mw.config.get( 'UserAgreement' );
		renderUserAgreement( uaData.useragreement );
	}
} );