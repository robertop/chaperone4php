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
 * This class represents rows from the `users` table as defined in the
 * database migrations.
 */
class UsersSelectDatabaseQuery extends BaseDatabaseQuery {

	public $username;
	public $first_name;
	public $last_name;
	public $email;
	public $password;
	public $password_salt;
	public $created;
	public $udpated;
}
