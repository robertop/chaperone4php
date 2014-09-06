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
 * A DatabaseLoader is a class that helps with bulk inserting (loading) a
 * large amount of records into a database table.
 *
 * Loading is done via bulk insert commands, also referred to as load data
 * commands. Because of this, it may not be possible to know how many
 * records failed to be inserted.
 *
 * Records will be stored in a temporary file, then loaded into the
 * database at once.
 *
 * Usage:
 * 1. Call init() to set the column and row delimiters and open 
 *    a temp file.
 * 2. Call add() to add rows to the batch. add() will be called many
 *    times until all desired rows are added.
 * 3. Call commit() to perform the batch insert.
 *
 * There are various rules regarding what happens when there are
 * duplicates or invalid records in the batch.  See the MySQL manual
 * page on "LOAD DATA LOCAL INFILE" syntax for more details.
 *
 * Note: Author had quite the trouble getting LOAD DATA LOCAL INFILE
 * to work within PHP.  You will most likely get an error: 
 * 
 * The used command is not allowed with this MySQL version
 *
 * A couple of suggestions:
 * 1. make sure you create your PDO connection with the enable
 *    local loading: use PDO::MYSQL_ATTR_LOCAL_INFILE in the constructor
 * 2. Use the pdo_mysqlnd driver as opposed to pdo_mysql driver.  
 *    See http://stackoverflow.com/questions/23525111/why-cant-i-use-load-data-local-with-pdo-even-though-i-can-from-cli-client
 *    I could not get local file loading to work with pdo_mysql driver; note
 *    that I was using version 5.5.16+dfsg-1+deb.sury.org~precise+1
 *    of the pdo_mysql driver from the PPA repository http://ppa.launchpad.net/ondrej/php5/ubuntu 
 *
 */
class DatabaseLoader {

	/**
	 * Columns in the temp file will be delimited by this character
	 * @var string 
	 */
	private $columnDelimiter;
	
	/**
	 * Rows (lines) in the temp file will be delimited by this character
	 * @var string 
	 */
	private $rowDelimiter;
	
	/**
	 * Columns in the temp file will be enclosed by this character
	 * @var string 
	 */
	private $enclosure;
	
	/**
	 * Full path to where rows are stored.
	 * @var string 
	 */
	private $tmpFile = '';
	
	/**
	 * @var resource the file handle to the temp file
	 */
	private $fp;
	
	/**
	 * @var string the name of the database table to insert the records into
	 */
	private $tableName;
	
	/**
	 * The names of the columns being inserted into the database. The
	 * names are in order that they will be written to in the temp file.
	 *
	 * @var array of strings 
	 */
	private $columns;
	
	/**
	 * values of a single row being added to the temp file.
	 * @var array os strings
	 */
	private $values;
	
	/**
	 * @var boolean TRUE if at least 1 row has been added
	 */
	private $hasAdded;
	
	public function __destruct() {
		if ($this->tmpFile && file_exists($this->tmpFile)) {
			unlink($this->tmpFile);
		}
	}

	/**
	 * Prepares the database loader by creating a temp file for
	 * records to go into. temp file will be created in the system temp 
	 * directory.
	 * 
	 * @param string $tableName the name of the table to be inserted
	 * @param array $columns the names of the columns to be inserted.
	 * @return bool TRUE if the temp file was opened.
	 */
	public function init($tableName, $columns) {
		$this->columnDelimiter = ',';
		
		// if this is changed, also change the sql in the commit() method
		$this->rowDelimiter = "\n";
		$this->enclosure = '"';
		$this->tableName = $tableName;
		$this->columns = array_values($columns);
		$this->values = array_fill(0, count($columns), '');
		$this->tmpFile = tempnam(sys_get_temp_dir(), $tableName);
		$this->fp = fopen($this->tmpFile, 'wb+');
		$this->hasAdded = FALSE;
		
		// be nice and put a file header, makes files more helpful
		if ($this->fp) {
			fputcsv($this->fp, $this->columns, $this->columnDelimiter,
				$this->enclosure);
		}
		
		return $this->fp !== FALSE;
	}
	
	/**
	 * adds the given item to the temp file to be inserted later on.
	 * The given item must contain ALL of the columns are properties.
	 *
	 * @var stdClass $item the object to insert
	 * @return bool TRUE if the row was added to the file
	 */
	public function add($item) {
		$added = FALSE;
		assert('$this->tmpFile');
		if (!$this->tmpFile) {
			return $added;
		}
		
		$columnCount = 0;
		foreach ($this->columns as $i => $col) {
			if (property_exists($item, $col)) {
				$this->values[$i] = $item->{$col};
				$columnCount++;
			}
		}
		$ret = FALSE;
		if ($columnCount == count($this->columns)) {		
			$written = fputcsv($this->fp, $this->values, $this->columnDelimiter,
				$this->enclosure);
			if ($written && !$this->hasAdded) {
				$this->hasAdded = TRUE;
			}
			if ($written) {
				$ret = TRUE;
			}
		}
		return $ret;
	}
	
	/**
	 * Performs the actual inserts into the database.
	 *
	 * @var \PDO $pdo the database connection
	 * @return int the number of records that were added, as reported by 
	 *         the database driver. 
	 *         This method will return -1 if no rows were added via
	 *         the add() method.
	 */
	public function commit(\PDO $pdo) {
		if (!$this->hasAdded) {
			return -1;
		}
		
		// close the file so that mysql can read it in
		fclose($this->fp);
		
		$sqlFormat = "LOAD DATA LOCAL INFILE '%s' INTO TABLE %s " . 
			"FIELDS TERMINATED BY '%s' " .
			"OPTIONALLY ENCLOSED BY '%s' " .
			"ESCAPED BY '' " .
			"LINES TERMINATED BY '%s' " .
			"IGNORE 1 LINES " .
			'(%s)';
		$sql = sprintf($sqlFormat, $this->tmpFile, $this->tableName,
			$this->columnDelimiter, $this->enclosure, "\\n",
			join(',', $this->columns)
		);
		$result = $pdo->exec($sql);
		
		// cleanup files
		unlink($this->tmpFile);
		
		return $result;
	}
}
