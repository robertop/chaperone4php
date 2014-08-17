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
namespace BackendProject;

/**
 * A BaseDatabaseQuery is a helpful to query rows from a database in
 * memory-efficient manner by reducing the amount of PHP variable 
 * allocations. BaseDatabaseQuery instances will define the SQL query to
 * be run as well as the variables that it needs to bind; the Base
 * class will prepare the query, bind the variables, and iterate through
 * the result set.
 * 
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
	 * statement.
	 *
	 * @param string $sql the statement to execute.
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
	 * @return bool TRUE if the query was valid AND was executed.
	 */
	public function prepare(\PDO $pdo) {
		assert('strlen($this->sql) > 0', 
			'initSql() must be used to set the query to be executed'
		);
		$sql = $this->sql;
		$this->stmt = $pdo->prepare($sql);
		
		// according to PHP docs, we must call execute() before we
		// bind the result set 
		$queryParams = $this->queryParams;
		$success = $this->stmt->execute($queryParams);
		if ($success) {
			$this->bindColumns($sql);
		}
		return $success;
	}
	
	/**
	 * iterate to the next record of the result set. After a call to this
	 * method, the bound parameters will have new values.
	 */
	public function fetch() {
		assert('$this->stmt != NULL',
			'A valid query must be executed before calling the fetch() method'
		);
		return $this->stmt->fetch(\PDO::FETCH_BOUND);
	}
		
	/**
	 * The default, simple way of binding columns to this object.  The query
	 * is parsed, and when a SELECT column has the same name as a property
	 * name of this object, then that property will be bound to the column.
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
		assert('$colNumber > 1',
			'no properties were bound to the statement. You must have at ' .
			'least 1 property that has the same name as a column name in the ' .
			'query'
		);
	}
	
	/**
	 * The default method of binding a SQL result column to a PHP variable.
	 * The parameter will be bound as a string parameter.
	 *
	 * @param \PDOStatement $stmt the statement 
	 * @param int $colNumber the index of the column in the SELECT clause. This
	 *        number is 1-based.
	 * @param string $colName the name of the column, as it was in the SELECT
	 *        clause
	 */
	protected function bindResultColumn(\PDOStatement $stmt, $colNumber, 
			$colName) {
		assert('property_exists($this, $colName)',
			"by default, this instance must have a property named {$colName} " .
			"so that the column may be bound to it"
		);
		$stmt->bindColumn($colNumber, $this->{$colName});
	}

}
