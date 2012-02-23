<?php

	/*
	 * Utils.inc.php
	 *
	 * Created By : sureshinde
	 *
	 * Created On : Sep 07, 2011 11:14:42 PM
	 *
	 * Created Under : Benchmark Catalog
	 *
	 * Copyright (C) 2010-2011 Indosoft Inc.
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

	require_once(dirname(__FILE__)."/Thumbnail.inc.php");

	class Utils
	{
		function __construct()
		{

		}
		/*
		*
			Following functions are to check password during Webservice login
			1. tep_validate_password
			2. CheckPassword
			3. crypt_private
			4. encode64
		*
		*/
		function tep_validate_password($plain, $encrypted)
		{
			if ($plain != "" && $encrypted != "")
			{
				if (preg_match('/^[A-Z0-9]{32}\:[A-Z0-9]{2}$/i', $encrypted) === 1)
				{
					$stack = explode(':', $encrypted);

					if (sizeof($stack) != 2) return false;

					if (md5($stack[1] . $plain) == $stack[0])
					{
						return true;
					}
				}
				else
				{
					$pass = $this->CheckPassword($plain, $encrypted);
					return $pass;
				}
			}
			else
			{
				return false;
			}
		}


		function CheckPassword($password, $stored_hash)
		{
			$hash = $this->crypt_private($password, $stored_hash);
			if ($hash[0] == '*')
				$hash = crypt($password, $stored_hash);

			return $hash == $stored_hash;
		}

		function crypt_private($password, $setting)
		{
			$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
			$output = '*0';
			$PHP_VERSION = 5;
			if (substr($setting, 0, 2) == $output)
				$output = '*1';

			$id = substr($setting, 0, 3);
			# We use "$P$", phpBB3 uses "$H$" for the same thing
			if ($id != '$P$' && $id != '$H$')
				return $output;

			$count_log2 = strpos($itoa64, $setting[3]);
			if ($count_log2 < 7 || $count_log2 > 30)
				return $output;

			$count = 1 << $count_log2;

			$salt = substr($setting, 4, 8);
			if (strlen($salt) != 8)
				return $output;

			if ($PHP_VERSION >= 5)
			{
				$hash = md5($salt . $password, TRUE);
				do
				{
					$hash = md5($hash . $password, TRUE);
				} while (--$count);
			}
			else
			{
				$hash = pack('H*', md5($salt . $password));
				do
				{
					$hash = pack('H*', md5($hash . $password));
				} while (--$count);
			}

			$output = substr($setting, 0, 12);
			$output .= $this->encode64($hash, 16);

			return $output;
		}

		function encode64($input, $count)
		{
			$output = '';
			$i = 0;
			$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
			do
			{
				$value = ord($input[$i++]);
				$output .= $itoa64[$value & 0x3f];
				if ($i < $count)
					$value |= ord($input[$i]) << 8;
				$output .= $itoa64[($value >> 6) & 0x3f];
				if ($i++ >= $count)
					break;
				if ($i < $count)
					$value |= ord($input[$i]) << 16;
				$output .= $itoa64[($value >> 12) & 0x3f];
				if ($i++ >= $count)
					break;
				$output .= $itoa64[($value >> 18) & 0x3f];
			} while ($i < $count);

			return $output;
		}
		//Redirect to next page
		function redirect($page)
		{
			if(headers_sent())
			{
			  echo "<script>window.location='$page'</script>";
			}
			else
			{
				header("Location: $page");
			}
		}
		//geting all the variables which are post/get
		function getRequestedData()
		{
			if($_GET && empty($_POST))
				$GetVar = $_GET ;
			else
				$GetVar = $_POST ;

			return $GetVar ;
		}

		//Session Validation
		function validation()
		{
			if(ereg("admin/" , $_SERVER['PHP_SELF']))
			{
				if(isset($_SESSION['admin_id']))
					$sessionset = true;
				else
					$sessionset = false;
			}
			else
			{
				if(isset($_SESSION['uid']))
					$sessionset = true;
				else
					$sessionset = false;
			}
			return $sessionset;
		}
		//Logout Session
		function logoutadmin()
		{
			 session_destroy();
			 echo "<script>window.location='login.php'</script>";
		}
	}
?>