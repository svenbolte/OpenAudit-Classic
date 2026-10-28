<?php

$query_array=array("name"=>array("name"=>__("IIS Settings"),
                                 "sql"=>"SELECT `system_name` FROM `system` WHERE `system_uuid` = '" . $_GET["pc"] . "'",
                                ),
                   "views"=>array("iis"=>array(
                                                "headline"=>__("IIS server"),
                                                "sql"=>"SELECT iis_version FROM system WHERE system_uuid = '" . $pc . "' OR system_name = '" . $pc . "' ",
                                                "image"=>"fa-circle-o",
                                                "fields"=>array("10"=>array("name"=>"iis_version", "head"=>__("IIS version"),),
                                                                   ),
                                                    ),

                                  "iis_2"=>array(
                                                 "headline"=>__("IIS installed features"),
                                                 "sql"=>"SELECT * FROM service 
                                                         WHERE service_uuid = '".$pc."' AND service_timestamp = '".$GLOBAL["system_timestamp"]."' 
                                                               AND (service_name = 'W3SVC' OR service_name = 'MSFTPSVC' OR service_name = 'SMTPSVC' 
                                                                    OR service_name = 'NNTPSVC' OR service_name = 'BITS' ) 
                                                         ORDER BY service_display_name",
                                                 "image"=>"fa-cog",
                                                 "table_layout"=>"horizontal",
                                                 "fields"=>array("10"=>array("name"=>"service_display_name", "head"=>__("Service Display Name"),),
                                                                 "20"=>array("name"=>"service_name", "head"=>__("Service Name"),),
                                                                 "30"=>array("name"=>"service_start_mode", "head"=>__("Start Mode"),),
                                                                 "40"=>array("name"=>"service_state", "head"=>__("State"),),
                                                                ),
                                                 ),

                                  "iis_web_ext"=>array(
                                                       "headline"=>__("Web Service Extensions"),
                                                       "sql"=>"SELECT * FROM iis_web_ext WHERE iis_web_ext_uuid = '" . $_GET["pc"] . "' AND iis_web_ext_timestamp = '".$GLOBAL["system_timestamp"]."' 
                                                               ORDER BY iis_web_ext_desc",
                                                       "image"=>"fa-globe",
                                                       "table_layout"=>"horizontal",
                                                       "fields"=>array("10"=>array("name"=>"iis_web_ext_desc", "head"=>__("Description"),),
                                                                       "20"=>array("name"=>"iis_web_ext_path", "head"=>__("Path"),),
                                                                       "30"=>array("name"=>"iis_web_ext_access", "head"=>__("Access"),),
                                                                      ),
                                                       ),
 
                                  "iis_web"=>array(
                                                   "headline"=>__("Web Sites"),
                                                   "sql"=>"SELECT * FROM iis 
                                                           WHERE iis_uuid = '".$_GET["pc"]."' AND iis_timestamp = '".$GLOBAL["system_timestamp"]."' 
                                                           ORDER BY iis_site ",
                                                   "image"=>"fa-circle-o",
                                                   "fields"=>array("10"=> array("name"=>"iis_site", "head"=>__("Site ID"),),
                                                                   "20"=> array("name"=>"iis_description", "head"=>__("Description"),),
                                                                   "30"=> array("name"=>"iis_site_state", "head"=>__("Site State"),),
                                                                   "40"=> array("name"=>"iis_home_directory", "head"=>__("Home Directory"),),
                                                                   "50"=> array("name"=>"iis_directory_browsing", "head"=>__("Directory Browsing"),),
                                                                   "60"=> array("name"=>"iis_default_documents", "head"=>__("Default Documents"),),
                                                                   "70"=> array("name"=>"iis_logging_enabled", "head"=>__("Logging Enabled"),),
                                                                   "80"=> array("name"=>"iis_logging_format", "head"=>__("Logging Format"),),
                                                                   "90"=> array("name"=>"iis_logging_time_period", "head"=>__("Logging Period"),),
                                                                   "100"=>array("name"=>"iis_logging_dir", "head"=>__("Logging Directory"),),
                                                                   "110"=>array("name"=>"iis_site_app_pool", "head"=>__("Application Pool"),),
                                                                   "120"=>array("name"=>"iis_site_anonymous_user", "head"=>__("Anonymous User"),),
                                                                   "130"=>array("name"=>"iis_site_anonymous_auth", "head"=>__("Anonymous Auth. Enabled"),),
                                                                   "140"=>array("name"=>"iis_site_basic_auth", "head"=>__("Basic Auth. Enabled"),),
                                                                   "150"=>array("name"=>"iis_site_ntlm_auth", "head"=>__("NTLM Auth. Enabled"),),
                                                                   "160"=>array("name"=>"iis_site_ssl_en", "head"=>__("SSL Comm. Enabled"),),
                                                                   "170"=>array("name"=>"iis_site_ssl128_en", "head"=>__("SSL 128 bit Enabled"),),
                                                                   "180"=>array("name"=>"iis_secure_ip", "head"=>__("SSL IP address"),),
                                                                   "190"=>array("name"=>"iis_secure_port", "head"=>__("SSL Port"),),
                                                                   "200"=>array("name"=>"IP_Settings", "head"=>__("IP Settings"),
                                                                                "get"=>array("head"=>__("Click me!"),
                                                                                             "file"=>"list.php",
                                                                                             "title"=>__("Click me!"),
                                                                                             "var"=>array("pc"=>"%iis_uuid",
                                                                                                          "view"=>"iis_ips_for_system",
                                                                                                          "iis_site"=>"%iis_site",
                                                                                                         ),
                                                                                            ),
                                                                                ),
                                                                   "210"=>array("name"=>"Virtual_Directories",
                                                                                "head"=>__("Virtual Directories"),
                                                                                "get"=>array("head"=>__("Click me!"),
                                                                                             "file"=>"list.php",
                                                                                             "title"=>__("Click me!"),
                                                                                             "var"=>array("pc"=>"%iis_uuid",
                                                                                                          "view"=>"iis_vhosts_for_system",
                                                                                                          "iis_site"=>"%iis_site",
                                                                                                         ),
                                                                                            ),
                                                                                ),

                                                                  ),
                                                ),
                                 ),
                  );
?>
