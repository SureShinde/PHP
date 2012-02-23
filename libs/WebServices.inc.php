<?php

	/*
	 * WebServices.inc.php
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

	@session_start();

	include_once(dirname(__FILE__).'/Utils.inc.php');
	include_once(dirname(__FILE__).'/XMLParser.inc.php');
	include_once(dirname(__FILE__).'/Logger.inc.php');
	include_once(dirname(__FILE__).'/MySQL.inc.php');
	
	/**
	 * OCatalog - Web Services Base Implementation
	 * @author sureshinde
	 * @package OCatalog
	 * @version 2.1
	 */
	class WebServices
	{
		var $utils;

		var $framework;

		var $parser;

		var $xml;

		var $defs;

		var $table_name;
		var $emailid;
		var $subject;
		var $headers;

		var $database;
		
		var $table_prefix 	= '';

		var $database_name 	= '';
		var $database_host 	= '';
		var $database_user 	= '';
		var $database_pass 	= '';

		var $soap;

		var $soap_session;

		var $soap_url 	= '';
		var $soap_username 	= '';
		var $soap_password 	= '';

		var $userPostedData 		= array();
		var $WBtableArray 			= array();
		var $tablesArray 			= array();
		var $joinkeyArray 			= array();
		var $tableAliasArray 		= array();
		var $configcolArray 		= array();
		var $configtblArray			= array();
		var $singleTblColArray		= array();
		var $totalColumnsArray		= array();
		var $appSettingsColArray	= array();

		var $columnlist;
		var $tableJoinStr;
		var $webserviceTable;

		var $condition;
		var $dateaddedcondition;
		var $datemodifiedcondition;
		var $orderbycondition;
		var $dateavailablecondition;
		var $productidcondition;
		var $categoryidcondition;
		var $deviceidcondition;
		var $groupbycondition;
		var $namesearchcond;
		var $metakeysearchcond;
		
		var $get;
		var $post;
		var $request;
		var $files;
		var $server;
		
		var $config;
		var $settings;
		
		var $no_record;
		
		var $tableMap;

		function __construct()
		{
			if (!isset($this->utils))
			{
				$this->utils = new Utils();
			}
			
			$this->tableMap = array(
				'settings' 	=> 'oc_settings',
				'category_settings' => 'oc_settings_category',
				'product_settings' 	=> 'oc_settings_product',
				'frames' 	=> 'oc_frames',
				'announcements' 	=> 'oc_announcements',
				'devices' 	=> 'oc_devices',
				'product_featured' 	=> 'product_featured',
				'synchronise' 	=> 'oc_synchronise',
				
			);
			
			$this->no_record = 'No Record Found';
			
			$this->get 		= (object) $_GET;
			$this->post 	= (object) $_POST;
			$this->request 	= (object) $_REQUEST;
			$this->files 	= (object) $_FILES;
			$this->server	= $_SERVER;
			
			$this->api_key	= @$this->request->key;
			
			$this->fillDefs();

			$this->db = new MySQLConnection($this->database_host,$this->database_user,$this->database_pass,$this->database_name);
			
			$this->store_id	= $this->getStoreId($this->api_key);

			$this->language_id	= $this->getLanguageId(@$this->request->locale);
						
			$this->initAppSettings();
			$this->initCategorySettings();
			$this->initProductSettings();
			$this->initFrames();
			$this->initAnnouncements();
			$this->initDevices();
			$this->initSynchronise();
			
			$this->settings = $this->loadSettings();
		}	

		function getStoreId($api_key = NULL)
		{
			if (isset($this->request->store_id) && $this->request->store_id != '') {
				return $this->request->store_id;
			} else if (!empty($api_key)) {
				$table = $this->table_prefix.$this->tableMap['settings'];			
				$where_clause = " WHERE api_key='".$api_key."'";				
				$sql = "SELECT store_id FROM {$table} {$where_clause} ORDER BY store_id LIMIT 1";
				$result = $this->db->query($sql);
				return $result->row['store_id'];
			} else {
				return 0;
			}
		}	
		function getLanguageId($locale = NULL)
		{			
			// TODO : To be implemented in Childrens
		}
		
		/**
		 * COMMON CODE STARTS
		 */
		
		/**
		 * getValue()
		 * 
		 * Get value of HTTP parameter
		 * 
		 * @param string $var
		 * @return string|NULL|boolean
		 */
		function getValue($var = NULL)
		{
			if (!empty($var)) return (isset($_POST[$var]) ? $_POST[$var] : (isset($_GET[$var]) ? $_GET[$var] : NULL));
			
			return false;
		}
		
		/**
		 * getStatusMessage()
		 * 
		 * Get Status Messages
		 * 
		 * @return string
		 */
		function getStatusMessage()
		{
			@session_start();
			
			if (isset($_SESSION['StatusMessage']))
			{
				$StatusMessage = $_SESSION['StatusMessage'];
				$_SESSION['StatusMessage'] = '';
				unset($_SESSION['StatusMessage']);
				return $StatusMessage;
			}
			
			return '';
		}
		
		/**
		 * setStatusMessage()
		 * 
		 * Set Status Messages
		 * 
		 * @param string $StatusMessage
		 * @return boolean
		 */
		function setStatusMessage($StatusMessage = '')
		{
			@session_start();
			
			$_SESSION['StatusMessage'] = $StatusMessage;
				
			return true;
		}
		
		/**
		 * renderJSON()
		 * 
		 * Render output in JSON format
		 * 
		 * @param multitype:array $output
		 */
		function renderJSON($output = array('hasErrors' => 'true','errors' => 'ACCES DENIED'))
		{
			header('Content-Type: text/plain; charset=utf-8');
			$output = json_encode($output);			
			die($output);
		}

		/**
		 * renderXML()
		 * 
		 * Render output in XML format
		 * 
		 * @param string $output
		 */
		function renderXML($output = '<?xml version="1.0" encoding="UTF-8"?>')
		{
			header('Content-Type: text/xml; charset=utf-8');
			$output = str_replace("&","&amp;",$output);
			die($output);
		}		
		/**
		 * redirect()
		 * 
		 * Redirect to specified URL
		 * 
		 * @param string $url
		 */
		public function redirect($url)
		{
			header('Location: ' . $url);
			exit;
		}

		/**
		 * fillDefs()
		 * 
		 * Fill Variables for our use
		 * 
		 */
		protected function fillDefs()
		{
			$this->WBtableArray 			= $this->defs['WBtablesArray'];
			$this->tablesArray 				= $this->defs['tablesArray'];
			$this->joinkeyArray 			= $this->defs['joinKeyArray'];
			$this->tableAliasArray			= $this->defs['tableAliasArray'];
			$this->configcolArray			= $this->defs['ConfigcolumnArray'];
			$this->configtblArray			= $this->defs['ConfigtableArray'];
			$this->singleTblColArray		= $this->defs['singleTableColArray'];
			$this->appSettingsColArray		= $this->defs['AppSetcolumnArray'];

			$this->condition 				= $this->defs['condition'];
			$this->dateaddedcondition 		= $this->defs['dateaddedcondition'];
			$this->datemodifiedcondition 	= $this->defs['datemodifiedcondition'];
			$this->orderbycondition 		= $this->defs['orderbycondition'];
			$this->dateavailablecondition 	= $this->defs['dateavailablecondition'];
			$this->productidcondition 		= $this->defs['productidcondition'];
			$this->categoryidcondition 		= $this->defs['categoryidcondition'];
			$this->deviceidcondition 		= $this->defs['deviceidcondition'];
			$this->groupbycondition 		= $this->defs['groupbycondition'];
			$this->namesearchcond 			= $this->defs['namesearchcond'];
			$this->metakeysearchcond 		= $this->defs['metakeysearchcond'];

			$this->columnlist 				= $this->getTableFields();
			$this->tableJoinStr				= $this->getTableJoinString();
			$this->totalColumnsArray		= $this->defs['TotalColArray'];//

		   //getting database details fetched from XML
		   
		   	$this->table_prefix 			= $this->configcolArray['table_prefix'];
		   	
			$this->database_name 			= $this->configcolArray['dbname'] ;
			$this->database_host 			= $this->configcolArray['host'] ;
			$this->database_user 			= $this->configcolArray['username'] ;
			$this->database_pass 			= $this->configcolArray['password'] ;

			@define('DIR_PATH', $this->configcolArray['dir_path']);
			@define('HTTP_HOST', $this->configcolArray['http_host']);
			@define('HTTP_PATH', HTTP_HOST.$this->configcolArray['http_path']);
			@define('HTTPS_IMAGE_PATH', $this->configcolArray['https_image_path']);
			@define('DIR_IMAGE', $this->configcolArray['dir_image']);
			@define('HOST_URL', $this->configcolArray['host_url']);
			
			// Added By Suresh On 25/11/2011 For Cart Processing
			@define('FRAMEWORK_DIR', $this->configcolArray['framework_dir']);
			@define('FRAMEWORK_URL', $this->configcolArray['framework_url']);			
		}

		/**
		 * getTableJoinString()
		 * 
		 * Get Table Join string if any
		 * 
		 * @return string
		 */
		function getTableJoinString()
		{
			// TODO: To be implemented in childrens as per requirements
			return "";
		}

		/**
		 * getTableFields()
		 * 
		 * Get Field names in table
		 * 
		 * @return string
		 */
		function getTableFields()
		{
			$columnStr = "";
			$tableArray = $this->WBtableArray;
			for($i = 0; $i < count($tableArray); $i++)
			{
				for($p = 0; $p < count($tableArray[$i]['columnlist']); $p++)
				{
					if ($columnStr == "")
						$columnStr = $tableArray[$i]['tablealias'].".".$tableArray[$i]['columnlist'][$p]." AS ".$tableArray[$i]['alias'][$p];
					else
						$columnStr = $columnStr.",".$tableArray[$i]['tablealias'].".".$tableArray[$i]['columnlist'][$p]." AS ".$tableArray[$i]['alias'][$p];
				}
			}
			return $columnStr;
		}		
		
		/**
		 * initAppSettings()
		 * 
		 * Initialise Application Settings
		 * 
		 * @return boolean
		 */
		public function initAppSettings()
		{			
			$table = $this->table_prefix.$this->tableMap['settings'];
			
			$result = $this->db->query("SHOW TABLES LIKE '{$table}'");
			
			if (!$result->num_rows)
			{
				$createTablesql = 	"CREATE TABLE IF NOT EXISTS `{$table}` (
					  `app_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Application ID',
					  `store_id` int(11) NOT NULL COMMENT 'Store ID',
					  `api_key` varchar(100) DEFAULT NULL COMMENT 'API Key',
					  `app_name` varchar(100) NOT NULL COMMENT 'Application Name',
					  
					  `app_bg` varchar(200) NOT NULL COMMENT 'Application Background Image',
					  `app_logo` varchar(200) NOT NULL COMMENT 'Application Logo',				 
					  `launching_screen` varchar(200) NOT NULL COMMENT 'Application Launching Screen',
					  `no_image` varchar(200) NOT NULL,
					  
					  `fb_app_id` varchar(200) NOT NULL COMMENT 'Facebook Application Id',
					  `fb_api_key` varchar(200) NOT NULL COMMENT 'Facebook API Key',
					  `fb_secret_key` varchar(200) NOT NULL COMMENT 'Facebook Secret Key',
					  
					  `tw_api_key` varchar(200) NOT NULL COMMENT 'Twitter API Key',
					  `tw_secret_key` varchar(200) NOT NULL COMMENT 'Twitter Secret Key',
					  
					  `force_upgrade` varchar(5) NOT NULL COMMENT 'Forceful Upgradation of Application',
					  `latest_version` varchar(11) NOT NULL COMMENT 'Latest Application Version',
					  `shopping_cart` enum('Y','N','W') NOT NULL COMMENT 'Shopping Cart Status',
					  `default_currency` varchar(50) DEFAULT NULL COMMENT 'Default Currency',

					  `date_added` datetime NOT NULL,
					  `date_modified` datetime NOT NULL,
					  PRIMARY KEY (`app_id`)
					) ENGINE=InnoDB  DEFAULT CHARSET=latin1  COMMENT='Application Settings' AUTO_INCREMENT=1
				";
	
				$this->db->query($createTablesql);
			}
			return true;
		}
		
		/**
		 * initCategorySettings()
		 * 
		 * Initialise Category Settings
		 * 
		 * @return boolean
		 */
		function initCategorySettings()
		{			
			$table = $this->table_prefix.$this->tableMap['category_settings'];
			
			$result = $this->db->query("SHOW TABLES LIKE '{$table}'");
			
			if (!$result->num_rows)
			{
				$createTablesql = 	"CREATE TABLE IF NOT EXISTS `{$table}` (
					  `setting_id` int(11) NOT NULL AUTO_INCREMENT,					  
					  `store_id` int(11) NOT NULL COMMENT 'Store ID',
					  `frame_id` int(11) NOT NULL,
					  `title_frame_id` int(11) NOT NULL,
					  `title_color` varchar(20) NOT NULL,
					  `title_color_hex` varchar(20) NOT NULL,	  
					  PRIMARY KEY (`setting_id`)
					) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Category Settings' AUTO_INCREMENT=1
				";

				$this->db->query($createTablesql);		
			}
			return true;	
		}
		
		function initProductSettings()
		{			
			$table = $this->table_prefix.$this->tableMap['product_settings'];
			
			$result = $this->db->query("SHOW TABLES LIKE '{$table}'");
			
			if (!$result->num_rows)
			{
				$createTablesql = "CREATE TABLE IF NOT EXISTS `{$table}` (
					  `setting_id` int(11) NOT NULL AUTO_INCREMENT,					  
					  `store_id` int(11) NOT NULL COMMENT 'Store ID',
					  `frame_id` int(11) NOT NULL,
					  `title_frame_id` int(11) NOT NULL,
					  `title_color` varchar(20) NOT NULL,
					  `title_color_hex` varchar(20) NOT NULL,
					  `price_color` varchar(20) NOT NULL,
					  `price_color_hex` varchar(20) NOT NULL,					
					  PRIMARY KEY (`setting_id`)
					) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Product Settings' AUTO_INCREMENT=1
				";
	
				$this->db->query($createTablesql);
			}
			return true;			
		}
		
		/**
		 * initAnnouncements()
		 * 
		 * Initialise Announcements Settings
		 * 
		 * @return boolean
		 */
		function initAnnouncements()
		{			
			$table = $this->table_prefix.$this->tableMap['announcements'];
			
			$result = $this->db->query("SHOW TABLES LIKE '{$table}'");
			
			if (!$result->num_rows)
			{
				$createTablesql = "CREATE TABLE IF NOT EXISTS `{$table}` (			
					  `id` int(11) NOT NULL AUTO_INCREMENT,					  
					  `store_id` int(11) NOT NULL COMMENT 'Store ID',
					  `frame_id` int(11) NOT NULL,
					  `order_id` int(11) NOT NULL,
					  `product_image` varchar(255) DEFAULT NULL,
					  `product_link` varchar(255) DEFAULT NULL,
					  `status` enum('A','I') DEFAULT NULL,
					  `modified_date` datetime DEFAULT NULL,
					  PRIMARY KEY (`id`)
					) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Hot Deals' AUTO_INCREMENT=1
				";
			
				$this->db->query($createTablesql);
					
				$addTablesql="INSERT INTO {$table}  (`frame_id`, `order_id`, `product_image`, `product_link`, `status`, `modified_date`, `store_id`) VALUES
				(1, 1, '', 'www.ocatalog.com', 'A', '".date("Y-m-d H:i:s")."', 0)";	
				$this->db->query($addTablesql);
			}
			return true;
		}
		
		/**
		 * initFrames()
		 * 
		 * Initialise Featured Frames Settings
		 * 
		 * @return boolean
		 */
		function initFrames()
		{			
			$table = $this->table_prefix.$this->tableMap['frames'];
			
			$result = $this->db->query("SHOW TABLES LIKE '{$table}'");
			
			if (!$result->num_rows)
			{
				$createTablesql = 	"CREATE TABLE IF NOT EXISTS `{$table}` (
					  `frame_id` int(11) NOT NULL AUTO_INCREMENT,
					  `image` varchar(255) DEFAULT NULL,
					  `type` enum('C','T','P','PT','F') DEFAULT NULL,
					  PRIMARY KEY (`frame_id`)
					) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Featured Frames' AUTO_INCREMENT=1
				";
			
				$this->db->query($createTablesql);
				
				$addTablesql="INSERT INTO {$table} (`image`, `type`) VALUES
				('featured-frame.png', 'F'),
				('featured-frame2.png', 'F'),
				('featured-frame3.png', 'F'),
				('category-frame.png', 'C'),
				('category-frame2.png', 'C'),
				('category-frame3.png', 'C'),
				('sub-category-title-bg1.png', 'T'),
				('sub-category-title-bg2.png', 'T'),
				('sub-category-title-bg3.png', 'T'),
				('product_img_bg-horizontal.png', 'P'),
				('product_img_bg-horizontal2.png', 'P'),
				('product_img_bg-horizontal3.png', 'P'),
				('sub-product-title-bg1.png', 'PT'),
				('sub-product-title-bg2.png', 'PT'),
				('sub-product-title-bg3.png', 'PT')";

				$this->db->query($addTablesql);
			}
			return true;
		}
		
		/**
		 * initDevices()
		 * 
		 * Initialise Device Logs
		 * 
		 * @return boolean
		 */
		function initDevices()
		{			
			$table = $this->table_prefix.$this->tableMap['devices'];
			
			$result = $this->db->query("SHOW TABLES LIKE '{$table}'");
			
			if (!$result->num_rows)
			{
				$createTablesql = 	"CREATE TABLE IF NOT EXISTS `{$table}` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `device_id` VARCHAR(200)  NOT NULL COMMENT 'Device ID',
					  `device_type` enum('P','T') NOT NULL COMMENT 'Device Type [P- Phone, T - Tablet]',
					  PRIMARY KEY (`id`),
					  UNIQUE KEY `device_id` (`device_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='List of Devices' AUTO_INCREMENT=1
				";
			
				$this->db->query($createTablesql);
			}
			return true;
		}
		
		/**
		 * initSynchronise()
		 * 
		 * Initialise Synchronise Logs
		 * 
		 * @return boolean
		 */
		function initSynchronise()
		{			
			$table = $this->table_prefix.$this->tableMap['synchronise'];
			
			$result = $this->db->query("SHOW TABLES LIKE '{$table}'");
			if (!$result->num_rows)
			{
				$createTablesql = 	"CREATE TABLE IF NOT EXISTS `{$table}` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `product_ids` VARCHAR(200)  NOT NULL COMMENT 'Product IDs comma seprated',
					  `device_id` VARCHAR(200)  NOT NULL COMMENT 'Device ID',
					  `store_id` int(11) NOT NULL COMMENT 'Store ID',
					  `synch_type` enum('F','M') NOT NULL COMMENT 'Synchronise Type [F- Favorite, M - Mycart]',
					  `synchronise_date` timestamp DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,				
					  PRIMARY KEY (`id`)					
					) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='List of Synchronises' AUTO_INCREMENT=1
				";
				$this->db->query($createTablesql);
			}
			return true;
		}
		
		/**
		 * authorize()
		 * 
		 * Authorize an Application
		 * 
		 * @return boolean
		 */
		function authorize()
		{
			if (defined("OC_AUTHORIZE_APP") && !OC_AUTHORIZE_APP)
				return true;

			if (isset($this->settings->api_key) && strcmp($this->settings->api_key, $this->request->key) == 0)
				return true;
			else
			{
				if (defined("DEFAULT_OUTPUT_FORMAT") && DEFAULT_OUTPUT_FORMAT == 'XML')
				{
					$this->renderXML("<errors>ACCESS DENIED</errors>");
				}
				else
				{
					$this->renderJSON();					
				}
			}
		}
		
		/**
		 * authenticate()
		 * 
		 * Authenticate User
		 * 
		 */
		function authenticate()
		{
			if (!isset($_SESSION['USERNAME']) && !isset($_SESSION['PASSWORD'])){
				header ("Location: login.php");
				exit;
			}
		}
		
		/**
		 * loadSettings()
		 * 
		 * Load Application Settings
		 * 
		 * @return StdClass
		 */
		function loadSettings()
		{
			if (isset($this->store_id))
			$where=" store_id=".$this->store_id;
			else
			$where=" store_id=0";
			 
			$select_query = "SELECT * FROM `".$this->table_prefix.$this->tableMap['settings']."`  WHERE ".$where;
						
			$select_result = $this->db->query($select_query);
		
			return (object) $select_result->row;
		}
		
		/**
		 * saveSettings()
		 * 
		 * Save Application Settings
		 * 
		 * @param StdClass $new_settings
		 * 
		 */
		function saveSettings($new_settings = NULL)
		{
			$table = $this->table_prefix.$this->tableMap['settings'];
			
			if ($new_settings)
				$settings = $new_settings;
			else
				$settings = $this->settings;
			
			if (isset($settings->app_id) && !empty($settings->app_id))
			{
				$settings->date_modified = date("Y-m-d H:i:s");
				$save_query = "UPDATE `{$table}` SET ";
		
				foreach ($settings as $key => $value)
				{
					if ($key != 'app_id')
					$save_query .= "`{$key}` = '{$value}', ";
				}
					
				$save_query = trim($save_query,", ");
				$save_query.=" WHERE app_id=".$settings->app_id;
			}
			else
			{
				if (isset($settings->app_id))
					unset($settings->app_id);
				
				$settings->date_added = date("Y-m-d H:i:s");
				$settings->date_modified = date("Y-m-d H:i:s");
				
				$save_query = "INSERT INTO `{$table}` ";
				$save_query .= "(`".implode("`, `", array_keys((array) $settings))."`) ";
				$save_query .= "VALUES ('".implode("', '", array_values((array) $settings))."')";
			}
		
			$save_result = $this->db->query($save_query);
			
			$this->settings = $this->loadSettings();
		}
		
		/**
		 * getCategorySettings()
		 * 
		 * Get Category Settings
		 * 
		 * @return multitype:array
		 */
		function getCategorySettings()
		{		
			$table = $this->table_prefix.$this->tableMap['category_settings'];
		
			if (isset($this->store_id))
			$where=" store_id=".$this->store_id;
			else
			$where=" store_id=0";
			
			$category_sql = "SELECT * FROM {$table} WHERE ".$where;
				
			$category_result = $this->db->query($category_sql);
		
			return $category_result->row;
		}
				
		/**
		 * updateCategorySettings()
		 * 
		 * Update Category Settings
		 * 
		 * @param multitype:array $data
		 * 
		 * @return boolean
		 */
		function updateCategorySettings($data = array())
		{
			$table = $this->table_prefix.$this->tableMap['category_settings'];
			
			if (count($data))
			{
				$hasRecords = false;
					
				if (isset($this->store_id))
				$where=" store_id=".$this->store_id;
				else
				$where=" store_id=0";	
				$result = $this->db->query("SELECT * FROM {$table} WHERE ".$where);
					
				if ($result->num_rows)
					$hasRecords = true;
				
				if ($hasRecords)
				{						
					$save_query = "UPDATE `{$table}` SET ";
			
					foreach ($data as $key => $value)
					{
						if ($key != 'setting_id')
							$save_query .= "`{$key}` = '{$value}', ";
					}
						
					$save_query = trim($save_query,", ");					
					$save_query.=" WHERE ".$where;			
				}
				else
				{
					$save_query = "INSERT INTO `{$table}` ";
					$save_query .= "(`".implode("`, `", array_keys((array) $data))."`) ";
					$save_query .= "VALUES ('".implode("', '", array_values((array) $data))."')";
				}
				
				$this->saveSettings();
								
				$this->db->query($save_query);
				
				//if ($this->db->countAffected())
					return true;
			}
			
			return false;
		}	
		
		/**
		 * getProductSettings()
		 * 
		 * Get Product Settings
		 * 
		 * @return multitype:array
		 */
		function getProductSettings()
		{		
			$table = $this->table_prefix.$this->tableMap['product_settings'];
			if (isset($this->store_id))
			$where=" store_id=".$this->store_id;
			else
			$where=" store_id=0";	
		
			$product_sql = "SELECT * FROM {$table} WHERE ".$where;
				
			$product_result = $this->db->query($product_sql);
		
			return $product_result->row;
		}
		
		/**
		 * updateProductSettings()
		 * 
		 * Update Product Settings
		 * 
		 * @param multitype:array $data
		 * 
		 * @return boolean
		 */
		function updateProductSettings($data = array())
		{
			$table = $this->table_prefix.$this->tableMap['product_settings'];
			
			if (count($data))
			{
				$hasRecords = false;
				if (isset($this->store_id))
				$where=" store_id=".$this->store_id;
				else
				$where=" store_id=0";	
					
				$result = $this->db->query("SELECT * FROM {$table} WHERE ".$where);
					
				if ($result->num_rows)
					$hasRecords = true;
				
				if ($hasRecords)
				{						
					$save_query = "UPDATE `{$table}` SET ";
			
					foreach ($data as $key => $value)
					{
						if ($key != 'setting_id')
						$save_query .= "`{$key}` = '{$value}', ";
					}
						
					$save_query = trim($save_query,", ");					
					$save_query.=" WHERE ".$where;							
				}
				else
				{
					$save_query = "INSERT INTO `{$table}` ";
					$save_query .= "(`".implode("`, `", array_keys((array) $data))."`) ";
					$save_query .= "VALUES ('".implode("', '", array_values((array) $data))."')";
				}
				
				$this->saveSettings();
								
				$this->db->query($save_query);
				
				//if ($this->db->countAffected())
					return true;
			}
			
			return false;
		}
		
		/**
		 * getFrameImage()
		 * 
		 * Get Frame Image
		 * 
		 * @param int $frame_id
		 */
		function getFrameImage($frame_id = 0)
		{	
			if (!$frame_id)
				return array();
			
			$table = $this->table_prefix.$this->tableMap['frames'];
				
			$frame_sql = "SELECT * FROM {$table} WHERE frame_id = ". $frame_id;
			
			$frame_result = $this->db->query($frame_sql);
		
			return $frame_result->row;
		}
		
		/**
		 * getFrameImages()
		 * 
		 * Get Frame Images by Type
		 * 
		 * @param string $type
		 * 
		 * @return multitype:array
		 */
		function getFrameImages($type = 'F')
		{
			$table = $this->table_prefix.$this->tableMap['frames'];

			$sql = "SELECT * FROM {$table} WHERE type = '{$type}'";

			$result = $this->db->query($sql);

			return $result->rows;
		}
		
		/**
		 * getAnnouncementInfo()
		 * 
		 * Get Featured Products Information
		 * 
		 * @return multitype:array
		 */
		function getAnnouncementInfo()
		{
			$table = $this->table_prefix.$this->tableMap['announcements'];
				
			$select_query = "SELECT * FROM {$table} LIMIT 1";
			
			$result = $this->db->query($select_query);
			
			if ($result->num_rows)
			{
				return $result->row;
			}
			
			return array();
		}
		
		/**
		 * getAnnouncements()
		 * 
		 * Get All Announcements
		 * 
		 * @param string $date
		 * 
		 * @return multitype:array
		 */
		function getAnnouncements($date = NULL, $all = false)
		{		
			$table = $this->table_prefix.$this->tableMap['announcements'];
			
			if (isset($this->store_id))
			  $wherestore=" AND store_id=".$this->store_id;
			  else
			  $wherestore=" AND store_id=0";		
			
			if (!$all)
				$where_clause = " WHERE status = 'A'";
			else
				$where_clause = " WHERE 1";
			
			if ($date)
			{
				$where_clause .= " AND modified_date >= '{$date}'";
			}
	
			$sql = "SELECT * FROM {$table} {$where_clause} {$wherestore} ORDER BY order_id";
	 		//echo $sql;exit;
			$result = $this->db->query($sql);
	
			return $result->rows;
		}	
		
		/**
		 * getSingleAnnouncements()
		 * 
		 * Get Single Hot Deal
		 * 
		 * @return multitype:array
		 */
		function getSingleAnnouncements($id = NULL)
		{			
			$table = $this->table_prefix.$this->tableMap['announcements'];
			
			if ($id)
				$where_clause = "id = {$id}";
			else
				$where_clause = "status ='A' ";
			if (isset($this->store_id))
				$wherestore=" AND store_id=".$this->store_id;
			else
				$wherestore=" AND store_id=0";
			
			$announcement_sql = "SELECT * FROM {$table} WHERE  {$where_clause} {$wherestore} ORDER BY order_id";		

			$announcement_result = $this->db->query($announcement_sql);
		
			return $announcement_result->row;
		}
		
		/**
		 * announcements_save()
		 * 
		 * Add/Update Announcements Info
		 * 
		 * @param multitype:array $data
		 * 
		 * @return boolean
		 */
		function announcements_save($data = array())
		{
			$table = $this->table_prefix.$this->tableMap['announcements'];
			
			if (count($data))
			{			
				if (isset($data['id']) && !empty($data['id']))
				{
					$data['modified_date'] = date("Y-m-d H:i:s");
					
					$save_query = "UPDATE `{$table}` SET ";
			
					foreach ($data as $key => $value)
					{
						if ($key != 'id')
						$save_query .= "`{$key}` = '{$value}', ";
					}
						
					$save_query = trim($save_query,", ");
					
					$save_query .= " WHERE id = {$data['id']}";
				}
				else
				{					
					$data['modified_date'] = date("Y-m-d H:i:s");
					
					$save_query = "INSERT INTO `{$table}` ";
					$save_query .= "(`".implode("`, `", array_keys((array) $data))."`) ";
					$save_query .= "VALUES ('".implode("', '", array_values((array) $data))."')";
				}
								
				$this->db->query($save_query);
				
				if ($this->db->countAffected())
					return true;
			}
			
			return false;
		}
		
		/**
		 * announcements_delete()
		 * 
		 * Delete Hot Deal
		 * 
		 * @param int $id
		 */
		function announcements_delete($id)
		{
			$table = $this->table_prefix.$this->tableMap['announcements'];
			
		    $delete_query =  "DELETE FROM {$table} WHERE id = '".$id."'";
		    
			$this->db->query($delete_query);
		}
		
		/**
		 * logDevice()
		 * 
		 * Log Devices
		 * 
		 * @param string $device_id
		 * @param string $device_type
		 * 
		 * @return boolean
		 */
		function logDevice($device_id = '', $device_type = 'P')
		{
			$table = $this->table_prefix.$this->tableMap['devices'];
			
			if (empty($device_id))
				return false;
			
			if (empty($device_type))
				$device_type = 'P';
			
			$device_type = ($device_type == '1' ? 'T' : 'P');
			
			if (!empty($device_id) && !empty($device_type))
			{
				$select_query = "SELECT * FROM {$table} WHERE `device_id` = '{$device_id}'";
					
				$result = $this->db->query($select_query);
					
				if ($result->num_rows)
				{
					return false;
				}
				
				$save_query = "INSERT INTO `{$table}` ";
				$save_query .= "(`device_id`, `device_type`) ";
				$save_query .= "VALUES ('{$device_id}', '{$device_type}')";
								
				$save_result = $this->db->query($save_query);
			}
			
			return false;
		}
		/**
		 * getSynchronise()
		 * 
		 * Get All Synchronise products
		 * 
		 * @param string $date
		 * 
		 * @return multitype:array
		 */
		function getSynchronise($type,$date = NULL, $device_id = NULL)
		{		
			$tableArray = $this->tablesArray;
			$joinkayArray = $this->joinkeyArray;
			$tblaliasArray = $this->tableAliasArray;
			$table = $this->table_prefix.$this->tableMap['synchronise'];
			$data=array();				
			
			if ($device_id)
				$where_clause = " WHERE device_id = '".$device_id."'";
			else
				$where_clause = " WHERE 1";
			if (isset($this->store_id))
			{
			  $wherestore=" AND {$tblaliasArray[2]}.store_id=".$this->store_id;
			  $where_clause.=" AND store_id=".$this->store_id;
			}
			else
			{
			  $wherestore=" AND {$tblaliasArray[2]}.store_id=0";		
			  $where_clause.=" AND store_id=0";	
			}
			 if (isset($this->language_id))
			  $wherestore.=" AND {$tblaliasArray[1]}.language_id=".$this->language_id;	
			
			if ($date)
			{
				$where_clause .= " AND synchronise_date >= '{$date}'";
			}	
			if ($type)
			{
				$where_clause .= " AND synch_type = '{$type}'";
			}	
				$sql_ids = "SELECT product_ids,device_id FROM {$table} {$where_clause} ORDER BY synchronise_date DESC";
				$result_ids = $this->db->query($sql_ids);
				$ids_array=$result_ids->rows;
				if (count($ids_array) >0)
				{
					for ($i=0;$i<count($ids_array);$i++)
					{				
						$device_id=$ids_array[$i]['device_id'];
						$ids=$ids_array[$i]['product_ids'];
						if ($ids!="")
						{
						$sql = "SELECT {$tblaliasArray[0]}.{$joinkayArray[0]},{$tblaliasArray[0]}.image,{$tblaliasArray[1]}.name FROM {$this->table_prefix}product $tblaliasArray[0] LEFT JOIN {$tableArray[1]} {$tblaliasArray[1]} ON {$tblaliasArray[1]}.{$joinkayArray[0]}={$tblaliasArray[0]}.{$joinkayArray[0]} LEFT JOIN {$tableArray[2]} {$tblaliasArray[2]} ON {$tblaliasArray[2]}.{$joinkayArray[0]}={$tblaliasArray[0]}.{$joinkayArray[0]} WHERE {$tblaliasArray[0]}.{$joinkayArray[0]} IN ({$ids}) AND  status=1   {$wherestore}  ORDER BY product_id DESC";	
					$result = $this->db->query($sql);		
						$data[$i]['device_id']=$device_id;
						$data[$i]['data']=$result->rows;	
				}
		}
		
					return $data;exit;
				}				
			
		}
		function delSynchronise($type,$device_id)
		{		
			$table = $this->table_prefix.$this->tableMap['synchronise'];
			
			$where_clause = " WHERE device_id = '".$device_id."' AND synch_type='".$type."'";
			$sql_ids = "DELETE FROM {$table} {$where_clause}";
			$result_ids = $this->db->query($sql_ids);			
			
		}
		/**
		 * cropCategoryImages()
		 * 
		 * Crop All Category Images Using Filters
		 * Called From Mobile Device
		 * 
		 */
		function cropCategoryImages()
		{
		 	$thumbnail = new Thumbnail();
		 	
			if (!file_exists(DIR_IMAGE)) {
				@mkdir(DIR_IMAGE, 0777);
			}

			// Image Dimenstions
			 $cat_img_tablet=array();
			 $cat_img_phone=array();
			 $cat_img_tab=explode('x',OC_CATEGORY_IMAGE_TABLET);
			 $cat_img_phone=explode('x',OC_CATEGORY_IMAGE_PHONE);
			 
			if (!file_exists(DIR_IMAGE.'category/'.OC_CATEGORY_IMAGE_PHONE)) {
				@mkdir(DIR_IMAGE.'category/', 0777);
				@mkdir(DIR_IMAGE.'category/'.OC_CATEGORY_IMAGE_TABLET, 0777);
				@mkdir(DIR_IMAGE.'category/'.OC_CATEGORY_IMAGE_PHONE, 0777);
			}
			
			$category_info_all = $this->getCategories();

			if ((isset($this->request->date) && $this->request->date != '0') || (isset($this->request->time) && $this->request->time==0))
			{
				$datetime = $this->request->date." ".$this->request->time;
				$date = date('Y-m-d H:i:s', strtotime($datetime));

				$category_info_all = $this->getCategories($date);
			}

			foreach($category_info_all as $category_info_all)
			{
				if ($this->framework=='OpenCart')
				{
					$path = @explode('/',$category_info_all['image']);
					$image=$path[1];
				}
				else
				 	$image = $category_info_all['image'];

				if (!file_exists(DIR_IMAGE.'category/'.$image))
				{
					@copy(HTTPS_IMAGE_PATH.$category_info_all['image'], DIR_IMAGE.'category/'.$image);
				}
				
				if (isset($this->request->type) && $this->request->type=='1')
				{
					if (!file_exists(DIR_IMAGE."category/".OC_CATEGORY_IMAGE_TABLET."/".$image))
					{
						$thumbnail->resizeCopy($image, $cat_img_tab[0], $cat_img_tab[1],'category/','category/'.OC_CATEGORY_IMAGE_TABLET.'/');
					}
				}
				else
				{
					if (!file_exists(DIR_IMAGE."category/".OC_CATEGORY_IMAGE_PHONE."/".$image))
					{
						$thumbnail->resizeCopy($image, $cat_img_phone[0], $cat_img_phone[1],'category/','category/'.OC_CATEGORY_IMAGE_PHONE.'/');
					}
				}
				
				if (file_exists(DIR_IMAGE.'category/'.$image) && $image!="")
				{
				    unlink(DIR_IMAGE.'category/'.$image);
				}
			}	
		}
		
		/**
		 * cropProductImages()
		 * 
		 * Crop All Product Images Using Filters
		 * Called From Mobile Device
		 * 
		 */
		function cropProductImages()
		{
		    $thumbnail = new Thumbnail();
		    
			if (!file_exists(DIR_IMAGE))
			{
				@mkdir(DIR_IMAGE, 0777);

			}
			// Image Dimenstions
			$pro_big_img_tablet=array();
			$pro_big_img_phone=array();
			$pro_small_img_tablet=array();
			$pro_small_img_phone=array();
			
			$pro_big_img_tablet=explode('x',OC_PRODUCT_BIG_IMAGE_TABLET);
			$pro_big_img_phone=explode('x',OC_PRODUCT_BIG_IMAGE_PHONE);
			$pro_small_img_tablet=explode('x',OC_PRODUCT_SMALL_IMAGE_TABLET);
			$pro_small_img_phone=explode('x',OC_PRODUCT_SMALL_IMAGE_PHONE);
			
			if (!file_exists(DIR_IMAGE.'products/')) {
				@mkdir(DIR_IMAGE.'products/', 0777);
				@mkdir(DIR_IMAGE.'products/'.OC_PRODUCT_BIG_IMAGE_TABLET, 0777);
				@mkdir(DIR_IMAGE.'products/'.OC_PRODUCT_SMALL_IMAGE_TABLET, 0777);
				@mkdir(DIR_IMAGE.'products/'.OC_PRODUCT_BIG_IMAGE_PHONE, 0777);
				@mkdir(DIR_IMAGE.'products/'.OC_PRODUCT_SMALL_IMAGE_PHONE, 0777);
			}
			 
		    $category_id 	= @$this->request->path;;
			$device_id 		= @$this->request->device_id;
			$date 	= @$this->request->date;
			$time 	= @$this->request->time;
			$start 	= @$this->request->start;

			if (!isset($this->request->start) || $this->request->start == '')
			{
				$start = 0;
				$end = 24;
			}else{
				$start = $this->request->start;
				$end = 24;
			}
			
			if (isset($this->request->type) && $this->request->type == '1')
			{
				$products_all = $this->getProductsByCategory($category_id,$start,$end);
			}
			else
			{
			 	$products_all = $this->getProductsByCategory($category_id,$start,$end);
			}

			if ($start > 0 and $this->request->date != '0' && $this->request->time != '0')
			{
				$date = $this->request->date." ".$this->request->time;
				if (isset($this->request->type) && $this->request->type=='1')
				{
					$products_all = $this->getProductsByDateCategory($category_id,$date,0,$start);
				}
				else
				{
					$products_all = $this->getProductsByDateCategory($category_id,$date);
				}
			}

			foreach($products_all as $products_all)
			{
			  	$path=@explode('/',$products_all['image']);
			  	
				if (count($path)>1)
					$image=	$path[1];
				else
					$image=	$products_all['image'];
					
				if (!file_exists(DIR_IMAGE.'products/'.$image))
				{
					@copy(HTTPS_IMAGE_PATH.$products_all['image'], DIR_IMAGE.'products/'.$image);
				}
				if (isset($this->request->type) && $this->request->type=='1')
				{
					if (!file_exists(DIR_IMAGE."products/".OC_PRODUCT_BIG_IMAGE_TABLET."/".$image))
			 		{
						$thumbnail->resizeCopy($image,$pro_big_img_tablet[0], $pro_big_img_tablet[1],'products/','products/'.OC_PRODUCT_BIG_IMAGE_TABLET.'/');
					}
					if (!file_exists(DIR_IMAGE."products/".OC_PRODUCT_SMALL_IMAGE_TABLET."/".$image))
			 		{
						$thumbnail->resizeCopy($image, $pro_small_img_tablet[0], $pro_small_img_tablet[1],'products/','products/'.OC_PRODUCT_SMALL_IMAGE_TABLET.'/');
					}
				}
				else
				{
					if (!file_exists(DIR_IMAGE."products/".OC_PRODUCT_BIG_IMAGE_PHONE."/".$image))
		 			{
						$thumbnail->resizeCopy($image,  $pro_big_img_phone[0],  $pro_big_img_phone[1],'products/','products/'.OC_PRODUCT_BIG_IMAGE_PHONE.'/');
					}
					if (!file_exists(DIR_IMAGE."products/".OC_PRODUCT_SMALL_IMAGE_PHONE."/".$image))
		 			{
						$thumbnail->resizeCopy($image, $pro_small_img_phone[0], $pro_small_img_phone[1],'products/','products/'.OC_PRODUCT_SMALL_IMAGE_PHONE.'/');
					}
				}
				if (file_exists(DIR_IMAGE.'products/'.$image) && $image!="")
				{
					unlink(DIR_IMAGE.'products/'.$image);
				}

				$this->cropProductDetailsImages($products_all['product_id']);
			}
		}
		
		/**
		 * cropProductDetailsImages()
		 * 
		 * Crops all Prodcut Details images
		 * 
		 * @param unknown_type $product_id
		 */
		function cropProductDetailsImages($product_id)
		{
		    $thumbnail = new Thumbnail();
		    
			if (!file_exists(DIR_IMAGE))
			{
				@mkdir(DIR_IMAGE, 0777);
			}
			// Image Dimenstions
			 $pro_big_img_tablet=array();
			 $pro_big_img_phone=array();
			 $pro_small_img_tablet=array();
			 $pro_small_img_phone=array();
			
			 $pro_big_img_tablet=explode('x',OC_PRODUCT_BIG_IMAGE_TABLET);
			 $pro_big_img_phone=explode('x',OC_PRODUCT_BIG_IMAGE_PHONE);
			 $pro_small_img_tablet=explode('x',OC_PRODUCT_SMALL_IMAGE_TABLET);
			 $pro_small_img_phone=explode('x',OC_PRODUCT_SMALL_IMAGE_PHONE);
			
			if (!file_exists(DIR_IMAGE.'products/details/')) {
				@mkdir(DIR_IMAGE.'products/details', 0777);
				@mkdir(DIR_IMAGE.'products/details/'.OC_PRODUCT_BIG_IMAGE_TABLET, 0777);
				@mkdir(DIR_IMAGE.'products/details/'.OC_PRODUCT_SMALL_IMAGE_TABLET, 0777);
				@mkdir(DIR_IMAGE.'products/details/'.OC_PRODUCT_BIG_IMAGE_PHONE, 0777);
				@mkdir(DIR_IMAGE.'products/details/'.OC_PRODUCT_SMALL_IMAGE_PHONE, 0777);
			}
			$product_image_info = $this->getProductImages($product_id);				
			if (count($product_image_info) > 0)
			{
				$index = 0;
				foreach($product_image_info as $product_image_info)
				{
	    			$path=@explode('/',$product_image_info['image']);
	    			
					if (count($path)>1)
						$image=$path[1];
					else
					 	$image=$product_image_info['image'];
					 	
					if (!file_exists(DIR_IMAGE.'products/details/'.$image))
					{
						@copy(HTTPS_IMAGE_PATH.$product_image_info['image'], DIR_IMAGE.'products/details/'.$image);
					}
					if (isset($this->request->type) && $this->request->type=='1')
					{
						if (!file_exists(DIR_IMAGE."products/details/".OC_PRODUCT_BIG_IMAGE_TABLET."/".$image))
						{
							$thumbnail->resizeCopy($image,  $pro_big_img_tablet[0],  $pro_big_img_tablet[1],'products/details/','products/details/'.OC_PRODUCT_BIG_IMAGE_TABLET.'/');
						}
						if (!file_exists(DIR_IMAGE."products/details/".OC_PRODUCT_SMALL_IMAGE_TABLET."/".$image))
						{
							$thumbnail->resizeCopy($image,  $pro_small_img_tablet[0],  $pro_small_img_tablet[1],'products/details/','products/details/'.OC_PRODUCT_SMALL_IMAGE_TABLET.'/');
						}
					}
					else
					{
						if (!file_exists(DIR_IMAGE."products/details/".OC_PRODUCT_BIG_IMAGE_PHONE."/".$image))
						{
							$thumbnail->resizeCopy($image,  $pro_big_img_phone[0],  $pro_big_img_phone[1],'products/details/','products/details/'.OC_PRODUCT_BIG_IMAGE_PHONE.'/');
						}
						if (!file_exists(DIR_IMAGE."products/details/".OC_PRODUCT_SMALL_IMAGE_PHONE."/".$image))
						{
							$thumbnail->resizeCopy($image, $pro_small_img_phone[0], $pro_small_img_phone[1],'products/details/','products/details/'.OC_PRODUCT_SMALL_IMAGE_PHONE.'/');
						}
					}
					if (file_exists(DIR_IMAGE.'products/details/'.$image) && $image!="")
					{
						unlink(DIR_IMAGE.'products/details/'.$image);
					}
				}
			}
		}

		/**
		 * genCategoryImages()
		 * 
		 * Generates All Category Images
		 * Called From Browser/AJAX
		 * 
		 */
		function genCategoryImages()
		{
			$thumbnail = new Thumbnail();

			if (!file_exists(DIR_IMAGE))
			{
				@mkdir(DIR_IMAGE, 0777);
			}
			// Image Dimenstions
			 $cat_img_tablet=array();
			 $cat_img_phone=array();
			 $cat_img_tab=explode('x',OC_CATEGORY_IMAGE_TABLET);
			 $cat_img_phone=explode('x',OC_CATEGORY_IMAGE_PHONE);
			 
			if (!file_exists(DIR_IMAGE.'category/'.OC_CATEGORY_IMAGE_PHONE)) {
				@mkdir(DIR_IMAGE.'category/', 0777);
				@mkdir(DIR_IMAGE.'category/'.OC_CATEGORY_IMAGE_TABLET, 0777);
				@mkdir(DIR_IMAGE.'category/'.OC_CATEGORY_IMAGE_PHONE, 0777);
			}

			$category_info_all = $this->getCategories();

			foreach($category_info_all as $category_info_all)
			{
				if ($this->framework=='OpenCart')
				{
					$path=@explode('/',$category_info_all['image']);
					$image=$path[1];
				}
				else
					$image=$category_info_all['image'];

				if (!file_exists(DIR_IMAGE.'category/'.$image))
				{
					@copy(HTTPS_IMAGE_PATH.$category_info_all['image'], DIR_IMAGE.'category/'.$image);
				}
				if (!file_exists(DIR_IMAGE."category/".OC_CATEGORY_IMAGE_TABLET."/".$image))
				{
					$thumbnail->resizeCopy($image, $cat_img_tab[0], $cat_img_tab[1],'category/','category/'.OC_CATEGORY_IMAGE_TABLET.'/');
				}
				if (!file_exists(DIR_IMAGE."category/".OC_CATEGORY_IMAGE_PHONE."/".$image))
				{
					$thumbnail->resizeCopy($image, $cat_img_phone[0], $cat_img_phone[1],'category/','category/'.OC_CATEGORY_IMAGE_PHONE.'/');
				}
				if (file_exists(DIR_IMAGE.'category/'.$image) && $image!="")
				{
				    unlink(DIR_IMAGE.'category/'.$image);
				}
			}		 	

		}
				
		/**
		 * genProductImages()
		 * 
		 * Generates All Product Images
		 * Called From Browser/AJAX
		 * 
		 */
		function genProductImages()
		{
		    $thumbnail = new Thumbnail();
		    
			if (!file_exists(DIR_IMAGE))
			{
				@mkdir(DIR_IMAGE, 0777);
			}
			// Image Dimenstions
			 $pro_big_img_tablet=array();
			 $pro_big_img_phone=array();
			 $pro_small_img_tablet=array();
			 $pro_small_img_phone=array();
			
			 $pro_big_img_tablet=explode('x',OC_PRODUCT_BIG_IMAGE_TABLET);
			 $pro_big_img_phone=explode('x',OC_PRODUCT_BIG_IMAGE_PHONE);
			 $pro_small_img_tablet=explode('x',OC_PRODUCT_SMALL_IMAGE_TABLET);
			 $pro_small_img_phone=explode('x',OC_PRODUCT_SMALL_IMAGE_PHONE);
			 
			
			if (!file_exists(DIR_IMAGE.'products/')) {
				@mkdir(DIR_IMAGE.'products/', 0777);
				@mkdir(DIR_IMAGE.'products/'.OC_PRODUCT_BIG_IMAGE_TABLET, 0777);
				@mkdir(DIR_IMAGE.'products/'.OC_PRODUCT_SMALL_IMAGE_TABLET, 0777);
				@mkdir(DIR_IMAGE.'products/'.OC_PRODUCT_BIG_IMAGE_PHONE, 0777);
				@mkdir(DIR_IMAGE.'products/'.OC_PRODUCT_SMALL_IMAGE_PHONE, 0777);
			}
			
			$products = $this->getAllProducts();

			foreach($products as $products_all)
			{
				$path=@explode('/',$products_all['image']);

				if (count($path)>1)
					$image=	$path[1];
				else
					$image=	$products_all['image'];

				if (!file_exists(DIR_IMAGE.'products/'.$image))
				{
					@copy(HTTPS_IMAGE_PATH.$products_all['image'], DIR_IMAGE.'products/'.$image);
				}
				if (!file_exists(DIR_IMAGE."products/".OC_PRODUCT_BIG_IMAGE_TABLET."/".$image))
				{
					$thumbnail->resizeCopy($image, $pro_big_img_tablet[0], $pro_big_img_tablet[1],'products/','products/'.OC_PRODUCT_BIG_IMAGE_TABLET.'/');
				}
				if (!file_exists(DIR_IMAGE."products/".OC_PRODUCT_SMALL_IMAGE_TABLET."/".$image))
				{
					$thumbnail->resizeCopy($image,  $pro_small_img_tablet[0],  $pro_small_img_tablet[1],'products/','products/'.OC_PRODUCT_SMALL_IMAGE_TABLET.'/');
				}
				if (!file_exists(DIR_IMAGE."products/".OC_PRODUCT_BIG_IMAGE_PHONE."/".$image))
				{
					$thumbnail->resizeCopy($image,  $pro_big_img_phone[0],  $pro_big_img_phone[1],'products/','products/'.OC_PRODUCT_BIG_IMAGE_PHONE.'/');
				}
				if (!file_exists(DIR_IMAGE."products/".OC_PRODUCT_SMALL_IMAGE_PHONE."/".$image))
				{
					$thumbnail->resizeCopy($image, $pro_small_img_phone[0], $pro_small_img_phone[1],'products/','products/'.OC_PRODUCT_SMALL_IMAGE_PHONE.'/');
				}
				if (file_exists(DIR_IMAGE.'products/'.$image) && $image!="")
				{
					@unlink(DIR_IMAGE.'products/'.$image);
				}
			}
		}

		/**
		 * genProductDetailsImages()
		 * 
		 * Generates All Product Details Images
		 * Called From Browser/AJAX
		 * 
		 */
		function genProductDetailsImages()
		{
		    $thumbnail = new Thumbnail();
		    
			if (!file_exists(DIR_IMAGE))
			{
				@mkdir(DIR_IMAGE, 0777);
			}
			
			// Image Dimenstions
			 $pro_big_img_tablet=array();
			 $pro_big_img_phone=array();
			 $pro_small_img_tablet=array();
			 $pro_small_img_phone=array();
			
			 $pro_big_img_tablet=explode('x',OC_PRODUCT_BIG_IMAGE_TABLET);
			 $pro_big_img_phone=explode('x',OC_PRODUCT_BIG_IMAGE_PHONE);
			 $pro_small_img_tablet=explode('x',OC_PRODUCT_SMALL_IMAGE_TABLET);
			 $pro_small_img_phone=explode('x',OC_PRODUCT_SMALL_IMAGE_PHONE);
			
			if (!file_exists(DIR_IMAGE.'products/details/')) {
				@mkdir(DIR_IMAGE.'products/details', 0777);
				@mkdir(DIR_IMAGE.'products/details/'.OC_PRODUCT_BIG_IMAGE_TABLET, 0777);
				@mkdir(DIR_IMAGE.'products/details/'.OC_PRODUCT_SMALL_IMAGE_TABLET, 0777);
				@mkdir(DIR_IMAGE.'products/details/'.OC_PRODUCT_SMALL_IMAGE_TABLET, 0777);
				@mkdir(DIR_IMAGE.'products/details/'.OC_PRODUCT_SMALL_IMAGE_PHONE, 0777);
			}

			$products = $this->getAllProducts();

			//Listing all categories
			foreach($products as $products)
			{
				$product_image_info = $this->getProductImages($products['product_id']);

				if (count($product_image_info) > 0)
				{
					$index = 0;
					foreach($product_image_info as $product_image_info)
					{
						$path=@explode('/',$product_image_info['image']);

						if (count($path)>1)
							$image=$path[1];
						else
							$image=$product_image_info['image'];

						if (!file_exists(DIR_IMAGE.'products/details/'.$image))
						{
							@copy(HTTPS_IMAGE_PATH.$product_image_info['image'], DIR_IMAGE.'products/details/'.$image);
						}
						if (!file_exists(DIR_IMAGE."products/details/".OC_PRODUCT_BIG_IMAGE_TABLET."/".$image))
						{
							$thumbnail->resizeCopy($image,  $pro_big_img_tablet[0],  $pro_big_img_tablet[1],'products/details/','products/details/'.OC_PRODUCT_BIG_IMAGE_TABLET.'/');
						}
						if (!file_exists(DIR_IMAGE."products/details/".OC_PRODUCT_SMALL_IMAGE_TABLET."/".$image))
						{
							$thumbnail->resizeCopy($image,  $pro_small_img_tablet[0],  $pro_small_img_tablet[1],'products/details/','products/details/'.OC_PRODUCT_SMALL_IMAGE_TABLET.'/');
						}
						if (!file_exists(DIR_IMAGE."products/details/".OC_PRODUCT_BIG_IMAGE_PHONE."/".$image))
						{
							$thumbnail->resizeCopy($image,  $pro_big_img_phone[0],  $pro_big_img_phone[1],'products/details/','products/details/'.OC_PRODUCT_BIG_IMAGE_PHONE.'/');
						}
						if (!file_exists(DIR_IMAGE."products/details/".OC_PRODUCT_SMALL_IMAGE_PHONE."/".$image))
						{
							$thumbnail->resizeCopy($image, $pro_small_img_phone[0], $pro_small_img_phone[1],'products/details/','products/details/'.OC_PRODUCT_SMALL_IMAGE_PHONE.'/');
						}

						if (file_exists(DIR_IMAGE.'products/details/'.$image) && $image!="")
						{
							unlink(DIR_IMAGE.'products/details/'.$image);
						}
					}
				}
			}
		}		
		/**
		 * setFrame()
		 * 
		 * Set Category & Product Image Dynamicaly
		 * 
		 * @param int $frame_id
		 * 
		 */
		function setFrame($frame_id)
		{			
			$table = $this->table_prefix.$this->tableMap['announcements'];
			
			 if (isset($this->store_id))
			  $wherestore=" WHERE  store_id=".$this->store_id;
			  else
			  $wherestore=" WHERE store_idstore_id=0";		
			  
			$update_query = "UPDATE `{$table}` SET frame_id = '".$frame_id."' {$wherestore}";
			
			$this->db->query($update_query);			
		}
		
		/**
		 * setOrder()
		 * 
		 * Set announcements Order
		 * 
		 * @param int $counter
		 * @param int $id
		 * 
		 */
		function setOrder($counter,$id)
		{			
			$table = $this->table_prefix.$this->tableMap['announcements'];							
									
			$update_query = "UPDATE {$table}
								SET
									order_id = '".$counter."'
								WHERE id = '".$id."'
								";
			
			$result = $this->db->query($update_query);
		}	
		
		/**
		 * getProductAttributesJSON()
		 * 
		 * Prepare Product Attributes Array
		 * 
		 * @param int $product_id
		 * @return multitype:array
		 */
		public function getProductAttributesJSON($product_id) 
		{
			$product_options = $this->getProductOptions($product_id);
				
			$ProductAttributes = array();
		
			if (count($product_options))
			{
				$ProductAttributes = array();
		
				foreach ($product_options as $product_option) {
						
					$ProductAttribute = array();
		
					$ProductAttribute['id'] = $product_option['attribute_id'];
					$ProductAttribute['name'] = html_entity_decode(addslashes($product_option['name']));
					$ProductAttribute['title'] = html_entity_decode(addslashes($product_option['name']));
					$ProductAttribute['order'] = $product_option['sort_order'];
					$ProductAttribute['type'] = $product_option['type'];
						
					$ProductAttribute['options'] = array();
						
					if (count($product_option['options']))
					{
						foreach ($product_option['options'] as $product_option_value) {
							$ProductAttributeOption['id'] = $product_option_value['option_id'];
							$ProductAttributeOption['attribute_id'] = $product_option['attribute_id'];
							$ProductAttributeOption['name'] = html_entity_decode(addslashes($product_option_value['name']));
							$ProductAttributeOption['value'] = $product_option_value['option_id'];
								
							if (isset($product_option_value['color']) && !empty($product_option_value['color']))
							$ProductAttributeOption['color'] = $product_option_value['color'];
								
							if (isset($product_option_value['image']) && !empty($product_option_value['image']))
							$ProductAttributeOption['image'] = $product_option_value['image'];
								
							$ProductAttributeOption['price'] = number_format($product_option_value['price'], 2, '.', '');
							$ProductAttributeOption['prefix'] = $product_option_value['prefix'];
							$ProductAttributeOption['order'] = $product_option_value['sort_order'];
		
							$ProductAttribute['options'][] = $ProductAttributeOption;
						}
					}
		
					$ProductAttributes[] = $ProductAttribute;
				}
			}
		
			return $ProductAttributes;
		}
		
		/**
		 * getProductAttributesXML()
		 * 
		 * Prepare Product Attributes XML
		 * 
		 * @param int $product_id
		 * @return string
		 */
		public function getProductAttributesXML($product_id) 
		{
			$product_options = $this->getProductOptions($product_id);
		
			$product_options_xml = "";
		
			if (count($product_options))
			{		
				foreach ($product_options as $product_option) {
		
					$product_options_xml .= "<attribute id='".$product_option['attribute_id']."' name='".html_entity_decode(addslashes($product_option['name']))."' title='".html_entity_decode(addslashes($product_option['name']))."' order='".$product_option['sort_order']."' type='".$product_option['type']."'>";
		
					if (count($product_option['options']))
					{
						foreach ($product_option['options'] as $product_option_value) {
							$product_options_xml .= "" .
										"<option" .
											" id='".$product_option_value['option_id']."'" .
											" attribute_id='".$product_option['attribute_id']."'" .
											" name='".html_entity_decode(addslashes($product_option_value['name']))."'" .
											" value='".$product_option_value['option_id']."'" .
											((isset($product_option_value['color']) && !empty($product_option_value['color'])) ? " color='".$product_option_value['color']."'" : "") .
											((isset($product_option_value['image']) && !empty($product_option_value['image'])) ? " image='".$product_option_value['image']."'" : "") .
											" price='".number_format($product_option_value['price'], 2, '.', '')."'" .
											" prefix='".$product_option_value['prefix']."'" .
											" order='".$product_option_value['sort_order']."'>" .
											html_entity_decode($product_option_value['name']).
										"</option>".
									"";
						}
					}
		
					$product_options_xml .= "</attribute>";
				}
			}
		
			return $product_options_xml;
		}
		
		/**
		 * getProductsListJSON()
		 * 
		 * Prepare Products List Array
		 * 
		 * @param multitype:array
		 * @return multitype:array 
		 */
		function getProductsListJSON($products = array(),$display_thumb='true')
		{
			$ProductsList = array();
			
			if (count($products))
			{
				foreach ($products as $product)
				{
					$productInfo = array();
					
					$product_description = html_entity_decode($product['description']);
					$title_val = str_replace("&#39;","'",$product_description);
					$title_val = str_replace("´","'",$title_val);
					$title_val = str_replace("&quot;","\"",$title_val);
					$title_val = str_replace("&amp;",'&',$title_val);
					$title_val = str_replace("&rsquo;","'",$title_val);
					$title_val = str_replace("&lt;",'<',$title_val);
					$title_val = str_replace("&gt;",'>',$title_val);
					$title_val = str_replace("&nbsp;",' ',$title_val);
					$title_val = str_replace("&acute;","'",$title_val);
					$title_val = str_replace("&mdash;","-",$title_val);
					$title_val = str_replace("&#8212;","-",$title_val);
				
					if (!isset($product['product_url']) && empty($product['product_url']))
						$product['product_url']	= $this->getProductURL($product['product_id']);
				
					$productInfo['id'] = $product['product_id'];
					$productInfo['title'] = html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8');
					$productInfo['link'] = $product['product_url'];
					$productInfo['description'] = $title_val;
					$productInfo['brand'] = html_entity_decode($product['manufacturer'], ENT_QUOTES, 'UTF-8');
					$productInfo['condition'] = 'new';
				
					if ($product['image'] != '' || $product['image_id'] != '') 
					{						
						$productInfo['image_link'] = $this->getProductImageURL($product);
						$productInfo['image_link_small'] = $this->getProductImageURL($product,true);
					} 
					else 
					{
						$productInfo['image_link'] = $this->no_record;
						$productInfo['image_link_small'] = $this->no_record;
					}
					$productInfo['mpn'] = $product['model'];
				
					$productInfo['price'] = number_format($product['price'], 2, '.', '');
				
					$productInfo['category_id'] = $product['category_id'];
					$productInfo['quantity'] = $product['quantity'];
					$productInfo['upc'] = $product['model'];
					$productInfo['weight'] = $product['weight'];
					$productInfo['featured_prod'] = '0';
					$productInfo['downloadDate'] = date('Y-m-d');
					$productInfo['downloadTime'] = date('H:i:s');
					if ($display_thumb!='false')
					{
						$product_image_info = $this->getProductImages($product['product_id']);
										
						if (count($product_image_info) > 0)
						{
							$productInfo['thumb_image_link'] = array();
							foreach($product_image_info as $product_image_info)
							{
								$productInfo['thumb_image_link'][] = $this->getProductThumbImageURL($product_image_info);
							}
						}
						else
						{
							$productInfo['thumb_image_link'] = array();
						}
					}
					
					if (isset($product['attributes']) && !empty($product['attributes']))
						$this->product_options = $product['attributes'];
				
					if($product['super_attributes'])
					$productInfo['super_attributes']= $this->getProductSuperAttributesJSON($product['super_attributes']);

					// Product Attributes
					$productInfo['attributes'] = $this->getProductAttributesJSON($product['product_id']);
				
					$ProductsList[] = $productInfo;
				}
			}
			
			return $ProductsList;
		}
		
		/**
		 * getProductsListXML()
		 * 
		 * Prepare Products List XML
		 * 
		 * @param multitype:array $products
		 * @return string
		 */
		function getProductsListXML($products = array(),$display_thumb='true')
		{
			$ProductsListXML = "";
				
			if (count($products))
			{
				foreach ($products as $product)
				{
					$product_description = html_entity_decode($product['description']);
					$title_val = str_replace("&#39;","'",$product_description);
					$title_val = str_replace("´","'",$title_val);
					$title_val = str_replace("&quot;","\"",$title_val);
					$title_val = str_replace("&amp;",'&',$title_val);
					$title_val = str_replace("&rsquo;","'",$title_val);
					$title_val = str_replace("&lt;",'<',$title_val);
					$title_val = str_replace("&gt;",'>',$title_val);
					$title_val = str_replace("&nbsp;",' ',$title_val);
					$title_val = str_replace("&acute;","'",$title_val);
					$title_val = str_replace("&mdash;","-",$title_val);
					$title_val = str_replace("&#8212;","-",$title_val);
					$category_id =$product['category_id'];
					
					if (!isset($product['product_url']) && empty($product['product_url']))
						$product['product_url'] = $this->getProductURL($product['product_id']);
	
					$ProductsListXML .= '<item>';
					$ProductsListXML .= '<title>' . html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8') . '</title>';
					$ProductsListXML .= '<link>'.$product['product_url']. '</link>';
					$ProductsListXML .= '<description><![CDATA[' . $title_val . ']]></description>';
					$ProductsListXML .= '<g:brand>' . html_entity_decode($product['manufacturer'], ENT_QUOTES, 'UTF-8') . '</g:brand>';
					$ProductsListXML .= '<g:condition>new</g:condition>';
					$ProductsListXML .= '<g:id>' . $product['product_id'] . '</g:id>';
	
					if ($product['image'] != '' || $product['image_id'] != '') 
					{
						$ProductsListXML .= '<g:image_link>'.$this->getProductImageURL($product).'</g:image_link>';
						$ProductsListXML .= '<g:image_link_small>'.$this->getProductImageURL($product,true).'</g:image_link_small>';
					} 
					else
					{
						$ProductsListXML .= '<g:image_link>No Record Found</g:image_link>';
						$ProductsListXML .= '<g:image_link_small>No Record Found</g:image_link_small>';
					}
	
					$ProductsListXML .= '<g:mpn>' . $product['model'] . '</g:mpn>';
	
					$ProductsListXML .= '<g:price>' . number_format($product['price'], 2, '.', '') . '</g:price>';
	
					$ProductsListXML .= '<g:product_type>' . $category_id . '</g:product_type>';
					$ProductsListXML .= '<g:quantity>' . $product['quantity'] . '</g:quantity>';
					$ProductsListXML .= '<g:upc>' . $product['model'] . '</g:upc>';
					$ProductsListXML .= '<g:weight>' . $product['weight'] . '</g:weight>';
					$ProductsListXML .= '<featured_prod>0</featured_prod>';
					$ProductsListXML .= '<downloadDate>'.date('Y-m-d').'</downloadDate>';
					$ProductsListXML .= '<downloadTime>'.date('H:i:s').'</downloadTime>';
						
					if ($display_thumb!='false')
					{
						$product_image_info = $this->getProductImages($product['product_id']);
										
						if (count($product_image_info) > 0)
						{
							$index=0;
							foreach($product_image_info as $product_image_info)
							{
								$index++;

								$ProductsListXML .= '<thumb_image_link pid="'.$product['product_id'].'" index="'.$index.'">' . $this->getProductThumbImageURL($product_image_info) . '</thumb_image_link>';								
							}
						}
					}

					if (isset($product['attributes']) && !empty($product['attributes']))
						$this->product_options = $product['attributes'];
					if ($product['super_attributes'])
						$ProductsListXML .= $this->getProductSuperAttributesXML($product['super_attributes']);
					// Product Attributes
					$ProductsListXML .= $this->getProductAttributesXML($product['product_id']);
	
					$ProductsListXML .= '</item>';
				}
			}
				
			return $ProductsListXML;
		}
		
		/**
		 * getStatesXML()
		 * 
		 * Prepare States XML
		 * 
		 * @param int $country_id
		 * @return string
		 */
		function getStatesXML($country_id = 0)
		{
			$statesXML = '<states>';
				
			$states = $this->getStates($country_id);
				
			foreach($states as $state)
			{
				$statesXML .= '<state id="'.$state['id'].'">' .htmlspecialchars($state['name']).'</state>';
			}
				
			$statesXML .= '</states>';
				
			return $statesXML;
		}
			
		/**
		 * displayCategoryListJSON()
		 * 
		 * Display Category List and Application Settings by Filters - JSON Way
		 * 
		 */
		function displayCategoryListJSON()
		{
			$CategoryList = array();
					
			$category_info_all = $this->getCategories();
				
			if ((isset($this->request->date) && $this->request->date != '0') || (isset($this->request->time) && $this->request->time != '0'))
			{
				$datetime = $this->request->date." ".$this->request->time;
				$date = date('Y-m-d H:i:s', strtotime($datetime));
			
				$category_info = $this->getCategories($date);
				$category_count = count($category_info);
			}
			else
			{
				$category_info = $category_info_all;
				$category_count = count($category_info_all);
			}
		
			$announcements = $this->getSingleAnnouncements();
				
			if (count($announcements) && $announcements['frame_id'] != "")
			{
				$frameimage = $this->getFrameImage($announcements['frame_id']);
				$str_path	= explode(".",$frameimage['image']);
				$vertical_image_name	= ($str_path[0]=="featured-frame"? $str_path[0].'1-vertical.'.$str_path[1]:$str_path[0].'-vertical.'.$str_path[1]);		
			}
				
			$cat_setting = $this->getCategorySettings();		
		
			if ($cat_setting['frame_id'] != "")
			{
				$cframeimage = $this->getFrameImage($cat_setting['frame_id']);		
			}
			
			if ($cat_setting['title_frame_id'] != "")
			{
				$tframeimage  = $this->getFrameImage($cat_setting['title_frame_id']);				
			}
				
			$pro_setting = $this->getProductSettings();
				
			if ($pro_setting['frame_id'] != "")
			{
				$pframeimage	= $this->getFrameImage($pro_setting['frame_id']);
				$ppriceframeimage	= $this->getFrameImage($pro_setting['title_frame_id']);
				$pimage_name	= str_replace("horizontal","vertical",$pframeimage['image']);		
			}
			
			if ((isset($this->request->date) && $this->request->date != '0') || (isset($this->request->time) && $this->request->time != '0'))
			{
				$current_datetime = $this->request->date." ".$this->request->time;
				$modified_datetime = $this->settings->date_modified;
				
				$current_timestamp = strtotime($current_datetime);
				$modified_timestamp = strtotime($modified_datetime);
				
				if ($modified_timestamp > $current_timestamp) {
					$CategoryList['settingsUpdated'] = "TRUE";
				} else {
					$CategoryList['settingsUpdated'] = "FALSE";
				}
			}
			else
			{
				$CategoryList['settingsUpdated'] = "TRUE";
			}
			$objFramework = new $this->framework('product');
			$featured_products=$objFramework->getFeaturedProducts('yes');
			if (!isset($_SESSION['featured_products_count'])) {
				$_SESSION['featured_products_update_time'] = date("Y-m-d H:i:s");
				$_SESSION['featured_products_count'] = count($featured_products);
			}
			if ($settingsUpdated == 'FALSE' && isset($_SESSION['featured_products_update_time']) && strtotime($_SESSION['featured_products_update_time']) > $current_timestamp){							
				$settingsUpdated = "TRUE";				
			}
			
			if ($settingsUpdated == 'FALSE' && $this->getCurrencyOption() != $this->settings->default_currency || count($featured_products) != $_SESSION['featured_products_count'])
			{
				if (count($featured_products) != $_SESSION['featured_products_count']){				
					$_SESSION['featured_products_update_time'] = date("Y-m-d H:i:s");	
				}		
				$_SESSION['featured_products_count'] = count($featured_products);
				if ($this->getCurrencyOption() != $this->settings->default_currency) {					
					$this->settings->default_currency = $this->getCurrencyOption();
					$this->saveSettings();
				}
				$settingsUpdated = "TRUE";
			}
			
			$CategoryList['settings'] = array();
			
			if ($CategoryList['settingsUpdated'] == "TRUE")
			{				
				$CategoryList['settings']['title'] = html_entity_decode($this->settings->app_name, ENT_QUOTES, 'UTF-8');
				
				$CategoryList['settings']['FacebookAPIKey'] = $this->settings->fb_api_key;
				$CategoryList['settings']['FacebookAppID'] = $this->settings->fb_app_id;
				$CategoryList['settings']['FacebookSecretKey'] = $this->settings->fb_secret_key;
				
				$CategoryList['settings']['TwitterAPIKey'] 		= $this->settings->tw_api_key;
				$CategoryList['settings']['TwitterSecretKey'] 	= $this->settings->tw_secret_key;
				
				$CategoryList['settings']['ForceUpgrade'] = $this->settings->force_upgrade;
				//if ($this->settings->force_upgrade=='ON')
				$CategoryList['settings']['LatestVersion'] = $this->settings->latest_version;
				$CategoryList['settings']['ShoppingCart'] = $this->settings->shopping_cart;
				$CategoryList['settings']['DefaultCurrency'] = $this->getCurrencyOption();
				
				$file_path_info = explode("/",$this->settings->app_logo);
				$str_path=explode(".",$file_path_info[4]);
				$image_name=$str_path[0];
				
				if (isset($this->request->type) && $this->request->type == '1')
				{
					$image_name.='-'.OC_APP_LOGO_IMAGE_TABLET.'.'.$str_path[1];
				}
				else
				{
					$image_name.='-'.OC_APP_LOGO_IMAGE_PHONE.'.'.$str_path[1];
				}
				
				$CategoryList['settings']['logoImage'] = $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name,$this->settings->app_logo)));
							
				if (!empty($this->settings->launching_screen)) 
				{			
					if (isset ($this->request->type) && $this->request->type == '1')
					{
						$CategoryList['settings']['launching_screen_horizontal_image'] = $this->getAppImageURL(str_replace("bigImage","smallImage",$this->settings->launching_screen));
						$CategoryList['settings']['launching_screen_vertical_image'] =$this->getAppImageURL(str_replace("bigImage","smallImage",str_replace("LaunchScreen_","LaunchScreen_vertical",$this->settings->launching_screen)));
					}
					else
					{
						$CategoryList['settings']['launching_screen_vertical_image'] = $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace("LaunchScreen_","LaunchScreen_ipad",$this->settings->launching_screen)));						
					}
				} 
				else 
				{
					if (isset ($this->request->type) && $this->request->type == '1')
						$CategoryList['settings']['launching_screen_horizontal_image'] = $this->no_record;
					$CategoryList['settings']['launching_screen_vertical_image'] = $this->no_record;
				}
				
				if ($this->settings->app_bg) 
				{
					$file_path_info = explode("/",$this->settings->app_bg);
					$str_path=explode(".",$file_path_info[4]);
					$image_name=$str_path[0];
					
					if (isset($this->request->type) && $this->request->type == '1')
					{
						$image_name.='-'.OC_APP_BG_IMAGE_TABLET.'.'.$str_path[1];
					}
					else
					{
						$image_name.='-'.OC_APP_BG_IMAGE_PHONE.'.'.$str_path[1];
					}
					
					$CategoryList['settings']['app_bg_image_link'] = $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name,$this->settings->app_bg)));
				}
				else 
				{
					$CategoryList['settings']['app_bg_image_link'] = $this->no_record;
				}
				
				if (!empty($this->settings->no_image))
				{
					$file_path_info = explode("/",$this->settings->no_image);
					$str_path = explode(".",$file_path_info[4]);
					$image_name = $str_path[0];
					if (isset($this->request->type) && $this->request->type=='1')
					{
						$image_name1=$image_name.'-'.OC_APP_NO_IMAGE_CATEGORY_TABLET.'.'.$str_path[1];
						$image_name2=$image_name.'-'.OC_APP_NO_IMAGE_PRODUCT_TABLET.'.'.$str_path[1];
						$image_name3=$image_name.'-'.OC_APP_NO_IMAGE_PRODUCT__DETAIL_TABLET.'.'.$str_path[1];
					}
					else
					{
						$image_name1=$image_name.'-'.OC_APP_NO_IMAGE_CATEGORY_PHONE.'.'.$str_path[1];
						$image_name2=$image_name.'-'.OC_APP_NO_IMAGE_PRODUCT_PHONE.'.'.$str_path[1];
						$image_name3=$image_name.'-'.OC_APP_NO_IMAGE_PRODUCT_DETAIL_PHONE.'.'.$str_path[1];
					}
					$CategoryList['settings']['category_no_image'] = $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name1,$this->settings->no_image)));
					$CategoryList['settings']['product_no_image'] = $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name2,$this->settings->no_image)));
					$CategoryList['settings']['product_detail_no_image'] = $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name3,$this->settings->no_image)));
				}			
				
				$CategoryList['settings']['frames'] = array();
			
				if ($frameimage['image'])
				{
					if (isset ($this->request->type) && $this->request->type == '1') {
						$CategoryList['settings']['frames']['featured_horizontal'] = $this->getAppImageURL('upload/frame/ipad/'.$frameimage['image']);
						$CategoryList['settings']['frames']['featured_vertical'] = $this->getAppImageURL('upload/frame/ipad/'.$vertical_image_name);
					}
					else
					{
						$CategoryList['settings']['frames']['featured_vertical'] = $this->getAppImageURL('upload/frame/iphone/'.$frameimage['image']);
					}
				}
				else
				{	if (isset ($this->request->type) && $this->request->type == '1')
						$CategoryList['settings']['frames']['featured_horizontal'] = $this->no_record;
					
					$CategoryList['settings']['frames']['featured_vertical'] = $this->no_record;
					
				}
				
				if ($cframeimage['image'])
				{
					if (isset ($this->request->type) && $this->request->type == '1')
						$CategoryList['settings']['frames']['categary'] = $this->getAppImageURL('upload/frame/ipad/'.$cframeimage['image']);
					else
						$CategoryList['settings']['frames']['categary'] = $this->getAppImageURL('upload/frame/iphone/'.$cframeimage['image']);
						
				}
				else
					$CategoryList['settings']['frames']['categary'] = $this->no_record;
					
				if ($tframeimage['image'])
				{
					if (isset ($this->request->type) && $this->request->type == '1')
						$CategoryList['settings']['frames']['categary_title'] = $this->getAppImageURL('upload/frame/ipad/'.$tframeimage['image']);
					else
						$CategoryList['settings']['frames']['categary_title'] = $this->getAppImageURL('upload/frame/iphone/'.$tframeimage['image']);
				}
				else
					$CategoryList['settings']['frames']['categary_title'] = $this->no_record;
				
				if ($pframeimage['image'])
				{
					if (isset ($this->request->type) && $this->request->type == '1')
					{
						$CategoryList['settings']['frames']['product_horizontal'] = $this->getAppImageURL('upload/frame/ipad/'.$pframeimage['image']);
						$CategoryList['settings']['frames']['product_vertical'] = $this->getAppImageURL('upload/frame/ipad/'.$pimage_name);
						$CategoryList['settings']['frames']['product_price'] = $this->getAppImageURL('upload/frame/ipad/'.$ppriceframeimage['image']);
					}
					else
					{
						$find=array('product_img_bg-horizontal','product_img_bg-horizontal2','product_img_bg-horizontal3');
						$replace=array('products-frame','products-frame2','products-frame3');
						
						$CategoryList['settings']['frames']['product_vertical'] = $this->getAppImageURL('upload/frame/iphone/'.str_replace($find,$replace,$pframeimage['image']));
						$CategoryList['settings']['frames']['product_price'] = $this->getAppImageURL('upload/frame/iphone/'.$ppriceframeimage['image']) ;
					}
				}
				else
				{
					if (isset ($this->request->type) && $this->request->type == '1')
						$CategoryList['settings']['frames']['product_horizontal'] = $this->no_record;
					$CategoryList['settings']['frames']['product_vertical'] = $this->no_record;
					$CategoryList['settings']['frames']['product_price'] = $this->no_record;
				}
				
				if ($cat_setting['title_color'])
					$CategoryList['settings']['category_title_color'] = $cat_setting['title_color'];
				else
					$CategoryList['settings']['category_title_color'] = '0,0,0';
				
				if ($pro_setting['title_color'])
					$CategoryList['settings']['product_title_color'] = $pro_setting['title_color'];
				else
					$CategoryList['settings']['product_title_color'] = '0,0,0';
				
				if ($pro_setting['price_color'])
					$CategoryList['settings']['product_price_color'] = $pro_setting['price_color'];
				else
					$CategoryList['settings']['product_price_color'] = '0,0,0';
					
				if (count($featured_products) > 0)
					$CategoryList['settings']['featured'] = 'Yes';
				else
					$CategoryList['settings']['featured'] = 'No';
							
				$CategoryList['settings']['downloadDate'] = date('Y-m-d');
				$CategoryList['settings']['downloadTime'] = date('H:i:s');	
	
			}
				
			$CategoryList['categoryAll'] = array();
				
			foreach($category_info_all as $category_info_all)
			{
				$product_count_all = $this->getCategoryProductsCount($category_info_all['category_id']);
				
				if ($product_count_all > 0 OR $category_info_all['product_count'] >0)
				{
					$CategoryList['categoryAll'][] = $category_info_all['category_id'];
				}
			}
						
			$CategoryList['categoryAllCount'] = count($CategoryList['categoryAll']);
					
			$CategoryList['categoryInfo'] = array();
			
			$category_count = 0;
			
			if (count($category_info))
			{	
				$CategoryList['categoryInfo'] = array();
				
				foreach($category_info as $category_info_single)
				{
					$categoryInfo = array();
					$product_count=$this->getCategoryProductsCount($category_info_single['category_id']);
					if ($product_count >0 OR $category_info_single['product_count'] > 0)
					{
						$category_count += 1;
						
						$category_id = $category_info_single['category_id'];
						$image = $category_info_single['image'];
						$parent_id = $category_info_single['parent_id'];
						$sort_order = $category_info_single['sort_order'];
						$date_added = $category_info_single['date_added'];
						$date_modified = (isset($category_info_single['date_modified'])) ? $category_info_single['date_modified'] : '';
						$status = (isset($category_info_single['status'])) ? $category_info_single['status'] : '';
						$name = $category_info_single['name'];
						$description = (isset($category_info_single['description'])) ? $category_info_single['description'] : '';
			
						$title_val = str_replace("&#39;","'",$name);
						$title_val = str_replace("&quot;","\"",$title_val);
						$title_val = str_replace("&amp;",'&',$title_val);
						$title_val = str_replace("&lt;",'<',$title_val);
						$title_val = str_replace("&gt;",'>',$title_val);
						$title_val = str_replace("&nbsp;",' ',$title_val);
						$title_val = str_replace("&acute;","'",$title_val);
			
						$dis_val = str_replace("&#39;","'",$description);
						$dis_val = str_replace("&quot;","\"",$dis_val);
						$dis_val = str_replace("&amp;",'&',$dis_val);
						$dis_val = str_replace("&lt;",'<',$dis_val);
						$dis_val = str_replace("&gt;",'>',$dis_val);
						$dis_val = str_replace("&nbsp;",' ',$dis_val);
						$dis_val = str_replace("&acute;","'",$dis_val);
						$dis_val = str_replace("&#39;","'",$dis_val);			
			
						$categoryInfo['categoryId'] = $category_id;
						$categoryInfo['parentId'] 	= $parent_id;
						$categoryInfo['categoryName'] = $title_val;
						$categoryInfo['productCount'] = $category_info_single['product_count'];
						
						if ($image != '')
						{							
							$categoryInfo['categoryImage'] = $this->getCategoryImage($image,(int) $this->request->type);			
						}
						else
						{
							$categoryInfo['categoryImage'] = $this->no_record;
						}
						if ($dis_val!="")
							$categoryInfo['categoryDesc'] = strip_tags($dis_val);
						else
							$categoryInfo['categoryDesc'] = $this->no_record;
						
						$categoryInfo['downloadDate'] = date('Y-m-d');
						$categoryInfo['downloadTime'] = date('H:i:s');			
			
						$CategoryList['categoryInfo'][] = $categoryInfo;
					}
				}				
			}
			
			$CategoryList['categoryInfoCount'] = $category_count;
			
			$this->renderJSON($CategoryList);
		}
		
		/**
		 * displayCategoryListXML()
		 * 
		 * Display Category List and Application Settings by Filters - XML Way
		 * 
		 */
		function displayCategoryListXML()
		{
			$setting_info = (array) $this->settings;
			
			$category_info_all = $this->getCategories();
		
			if ((isset($this->request->date) && $this->request->date != '0') || (isset($this->request->time) && $this->request->time != '0')) {
				$datetime = $this->request->date." ".$this->request->time;
				$date = date('Y-m-d H:i:s', strtotime($datetime));
			
				$category_info = $this->getCategories($date);
				$category_count = count($category_info);
			} else {
				$category_info = $category_info_all;
				$category_count = count($category_info_all);
			}
		
			$announcements = $this->getSingleAnnouncements();
				
			if (count($announcements) && $announcements['frame_id'] != "") {
				$frameimage = $this->getFrameImage($announcements['frame_id']);
				$str_path	= explode(".",$frameimage['image']);
				$image_name	= ($str_path[0]=="featured-frame"? $str_path[0].'1-vertical.'.$str_path[1]:$str_path[0].'-vertical.'.$str_path[1]);		
			}
				
			$cat_setting = $this->getCategorySettings();		
		
			if ($cat_setting['frame_id']!="") {
				$cframeimage = $this->getFrameImage($cat_setting['frame_id']);		
			}
			
			if ($cat_setting['title_frame_id']!="") {
				$tframeimage  = $this->getFrameImage($cat_setting['title_frame_id']);				
			}
				
			$pro_setting = $this->getProductSettings();
				
			if ($pro_setting['frame_id']!="") {
				$pframeimage	= $this->getFrameImage($pro_setting['frame_id']);
				$ppriceframeimage	= $this->getFrameImage($pro_setting['title_frame_id']);
				$pimage_name	= str_replace("horizontal","vertical",$pframeimage['image']);		
			}
			
			$settingsUpdated = "FALSE";
			
			if ((isset($this->request->date) && $this->request->date != '0') || (isset($this->request->time) && $this->request->time != '0')) {
				$current_datetime = $this->request->date." ".$this->request->time;
				$modified_datetime = $this->settings->date_modified;
				
				$current_timestamp = strtotime($current_datetime);
				$modified_timestamp = strtotime($modified_datetime);
				
				if ($modified_timestamp > $current_timestamp) {
					$settingsUpdated = "TRUE";
				}
			} else {
				$settingsUpdated = "TRUE";
			}			
			$objFramework = new $this->framework('product');
			$featured_products=$objFramework->getFeaturedProducts('yes');
			if (!isset($_SESSION['featured_products_count'])) {
				$_SESSION['featured_products_update_time'] = date("Y-m-d H:i:s");
				$_SESSION['featured_products_count'] = count($featured_products);
			}
			if ($settingsUpdated == 'FALSE' && isset($_SESSION['featured_products_update_time']) && strtotime($_SESSION['featured_products_update_time']) > $current_timestamp){							
				$settingsUpdated = "TRUE";				
			}
			
			if ($settingsUpdated == 'FALSE' && $this->getCurrencyOption() != $this->settings->default_currency || count($featured_products) != $_SESSION['featured_products_count'])
			{
				if (count($featured_products) != $_SESSION['featured_products_count']){				
					$_SESSION['featured_products_update_time'] = date("Y-m-d H:i:s");	
				}	
				$_SESSION['featured_products_count'] = count($featured_products);
				if ($this->getCurrencyOption() != $this->settings->default_currency) {					
					$this->settings->default_currency = $this->getCurrencyOption();
					$this->saveSettings();
				}
				$settingsUpdated = "TRUE";
			}
					
			$app_setting_data = (array) $this->settings;
		
			$output = '<MainCategory>';
			$output .= '<MainCategoryAll>';
			foreach($category_info_all as $category_info_all)
			{
				$product_count_all=$this->getCategoryProductsCount($category_info_all['category_id']);
				if ($product_count_all >0 OR $category_info_all['product_count'] >0)
				{
					$output .= '<categoryIdAll>'.$category_info_all['category_id'].'</categoryIdAll>';
				}
			}
			$output .= '</MainCategoryAll>';			
		
			$output .= '<settingsUpdated>'.$settingsUpdated.'</settingsUpdated>';
				
			if ($settingsUpdated == "TRUE")
			{
				$output .= '<settingInfo>';
				$output .= '<Frames>';
				if ($frameimage['image'])
				{
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1') {
						$output .= '<featured_image_horizontal>' . $this->getAppImageURL('upload/frame/ipad/'.$frameimage['image']). '</featured_image_horizontal>';
						$output .= '<featured_image_vertical>' . $this->getAppImageURL('upload/frame/ipad/'.$image_name). '</featured_image_vertical>';
					}
					else
					{
						$output .= '<featured_image_vertical>' . $this->getAppImageURL('upload/frame/iphone/'.$frameimage['image']). '</featured_image_vertical>';
					}
				}
				else
				{	
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1')
						$output .= '<featured_image_horizontal>No Record Found</featured_image_horizontal>';
					$output .= '<featured_image_vertical>No Record Found</featured_image_vertical>';
					
				}
				if ($cframeimage['image'])
				{
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1')
						$output .= '<categary_image>' . $this->getAppImageURL('upload/frame/ipad/'.$cframeimage['image']). '</categary_image>';
					else
						$output .= '<categary_image>' . $this->getAppImageURL('upload/frame/iphone/'.$cframeimage['image']). '</categary_image>';
						
				}
				else
					$output .= '<categary_image>No Record Found</categary_image>';
					
				if ($tframeimage['image'])
				{
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1')
						$output .= '<categary_title_image>' . $this->getAppImageURL('upload/frame/ipad/'.$tframeimage['image']). '</categary_title_image>';
					else
						$output .= '<categary_title_image>' . $this->getAppImageURL('upload/frame/iphone/'.$tframeimage['image']). '</categary_title_image>';
				}
				else
					$output .= '<categary_title_image>No Record Found</categary_title_image>';
				if ($pframeimage['image'])
				{
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1')
					{
						$output .= '<product_image_horizontal>' . $this->getAppImageURL('upload/frame/ipad/'.$pframeimage['image']). '</product_image_horizontal>';
						$output .= '<product_image_vertical>' . $this->getAppImageURL('upload/frame/ipad/'.$pimage_name). '</product_image_vertical>';
						$output .= '<product_price>' . $this->getAppImageURL('upload/frame/ipad/'.$ppriceframeimage['image']). '</product_price>';
					}
					else
					{
						$find=array('product_img_bg-horizontal','product_img_bg-horizontal2','product_img_bg-horizontal3');
						$replace=array('products-frame','products-frame2','products-frame3');
						$output .= '<product_image_vertical>' . $this->getAppImageURL('upload/frame/iphone/'.str_replace($find,$replace,$pframeimage['image'])). '</product_image_vertical>';
						$output .= '<product_price>' . $this->getAppImageURL('upload/frame/iphone/'.$ppriceframeimage['image']). '</product_price>';
					}
				}
				else
				{
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1')
						$output .= '<product_image_horizontal>No Record Found</product_image_horizontal>';
					$output .= '<product_image_vertical>No Record Found</product_image_vertical>';
					$output .= '<product_price>No Record Found</product_price>';
				}
				$output .= '</Frames>';
				if ($cat_setting['title_color'])
					$output .= '<category_title_color>'.$cat_setting['title_color'].'</category_title_color>';
				else
					$output .= '<category_title_color>0,0,0</category_title_color>';
				if ($pro_setting['title_color'])
					$output .= '<product_title_color>'.$pro_setting['title_color'].'</product_title_color>';
				else
					$output .= '<product_title_color>0,0,0</product_title_color>';
				if ($pro_setting['price_color'])
					$output .= '<product_price_color>'.$pro_setting['price_color'].'</product_price_color>';
				else
					$output .= '<product_price_color>0,0,0</product_price_color>';
					
				if (count($featured_products) > 0)
				{
					$output .= '<featured>Yes</featured>';
				}
				else
				{
					$output .= '<featured>No</featured>';
						
				}
						
				$output .= '<title>'.html_entity_decode($setting_info['app_name'], ENT_QUOTES, 'UTF-8').'</title>';
				$output .= '<FacebookAPIKey>'.$setting_info['fb_api_key'].'</FacebookAPIKey>';
				$output .= '<FacebookAppID>'.$setting_info['fb_app_id'].'</FacebookAppID>';
				$output .= '<FacebookSecretKey>'.$setting_info['fb_secret_key'].'</FacebookSecretKey>';
				$output .= '<TwitterAPIKey>'.$setting_info['tw_api_key'].'</TwitterAPIKey>';
				$output .= '<TwitterSecretKey>'.$setting_info['tw_secret_key'].'</TwitterSecretKey>';
				$output .= '<ForceUpgrade>'.$setting_info['force_upgrade'].'</ForceUpgrade>';
				//if ($setting_info['force_upgrade']=='ON')
				$output .= '<LatestVersion>'.$setting_info['latest_version'].'</LatestVersion>';
				$output .= '<ShoppingCart>'.$setting_info['shopping_cart'].'</ShoppingCart>';
				$output .= '<DefaultCurrency>'.$this->getCurrencyOption().'</DefaultCurrency>';
			    $output .= '<GuestCheckout>'.$this->getGuestCheckout().'</GuestCheckout>';
				$file_path_info = explode("/",$setting_info['app_logo']);
				$str_path=explode(".",$file_path_info[4]);
				$image_name=$str_path[0];
				if (isset($_REQUEST['type']) && $_REQUEST['type']=='1')
				{
					$image_name.='-'.OC_APP_LOGO_IMAGE_TABLET.'.'.$str_path[1];
				}
				else
				{
					$image_name.='-'.OC_APP_LOGO_IMAGE_PHONE.'.'.$str_path[1];
				}
				$output .= '<logoImage>'.$this->getAppImageURL(str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name,$setting_info['app_logo']))).'</logoImage>';
						
				if ($setting_info['launching_screen']) 
				{						
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1')
					{
						$output .= '<launching_screen_horizontal_image>' . $this->getAppImageURL(str_replace("bigImage","smallImage",$setting_info['launching_screen']))  . '</launching_screen_horizontal_image>';
						$output .= '<launching_screen_vertical_image>' . $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace("LaunchScreen_","LaunchScreen_vertical",$setting_info['launching_screen'])))  . '</launching_screen_vertical_image>';
					}
					else
					{
						$output .= '<launching_screen_vertical_image>' . $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace("LaunchScreen_","LaunchScreen_phone",$setting_info['launching_screen'])))  . '</launching_screen_vertical_image>';
							
					}
				}
				else
				{
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1')
						$output .= '<launching_screen_horizontal_image>No Record Found</launching_screen_horizontal_image>';
					$output .= '<launching_screen_vertical_image>No Record Found</launching_screen_vertical_image>';
				}
	
				if ($setting_info['app_bg']) 
				{
					$file_path_info = explode("/",$setting_info['app_bg']);
					$str_path=explode(".",$file_path_info[4]);
					$image_name=$str_path[0];
	
					if (isset($_REQUEST['type']) && $_REQUEST['type']=='1')
					{
						$image_name.='-'.OC_APP_BG_IMAGE_TABLET.'.'.$str_path[1];
					}else{
						$image_name.='-'.OC_APP_BG_IMAGE_PHONE.'.'.$str_path[1];
					}
					$output .= '<app_bg_image_link>' .  $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name,$setting_info['app_bg'])))  . '</app_bg_image_link>';
	
				}
				else {
					$output .= '<app_bg_image_link>No Record Found</app_bg_image_link>';
				}
	
	
				if ($app_setting_data['no_image'])
				{
					$file_path_info = explode("/",$app_setting_data['no_image']);
					$str_path=explode(".",$file_path_info[4]);
					$image_name=$str_path[0];
	
					if (isset($_REQUEST['type']) && $_REQUEST['type']=='1')
					{
						$image_name1=$image_name.'-'.OC_APP_NO_IMAGE_CATEGORY_TABLET.'.'.$str_path[1];
						$image_name2=$image_name.'-'.OC_APP_NO_IMAGE_PRODUCT_TABLET.'.'.$str_path[1];
						$image_name3=$image_name.'-'.OC_APP_NO_IMAGE_PRODUCT__DETAIL_TABLET.'.'.$str_path[1];
					}
					else
					{
						$image_name1=$image_name.'-'.OC_APP_NO_IMAGE_CATEGORY_PHONE.'.'.$str_path[1];
						$image_name2=$image_name.'-'.OC_APP_NO_IMAGE_PRODUCT_PHONE.'.'.$str_path[1];
						$image_name3=$image_name.'-'.OC_APP_NO_IMAGE_PRODUCT_DETAIL_PHONE.'.'.$str_path[1];
					}
					$output .= '<category_no_image>'. $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name1,$app_setting_data['no_image']))).'</category_no_image>';
					$output .= '<product_no_image>'. $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name2,$app_setting_data['no_image']))).'</product_no_image>';
					$output .= '<product_detail_no_image>'. $this->getAppImageURL(str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name3,$app_setting_data['no_image']))).'</product_detail_no_image>';
				}
	
				$output .= '<downloadDate>'.date('Y-m-d').'</downloadDate>';
				$output .= '<downloadTime>'.date('H:i:s').'</downloadTime>';
				
				$output .= '</settingInfo>';
			}
			else
			{
				$output .= '<settingInfo>NO RECORD FOUND</settingInfo>';
			}
		
			$output .= '<categoryInfo>';
			$output .= '<categoryCount>#CAT_COUNT#</categoryCount>';
			foreach($category_info as $category_info)
			{
				$product_count=$this->getCategoryProductsCount($category_info['category_id']);
				if ($product_count>0 OR $category_info['product_count'] >0)
				{
					$category_id = $category_info['category_id'];
					$image = $category_info['image'];
					$parent_id = $category_info['parent_id'];
					$sort_order = $category_info['sort_order'];
					$date_added = $category_info['date_added'];
					$date_modified = (isset($category_info['date_modified'])) ? $category_info['date_modified'] : '';
					$status = (isset($category_info['status'])) ? $category_info['status'] : '';
					$name = $category_info['name'];
					$description = (isset($category_info['description'])) ? $category_info['description'] : '';
		
					$title_val = str_replace("&#39;","'",$name);
					$title_val = str_replace("&quot;","\"",$title_val);
					$title_val = str_replace("&amp;",'&',$title_val);
					$title_val = str_replace("&lt;",'<',$title_val);
					$title_val = str_replace("&gt;",'>',$title_val);
					$title_val = str_replace("&nbsp;",' ',$title_val);
					$title_val = str_replace("&acute;","'",$title_val);
		
					$dis_val = str_replace("&#39;","'",$description);
					$dis_val = str_replace("&quot;","\"",$dis_val);
					$dis_val = str_replace("&amp;",'&',$dis_val);
					$dis_val = str_replace("&lt;",'<',$dis_val);
					$dis_val = str_replace("&gt;",'>',$dis_val);
					$dis_val = str_replace("&nbsp;",' ',$dis_val);
					$dis_val = str_replace("&acute;","'",$dis_val);
					$dis_val = str_replace("&#39;","'",$dis_val);
		
		
					$output .= '<category>';
					$output .= '<categoryId>'.$category_id.'</categoryId>';
					$output .= '<parentId>'.$parent_id.'</parentId>';
					$output .= '<categoryName>'.$title_val.'</categoryName>';
					$output .= '<productCount>'.$category_info['product_count'].'</productCount>';
					if ($image!='') {					
						$output .= '<categoryImage>'.$this->getCategoryImage($image,(int) $this->request->type).'</categoryImage>';
		
					} else {
						$output .= '<categoryImage>No Record Found</categoryImage>';
					}
					if ($dis_val!="")
					$output .= '<categoryDesc>'.strip_tags($dis_val).'</categoryDesc>';
					else
					$output .= '<categoryDesc>No Record Found</categoryDesc>';
					$output .= '<downloadDate>'.date('Y-m-d').'</downloadDate>';
					$output .= '<downloadTime>'.date('H:i:s').'</downloadTime>';
		
		
					$output .='</category>';
				}
				else
					$category_count -= 1;
			}
			$output = str_replace("#CAT_COUNT#",$category_count,$output);
			$output .= '</categoryInfo>';
			$output .= '</MainCategory>';
		
			$this->renderXML($output);
		}
		
		/**
		 * displayCategoryListREST()
		 * 
		 * Display Category List and Application Settings by Filters - XML Way
		 * 
		 */
		function displayCategoryListREST()
		{
			$setting_info = (array) $this->settings;
			
			$category_info_all = $this->getCategories();
		
			if ((isset($this->request->date) && $this->request->date != '0') || (isset($this->request->time) && $this->request->time != '0')) {
				$datetime = $this->request->date." ".$this->request->time;
				$date = date('Y-m-d H:i:s', strtotime($datetime));
			
				$category_info = $this->getCategories($date);
				$category_count = count($category_info);
			} else {
				$category_info = $category_info_all;
				$category_count = count($category_info_all);
			}
		
			$announcements = $this->getSingleAnnouncements();
				
			if (count($announcements) && $announcements['frame_id'] != "") {
				$frameimage = $this->getFrameImage($announcements['frame_id']);
				$str_path	= explode(".",$frameimage['image']);
				$image_name	= ($str_path[0]=="featured-frame"? $str_path[0].'1-vertical.'.$str_path[1]:$str_path[0].'-vertical.'.$str_path[1]);		
			}
				
			$cat_setting = $this->getCategorySettings();		
		
			if ($cat_setting['frame_id']!="") {
				$cframeimage = $this->getFrameImage($cat_setting['frame_id']);		
			}
			
			if ($cat_setting['title_frame_id']!="") {
				$tframeimage  = $this->getFrameImage($cat_setting['title_frame_id']);				
			}
				
			$pro_setting = $this->getProductSettings();
				
			if ($pro_setting['frame_id']!="") {
				$pframeimage	= $this->getFrameImage($pro_setting['frame_id']);
				$ppriceframeimage	= $this->getFrameImage($pro_setting['title_frame_id']);
				$pimage_name	= str_replace("horizontal","vertical",$pframeimage['image']);		
			}
			
			$settingsUpdated = "FALSE";
			
			if ((isset($this->request->date) && $this->request->date != '0') || (isset($this->request->time) && $this->request->time != '0')) {
				$current_datetime = $this->request->date." ".$this->request->time;
				$modified_datetime = $this->settings->date_modified;
				
				$current_timestamp = strtotime($current_datetime);
				$modified_timestamp = strtotime($modified_datetime);
				
				if ($modified_timestamp > $current_timestamp) {
					$settingsUpdated = "TRUE";
				}
			} else {
				$settingsUpdated = "TRUE";
			}			
			//print "<pre>"; print_r($this->settings);exit;
			if ($this->getCurrencyOption()!=$this->settings->default_currency) {
				$settingsUpdated = "TRUE";
			}
					
			$app_setting_data = (array) $this->settings;
			
			$doc = new DOMDocument();
			$doc->formatOutput = true;
			$MainCategoryElement = $doc->createElement("MainCategory");
			$doc->appendChild($MainCategoryElement);
			
			$MainCategoryAllElement = $doc->createElement("MainCategoryAll");
			$MainCategoryElement->appendChild($MainCategoryAllElement);
					
			foreach($category_info_all as $category_info_all) {				
				$categoryIdAllElement = $doc->createElement("categoryIdAll");
				$categoryIdAllElement->appendChild($doc->createTextNode($category_info_all['category_id']));
				$MainCategoryAllElement->appendChild($categoryIdAllElement);
			}
			
			$settingsUpdatedElement = $doc->createElement("settingsUpdated");
			$settingsUpdatedElement->appendChild($doc->createTextNode($settingsUpdated));
			$MainCategoryElement->appendChild($settingsUpdatedElement);									
			
				
			if ($settingsUpdated == "TRUE") {
				$settingInfoElement = $doc->createElement("settingInfo");
				$MainCategoryElement->appendChild($settingInfoElement);
				$output .= '<settingInfo>';
				
				$FramesElement = $doc->createElement("Frames");
				$settingInfoElement->appendChild($FramesElement);
				if ($frameimage['image']) {
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1') {
						$featured_image_horizontalElement = $doc->createElement("featured_image_horizontal");
						$featured_image_verticalElement = $doc->createElement("featured_image_vertical");						
						
						$featured_image_horizontalElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/ipad/'.$frameimage['image']));						
						$featured_image_verticalElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/ipad/'.$image_name));
						
						$FramesElement->appendChild($featured_image_horizontalElement);			
						$FramesElement->appendChild($featured_image_verticalElement);			
					}
					else
					{
						$featured_image_verticalElement = $doc->createElement("featured_image_vertical");						
						
						$featured_image_verticalElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/ipad/'.$image_name));
						
						$FramesElement->appendChild($featured_image_verticalElement);
					}
				} else {	
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1')
					{
						$featured_image_horizontalElement = $doc->createElement("featured_image_horizontal");
											
						$featured_image_horizontalElement->appendChild($doc->createTextNode($this->no_record));
						
						$FramesElement->appendChild($featured_image_horizontalElement);
					}
					
					$featured_image_verticalElement = $doc->createElement("featured_image_vertical");
											
					$featured_image_verticalElement->appendChild($doc->createTextNode($this->no_record));
					
					$FramesElement->appendChild($featured_image_verticalElement);
					
				}
				
				if ($cframeimage['image']) {
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1') {
						$categary_imageElement = $doc->createElement("categary_image");
							
						$categary_imageElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/ipad/'.$cframeimage['image']));
							
						$FramesElement->appendChild($categary_imageElement);
					} else {						
						$categary_imageElement = $doc->createElement("categary_image");
							
						$categary_imageElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/iphone/'.$cframeimage['image']));
							
						$FramesElement->appendChild($categary_imageElement);
					}
											
				} else {	
									
					$categary_imageElement = $doc->createElement("categary_image");
												
					$categary_imageElement->appendChild($doc->createTextNode($this->no_record));
						
					$FramesElement->appendChild($categary_imageElement);
					
				}
					
				if ($tframeimage['image']) {
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1') {						
							
						$categary_title_imageElement = $doc->createElement("categary_title_image");
							
						$categary_title_imageElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/ipad/'.$tframeimage['image']));
							
						$FramesElement->appendChild($categary_title_imageElement);
						
					} else {							
						$categary_title_imageElement = $doc->createElement("categary_title_image");
													
						$categary_title_imageElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/iphone/'.$tframeimage['image']));
							
						$FramesElement->appendChild($categary_title_imageElement);
					}
				} else {					
						
					$categary_title_imageElement = $doc->createElement("categary_title_image");
												
					$categary_title_imageElement->appendChild($doc->createTextNode($this->no_record));
						
					$FramesElement->appendChild($categary_title_imageElement);
				}
				
				if ($pframeimage['image'])
				{
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1') {							
						
						$product_image_horizontalElement = $doc->createElement("product_image_horizontal");						
						$product_image_verticalElement = $doc->createElement("product_image_vertical");						
						$product_priceElement = $doc->createElement("product_price");
							
						$product_image_horizontalElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/ipad/'.$pframeimage['image']));							
						$product_image_verticalElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/ipad/'.$pimage_name));							
						$product_priceElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/iphone/'.$ppriceframeimage['image']));
							
						$FramesElement->appendChild($product_image_horizontalElement);							
						$FramesElement->appendChild($product_image_verticalElement);							
						$FramesElement->appendChild($product_priceElement);
					} else {						
						
						$find=array('product_img_bg-horizontal','product_img_bg-horizontal2','product_img_bg-horizontal3');
						$replace=array('products-frame','products-frame2','products-frame3');
						
						$product_image_verticalElement = $doc->createElement("product_image_vertical");						
						$product_priceElement = $doc->createElement("product_price");
							
						$product_image_verticalElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/iphone/'.str_replace($find,$replace,$pframeimage['image'])));							
						$product_priceElement->appendChild($doc->createTextNode(HTTP_PATH.'upload/frame/iphone/'.$ppriceframeimage['image']));
							
						$FramesElement->appendChild($product_image_verticalElement);							
						$FramesElement->appendChild($product_priceElement);
					}
				} else {
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1') {						
							
						$product_image_horizontalElement = $doc->createElement("product_image_horizontal");	
							
						$product_image_horizontalElement->appendChild($doc->createTextNode($this->no_record));
							
						$FramesElement->appendChild($product_image_horizontalElement);
					}
											
					$product_image_verticalElement = $doc->createElement("product_image_vertical");			
					$product_priceElement = $doc->createElement("product_price");
							
					$product_image_verticalElement->appendChild($doc->createTextNode($this->no_record));							
					$product_priceElement->appendChild($doc->createTextNode($this->no_record));
							
					$FramesElement->appendChild($product_image_verticalElement);							
					$FramesElement->appendChild($product_priceElement);
				}
				$this->renderXML($doc->saveXML());
				if ($cat_setting['title_color']) {					
						
					$categary_title_imageElement = $doc->createElement("categary_title_image");
												
					$categary_title_imageElement->appendChild($doc->createTextNode($this->no_record));
						
					$FramesElement->appendChild($categary_title_imageElement);
				}
					//$output .= '<category_title_color>'.$cat_setting['title_color'].'</category_title_color>';
				else {					
						
					$categary_title_imageElement = $doc->createElement("categary_title_image");
												
					$categary_title_imageElement->appendChild($doc->createTextNode($this->no_record));
						
					$FramesElement->appendChild($categary_title_imageElement);
				}
					//$output .= '<category_title_color>0,0,0</category_title_color>';
				if ($pro_setting['title_color'])
					$output .= '<product_title_color>'.$pro_setting['title_color'].'</product_title_color>';
				else
					$output .= '<product_title_color>0,0,0</product_title_color>';
				if ($pro_setting['price_color'])
					$output .= '<product_price_color>'.$pro_setting['price_color'].'</product_price_color>';
				else
					$output .= '<product_price_color>0,0,0</product_price_color>';
					
				$objFramework = new $this->framework('product');
				$featured_prodcuts=$objFramework->getFeaturedProducts();					
				if (count($featured_prodcuts) > 0)
				{
					$output .= '<featured>Yes</featured>';
				}
				else
				{
					$output .= '<featured>No</featured>';
						
				}
						
				$output .= '<title>'.html_entity_decode($setting_info['app_name'], ENT_QUOTES, 'UTF-8').'</title>';
				$output .= '<FacebookAPIKey>'.$setting_info['fb_api_key'].'</FacebookAPIKey>';
				$output .= '<FacebookAppID>'.$setting_info['fb_app_id'].'</FacebookAppID>';
				$output .= '<FacebookSecretKey>'.$setting_info['fb_secret_key'].'</FacebookSecretKey>';
				$output .= '<TwitterAPIKey>'.$setting_info['tw_api_key'].'</TwitterAPIKey>';
				$output .= '<TwitterSecretKey>'.$setting_info['tw_secret_key'].'</TwitterSecretKey>';
				$output .= '<ForceUpgrade>'.$setting_info['force_upgrade'].'</ForceUpgrade>';
				//if ($setting_info['force_upgrade']=='ON')
				$output .= '<LatestVersion>'.$setting_info['latest_version'].'</LatestVersion>';
				$output .= '<ShoppingCart>'.$setting_info['shopping_cart'].'</ShoppingCart>';
				$output .= '<DefaultCurrency>'.$this->getCurrencyOption().'</DefaultCurrency>';
			    $output .= '<GuestCheckout>'.$this->getGuestCheckout().'</GuestCheckout>';
				$file_path_info = explode("/",$setting_info['app_logo']);
				$str_path=explode(".",$file_path_info[4]);
				$image_name=$str_path[0];
				if (isset($_REQUEST['type']) && $_REQUEST['type']=='1')
				{
					$image_name.='-468x61.'.$str_path[1];
				}
				else
				{
					$image_name.='-200x50.'.$str_path[1];
				}
				$output .= '<logoImage>'.HTTP_PATH.str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name,$setting_info['app_logo'])) .'</logoImage>';
						
				if ($setting_info['launching_screen']) 
				{						
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1')
					{
						$output .= '<launching_screen_horizontal_image>' . HTTP_PATH.str_replace("bigImage","smallImage",$setting_info['launching_screen'])  . '</launching_screen_horizontal_image>';
						$output .= '<launching_screen_vertical_image>' . HTTP_PATH.str_replace("bigImage","smallImage",str_replace("LaunchScreen_","LaunchScreen_vertical",$setting_info['launching_screen']))  . '</launching_screen_vertical_image>';
					}
					else
					{
						$output .= '<launching_screen_vertical_image>' . HTTP_PATH.str_replace("bigImage","smallImage",str_replace("LaunchScreen_","LaunchScreen_ipad",$setting_info['launching_screen']))  . '</launching_screen_vertical_image>';
							
					}
				}
				else
				{
					if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1')
						$output .= '<launching_screen_horizontal_image>No Record Found</launching_screen_horizontal_image>';
					$output .= '<launching_screen_vertical_image>No Record Found</launching_screen_vertical_image>';
				}
	
				if ($setting_info['app_bg']) 
				{
					$file_path_info = explode("/",$setting_info['app_bg']);
					$str_path=explode(".",$file_path_info[4]);
					$image_name=$str_path[0];
	
					if (isset($_REQUEST['type']) && $_REQUEST['type']=='1')
					{
						$image_name.='-1024x780.'.$str_path[1];
					}else{
						$image_name.='-320x480.'.$str_path[1];
					}
					$output .= '<app_bg_image_link>' .  HTTP_PATH.str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name,$setting_info['app_bg']))  . '</app_bg_image_link>';
	
				}
				else {
					$output .= '<app_bg_image_link>No Record Found</app_bg_image_link>';
				}
	
	
				if ($app_setting_data['no_image'])
				{
					$file_path_info = explode("/",$app_setting_data['no_image']);
					$str_path=explode(".",$file_path_info[4]);
					$image_name=$str_path[0];
	
					if (isset($_REQUEST['type']) && $_REQUEST['type']=='1')
					{
						$image_name1=$image_name.'-228x147.'.$str_path[1];
						$image_name2=$image_name.'-145x102.'.$str_path[1];
						$image_name3=$image_name.'-990x606.'.$str_path[1];
					}
					else
					{
						$image_name1=$image_name.'-100x100.'.$str_path[1];
						$image_name2=$image_name.'-73x82.'.$str_path[1];
						$image_name3=$image_name.'-301x344.'.$str_path[1];
					}
					$output .= '<category_no_image>'. HTTP_PATH.str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name1,$app_setting_data['no_image'])).'</category_no_image>';
					$output .= '<product_no_image>'. HTTP_PATH.str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name2,$app_setting_data['no_image'])).'</product_no_image>';
					$output .= '<product_detail_no_image>'. HTTP_PATH.str_replace("bigImage","smallImage",str_replace($file_path_info[4],$image_name3,$app_setting_data['no_image'])).'</product_detail_no_image>';
				}
	
				$output .= '<downloadDate>'.date('Y-m-d').'</downloadDate>';
				$output .= '<downloadTime>'.date('H:i:s').'</downloadTime>';
				
				$output .= '</settingInfo>';
			}
			else
			{
				$output .= '<settingInfo>NO RECORD FOUND</settingInfo>';
			}
		
			$output .= '<categoryInfo>';
			$output .= '<categoryCount>#CAT_COUNT#</categoryCount>';
			foreach($category_info as $category_info)
			{
				$product_count=$this->getCategoryProductsCount($category_info['category_id']);
				if ($product_count>0 OR $category_info['product_count'] >0)
				{
					$category_id = $category_info['category_id'];
					$image = $category_info['image'];
					$parent_id = $category_info['parent_id'];
					$sort_order = $category_info['sort_order'];
					$date_added = $category_info['date_added'];
					$date_modified = (isset($category_info['date_modified'])) ? $category_info['date_modified'] : '';
					$status = (isset($category_info['status'])) ? $category_info['status'] : '';
					$name = $category_info['name'];
					$description = (isset($category_info['description'])) ? $category_info['description'] : '';
		
					$title_val = str_replace("&#39;","'",$name);
					$title_val = str_replace("&quot;","\"",$title_val);
					$title_val = str_replace("&amp;",'&',$title_val);
					$title_val = str_replace("&lt;",'<',$title_val);
					$title_val = str_replace("&gt;",'>',$title_val);
					$title_val = str_replace("&nbsp;",' ',$title_val);
					$title_val = str_replace("&acute;","'",$title_val);
		
					$dis_val = str_replace("&#39;","'",$description);
					$dis_val = str_replace("&quot;","\"",$dis_val);
					$dis_val = str_replace("&amp;",'&',$dis_val);
					$dis_val = str_replace("&lt;",'<',$dis_val);
					$dis_val = str_replace("&gt;",'>',$dis_val);
					$dis_val = str_replace("&nbsp;",' ',$dis_val);
					$dis_val = str_replace("&acute;","'",$dis_val);
					$dis_val = str_replace("&#39;","'",$dis_val);
		
		
					$output .= '<category>';
					$output .= '<categoryId>'.$category_id.'</categoryId>';
					$output .= '<parentId>'.$parent_id.'</parentId>';
					$output .= '<categoryName>'.$title_val.'</categoryName>';
					$output .= '<productCount>'.$category_info['product_count'].'</productCount>';
					if ($image!='')
					{
						if (isset($_REQUEST['type']) && $_REQUEST['type']=='1')
						{
							if ($this->framework=='OpenCart')
							$image_name=str_replace("data","category/".OC_CATEGORY_IMAGE_TABLET,$image);
							else
							$image_name="category/".OC_CATEGORY_IMAGE_TABLET."/".$image;
						}
						else{
							if ($this->framework=='OpenCart')
							$image_name=str_replace("data","category/".OC_CATEGORY_IMAGE_PHONE,$image);
							else
							$image_name="category/".OC_CATEGORY_IMAGE_PHONE."/".$image;
						}
						$output .= '<categoryImage>'.HTTP_PATH.'upload/'.strtolower($this->framework).'/'.$image_name.'</categoryImage>';
		
					}else{
						$output .= '<categoryImage>No Record Found</categoryImage>';
					}
					if ($dis_val!="")
					$output .= '<categoryDesc>'.strip_tags($dis_val).'</categoryDesc>';
					else
					$output .= '<categoryDesc>No Record Found</categoryDesc>';
					$output .= '<downloadDate>'.date('Y-m-d').'</downloadDate>';
					$output .= '<downloadTime>'.date('H:i:s').'</downloadTime>';
		
		
					$output .='</category>';
				}
				else
					$category_count -= 1;
			}
			$output = str_replace("#CAT_COUNT#",$category_count,$output);
			$output .= '</categoryInfo>';
			$output .= '</MainCategory>';
		
			$this->renderXML($output);
		}
		
		
		
		/**
		 * displayProductsListJSON()
		 * 
		 * Display Products List by Filters - JSON Way
		 * 
		 */
		function displayProductsListJSON()
		{
			$this->logDevice($this->request->device_id,$this->request->type);
			
			$ProductsList = array();
			
			$category_id= ($this->request->path ? $this->request->path : '' );
			$device_id 	= ($this->request->device_id ? $this->request->device_id : '' );
			$date 		= ($this->request->date ? $this->request->date : '' );
			$time 		= ($this->request->time ? $this->request->time : '' );
			$start 		= ($this->request->start ? $this->request->start : 0 );
			
			//Get All products ID's
			$products_id = $this->getProductsByCategory($category_id);

			$products_count = (int) count($products_id);

			if (!isset($this->request->start) || $this->request->start == '')
			{
				$start = 0;
				$end = 24;
			}
			else
			{
				$start = $this->request->start;
				$end = 24;
			}

			$products = $this->getProductsByCategory($category_id, $start, $end);
			
			$prod_counts = 0;			
			
			if ($start > 0 && isset($this->request->date) && $this->request->date != '0' && isset($this->request->date) && $this->request->time != '0')
			{
				$date = $this->request->date." ".$this->request->time;

				if (isset($this->request->type) && $this->request->type == '1')
				{
					$products_date = $this->getProductsByDateCategory($category_id,$date,0,$start);
				}
				else
				{
					$products_date = $this->getProductsByDateCategory($category_id,$date);
				}

				$prod_counts = count($products_date);
			}

			$ProductsList['productAll'] = array();
			
			if (count($products_id) > 0)
			{
				foreach($products_id as $products_id)
				{
					$ProductsList['productAll'][] = $products_id['product_id'];
				}
			} else {
				$ProductsList['productAll'] = array();
			}

			$ProductsList['productAllCount'] = $products_count;

			$ProductsList['productInfo'] = array();
			
			$productInfoDate = array();
			
			if ($start > 0 and $prod_counts > 0)
			{
				$productInfoDate = $this->getProductsListJSON($products_date);				
			}

			$productInfo = $this->getProductsListJSON($products);
			
			$prod_counts = count($productInfo);
			
			$ProductsList['productInfo'] = array_merge($productInfoDate,$productInfo);
			$ProductsList['productInfoCount'] = $prod_counts;

			$this->renderJSON($ProductsList);
		}
		
		/**
		 * displayProductsListXML()
		 * 
		 * Display Products List by Filters - XML Way
		 * 
		 */
		function displayProductsListXML()
		{
			$this->logDevice($this->request->device_id,$this->request->type);
			$ProductsList = "";
					
			$category_id= ($this->request->path ? $this->request->path : '' );
			$device_id 	= ($this->request->device_id ? $this->request->device_id : '' );
			$date 		= ($this->request->date ? $this->request->date : '' );
			$time 		= ($this->request->time ? $this->request->time : '' );
			$start 		= ($this->request->start ? $this->request->start : 0 );
			
			//Get All products ID's
			$products_id = $this->getProductsByCategory($category_id);

			$products_count = (int) count($products_id);

			if (!isset($this->request->start) || $this->request->start == '')
			{
				$start = 0;
				$end = 24;
			}
			else
			{
				$start = $this->request->start;
				$end = 24;
			}
			
			$prod_counts = 0;			
			
			if ($start > 0 && isset($this->request->date) && $this->request->date != '0' && isset($this->request->date) && $this->request->time != '0')
			{
				$date = $this->request->date." ".$this->request->time;

				if (isset($this->request->type) && $this->request->type == '1')
				{
					$products_date = $this->getProductsByDateCategory($category_id,$date,0,$start);
				}
				else
				{
					$products_date = $this->getProductsByDateCategory($category_id,$date);
				}

				$prod_counts = count($products_date);
			}
			
			if (isset($this->request->missed) && !empty($this->request->missed)) {
				$products = $this->getProductsByCategory($category_id, 0, '', $this->request->missed);
			} else {
				$products = $this->getProductsByCategory($category_id, $start, (($end > $products_count) ? $products_count - $prod_counts : $end - $prod_counts));
			}			
		
			$ProductsList = '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">';
			$ProductsList .= '<channel>';
			$ProductsList .= '<title></title>';
			$ProductsList .= '<description></description>';
			$ProductsList .= '<link></link>';
			$ProductsList .= '<productAllId>';
			
			if (count($products_id) > 0)
			{
				foreach($products_id as $products_id)
				{
					$ProductsList .= '<productId>'.$products_id['product_id'].'</productId>';
				}
			}
			else
			{
				$ProductsList .= '<productId>No Records Found</productId>';
			}
			$ProductsList .= '</productAllId>';
		
			$ProductsList .= '<productCount>'.$products_count.'</productCount>';
		
			if ($start > 0 and $prod_counts > 0)
			{
				$ProductsList .= $this->getProductsListXML($products_date);
			}
		
			$ProductsList .= $this->getProductsListXML($products);
		
			$ProductsList .= '</channel>';
			$ProductsList .= '</rss>';
		
			$this->renderXML($ProductsList);
		}

		/**
		 * displayProductsSearchJSON()
		 * 
		 * Display Products Search Result by Filters - JSON Way
		 * 
		 */
		function displayProductsSearchJSON()
		{
			$ProductsList = array();
					
			$keyword = @$this->request->keyword;
			$device_id = @$this->request->device_id;
		
			//Get product information based on date and start points
			$products = $this->getProductsBySearch($keyword);
		
			$products_count = count($products);
			
			$ProductsList['product'] = $this->getProductsListJSON($products);
			$ProductsList['productCount'] = $products_count;
			
			$this->renderJSON($ProductsList);
		}
		
		/**
		 * displayProductsSearchXML()
		 * 
		 * Display Products Search Result by Filters - XML Way
		 * 
		 */
		function displayProductsSearchXML()
		{
			$ProductsList = array();
				
			$keyword = @$this->request->keyword;
			$device_id = @$this->request->device_id;
		
			$products = $this->getProductsBySearch($keyword);
		
			$products_count = count($products);

			$ProductsList = '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">';
			$ProductsList .= '<channel>';
			$ProductsList .= '<title></title>';
			$ProductsList .= '<description></description>';
			$ProductsList .= '<link></link>';
			
			$ProductsList .= $this->getProductsListXML($products);
			
			$ProductsList .= '<productCount>'.$products_count.'</productCount>';
				
			$ProductsList .= '</channel>';
			$ProductsList .= '</rss>';
				
			$this->renderXML($ProductsList);
		}
		
		/**
		 * displayFeaturedProductsJSON()
		 * 
		 * Display Featured Products by Filters - JSON Way
		 * 
		 */
		function displayFeaturedProductsJSON()
		{
			$FeaturedProductsList = array();
			
			$featured_product_all = array();
				
			$featured_product_all = $this->getFeaturedProducts();					
				
			$FeaturedProductsList['featuredAll'] = array();
			
			$products_count = count($featured_product_all);			
			$FeaturedProductsList['product'] = $this->getProductsListJSON($featured_product_all,'false');
			$FeaturedProductsList['productCount'] = $products_count;
				
			$this->renderJSON($FeaturedProductsList);
		}
		
		/**
		 * displayFeaturedProductsJSON()
		 * 
		 * Display Featured Products by Filters - JSON Way
		 * 
		 */
		function displayAnnouncementsJSON()
		{
			$AnnouncementProductsList = array();
			
			$announcements = array();
				
			$announcements_all = $this->getAnnouncements();
				
			if (isset($this->request->date) && $this->request->date != '0' && isset($this->request->time) && $this->request->time != '0')
			{
				$date = $this->request->date." ".$this->request->time;
				$announcements = $this->getAnnouncements($date);		
			}
			else
			{
				$announcements = $announcements_all;
			}

			$frame_id = 0;
			
			if (count($announcements))
				$frame_id = $announcements[(count($announcements)-1)]['frame_id'];
				
			$AnnouncementProductsList['AnnouncementAll'] = array();
			
			if (count($announcements_all) > 0)
			{
				foreach($announcements_all as $announcements_all)
				{
					$AnnouncementProductsList['AnnouncementAll'][] = $announcements_all['id'];
				}
			}
			else
				$AnnouncementProductsList['AnnouncementAll'] = array();
				
			$AnnouncementProductsList['productInfo'] = array();
			
			if (count($announcements) > 0)
			{
				foreach($announcements as $announcements)
				{
					if ($announcements['status'] == 'A')
					{		
						$productInfo['productId'] = $announcements['id'];
						$productInfo['link'] = $announcements['product_link'];
		
						if ($announcements['product_image'])
						{
							if (isset ($this->request->type) && $this->request->type == '1')
							{
								$productInfo['big_image'] = HTTP_PATH.str_replace("bigImage","smallImage",$announcements['product_image']);
								$productInfo['thumb_image'] = HTTP_PATH.str_replace("bigImage","thumb",$announcements['product_image'])."?thumb";
							}
							else
							{
								$productInfo['big_image'] = HTTP_PATH.str_replace("bigImage","smallImage",str_replace("procduct_","procduct_iphone",$announcements['product_image']));
								$productInfo['thumb_image'] = HTTP_PATH.str_replace("bigImage","thumb",str_replace("procduct_","procduct_iphone_thumb",$announcements['product_image']))."?thumb";									
							}
						}
						else
							$productInfo['big_image'] = $this->no_record;
						
						$productInfo['downloadDate'] = date('Y-m-d');
						$productInfo['downloadTime'] = date('H:i:s');
						
						$AnnouncementProductsList['productInfo'][] = $productInfo;								
					}
				}
			}
		
			$this->renderJSON($AnnouncementProductsList);
		}
		
		/**
		 * displayFeaturedProductsXML()
		 * 
		 * Display Featured Products by Filters - JSON Way
		 * 
		 */
		 function displayFeaturedProductsXML()
		{
			$FeaturedProductsList = "";
			
			$featured_product_all = array();				
					
			$featured_product_all = $this->getFeaturedProducts();
		
			$products_count = count($featured_product_all);

			$ProductsList = '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">';
			$ProductsList .= '<channel>';
			$ProductsList .= '<title></title>';
			$ProductsList .= '<description></description>';
			$ProductsList .= '<link></link>';
			
			$ProductsList .= '<productAllId>';
			
			if (count($featured_product_all) > 0)
			{
				foreach($featured_product_all as $products_id)
				{
					$ProductsList .= '<productId>'.$products_id['product_id'].'</productId>';
				}
			}
			else
			{
				$ProductsList .= '<productId>No Records Found</productId>';
			}
			$ProductsList .= '</productAllId>';
			
			$ProductsList .= $this->getProductsListXML($featured_product_all,'false');
			
			//$ProductsList .= '<productCount>'.$products_count.'</productCount>';
				
			$ProductsList .= '</channel>';
			$ProductsList .= '</rss>';
				
			$this->renderXML($ProductsList);			
		}
		function displayAnnouncementsXML()
		{
			$AnnouncementProductsList = "";
			
			$announcements = array();
				
			$announcements_all = $this->getAnnouncements();
				
			if (isset($this->request->date) && $this->request->date != '0' && isset($this->request->time) && $this->request->time != '0')
			{
				$date = $this->request->date." ".$this->request->time;
				$announcements = $this->getAnnouncements($date);		
			}
			else
			{
				$announcements = $announcements_all;
			}

			$frame_id = 0;
			
			if (count($announcements))
				$frame_id = $announcements[(count($announcements)-1)]['frame_id'];
				
			$AnnouncementProductsList = '<AnnouncementInfo>';
			
			if (count($announcements_all) >0)
			{
				$AnnouncementProductsList .= '<AnnouncementAll>';
				foreach($announcements_all as $announcements_all)
				{
					$AnnouncementProductsList .= '<AnnouncementIdAll>'.$announcements_all['id'].'</AnnouncementIdAll>';
				}
				$AnnouncementProductsList .= '</AnnouncementAll>';
			}
			else
				$AnnouncementProductsList .='<AnnouncementAll>No Record Found</AnnouncementAll>';
				
			if (count($announcements) > 0)
			{
				foreach($announcements as $announcements)
				{
					$r=0;
					if ($announcements['status'] == 'A')
					{
						$r++;
						$AnnouncementProductsList .= '<productInfo>';
		
						$AnnouncementProductsList .= '<productId>'. $announcements['id'] . '</productId>';
						$AnnouncementProductsList .= '<link><![CDATA['. $announcements['product_link'] . ']]></link>';
		
						if ($announcements['product_image'])
						{
							if (isset ($_REQUEST['type']) && $_REQUEST['type'] == '1')
							{
								$AnnouncementProductsList .= '<big_image>' . HTTP_PATH.str_replace("bigImage","smallImage",$announcements['product_image']) . '</big_image>';
								$AnnouncementProductsList .= '<thumb_image>' . HTTP_PATH.str_replace("bigImage","thumb",$announcements['product_image']) ."?thumb". '</thumb_image>';
							}
							else
							{
								$AnnouncementProductsList .= '<big_image>' . HTTP_PATH.str_replace("bigImage","smallImage",str_replace("procduct_","procduct_iphone",$announcements['product_image'])) . '</big_image>';
								$AnnouncementProductsList .= '<thumb_image>' . HTTP_PATH.str_replace("bigImage","thumb",str_replace("procduct_","procduct_iphone_thumb",$announcements['product_image']))."?thumb" . '</thumb_image>';
									
							}
						}
						else
							$AnnouncementProductsList .= '<big_image>No Record Found</big_image>';
						
						$AnnouncementProductsList .= '<downloadDate>'.date('Y-m-d').'</downloadDate>';
						$AnnouncementProductsList .= '<downloadTime>'.date('H:i:s').'</downloadTime>';
						$AnnouncementProductsList .= '</productInfo>';
							
					}
		
				}
				if ($r==0)
				{
					$AnnouncementProductsList .= '<productInfo>No Record Found</productInfo>';
				}
			}
			else
			{
				$AnnouncementProductsList .= '<productInfo>No Record Found</productInfo>';
			}
				
				
			$AnnouncementProductsList .= '</AnnouncementInfo>';
		
			$this->renderXML($AnnouncementProductsList);
		}
		
		/**
		 * displayCustomerDetailsJSON()
		 * 
		 * Display Featured Products by Filters - JSON Way
		 * 
		 * @return boolean
		 */
		function displayCustomerDetailsJSON()
		{
			$CustomerDetails = array();
			$username = trim($this->request->uid);
			$password = trim($this->request->secret);
						
			if (!empty($username) && !empty($password))
			{
				$details = $this->getCustomerDetails($username,$password);
		
				if (!empty($details))
				{
					$CustomerDetails['customer_id'] = $details["customer_id"];
						
					$allDetails = '';
		
					foreach($details as $key => $value)
					{
						if (!in_array($key,array('user','password')))
						{
							if ($value != "")
								$CustomerDetails['shipping_address'][$key] = $value;
							else
								$CustomerDetails['shipping_address'][$key] = $this->no_record;
						}
					}
					
					$CustomerDetails['shipping_address']['address_id'] = $details['address_id'];
						
					$CustomerDetails['billing_address'] = $CustomerDetails['shipping_address'];
											
					$this->renderJSON($CustomerDetails);
						
					return true;
				}
			}
				
			$this->renderJSON($this->no_record);
		}
		
		/**
		 * displayCustomerDetailsXML()
		 * 
		 * Enter description here ...
		 * 
		 */
		function displayCustomerDetailsXML()
		{
			$username = trim($_REQUEST['uid']);
			$password = trim($_REQUEST['secret']);
						
			if (!empty($username) && !empty($password))
			{
				$details = $this->getCustomerDetails($username,$password);
		
				if (!empty($details))
				{
					$detailsXML = '<customer id="'.$details["customer_id"].'">';
						
					$allDetails = '';
		
					foreach($details as $key => $value)
					{
						if (!in_array($key,array('user','password')))
						{
							if ($value!="")
								$allDetails .= '<'.$key.'>'.$value.'</'.$key.'>';
							else
								$allDetails .= '<'.$key.'>No Record Found</'.$key.'>';
						}
					}
						
					$detailsXML .= '<billing id="'.$details['address_id'].'">'.$allDetails.'</billing>'.'<shipping id="'.$details['address_id'].'">'.$allDetails.'</shipping>';
		
					$detailsXML .= '</customer>';
						
					$this->renderXML($detailsXML);
				}
			}
				
			$detailsXML = '<customer>No Record Found</customer>';
				
			$this->renderXML($detailsXML);
		}
		
		/**
		 * getCountriesJSON()
		 * 
		 * Enter description here ...
		 * 
		 * @return string
		 */
		function getCountriesJSON()
		{
			$countriesList = array();
			
			$countriesList['countries'] = array();
				
			$countries = $this->getCountries();
		
			foreach($countries as $country)
			{
				$countryInfo['id'] = $country['id'];
				$countryInfo['name'] = $country['name'];				
				
				$countryInfo['states'] = $this->getStates($country['id']);
																		
				$countriesList['countries'][] = $countryInfo;
			}
			
			$this->renderJSON($countriesList);			
		}
		
		/**
		 * getCountriesXML()
		 * 
		 * Enter description here ...
		 * 
		 */
		function getCountriesXML()
		{
			$countriesXML = '<countries>';
				
			$countries = $this->getCountries();
		
			foreach($countries as $country)
			{
				$countriesXML .= '<country id="'.$country['id'].'">' .
									'<id>'.$country['id'].'</id>' .
									'<name>'.($country['name']).'</name>' .
									$this->getStatesXML($country['id']).
								 '</country>';
			}
			
			$countriesXML .= '</countries>';
				
			$this->renderXML($countriesXML);
		}		
		/**
		 * updateAddress()
		 * 
		 * Update Address
		 * 
		 */
		function updateAddress()
		{
			$data = $_REQUEST;
			
			if (!isset($data['zone_id']) || $data['zone_id'] == "")			
				$data['zone_id'] = $data['state_id'];
						
			if (isset($data['address_1']) && isset($data['city']) && isset($data['country_id']) && isset($data['state_id']))
			{
				echo $this->addAddress($data);				
			}
			else
				echo '0';
		}
		
		/**
		 * COMMON CODE ENDS
		 */
		
		/**
		 * DEPENDENT CODE STARTS
		 */
		
		/**
		 * getAdminDetails()
		 * 
		 * Get Administrator Login Details
		 * 
		 * @param string $uname
		 * @param string $passwd
		 * 
		 * @return string
		 */
		function getAdminDetails($uname ,$passwd)
		{
			/**
			 * TODO : To be implemented in Childrens
			 */
			return '0';
		}
		
		/**
		 * getCategories()
		 * 
		 * Get All Categories With/Without Filter
		 * 
		 * @param string $date
		 * 
		 * @return multitype:array
		 */
		function getCategories($date = NULL)
		{
			//TODO:
			return array();
		}
		
		/**
		 * getCategoryImage()
		 * 
		 * Enter description here ...
		 * 
		 * @param unknown_type $image
		 * @param unknown_type $type
		 * @return string
		 */
		function getCategoryImage($image,$type = 0, $id = 0)
		{
			if (isset($this->request->type) && $this->request->type == '1')
			{
				if (file_exists(DIR_PATH.'upload/'.strtolower($this->framework).'/'.'category/'.OC_CATEGORY_IMAGE_TABLET.''.$image))
					return HTTP_PATH.'upload/'.strtolower($this->framework).'/'.'category/'.OC_CATEGORY_IMAGE_TABLET.''.$image;
			}
			else
			{
				if (file_exists(DIR_PATH.'upload/'.strtolower($this->framework).'/'.'category/'.OC_CATEGORY_IMAGE_PHONE.''.$image))
					return HTTP_PATH.'upload/'.strtolower($this->framework).'/'.'category/'.OC_CATEGORY_IMAGE_PHONE.''.$image;
			}
			return $this->no_record;
		}
				
		/**
		 * getCategoryProductsCount
		 * 
		 * Get Products Count user Category
		 *
		 * @param int $categoryid
		 * @return number
		 */
		function getCategoryProductsCount($categoryid)
		{					
			//TODO:
		
			return 0;
		}
		
		/**
		 * getAllProducts()
		 * 
		 * Get All Products List (With/Without Modified Date Filter)
		 * 
		 * @return multitype:array
		 */
		function getAllProducts()
		{
			/**
			 * TODO: In Childrens
			 */
			return array();
		}
		
		/**
		 * getProductsByCategory()
		 * 
		 * Get All Products By Category
		 * 
		 * @param int $category_id
		 * @param int $start
		 * @param int $end
		 * 
		 * @return multitype:array
		 */
		function getProductsByCategory($category_id, $start = '', $end = '')
		{
			//TODO:
			return array();
		}

		/**
		 * getProductsByDateCategory()
		 * 
		 * Get All Products By Modified Date and Category
		 * 
		 * @param int $category_id
		 * @param string $date
		 * @param int $start
		 * @param int $end
		 * 
		 * @return multitype:array
		 */
		function getProductsByDateCategory($category_id, $date, $start = '', $end = '')
		{
			//TODO:
			return array();
		}
		
		/**
		 * getProductsBySearch()
		 * 
		 * Get All Products by Search
		 * 
		 * @param string $keyword
		 * 
		 * @return multitype:array
		 */
		function getProductsBySearch($keyword)
		{
			/**
			 * TODO: In Childrens
			 */
			return array();
		}

		/**
		 * getProductImages()
		 * 
		 * Get All Product Images
		 * 
		 * @param int $product_id
		 * 
		 * @return multitype:array
		 */
		function getProductImages($product_id)
		{
			/**
			 * TODO: In Childrens
			 */
			return array();
		}
		
		/**
		 * getProductOptions()
		 * 
		 * Get All Product Options
		 * 
		 * @return multitype:array
		 */
		function getProductOptions()
		{
			// TODO:
			return array();
		}
		
		/**
		 * getProductURL()
		 * 
		 * Get Product URL
		 * 
		 * @param int $product_id
		 * 
		 * @return string
		 */
		function getProductURL($product_id)
		{
			// TODO:
			return "#";
		}		
		
		/**
		 * getProductImageURL()
		 * 
		 * Get Product Image URL
		 * 
		 * @param multitype:array $product_image_info
		 * @param boolean $small
		 * 
		 * @return string
		 */
		function getProductImageURL($product_image_info, $small = false)
		{
			$product_image = $product_image_info['image'];
	
			$path = @explode('/',$product_image);
							
			if (count($path) > 1)
				$image = $path[1];
			else
				$image = $product_image;
				
			if (isset($this->request->type) && $this->request->type == '1')
			{
				//$image_normal	= "products/354x541/".$image;
				//$image_small 	= "products/198x130/".$image;
				$image_normal	= "products/".OC_PRODUCT_BIG_IMAGE_TABLET."/".$image;
				$image_small 	= "products/".OC_PRODUCT_SMALL_IMAGE_TABLET."/".$image;
			}
			else
			{
				$image_normal	= "products/".OC_PRODUCT_BIG_IMAGE_PHONE."/".$image;
				$image_small	= "products/".OC_PRODUCT_SMALL_IMAGE_PHONE."/".$image;
			}
			
			if ($small)
			{
				if (file_exists(DIR_PATH.'upload/'.strtolower($this->framework).'/'.$image_small))
					return HTTP_PATH.'upload/'.strtolower($this->framework).'/'.$image_small."?small";
			}
			else
			{
				if (file_exists(DIR_PATH.'upload/'.strtolower($this->framework).'/'.$image_normal))
					return HTTP_PATH.'upload/'.strtolower($this->framework).'/'.$image_normal."?big";
			}
			return $this->no_record;
		}		
				
		/**
		 * getProductThumbImageURL()
		 * 
		 * Get Product Thumbnail Image URL
		 * 
		 * @param multitype:array $product_image_info
		 * 
		 * @return string
		 */
		function getProductThumbImageURL($product_image_info = NULL)
		{
			$product_image = $product_image_info['image'];
			
			$path = @explode('/',$product_image);
									
			if (count($path)>1)
				$image=	$path[1];
			else
				$image=	$product_image;

			if (isset($this->request->type) && $this->request->type=='1')
			{
				//$small_img_name = "products/details/354x541/".$image;
				$small_img_name = "products/details/".OC_PRODUCT_BIG_IMAGE_TABLET."/".$image;

			}else{
				$small_img_name = "products/details/".OC_PRODUCT_BIG_IMAGE_PHONE."/".$image;
			}

			if (file_exists(DIR_PATH.'upload/'.strtolower($this->framework).'/'.$small_img_name))
			{
				return HTTP_PATH.'upload/'.strtolower($this->framework).'/'.$small_img_name."?big";
			}
			return $this->no_record;
		}	
		
		/**
		 * getCountries()
		 * 
		 * Enter description here ...
		 * 
		 * @return multitype:
		 */
		function getCountries()
		{
			return array();
		}
		
		/**
		 * getStates()
		 * 
		 * Get All States for Country
		 * 
		 * @param int $country_id
		 * @return multitype:array
		 */
		function getStates($country_id = 0)
		{
			return array();
		}
		
		/**
		 * getCustomerDetails()
		 * 
		 * Get Customer Details
		 * 
		 * @param string $username
		 * @param string $password
		 * @return multitype:array
		 */
		function getCustomerDetails($username = '',$password = '')
		{
			return array();
		}
		
		/**
		 * addAddress()
		 * 
		 * Add Address to Database
		 * 
		 * @param multitype:array $data
		 * @return number
		 */
		function addAddress($data)
		{
			// TODO:
			return 0;
		}
		
		/**
		 * getCurrencyOption()
		 * 
		 * Get Currency Option
		 * 
		 * @return string
		 */
		function getCurrencyOption()
		{	
			// TODO : To be implemented in Childrens
			return 'USD';
		}		
		
		/**
		 * checkCart()
		 * 
		 * Check Cart for Quantity
		 * 
		 */
		function checkCart()
		{
			// TODO : To be implemented in Childrens
		}
		
		/**
		 * processCart()
		 * 
		 * Process Cart
		 * 
		 */
		function processCart()
		{
			// TODO : To be implemented in Childrens
		}
		
		/**
		 * checkoutCart()
		 * 
		 * Checkout Cart
		 */
		function checkoutCart()
		{
			// TODO : To be implemented in Childrens
		}		
		 /**
		 * getFeaturedProducts()
		 * 	
		 */
		function getFeaturedProducts()
		{
		
			// TODO : To be implemented in Childrens
		}		
		/**
		 * getStores()
		 * 	
		 */
		function getStores()
		{
		
			// TODO : To be implemented in Childrens
		}	
		/**
		/**
		 * getFrameId()
		 * 
		 * Get Frame Id of current Store
		 * 		
		 * 
		 * @return FrameId
		 */
		function getFrameId()
		{			
					
				$table = $this->table_prefix.$this->tableMap['announcements'];			
				if (isset($this->store_id))
			  	$where_clause=" WHERE store_id=".$this->store_id;
			 	 else
			 	 $where_clause=" WHERE store_id=0";				
				$sql = "SELECT frame_id FROM {$table} {$where_clause} ORDER BY store_id DESC LIMIT 1";
				$result = $this->db->query($sql);
				return $result->row['frame_id'];
			
		}/**
		/**
		 * getMaxOrderId()
		 * 
		 * Get Max order Id of current Store
		 * 		
		 * 
		 * @return orderid
		 */
		function getMaxOrderId()
		{			
					
				$table = $this->table_prefix.$this->tableMap['announcements'];			
				if (isset($this->store_id))
			  	$where_clause=" WHERE store_id=".$this->store_id;
			 	 else
			 	 $where_clause=" WHERE store_id=0";				
				$sql = "SELECT max(order_id) as order_id FROM {$table} {$where_clause} ";
				$result = $this->db->query($sql);
				return $result->row['order_id'];
			
		}/**
		/**
		 * get()
		 * 
		 * Get Guest Checkout option 
		 * 
		 * @return string
		 */
		function getGuestCheckout()
		{	
			// TODO : To be implemented in Childrens
		
		}	
		/**
		 * getSynProductImageURL()
		 * 
		 * Get Product Image URL
		 * 
		 * @param multitype:array $product_image_info
		 * @param boolean $small
		 * 
		 * @return string
		 */
		function getSynProductImageURL($product_image_info,$folder)
		{
			$product_image = $product_image_info['image'];
	
			$path = @explode('/',$product_image);
							
			if (count($path) > 1)
				$image = $path[1];
			else
				$image = $product_image;
				
				$image_normal	= "products/".$folder."/".$image;			
				if (file_exists(DIR_PATH.'upload/'.strtolower($this->framework).'/'.$image_normal))
				return HTTP_PATH.'upload/'.strtolower($this->framework).'/'.$image_normal;
				else
				return $this->no_record;
		}	
		function synchroniseData($data = array())
		{
			$table = $this->table_prefix.$this->tableMap['synchronise'];
			if (count($data))
			{
				$hasRecords = false;
					
				$where=" device_id='".$data['device_id']."' AND synch_type='".$data['synch_type']."'";

				if (isset($this->store_id))
					$where.=" AND store_id=".$this->store_id;
				else
					$where.=" AND store_id=0";	
				
				$result = $this->db->query("SELECT * FROM {$table} WHERE ".$where);
				if ($result->num_rows)
					$hasRecords = true;
				
				if ($hasRecords)
				{						
					$save_query = "UPDATE `{$table}` SET ";
			
					foreach ($data as $key => $value)
					{
						if ($key != 'setting_id')
							$save_query .= "`{$key}` = '{$value}', ";
					}
						
					$save_query = trim($save_query,", ");		
					$save_query.=" WHERE ".$where;			
				}
				else
				{
					$save_query = "INSERT INTO `{$table}` ";
					$save_query .= "(`".implode("`, `", array_keys((array) $data))."`) ";
					$save_query .= "VALUES ('".implode("', '", array_values((array) $data))."')";
				}		
					$this->db->query($save_query);
				
				//if ($this->db->countAffected())
					return true;
			}
			
			return false;
		}

		function sendNotification($device_id,$message)
		{
			$status = array('error' => true);
			
			$device_id = trim($device_id);
			
			$device_type = 'C2DM';
			
			if (strlen($device_id) == 15)
			{
				$device_type = 'C2DM';
			}
			else if (strlen($device_id) == 40)
			{
				$device_type = 'APNS';
			}
			
			require_once LIB_DIR_PATH.$device_type.'.inc.php';
			
			try
			{			
				switch ($device_type) {
					case 'C2DM':
						$career = new C2DM();
						$career->setUsername('surecards@gmail.com');
						$career->setPassword('sunevenus');
						$career->setSource('Benchmark-ScentSational-2.1');
						
						$career->googleAuthenticate();			
						
						$status = $career->send($device_id,$message);
						
						if ($status)
						{
							if (strstr($status, "Error="))
							{
								$status = array('success' => false, 'error' => true, 'errors' => str_replace("Error=","", $status));
							}
							else {
								$status = array('success' => true, 'error' => $status);
							}
						}
						else
						{
							$status = array('success' => false, 'error' => true, 'errors' => 'Unknown Error');
						}
						break;
					case 'APNS':
						$career = new APNS('development');
						$career->setCertificate('/usr/local/apns/apns.pem');
						$status = $career->sendNotification($device_id,$message);
						break;
				}
			}
			catch (Exception $e){
				$status = array('success' => false, 'error' => true, 'errors' => $e->getMessage());
			}
			
			$this->renderJSON($status);
		}
		/**
		 * getProductSuperAttributesXML()
		 * 
		 * Prepare Product Super Attributes XML
		 * 
		 * @param int $product_id
		 * @return string
		 */
		public function getProductSuperAttributesXML($super_attributes) 
		{
			$product_options_xml = "";
		   	if (count($super_attributes))
			{		
				for($i=0;$i<count($super_attributes);$i++) {
					$product_options_xml .= "<super_attribute id='".$super_attributes[$i]['id']."' name='".html_entity_decode(addslashes($super_attributes[$i]['label']))."' title='".html_entity_decode(addslashes($super_attributes[$i]['label']))."'>";
		
					if (count($super_attributes[$i]['values']))
					{
						$product_option_value=$super_attributes[$i]['values'];
						for($j=0;$j<count($product_option_value);$j++) {
						if ($product_option_value[$j]['product_super_attribute_id']!="")
							{
							$product_options_xml .= "" .
										"<option" .
												" id='".$product_option_value[$j]['value_index']."'" .		
												" name='".html_entity_decode(addslashes($product_option_value[$j]['label']))."'" .
												" is_percent='".$product_option_value[$j]['is_percent']."'" .
												" pricing_value='".$product_option_value[$j]['pricing_value']."'" .
												" value_id='".$product_option_value[$j]['value_id']."'" .
												" value='".$product_option_value[$j]['value_index']."'>" .					
												html_entity_decode($product_option_value[$j]['label']).
										"</option>".
									"";
							}
						}
					}
		
					$product_options_xml .= "</super_attribute>";
				}
			}
		
			return $product_options_xml;
		}		
		/**
		 * getProductAttributesJSON()
		 * 
		 * Prepare Product Attributes Array
		 * 
		 * @param int $product_id
		 * @return multitype:array
		 */
		public function getProductSuperAttributesJSON($super_attributes) 
		{
						
			$ProductAttributes = array();
		
			if (count($super_attributes))
			{
				$ProductAttributes = array();
		
				for($i=0;$i<count($super_attributes);$i++) {
						
					$ProductAttribute = array();
		
					$ProductAttribute['id'] = $super_attributes[$i]['id'];
					$ProductAttribute['name'] = html_entity_decode(addslashes($super_attributes[$i]['label']));
					$ProductAttribute['title'] = html_entity_decode(addslashes($super_attributes[$i]['label']));				
					$ProductAttribute['options'] = array();
						
					if(count($super_attributes[$i]['values']))
					{
						$product_option_value=$super_attributes[$i]['values'];
						for($j=0;$j<count($product_option_value);$j++) {
							$ProductAttributeOption['id'] = $product_option_value[$j]['value_index'];
							$ProductAttributeOption['name'] = html_entity_decode(addslashes($product_option_value[$j]['label']));
							$ProductAttributeOption['is_percent'] = $product_option_value[$j]['is_percent'];
							$ProductAttributeOption['pricing_value'] = $product_option_value[$j]['pricing_value'];							
							$ProductAttributeOption['value_id'] = $product_option_value[$j]['value_id'];
							$ProductAttributeOption['value'] =$product_option_value[$j]['value_index'];
		
							$ProductAttribute['options'][] = $ProductAttributeOption;
						}
					}
		
					$ProductAttributes[] = $ProductAttribute;
				}
			}
		
			return $ProductAttributes;
		}
		/**
		 * getAppImageURL()
		 * 
		 * Get App Image URL
		 * 
		 * @param multitype:array $image
		 * @param boolean $small
		 * 
		 * @return string
		 */
		function getAppImageURL($image)
		{		
			if (file_exists(DIR_PATH.$image)){
				return HTTP_PATH.$image."";
			}	
			
			return $this->no_record;
		}					
		/**
		 * DEPENDENT CODE ENDS
		 */
	}
?>