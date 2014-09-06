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

class DatabaseLoaderTest extends BaseTest {

	/**
	 * The object under test
	 *
	 * @var \Chaperone4php\DatabaseLoader
	 */
	private $loader;
	
	/**
	 * The row to be added to the database table
	 */
	private $user;
	
	/**
	 * the columns of the database table that will have non-default values.
	 */
	private $userColumns;
	
	protected function setUp() {
		$this->loader = new \Chaperone4php\DatabaseLoader();
		$this->truncate('users');
		
		$this->user = new \stdClass();
		$this->user->username = 'john.doe';
		$this->user->password = '';
		$this->user->password_salt = '';
		$this->user->email = 'john.doe@gmail.com';
		$this->user->first_name = 'john';
		$this->user->last_name = 'doe';
		
		$this->userColumns = array(
			'username',
			'password',
			'password_salt',
			'email',
			'first_name',
			'last_name'			
		); 
	}

	function testNoRows() {
		
		// when we attempt to commit with no rows, we should
		// return a proper false response
		$good = $this->loader->init('users', $this->userColumns);
		$this->assertTrue($good);
		
		$numberAdded = $this->loader->commit($this->pdo());
		$this->assertEquals(-1, $numberAdded);
	}
	
	function testCommitSingleRow() {
		
		// test the success case; row is successfully added to the 
		// database table.
		$good = $this->loader->init('users', $this->userColumns);
		$this->assertTrue($good);
		
		$good = $this->loader->add($this->user);
		$this->assertTrue($good);
		
		$numberAdded = $this->loader->commit($this->pdo());
		$this->assertEquals(1, $numberAdded);
		$this->assertSingleUserSaved();
	}
	
	function testWithColumnDelimitersInText() {
		
		// test the success case when a column has delimiters as part of the
		// text
		$good = $this->loader->init('users', $this->userColumns);
		$this->assertTrue($good);
		
		$this->user->last_name = "O'neil";
		
		$good = $this->loader->add($this->user);
		$this->assertTrue($good);
		
		$numberAdded = $this->loader->commit($this->pdo());
		$this->assertEquals(1, $numberAdded);
		$this->assertSingleUserSaved();
	}
	
	function testRowDelimitersInText() {
		
		// test the success case when a column has delimiters as part of the
		// text
		$good = $this->loader->init('users', $this->userColumns);
		$this->assertTrue($good);
		
		$this->user->last_name = "James\nColes";
		
		$good = $this->loader->add($this->user);
		$this->assertTrue($good);
		
		$numberAdded = $this->loader->commit($this->pdo());
		$this->assertEquals(1, $numberAdded);
		$this->assertSingleUserSaved();
	}
	
	function testCommitMultipleRows() {
	
		// test that more than 1 row can be inserted
		$good = $this->loader->init('users', $this->userColumns);
		$this->assertTrue($good);
		
		// add user number 1
		$good = $this->loader->add($this->user);
		$this->assertTrue($good);
	
		// add another user, but modify the object so that we use
		// different values
		$this->user->username = 'jane.smith';
		$this->user->email = 'jsmith@gmail.com';
		$this->user->first_name = 'jane';
		$this->user->last_name = 'smith';
		$good = $this->loader->add($this->user);
		$this->assertTrue($good);
		
		$numberAdded = $this->loader->commit($this->pdo());
		$this->assertEquals(2, $numberAdded);
		
		// make sure both users have been added
		$sql = 'SELECT * FROM users ORDER BY username';
		$stmt = $this->pdo()->prepare($sql);
		$stmt->execute(array('jane.smith'));
		
		$this->user->username = 'jane.smith';
		$this->user->email = 'jsmith@gmail.com';
		$this->user->first_name = 'jane';
		$this->user->last_name = 'smith';
		
		$result =  $stmt->fetch(\PDO::FETCH_ASSOC);
		$this->assertInternalType('array', $result);
		$this->assertEquals($this->user->username, $result['username']);
		$this->assertEquals($this->user->password, $result['password']);
		$this->assertEquals($this->user->password_salt, $result['password_salt']);
		$this->assertEquals($this->user->email, $result['email']);
		$this->assertEquals($this->user->first_name, $result['first_name']);
		$this->assertEquals($this->user->last_name, $result['last_name']);
		
		$this->user->username = 'john.doe';
		$this->user->email = 'john.doe@gmail.com';
		$this->user->first_name = 'john';
		$this->user->last_name = 'doe';
		
		$result =  $stmt->fetch(\PDO::FETCH_ASSOC);
		$this->assertInternalType('array', $result);
		$this->assertEquals($this->user->username, $result['username']);
		$this->assertEquals($this->user->password, $result['password']);
		$this->assertEquals($this->user->password_salt, $result['password_salt']);
		$this->assertEquals($this->user->email, $result['email']);
		$this->assertEquals($this->user->first_name, $result['first_name']);
		$this->assertEquals($this->user->last_name, $result['last_name']);
		
	}
	
	/**
	 * queries the database, loads the user record and asserts that
	 * the database column values match the user object
	 */
	private function assertSingleUserSaved() {
	
		// now make sure that the database table contains 
		// the new row
		$sql = 'SELECT * FROM users WHERE username = ?';
		$stmt = $this->pdo()->prepare($sql);
		$stmt->execute(array('john.doe'));
		$result =  $stmt->fetch(\PDO::FETCH_ASSOC);
		$this->assertInternalType('array', $result);
		$this->assertEquals($this->user->username, $result['username']);
		$this->assertEquals($this->user->password, $result['password']);
		$this->assertEquals($this->user->password_salt, $result['password_salt']);
		$this->assertEquals($this->user->email, $result['email']);
		$this->assertEquals($this->user->first_name, $result['first_name']);
		$this->assertEquals($this->user->last_name, $result['last_name']);
		
		$this->assertFalse($stmt->fetch());
	}
}