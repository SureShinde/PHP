<?php
	/*
	 * Thumbnail.inc.php
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

	class Thumbnail
	{
		var $source_file;
		var $dest_file;

		var $old_image;
		var $new_image;

		var $old_dimensions;
		var $new_dimensions;

		var $max_width;
		var $max_height;

		var $size;

		var $mime;

		var $type;

		var $scale;

		var $crop;
		
		var $frame;

		var $errors;

		public function __construct()
		{
			$this->source_file	= "";
			$this->dest_file	= "";

			$this->old_image;
			$this->new_image;

			$this->old_dimensions	= array();
			$this->new_dimensions	= array();

			$this->max_width	= 0;
			$this->max_height	= 0;

			$this->size	= 0;

			$this->mime	= "";

			$this->type	= "";

			$this->scale	= 0;

			$this->crop		= false;

			$this->frame	= false;

			$this->errors	= array();

			ini_set("memory_limit", "256M");
			ini_set('max_execution_time',6000);
			ini_set('max_input_time',6000);
			ini_set('output_buffering',4096);

			set_time_limit(0);

		}

		/**
		 * Function used to Scale an Image
		 *
		 * @param string $source_file Source file to be scaled.
		 * @param string $dest_file Target file name where scaled image to be saved.
		 * @param integer $scale Percentage(%) of Scale
		 *
		 */
		public function scale($source_file, $dest_file, $scale = 0)
		{
			return $this->create($source_file, $dest_file, 0, 0, $scale, false);
		}


		/**
		 * Function used to Crop an Image
		 *
		 * @param string $source_file Source file to be scaled.
		 * @param string $dest_file Target file name where scaled image to be saved.
		 * @param integer $max_width Required Width of an image to be cropped.
		 * @param integer $max_height Required Height of an image to be cropped.
		 *
		 */
		public function crop($source_file, $dest_file, $max_width = 0, $max_height = 0)
		{
			return $this->create($source_file, $dest_file, $max_width, $max_height, 0, true);
		}

		/**
		 * Function used to Resize an Image
		 *
		 * @param string $source_file Source file to be scaled.
		 * @param string $dest_file Target file name where scaled image to be saved.
		 * @param integer $max_width Required Width of an image to be resized.
		 * @param integer $max_height Required Height of an image to be resized.
		 *
		 */
		public function resize($filename, $width = 0, $height = 0)
		{
			$framework = (isset($_REQUEST['framework'])) ? $_REQUEST['framework'] : CURRENT_FRAMEWORK;
					
			if (!file_exists(DIR_PATH . $filename) || !is_file(DIR_PATH . $filename)) {
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
						
			$this->generate(DIR_PATH . $old_image, DIR_PATH. $new_image, $width, $height, true);
						
			return HTTP_PATH . $new_image;
		}
		
		public function resizeCopy($filename, $width = 0, $height = 0, $src_dir = '', $dst_dir = '')
		{
			$framework = (isset($_REQUEST['framework'])) ? $_REQUEST['framework'] : CURRENT_FRAMEWORK;
			
			$this->src_dir = DIR_IMAGE.$src_dir;
			$this->dst_dir = DIR_IMAGE.$dst_dir;			
			
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
				
				$this->generate($this->src_dir . $old_image, $this->dst_dir . $new_image, $width, $height, true);
			}
		 	return 0;
		}
		
		function resizeNoImage($filename, $width = 0, $height = 0, $type = '')
		{
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
			
			$this->generate(DIR_PATH . $old_image, DIR_PATH . $new_image, $width, $height, true);
			
			return HTTP_PATH . $new_image;
		}
		
		public function generate($source_file, $dest_file, $width = 0, $height = 0, $frame = true)
		{
			return  $this->create($source_file, $dest_file, $width, $height, 0, false, $frame);
		}

		public function create($source_file, $dest_file, $max_width = 0, $max_height = 0, $scale = 0, $crop = false, $frame = false)
		{
			// Check If File Exists
			if (!file_exists($source_file))
			{
				$this->errors[] = "File doesn't exists.";
				return false;
			}
			else if (!is_readable($source_file))
			{
				$this->errors[] = "File is not readable.";
				return false;
			}

			// Set values
			$this->source_file	 	= $source_file;
			$this->dest_file	 	= $dest_file;

			$this->max_width  	= $max_width;
			$this->max_height 	= $max_height;

			$this->scale  		= $scale;
			$this->crop	  		= $crop;
			$this->frame		= $frame;

			// Set Image Properties
			$this->setProperties($source_file);

			// Check Image Size
			if($this->size == 0)
			{
				$this->errors[] = "File is Empty.";
				return false;
			}

			// Check Image Type
			if ($this->type == "UNKNOWN")
			{
				$this->errors[] = "Unsupprorted file format.";
				return false;
			}

			return $this->generateImage();
		}

		private function setProperties($source_file)
		{
			// Get Image Properties
			$image_property = getImageSize($source_file);

			// Set Old Dimensions
			$this->setDimensions($image_property[0],$image_property[1]);

			// Get New Dimensions
			$this->new_dimensions  = $this->getDimensions($image_property[0], $image_property[1]);

			// Set Image Size
			$this->size = $image_property['bits'];

			// Set Image Mime Type
			$this->mime	= $image_property['mime'];

			// Set Image Type
			switch($image_property[2])
			{
				case 1:
					// GIF Image
					$this->type = "GIF";
					break;
				case 2:
					// JPG Image
					$this->type = "JPG";
					break;
				case 3:
					// PNG Image
					$this->type = "PNG";
					break;
				case 6:
					// BMP Image
					//$this->type = "BMP";
					//break;
				case 4:
					// SWF Image
				case 5:
					// PSD Image
				case 7:
					// TIFF Image
				default :
					$this->type = "UNKNOWN";
			}
		}

		// Convert to Dimensions
		private function toDimensions($width, $height)
		{
			return array(
						 "width" 	=> intval($width),
						 "height" 	=> intval($height)
						 );
		}

		// Set Old Dimensions
		private function setDimensions($width, $height)
		{
			$this->old_dimensions = $this->toDimensions($width, $height);
		}

		// Calculate New Dimensions From Width
		private function getDimensionsFromWidth($width, $height)
		{
			$new_width  = $this->max_width;
			$new_wp     = (100 * $new_width) / $width;
			$new_height = ($height * $new_wp) / 100;

			return $this->toDimensions($new_width, $new_height);
		}

		// Calculate New Dimensions From Height
		private function getDimensionsFromHeight($width, $height)
		{
			$new_height = $this->max_height;
			$new_hp     = (100 * $new_height) / $height;
			$new_width  = ($width * $new_hp) / 100;

			return $this->toDimensions($new_width, $new_height);
		}

		// Calculate New Dimensions From Scale
		private function getDimensionsFromScale($width, $height)
		{
			$new_width  = ($width * $this->scale) / 100;
			$new_height = ($height * $this->scale) / 100;

			return $this->toDimensions($new_width, $new_height);
		}

		// Calculate New Dimensions From Zoom
		private function getDimensionsFromZoom($width, $height) // Added On 11/01/2012
		{
			$new_width  = $this->max_width;
			$new_height = $this->max_height;
			
			$hd = $new_height - $height;
			$wd = $new_width - $width;
			
			$hs = $new_height / $height;
			$ws = $new_width / $width;
			
			if($hd < $wd)
			{
				$new_width = $width * $hs; 
			}
			else
			{
				$new_height = $height * $ws;
			}

			return $this->toDimensions($new_width, $new_height);
		}

		// Calculate New Image Size
		private function getDimensions($width, $height)
		{
			$new_dimensions = $this->toDimensions($width, $height);

			if ($this->scale > 0)
			{
				$new_dimensions = $this->getDimensionsFromScale($width, $height);
			}
			else if($width > $this->max_width || $new_dimensions['width'] > $this->max_height)
			{
				if ($this->max_width > 0 && $new_dimensions['width'] > $this->max_width)
				{
					$new_dimensions = $this->getDimensionsFromWidth($width, $height);

					if ($this->max_height > 0 && $new_dimensions['height'] > $this->max_height)
					{
						$new_dimensions = $this->getDimensionsFromHeight($new_dimensions['width'], $new_dimensions['height']);
					}
				}
				else if ($this->max_height > 0 && $new_dimensions['height'] > $this->max_height)
				{
					$new_dimensions = $this->getDimensionsFromHeight($width, $height);
				}
			}
			else // Added On 11/01/2012
			{
				$new_dimensions = $this->getDimensionsFromZoom($width, $height);
			}

			return ($new_dimensions);
		}

		private function generateImage()
		{
			if (count($this->errors) > 0)
			{
				return $this->errors;
			}

			$old_width 	= ($this->crop) ? $new_width : $this->old_dimensions['width'];
			$old_height	= ($this->crop) ? $new_height : $this->old_dimensions['height'];
			
			$new_width 	= $this->new_dimensions['width'];
			$new_height	= $this->new_dimensions['height'];			
			
			$max_width = $this->max_width;
			$max_height = $this->max_height;
						
			// Set functions by Type
			switch ($this->type)
			{
				case "GIF":
					$createFunction = "ImageCreateFromGif";
					$copyFunction 	= "ImageGif";
					$alpha = true;
					break;
				case "JPG":
					$createFunction = "ImageCreateFromJpeg";
					$copyFunction 	= "ImageJpeg";
					$alpha = false;
					break;
				case "PNG":
					$createFunction = "ImageCreateFromPng";
					$copyFunction 	= "ImagePng";
					$alpha = true;
					break;
			}

			// Check For GD Support
			if (function_exists("ImageCreateTrueColor"))
			{
				if($this->frame)
					$this->new_image = ImageCreateTrueColor($max_width, $max_height);
				else
					$this->new_image = ImageCreateTrueColor($new_width, $new_height);
			}
			else
			{
				if($this->frame)
					$this->new_image = ImageCreate($max_width, $max_height);
				else
					$this->new_image = ImageCreate($new_width, $new_height);
			}

			// Enable Options For Quality
			if (function_exists("ImageAntiAlias"))
			{
				@ImageAntiAlias($this->new_image, true);
			}
			if (function_exists("ImageAlphaBlending"))
			{
				ImageAlphaBlending($this->new_image, true);
			}
			if (function_exists("ImageSaveAlpha"))
			{
				ImageSaveAlpha($this->new_image, true);
			}

			// Decode Old Image
			$this->old_image = $createFunction($this->source_file);

			// Fill Background
			if (function_exists("ImageFill"))
			{				
				if($alpha || $this->frame)
				{
					// Create Backgrounds
					if (function_exists("ImageColorAllocateAlpha"))
					{
						$black_bg = ImageColorAllocateAlpha($this->new_image, 0, 0, 0,127);
					}

					// Extract Alpha Backgrounds
					if (function_exists("ImageColorTransparent"))
					{
						$alpha_bg = ImageColorTransparent($this->old_image,$black_bg);
					}

					@ImageFill($this->new_image , 0, 0 , $alpha_bg);
				}
				else
				{
					$white_bg = ImageColorAllocate($this->new_image, 255, 255, 255);
					ImageFill($this->new_image , 0, 0 , $white_bg);
				}
			}

			if($this->frame)
			{
				$x_center = (int)(($new_width == $max_width) ? 0 : (($max_width - $new_width) / 2));
				$y_center = (int)(($new_height == $max_height) ? 0 : (($max_height - $new_height) / 2));
				
				/*echo "OLD:".$old_width."X".$old_height."\n";
				echo "MAX:".$max_width."X".$max_height."\n";
				echo "NEW:".$new_width."X".$new_height."\n";
				echo "POS:".$x_center."X".$y_center;*/
				
				ImageCopyResampled ($this->new_image, $this->old_image, $x_center, $y_center, 0, 0, $new_width, $new_height, $old_width, $old_height);
			}
			else
			{
				$x_center = ($this->crop) ? ($this->old_dimensions['width']/2- $this->max_width/2) : 0;
				$y_center = ($this->crop) ? ($this->old_dimensions['height']/2- $this->max_height/2) : 0;
				
				ImageCopyResampled ($this->new_image, $this->old_image, 0, 0, $x_center, $y_center, $new_width, $new_height, $old_width, $old_height);
			}

			if($this->frame)
			{
				$copyFunction = "ImagePng";
			}

			// Create Image
			if (!empty($this->dest_file))
			{
				$pathinfo = pathinfo($this->dest_file);
				
				if(!is_dir($pathinfo['dirname']))
				{
					@mkdir($pathinfo['dirname'],0777,true);
				}
				
				$quality = 100;
				
				if($copyFunction == 'ImagePng') $quality = 9;
				
				$copyFunction($this->new_image, $this->dest_file,$quality);
			}
			else
			{
				header("Content-type: ".$this->mime);
				$copyFunction($this->new_image);
			}

			ImageDestroy($this->new_image);
			ImageDestroy($this->old_image);

			return true;
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
	}
?>
