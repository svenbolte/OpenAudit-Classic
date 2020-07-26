<?php

$query_array=array("headline"=>__("List Systems and Services with Service User Logon details."),
                   "sql"=>"SELECT * FROM service, system, service_details WHERE  service_uuid  = system_uuid AND service_timestamp = system_timestamp AND sd_display_name = service_display_name ",
                   "sort"=>"system_name",
                   "dir"=>"ASC",
                   "get"=>array("file"=>"system.php",
                                "title"=>"Go to System",
                                "var"=>array("pc"=>"%system_uuid",
                                             "view"=>"summary",
                                            ),
                               ),
                   "fields"=>array("10"=>array("name"=>"system_uuid",
                                               "head"=>__("UUID"),
                                               "show"=>"n",
                                              ),
                                   "20"=>array("name"=>"net_ip_address",
                                               "head"=>__("IP"),
                                               "show"=>"y",
                                               "link"=>"y",
                                              ),
                                   "30"=>array("name"=>"system_name",
                                               "head"=>__("Hostname"),
                                               "show"=>"y",
                                               "link"=>"y",
                                              ),
									"35"=>array("name"=>"service_name",
                                               "head"=>__("Service Name"),
                                               "show"=>"y",
                                               "link"=>"y",
                                              ),
                                   "40"=>array("name"=>"service_start_mode",
                                               "head"=>__("Start Mode"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),
                                   "50"=>array("name"=>"service_state",
                                               "head"=>__("State"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),
                                   "60"=>array("name"=>"service_started",
                                               "head"=>__("Started"),
                                               "show"=>"n",
                                               "link"=>"n",
                                              ),
                                   "70"=>array("name"=>"service_start_name",
                                               "head"=>__("Logon As"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),
                                   "80"=>array("name"=>"",
                                               "head"=>__("Descr."),
                                               "show"=>"y",
                                               "link"=>"n",
                                               "sort"=>"n",
                                               "search"=>"n",
                                               "help"=>"%sd_description",
                                              ),
                                  ),
                  );
?>