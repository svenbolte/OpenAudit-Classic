<?php
/**********************************************************************************************************
Module: list_viewdef_all_printers_ipv4.php
Purpose: Show shared/network printers. A non-empty share name is the primary criterion;
         the Shared flag is accepted as fallback for inconsistent WMI data.
**********************************************************************************************************/
$query_array=array("headline"=>__("List Shared / Network Printers"),

"sql"=>"SELECT o.*
        FROM other o
        WHERE o.other_type = 'printer'
          AND (
               TRIM(COALESCE(o.other_p_share_name,'')) <> ''
               OR LOWER(TRIM(COALESCE(o.other_p_shared,''))) IN ('true','1','yes','y')
          )",

                   "sort"=>"other_network_name",
                   "dir"=>"ASC",
                   "get"=>array("file"=>"system.php",
                                "title"=>__("Go to Printer"),
                                "var"=>array("other"=>"%other_id",
                                             "view"=>"printer",
                                            ),
                               ),
                   "fields"=>array("10"=>array("name"=>"other_linked_pc","head"=>__("UUID"),"show"=>"n"),
                                   "20"=>array("name"=>"other_network_name","head"=>__("Attached Device"),"show"=>"y","link"=>"y",
                                               "get"=>array("file"=>"system.php","title"=>__("Go to System"),
                                                            "var"=>array("pc"=>"%other_linked_pc","view"=>"summary"))),
                                   "30"=>array("name"=>"other_description","head"=>__("Description"),"show"=>"y","link"=>"n"),
                                   "40"=>array("name"=>"other_p_port_name","head"=>__("Port"),"show"=>"y","link"=>"n"),
                                   "50"=>array("name"=>"other_p_shared","head"=>__("Shared"),"show"=>"y","link"=>"n"),
                                   "60"=>array("name"=>"other_p_share_name","head"=>__("Share Name"),"show"=>"y","link"=>"n"),
                                   "62"=>array("name"=>"other_value","head"=>__("Comment"),"show"=>"y","link"=>"n"),
                                   "70"=>array("name"=>"other_location","head"=>__("Location"),"show"=>"y","link"=>"n"),
                                   "80"=>array("name"=>"other_model","head"=>__("Driver Name"),"show"=>"y","link"=>"n"),
                                   "90"=>array("name"=>"other_timestamp","head"=>__("Audit"),"show"=>"y","link"=>"n"),
                                  ),
                  );
?>
