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

/**
 * This file is a manual test that exercises precondition classes. It's 
 * purpose is to manually construct scenarios that cannot be
 * recreated with unit tests, like low memory thresholds or low disk space
 * thresholds.
 */

require_once(__DIR__ . '/../../vendor/autoload.php');

$opts = getopt('d:m:h', array('disk:', 'memory:', 'help'));
if (isset($opts['h']) || isset($opts['help'])) {
	echo <<<HELP
This file is a manual test that exercises precondition classes. It's 
purpose is to manually construct scenarios that cannot be
recreated with unit tests, like low memory thresholds or low disk space
thresholds.

Usage: 

php tests/manual/pre_conditions.php
php tests/manual/pre_conditions.php -d 5000


Arguments:
-d | --disk          Turn on disk threshold, use the given number as the 
                     threshold. Threshold is in bytes.
-m | --memory        Turn on memory threshold, use the given number as the 
                     threshold. Threshold is in bytes.					
-h | --help          Prints this help message


HELP;

	exit(-2);
}

$diskThreshold = 0;
$hasDiskThreshold = FALSE;
$memoryThreshold = 0;
$hasMemoryThreshold =  FALSE;

if (isset($opts['d'])) {
	$diskThreshold = $opts['d']; 
	$hasDiskThreshold = TRUE;
}
if (isset($opts['disk'])) {
	$diskThreshold = $opts['disk']; 
	$hasDiskThreshold = TRUE;
}
if (isset($opts['m'])) {
	$memoryThreshold = $opts['m'];
	$hasMemoryThreshold = TRUE;
}
if (isset($opts['memory'])) {
	$memoryThreshold = $opts['memory'];
	$hasMemoryThreshold = TRUE;
}

if ($hasDiskThreshold && !filter_var($diskThreshold, FILTER_VALIDATE_FLOAT)) {
	echo "Invalid value '{$diskThreshold}' for --disk argument.  " .
		"It must be a number greater than zero.\n";
	echo "See --help for details.\n\n";
	exit(-1);
}

if ($hasMemoryThreshold && !filter_var($memoryThreshold, FILTER_VALIDATE_FLOAT)) {
	echo "Invalid value '{$memoryThreshold}' for --memory argument.  " .
		"It must be a number greater than zero.\n";
	echo "See --help for details.\n\n";
	exit(-1);
}

if (!$hasDiskThreshold && !$hasMemoryThreshold) {
	echo "You must give at least 1 pre-condition\n";
	exit(-1);
}

// setup pre-conditions according to command line arguments that were
// given
$allPreconditions = array();
if ($hasDiskThreshold) {
	$allPreconditions []= new \Chaperone4php\Pre\FreeDiskSpacePreCondition(
		$diskThreshold, '/'
	);
}
if ($hasMemoryThreshold) {
	$allPreconditions []= new \Chaperone4php\Pre\FreeMemoryPreCondition(
		$memoryThreshold
	);
}

// now test each pre-condition
$passedAll = TRUE;
foreach ($allPreconditions as $preCondition) {
	if (!$preCondition->check()) {
		$passedAll = FALSE;
		echo "Failed precondition " . get_class($preCondition) . "\n";
	}
}

if ($passedAll) {
	echo "All pre-conditions have passed.\n";
}

