<?php
	/*
	 * MySQL.inc.php
	 *
	 * Created By : sureshinde
	 *
	 * Created On : Jan 2, 2012 2:09:58 PM
	 *
	 * Created Under : 
	 *
	 * Copyright (C) 2010-2012 Indosoft Inc.
	 *
	 * Suresh Shinde <sureshshinde@benchmarkitsolutions.com>
	 *
	 * This program is free software; you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation; either version 2, or (at your option)
	 * any later version.
	 *
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with this program; if not, write to the Free Software
	 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
	 *
	 */

	final class MySQLConnection 
	{
		private $connection;

		public $query;
		
		public $logs;

		public function __construct($hostname, $username, $password, $database) 
		{
			if (!$this->connection = mysql_connect($hostname, $username, $password)) {
				exit('Error: Could not make a database connection using ' . $username . '@' . $hostname);
			}

			if (!mysql_select_db($database, $this->connection)) {
				exit('Error: Could not connect to database ' . $database);
			}

			mysql_query("SET NAMES 'utf8'", $this->connection);
			mysql_query("SET CHARACTER SET utf8", $this->connection);
			mysql_query("SET CHARACTER_SET_CONNECTION=utf8", $this->connection);
			mysql_query("SET SQL_MODE = ''", $this->connection);
			mysql_query("SET SESSION time_zone = '+0.00'", $this->connection);
			mysql_query("SET time_zone = '+0.00'", $this->connection);
			
			if(defined("OC_DEBUG_MYSQL") && OC_DEBUG_MYSQL)
				$this->logs = new Logger(OC_LOG_DIR,"mysql");
		}

		public function query($sql) {
			$this->sql = $sql;
			
			if(defined("OC_DEBUG_MYSQL") && OC_DEBUG_MYSQL)
				$this->logs->add($sql,"QUERY");
			
			$resource = mysql_query($sql);

			if ($resource) {
				if (is_resource($resource)) {
					$i = 0;

					$data = array();

					while ($result = mysql_fetch_assoc($resource)) {
						$data[$i] = $result;

						$i++;
					}

					mysql_free_result($resource);

					$query = new stdClass();
					$query->sql = $sql;
					$query->row = isset($data[0]) ? $data[0] : array();
					$query->rows = $data;
					$query->num_rows = $i;

					unset($data);

					return $query;
				} else {
					return TRUE;
				}
			} else {
				
				if(defined("OC_DEBUG_MYSQL") && OC_DEBUG_MYSQL)
					$this->logs->add(mysql_error($this->connection),"ERROR");
				exit('Error: ' . mysql_error($this->connection) . '<br />Error No: ' . mysql_errno($this->connection) . '<br />' . $sql);
			}
		}

		public function escape($value) {
			return mysql_real_escape_string($value, $this->connection);
		}

		public function countAffected() {
			return mysql_affected_rows($this->connection);
		}

		public function getLastId() {
			return mysql_insert_id($this->connection);
		}
		
		public function getConnection()
		{
			return $this->connection;
		}

		public function __destruct() {
			@mysql_close($this->connection);
		}
	}
?>