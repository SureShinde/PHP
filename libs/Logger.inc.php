<?php
	/*
	 * Logger.inc.php
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
	
	class Logger 
	{
		/**
		 * Log Directory.
		 *
		 * @var string log_dir
		 */
		var $log_dir = '';
	
		/**
		 * Log File Prefix.
		 *
		 * @var string prefix
		 */
		var $prefix = "log_";
	
		/**
		 * Log File Suffix.
		 *
		 * @var string prefix
		 */
		var $suffix = ".log";
	
		/**
		 * Log File Resource.
		 *
		 * @var resource file
		 */
		var $file;
	
		/**
		 * Log File Name.
		 *
		 * @var string file_name
		 */
		var $file_name;
	
		/**
		 * Log File Path.
		 *
		 * @var string file_path
		 */
		var $file_path;
	
		/**
		 * Log Data.
		 *
		 * @var string data
		 */
		var $data;
	
		/**
		 * Log Response.
		 *
		 * @var string response
		 */
		var $type;
	
		public function __construct($log_dir = "", $prefix = "log", $suffix = ".log")
		{
			if(!empty($log_dir))
			{
				return $this->init($log_dir, $prefix, $suffix);
			}
			return true;
		}
	
		public function init($log_dir, $prefix = "log", $suffix = ".log")
		{
			if(!file_exists($log_dir))
			{
				if(!mkdir($log_dir,0777,true))
				return false;
			}
	
			$this->log_dir = $log_dir;
			$this->prefix	= $prefix;
			$this->suffix	= $suffix;
			$this->file_name = $this->prefix."#".date("Y-m-d").$this->suffix;
			$this->file_path = $this->log_dir.$this->file_name;
	
			if(!$this->file)
				$this->open();
		}
	
		public function add($data = "", $type = 'PHP')
		{
			$ip = "[".$_SERVER["REMOTE_ADDR"]."]";
			$ip = str_pad($ip,17," ",STR_PAD_RIGHT);
			$this->data 	= $data;

			$logData= $ip."[".date("H:i:s")."] [{$type}] [".trim($data)."]\n";
			
			if(fwrite($this->file,$logData))
				return true;
			else
				return false;
		}
	
		public function push($type = '',$message = "")
		{
			if(!empty($message))
			{
				$ip = "[".$_SERVER["REMOTE_ADDR"]."]";
				$ip = str_pad($ip,17," ",STR_PAD_RIGHT);
				$type = strtoupper($type);
				$message = $ip."[".date("Y-m-d")."] [".date("H:i:s")."] [$type]    [$message]\n";
	
				if(fwrite($this->file,$message))
					return true;
				else
					return false;
			}
			return false;
		}
	
		private function open()
		{
			$this->file = fopen($this->file_path, 'a+') or exit("Can't open {$this->file_path}!");
		}
	
		private function close()
		{
			fclose($this->file);
		}
	}
?>