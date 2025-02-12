<?php

$query_array=array("headline"=>__("List all LENOVO Systems Warranty"),
                   "sql"=>"SELECT * FROM `system`, `processor`, `hard_drive` WHERE(system_vendor = 'LENOVO' OR system_vendor = 'LENOVO') AND system_uuid = hard_drive_uuid AND system_uuid = processor_uuid AND hard_drive_uuid = processor_uuid AND system_timestamp = processor_timestamp AND system_timestamp = hard_drive_timestamp AND processor_device_id = 'CPU0' AND hard_drive_index = 0 ",
                   "sort"=>"system_name",
                   "dir"=>"ASC",
                   "get"=>array("file"=>"system.php",
                                "title"=>__("Go to System"),
                                "var"=>array("pc"=>"%system_uuid",
                                             "view"=>"summary",
                                            ),
                               ),
                   "fields"=>array("10"=>array("name"=>"system_uuid",
                                               "head"=>__("UUID"),
                                               "show"=>"n",
                                              ),
                                   "30"=>array("name"=>"system_name",
                                               "head"=>__("Hostname"),
                                               "show"=>"y",
                                               "link"=>"y",
                                              ),
									"40"=>array("name"=>"net_user_name",
                                               "head"=>__("Network User"),
                                               "show"=>"y",
                                               "link"=>"y",
                                              ),
                                   "50"=>array("name"=>"system_vendor",
                                               "head"=>__("Vendor"),
                                               "show"=>"y",
                                              ), 
                                   "120"=>array("name"=>"system_model",
                                               "head"=>__("Model"),
                                               "show"=>"y",
                                              ),           
                                   "70"=>array("name"=>"system_description",
                                               "head"=>__("Description"),
                                               "show"=>$show_description,
                                              ),
                                   "80"=>array("name"=>"system_id_number",
                                               "head"=>__("Serial #"),
                                               "show"=>"y",
                                              ),
                                   "90"=>array("name"=>"lenovo_warranty",
                                               "head"=>__("Warranty"),
											   "lenovo_warranty"=>"y",
											   "calculate"=>"y",
                                               "show"=>"y",
                                              ),
                                   
                                  ),
                  );
?>
