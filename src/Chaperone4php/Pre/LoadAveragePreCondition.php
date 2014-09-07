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
 * LoadAveragePreCondition checks that the system is not extremely busy.  This 
 * check is useful to prevent a machine from being overloaded. If a system
 * is overloaded, we don't want to add more processes, as it becomes even more
 * overloaded and can prevent programs from finishing. It can even
 * prevent users from login into the system to perform maintenance.
 *
 * The load average is checked with the sys_getloadavg function; the function
 * is not available on windows systems. See the PHP manual for more info
 * http://php.net/manual/en/function.sys-getloadavg.php
 */
class LoadAveragePreCondition extends PreCondition {

	/**
	 * The maximum load average the system must have so that the
	 * check passes.
	 *
	 * @var float in bytes
	 */
	private $loadThreshold;
	
	/**
	 * The load average sample to compare against (last 1, 5 or 15 minutes).
	 *
	 * @var int
	 */
	private $sampleToCheck;
	
	/**
	 * The sample of the last minute
	 */
	const SAMPLE_01 = 0;
	
	/**
	 * The sample of the last 5 minutes
	 */
	const SAMPLE_10 = 1;
	
	/**
	 * The sample of the last 15 minutes
	 */
	const SAMPLE_15 = 2;
	
	
	/**
	 *
	 * @param float $loadThreshold The maximum load average that the system must 
	 *        have so that the check passes.
	 * @param int $sample the average to check, one of SAMPLE_* constants
	 */
	public function __construct($loadThreshold, $sample) {
		$this->loadThreshold = $loadThreshold;
		$this->sampleToCheck = $sample;
		
		assert('self::SAMPLE_01 == $sample || ' .
			'self::SAMPLE_05 == $sample || ' . 
			'self::SAMPLE_15 == $sample'
		);
	}

	/**        
	 * @return bool FALSE when the system load average is higher than the 
	 *         threshold.
	 */
	public function check() {
		$loads = sys_getloadavg();
		if (isset($loads[$this->sampleToCheck])) {
			return $loads[$this->sampleToCheck] <= $this->loadThreshold;
		}		
		return false;
	}
}
