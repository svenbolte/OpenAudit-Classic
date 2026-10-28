<?php
/**********************************************************************************************************
Filename:	list_viewdef_statistic_printer.php

Description:
	
		
Changelog:
	
	[Edoardo]	22/05/2010	New page
	[Edoardo]	07/06/2010	Updated query - Suggestions by jpa
	
**********************************************************************************************************/

$query_array=array("headline"=>__("Statistic for Installed Printer Models (without UNC connections)"),
                   "sql"=>"SELECT other_description, COUNT(*) AS count_item,
                         COUNT(DISTINCT NULLIF(other_linked_pc, '')) AS device_count,
                         GROUP_CONCAT(DISTINCT NULLIF(other_model, '') ORDER BY other_model SEPARATOR ', ') AS driver_names,
                         GROUP_CONCAT(DISTINCT NULLIF(other_p_port_name, '') ORDER BY other_p_port_name SEPARATOR ', ') AS ports,
                         GROUP_CONCAT(DISTINCT NULLIF(other_location, '') ORDER BY other_location SEPARATOR ', ') AS locations,
                         ROUND(100.0 * COUNT(*) / NULLIF((SELECT COUNT(*) FROM other WHERE other_type='printer' AND LEFT(TRIM(COALESCE(other_description,'')),2) <> CHAR(92,92) AND LEFT(TRIM(COALESCE(other_p_port_name,'')),2) <> CHAR(92,92)),0), $round_to_decimal_places) AS percentage
                         FROM other WHERE other_type='printer' AND LEFT(TRIM(COALESCE(other_description,'')),2) <> CHAR(92,92) AND LEFT(TRIM(COALESCE(other_p_port_name,'')),2) <> CHAR(92,92)
                         GROUP BY other_description",
                   "sort"=>"count_item",
                   "dir"=>"DESC",
                   "get"=>array("file"=>"list.php",
                                "title"=>__("Systems installed this Model of this Printer"),
                                "var"=>array("name"=>"%other_description",
                                             "version"=>"",
                                             "view"=>"systems_for_installed_printer_version",
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
                                   "12"=>array("name"=>"device_count", "head"=>__("Devices"), "show"=>"y", "link"=>"n", "search"=>"n"),
                                   "14"=>array("name"=>"driver_names", "head"=>__("Driver Name"), "show"=>"y", "link"=>"n"),
                                   "16"=>array("name"=>"ports", "head"=>__("Port"), "show"=>"y", "link"=>"n"),
                                   "18"=>array("name"=>"locations", "head"=>__("Location"), "show"=>"y", "link"=>"n"),
                                   "30"=>array("name"=>"percentage",
                                               "head"=>__("Percentage"),
                                               "show"=>"y",
                                               "link"=>"n",
                                               "search"=>"n",
                                              ),
                                  ),
                  );
?>
