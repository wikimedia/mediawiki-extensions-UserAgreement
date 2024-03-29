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

use ApiBase;
use ApiMain;

class ApiUAAcceptAgreement extends ApiBase {

	/**
	 * @var UserAgreementStore
	 */
	private $userAgreementStore;

	/**
	 * @param ApiMain $main main module
	 * @param string $action name of this module
	 * @param UserAgreementStore $userAgreementStore
	 */
	public function __construct( ApiMain $main, string $action, UserAgreementStore $userAgreementStore ) {
		parent::__construct( $main, $action );
		$this->userAgreementStore = $userAgreementStore;
	}

	public function execute(): void {
		$this->userAgreementStore->updateUserAcceptedTimestamp( $this->getUser()->getId() );
	}

	/**
	 * @return string the token type this module requires in order to execute
	 */
	public function needsToken(): string {
		return 'csrf';
	}
}
