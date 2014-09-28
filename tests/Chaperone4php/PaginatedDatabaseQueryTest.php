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

class PaginatedDatabaseQueryTest extends BaseTest {

	/**
	 * The object under test.
	 * @var \Chaperone4php\BasePaginatedDatabaseQuery
	 */
	private $query;

	public function setup() {
		$this->query = new UsersSelectPaginatedDatabaseQuery();
		
		$this->query->id = '';
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
		$limit = 50;
		$this->query->initSql(
			'SELECT id, ' . 
			'username, password, password_salt, email, first_name, last_name,' .
			'created, updated ' .
			'FROM users ' .
			'WHERE id > :id AND username = :username ORDER BY id',
			array(':username' => 'jane.smith'),
			$limit,
			'id'
		);
		
		$success = $this->query->prepare($this->pdo());
		$this->assertTrue($success);
		
		$success = $this->query->fetch($this->pdo());
		$this->assertTrue($success);
		
		$this->assertEquals('jane.smith', $this->query->username);
		$this->assertEquals(md5('pass'), $this->query->password);
		$this->assertEquals(substr(md5('pass'), 0, 12), $this->query->password_salt);
		$this->assertEquals('jane.smith@fakemail.com', $this->query->email);
		$this->assertEquals('jane', $this->query->first_name);
		$this->assertEquals('smith', $this->query->last_name);
		$this->assertNotEmpty($this->query->created);
		$this->assertNull($this->query->updated);
		
		$this->assertFalse($this->query->fetch($this->pdo()));
	}
	
	function testPagination() {
		
		// this test exercises pagination; the PaginatedQuery class
		// will automatically query all pages for us
		// use a low limit so that we can test that the queries are 
		// executed again
		$limit = 1;
		$this->query->initSql(
			'SELECT id, ' . 
			'username, password, password_salt, email, first_name, last_name,' .
			'created, updated ' .
			'FROM users ' .
			'WHERE id > :id ORDER BY id',
			array(),
			$limit,
			'id'
		);
		
		$success = $this->query->prepare($this->pdo());
		$this->assertTrue($success);
		
		$success = $this->query->fetch($this->pdo());
		$this->assertTrue($success);
		
		$this->assertEquals('john.doe', $this->query->username);
		$this->assertEquals(md5('abc123'), $this->query->password);
		$this->assertEquals(substr(md5('abc123'), 0, 12), $this->query->password_salt);
		$this->assertEquals('john.doe@fakemail.com', $this->query->email);
		$this->assertEquals('john', $this->query->first_name);
		$this->assertEquals('doe', $this->query->last_name);
		$this->assertNotEmpty($this->query->created);
		$this->assertNull($this->query->updated);
		
		// page #2
		$success = $this->query->fetch($this->pdo());
		$this->assertTrue($success);
		
		$this->assertEquals('jane.smith', $this->query->username);
		$this->assertEquals(md5('pass'), $this->query->password);
		$this->assertEquals(substr(md5('pass'), 0, 12), $this->query->password_salt);
		$this->assertEquals('jane.smith@fakemail.com', $this->query->email);
		$this->assertEquals('jane', $this->query->first_name);
		$this->assertEquals('smith', $this->query->last_name);
		$this->assertNotEmpty($this->query->created);
		$this->assertNull($this->query->updated);
		
		// page #3
		$success = $this->query->fetch($this->pdo());
		$this->assertTrue($success);
		
		$this->assertEquals('sally.james', $this->query->username);
		$this->assertEquals(md5('none'), $this->query->password);
		$this->assertEquals(substr(md5('none'), 0, 12), $this->query->password_salt);
		$this->assertEquals('sally.james@fakemail.com', $this->query->email);
		$this->assertEquals('sally', $this->query->first_name);
		$this->assertEquals('james', $this->query->last_name);
		$this->assertNotEmpty($this->query->created);
		$this->assertNull($this->query->updated);
		
		$success = $this->query->fetch($this->pdo());
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
