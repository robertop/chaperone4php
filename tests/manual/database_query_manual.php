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
 * This file is a manual test that exercises the database query class. It's 
 * purpose is so that we can run the database loader against a huge number of 
 * records to test the query's memory usage.
 */

require_once(__DIR__ . '/../../vendor/autoload.php');

$opts = getopt('n:th', array('number:', 'truncate', 'help'));
if (isset($opts['h']) || isset($opts['help'])) {
	echo <<<HELP
This script is a manual test that exercises the database query class. It's 
purpose is so that we can run the database loader against a huge number of 
records to test the query's memory usage.

This script requires a single database table called `users`. See the
database migration for info about its structure.

Usage: 

php tests/manual/database_loader_query.php
php tests/manual/database_loader_query.php -n 5000


Arguments:
-n | --number        The number of records to query from the database.
                     Defaults to 2000. Must be greater than zero.
-h | --help          Prints this help message


HELP;

	exit(-2);
}
$recordsToQuery = 2000;
if (isset($opts['n'])) {
	$recordsToQuery = $opts['n']; 
}
if (isset($opts['number'])) {
	$recordsToQuery = $opts['number']; 
}

if (!filter_var($recordsToQuery, FILTER_VALIDATE_INT)) {
	echo "Invalid value '{$recordsToQuery}' for --number argument.  " .
		"It must be a number greater than zero.\n";
	echo "See --help for details.\n\n";
	exit(-1);
}

// setup the autoloader to find all of the classes we intend to test
// also, add the test directory to the autoload paths so that classes
// used in testing are autoloaded as well.
$strRootPath = __DIR__ . '/../../';
$objLoader = require $strRootPath . '/vendor/autoload.php';
$objLoader->add("", array(
	$strRootPath . '/tests'
));

// read db config from phinx config
$configFile = __DIR__ . '/../phinx.yml';
$config = \Phinx\Config\Config::fromYaml($configFile);
$dbCconfig = $config->getEnvironment('testing');

$dsn = $dbCconfig['adapter'] . ':' . 
	'host=' . $dbCconfig['host'] .
	';dbname=' . $dbCconfig['name'];

$pdo = new \PDO($dsn, $dbCconfig['user'], $dbCconfig['pass']);
$pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, FALSE);

$start = microtime(TRUE);

// perform some arbritrary calculation on the users.
// we check which users' last names start the letter 'p'
$query = new \Chaperone4php\UsersSelectDatabaseQuery();
$sql = <<<SQL
	SELECT username, email, first_name, last_name, created, updated
	FROM users
	LIMIT {$recordsToQuery}
SQL;

$query->initSql($sql, array());
$matchedUserCount = 0;
$testedUserCount = 0;
if ($query->prepare($pdo)) {
	while ($query->fetch()) {
		$testedUserCount++;
		if (strlen($query->last_name) > 0 && 
			strtolower($query->last_name[0]) == 'p') {
			$matchedUserCount++;
		}
	}
	
	$end = microtime(TRUE);
	
	printf("tested %s users.\n", number_format($testedUserCount, '0'));
	printf("%s users had a last name starting with 'p'.\n", 
		number_format($matchedUserCount, '0'));
	printf("Total time: %s sec\n", number_format($end - $start, 2));
}
else {
	echo "could not prepare query. Invalid query?";
}

echo "\n\n";