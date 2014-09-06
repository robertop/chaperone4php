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


class DatabaseQueryTest extends BaseTest {

	/**
	 * The object under test.
	 * @var BaseDatabaseQuery
	 */
	private $query;

	public function setup() {
		$this->query = new UsersSelectDatabaseQuery();
		
		$this->query->username = '';
		$this->query->password = '';
		$this->query->password_salt = '';
		$this->query->email = '';
		$this->query->first_name = '';
		$this->query->last_name = '';
		$this->query->created = '';
		$this->query->updated = '';
		
		$this->truncate('users');
		$this->insertUser('john', 'doe', 'abc123');
		$this->insertUser('jane', 'smith', 'pass');
		$this->insertUser('sally', 'james', 'none');
	}
	
	function testOneRecord() {
		
		// this test will exercise when the result set has only
		// 1 record in it.
		$this->query->initSql(
			'SELECT ' . 
			'username, password, password_salt, email, first_name, last_name,' .
			'created, updated ' .
			'FROM users ' .
			'WHERE username = ?',
			array('jane.smith')
		);
		
		$success = $this->query->prepare($this->pdo());
		$this->assertTrue($success);
		
		$success = $this->query->fetch();
		$this->assertTrue($success);
		
		$this->assertEquals('jane.smith', $this->query->username);
		$this->assertEquals(md5('pass'), $this->query->password);
		$this->assertEquals(substr(md5('pass'), 0, 12), $this->query->password_salt);
		$this->assertEquals('jane.smith@fakemail.com', $this->query->email);
		$this->assertEquals('jane', $this->query->first_name);
		$this->assertEquals('smith', $this->query->last_name);
		$this->assertNotEmpty($this->query->created);
		$this->assertNull($this->query->updated);
		
		$this->assertFalse($this->query->fetch());
	}
	
	function testManyRecords() {
	
		// this test will exercise when the result set has more
		// than 1 record in it.
		$this->query->initSql(
			'SELECT ' . 
			'username, password, password_salt, email, first_name, last_name,' .
			'created, updated ' .
			'FROM users ' .
			'ORDER BY username',
			array()
		);
		
		$success = $this->query->prepare($this->pdo());
		$this->assertTrue($success);
		
		// to test fetching, we assert each row came back in the order
		// of the sort
		$success = $this->query->fetch();
		$this->assertTrue($success);
		
		$this->assertEquals('jane.smith', $this->query->username);
		$this->assertEquals(md5('pass'), $this->query->password);
		$this->assertEquals(substr(md5('pass'), 0, 12), $this->query->password_salt);
		$this->assertEquals('jane.smith@fakemail.com', $this->query->email);
		$this->assertEquals('jane', $this->query->first_name);
		$this->assertEquals('smith', $this->query->last_name);
		$this->assertNotEmpty($this->query->created);
		$this->assertNull($this->query->updated);
		
		$success = $this->query->fetch();
		$this->assertTrue($success);
		
		$this->assertEquals('john.doe', $this->query->username);
		$this->assertEquals(md5('abc123'), $this->query->password);
		$this->assertEquals(substr(md5('abc123'), 0, 12), $this->query->password_salt);
		$this->assertEquals('john.doe@fakemail.com', $this->query->email);
		$this->assertEquals('john', $this->query->first_name);
		$this->assertEquals('doe', $this->query->last_name);
		$this->assertNotEmpty($this->query->created);
		$this->assertNull($this->query->updated);
		
		$success = $this->query->fetch();
		$this->assertTrue($success);
		
		$this->assertEquals('sally.james', $this->query->username);
		$this->assertEquals(md5('none'), $this->query->password);
		$this->assertEquals(substr(md5('none'), 0, 12), $this->query->password_salt);
		$this->assertEquals('sally.james@fakemail.com', $this->query->email);
		$this->assertEquals('sally', $this->query->first_name);
		$this->assertEquals('james', $this->query->last_name);
		$this->assertNotEmpty($this->query->created);
		$this->assertNull($this->query->updated);
		
		$this->assertFalse($this->query->fetch());
	}
	
