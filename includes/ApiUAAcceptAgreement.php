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

class ApiUAAcceptAgreement extends ApiBase {

	/**
	 * @param ApiMain $main main module
	 * @param string $action name of this module
	 */
	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	/**
	 * Evaluates the parameters, performs the requested query, and sets up
	 * the result. Implementations must not produce any output on their own
	 * and are not expected to handle any errors. The result data should be
	 * stored in the ApiResult object available through getResult().
	 */
	public function execute() {
		$userId = $this->getUser()->getId();
		$dbw = wfGetDB( DB_PRIMARY );
		$dbw->upsert(
			'useragreement',
			[
				'ua_user' => $userId,
				UserAgreement::USER_UA_ACCEPTED_DATE_COL => wfTimestamp( TS_MW )
			],
			[
				'ua_user'
			],
			[
				UserAgreement::USER_UA_ACCEPTED_DATE_COL => wfTimestamp( TS_MW )
			],
			__METHOD__
		);
	}

	/**
	 * @return string the token type this module requires in order to execute
	 */
	public function needsToken() {
		return 'csrf';
	}
}
