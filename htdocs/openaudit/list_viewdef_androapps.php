<?php
$query_array=array("headline"=>__("List all Android Software"),
                   "sql"=>"     SELECT id, geraet, produkt, version, datum, hlink 
							FROM androidsoftware
						 ",
                   "sort"=>"produkt",
                   "dir"=>"ASC",
                   "get"=>array("file"=>"list.php",
                                "title"=>__("Systems installed this Version of this Software"),
                                "var"=>array("name"=>"%software_name",
                                             "version"=>"%software_version",
                                             "view"=>"systems_for_software_version",
                                             "headline_addition"=>"%software_name",
                                            ),
                               ),
                   "fields"=>array("20"=>array("name"=>"produkt",
                                               "head"=>__("Name"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

                                   "30"=>array("name"=>"version",
                                               "head"=>__("Version"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

                                   "30"=>array("name"=>"geraet",
                                               "head"=>__("Geraet"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

								   "32"=>array("name"=>"id",
                                               "head"=>__("ListID"),
                                               "show"=>"y",
                                               "link"=>"n",
											   "search"=>"n",
                                              ),

								   "33"=>array("name"=>"datum",
                                               "head"=>__("datum"),
                                               "show"=>"y",
                                               "link"=>"n",
											   "search"=>"n",
                                              ),

								   "36"=>array("name"=>"hlink",
                                               "head"=>__("Storelink"),
                                               "show"=>"y",
                                               "link"=>"y",
											   "sort"=>"y",
                                              ),

							  ),
                  );
?>