	function testBadQuery() {
	
		// test that when a bad query (invalid sql) is used, that 
		// the prepare method returns FALSE
		$this->query->initSql(
			'SELECT ' . 
			'username, non-existant-field' .
			'FROM users ' .
			'ORDER BY username',
			array()
		);
		
		$success = $this->query->prepare($this->pdo());
		$this->assertFalse($success);
	}
	
	function testCaseColumn() {
	
		// test conditionals in a SELECT clause
		// should bind columns to properties
		$this->query->SMITH_COUNT = '';
		$this->query->initSql(
			"SELECT " .
			"SUM(CASE WHEN (u.last_name = ?) THEN 1 ELSE 0 END) AS 'SMITH_COUNT' " .
			"FROM users u",
			array('Smith')
		);
		
		$success = $this->query->prepare($this->pdo());
		$this->assertTrue($success);
		
		$success = $this->query->fetch();
		$this->assertTrue($success);
		$this->assertEquals(1, $this->query->SMITH_COUNT);
		$success = $this->query->fetch();
		$this->assertFalse($success);
	}
	
	
	function testFunctions() {
		
		// test that we can parse functions from the select clause
		// correctly
		$this->query->day_created = '';
		$this->query->initSql(
			"SELECT " .
			"created, " .
			"DATE_FORMAT(u.created, '%Y%m%d') as day_created " .
			"FROM users u " .
			"WHERE username = ?",
			array('john.doe')
		);
		
		$success = $this->query->prepare($this->pdo());
		$this->assertTrue($success);
		
		$success = $this->query->fetch();
		$this->assertTrue($success);

		// when asserting, be careful of comparisons so that tests
		// are not brittle; use the db time to calculate the day
		// and now PHP time()
		$this->assertEquals(date('Ymd', strtotime($this->query->created)), 
			$this->query->day_created);
		$success = $this->query->fetch();
		$this->assertFalse($success);
	}
	
	function testAliasColumns() {
		
		// test that when the query contains aliased columns
		// that the binding of columns is done properly
		// note that mysql treats the second column as an alias too
		$this->query->last = '';
		$this->query->first = '';
		$this->query->initSql(
			'SELECT last_name as last, first_name first ' .
			'FROM users ' .
			'WHERE username = ?',
			array('jane.smith')
		);
		$this->query->cnt = '';
		
		$success = $this->query->prepare($this->pdo());
		$this->assertTrue($success);
		
		$success = $this->query->fetch();
		$this->assertTrue($success);
		$this->assertEquals('smith', $this->query->last);
		$this->assertEquals('jane', $this->query->first);
		$success = $this->query->fetch();
		$this->assertFalse($success);
	}
	
		
	function testQualifiedColumn() {
		
		// test that when the query contains a fully qualified
		// column (table.column) that the binding of columns is done properly
		$this->query->initSql(
			'SELECT u.first_name, last_name ' .
			'FROM users u ' .
			'ORDER BY u.first_name',
			array()
		);
	
		$success = $this->query->prepare($this->pdo());
		$this->assertTrue($success);
		
		$success = $this->query->fetch();
		$this->assertTrue($success);
		$this->assertEquals('jane', $this->query->first_name);
		$success = $this->query->fetch();
		$this->assertTrue($success);
		$this->assertEquals('john', $this->query->first_name);
		$success = $this->query->fetch();
		$this->assertTrue($success);
		$this->assertEquals('sally', $this->query->first_name);
		$success = $this->query->fetch();
		$this->assertFalse($success);
	}
	
	private function insertUser($firstName, $lastName, $password) {
		$sql = 'INSERT INTO users(' .
			'username, password, password_salt, email, first_name, ' .
			'last_name, created, updated' .
			') VALUES (' . 
			'?, ?, ?, ?, ?, ?, NOW(), NULL' .
			')';
		$pdo = $this->pdo();
		$stmt = $pdo->prepare($sql);
		$stmt->execute(array(
			"{$firstName}.{$lastName}",
			
			// this is test code, don't do this in production code
			md5($password),
			substr(md5($password), 0, 12),
			"{$firstName}.{$lastName}@fakemail.com",
			$firstName, 
			$lastName
		));
		
	}
}
