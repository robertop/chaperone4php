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
 *
 * Notes about result set column binding:
 *
 * - the name of the column and the name of the PHP property must match exactly
 *   (case-sensitive)
 * - SQL column aliases are supported
 * - SQL fully qualified columns are supported; when used the PHP property
 *   must match the SQL (unqualified) column name
 * - If no columns could be bound (because no properties matched column names) 
 *   a PHP assertion will be triggered.
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
	}

	/**
	 * Prepares the query to be executed, executes the query, and binds
	 * columns of the result set to PHP variables. By default, columns
	 * of the result set are bound to public members of this instance. 
	 *
	 * As an example, let's say you want to run this query:
	 *
	 * SELECT
	 *    first_name, last_name, zip_code
	 * FROM
	 *    users u JOIN user_addresses a ON(u.user_id = a.user_id)
	 *
	 * When this method is executed, the result set bindings will be as 
	 * follows:
	 *
	 * first_name ==> $this->first_name
	 * last_name ==> $this->last_name
	 * zip_code ==> $this->zip_code
	 *
	 * The properties MUST exist before this method is called.
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
			$success = $this->stmt->execute($queryParams);
			if ($success) {
				$this->bindColumns($sql);
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
		
	/**
	 * The default, simple way of binding columns to this object.  The query
	 * is parsed, and when a SELECT column has the same name as a property
	 * name of this object, then that property will be bound to the column. 
	 * Column aliases and fully qualified column names are taken into account.
	 *
	 * @param string $sql the SQL that is being executed
	 */
	protected function bindColumns($sql) {
		$parser = new \PHPSQLParser\PHPSQLParser($sql);
		$sqlTree = $parser->parsed;
		$colNumber = 1;
		if (isset($sqlTree['SELECT'])) {
			
			$columns = $sqlTree['SELECT'];
			foreach ($columns as $col) {
				$colName = '';
				if (in_array($col['expr_type'], array('aggregate_function', 'function'))
					&& isset($col['alias'], $col['alias']['as'], $col['alias']['name']) 
					&& $col['alias']['as'] && FALSE === stripos($col['alias']['name'], "'")) {
					
					// an aggregate function, like " SUM(*) as s"
					// these should have aliases; the alias is the property
					// to bind to
					// using 'parts' so that we get the alias without any
					// single quotes
					$colName = $col['alias']['name'];
				}
				else if (in_array($col['expr_type'], array('aggregate_function', 'function'))
					&& isset($col['alias'],
						$col['alias']['no_quotes']['parts'])
					&& $col['alias']['no_quotes']['parts']) {
					
					// an aggregate function, like "SUM(*) 'total'"
					// note the single quotes
					// these should have aliases; the alias is the property
					// to bind to
					$colName = end($col['alias']['no_quotes']['parts']);
				}
				else if ($col['expr_type'] == 'colref' 
					&& isset($col['alias'], $col['alias']['as']) 
					&& FALSE === stripos($col['alias']['name'], "'")) {
					
					// an aliased column, ie. name as 'n'
					$colName = $col['alias']['name'];
				}
				else if ($col['expr_type'] == 'colref'
					&& isset($col['no_quotes'], $col['no_quotes']['parts'])) {
					
					// a fully qualified column name; a column name that has
					// "parts" ie. table.col, parts are [table, col]
					// this also handles quotes in aliases like
					// "item_count as 'count' "
					$colName = end($col['no_quotes']['parts']);
				}
				
				else if ($col['expr_type'] == 'colref' 
					&& isset($col['base_expr'])) {
					
					// a "no-frills" column
					$colName = $col['base_expr'];
				}
				
				if (property_exists($this, $colName)) {
					$this->bindResultColumn($this->stmt, $colNumber, $colName);
					$colNumber++;
				}
			}
		}
		assert('$colNumber > 1');
	}
	
	/**
	 * The default method of binding a SQL result column to a PHP variable.
	 * The parameter will be bound as a string parameter.
	 *
	 * @param \PDOStatement $stmt the statement being executed
	 * @param int $colNumber the index of the column in the SELECT clause. This
	 *        number should be 1-based.
	 * @param string $colName the name of the column, as it was in the SELECT
	 *        clause
	 */
	protected function bindResultColumn(\PDOStatement $stmt, $colNumber, 
			$colName) {
		assert('property_exists($this, $colName)');
		$stmt->bindColumn($colNumber, $this->{$colName});
	}

}
