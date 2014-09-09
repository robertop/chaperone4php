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
 * DatabasePreCondition checks that a database connection can be established.
 *
 */
class DatabasePreCondition extends PreCondition {

	/**
	 * The connection to check.
	 *
	 * @var \PDO $pdo
	 */
	private $pdo;
		
	/**
	 *
	 * @param \PDO the connection to check
	 */
	public function __construct(\PDO $pdo) {
		$this->pdo = $pdo;
	}

	/**        
	 * @return bool FALSE when the PDO connection fails. Note that 
	 *         a PDO exception is never thrown.
	 */
	public function check() {
		try {
			$stmt = $this->pdo->prepare('SELECT 1');
			$stmt->execute(array());
			$stmt->closeCursor();
			return TRUE;
		} catch (\PDOException $exception) {
		
		}
		return false;
	}
}
