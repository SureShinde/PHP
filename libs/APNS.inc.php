<?php
	/*
	 * APNS.inc.php
	 * 
	 * Apple Push Notification Service  Library
	 *
	 * Created By : Suresh Shinde
	 *
	 * Created On : Feb 3, 2012 1:17:40 PM
	 *
	 * Created Under : 
	 *
	 * Copyright (C) 2012 Indosoft Inc.
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

	include_once 'APNS/APNS.Base.inc.php';
	include_once 'APNS/APNS.Notification.inc.php';
	include_once 'APNS/APNS.Feedback.inc.php';

	class APNS 
	{
	
		/**
		 * Absolute path to your Production Certificate
		 *
		 * @var string
		 * @access private
		 */
		private $certificate = '/usr/local/apns/apns.pem';
	
		/**
		 * Absolute path to your Development Certificate
		 *
		 * @var string
		 * @access private
		 */
		private $sandboxCertificate = '/usr/local/apns/apns-dev.pem'; // change this to your development certificate absolute path
				
		/**
		 * User Device Token
		 *
		 * @var string
		 * @access private
		 */
		private $deviceToken;
		
		/**
		 * Message to push to user
		 *
		 * @var string
		 * @access private
		 */
		private $message;
				
		/**
		 * Server Environment
		 *
		 * @var string
		 * @access private
		 */
		private $environment;
		
		/**
		 * Notification Object
		 *
		 * @var string
		 * @access private
		 */
		
		public $notification;
		
		/**
		 * Feedback Object
		 *
		 * @var string
		 * @access private
		 */
		
		public $feedback;
	
		/**
		 * Constructor.
		 *
		 * Initializes a database connection and perfoms any tasks that have been assigned.
		 *
		 * <code>
		 * <?php
		 * 		$apns = new APNS();
		 * ?>
	 	 * </code>
	     *
	     * @param object $db Database Object
		 * @param array $args Optional arguments passed through $argv or $_GET
	     * @access 	public
	     */
		public function __construct($environment = 'development') {
			$this->environment = $environment;
			$this->certificate = '/usr/local/apns/apns.pem';
			$this->sandboxCertificate = '/usr/local/apns/apns-dev.pem';
		}
	
		public function setCertificate($certificate = '/usr/local/apns/apns.pem') {
			switch($this->environment)
			{
				case 'production':
					$this->certificate = $certificate;
					break;
				case 'development':
					$this->sandboxCertificate = $certificate;
					break;
			}			
		}
		
		public function sendNotification($deviceToken, $message = "Hello"){
			if(!isset($this->notification)){
				$this->notification = new APNSNotification($this->environment);
				
				switch($this->environment)
				{
					case 'production':
						$this->notification->setPrivateKey($this->certificate);
						break;
					case 'development':
						$this->notification->setPrivateKey($this->sandboxCertificate);
						break;
				}
			}
			
			$this->deviceToken = $deviceToken;
			$this->message 		= $message;
						
			$this->notification->setBadge(1);
		  	$this->notification->setDeviceToken($this->deviceToken);
		  	$this->notification->setMessage($this->message);
		  	$this->notification->send();
		}
		
		public function getFeedback(){		
			if(!isset($this->feedback)){
				$this->feedback = new APNSFeedback($this->environment);
				
				switch($this->environment)
				{
					case 'production':
						$this->feedback->setPrivateKey($this->certificate);
						break;
					case 'development':
						$this->feedback->setPrivateKey($this->sandboxCertificate);
						break;
				}
			}
		}
	}
?>