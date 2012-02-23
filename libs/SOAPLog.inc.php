<?
	/*
	 * SOAPLog.inc.php
	 *
	 * Create a SOAP Logs.
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

	class SOAPLog {
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
		 * Log Request.
		 *
		 * @var string request
		 */
		var $request;

		/**
		 * Log Response.
		 *
		 * @var string response
		 */
		var $response;

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

		public function add($request = "",$response = "",$suppress = '')
		{
			if(!empty($request) && !empty($response))
			{
				$ip = "[".$_SERVER["REMOTE_ADDR"]."]";
				$ip = str_pad($ip,17," ",STR_PAD_RIGHT);
				$this->request 	= preg_replace('~\s*(<([^>]*)>[^<]*</\2>|<[^>]*>)\s*~','$1',$request);
				$this->response = preg_replace('~\s*(<([^>]*)>[^<]*</\2>|<[^>]*>)\s*~','$1',$response);

				preg_match('/<soap:Body>(.*)?<\/soap:Body>/i', $this->request, $matchedRequest);
				preg_match('/(<)(\w+)/', $matchedRequest[1], $matchedRequestTag);

				$requestMsg = $ip."[".date("Y-m-d")."] [".date("H:i:s")."] [REQUEST]  [".$matchedRequestTag[2]."] [".trim($matchedRequest[1])."]\n";

				preg_match('/<soap:Body>(.*)?<\/soap:Body>/i', $this->response, $matchedResponse);
				preg_match('/(<)(\w+)/', $matchedResponse[1], $matchedResponseTag);

				if(empty($suppress))
					$responseMsg = $ip."[".date("Y-m-d")."] [".date("H:i:s")."] [RESPONSE] [".$matchedResponseTag[2]."] [".trim($matchedResponse[1])."]\n";
				else
				{
					preg_match("/<$suppress(.*)<\/$suppress>/i", $matchedResponse[1], $matchedSuppress);
					$suppress = strtoupper($suppress);
					$matchedResponse[1] = str_replace($matchedSuppress[1],">".$suppress,$matchedResponse[1]);

					$responseMsg = $ip."[".date("Y-m-d")."] [".date("H:i:s")."] [RESPONSE] [".$matchedResponseTag[2]."] [".trim($matchedResponse[1])."]\n";
				}

				if(fwrite($this->file,$requestMsg) && fwrite($this->file,$responseMsg))
					return true;
				else
					return false;
			}
			else if(!empty($request))
			{
					$ip = "[".$_SERVER["REMOTE_ADDR"]."]";
					$ip = str_pad($ip,17," ",STR_PAD_RIGHT);
					$this->request 	= preg_replace('~\s*(<([^>]*)>[^<]*</\2>|<[^>]*>)\s*~','$1',$request);
					$this->response = "";

					preg_match('/<soap:Body>(.*)?<\/soap:Body>/i', $this->request, $matchedRequest);
					preg_match('/(<)(\w+)/', $matchedRequest[1], $matchedRequestTag);

					$requestMsg = $ip."[".date("Y-m-d")."] [".date("H:i:s")."] [REQUEST]  [".$matchedRequestTag[2]."] [".trim($matchedRequest[1])."]\n";

					preg_match('/<soap:Body>(.*)?<\/soap:Body>/i', $this->response, $matchedResponse);
					preg_match('/(<)(\w+)/', $matchedResponse[1], $matchedResponseTag);

					$responseMsg = $ip."[".date("Y-m-d")."] [".date("H:i:s")."] [RESPONSE] [BLANK]\n";

					if(fwrite($this->file,$requestMsg) && fwrite($this->file,$responseMsg))
						return true;
					else
						return false;
			}
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
