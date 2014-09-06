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
namespace Chaperone4php;

/**
 * base class for all Chaperone4php tests.
 */
abstract class BaseTest extends \PHPUnit_Framework_TestCase {

	/**
	 * The connection to use
	 */
	private $pdo;
	
	protected function pdo() {
		if ($this->pdo) {
			return $this->pdo;
		}
		
		$configFile = __DIR__ . '/../phinx.yml';
		$config = \Phinx\Config\Config::fromYaml($configFile);
		$dbCconfig = $config->getEnvironment('testing');
		
		$dsn = $dbCconfig['adapter'] . ':' . 
			'host=' . $dbCconfig['host'] .
			';dbname=' . $dbCconfig['name'];
		
		$this->pdo = new \PDO($dsn, $dbCconfig['user'], $dbCconfig['pass'],
			array(\PDO::MYSQL_ATTR_LOCAL_INFILE => '1'));
		return $this->pdo;
	}
	
	protected function truncate($table) {
		$pdo = $this->pdo();
		$pdo->exec('TRUNCATE TABLE '. $table);
	}
}
