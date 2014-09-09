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
 * InstancePreCondition checks that only a single instance of a script
 * is run on the system at any time.  This check is extemely useful when 
 * scripts run on a short timer unattended; instance checks prevent the 
 * same script from running if its already running, thereby preventing
 * multiple copies of the script from running. Multiple copies of the 
 * same script saturate the system, and at worst could result in corrupt
 * data.
 *
 * Instance checks are done by using PID files. The PID in the file is checked
 * again the PIDs in the system.  If the PID is valid AND it has the 
 * name of the script in the command line, the script is considered as 
 * being run and the instance check will fail.
 *
 * Note 1:
 *  A command line may have arguments; for now we ignore them.
 *	A script is considered to be the same instance even if it has different 
 *  arguments. For example, the instance check will fail for the 
 *  second command.
 *
 *     php test/code.php
 *
 *     vs.
 *
 *     php test/code.php --number=40
 *
 * Note 2:
 * A script is considered a different instance if the script part of 
 * the command line is different than the command line in the process list.
 * The instance check can be "fooled" if the same script is started
 * from 2 different directories; for example:
 *
 *     php test/code.php
 *
 *     vs.
 *
 *     cd test && php code.php
 * 
 */
class InstancePreCondition extends PreCondition {

	/**
	 * The PID file, the file will contain the PID of the running script.
	 *
	 * @var string full path to the PID file
	 */
	private $pidFile;
	
	/**
	 * @var string $scriptFile the full path to the script that is being 
	 *        run (the running script)
	 */
	private $scriptFile;
	
	/**
	 *
	 * @param string $pidFile full path to the PID file. Will be created if
	 *        it does not exist, however the directory that the file is in
	 *        must exist.
	 * @param string $scriptFile the full path to the script that is being 
	 *        run (the running script). This is usually the value 
	 *        of the global $argv.
	 */
	public function __construct($pidFile, $scriptFile) {
		$this->pidFile = $pidFile;
		$this->scriptFile = $scriptFile;
	}

	/**
	 * @return bool FALSE when
	 *   - PID file is not writable
	 *   - PID file contains a PID, that PID is valid, AND the PID
	 *     command is this PHP script 
	 */
	public function check() {
		$fp = fopen($this->pidFile, 'a+');
		if (!$fp) {
		
			// file was attempted to be created, by file creation failed
			assert('$fp !== FALSE');
			return FALSE;
		}
		
		// read the file to see if it has a PID 
		if (!flock($fp, LOCK_EX)) {
			return FALSE;
		}
		
		$line = fgets($fp, 1024);
		$line = trim($line);
		if (strlen($line) == 0) {
			
			// no PID in file, means that no other instance is running.
			// pre condition passes.
			// write the PID, unlock the file, and we are done.
			$ret = ftruncate($fp, 0)
				&& fwrite($fp, sprintf("%d\n", getmypid())) > 0
				&& fflush($fp);
			
			flock($fp, LOCK_UN);
			fclose($fp);
			return $ret;
		}
		
		// the file contains a PID, is the PID valid?
		fseek($fp, 0);
		$pid = trim(fgets($fp, 16));
		
		// by default assume instance not running if there is an empty file
		// or some other error
		$instanceRunning = FALSE;
		if ($pid) {
			$cmd = sprintf('ps -o cmd --no-heading --pid=%s', 
				escapeshellarg($pid));
			$output = array();
			$retCode = 0;
			exec($cmd, $output, $retCode);
			if (0 == $retCode) {
			
				// PID exists; but is it an instance of this script?
				// look at the command line and compare
				// command line may have arguments; for now we ignore them
				// a script cannot be run even if it has different arguments
				$instanceRunning = strpos($output[0], $this->scriptFile) !== FALSE;
			}
		}
		
		if (!$instanceRunning) {
			$ret = ftruncate($fp, 0)
				&& fwrite($fp, sprintf("%d\n", getmypid())) > 0
				&& fflush($fp);
			flock($fp, LOCK_UN);
			fclose($fp);
			return $ret;
		}
		return FALSE;
	}
}
