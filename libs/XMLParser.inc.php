<?php

	/*
	 * XMLParser.inc.php
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

	class XMLParser
	{
		var $xml;
		var $dom;

		var $tables;

		var $webserviceTable;

		function __construct($webserviceTable = '')
		{
			$this->webserviceTable = $webserviceTable;

			$this->dom = new DomDocument();

			$this->dom->preserveWhiteSpace = FALSE;
		}

		function parse($xml = '')
		{
			$this->xml = $xml;

			$this->dom->load($xml);

			$this->tables = $this->dom->getElementsByTagName('tableinfo');

			return $this->fill();
		}

		private function fill()
		{
			$ConfigcolumnArray = array();
			$ConfigtableArray = array();
			$WBtablesArray = array();
			$tablesArray = array();

			$joinKeyArray = array();
			$tableAliasArray = array();
			$singleTableColArray = array();
			$TotalColArray = array();
			$AppSetcolumnArray = array();

			$k=0;
			foreach ($this->tables as $param)
			{
				$parent_table = $this->tables->item($k)->getAttribute('id');
				$framework = $this->tables->item($k)->getAttribute('framework');
				if($parent_table == 'config' || $parent_table == 'settings')
				{
					$Tables = $this->tables->item($k)->getElementsByTagName('table');
					$i=0;
					foreach($Tables as $p)
					{
						$tableNames = $Tables->item($i)->getAttribute('id');
						$Columns = $Tables->item($i)->getElementsByTagName('cname');
						array_push($ConfigtableArray, $tableNames);
						$j=0;
						foreach ($Columns as $p2)
						{
							$CType = $Columns->item($j)->getAttribute('name');
							$column_name = $Columns->item($j)->nodeValue;
							if($parent_table == "config")
							{
								//array_push($ConfigcolumnArray, $column_name);
								$ConfigcolumnArray[$CType] = $column_name;
							}
							else if($parent_table == "config" && $tableNames == 'framework_paths')
							{
								//array_push($ConfigcolumnArray, $column_name);
								$ConfigcolumnArray[$CType] = $column_name;
							}
							else
								array_push($AppSetcolumnArray, $column_name);
							$j++;
						}
						$i++;
					}
				}

				if($parent_table == $this->webserviceTable)
				{
					$Tables 					= $this->tables->item($k)->getElementsByTagName('table');

					$condition 					= $this->tables->item($k)->getAttribute('condition');
					$dateaddedcondition 		= $this->tables->item($k)->getAttribute('dateaddedcondition');
					$datemodifiedcondition 		= $this->tables->item($k)->getAttribute('datemodifiedcondition');
					$orderbycondition 			= $this->tables->item($k)->getAttribute('orderbycondition');
					$dateavailablecondition 	= $this->tables->item($k)->getAttribute('dateavailablecondition');
					$productidcondition 		= $this->tables->item($k)->getAttribute('productidcondition');
					$categoryidcondition 		= $this->tables->item($k)->getAttribute('categoryidcondition');
					$deviceidcondition 			= $this->tables->item($k)->getAttribute('deviceidcondition');
					$groupbycondition 			= $this->tables->item($k)->getAttribute('groupbycondition');
					$namesearchcond 			= $this->tables->item($k)->getAttribute('namesearchcond');
					$metadessearchcond 			= $this->tables->item($k)->getAttribute('metadessearchcond');
					$metakeysearchcond 			= $this->tables->item($k)->getAttribute('metakeysearchcond');

					$i=0;
					foreach ($Tables as $p)
					{
						$tableNames = $Tables->item($i)->getAttribute('id');
						$tableAlias = $Tables->item($i)->getAttribute('substring');
						$tableType  = $Tables->item($i)->getAttribute('type');
						array_push($tablesArray, $tableNames);
						array_push($tableAliasArray, $tableAlias);
						$columnArray = array();
						$AliasArray = array();

						$Columns = $Tables->item($i)->getElementsByTagName('cname');
						$j=0;
						foreach ($Columns as $p2)
						{
							$CName = $Columns->item($j)->getAttribute('name');
							$CType = $Columns->item($j)->getAttribute('type');
							$column_name = $Columns->item($j)->nodeValue;
							if($CName != '')
							{
								if($tableType == "single")
								{
									array_push($singleTableColArray, $column_name);
								}
								else
								{
									array_push($AliasArray, $CName);
									array_push($columnArray, $column_name);
								}
								array_push($TotalColArray, $column_name);
							}
							if($CType == "join_key")
							{
								array_push($joinKeyArray, $column_name);
							}
							$j++;
						}
						$WBtablesArray[] = array(
							'table' 		=> $tableNames,
							'tablealias'	=> $tableAlias,
							'columnlist' 	=> $columnArray,
							'alias'			=> $AliasArray
						);
						$i++;
					}
				}
				$k++;
			}
			
			$iTables = array();
			
			foreach($WBtablesArray as $WBtable)
			{
				$count = 0;
				//$iTables[$WBtable['table']]['alias'] = $WBtable['tablealias'];
				foreach($WBtable['columnlist'] as $columns)
				{
					$iTables[$WBtable['table']][$WBtable['alias'][$count++]] = $columns;
				}
			}

			$result =  @array(
				'iTables'			=> $iTables,
				'ConfigcolumnArray'	=> $ConfigcolumnArray,//
				'ConfigtableArray'	=> $ConfigtableArray,//
				'WBtablesArray'		=> $WBtablesArray,//
				'tablesArray'		=> $tablesArray,//
				'joinKeyArray'		=> $joinKeyArray,//
				'tableAliasArray'	=> $tableAliasArray,//
				'singleTableColArray'	=> $singleTableColArray,//
				'TotalColArray'		=> $TotalColArray,//
				'AppSetcolumnArray'	=> $AppSetcolumnArray,//

				'condition'			=> $condition,
				'dateaddedcondition'=> $dateaddedcondition,
				'datemodifiedcondition'	=> $datemodifiedcondition,
				'orderbycondition'	=> $orderbycondition,
				'dateavailablecondition'	=> $dateavailablecondition,
				'productidcondition'=> $productidcondition,
				'categoryidcondition'	=> $categoryidcondition,
				'deviceidcondition'	=> $deviceidcondition,
				'groupbycondition'	=> $groupbycondition,
				'namesearchcond'	=> $namesearchcond,
				'metadessearchcond' => $metadessearchcond,
				'metakeysearchcond'	=> $metakeysearchcond
			);

			return $result;
		}
	}
?>