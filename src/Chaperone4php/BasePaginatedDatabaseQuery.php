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
 * A BasePaginatedDatabaseQuery is a helpful to query rows from a database in
 * a "paginated" form; it will query for 1 page of data at time, automatically
 * ask for the next page while there are more pages to be read.
 *
 * BasePaginatedDatabaseQuery uses a BaseDatabaseQuery under the covers, so
 * the same way that BaseDatabaseQuery binds variables to this object is the
 * same way that BasePaginatedDatabaseQuery will bind variables to this object.
 *
 * Usage:
 * - Create a class that inherits from BasePaginatedDatabaseQuery
 * - The sub-class should have 1 public property for each column in
 *   th result set.
 * - Instantiate an object of the sub-class type, 
 * - Call initSql() to define the query being executed and the query
 *   parameters
 * - Call prepare() to execute the query
 * - Call fetch() repeatedly to iterate through the result set
 * - Access result set values via the public properties of the sub-class object
 *
 *
 * This class implements paginated queries *WITHOUT* using LIMIT,OFFSET clause
 * as the OFFSET clause suffers from performance degradation when querying
 * the final set of pages. Instead, this class requires a column to be used as
 * a unique identifier. For example, instead of using this query:
 *
 *      SELECT username, address FROM users LIMIT 1000, 250;
 *
 * it uses this query:
 *
 *      SELECT user_id, username, address 
 *      FROM users 
 *      WHERE user_id > 10000 ORDER BY user_id LIMIT 250;
 *
 * This class will keep track of the identifier and will reissue the query
 * after the final result in the page has been fetched.
 *
 * See http://use-the-index-luke.com/sql/partial-results/fetch-next-page to
 * read more info about how to substitute OFFSET clause with a condition that
 * uses an index.
 * 
 * See docs/04-bulk-operations.md for more info.
 *
 * See \Chaperone4php\ResultSetBinder for more info about result set column binding.
 *
 */
class BasePaginatedDatabaseQuery {

	/**
	 * The statement that we are iterating over. Will be NULL until the
	 * query is prepared.
	 *
	 * @var \PDOStatement
	 */
	private $stmt;
	
	/**
	 * Used to bind result set columns to properties on this object
	 *
	 * @var \Chaperone4php\ResultSetBinder
	 */
	private $binder;
	
	/**
	 * The SQL statement being executed. This can have placeholders, but will
	 * NOT have a LIMIT clause.
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
	 * The number of rows to query at once.
	 *
	 * @var int
	 */
	private $pageSize;
	
	/**
	 * To keep track of the number of rows on the current page. We need this
	 * number because we use it to determine whether to query for the next
	 * page (when currentPageRows >= pageSize). 
	 *
	 * @var int
	 */
	private $currentPageRows;
	
	/**
	 *
	 * @var string name of the field in the SQL statement that will be 
	 *       used as the unique identifier
	 */
	private $identifierColumn;
	
	/**
	 * The last value of the identifier column that was fetched. 
	 *
	 * @var int
	 */
	private $lastIdentifierValue;
	
	/**
	 * Set the query to be execute and the parameters to bind to the 
	 * statement. The rules for binding parameters are exactly the same as
	 * those for PDO::prepare() and PDOStatement::execute(), see the PHP docs at 
	 * http://php.net/manual/en/pdo.prepare.php and
	 * http://php.net/manual/en/pdostatement.execute.php
	 *
	 * There are a couple of preconditions that the query must meet:
	 *
	 * 1. It must be a SELECT statement, No other SQL queries are 
	 *    supported (INSERT, DELETE)
	 * 2. It should NOT have a LIMIT clause, it will be added 
	 *    by this class.
	 * 3. It should specify an order (ORDER BY clause) and the
	 *    clause should be of the given identifier column.
	 * 4. It should have an identifier column that is unique to each row.
	 * 5. The WHERE clause should contain, a condition
	 *    that compares the identifier column. This is how pagination is 
	 *    achieved.
	 * 6. The query should contained named parameters, as it is more consistent
	 *    to use named parameters because this class will automatically
	 *    bind the identifier parameter to the next set of rows.
	 *
	 * Example parameters:
	 * $sql:  SELECT user_name, user_id FROM users WHERE user_id > :user_id AND created >= :created ORDER BY user_id
	 * $queryParams: [':created' => '2014-09-01 00:00:00']
	 * $limit: 5000
	 * $identifierColumn: 'user_id'
	 *
	 * @param string $sql the statement to execute. This MUST be a SELECT
	 *        statement. No other SQL queries are supported (INSERT, DELETE)
	 *        See example above.
	 * @params array $queryParams associative array of parameters to bind to
	 *         the query. This 
	 * @param int $limit the number of rows to fetch at once.
	 * @param string $identifierColumn the name of the column that is used as
	 *        the.  This column MUST be in the SELECT clause, the WHERE clause 
	 *        AND the ORDER BY clause of the given SQL query.
	 */
	public function initSql($sql, $queryParams, $limit, $identifierColumn) {
		$this->sql = $sql;
		$this->queryParams = $queryParams;
		$this->limit = filter_var($limit, FILTER_SANITIZE_NUMBER_INT);
		$this->currentPageRows = 0;
		
		assert('$this->limit > 0');
		
		$this->identifierColumn = $identifierColumn;
		$this->lastIdentifierValue = 0;
		
		// set the bound parameter's initial value
		$this->queryParams[':' . $this->identifierColumn] = $this->lastIdentifierValue;
		$this->binder = new \Chaperone4php\ResultSetBinder();
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
		$sql .= ' LIMIT ' . $this->limit;
		
		// set the bound parameter's identifier value so that
		// we can query the next page of results.
		$this->queryParams[':' . $this->identifierColumn] = $this->lastIdentifierValue;
		
		$success = FALSE;
		$this->stmt = NULL;
		if ($sql) {
			$this->stmt = $pdo->prepare($sql);
			
			// according to PHP docs, we must call execute() before we
			// bind the result set 
			// TODO: how to bubble up statement errors
			$success = $this->stmt->execute($this->queryParams);
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
	 * Fetch may automatically query the next page when the current
	 * page has fetched in its entirety.
	 *
	 * @return bool TRUE if a new row was fetched (PHP variables were updated).
	 *         FALSE if there are no more records.
	 */
	public function fetch(\PDO $pdo) {
		if (!$this->stmt) {
			return FALSE;
		}
		$hasMore = $this->stmt->fetch(\PDO::FETCH_BOUND);
		
		// is there another page to query? when the last page returned
		// the page size number of rows, then there (might) be 
		// a next page
		if (!$hasMore & $this->currentPageRows >= $this->pageSize) {
			
			// time to close the result set and execute a query
			// to get the next page of results
			// prepare will bind
			$this->closeCursor();
			$this->currentPageRows = 0;
			$hasMore = $this->prepare($pdo);
			if ($hasMore) {
				$hasMore = $this->stmt->fetch(\PDO::FETCH_BOUND);
				
				// since we fetched a row, need to increment the counters
				$this->currentPageRows++;
				$this->lastIdentifierValue = $this->{$this->identifierColumn};
			}
			else {
				$this->closeCursor();
			}
		}
		else if (!$hasMore) {
		
			// time to close the result set, no more pages to query
			$this->closeCursor();
		}
		else {
			
			// when a new row is fetched, we need to increment the counter
			// and we need to keep track of the identifier of the row
			// so that we can use it in the query for the next page
			$this->currentPageRows++;
			$this->lastIdentifierValue = $this->{$this->identifierColumn};
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
