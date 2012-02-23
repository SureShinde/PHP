<?
	/*
	 * DataLog.inc.php
	 *
	 * Create a DATA Logs.
	 *
	 * Copyright (C) 2010-2020 Indosoft Inc.
	 *
	 * Atul Ingale
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

	class DataLog {
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
		 * Log Code.
		 *
		 * @var string code or identifier of activity
		 */
		var $code;

		/**
		 * Log Data.
		 *
		 * @var string data related to activity
		 */
		var $data;

		public function __construct($log_dir = "", $prefix = "data_log", $suffix = ".log")
		{
			if(!empty($log_dir))
			{
				return $this->init($log_dir, $prefix, $suffix);
			}
			return true;
		}

		public function init($log_dir, $prefix = "data_log", $suffix = ".log")
		{
			if(!file_exists($log_dir))
			{
				if(!mkdir($log_dir,0777,true))
					return false;
			}

			$this->log_dir 	= $log_dir;
			$this->prefix	= $prefix;
			$this->suffix	= $suffix;
			$this->file_name = $this->prefix."#".date("Y-m-d").$this->suffix;
			$this->file_path = $this->log_dir.$this->file_name;

			if(!$this->file)
				$this->open();
		}
		
		function createDataString($data=array())
		{
			$string = '';
			foreach($data as $key=>$value)
			{
				$string .= '"'.$key.' = '.addslashes($value).'"; ';
			}
			
			$this->data = $string; 
		}
		
		public function add($code = "",$data = array())
		{
			if(!empty($code) && !empty($data))
			{
				$this->code 	= $code;
				$this->data 	= $data;
				$this->createDataString($data);
				
				$dataMsg = "[".date("Y-m-d")."] [".date("H:i:s")."]  [".$this->code."] [".$this->data."]\n";

				if(fwrite($this->file,$dataMsg))
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
