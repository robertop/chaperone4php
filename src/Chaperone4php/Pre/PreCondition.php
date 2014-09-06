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
namespace Chaperone4php\Pre;

/**
 * A Pre Condition is a class that encapsulates some checks to be done before
 * a script performs its work.  Types of conditions include: checking for 
 * available disk space, checking for database access.
 */

abstract class PreCondition {

	/**
	 * Subclasses will implment their checks in this method.
	 *
	 * @return bool Subclasses should return TRUE if the condition 
	 *         has been met
	 */
	abstract public function check();
}
