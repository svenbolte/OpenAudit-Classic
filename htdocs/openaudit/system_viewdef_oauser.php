<?php

$query_array=array("name"=>array("name"=>__("User"),
                                 "sql"=>"SELECT `auth_realname` FROM `auth` WHERE `auth_id` = '" . $_GET["user"] . "'",
                                ),
                   "views"=>array("manual"=>array(
                                                    "headline"=>__("Account Data"),
                                                    "sql"=>"SELECT * FROM auth WHERE `auth_id` = '" . $_GET["user"] . "'",
                                                    "image"=>"fa-user",
                                                    "edit"=>"y",
                                                    "fields"=>array("10"=>array("name"=>"auth_id",
                                                                                "show"=>"n",
                                                                               ),
                                                                    "20"=>array("name"=>"auth_username", "head"=>__("Username"), "edit"=>"y",),
                                                                    "30"=>array("name"=>"auth_hash", "head"=>__("Password"), "edit"=>"y","edit_type"=>"password",),
                                                                    "40"=>array("name"=>"auth_realname", "head"=>__("Realname"), "edit"=>"y",),
                                                                    "50"=>array("name"=>"auth_enabled", "head"=>__("Enabled"), "edit"=>"y","edit_type"=>"select_static", "select_static_values"=>array(__("No")=>0,__("Yes")=>1),),
                                                                    "60"=>array("name"=>"auth_admin", "head"=>__("Admin"),"edit"=>"y","edit_type"=>"select_static", "select_static_values"=>array(__("No")=>0,__("Yes")=>1),),
                                                                   ),
                                                ),

                                 ),
                  );
?>
