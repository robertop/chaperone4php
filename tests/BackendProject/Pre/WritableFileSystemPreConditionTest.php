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
namespace BackendProject\Pre;

use BackendProject\BaseTest;

class WritableFileSystemPreConditionTest extends BaseTest {

	/**
	 * The object under test
	 * \BackendProject\Pre\WritableFileSystemPrecondition
	 */
	private $condition;
	
	function testWritableFile() {
		
		// test that the check passes when we give
		// a file that is writable
		// we test our selves, it must be writable, amirite?
		$this->condition = new 
			\BackendProject\Pre\WritableFileSystemPreCondition(__FILE__);
		$this->assertTrue($this->condition->check());
	}
	
	function testWritableDir() {
		
		// test that the check passes when we give
		// a directory that is writable
		// we test our directory, it must be writable, amirite?
		$this->condition = new 
			\BackendProject\Pre\WritableFileSystemPreCondition(__DIR__);
		$this->assertTrue($this->condition->check());
	}
	
	function testReadonlyFile() {
		
		// test that the check FAILS when we give
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
			\BackendProject\Pre\WritableFileSystemPreCondition($hostsFile);
		$this->assertFalse($this->condition->check());
	}
	
	
	function testLinkFile() {
		
		// test that the check FAILS when we give
		// when a read-only file is given
		// check the hosts file
		// for linux. use the /etc/hosts
		// for windows, use C:\Windows\System32\drivers\etc\hosts
		$hostsFile = '';
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$this->markTestSkipped("cannot test symbolic links on MSW");
		}
		
		// create a symlink to a read-only file
		$linkFile = tempnam(sys_get_temp_dir(), 'temp_link');
		
		// tempnam() creates a file, but we just wanted the name
		// because ln command will create the file
		unlink($linkFile);
		
		$hostsFile = '/etc/hosts';
		exec(sprintf(
			'ln -s %s %s', 
			escapeshellarg($hostsFile), escapeshellarg($linkFile)
		));
		
		$this->assertFileExists($linkFile);
		
		// now check that the link is marked as "not writable"
		$this->condition = new 
			\BackendProject\Pre\WritableFileSystemPreCondition($hostsFile);
		$this->assertFalse($this->condition->check());
		
		// remove the one created by ln -s
		unlink($linkFile);
	}
	
	function testReadonlyDir() {
		
		// test that the check FAILS when we give
		// when a read-only directory is given
		// check the hosts file
		// for linux. use the /etc/
		// for windows, use C:\Windows\System32
		$hostsFile = '';
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$hostsFile = 'C:\Windows\System32';
		}
		else {
			$hostsFile = '/etc';
		}
		$this->condition = new 
			\BackendProject\Pre\WritableFileSystemPreCondition($hostsFile);
		$this->assertFalse($this->condition->check());
	}
	
	function testInvalidFile() {
		
		// test that the check FAILS when we give
		// when a non-existant file
		$file = sys_get_temp_dir() . '/adadadadadqwrrr';
		$this->condition = new 
			\BackendProject\Pre\WritableFileSystemPreCondition($file);
		$this->assertFalse($this->condition->check());
	}	
}