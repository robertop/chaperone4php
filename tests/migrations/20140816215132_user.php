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
use Phinx\Migration\AbstractMigration;

/**
 * This migration creates tables used by the DatabaseQuery tests.
 */
class User extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     *
     * Uncomment this method if you would like to use it.
     *
    public function change()
    {
    }
    */
    
    /**
     * Migrate Up.
     */
    public function up()
    {
		$users = $this->table('users');
		$users->addColumn('username', 'string', array(
				'limit' => 20,
				'default' => '', 
				'null' => FALSE
			))
			->addColumn('password', 'string', array(
				'limit' => 40,
				'default' => '', 
				'null' => FALSE
			))
			->addColumn('password_salt', 'string', array(
				'limit' => 40,
				'default' => '', 
				'null' => FALSE
			))
			->addColumn('email', 'string', array(
				'limit' => 100,
				'default' => '', 
				'null' => FALSE
			))
			->addColumn('first_name', 'string', array(
				'limit' => 30,
				'default' => '', 
				'null' => FALSE
			))
			->addColumn('last_name', 'string', array(
				'limit' => 30,
				'default' => '', 
				'null' => FALSE
			))
			->addColumn('created', 'datetime')
			->addColumn('updated', 'datetime', array(
				'default' => null, 
				'null' => TRUE
			))
			->addIndex(array('username', 'email'), array('unique' => true))
			->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
		$this->dropTable('users');
    }
}