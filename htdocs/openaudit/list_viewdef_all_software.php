<?php
$query_array=array("headline"=>__("List all Software"),
                   "sql"=>"
SELECT
  COUNT(DISTINCT CONCAT_WS('|',
    s.software_uuid,
    s.software_timestamp,
    s.software_name,
    s.software_version
  )) AS software_count,

  s.software_name,
  s.software_version,

  MAX(sv.sv_bemerkungen)  AS sv_bemerkungen,
  MAX(sv.sv_lizenztyp)    AS sv_lizenztyp,
  MAX(sv.sv_version)      AS sv_version,
  MAX(sv.sv_instlocation) AS sv_instlocation,
  MAX(sv.sv_icondata)     AS sv_icondata,

  MAX(s.software_publisher)       AS software_publisher,
  MAX(s.software_url)             AS software_url,
  MAX(s.software_comment)         AS software_comment,
  MIN(s.software_first_timestamp) AS software_first_timestamp,
  (1=1) AS sv_newer
FROM system sy
JOIN software s
  ON s.software_uuid = sy.system_uuid
 AND s.software_timestamp = sy.system_timestamp

LEFT JOIN (
  SELECT
    sv1.sv_product,
    sv1.sv_bemerkungen,
    sv1.sv_lizenztyp,
    sv1.sv_version,
    sv1.sv_instlocation,
    sv1.sv_icondata,
    sv1.sv_product AS _join_key
  FROM softwareversionen sv1
) sv
  ON sv._join_key = (
    SELECT sv2.sv_product
    FROM softwareversionen sv2
    WHERE
      (
        REGEXP_REPLACE(LOWER(REPLACE(s.software_name, '(x64)', '')), '[^a-z0-9]+', '')
        LIKE CONCAT('%',
             REGEXP_REPLACE(LOWER(REPLACE(sv2.sv_product, '(x64)', '')), '[^a-z0-9]+', ''),
             '%'
        )
        OR
        REGEXP_REPLACE(LOWER(REPLACE(sv2.sv_product, '(x64)', '')), '[^a-z0-9]+', '')
        LIKE CONCAT('%',
             REGEXP_REPLACE(LOWER(REPLACE(s.software_name, '(x64)', '')), '[^a-z0-9]+', ''),
             '%'
        )
      )
    ORDER BY LENGTH(sv2.sv_product) ASC
    LIMIT 1
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

GROUP BY
  s.software_name,
  s.software_version


				   
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
