<?
	/**
	 * Thumbnail.inc.php
	 *
	 * Description 	: Lightweight Thumbnail Utility In PHP
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

			$this->errors	= array();

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
		public function resize($source_file, $dest_file, $max_width = 0, $max_height = 0)
		{
			return $this->create($source_file, $dest_file, $max_width, $max_height, 0, false);
		}

		public function create($source_file, $dest_file, $max_width = 0, $max_height = 0, $scale = 0, $crop = false)
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

			return ($new_dimensions);
		}

		private function generateImage()
		{
			if (count($this->errors) > 0)
			{
				return $this->errors;
			}

			$new_width 	= $this->new_dimensions['width'];
			$new_height	= $this->new_dimensions['height'];

			$old_width 	= ($this->crop) ? $new_width : $this->old_dimensions['width'];
			$old_height	= ($this->crop) ? $new_height : $this->old_dimensions['height'];

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
				$this->new_image = ImageCreateTrueColor($new_width, $new_height);
			}
			else
			{
				$this->new_image = ImageCreate($new_width, $new_height);
			}

			// Enable Options For Quality
			ImageAntiAlias($this->new_image, true);
			ImageAlphaBlending($this->new_image, true);
			ImageSaveAlpha($this->new_image, true);

			// Decode Old Image
			$this->old_image = $createFunction($this->source_file);

			// Create Backgrounds
			$black_bg = ImageColorAllocateAlpha($this->new_image, 0, 0, 0,127);
			$white_bg = ImageColorAllocateAlpha($this->new_image, 255, 255, 255,127);

			// Extract Alpha Backgrounds
			$alpha_bg = ImageColorTransparent($this->old_image,$black_bg);

			// Fill Background
			if($alpha)
			{
				ImageFill($this->new_image , 0, 0 , $alpha_bg);
			}
			else
			{
				ImageFill($this->new_image , 0, 0 , $white_bg);
			}


			$x_center = ($this->crop) ? ($this->old_dimensions['width']/2- $this->max_width/2) : 0;
			$y_center = ($this->crop) ? ($this->old_dimensions['height']/2- $this->max_height/2) : 0;

			ImageCopyResampled ($this->new_image, $this->old_image, 0, 0, $x_center, $y_center, $new_width, $new_height, $old_width, $old_height);

			// Create Image
			if (!empty($this->dest_file))
			{
				$copyFunction($this->new_image, $this->dest_file);
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
	}
?>
