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

use MediaWiki\Page\WikiPageFactory;
use Wikimedia\Rdbms\ILoadBalancer;

class UserAgreementStore {
	// this serves to effectively say "beginning of time"
	private const DEFAULT_UA_ACCEPTED_TIMESTAMP = '19700101000000';

	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var WikiPageFactory
	 */
	private $wikiPageFactory;

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param WikiPageFactory $wikiPageFactory
	 */
	public function __construct(
		ILoadBalancer $loadBalancer,
		WikiPageFactory $wikiPageFactory
	) {
		$this->loadBalancer = $loadBalancer;
		$this->wikiPageFactory = $wikiPageFactory;
	}

	/**
	 * Return the last modified timestamp of the MediaWiki:Useragreement page
	 * formatted as a unix timestamp.
	 *
	 * If the user agreement has never been modified then MediaWiki:Useragreement
	 * will not exist as a page in the database. In that case the last modified
	 * timestamp will be considered to be "the beginning of time," which is to say:
	 * 1970-01-01T00:00:00. Thus, the returned unix timestamp will be '0'.
	 *
	 * @return string The last modified timestamp of the MediaWiki:Useragreement
	 * page formatted as a unix timestamp or '0' if it has never been modified.
	 */
	public function getAgreementLastModificationTimestamp(): string {
		$result = $this->loadBalancer->getConnection( DB_REPLICA )
			->newSelectQueryBuilder()
			->select( [
				'page_id'
			] )
			->from( 'page' )
			->where( [
				'page_title' => 'Useragreement',
				'page_namespace' => NS_MEDIAWIKI
			] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( $result === false ) {
			// the agreement has never been modified so say it was
			// modified at "the beginning of time."
			$timestamp = self::DEFAULT_UA_ACCEPTED_TIMESTAMP;
		} else {
			// if we found the agreement page then get it's modified timestamp
			$wikipage = $this->wikiPageFactory->newFromID( $result->page_id );
			$timestamp = $wikipage->getRevisionRecord()->getTimestamp();
		}

		return wfTimestamp( TS_UNIX, $timestamp );
	}

	/**
	 * Return the timestamp of when the given user accepted the user agreement
	 * formatted as a unix timestamp.
	 *
	 * The given user's user agreement accepted timestamp is the value stored in
	 * the useragreement.user_ua_accepted_timestamp database column formatted as
	 * a standard MediaWiki timestamp.
	 *
	 * If the user has never accepted the user agreement (any revision of it)
	 * then a value of '0' is returned instead. (This serves to effectively say
	 * they accepted at the beginning of time)
	 *
	 * @param int $userId MySQL database ID of the user for which to
	 *  retrieve the user agreement accepted timestamp.
	 *
	 * @return string The timestamp at which the given user last accepted any
	 *  revision of the user agreement stored on the MediaWiki:Useragreement
	 *  page formatted as a unix timestamp or 0 if it has never been accepted.
	 */
	public function getUserAcceptedTimestamp( int $userId ): string {
		$result = $this->loadBalancer->getConnection( DB_REPLICA )
			->newSelectQueryBuilder()
			->select( [
				'ua_user_accepted_timestamp'
			] )
			->from( 'useragreement' )
			->where( [
				'ua_user' => $userId
			] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( $result === false ) {
			// default user ua accepted timestamp of "the beginning of time"
			$acceptedTimestamp = self::DEFAULT_UA_ACCEPTED_TIMESTAMP;
		} else {
			$acceptedTimestamp = $result->ua_user_accepted_timestamp;
		}

		// return the unix timestamp formatted accepted timestamp, either the real one or the default
		return wfTimestamp( TS_UNIX, $acceptedTimestamp );
	}

	/**
	 * @param int $userId
	 * @return void
	 */
	public function updateUserAcceptedTimestamp( int $userId ): void {
		$timestamp = wfTimestamp( TS_MW );
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		$dbw->upsert(
			'useragreement',
			[
				'ua_user' => $userId,
				'ua_user_accepted_timestamp' => $timestamp
			],
			[
				'ua_user'
			],
			[
				'ua_user_accepted_timestamp' => $timestamp
			],
			__METHOD__
		);
	}
}
