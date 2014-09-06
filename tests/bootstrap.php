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

// setup the autoloader to find all of the classes we intend to test
// also, add the test directory to the autoload paths so that classes
// used in testing are autoloaded as well.
$strRootPath = __DIR__ . '/../';
$objLoader = require $strRootPath . '/vendor/autoload.php';
$objLoader->add("", array(
	$strRootPath . '/tests'
));

require_once(__DIR__ . '/Chaperone4php/BaseTest.php');