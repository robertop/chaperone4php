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

/**
 * ReadableFilePreCondition checks that a given file
 * can be read by the user that is running the current script.
 *
 */
class ReadableFilePreCondition extends PreCondition {

	/**
	 * the path to check. It must be a file or else the check
	 * will fail. It must be a full path.
	 *
	 * @var string
	 */
	private $fullPath;
	
	/**
	 *
	 * @param $fullPath string the path to check. It must be a file or else the 
	 * check will fail. It must be a full path.
	 */
	public function __construct($fullPath) {
		$this->fullPath = $fullPath;
	}

	/**
	 * @return bool FALSE when
	 *   - full path is a file and is not readable
	 *   - full path is not a file
	 */
	public function check() {
		return is_file($this->fullPath) && is_readable($this->fullPath);
	}
}