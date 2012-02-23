<?php
	/*
	 * Cache.inc.php
	 *
	 * Create a Data Cache.
	 *
	 * Copyright (C) 2010-2020 Indosoft Inc.
	 *
	 * Suresh Shinde <sureshinde@inbox.com>
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


	class Cache
	{

		/**
		 * Cache TTL.
		 *
		 * @var string ttl
		 */
		var $ttl;

		/**
		 * Cache Directory.
		 *
		 * @var string cache_dir
		 */
		var $cache_dir = '';

		/**
		 * Cache File Prefix.
		 *
		 * @var string prefix
		 */
		var $prefix = "response_";

		/**
		 * Cache File Suffix.
		 *
		 * @var string prefix
		 */
		var $suffix = ".cache";

		/**
		 * Cache Current Timestamp.
		 *
		 * @var int current_timestamp
		 */
		var $current_timestamp = 0;

		/**
		 * Cache File Created Timestamp.
		 *
		 * @var int created_timestamp
		 */
		var $created_timestamp = 0;

		/**
		 * Cache File Expiry Timestamp.
		 *
		 * @var int expiry_timestamp
		 */
		var $expiry_timestamp = 0;

		/**
		 * Cache File Resource.
		 *
		 * @var resource file
		 */
		var $file;

		/**
		 * Cache File Name.
		 *
		 * @var string file_name
		 */
		var $file_name;

		/**
		 * Cache File Path.
		 *
		 * @var string file_path
		 */
		var $file_path;

		/**
		 * Cache Request.
		 *
		 * @var string request
		 */
		var $request;

		/**
		 * Cache Response.
		 *
		 * @var string response
		 */
		var $response;

		/**
		 * Cache Request Checksum.
		 *
		 * @var string checksum
		 */
		var $checksum;

		public function __construct($cache_dir = "", $ttl = "+5 hours", $prefix = "response", $suffix = ".cache")
		{
			if(!empty($cache_dir))
			{
				return $this->init($cache_dir, $ttl, $prefix, $suffix);
			}
			return true;
		}

		public function init($cache_dir, $ttl = "+5 hours", $prefix = "response", $suffix = ".cache")
		{
			if(!file_exists($cache_dir))
			{
				if(!mkdir($cache_dir,0777,true))
					return false;
			}

			$this->cache_dir = $cache_dir;
			$this->ttl	= $ttl;
			$this->prefix	= $prefix;
			$this->suffix	= $suffix;

			$this->current_timestamp = strtotime("NOW");
			$this->expiry_timestamp = strtotime(str_replace("+","-",$ttl));
		}

		public function add($request = "",$response = "")
		{
			if(!empty($request) && !empty($response))
			{
				$this->request 	= $request;
				$this->response = $response;
				$this->checksum = crc32($this->request);
				$this->file_name = $this->prefix."#".$this->checksum.$this->suffix;
				$this->file_path = $this->cache_dir.$this->file_name;

				if($this->file = file_put_contents($this->file_path,$response))
					return true;
				else
					return false;
			}
			return false;
		}

		public function update($request = "",$response = "")
		{
			return $this->add($request,$response);;
		}

		public function delete($request = "")
		{
			if(!empty($request))
			{
				$this->request 	= $request;
				$this->checksum = crc32($this->request);
				$this->file_name = $this->prefix."#".$this->checksum.$this->suffix;
				$this->file_path = $this->cache_dir.$this->file_name;

				if(file_exists($this->file_path))
				{
					return unlink($this->file_path);
				}
				return true;
			}
			return false;
		}

		public function retrive($request = "")
		{
			if(!empty($request))
			{
				$this->request 	= $request;
				$this->checksum = crc32($this->request);
				$this->file_name = $this->prefix."#".$this->checksum.$this->suffix;
				$this->file_path = $this->cache_dir.$this->file_name;

				if(file_exists($this->file_path))
				{
					if(true || filemtime($this->file_path) > $this->expiry_timestamp) // remove true after testing
						return file_get_contents($this->file_path);
				}
			}
			return false;
		}

		public function valid($request = "")
		{
			if(!empty($request))
			{
				$this->request 	= $request;
				$this->checksum = crc32($this->request);
				$this->file_name = $this->prefix."#".$this->checksum.$this->suffix;
				$this->file_path = $this->cache_dir.$this->file_name;

				if(file_exists($this->file_path))
				{
					if(filemtime($this->file_path) > $this->expiry_timestamp) // remove true after testing
						return true;
				}
			}
			return false;
		}
	}

?>