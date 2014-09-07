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
 * FreeMemorySpacePreCondition checks that there is a certain
 * amount of free memory in the system.  This is so
 * that if a script is memory-intensive, the script
 * can check whether there is enough space for it to do its
 * job.
 *
 * Free memory is checked by using the `free` system
 * command, therefore this pre-condition can only be used
 * on linux systems.
 */
class FreeMemoryPreCondition extends PreCondition {

	/**
	 * The number of free bytes of memory that the system must have so that the
	 * check passes.
	 *
	 * @var float in bytes
	 */
	private $freeThreshold;
	
	/**
	 *
	 * @param float The number of free bytes of memory that the system must have 
	 *        so that the check passes.
	 */
	public function __construct($freeThreshold) {
		$this->freeThreshold = $freeThreshold;
	}

	/**        
	 * @return bool FALSE when the system has less memory than the threshold.
	 */
	public function check() {
		
		// get the amount of free memory in the system
		// using the 'free' command available on linux only.
		// first, make sure the command actually exists on the system
		// if the command does not exist, we
		$retCode = 0;
		$output = array();
		exec('free -V', $output, $retCode);
		assert('$retCode == 0');
		if ($retCode == 0) {
			$retCode = 0;
			$output = array();
			
			exec('free -b', $output, $retCode);
			
			assert('$retCode == 0');
			if ($retCode == 0) {
				// the output is something like this
				//              total       used       free     shared    buffers     cached
				// Mem:    1043816448  404135936  639680512          0   19636224  283136000
				// -/+ buffers/cache:  101363712  942452736
				// Swap:    805302272          0  805302272 
				//
				if (count($output) >= 2) {
					$columns = preg_split('/\s+/', $output[1]);
					if (count($columns) >= 4) {
						$bytesFree = $columns[3];
						return $bytesFree >= $this->freeThreshold;
					}
				}
				
				// if we get here, it means some unexpected output
				assert('FALSE');
			}
		}
		return false;
	}
}
