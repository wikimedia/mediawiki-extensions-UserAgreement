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

class UserAgreement {
	const USER_UA_ACCEPTED_DATE_COL = 'ua_user_accepted_timestamp';

	// this serves to effectively say "beginning of time"
	const DEFAULT_UA_ACCEPTED_DATE = '19700101000000';

	/**
	 * Return the last modified date of the MediaWiki:Useragreement page
	 * formatted as a unix timestamp.
	 *
	 * If the user agreement has never been modified then MediaWiki:Useragreement
	 * will not exist as a page in the database. In that case the last modified
	 * date will be considered to be "the beginning of time," which is to say:
	 * 1970-01-01T00:00:00. Thus, the returned unix timestamp will be '0'.
	 *
	 * @return string The last modified date of the MediaWiki:Useragreement
	 * page formatted as a unix timestamp or '0' if it has never been modified.
	 */
	public static function getUALastModifiedDate() {
		$dbr = wfGetDB( DB_REPLICA );
		$result = $dbr->select(
			'page',
			[ 'page_id' ],
			[
				'page_title' => 'Useragreement',
				'page_namespace' => NS_MEDIAWIKI
			],
			__METHOD__
		);

		if ( $result->numRows() > 0 ) {
			$uaArticleId = $result->fetchObject()->page_id;
		}

		if ( isset( $uaArticleId ) ) {
			// if we found the agreement page then get it's modified date
			$uaArticle = Article::newFromId( $uaArticleId );
			$uaPage = $uaArticle->getPage();
			$uaModifiedDate = $uaPage->getRevision()->getTimestamp();
		} else {
			// otherwise the agreement has never been modified so say it was
			// modified at "the beginning of time."
			$uaModifiedDate = self::DEFAULT_UA_ACCEPTED_DATE;
		}

		return wfTimestamp( TS_UNIX, $uaModifiedDate );
	}

	/**
	 * Return the date on which the given user accepted the user agreement
	 * formatted as a unix timestamp.
	 *
	 * The given user's user agreement accepted date is the value stored in
	 * the useragreement.user_ua_accepted_date database column formatted as
	 * a standard MediaWiki timestamp.
	 *
	 * If the user has never accepted the user agreement (any revision of it)
	 * then a value of '0' is returned instead. (This serves to effectively say
	 * they accepted at the beginning of time)
	 *
	 * @param int $userId MySQL database ID of the user for which to
	 *  retrieve the user agreement accepted date.
	 *
	 * @return string The date on which the given user last accepted any
	 *  revision of the user agreement stored on the MediaWiki:Useragreement
	 *  page formatted as a unix timestamp or 0 if it has never been accepted.
	 */
	public static function getUserUAAcceptedDate( $userId ) {
		// default user ua accepted date of "the beginning of time"
		$userUAAcceptedDate = self::DEFAULT_UA_ACCEPTED_DATE;

		// now try to actually get the real user ua accepted date.
		try {
			$dbr = wfGetDB( DB_REPLICA );
			$row = $dbr->selectRow(
				[
					'user',
					'useragreement'
				],
				[
					self::USER_UA_ACCEPTED_DATE_COL,
				],
				[
					'user_id' => $userId,
				],
				__METHOD__,
				[],
				[
					'useragreement' => [ 'JOIN', [ 'user_id=ua_user' ] ]
				]
			);

			if ( $row === false ) {
				return wfTimestamp( TS_UNIX, $userUAAcceptedDate );
			} else {
				$date = $row->ua_user_accepted_timestamp;
				$userUAAcceptedDate = $date != null ? $date : $userUAAcceptedDate;
			}
		} catch ( DBQueryError $e ) {
			wfLogWarning(
				'[UserAgreement][getUserUAAcceptedDate] A database error has occurred. ' .
				'Most likely the useragreement table is missing. ' .
				'The UserAgreement extension may not have been installed correctly. ' .
				'Please run update.php to try and correct this error.'
			);
		}

		// return the unix timestamp formatted accepted date, either the real one or the default
		return wfTimestamp( TS_UNIX, $userUAAcceptedDate );
	}
}
