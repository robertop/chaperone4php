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
 * FreeDiskSpacePreCondition checks that a given volume
 * has a certain number of free bytes left.  This is so
 * that if a script creates sizable files, the script
 * can check whether there is enough space for it to do its
 * job.
 */
class ReadableFilePreCondition extends PreCondition {

	/**
	 * the path to check. It must be a directory.
	 *
	 * @var string
	 */
	private $directory;
	
	/**
	 * The number of free bytes that the volume must have so that the
	 * check passes.
	 *
	 * @var int in bytes
	 */
	private $freeThreshold;
	
	/**
	 *
	 * @param $directory string the path to check. It must be a directory
	 *        where a volume is mounted onto (for example, '/')
	 * @param float The number of free bytes that the volume must have so that 
	 *        the check passes.
	 */
	public function __construct($directory = '/', $freeThreshold) {
		$this->directory = $directory;
		$this->freeThreshold = $freeThreshold;
	}

	/**
	 * @return bool FALSE when
	 *   - full path is a file and is not readable
	 *   - full path is not a file
	 */
	public function check() {
		return is_file($this->fullPath) 
			&& is_readable($this->fullPath)
			&& disk_free_space($this->directory) >= $this->freeThreshold);
	}
}