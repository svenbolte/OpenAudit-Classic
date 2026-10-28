<?php
/**********************************************************************************************************
Recent Changes:

[Edoardo]	26/08/2010	Sorted users query and fixed labels (users/groups description to be fixed)
[Edoardo]	01/09/2010	Added "Locked out" in the Users section
					
**********************************************************************************************************/

$query_array=array("name"=>array("name"=>__("Users & Groups"),
                                 "sql"=>"SELECT `system_name` FROM `system` WHERE `system_uuid` = '" . $_GET["pc"] . "'",
                                ),
                   "image"=>"fa-user",
                   "views"=>array("users"=>array(
                                                    "headline"=>__("Users"),
                                                    "sql"=>"SELECT * FROM users WHERE users_uuid = '".$_GET["pc"]."' AND users_timestamp = '".$GLOBAL["system_timestamp"]."' ORDER BY users_name ",
                                                    "image"=>"fa-user",
                                                    "fields"=>array("10"=>array("name"=>"users_name", "head"=>__("Name"),),
                                                                    "20"=>array("name"=>"users_full_name", "head"=>__("Full Name"),),
                                                                    "30"=>array("name"=>"users_sid", "head"=>__("SID"),),
                                                                    "40"=>array("name"=>"users_disabled", "head"=>__("Disabled"),),
                                                                    "50"=>array("name"=>"users_password_changeable", "head"=>__("Password changeable"),),
                                                                    "60"=>array("name"=>"users_password_required", "head"=>__("Password required"),),
                                                                    //"70"=>array("name"=>"ud_description", "head"=>__("Description"),),
                                                                    "80"=>array("name"=>"users_password_expires", "head"=>__("Password expires"),),
																	"90"=>array("name"=>"users_lockout", "head"=>__("Locked Out"),),
                                                                   ),
                                                    ),
                                   "groups"=>array(
                                                    "headline"=>__("Groups"),
                                                    "sql"=>"SELECT * FROM groups WHERE groups_uuid = '".$_GET["pc"]."' AND groups_timestamp = '".$GLOBAL["system_timestamp"]."' ORDER BY groups_name ",
                                                    "table_layout"=>"horizontal",
                                                    "image"=>"fa-users",
                                                    "fields"=>array("10"=>array("name"=>"groups_name", "head"=>__("Name"),),
                                                                    "20"=>array("name"=>"groups_members", "head"=>__("Members"),),
                                                                    //"30"=>array("name"=>"gd_description", "head"=>__("Description"),),
																	"40"=>array("name"=>"groups_sid", "head"=>__("SID"),),
                                                                   ),
                                                    ),
                                ),
                  );
?>
