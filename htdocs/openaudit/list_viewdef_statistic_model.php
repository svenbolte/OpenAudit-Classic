<?php

$query_array=array("headline"=>__("Statistic for Model"),
                   "sql"=>"SELECT system_vendor, system_model,
													COUNT( system_uuid ) AS count_item,
													round( 100 /
													(
													SELECT count(system_uuid)
													FROM system  WHERE system_model != ''
													) * COUNT( system_uuid ), 2 ) AS percentage
													FROM system
													GROUP BY system_model",
                   "sort"=>"count_item",
                   "dir"=>"DESC",
                   "get"=>array("file"=>"list.php",
                                "title"=>__("Systems by model"),
                                "var"=>array("name"=>"%system_model",
                                             "view"=>"systems_for_model",
                                             "headline_addition"=>"%system_model",
                                            ),
                               ),
                   "fields"=>array(
                                   "9"=>array("name"=>"count_item",
                                               "head"=>__("Count"),
                                               "show"=>"y",
                                               "link"=>"n",
                                               "search"=>"n",
                                              ),
									"10"=>array("name"=>"system_model",
                                               "head"=>__("Model"),
                                               "show"=>"y",
                                               "link"=>"y",
                                              ),
									"20"=>array("name"=>"system_vendor",
                                               "head"=>__("Manufacturer"),
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
