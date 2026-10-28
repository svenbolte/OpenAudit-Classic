<?php
/**********************************************************************************************************
Filename:	list_viewdef_statistic_printer.php

Description:
	
		
Changelog:
	
	[Edoardo]	22/05/2010	New page
	[Edoardo]	07/06/2010	Updated query - Suggestions by jpa
	
**********************************************************************************************************/

$query_array=array("headline"=>__("Statistic for Printer models"),
                   "sql"=>"SELECT other_description, COUNT(*) AS count_item,
                         ROUND(100.0 * COUNT(*) / NULLIF((SELECT COUNT(*) FROM other WHERE other_type='printer'),0), $round_to_decimal_places) AS percentage
                         FROM other WHERE other_type='printer'
                         GROUP BY other_description",
                   "sort"=>"count_item",
                   "dir"=>"DESC",
                   "get"=>array("file"=>"list.php",
                                "title"=>__("Systems installed this Model of this Printer"),
                                "var"=>array("name"=>"%other_description",
                                             "version"=>"",
                                             "view"=>"systems_for_printer_version",
                                             "headline_addition"=>"%other_description",
                                            ),
                               ),
                   "fields"=>array(
                                   "9"=>array("name"=>"count_item",
                                               "head"=>__("Count"),
                                               "show"=>"y",
                                               "link"=>"n",
                                               "search"=>"n",
                                              ),
									"10"=>array("name"=>"other_description",
                                               "head"=>__("Model"),
                                               "show"=>"y",
                                               "link"=>"y",
                                              ),
                                   "30"=>array("name"=>"percentage",
                                               "head"=>__("Percentage"),
                                               "show"=>"y",
                                               "link"=>"n",
                                               "search"=>"n",
                                              ),
                                  ),
                  );
?>
