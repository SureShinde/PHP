<?
	/**
	 * Template.inc.php
	 *
	 * Description 	: Lightweight Template Processing Tool In PHP
	 *
	 * Developed By : Suresh Shinde <sureshinde@inbox.com>
	 *
	 * Developed On : 13 August, 2010
	 *
	 * Copyright (C) 2010-2020 Indosoft Inc.
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

	class ETemplate
	{
		var $path;
		var $data;
		var $template;
		var $tags;

		public function __construct()
		{
			$this->path = ".";
			$this->data = "";
			$this->template = "";
			$this->tags = array();
		}

		/**
		 * Set Template Path
		 *
		 * @param string $path 	Template Path
		 *
		 */

		public function init($path = '.')
		{
			$this->path = $path;
		}

		/**
		 * Process Template File
		 *
		 * @param string $template 	Template Name
		 *
		 * @param array $tags		Replacement Tags
		 *
		 */
		public function process($template = "", $tags = array())
		{
			$this->data = "";
			$this->template = "";
			$this->tags = array();

			if($template != "")
			{
				$this->readTemplate($template);
				$this->processTemplate($tags);
			}

			return $this->data;
		}

		/**
		 * Save Processed Template to a File
		 *
		 * @param string $dest_file 	Target File Name
		 *
		 */
		public function save($dest_file)
		{
			umask(0);

			$dest_dir = dirname($dest_file);

			if(!is_dir($dest_dir))
				mkdir($dest_dir,0777,true);

			$fp = fopen($dest_file,'w+');
			fwrite($fp,$this->data);
			fclose($fp);

			chmod($dest_file,0777);
		}

		/**
		 * Read Template File
		 *
		 * @param string $template 	Template Name
		 *
		 */
		private function readTemplate($template)
		{
			$this->template = implode('',file($this->path.$template));
		}

		/**
		 * Process Template File
		 *
		 * @param array $tags		Replacement Tags
		 *
		 */
		private function processTemplate($tags = array())
		{
			$parts = preg_split("/<%|%>/i", $this->template);
			$cstat	= array();
			$level	= 0;
			$text 	= '';
			$cstat[]=1;

			for ($i = 0; $i < count($parts); $i++)
			{
				if($i % 2 == 0)
				{
					if($cstat[$level])
						$text	.=	$parts[$i];
				}
				else
				{
					// Check For Conditional Templates
					preg_match_all('/^((?:unless|if|endif|else)\b)?(.*?)(?:([=<>!])(.*?))?$/i', $parts[$i], $matches);
					$statement	=	$matches[1][0];
					$tag	=	strtolower($matches[2][0]);
					$oper	=	strtolower($matches[3][0]);
					$str	=	$matches[4][0];

					switch($statement)
					{
						case 'unless':
						case 'if':
							$level++;
							if(is_array($tags) && (!is_null($tags[$tag])) && (
								$oper	==	''	&& $tags[$tag] ||
								$oper	==	'=' && $tags[$tag]==$str ||
								$oper	==	'!' && $tags[$tag]!=$str ||
								$oper	==	'>' && $tags[$tag]>$str ||
								$oper	==	'<' && $tags[$tag]<$str)
							)
							{
								$cstat[$level]	=	($statement	==	'if')	?	$cstat[$level-1]	:	0;
							}
							else
							{
								$cstat[$level]	=	($statement	==	'unless')	?	$cstat[$level-1]	:	0;
							}
							break;
						case 'endif':
							if($level>0)
								$level--;
							break;
						case 'else':
							if($level>0)
								$cstat[$level]	=	$cstat[$level-1]	?	$cstat[$level]	?	0	:	1	:	0;
							break;
						default:
							if($cstat[$level])
								$text	.=	$tags[$tag];
							break;
					}
				}
			}
			
			
			$this->data = $text;

			return $text;
		}
	}
?>
