<?php

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

class UserAgreementHooks {

	/**
	 * Implements LoadExtensionSchemaUpdates hook.
	 *
	 * @param DatabaseUpdater $updater the database updater
	 */
	public static function loadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$dir = $GLOBALS['wgExtensionDirectory'] . DIRECTORY_SEPARATOR .
			'UserAgreement' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR;
		$updater->addExtensionTable( 'useragreement',
			$dir . DIRECTORY_SEPARATOR . 'AddTable.sql' );
	}

	/**
	 * Present the current logged in user with the user agreement message when
	 * appropriate.
	 *
	 * If the current logged in user has never accepted the user agreement, or
	 * the agreement has changed since the last time the user accepted it, they
	 * will be prompted with the agreement again. Otherwise, the agreement will
	 * not appear.
	 *
	 * Note: If the user agreement is empty or doesn't exist, then the user is
	 * allowed to proceed without being prompted to accept anything.
	 *
	 * @param OutputPage &$out The OutputPage object
	 * @param Skin &$skin Skin object that will be used to generate the page
	 */
	public static function addUserAgreement( &$out, &$skin ) {
		// get the current user's ID. It will be 0 if they are not logged in
		$userId = $out->getContext()->getUser()->getId();

		// get the relevant dates for comparison
		$uaModifiedDate = UserAgreement::getUALastModifiedDate();
		$userUAAcceptedDate = UserAgreement::getUserUAAcceptedDate( $userId );

		/* If the current user is logged in and the user agreement was changed
		 * after the user last accepted it then they need to do it again.
		 *
		 * Note: the date comparison MUST BE '<=' and NOT '<' to work properly.
		 * The strict equality condition exists to handle the case where the
		 * MediaWiki:Useragreement page has never been edited AND the user has
		 * never accepted any revision of the user agreement. In this case,
		 * both dates will be the default value (and thus, equivalent).
		 */
		if ( $userId > 0 && $userUAAcceptedDate <= $uaModifiedDate ) {
			// get the user agreement
			$useragreement = wfMessage( 'useragreement' )->text();
			// do not render the UA if it is empty or non-existant
			if ( strlen( $useragreement ) == 0 ) {
				return;
			}

			// blow away all the html so we use its parsing for the UA html
			$out->clearHTML();

			// use the output page to safely parse the user agreement
			$out->addWikiText( $useragreement );
			$useragreement = $out->getHTML();
			// pack user agreement into json for passing to js
			$useragreement = json_encode( [ 'ua' => $useragreement ] );

			// blow away all the html again to keep it fast
			$out->clearHTML();

			$uaParams = [
				'useragreement' => $useragreement,
			];
			$out->addJsConfigVars( 'UserAgreement', $uaParams );
			$out->addModules( 'ext.UserAgreement.render' );
		}
	}
}
