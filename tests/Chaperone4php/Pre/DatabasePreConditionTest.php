<?php
/**
 *   Copyright 2014 Roberto Perpuly
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */
namespace Chaperone4php\Pre;

use Chaperone4php\BaseTest;

class DatabasePreConditionTest extends BaseTest {

	/**
	 * The object under test
	 *
	 * @var \Chaperone4php\Pre\DatabasePreCondition
	 */
	private $condition;
	
	protected function setUp() {
		$this->condition = new \Chaperone4php\Pre\DatabasePreCondition(
			$this->pdo()
		);
	}

	function testSuccess() {
		
		// this is the success case, that the pre condition returns
		// true when a connection can be established
		$this->assertTrue($this->condition->check());
	}
}
