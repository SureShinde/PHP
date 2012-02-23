<?php
	/*
	 * Archive.inc.php
	 *
	 * Description 	: Lightweight Archive Generation Utility In PHP
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

	class Archive
	{
		var $archive;
		var $basedir;
		var $name;
		var $prepend;
		var $inmemory;
		var $overwrite;
		var $recurse;
		var $storepaths;
		var $followlinks;
		var $level;
		var $method;
		var $sfx;
		var $type;
		var $comment;

		var $files;
		var $exclude;
		var $storeonly;
		var $error;

		// Default Constructor
		public function __construct()
		{
			$this->basedir 		= ".";
			$this->prepend		= "";
			$this->inmemory		= 0;
			$this->overwrite	= 1;
			$this->recurse		= 1;
			$this->storepaths	= 1;
			$this->followlinks	= 0;
			$this->level	= 3;
			$this->method	= 1;
			$this->sfx		= "";
			$this->type		= "";
			$this->comment	= "";
			$this->files 	= array ();
			$this->exclude 	= array ();
			$this->storeonly= array ();
			$this->error 	= array ();
		}

		public function init($basedir = ".")
		{
			$this->basedir 		= $basedir;
		}

		public function add($list)
		{
			$temp = $this->list_files($list);
			foreach ($temp as $current)
				$this->files[] = $current;
		}

		public function exclude($list)
		{
			$temp = $this->list_files($list);
			foreach ($temp as $current)
				$this->exclude[] = $current;
		}

		public function store($list)
		{
			$temp = $this->list_files($list);
			foreach ($temp as $current)
				$this->storeonly[] = $current;
		}

		public function create($name, $type)
		{
			$this->name	= $name;
			$this->type = $type;
			$this->make_list();

			if ($this->inmemory == 0)
			{
				$pwd = getcwd();

				chdir($this->basedir);

				$tmpname = $this->name. ($this->type == "gzip" || $this->type == "bzip" ? ".tmp" : "");

				if ($this->overwrite == 0 && file_exists($tmpname))
				{
					$this->error[] = "File {$this->name} already exists.";
					chdir($pwd);
					return 0;
				}
				else if ($this->archive = @fopen($tmpname, "wb+"))
				{
					chdir($pwd);
				}
				else
				{
					$this->error[] = "Could not open {$this->name} for writing.";
					chdir($pwd);
					return 0;
				}
			}
			else
				$this->archive = "";

			switch ($this->type)
			{
				case "zip":
					if (!Zip::create())
					{
						$this->error[] = "Could not create zip file.";
						return 0;
					}
					break;
				case "bzip":
					if (!Tar::create())
					{
						$this->error[] = "Could not create tar file.";
						return 0;
					}
					if (!Bzip::create())
					{
						$this->error[] = "Could not create bzip2 file.";
						return 0;
					}
					break;
				case "gzip":
					if (!Tar::create())
					{
						$this->error[] = "Could not create tar file.";
						return 0;
					}
					if (!Gzip::create())
					{
						$this->error[] = "Could not create gzip file.";
						return 0;
					}
					break;
				case "tar":
					if (!Tar::create())
					{
						$this->error[] = "Could not create tar file.";
						return 0;
					}
			}

			if ($this->inmemory == 0)
			{
				fclose($this->archive);
				if ($this->type == "gzip" || $this->type == "bzip")
					unlink($this->basedir . "/" . $this->name . ".tmp");
			}
		}

		public function download()
		{
			chdir($this->basedir);
			if ($this->inmemory == 1)
			{
				$this->error[] = "Can only use download_file() if archive is in memory. Redirect to file otherwise, it is faster.";
				return;
			}
			switch ($this->type)
			{
				case "zip":
					header("Content-Type: application/zip");
					break;
				case "bzip":
					header("Content-Type: application/x-bzip2");
					break;
				case "gzip":
					header("Content-Type: application/x-gzip");
					break;
				case "tar":
					header("Content-Type: application/x-tar");
			}

			$header = "Content-Disposition: attachment; filename=\"";
			$header .= strstr($this->name, "/") ? substr($this->name, strrpos($this->name, "/") + 1) : $this->name;
			$header .= "\"";
			header($header);
			header("Content-Length: " . filesize($this->name));
			header("Content-Transfer-Encoding: binary");
			header("Cache-Control: no-cache, must-revalidate, max-age=60");
			header("Expires: 0");

			readfile($this->name);
		}

		public function delete()
		{
			chdir($this->basedir);
			unlink($this->name);
		}

		protected function list_files($list)
		{
			if (!is_array ($list))
			{
				$temp = $list;
				$list = array ($temp);
				unset ($temp);
			}

			$files = array ();

			$pwd = getcwd();
			chdir($this->basedir);

			foreach ($list as $current)
			{
				$current = str_replace("\\", "/", $current);
				$current = preg_replace("/\/+/", "/", $current);
				$current = preg_replace("/\/$/", "", $current);

				if (strstr($current, "*"))
				{
					$regex = preg_replace("/([\\\^\$\.\[\]\|\(\)\?\+\{\}\/])/", "\\\\\\1", $current);
					$regex = str_replace("*", ".*", $regex);
					$dir = strstr($current, "/") ? substr($current, 0, strrpos($current, "/")) : ".";
					$temp = $this->parse_dir($dir);
					foreach ($temp as $current2)
						if (preg_match("/^{$regex}$/i", $current2['name']))
							$files[] = $current2;
					unset ($regex, $dir, $temp, $current);
				}
				else if (@is_dir($current))
				{
					$temp = $this->parse_dir($current);
					foreach ($temp as $file)
						$files[] = $file;
					unset ($temp, $file);
				}
				else if (@file_exists($current))
				{
					$files[] = array (
										'name' => $current,
										'name2' => $this->prepend .preg_replace("/(\.+\/+)+/", "", ($this->storepaths == 0 && strstr($current, "/")) ? substr($current, strrpos($current, "/") + 1) : $current),
										'type' => @is_link($current) && $this->followlinks == 0 ? 2 : 0,
										'ext' => substr($current, strrpos($current, ".")), 'stat' => stat($current)
									);
				}
			}

			chdir($pwd);

			unset ($current, $pwd);

			usort($files, array ("Archive", "sort_files"));

			return $files;
		}

		protected function add_data($data)
		{
			if ($this->inmemory == 0)
				fwrite($this->archive, $data);
			else
				$this->archive .= $data;
		}

		protected function make_list()
		{
			if (!empty ($this->exclude))
				foreach ($this->files as $key => $value)
					foreach ($this->exclude as $current)
						if ($value['name'] == $current['name'])
							unset ($this->files[$key]);
			if (!empty ($this->storeonly))
				foreach ($this->files as $key => $value)
					foreach ($this->storeonly as $current)
						if ($value['name'] == $current['name'])
							$this->files[$key]['method'] = 0;
			unset ($this->exclude, $this->storeonly);
		}

		protected function parse_dir($dirname)
		{
			if ($this->storepaths == 1 && !preg_match("/^(\.+\/*)+$/", $dirname))
			{
				$files = array (
								array (
									   'name' => $dirname,
									   'name2' => $this->prepend .	preg_replace("/(\.+\/+)+/", "", ($this->storepaths == 0 && strstr($dirname, "/")) ?	substr($dirname, strrpos($dirname, "/") + 1) : $dirname),
									   'type' => 5,
									   'stat' => stat($dirname)
									   )
								);
			}
			else
				$files = array ();

			$dir = @opendir($dirname);

			while ($file = @readdir($dir))
			{
				$fullname = $dirname . "/" . $file;

				if ($file == "." || $file == "..")
					continue;
				else if (@is_dir($fullname))
				{
					if (empty ($this->recurse))
						continue;

					$temp = $this->parse_dir($fullname);

					foreach ($temp as $file2)
						$files[] = $file2;
				}
				else if (@file_exists($fullname))
				{
					$files[] = array (
									  'name' => $fullname,
									  'name2' => $this->prepend . preg_replace("/(\.+\/+)+/", "", ($this->storepaths == 0 && strstr($fullname, "/")) ?	substr($fullname, strrpos($fullname, "/") + 1) : $fullname),
									  'type' => @is_link($fullname) && $this->followlinks == 0 ? 2 : 0,
									  'ext' => substr($file, strrpos($file, ".")), 'stat' => stat($fullname));
				}
			}

			@closedir($dir);

			return $files;
		}

		protected function sort_files($a, $b)
		{
			if ($a['type'] != $b['type'])
				if ($a['type'] == 5 || $b['type'] == 2)
					return -1;
				else if ($a['type'] == 2 || $b['type'] == 5)
					return 1;
			else if ($a['type'] == 5)
				return strcmp(strtolower($a['name']), strtolower($b['name']));
			else if ($a['ext'] != $b['ext'])
				return strcmp($a['ext'], $b['ext']);
			else if ($a['stat'][7] != $b['stat'][7])
				return $a['stat'][7] > $b['stat'][7] ? -1 : 1;
			else
				return strcmp(strtolower($a['name']), strtolower($b['name']));
			return 0;
		}
	}

	class Tar extends Archive
	{
		public function __construct()
		{
			$this->type = "tar";
		}

		public function create()
		{
			$pwd = getcwd();
			chdir($this->basedir);

			foreach ($this->files as $current)
			{
				if ($current['name'] == $this->name)
					continue;
				if (strlen($current['name2']) > 99)
				{
					$path = substr($current['name2'], 0, strpos($current['name2'], "/", strlen($current['name2']) - 100) + 1);
					$current['name2'] = substr($current['name2'], strlen($path));
					if (strlen($path) > 154 || strlen($current['name2']) > 99)
					{
						$this->error[] = "Could not add {$path}{$current['name2']} to archive because the filename is too long.";
						continue;
					}
				}

				$block = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12", $current['name2'], sprintf("%07o",
					$current['stat'][2]), sprintf("%07o", $current['stat'][4]), sprintf("%07o", $current['stat'][5]),
					sprintf("%011o", $current['type'] == 2 ? 0 : $current['stat'][7]), sprintf("%011o", $current['stat'][9]),
					"        ", $current['type'], $current['type'] == 2 ? @readlink($current['name']) : "", "ustar ", " ",
					"Unknown", "Unknown", "", "", !empty ($path) ? $path : "", "");

				$checksum = 0;

				for ($i = 0; $i < 512; $i++)
					$checksum += ord(substr($block, $i, 1));

				$checksum = pack("a8", sprintf("%07o", $checksum));

				$block = substr_replace($block, $checksum, 148, 8);

				if ($current['type'] == 2 || $current['stat'][7] == 0)
					$this->add_data($block);

				else if ($fp = @fopen($current['name'], "rb"))
				{
					$this->add_data($block);
					while ($temp = fread($fp, 1048576))
						$this->add_data($temp);
					if ($current['stat'][7] % 512 > 0)
					{
						$temp = "";
						for ($i = 0; $i < 512 - $current['stat'][7] % 512; $i++)
							$temp .= "\0";
						$this->add_data($temp);
					}
					fclose($fp);
				}
				else
					$this->error[] = "Could not open file {$current['name']} for reading. It was not added.";
			}

			$this->add_data(pack("a1024", ""));

			chdir($pwd);

			return 1;
		}

		public function extract()
		{
			$pwd = getcwd();
			chdir($this->basedir);

			if ($fp = $this->open())
			{
				if ($this->inmemory == 1)
					$this->files = array ();

				while ($block = fread($fp, 512))
				{
					$temp = unpack("a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100symlink/a6magic/a2temp/a32temp/a32temp/a8temp/a8temp/a155prefix/a12temp", $block);
					$file = array (
						'name' => $temp['prefix'] . $temp['name'],
						'stat' => array (
									2 => $temp['mode'],
									4 => octdec($temp['uid']),
									5 => octdec($temp['gid']),
									7 => octdec($temp['size']),
									9 => octdec($temp['mtime']),
								),
						'checksum' => octdec($temp['checksum']),
						'type' => $temp['type'],
						'magic' => $temp['magic'],
					);

					if ($file['checksum'] == 0x00000000)
						break;
					else if (substr($file['magic'], 0, 5) != "ustar")
					{
						$this->error[] = "This script does not support extracting this type of tar file.";
						break;
					}

					$block = substr_replace($block, "        ", 148, 8);
					$checksum = 0;

					for ($i = 0; $i < 512; $i++)
						$checksum += ord(substr($block, $i, 1));

					if ($file['checksum'] != $checksum)
						$this->error[] = "Could not extract from {$this->name}, it is corrupt.";

					if ($this->inmemory == 1)
					{
						$file['data'] = fread($fp, $file['stat'][7]);
						fread($fp, (512 - $file['stat'][7] % 512) == 512 ? 0 : (512 - $file['stat'][7] % 512));
						unset ($file['checksum'], $file['magic']);
						$this->files[] = $file;
					}
					else if ($file['type'] == 5)
					{
						if (!is_dir($file['name']))
							mkdir($file['name'], $file['stat'][2]);
					}
					else if ($this->overwrite == 0 && file_exists($file['name']))
					{
						$this->error[] = "{$file['name']} already exists.";
						continue;
					}
					else if ($file['type'] == 2)
					{
						symlink($temp['symlink'], $file['name']);
						chmod($file['name'], $file['stat'][2]);
					}
					else if ($new = @fopen($file['name'], "wb"))
					{
						fwrite($new, fread($fp, $file['stat'][7]));
						fread($fp, (512 - $file['stat'][7] % 512) == 512 ? 0 : (512 - $file['stat'][7] % 512));
						fclose($new);
						chmod($file['name'], $file['stat'][2]);
					}
					else
					{
						$this->error[] = "Could not open {$file['name']} for writing.";
						continue;
					}
					chown($file['name'], $file['stat'][4]);
					chgrp($file['name'], $file['stat'][5]);
					touch($file['name'], $file['stat'][9]);
					unset ($file);
				}
			}
			else
				$this->error[] = "Could not open file {$this->name}";

			chdir($pwd);
		}

		public function open()
		{
			return @fopen($this->name, "rb");
		}
	}

	class Gzip extends Tar
	{
		public function __construct()
		{
			$this->tar($this->name);
			$this->type = "gzip";
		}

		public function create()
		{
			if ($this->inmemory == 0)
			{
				$pwd = getcwd();
				chdir($this->basedir);
				if ($fp = gzopen($this->name, "wb{$this->level}"))
				{
					fseek($this->archive, 0);
					while ($temp = fread($this->archive, 1048576))
						gzwrite($fp, $temp);
					gzclose($fp);
					chdir($pwd);
				}
				else
				{
					$this->error[] = "Could not open {$this->name} for writing.";
					chdir($pwd);
					return 0;
				}
			}
			else
				$this->archive = gzencode($this->archive, $this->level);

			return 1;
		}

		public function open()
		{
			return @gzopen($this->name, "rb");
		}
	}

	class Bzip extends Tar
	{
		public function __construct()
		{
			$this->tar($this->name);
			$this->type = "bzip";
		}

		public function create()
		{
			if ($this->inmemory == 0)
			{
				$pwd = getcwd();
				chdir($this->basedir);
				if ($fp = bzopen($this->name, "w"))
				{
					fseek($this->archive, 0);
					while ($temp = fread($this->archive, 1048576))
						bzwrite($fp, $temp);
					bzclose($fp);
					chdir($pwd);
				}
				else
				{
					$this->error[] = "Could not open {$this->name} for writing.";
					chdir($pwd);
					return 0;
				}
			}
			else
				$this->archive = bzcompress($this->archive, $this->level);

			return 1;
		}

		public function open()
		{
			return @bzopen($this->name, "rb");
		}
	}

	class Zip extends Archive
	{
		public function __construct()
		{
			$this->type = "zip";
		}

		public function create()
		{
			$files = 0;
			$offset = 0;
			$central = "";

			if (!empty ($this->sfx))
				if ($fp = @fopen($this->sfx, "rb"))
				{
					$temp = fread($fp, filesize($this->sfx));
					fclose($fp);
					$this->add_data($temp);
					$offset += strlen($temp);
					unset ($temp);
				}
				else
					$this->error[] = "Could not open sfx module from {$this->sfx}.";

			$pwd = getcwd();
			chdir($this->basedir);

			foreach ($this->files as $current)
			{
				if ($current['name'] == $this->name)
					continue;

				$timedate = explode(" ", date("Y n j G i s", $current['stat'][9]));
				$timedate = ($timedate[0] - 1980 << 25) | ($timedate[1] << 21) | ($timedate[2] << 16) |
					($timedate[3] << 11) | ($timedate[4] << 5) | ($timedate[5]);

				$block = pack("VvvvV", 0x04034b50, 0x000A, 0x0000, (isset($current['method']) || $this->method == 0) ? 0x0000 : 0x0008, $timedate);

				if ($current['stat'][7] == 0 && $current['type'] == 5)
				{
					$block .= pack("VVVvv", 0x00000000, 0x00000000, 0x00000000, strlen($current['name2']) + 1, 0x0000);
					$block .= $current['name2'] . "/";
					$this->add_data($block);
					$central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->method == 0 ? 0x0000 : 0x000A, 0x0000,
						(isset($current['method']) || $this->method == 0) ? 0x0000 : 0x0008, $timedate,
						0x00000000, 0x00000000, 0x00000000, strlen($current['name2']) + 1, 0x0000, 0x0000, 0x0000, 0x0000, $current['type'] == 5 ? 0x00000010 : 0x00000000, $offset);
					$central .= $current['name2'] . "/";
					$files++;
					$offset += (31 + strlen($current['name2']));
				}
				else if ($current['stat'][7] == 0)
				{
					$block .= pack("VVVvv", 0x00000000, 0x00000000, 0x00000000, strlen($current['name2']), 0x0000);
					$block .= $current['name2'];
					$this->add_data($block);
					$central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->method == 0 ? 0x0000 : 0x000A, 0x0000,
						(isset($current['method']) || $this->method == 0) ? 0x0000 : 0x0008, $timedate,
						0x00000000, 0x00000000, 0x00000000, strlen($current['name2']), 0x0000, 0x0000, 0x0000, 0x0000, $current['type'] == 5 ? 0x00000010 : 0x00000000, $offset);
					$central .= $current['name2'];
					$files++;
					$offset += (30 + strlen($current['name2']));
				}
				else if ($fp = @fopen($current['name'], "rb"))
				{
					$temp = fread($fp, $current['stat'][7]);
					fclose($fp);
					$crc32 = crc32($temp);
					if (!isset($current['method']) && $this->method == 1)
					{
						$temp = gzcompress($temp, $this->level);
						$size = strlen($temp) - 6;
						$temp = substr($temp, 2, $size);
					}
					else
						$size = strlen($temp);
					$block .= pack("VVVvv", $crc32, $size, $current['stat'][7], strlen($current['name2']), 0x0000);
					$block .= $current['name2'];
					$this->add_data($block);
					$this->add_data($temp);
					unset ($temp);
					$central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->method == 0 ? 0x0000 : 0x000A, 0x0000,
						(isset($current['method']) || $this->method == 0) ? 0x0000 : 0x0008, $timedate,
						$crc32, $size, $current['stat'][7], strlen($current['name2']), 0x0000, 0x0000, 0x0000, 0x0000, 0x00000000, $offset);
					$central .= $current['name2'];
					$files++;
					$offset += (30 + strlen($current['name2']) + $size);
				}
				else
					$this->error[] = "Could not open file {$current['name']} for reading. It was not added.";
			}

			$this->add_data($central);

			$this->add_data(pack("VvvvvVVv", 0x06054b50, 0x0000, 0x0000, $files, $files, strlen($central), $offset,
				!empty ($this->comment) ? strlen($this->comment) : 0x0000));

			if (!empty ($this->comment))
				$this->add_data($this->comment);

			chdir($pwd);

			return 1;
		}
	}
?>
