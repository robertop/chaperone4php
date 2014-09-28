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
 * A ResultSetBinder maps an SQL query to properties of an object. Mapping
 * is done by parsing an SQL query, and binding a query's SELECT columns
 * to an object's properties.  The SQL query is parsed; aliased columns
 * are properly handled.
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
class ResultSetBinder {

	/**
	 * The default, simple way of binding columns to this object.  The query
	 * is parsed, and when a SELECT column has the same name as a property
	 * name of the given object, then that property will be bound to the column. 
	 * Column aliases and fully qualified column names are taken into account.
	 *
	 * As an example, let's the following query was run:
	 *
	 * SELECT
	 *    first_name, last_name, zip_code
	 * FROM
	 *    users u JOIN user_addresses a ON(u.user_id = a.user_id)
	 *
	 * After this method is called, the result set bindings will be as 
	 * follows:
	 *
	 * first_name ==> $obj->first_name
	 * last_name ==> $obj->last_name
	 * zip_code ==> $obj->zip_code
	 *
	 * The properties MUST exist on $obj before this method is called.
	 *
	 * @param string $sql the SQL that is being executed
	 * @param \PDOStatement $stmt the statement being executed
	 * @param stdClass $obj the object to bind the result set to
	 * @return void
	 */
	public function bind($sql, \PDOStatement $stmt, $obj) {
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
				
				if (property_exists($obj, $colName)) {
					$this->bindResultColumn($stmt, $obj, $colNumber, $colName);
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
	 * @param stdClass $obj the object to bind the result set to
	 * @param int $colNumber the index of the column in the SELECT clause. This
	 *        number should be 1-based.
	 * @param string $colName the name of the column, as it was in the SELECT
	 *        clause
	 */
	protected function bindResultColumn(\PDOStatement $stmt, $obj, $colNumber, 
			$colName) {
		assert('property_exists($obj, $colName)');
		$stmt->bindColumn($colNumber, $obj->{$colName});
	}
}