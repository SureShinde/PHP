<?php
	/*
	 * SOAP.inc.php
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

	class SOAP
	{
		private $curl;              // cURL object

		private $url;               // URL of the SOAP
		private $xml;               // XML name for SOAP

		private $options;			// cURL Options
		private $data;              // Data for SOAP
		private $response;          // Data for SOAP

		public $template;			// SOAP Template
		private $parser;			// SOAP Parser

		public $error_code;         // Error code returned as an int
		public $error_string;       // Error message returned as a string
		public $info;               // Returned after request (elapsed time, etc)

		function __construct() {

			require_once("cURL.inc.php");
			require_once("Template.inc.php");

			$this->template = new Template();

			$this->template->init(dirname(__FILE__).'/xml/');
		}

		public function setup($url,$template,$details = array(),$headers = array())
		{
			if(empty($url))
				return false;

			$this->url = $url;

			$this->curl = new cURL($url);

			return $this->load($template,$details,$headers);
		}

		public function fetch($parse = true)
		{
			if($parse)
				return $this->parse();
			else
				return $this->response;
		}

		private function load($template,$details = array(),$headers)
		{
			$this->data = $this->template->process($template, $details);

			$this->options = array(
								   CURLOPT_HEADER 			=> 0,
								   CURLOPT_FRESH_CONNECT 	=> true,
								   CURLOPT_HTTPHEADER 		=> array_merge(array("Content-Type: text/xml;charset: utf-8", "Content-length: ".strlen($this->data)),$headers)
								);

			$this->response = $this->curl->post($this->url, $this->data, $this->options);

			if(isset($_GET['debug']))
			{
				echo "<pre>\n".$this->data."\n".$this->response."</pre>";die;
			}
			return $this->response;
		}

		private function parse($response = NULL)
		{
			if($response != NULL)
				$this->response = $response;

			$this->parser = xml_parser_create();

			xml_parse_into_struct($this->parser,$this->response,$values);

			xml_parser_free($this->parser);

			return $values;
		}

		public function getData()
		{
			return $this->data;
		}

		public function getResponse()
		{
			return $this->response;
		}

	}
	// END SOAP Class
