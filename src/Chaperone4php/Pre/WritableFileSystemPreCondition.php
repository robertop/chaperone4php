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

/**
 * WritableFileSystemPreCondition checks that a given directory or file
 * can be written to by the user that is running the current script.
 *
 */
class WritableFileSystemPreCondition extends PreCondition {

	/**
	 * the path to check. It may be either a file or a directory, but should
	 * @var string
	 *
	 * be a full path.
	 */
	private $fullPath;
	
	/**
	 *
	 * @param $fullPath string the path to check. It may be either a file or a 
	 *        directory, but should be a full path. Note that it is safe
	 *        to pass a symbolic link, the check will properly determine if
	 *        the file that is linked to is writable.
	 */
	public function __construct($fullPath) {
		$this->fullPath = $fullPath;
	}

	/**
	 * @return bool FALSE when
	 *   - full path is a file and is not writable
	 *   - full path is a directory and is not writable
	 *   - full path is not a file or a directory
	 */
	public function check() {
		$sucess = TRUE;
		if (is_file($this->fullPath) && !is_writable($this->fullPath)) {
			$sucess = FALSE;
		}
		else if (is_dir($this->fullPath) && !is_writable($this->fullPath)) {
			$sucess = FALSE;
		}
		else if (!is_file($this->fullPath) && !is_dir($this->fullPath)) {
			$sucess = FALSE;
		}
		return $sucess;
	}
}