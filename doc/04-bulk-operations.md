#Bulk Operations#
Background processes in PHP often need to query a batch of records from a 
combination of SQL tables.  Often times we just use the ORM that comes with the
PHP framework that we are using; that is fine until the need arises to handle
thousands of records in an efficient manner.  ORMs do not make it easy to
handle SELECTs of thousands of rows because of the "hydration" process; mapping
columns in result set to PHP variables.  BackendProject comes with a 
BaseDatabaseQuery class that allows you to easily define queries that allow
iterating through results in an efficient manner.

#Bulk SELECTs#
A BaseDatabaseQuery is a helpful to query rows from a database in
memory-efficient manner by reducing the amount of PHP variable 
allocations. BaseDatabaseQuery instances will define the SQL query to
be run as well as the variables that it needs to bind; the Base
class will prepare the query, bind the variables, and iterate through
the result set.

##Sample usage##
Say that you need to run this query:

``

SELECT
 first_name, last_name, zip_code
FROM
 users u JOIN user_addresses a ON(u.user_id = a.user_id) 
WHERE
 zip_code >= ? AND zip_code <= ?

``

To execute this, first create a sub-class of BaseDatabaseQuery

``php

 class UserZipCodeDatabaseQuery extends BaseDatabaseQuery {

		// BaseDatabaseQuery will automatically bind these variables to
		// columns in the result set (based on their name); that way we can 
		// iterate through the entire result set without repeatedly allocating 
		// PHP variables
		public $first_name;
		public $last_name;
		public $zip_code;'


	public function init($beginZipCode, $endZipCode) {
		$sql = <<SQL
			SELECT
				first_name, last_name, zip_code
			FROM
				users u JOIN user_addresses a ON(u.user_id = a.user_id) 
			WHERE
				zip_code >= ? AND zip_code <= ?
SQL;
		return $this->initSql($sql, array($beginZipCode, $endZipCode));
	}
 
 }

``

Then, the sub-class can be used to efficiently iterate through records,
no matter how many records the result set has:

``
$query = new UserZipCodeDatabaseQuery();
if ($query->init(91000, 95000) && $query->prepare($pdo)) {
	while ($query->fetch()) {
		echo $query->first_name . ' ' $query->last_name;
		echo ' zip=' . $query->zip_code . "\n"; 
	}
}

``

Notes about result set column binding:

- the name of the column and the name of the PHP property must match exactly
  (case-sensitive)
- SQL column aliases are supported
- SQL fully qualified columns are supported; when used the PHP property
  must match the SQL column name
- If no columns could be bound (because no properties matched column names) 
  a PHP assertion will be triggered.

