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
 * A BaseDatabaseQuery is a helpful to query rows from a database in
 * memory-efficient manner by reducing the amount of PHP variable 
 * allocations. BaseDatabaseQuery instances will define the SQL query to
 * be run as well as the variables that it needs to bind; the Base
 * class will prepare the query, bind the variables, and iterate through
 * the result set.
 *
 * Usage:
 * - Create a class that inherits from BaseDatabaseQuery
 * - The sub-class should have 1 public property for each column in
 *   th result set.
 * - Instantiate an object of the sub-class type, 
 * - Call initSql() to define the query being executed and the query
 *   parameters
 * - Call prepare() to execute the query
 * - Call fetch() repeatedly to iterate through the result set
 * - Access result set values via the public properties of the sub-class object
 *
 * See docs/04-bulk-operations.md for more info.
 * See \Chaperone4php\ResultSetBinder to learn more about the binding mechanism.
 */
class BaseDatabaseQuery {

	/**
	 * The statement that we are iterating over. Will be NULL until the
	 * query is prepared.
	 *
	 * @var \PDOStatement
	 */
	private $stmt;
	
	/**
	 * The SQL statement being executed. This can have placeholders.
	 * 
	 * @var string
	 */
	private $sql;

	/**
	 * The query parameters. This is an associative array; it can be
	 * either named parameters or index-based parameters.
	 * 
	 * @var array
	 */
	private $queryParams;
	
	/**
	 * Used to bind result set columns to properties on this object
	 *
	 * @var \Chaperone4php\ResultSetBinder
	 */
	private $binder;
	
	/**
	 * Set the query to be execute and the parameters to bind to the 
	 * statement. The rules for binding parameters are exactly the same as
	 * those for PDO::prepare() and PDOStatement::execute(), see the PHP docs at 
	 * http://php.net/manual/en/pdo.prepare.php and
	 * http://php.net/manual/en/pdostatement.execute.php
	 *
	 * @param string $sql the statement to execute. This MUST be a SELECT
	 *        statement. No other SQL queries are supported (INSERT, DELETE)
	 * @params array $queryParams associative array of parameters to bind to
	 *         the query.
	 */
	public function initSql($sql, $queryParams) {
		$this->sql = $sql;
		$this->queryParams = $queryParams;
		$this->binder = new \Chaperone4php\ResultSetBinder();
	}

	/**
	 * Prepares the query to be executed, executes the query, and binds
	 * columns of the result set to PHP variables. By default, columns
	 * of the result set are bound to public members of this instance. 
	 * See \Chaperone4php\ResultSetBinder for more info.
	 *
	 * The query can executed as an unbuffered query, but this will require 
	 * that the MYSQL_ATTR_USE_BUFFERED_QUERY on the given PDO connection be
	 * set to FALSE.  There are a couple of points to be aware of when
	 * using unbuffered queries.
	 * 
	 * 1. The upside is that large result sets will maintain constant memory 
	 *    usage, even very large result sets (1000+)
	 * 2. The downside is that no other queries can be executed on the
	 *    given PDO connection until all of the result rows has been
	 *    fetched.
	 *
	 * Given these parameters, it is up to you to choose to turn off
	 * buffered queries. This library does not impose that choice.
	 *
	 * See http://php.net/manual/en/mysqlinfo.concepts.buffering.php 
	 * for more info on unbuffered queries
	 *
	 * @param \PDO $pdo the connection to execute the query on.  The last
	 *         query given to the initSql() method will be executed. The
	 *         connection may have the attribute MYSQL_ATTR_USE_BUFFERED_QUERY
	 *         set to FALSE in order for the queries to run unbuffered and
	 *         be more memory-effificent.
	 * @return bool TRUE if the query was valid AND was executed.
	 */
	public function prepare(\PDO $pdo) {
		assert('strlen($this->sql) > 0');
		$sql = $this->sql;
		$success = FALSE;
		$this->stmt = NULL;
		if ($sql) {
			$this->stmt = $pdo->prepare($sql);
			
			// according to PHP docs, we must call execute() before we
			// bind the result set 
			$queryParams = $this->queryParams;
			
			// TODO: how to bubble up statement errors
			$success = $this->stmt->execute($queryParams);
			if ($success) {
				$this->binder->bind($sql, $this->stmt, $this);
			}
		}
		return $success;
	}
	
	/**
	 * iterate to the next record of the result set. After a call to this
	 * method, the PHP variables bound to the result set columns
	 * will have new values.
	 * After the last result set row is fetched, the statement cursor will
	 * be closed.
	 *
	 * @return bool TRUE if a new row was fetched (PHP variables were updated).
	 */
	public function fetch() {
		assert('$this->stmt != NULL');
		if (!$this->stmt) {
			return FALSE;
		}
		$hasMore = $this->stmt->fetch(\PDO::FETCH_BOUND);
		if (!$hasMore) {
			
			// time to close the result set
			$this->closeCursor();
		}
		return $hasMore;
	}
	
	/**
	 * close the result set cursor manually. This often is not necessary, it 
	 * is needed only if not all of the rows of the result set are fetched.
	 *
	 * See http://php.net/manual/en/pdostatement.closecursor.php
	 */
	public function closeCursor() {
		if ($this->stmt) {
			$this->stmt->closeCursor();
			$this->stmt = NULL;
		}
	}
}
