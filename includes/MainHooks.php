<?php
/*
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

namespace MediaWiki\Extension\UserAgreement;

use Config;
use DateTimeImmutable;
use MediaWiki\Hook\BeforePageDisplayHook;
use OutputPage;
use Skin;

class MainHooks implements BeforePageDisplayHook {

	/**
	 * @var int
	 */
	private $daysToReaccept;

	/**
	 * @var UserAgreementStore
	 */
	private $userAgreementStore;

	/**
	 * @param Config $config
	 * @param UserAgreementStore $userAgreementStore
	 */
	public function __construct( Config $config, UserAgreementStore $userAgreementStore ) {
		$this->daysToReaccept = $config->get( 'UserAgreement_DaysToReaccept' );
		$this->userAgreementStore = $userAgreementStore;
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
	 * @param OutputPage $out The OutputPage object
	 * @param Skin $skin Skin object that will be used to generate the page
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		// get the current user's ID. It will be 0 if they are not logged in
		$userId = $out->getContext()->getUser()->getId();

		// nothing to do if no user is logged in
		if ( $userId === 0 ) {
			return;
		}

		// get the user agreement
		$useragreement = wfMessage( 'useragreement' )->text();

		// do not render the user agreement if it is empty or non-existant
		if ( strlen( $useragreement ) == 0 ) {
			return;
		}

		// get the relevant dates for comparison
		$agreementLastModificationTimestamp =
			$this->userAgreementStore->getAgreementLastModificationTimestamp();
		$userAcceptedTimestamp =
			$this->userAgreementStore->getUserAcceptedTimestamp( $userId );

		/* Check if user agreement  was changed after the user last accepted it or
		 * if the number of days to reaccept has been exceeded.
		 *
		 * Note: the timestamp comparison MUST BE '<=' and NOT '<' to work properly.
		 * The strict equality condition exists to handle the case where the
		 * MediaWiki:Useragreement page has never been edited AND the user has
		 * never accepted any revision of the user agreement. In this case, both
		 * timestamps will be the default value (and thus, equivalent).
		 */
		if ( $userAcceptedTimestamp > $agreementLastModificationTimestamp ) {
			// Check the cutoff for reaccepting
			if ( is_int( $this->daysToReaccept ) && $this->daysToReaccept > 0 ) {
				// get today's date
				$date = new DateTimeImmutable();
				// subtract the number of days to reaccept
				$date = $date->modify( "-" . $this->daysToReaccept . " days" );
				$timestampCutoff = $date->getTimestamp();
				if ( $userAcceptedTimestamp > $timestampCutoff ) {
					return;
				}
			} else {
				return;
			}
		}

		/* If the current user is logged in and the user agreement is not empty
		 * and was changed after the user last accepted it or the number of days
		 * to reaccept the user agreement has been exceeded, then they need to do
		 * it again.
		 */

		// blow away all the html so we use its parsing for the user agreement html
		$out->clearHTML();

		// use the output page to safely parse the user agreement
		$out->addWikiTextAsInterface( $useragreement );
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
