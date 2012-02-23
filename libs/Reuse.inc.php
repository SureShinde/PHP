<?php

	/*
	 * Reuse.inc.php
	 *
	 * Created By : sureshinde
	 *
	 * Created On : Sep 07, 2011 11:14:42 PM
	 *
	 * Created Under : Benchmark Catalog
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

	//require_once(dirname(__FILE__)."/Image.inc.php");
	require_once(dirname(__FILE__)."/Thumbnail.inc.php");

	class ModelToolImage  {

		var $src_dir;
		var $dst_dir;

		function __construct()
		{
			$this->src_dir = '';
			$this->dst_dir = '';
		}

		function resize($filename, $width, $height) {
		
		$framework = (isset($_REQUEST['framework'])) ? $_REQUEST['framework'] : CURRENT_FRAMEWORK;
			
			$this->src_dir = DIR_IMAGE;
			$this->dst_dir = DIR_IMAGE;

			if (!file_exists($this->src_dir . $filename) || !is_file($this->src_dir . $filename)) {
				return;
			}

			$info = pathinfo($filename);
			$extension = $info['extension'];

			$old_image = $filename;
			$TmpStr=$this->createRandomPassword(4);
			$image_name_temp=date("YmdHis") . $TmpStr;
			$new_image = 'cache/'.$image_name_temp. '.' . $extension;
			//$new_image = 'cache/' . substr($filename, 0, strrpos($filename, '.')) . '-' . $width . 'x' . $height . '.' . $extension;

			if (!file_exists($this->dst_dir . $new_image) || (filemtime($this->src_dir . $old_image) > filemtime($this->dst_dir . $new_image))) {

				$path = '';

				$directories = explode('/', dirname(str_replace('../', '', $new_image)));

				foreach ($directories as $directory) {
					$path = $path . '/' . $directory;

					if (!file_exists($this->dst_dir . $path)) {
						@mkdir($this->dst_dir . $path, 0777);
					}
				}

				$image = new Image($this->src_dir . $old_image);
				$image->resize($width, $height);
				$image->save($this->dst_dir . $new_image);
			}

			if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
				return HTTPS_IMAGE_PATH . $new_image;
			} else {

				return HTTPS_IMAGE_PATH . $new_image;
			}
		}

		function iresize($filename, $width, $height, $src_dir = NULL, $dst_dir = NULL) {

			if($src_dir)
				$this->src_dir = str_replace("\\","/",$src_dir);
			else
				$this->src_dir = DIR_IMAGE;

			if($dst_dir)
				$this->dst_dir = str_replace("\\","/",$dst_dir);
			else
				$this->dst_dir = DIR_IMAGE;

			if (!file_exists($this->src_dir . $filename) || !is_file($this->src_dir . $filename)) {
				return HTTPS_IMAGE_PATH.$filename;
			}

			$info = pathinfo($filename);
			$extension = $info['extension'];

			$old_image = $filename;
			$new_image = 'cache/' . substr($filename, 0, strrpos($filename, '.')) . '-' . $width . 'x' . $height . '.' . $extension;

			if (!file_exists($this->dst_dir . $new_image) || (filemtime($this->src_dir . $old_image) > filemtime($this->dst_dir . $new_image))) {

				$path = '';

				$directories = explode('/', dirname(str_replace('../', '', $new_image)));

				foreach ($directories as $directory) {
					$path = $path . '/' . $directory;

					if (!file_exists($this->dst_dir . $path)) {
						@mkdir($this->dst_dir . $path, 0777);
					}
				}

				$image = new Image($this->src_dir . $old_image);
				$image->resize($width, $height);
				$image->save($this->dst_dir . $new_image);
			}

			if($dst_dir)
				return $new_image;
			else
				return HTTPS_IMAGE_PATH . $new_image;
		}

		function resizeNew($filename, $width, $height) {
		$framework = (isset($_REQUEST['framework'])) ? $_REQUEST['framework'] : CURRENT_FRAMEWORK;

			//$DIR_PATH = '/var/www/html/shoppingcart/webservices/';
			//$HTTP_PATH = 'http://www.ocatalog.com/shoppingcart/webservices/';

			$DIR_PATH = DIR_PATH;
			$HTTP_PATH = HTTP_PATH;
			if (!file_exists($DIR_PATH . $filename) || !is_file($DIR_PATH . $filename)) {
				return;
			}

			$file_path_info = explode("/",$filename);

			$info = pathinfo($filename);
			$extension = $info['extension'];
			$old_image = $filename;
			$position = strpos($filename, "appsettings");					
			if($position == "" || $position == -1)
			{
				if($framework == 'PrestaShop')
				$new_image = 'upload/'.strtolower($framework).'/hotdeal/smallImage/hotdeal-'. $width . 'x' . $height . '.'. $extension;
				else if($framework == 'OpenCart' || $framework == 'OSCommerce' || $framework == 'Magento')
				{
				$str_path=@explode(".",$file_path_info[4]);
				$new_image = 'upload/'.strtolower($framework).'/hotdeal/smallImage/'.$str_path[0].'-' . $width . 'x' . $height . '.' . $extension;
				}
				else
				$new_image = 'upload/hotdeal/smallImage/'.$file_path_info[3].'-' . $width . 'x' . $height . '.' . $extension;
			}
			else
			{
				if($framework == 'PrestaShop')
				$new_image = 'upload/'.strtolower($framework).'/appsettings/smallImage/appsettings-'. $width . 'x' . $height . '.'.$extension;
				else if($framework == 'OpenCart' || $framework == 'OSCommerce' || $framework == 'Magento')
				{
				$str_path=@explode(".",$file_path_info[4]);
				$new_image = 'upload/'.strtolower($framework).'/appsettings/smallImage/'.$str_path[0].'-' . $width . 'x' . $height . '.' . $extension;
				}
				else
				$new_image = 'upload/appsettings/smallImage/'.$file_path_info[3].'-' . $width . 'x' . $height . '.' . $extension;
			}
		
		
			//echo $DIR_PATH . $new_image;exit;
			$image = new Image($DIR_PATH . $old_image);
			$image->resize($width, $height);
			$image->save($DIR_PATH. $new_image);

			if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
				return $HTTP_PATH . $new_image;
			} else {

				return $HTTP_PATH . $new_image;
			}
		}

		function NoImageresizeNew($filename, $width, $height, $type) {

		$framework = (isset($_REQUEST['framework'])) ? $_REQUEST['framework'] : CURRENT_FRAMEWORK;

			$DIR_PATH = DIR_PATH;
			$HTTP_PATH = HTTP_PATH;

			if (!file_exists($DIR_PATH . $filename) || !is_file($DIR_PATH . $filename)) {
				return;
			}

			$file_path_info = explode("/",$filename);

			$info = pathinfo($filename);
			$extension = $info['extension'];
			

			$old_image = $filename;
			$position = strpos($filename, "appsettings");

			if($position == "" || $position == -1)
			{
			 if($framework == 'PrestaShop')
			  $new_image = 'upload/'.strtolower($framework ).'/hotdeal/smallImage/no_image-' . $width . 'x' . $height .'.' . $extension;
			  else if($framework  == 'OpenCart' || $framework  == 'OSCommerce' || $framework  == 'Magento')
				{
				$str_path=@explode(".",$file_path_info[4]);
				$new_image = 'upload/'.strtolower($framework).'/hotdeal/smallImage/'.$str_path[0].'-' . $width . 'x' . $height . '.' . $extension;
				}
			 else
			 $new_image = 'upload/hotdeal/smallImage/'.$file_path_info[3].'-' . $width . 'x' . $height . '.' . $extension;
			}
			else
			{
			if($framework  == 'PrestaShop')
				$new_image = 'upload/'.strtolower($framework).'/appsettings/smallImage/no_image-' . $width . 'x' . $height .'.' . $extension;
			else if($framework  == 'OpenCart' || $framework  == 'OSCommerce' || $framework  == 'Magento')
			{
				$str_path=@explode(".",$file_path_info[4]);
				$new_image = 'upload/'.strtolower($framework ).'/appsettings/smallImage/'.$str_path[0].'-' . $width . 'x' . $height . '.' . $extension;
			}
			else
			$new_image = 'upload/appsettings/smallImage/'.$type."_".$file_path_info[3].'-' . $width . 'x' . $height . '.' . $extension;
			}

			$image = new Image($DIR_PATH . $old_image);
			$image->resize($width, $height);
			$image->save($DIR_PATH . $new_image);
			if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
				return $HTTP_PATH . $new_image;
			} else {

				return $HTTP_PATH . $new_image;
			}
		}

		function smart_resize_image($filename, $width, $height) {
			if (!file_exists(DIR_IMAGE . $filename) || !is_file(DIR_IMAGE . $filename)) {
				return;
			}

			$info = pathinfo($filename);
			$extension = $info['extension'];

			$old_image = $filename;
			$new_image = 'cache/' . substr($filename, 0, strrpos($filename, '.')) . '-' . $width . 'x' . $height . '.' . $extension;

			if (!file_exists(DIR_IMAGE . $new_image) || (filemtime(DIR_IMAGE . $old_image) > filemtime(DIR_IMAGE . $new_image))) {
				$path = '';

				$directories = explode('/', dirname(str_replace('../', '', $new_image)));

				foreach ($directories as $directory) {
					$path = $path . '/' . $directory;

					if (!file_exists(DIR_IMAGE . $path)) {
						@mkdir(DIR_IMAGE . $path, 0777);
					}
				}

				$image = new Image(DIR_IMAGE . $old_image);
				$image->smart_resize_image($width, $height);
				$image->save(DIR_IMAGE . $new_image);
			}

			if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
				return HTTPS_IMAGE_PATH . $new_image;
			} else {
				return HTTPS_IMAGE_PATH . $new_image;
			}
		}

		function thumb($filename,$d_filename,$th_width, $th_height, $forcefill=1)
		{
			list($width, $height) = getimagesize($filename);
			$source = imagecreatefromjpeg($filename);

			if($th_height=="")
			{
				imagejpeg($source,$d_filename);
				ImageDestroy ($source);
				return true;
			}
			if($width > $th_width || $height > $th_height)
			{
				$a = $th_width/$th_height;
				$b = $width/$height;

				if(($a > $b)^$forcefill)
				{
					$src_rect_width  = $a * $height;
					$src_rect_height = $height;
					if(!$forcefill)
					{
						$src_rect_width = $width;
						$th_width = $th_height/$height*$width;
					}
				}
				else
				{
					$src_rect_height = $width/$a;
					$src_rect_width  = $width;
					if(!$forcefill)
					{
						$src_rect_height = $height;
						$th_height = $th_width/$width*$height;
					}
				}

				$src_rect_xoffset = ($width - $src_rect_width)/2* intval($forcefill);
				$src_rect_yoffset = ($height - $src_rect_height)/2* intval($forcefill);

				$thumb  = imagecreatetruecolor($th_width, $th_height);
				imagecopyresampled($thumb, $source, 0, 0, $src_rect_xoffset, $src_rect_yoffset, $th_width, $th_height, $src_rect_width, $src_rect_height);

				imagejpeg($thumb,$d_filename);
				ImageDestroy ($thumb);
				return true;
		   }
		   else
		   {

				imagejpeg($source,$d_filename);
				ImageDestroy ($source);
				return true;
		   }
		}

		public function thumbType($filename, $type , $d_filename ,$th_width , $th_height, $forcefill=1, $deviceType)
		{
		 $framework = (isset($_REQUEST['framework'])) ? $_REQUEST['framework'] : CURRENT_FRAMEWORK;
			$file_path_info = explode("/",$filename);
			if($framework == 'PrestaShop' || $framework == 'OpenCart' || $framework == 'OSCommerce' || $framework == 'Magento')
			$extensionfile = $this->file_extension($file_path_info[4]);
			else
			$extensionfile = $this->file_extension($file_path_info[3]);

			$type = $extensionfile;
		
			/*if($deviceType == 1)
			$D_path = 'upload/appsettings/smallImage/'.$file_path_info[3].'-' . 1024 . 'x' . 786 . '.' . $type;
			else if($deviceType == 0)
			$D_path = 'upload/appsettings/smallImage/'.$file_path_info[3].'-' . 320 . 'x' . 480 . '.' . $type;*/
			if($framework == 'PrestaShop')
			$D_path = 'upload/'.strtolower($framework).'/appsettings/smallImage/thumb-'.$th_width . 'x' . $th_height . '.'. $type;
			 else if($framework == 'OpenCart' || $framework == 'OSCommerce' || $framework == 'Magento')
			{
				$str_path=explode(".",$file_path_info[4]);
				$D_path = 'upload/'.strtolower($framework).'/appsettings/smallImage/'.$str_path[0].'-' . $th_width . 'x' . $th_height . '.' . $type;
			}
			else
			$D_path = 'upload/appsettings/smallImage/'.$file_path_info[3].'-' . $th_width . 'x' . $th_height . '.' . $type;

			$d_filename = $D_path;
			
		
			list($width, $height) = @getimagesize($filename);
		

			$type =strtolower($type);

			if($type=="gif")
				$source = @imagecreatefromgif($filename);

			if($type=="jpeg" || $type=="jpg")
				$source = @imagecreatefromjpeg($filename);

			if($type=="png")
				$source = @imagecreatefrompng($filename);

			if($th_height=="")
			{
				if($type=="gif")
					@imagegif($thumb,$d_filename);

				if($type=="jpeg" || $type=="jpg")
					@imagejpeg($source,$d_filename);

				if($type=="png")
					@imagepng($thumb,$d_filename);

				@ImageDestroy ($source);

				return HTTP_PATH . $d_filename;
			}

			if($width > $th_width || $height > $th_height)
			{
				$a = $th_width/$th_height;
				$b = $width/$height;

				if(($a > $b)^$forcefill)
				{
					$src_rect_width  = $a * $height;
					$src_rect_height = $height;
					if(!$forcefill)
					{
					   $src_rect_width = $width;
					   $th_width = $th_height/$height*$width;
					}
				}
				else
				{
					$src_rect_height = $width/$a;
					$src_rect_width  = $width;
					if(!$forcefill)
					{
					   $src_rect_height = $height;
					   $th_height = $th_width/$width*$height;
					}
				}

				$src_rect_xoffset = ($width - $src_rect_width)/2* intval($forcefill);
				$src_rect_yoffset = ($height - $src_rect_height)/2* intval($forcefill);

				$thumb  = @imagecreatetruecolor($th_width, $th_height);
				@imagecopyresampled($thumb, $source, 0, 0, $src_rect_xoffset, $src_rect_yoffset, $th_width, $th_height, $src_rect_width, $src_rect_height);


				if($type=="gif")
					@imagegif($thumb,$d_filename);

				if($type=="jpeg" || $type=="jpg")
					@imagejpeg($thumb,$d_filename);

				if($type=="png")
					@imagepng($thumb,$d_filename);

				ImageDestroy ($thumb);

				return HTTP_PATH . $d_filename;
			}
			else
			{

				if($type=="jpeg" || $type=="jpg")
					@imagejpeg($source,$d_filename);

				if($type=="gif")
					@imagegif($source,$d_filename);

				if($type=="png")
					@imagepng($source,$d_filename);

				@ImageDestroy ($source);				
				return HTTP_PATH . $d_filename;
			}
		}

		function file_extension($filename)
		{
			$path_info = pathinfo($filename);
			return $path_info['extension'];
		}
		function createRandomPassword($lm='')
		{
			$password = "";
				//$possible = "0123456789"; 
			$possible = "ABCBEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"; 
		
			if($lm!='')
			{
				$l = $lm;
			}
			else
			{
				$l = 4;
			}
				$i = 0; 
			
			while ($i < $l) 
			{ 
				$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
			
				if (!strstr($password, $char)) 
				{ 
				$password .= $char;
				$i++;
				}
			}
			return $password;
		}
			function resizeImage($filename, $width, $height,$src_dir,$des_dir) {
			$framework = (isset($_REQUEST['framework'])) ? $_REQUEST['framework'] : CURRENT_FRAMEWORK;

		/*	if($framework == 'Magento')
			{
				$this->src_dir = DIR_PATH."../media/catalog/".WS_TABLE."/";
				$this->dst_dir = DIR_PATH."../media/catalog/".WS_TABLE."/";
			}
			else
			{
				$this->src_dir = DIR_IMAGE.$src_dir;
				$this->dst_dir = DIR_IMAGE.$des_dir;
			}*/
			
			$this->src_dir = DIR_IMAGE.$src_dir;
			$this->dst_dir = DIR_IMAGE.$des_dir;
			
			
			if (!file_exists($this->src_dir . $filename) || !is_file($this->src_dir . $filename)) {
				return;
			}		

			$old_image = $filename;
			$new_image = $filename;
			
			if (!file_exists($this->dst_dir . $new_image) || (filemtime($this->src_dir . $old_image) > filemtime($this->dst_dir . $new_image))) {

				$path = '';

				$directories = explode('/', dirname(str_replace('../', '', $new_image)));

				foreach ($directories as $directory) {
					$path = $path . '/' . $directory;

					if (!file_exists($this->dst_dir . $path)) {
						@mkdir($this->dst_dir . $path, 0777);
					}
				}

				$image = new Image($this->src_dir . $old_image);
				$image->resize($width, $height);
				$image->save($this->dst_dir . $new_image);
			}
		 return 0;
		}		
		
		function resizeProducts($filename, $width, $height) {
		$dir_image = DIR_IMAGE.'p/';
		
		if (!file_exists($dir_image . $filename) || !is_file($dir_image . $filename)) {
			
			return;
		} 		
		$info = pathinfo($filename);
		$extension = $info['extension'];
		
		$old_image = $filename;
		$TmpStr=$this->createRandomPassword(4);
		$image_name_temp=date("YmdHis") . $TmpStr;
		$new_image = 'cache/'.$image_name_temp. '.' . $extension;
		//$new_image = 'cache/' . substr($filename, 0, strrpos($filename, '.')) . '-' . $width . 'x' . $height . '.' . $extension;
		
		if (!file_exists($dir_image . $new_image) || (filemtime($dir_image . $old_image) > filemtime($dir_image . $new_image))) {
			
			$path = '';
			
			$directories = explode('/', dirname(str_replace('../', '', $new_image)));
			
			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;
				
				if (!file_exists($dir_image . $path)) {
					@mkdir($dir_image . $path, 0777);
				}		
			}
			
			$image = new Image($dir_image . $old_image);
			$image->resize($width, $height);
			$image->save($dir_image . $new_image);
		}
		
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
				return HTTPS_IMAGE_PATH.'p/'.$new_image;
		} else {
		
			return HTTPS_IMAGE_PATH.'p/'.$new_image;
		}	
	}


	}
?>