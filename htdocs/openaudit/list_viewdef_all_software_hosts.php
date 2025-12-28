<?php

$query_array=array("headline"=>__("List all Software with Hosts"),
                   "sql"=>"
WITH
base AS (
  SELECT
    sy.system_uuid,
    sy.system_name,
    sy.net_user_name,
    s.software_name,
    s.software_version,
    s.software_publisher,
    s.software_location,
    s.software_first_timestamp,
    REGEXP_REPLACE(LOWER(REPLACE(s.software_name,'(x64)','')), '[^a-z0-9]+', '') AS norm_s
  FROM system sy
  JOIN software s
    ON s.software_uuid = sy.system_uuid
   AND s.software_timestamp = sy.system_timestamp
  WHERE s.software_name NOT LIKE '%hotfix%'
    AND s.software_name NOT LIKE '%Service Pack%'
    AND s.software_name NOT LIKE '%MUI (%'
    AND s.software_name NOT LIKE '%Proofing %'
    AND s.software_name NOT LIKE '%Language%'
    AND s.software_name NOT LIKE '%Korrektur%'
    AND s.software_name NOT LIKE '%linguisti%'
    AND s.software_name NOT REGEXP 'SP[1-4]{1,}'
    AND s.software_name NOT REGEXP '[KB|Q][0-9]{6,}'
),
sv_norm AS (
  SELECT
    sv.*,
    REGEXP_REPLACE(LOWER(REPLACE(sv.sv_product,'(x64)','')), '[^a-z0-9]+', '') AS norm_p
  FROM softwareversionen sv
),
cand AS (
  SELECT
    b.*,
    sv.sv_version,
    sv.sv_icondata,
    sv.sv_instlocation,
    sv.sv_lizenztyp,
    sv.sv_product,
    CASE
      WHEN b.norm_s = sv.norm_p THEN 3
      WHEN b.norm_s LIKE CONCAT('%', sv.norm_p, '%') THEN 2
      WHEN sv.norm_p LIKE CONCAT('%', b.norm_s, '%') THEN 1
      ELSE 0
    END AS score,
    LENGTH(sv.norm_p) AS plen,
    ROW_NUMBER() OVER (
      PARTITION BY b.system_uuid, b.software_name, b.software_version
      ORDER BY
        CASE
          WHEN b.norm_s = sv.norm_p THEN 3
          WHEN b.norm_s LIKE CONCAT('%', sv.norm_p, '%') THEN 2
          WHEN sv.norm_p LIKE CONCAT('%', b.norm_s, '%') THEN 1
          ELSE 0
        END DESC,
        LENGTH(sv.norm_p) DESC
    ) AS rn
  FROM base b
  LEFT JOIN sv_norm sv
    ON (
         b.norm_s LIKE CONCAT('%', sv.norm_p, '%')
         OR sv.norm_p LIKE CONCAT('%', b.norm_s, '%')
       )
   AND NOT (
     LOWER(b.software_name) LIKE '%google chrome%'
     AND LOWER(sv.sv_product) REGEXP '(beta|dev|canary|chromium|google update|googleupdate)'
   )
)
SELECT
  software_name,
  software_version,
  software_publisher,
  software_location,
  sv_version,
  sv_icondata,
  sv_instlocation,
  system_name,
  net_user_name,
  system_uuid,
  sv_lizenztyp,
  software_first_timestamp,
  (1=1) AS sv_newer
FROM cand
WHERE rn = 1 
				   
				   
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
                   "fields"=>array("10"=>array("name"=>"system_uuid",
                                               "head"=>__("UUID"),
                                               "show"=>"n",
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

                                   "40"=>array("name"=>"system_name",
                                               "head"=>__("Hostname"),
                                               "show"=>"y",
                                               "link"=>"y",
                                               "get"=>array("file"=>"system.php",
                                                            "title"=>__("Go to System"),
                                                            "var"=>array("pc"=>"%system_uuid",
                                                                         "view"=>"summary",
                                                                        ),
                                                           ),
                                              ),
                                    "50"=>array("name"=>"net_user_name",
                                               "head"=>__("Network User"),
                                               "show"=>"y",
                                               "link"=>"y",
                                              ),
									"60"=>array("name"=>"software_location",
                                               "head"=>__("Installdir"),
                                               "show"=>"y",
                                               "link"=>"y",
                                              ),

									"65"=>array("name"=>"software_first_timestamp",
                                               "head"=>__("First installed"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

								   "70"=>array("name"=>"sv_lizenztyp",
                                               "head"=>__("Lizenztyp"),
                                               "show"=>"y",
                                               "link"=>"n",
											   "search"=>"y",
                                              ),
										  
                                  ),
                  );
?>
