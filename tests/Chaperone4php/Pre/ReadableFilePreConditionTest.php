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

class ReadableFilePreConditionTest extends BaseTest {

	/**
	 * The object under test
	 * \Chaperone4php\Pre\ReadableFilePrecondition
	 */
	private $condition;
	
	function testDir() {
		
		// test that the check fails when we give
		// a directory
		$this->condition = new 
			\Chaperone4php\Pre\ReadableFilePreCondition(__DIR__);
		$this->assertFalse($this->condition->check());
	}
	
	function testReadonlyFile() {
		
		// test that the check succeeds when we give
		// when a read-only file is given
		// check the hosts file
		// for linux. use the /etc/hosts
		// for windows, use C:\Windows\System32\drivers\etc\hosts
		$hostsFile = '';
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$hostsFile = 'C:\Windows\System32\drivers\etc\hosts';
		}
		else {
			$hostsFile = '/etc/hosts';
		}
		$this->condition = new 
			\Chaperone4php\Pre\ReadableFilePreCondition($hostsFile);
		$this->assertTrue($this->condition->check());
	}
	
	
	function testInvalidFile() {
		
		// test that the check FAILS when we give
		// when a non-existant file
		$file = sys_get_temp_dir() . '/adadadadadqwrrr';
		$this->condition = new 
			\Chaperone4php\Pre\ReadableFilePreCondition($file);
		$this->assertFalse($this->condition->check());
	}	
}