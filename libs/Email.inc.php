<?php
	/*
	 * Email.inc.php
	 *
	 * Copyright (C) 2010-2020 Indosoft Inc.
	 *
	 * Developed By : Atul Ingale
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

	class Email
	{

		public $template;			// Email Template
		private $subject;			// Email subject
		private $data;				// Data to send email
		private $to;					// Array containing recipiants
		private $sender;				// Array of sender info
		private $options;

		private $mail;				// Array of sender info

		public $error_code;         // Error code returned as an int
		public $error_string;       // Error message returned as a string
		public $error_info;			// Technical description of error.



		function __construct()
		{

			require_once("eTemplate.inc.php");
			$this->template = new ETemplate();
			$lang = "pt-PT";
			$this->template->init(dirname(__FILE__).'/template/'.$lang.'/');

			// Initialize mail object
			$this->initMail();
		}



		public function setup($type="",$details = array(),$users = array(),$options=array())
		{
			if(empty($type)||empty($users))
			{
				$this->setError(1,get_ilocale("Unable to initialize email."),"Invalid parameters to setup mail function.");
 				return false;
			}
			$footer = '';
			$footer1 = '';
			switch($type)
			{
				case 'auto_pass':
								// Tempararily added
								// Setup subject and email template
								$this->subject 	= get_ilocale("Password recovery Continent Insurance");
								$template = "auto_pass.tpl";
								break;
								break;

				case 'register':
							// Setup subject and email template
							$this->subject 	= get_ilocale("Welcome to Continental Insurance");
							$template = "register.tpl";
							$footer = "<div id='footer'><img src='{::seguros_logo_path::}email-footer.jpg' title='footer 			 										logo' alt='footer logo'></div>";
							break;
				case "forgot_pass":
							$this->subject 	= get_ilocale("Password recovery Continent Insurance");
							$template = "forgot_pass.tpl";
							$footer = "<div id='footer'><img src='{::seguros_logo_path::}email-footer.jpg' title='footer 			 										logo' alt='footer logo'></div>";
							break;
				case "quote_pdf":
							$this->subject 	= "Simulação {$details['quote_type']}-Seguros Continente";
							$template = "quote_pdf.tpl";
							break;
				case "contact_us":
							$this->subject 	= isset($_REQUEST['select_subject']) ? $_REQUEST['select_subject'] : get_ilocale("Contact Us");
							$template = "contact_us.tpl";
							break;
				case "contact_us_consigo":
							$this->subject 	= get_ilocale("Contact Us")." consigo!";
							$template = "contact_us.tpl";
							break;
				case "recommend_fiend":
							$this->subject 	= get_ilocale("Recommendation of a friend.");//"Recommend a friend!";
							$template = "recommend.tpl";
							break;
				case "recommend_fiend_auto":  //Auto recommed a friend
							$this->subject 	= get_ilocale("Recommendation of a friend.");//"Recommend a friend!";
							$template = "recommend_auto.tpl";
							$footer = "<div id='footer'><img src='{::seguros_logo_path::}welcome-footer.jpg' title='footer 			 										logo' alt='footer logo'></div>";
							break;
				case "recommend_fiend_moto":  //Moto recommed a friend
							$this->subject 	= get_ilocale("Recommendation of a friend.");//"Recommend a friend!";
							$template = "recommend_moto.tpl";
							$footer = "<div id='footer'><img src='{::seguros_logo_path::}welcome-footer.jpg' title='footer 			 										logo' alt='footer logo'></div>";
							break;
				case "recommend_fiend_injury":  //Personal Injury recommed a friend
							$this->subject 	= get_ilocale("Recommendation of a friend.");//"Recommend a friend!";
							$template = "recommend_moto.tpl";
							$footer = "<div id='footer'><img src='{::seguros_logo_path::}welcome-footer.jpg' title='footer 			 										logo' alt='footer logo'></div>";
							break;
				case "recommend_fiend_health":  //Health recommed a friend
							$this->subject 	= get_ilocale("Recommendation of a friend.");//"Recommend a friend!";
							$template = "recommend_health.tpl";
							$footer = "<div id='footer'><img src='{::seguros_logo_path::}health-footer.jpg' title='footer 			 										logo' alt='footer logo'></div>";
							$footer1 = "<tr><td style='border:none;border-top:solid #CCCCCC 1.0pt; padding-left:20px;'><p> <div id='footer1'><img src='{::seguros_logo_path::}email-footer.jpg' title='footer	logo' alt='footer logo'></div></p></td></tr>";
							break;
				case "survay_email":
							$code = "";
							switch($_REQUEST['type'])
							{
								case "A":
									$code = "AU-";
									break;
								case "M":
									$code = "MT-";
									break;
							}
							$this->subject 	= $code.get_ilocale("Get a Quote");
							$template = "survay_email.tpl";
							break;
				case "schedule_app":
							$this->subject 	= get_ilocale("Mark Query");
							$template = "schedule_app.tpl";
							break;
				case "update_prfile":
							$this->subject 	= get_ilocale("Change Personal Data");
							$template = "update_profile.tpl";
							break;
				case "update_prfile_call_center":
							$this->subject 	= get_ilocale("Change Personal Data");
							$template = "update_profile_call_center.tpl";
							break;
				default:
							$this->setError(1,get_ilocale("Unable to initialize email."),"Error in setup email switch case.");
							return false;
			}


			$this->to 		= $users;
			$this->options	= $options;

			// Email options
			$this->setMailOptions($options);

			$details["footer_site_name"] = MAILER_DEFAULT_FOOTER_SITE;

			// Email template
			$this->loadTemplate($template, $details, $footer, $footer1); // COPY - pass $footer
			return true;
		}


		private function initMail()
		{
			if (!class_exists("PHPMailer"))
			{
				require_once(ABSPATH.'wp-includes/class-phpmailer.php');
			}

			$seguros_config = get_option("seguros_config");

			$this->mail = new PHPMailer();

			$this->mail->CharSet 		= get_bloginfo('charset');
			$this->mail->Host     		= (!empty($seguros_config['EMAIL']['SMTP']['HOST'])) ? $seguros_config['EMAIL']['SMTP']['HOST'] : SMTP_HOST;
			$this->mail->Port			= (!empty($seguros_config['EMAIL']['SMTP']['PORT'])) ? $seguros_config['EMAIL']['SMTP']['PORT'] : SMTP_PORT;
			$this->mail->Username 		= (!empty($seguros_config['EMAIL']['SMTP']['USER'])) ? $seguros_config['EMAIL']['SMTP']['USER'] : SMTP_USER;
			$this->mail->Password 		= (!empty($seguros_config['EMAIL']['SMTP']['PASSWORD'])) ? $seguros_config['EMAIL']['SMTP']['PASSWORD'] : SMTP_PASSWORD;
			$this->mail->ContentType 	= "text/html";

			if(defined("SMTP_AUTH") && SMTP_AUTH)
			{
				$this->mail->IsSMTP();

				if($this->mail->Mailer == 'smtp') {
					$this->mail->SMTPAuth = SMTP_AUTH;
				}
			}
		}

		private function setMailOptions($options=array())
		{
			$seguros_config = get_option("seguros_config");

			if(in_array('From',$options) && in_array('FromName',$options) )
				$this->mail->FromName     		= $options["FromName"];
			else
				$this->mail->FromName 		= (!empty($seguros_config['EMAIL']['SENDER']['NAME'])) ? $seguros_config['EMAIL']['SENDER']['NAME'] : MAILER_DEFAULT_FROM_NAME;

			if(in_array('From',$options))
				$this->mail->From     		= $options["From"];
			else
				$this->mail->From     		= (!empty($seguros_config['EMAIL']['SENDER']['EMAIL'])) ? $seguros_config['EMAIL']['SENDER']['EMAIL'] :MAILER_DEFAULT_FROM_EMAIL;

			if(in_array('Sender',$options))
				$this->mail->Sender     		= $options["Sender"];
			else
				$this->mail->Sender     	= (!empty($seguros_config['EMAIL']['SENDER']['EMAIL'])) ? $seguros_config['EMAIL']['SENDER']['EMAIL'] :MAILER_DEFAULT_FROM_EMAIL;

			if((defined("MAILER_DEFAULT_BCCS") && MAILER_DEFAULT_BCCS != "") || !empty($seguros_config['EMAIL']['BCC']))
			{
				$mailer_bccs = explode(";",!empty($seguros_config['EMAIL']['BCC']) ? $seguros_config['EMAIL']['BCC'] : MAILER_DEFAULT_BCCS);
				if(count($mailer_bccs))
				{
					foreach($mailer_bccs as $mailer_bcc)
					{
						$this->mail->AddBCC($mailer_bcc);
					}
				}
			}
		}

		private function loadTemplate($template="", $details=array(), $footer="", $footer1="") // COPY - pass footer
		{
			$template_data = $this->template->process($template, $details);


			$email_templete_html = file_get_contents(ABSPATH."wp-content/plugins/benchmark/themes/seguros/email_template.php","r") ;
			$img_path = home_url( '/' )."wp-content/plugins/benchmark/themes/seguros/images/";
			if($email_templete_html!='')
			{
				$email_templete_html = str_replace('{::seguros_email_footer::}',$footer,$email_templete_html);
				$email_templete_html = str_replace('{::seguros_email_footer1::}',$footer1,$email_templete_html);
				$email_templete_html = str_replace('{::seguros_logo_path::}',$img_path,$email_templete_html);
				$msg = str_replace('{::seguros_email_content::}',$template_data,$email_templete_html);
			}

			$this->data 	= $msg;
		}

		public function send()
		{
			// TO
			foreach($this->to as $user)
			{
				if(isset($user['name']) && !empty($user['name']))
					$user_name = $user['name'];

				$this->mail->AddAddress($user['email'], $user_name);
			}
			// Message subject
			$this->mail->Subject = $this->subject;
			// MEssage content
			$this->mail->MsgHTML($this->data);

			// Send The Mail if($mail->Send()) {
			if(!$this->mail->Send())
			{
				// SET ERROR
				$this->setError(2,get_ilocale("Error in sending email."),$this->mail->ErrorInfo);
				return false;
			}
			return true;
		}

		public function addAttachment($attachment=array())
		{
			if(empty($attachment))
			{
				return false;
			}

			$this->mail->Addattachment($attachment['link'],$attachment['name']);
			return true;
		}

		public function getData()
		{
			return $this->data;
		}

		public function getRecievers()
		{
			return $this->to;
		}

		public function addReciever($address)
		{
			if(empty($address))
				return false;

			$this->to[] =  $address;
			return true;
		}

		private function setError($code='',$desc='',$info='')
		{
			$this->error_code 	= $code;
			$this->error_string = $desc;
			$this->error_info = $info;
		}

		public function getError()
		{
			return array("Error_Code"=>$this->error_code,"Error"=>$this->error_string);
		}
		public function getErrorMessage()
		{
			return $this->error_string;
		}

	}
	// END SOAP Class
