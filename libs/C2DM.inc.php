<?php
	/*
	 * C2DM.inc.php
	 * 
	 * Android Cloud to Device Messaging Service Library
	 *
	 * Created By : sureshinde
	 *
	 * Created On : Sep 07, 2011 11:14:42 PM
	 *
	 * Created Under : Benchmark Catalog
	 * 
	 * Last Stable : 6135,6370
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

	class C2DM 
	{
		private $source;
		private $authToken;
		
		private $username;
		private $password;
	
		public function __construct() {
	
		}
	
		public function setSource($s) {
			$this->source = $s;
		}
	
		public function setUsername($u) {
			$this->username = $u;
		}
	
		public function setPassword($p) {
			$this->password = $p;
		}
	
		public function setAuthToken($a) {
			$this->authToken = $a;
		}
	
		public function getAuthToken() {
			if($this->authToken) {
				return $this->authToken;
			} else {
				return $this->googleAuthenticate();
			}
		}
	
		/**
		 * Get Google login auth token
		 * @see http://code.google.com/apis/accounts/docs/AuthForInstalledApps.html
		 */
		public function googleAuthenticate($username = NULL, $password = NULL) {
			
			if($username) $this->username = $username;
			if($password) $this->password = $password;
	
			// Initialize the curl object
			$curl = curl_init();
	
			curl_setopt($curl, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	
			$data = array(
				'Email'         => $this->username,
				'Passwd'        => $this->password,
				'accountType'   => 'HOSTED_OR_GOOGLE',
				'source'        => $this->source,
				'service'       => 'ac2dm'
			);
	
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	
			$response = curl_exec($curl);
			curl_close($curl);
	
			// Get the Auth string
			preg_match("/Auth=([a-z0-9_\-]+)/i", $response, $matches);
			$this->authToken = $matches[1];
			
			return $this->authToken;
		}
	
		/**
		 * Send HTTP POST data form
		 */
		function send($deviceRegistrationId, $msg) {
			$headers[] = 'Authorization: GoogleLogin auth='.$this->authToken;
			$data = array(
				'registration_id' => $deviceRegistrationId,
				'collapse_key' => 1,
				'data.message' => $msg //TODO: Add your data here.
			);
	
			$curl = curl_init();
			  
			curl_setopt($curl, CURLOPT_URL, "https://android.apis.google.com/c2dm/send");
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	
			$response = curl_exec($curl);
			curl_close($curl);
	
			return $response;
		}
	}
?>