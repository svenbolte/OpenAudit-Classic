<?php
/**********************************************************************************************************
Module:	list_viewdef_all_network_printers_ipv4.php

**********************************************************************************************************/
$query_array=array("headline"=>__("List All Printers/Networks with IP v4 Portname"),

"sql"=>"SELECT o.*
        FROM other o
        LEFT JOIN system sy
          ON o.other_timestamp = sy.system_timestamp
         AND o.other_linked_pc = sy.system_uuid
        WHERE o.other_type = 'printer'
          AND (o.other_linked_pc = sy.system_uuid OR o.other_linked_pc = '')
          AND (
               o.other_p_port_name REGEXP '(^|[^0-9])((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])\\.){3}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])([^0-9]|$)'
            OR o.other_value REGEXP '(^|[^0-9])((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])\\.){3}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])([^0-9]|$)'
            OR o.other_p_port_name LIKE 'WSD-%'
            OR o.other_p_port_name LIKE 'IP_%'
            OR o.other_p_port_name LIKE 'TCP%'
            OR o.other_p_port_name LIKE 'http%'
            OR o.other_p_port_name LIKE 'ipp%'
          )",
                   "sort"=>"other_network_name",
                   "dir"=>"ASC",
                   "get"=>array("file"=>"system.php",
                                "title"=>__("Go to Printer"),
                                "var"=>array("other"=>"%other_id",
                                             "view"=>"printer",
                                            ),
                               ),
                   "fields"=>array("10"=>array("name"=>"other_linked_pc",
                                               "head"=>__("UUID"),
                                               "show"=>"n",
                                              ),
                                   "20"=>array("name"=>"other_network_name",
                                               "head"=>__("Attached Device"),
                                               "show"=>"y",
                                               "link"=>"y",
                                               "get"=>array("file"=>"system.php",
                                                            "title"=>__("Go to System"),
                                                            "var"=>array("pc"=>"%other_linked_pc",
                                                                         "view"=>"summary",
                                                                        ),
                                                           ),
                                              ),
                                   "30"=>array("name"=>"other_description",
                                               "head"=>__("Description"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),
                                              
                                   "40"=>array("name"=>"other_p_port_name",
                                               "head"=>__("Port"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),
                                   "50"=>array("name"=>"other_p_shared",
                                               "head"=>__("Shared"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),
                                              
                                   "60"=>array("name"=>"other_p_share_name",
                                               "head"=>__("Share Name"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

                                   "62"=>array("name"=>"other_value",
                                               "head"=>__("Value"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

                                   "70"=>array("name"=>"other_location",
                                               "head"=>__("Location"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),
                                   "80"=>array("name"=>"other_model",
                                               "head"=>__("Driver Name"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),
                                   "90"=>array("name"=>"other_first_timestamp",
                                               "head"=>__("First Audit"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),
                                  ),
                  );
?>
