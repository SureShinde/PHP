<?
	/**
	 * Paginaction.inc.php
	 *
	 * Description 	: Lightweight Pagination(With AJAX) Utility In PHP
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
	 *
	 * Usage :
	 *			PHP : require_once("Pagination.inc.php");
	 *
	 *			CSS : <link rel="stylesheet" href="Pagination.inc.css">
	 *
	 *			$startIndex = $incrementBy * ($pageNumber - 1);
	 *			$endIndex = $startIndex + $incrementBy;
	 */

	class Pagination
	{
		var $page_name;
		var $page_style;
		var $query_string;

		var $total_records;
		var $first_record;
		var $last_record;

		var $startIndex;
		var $incrementBy;
		var $endIndex;

		var $total_pages;
		var $first_page;
		var $previous_page;
		var $current_page;
		var $next_page;
		var $last_page;

		var $anchors;

		var $ajax = false;

		public function __construct()
		{
			$this->page_name 	= "";
			$this->page_style 	= "";
			$this->query_string = "";

			$this->total_records 	= 0;
			$this->first_record 	= 0;
			$this->last_record 		= 0;

			$this->startIndex 	= 0;
			$this->incrementBy 	= 0;
			$this->endIndex		= 0;

			$this->total_pages		= 0;
			$this->first_page 		= 0;
			$this->previous_page	= 0;
			$this->current_page	= 0;
			$this->next_page	= 0;
			$this->last_page	= 0;

			$this->anchors	= "";
		}

		/**
		 * Create Pagination
		 *
		 * @param string	pageName 	Pagination Referer
		 *
		 * @param integer	pageNumber 	Current Page Number (Default 1)
		 *
		 * @param integer	totalRecords	Total Records
		 *
		 * @param integer	incrementBy	No. of Records per Page
		 *
		 * @param string	queryString 	Query String to be sent for Next Page
		 *
		 * @param string	pageStyle	Pagination Style
									: 'clean' (Default)
									: 'digg'
									: 'flickr'
		 * @param string	content	Use AJAX Pagination and Replace Contents in Specified Content ID
		 *
		*/
		function create($pageName, $pageNumber, $totalRecords, $incrementBy, $queryString = '', $pageStyle = "clean", $content = "")
		{
			if($totalRecords > 0)
			{
				$this->page_name    =   $pageName;

				$this->page_style   =   $pageStyle;

				$this->query_string =   $queryString;

				$this->current_page =	$pageNumber;

				$this->total_records=   $totalRecords;

				$this->incrementBy  =   $incrementBy;

				if($content != '')
				{
					$this->ajax = true;
					$this->content = $content;
				}

				$this->calculate();

				$this->show();
			}
		}

		// Calculate Pagination Values
		private function calculate()
		{
			// Set Page Request
			$this->query_string = ($this->query_string == '') ? '' : "&".$this->query_string;

			// Calculate Total Page(s)
			$this->total_pages	=	ceil($this->total_records / $this->incrementBy);

			// Modify Page Number if Page Doesn't Exist
			$this->current_page = ($this->total_pages < $this->current_page) ? $this->total_pages : $this->current_page;

			// Calculate Start Index
			$this->startIndex = $this->incrementBy * ($this->current_page - 1);

			// Calculate End Index
			$this->endIndex   =	 $this->startIndex + $this->incrementBy;

			if($this->endIndex >= $this->total_records)
			{
				$this->endIndex = $this->total_records;
			}

			// Set First Page
			$this->first_page = 1;

			// Set Previous Page
			$this->previous_page = $this->current_page - 1;

			// Set Next Page
			$this->next_page = $this->current_page + 1;

			// Calculate Last Page
			if($this->total_records % $this->incrementBy != 0)
			{
				$this->last_page = ((intval($this->total_records / $this->incrementBy))) + 1;
			}
			else
			{
				$this->last_page = ((intval($this->total_records / $this->incrementBy)));
			}

			$this->first_record = $this->startIndex + 1;
			$this->last_record = $this->endIndex;

		}

		// Show Pagination (complete)
		private function show()
		{
			echo "<div class='pagination'>";
				$this->showTotal();
				$this->showAnchor();
			echo "</div>";
		}

		// Show Total Counter
		private function showTotal()
		{
			echo "<span id='".$this->page_style."' class='total'>Showing ".$this->first_record." to ".$this->last_record." of ".$this->total_records." </span>";
		}

		// Show Anchor
		private function showAnchor()
		{
			// Start Anchor
			$this->anchors = "<ul id='".$this->page_style."'>";

			if($this->previous_page < $this->first_page)
			{
				$this->anchors .= "<li class='previous-off'>First</li>";
				$this->anchors .= "<li class='previous-off'>Previous</li>";
			}
			else
			{
				if($this->ajax)
				{
					$this->anchors .= "<li class='next'><a href=\"javascript:pagination($this->first_page,'$this->page_name','$this->query_string','$this->content');\">First </a></li>";
					$this->anchors .= "<li class='next'><a href=\"javascript:pagination($this->previous_page,'$this->page_name','$this->query_string','$this->content');\">Previous </a></li>";
				}
				else
				{
					$this->anchors .= "<li class='next'><a href=\"".$this->page_name."?page=".$this->first_page.$this->query_string."\">First </a></li>";
					$this->anchors .= "<li class='next'><a href=\"".$this->page_name."?page=".$this->previous_page.$this->query_string."\">Previous </a></li>";
				}
			}

			// Start Numbering
			$this->showNumbers();

			// End Numbering
			if($this->next_page > $this->last_page)
			{
				$this->anchors.= "<li class='previous-off'>Next </li>";
				$this->anchors .= "<li class='previous-off'>Last</li>";
			}
			else
			{
				if($this->ajax)
				{
					$this->anchors .= "<li class='next'><a href=\"javascript:pagination($this->next_page,'$this->page_name','$this->query_string','$this->content');\">Next </a></li>";
					$this->anchors .= "<li class='next'><a href=\"javascript:pagination($this->last_page,'$this->page_name','$this->query_string','$this->content');\">Last</a></li>";
				}
				else
				{
					$this->anchors .= "<li class='next'><a href=\"".$this->page_name."?page=".$this->next_page.$this->query_string."\">Next </a></li>";
					$this->anchors .= "<li class='next'><a href=\"".$this->page_name."?page=".$this->last_page.$this->query_string."\">Last</a></li>";
				}
			}

			$this->anchors .= "</ul>";
			// End Anchor

			echo $this->anchors;

		}

		private function showNumbers()
		{
			// Reinitialize anchor
			$anchor     =   "";

			// No. of Pages Left to Current
			$norepeat   =   5;


			// Left Pages
			$counter    =   1;

			for($count = $this->current_page; $count > 1; $count--)
			{
				$this->previous_page = $count - 1;

				$page = ceil($this->previous_page * $this->incrementBy ) - $this->incrementBy;


				if($this->ajax)
				{
					$anchor = "<li><a href=\"javascript:pagination($this->previous_page,'$this->page_name','$this->query_string','$this->content');\">$this->previous_page </a></li>".$anchor;
				}
				else
				{
					$anchor = "<li><a href=\"".$this->page_name."?page=".$this->previous_page.$this->query_string."\">$this->previous_page </a></li>".$anchor;
				}

				if($counter == $norepeat)
					break;
				$counter++;
			}

			$this->anchors .= $anchor;

			// Current Page
			$this->anchors .= "<li class='active'>".$this->current_page."</li>";

			// Right Pages
			$counter = 1;

			for($count = $this->current_page; $count < $this->total_pages; $count++)
			{
				$this->next_page = $count + 1;
				$page = ceil($this->next_page * $this->incrementBy) - $this->incrementBy;

				if($this->ajax)
				{
					$this->anchors .= "<li><a href=\"javascript:pagination($this->next_page,'$this->page_name','$this->query_string','$this->content');\">$this->next_page</a></li>";
				}
				else
				{
					$this->anchors .= "<li><a href=\"".$this->page_name."?page=".$this->next_page.$this->query_string."\">$this->next_page</a></li>";
				}

				if($counter == $norepeat)
					break;

				$counter++;
			}
		}
	}
?>
