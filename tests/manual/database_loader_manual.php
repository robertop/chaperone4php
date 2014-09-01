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
 * This file is a manual test that exercises the database loader. It's purpose
 * is so that we can run loader against a huge number of records to
 * test database loader's memory usage.
 */

require_once(__DIR__ . '/../../vendor/autoload.php');

$opts = getopt('n:th', array('number:', 'truncate', 'help'));
if (isset($opts['h']) || isset($opts['help'])) {
	echo <<<HELP
This script is a manual test that exercises the database loader. It's purpose
is so that we can run loader against a huge number of records to
test database loader's memory usage.

This script requires a single database table called `users`. See the
database migration for info about its structure.

Usage: 

php tests/manual/database_loader_manual.php
php tests/manual/database_loader_manual.php -n 5000


Arguments:
-n | --number        The number of records to load into the database.
                     Defaults to 2000. Must be greater than zero.
-t | --truncate      Truncates the table before inserting. Defaults to false.
-h | --help          Prints this help message


HELP;

	exit(-2);
}
$recordsToCreate = 2000;
$doTruncate = FALSE;
if (isset($opts['n'])) {
	$recordsToCreate = $opts['n']; 
}
if (isset($opts['number'])) {
	$recordsToCreate = $opts['number']; 
}
if (isset($opts['t']) || isset($opts['truncate'])) {
	$doTruncate = TRUE;
}

if (!filter_var($recordsToCreate, FILTER_VALIDATE_INT)) {
	echo "Invalid value '{$recordsToCreate}' for --number argument.  " .
		"It must be a number greater than zero.\n";
	echo "See --help for details.\n\n";
	exit(-1);
}


// read db config from phinx config

$configFile = __DIR__ . '/../phinx.yml';
$config = \Phinx\Config\Config::fromYaml($configFile);
$dbCconfig = $config->getEnvironment('testing');

$dsn = $dbCconfig['adapter'] . ':' . 
	'host=' . $dbCconfig['host'] .
	';dbname=' . $dbCconfig['name'];

$pdo = new \PDO($dsn, $dbCconfig['user'], $dbCconfig['pass'],
	array(\PDO::MYSQL_ATTR_LOCAL_INFILE => '1'));

// truncate if desired
if ($doTruncate) {
	$pdo->exec('TRUNCATE TABLE `users`');
}

// generate data using faker, and keep adding it to the
// database loader. Even when the number of records is large
// memory usage is (should) remain constant, since records are
// added to a temp file.
$start = microtime(TRUE);

$loader = new \BackendProject\DatabaseLoader();
$loader->init('users', array(
	'username',
	'first_name',
	'last_name',
	'email',
	'password',
	'password_salt'
));

// generate some fake data
$faker = \Faker\Factory::create();

$user = new stdClass;
for ($i = 0; $i < $recordsToCreate; $i++) {
	$user->first_name = $faker->firstName;
	$user->last_name = $faker->lastName . '_' . $i;
	$user->username = $user->first_name . '.' .  $user->last_name;
	$user->email = $user->first_name . '.' .  $user->last_name . '@gmail.com';
	$user->password = '';
	$user->password_salt = '';
	
	if (!$loader->add($user)) {
		echo "failure in adding user $i\n";
	}
}

$numberAdded = $loader->commit($pdo);

$end = microtime(TRUE);

if ($numberAdded < 0) {
	echo "data load failed because no rows were added.\n";
}
else if ($numberAdded == 0) {
	echo "data load failed. \n";
}
else {
	printf("generated and loaded data successfully. %s rows were added. " .
		"Total time %s sec \n",
		number_format($numberAdded, 0),
		number_format($end - $start, 2)
	);
}
