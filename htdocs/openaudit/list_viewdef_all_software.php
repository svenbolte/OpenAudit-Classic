<?php
$query_array=array("headline"=>__("List all Software"),
                   "sql"=>"
				   SELECT
  COUNT(s.software_name) AS software_count,
  s.software_name,
  sv.sv_bemerkungen,
  sv.sv_lizenztyp,
  sv.sv_version,
  sv.sv_instlocation,
  sv.sv_icondata,
  s.software_version,
  s.software_publisher,
  s.software_url,
  s.software_comment,
  s.software_first_timestamp,
  (1=1) as sv_newer
FROM system sy
JOIN software s
  ON s.software_uuid = sy.system_uuid
 AND s.software_timestamp = sy.system_timestamp
LEFT JOIN softwareversionen sv
  ON (
       (
         REGEXP_REPLACE(LOWER(REPLACE(s.software_name, '(x64)', '')), '[^a-z0-9]+', '')
         LIKE CONCAT('%',
              REGEXP_REPLACE(LOWER(REPLACE(sv.sv_product,  '(x64)', '')), '[^a-z0-9]+', ''),
              '%'
         )
       )
       OR
       (
         REGEXP_REPLACE(LOWER(REPLACE(sv.sv_product,  '(x64)', '')), '[^a-z0-9]+', '')
         LIKE CONCAT('%',
              REGEXP_REPLACE(LOWER(REPLACE(s.software_name, '(x64)', '')), '[^a-z0-9]+', ''),
              '%'
         )
       )
     )
WHERE s.software_name NOT LIKE '%hotfix%'
  AND s.software_name NOT LIKE '%Service Pack%'
  AND s.software_name NOT LIKE '% Edge Update%'
  AND s.software_name NOT LIKE '%MUI (%'
  AND s.software_name NOT LIKE '%Proofing %'
  AND s.software_name NOT LIKE '%Language%'
  AND s.software_name NOT LIKE '%Korrektur%'
  AND s.software_name NOT LIKE '%linguisti%'
  AND s.software_name NOT REGEXP 'SP[1-4]{1,}'
  AND s.software_name NOT REGEXP '[KB|Q][0-9]{6,}'
GROUP BY s.software_name, s.software_version
				   
				   ",
                   "sort"=>"software_name",
                   "dir"=>"ASC",
                   "get"=>array("file"=>"list.php",
                                "title"=>__("Systems installed this Version of this Software"),
                                "var"=>array("name"=>"%software_name",
                                             "version"=>"%software_version",
                                             "view"=>"systems_for_software_version",
                                             "headline_addition"=>"%software_name",
                                            ),
                               ),
                   "fields"=>array("10"=>array("name"=>"software_count",
                                               "head"=>__("Count"),
                                               "show"=>"y",
                                               "link"=>"y",
                                               "sort"=>"y",
                                               "search"=>"n",
                                              ),
                                   "20"=>array("name"=>"software_name",
                                               "head"=>__("Name"),
                                               "show"=>"y",
                                               "link"=>"y",
											   "get"=>array("file"=>"list.php",
                                                            "title"=>__("Systems installed this Software"),
                                                            "var"=>array("name"=>"%software_name",
                                                                         "view"=>"systems_for_software",
                                                                         "headline_addition"=>"%software_name",
                                                                        ),
                                                           ),
                                              ),
                                   "30"=>array("name"=>"software_version",
                                               "head"=>__("Version"),
                                               "show"=>"y",
                                               "link"=>"y",
                                              ),

								   "33"=>array("name"=>"sv_version",
                                               "head"=>__("Ver from DB"),
                                               "show"=>"y",
                                               "link"=>"n",
											   "search"=>"n",
                                              ),

								   "34"=>array("name"=>"sv_newer",
                                               "head"=>__("OLD"),
                                               "show"=>"y",
                                               "link"=>"n",
												"search"=>"n",
											   "sort"=>"n",
                                              ),

								   "36"=>array("name"=>"sv_instlocation",
                                               "head"=>__("SCX"),
                                               "show"=>"y",
                                               "link"=>"n",
											   "sort"=>"y",
                                              ),

								   "41"=>array("name"=>"sv_bemerkungen",
                                               "head"=>__("Anmerkungen"),
                                               "show"=>"n",
                                               "link"=>"n",
                                              ),

								   "42"=>array("name"=>"software_comment",
                                               "head"=>__("Comment"),
                                               "show"=>"y",
                                               "link"=>"n",
											   "search"=>"y",
                                              ),

								   "44"=>array("name"=>"sv_lizenztyp",
                                               "head"=>__("Lizenztyp"),
                                               "show"=>"y",
                                               "link"=>"n",
											   "search"=>"y",
                                              ),

                                   "46"=>array("name"=>"software_publisher",
                                               "head"=>__("Publisher"),
                                               "show"=>"y",
                                               "link"=>"y",
											   "search"=>"y",
                                               "get"=>array("file"=>"%software_url",
                                                            "title"=>__("External Link"),
                                                            "target"=>"_BLANK",
                                                           ),
                                              ),

								  "50"=>array("name"=>"software_first_timestamp",
                                               "head"=>__("First installed"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

							  ),
                  );
?>
