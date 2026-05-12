<?php
$query_array=array("headline"=>__("List all known old Software with hosts"),
                   "sql"=>"

WITH
base AS (
  SELECT
    sy.system_uuid,
    sy.system_name,
    sy.net_user_name,
    s.software_location,
    s.software_name,
    s.software_version,
    s.software_first_timestamp,

    REGEXP_REPLACE(
      LOWER(REPLACE(s.software_name, '(x64)', '')),
      '[^a-z0-9]+',
      ''
    ) AS norm_s,

    CONCAT(
      LPAD(COALESCE(NULLIF(SUBSTRING_INDEX(s.software_version, '.', 1), ''), '0'), 15, '0'),
      LPAD(COALESCE(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(s.software_version, '.', 2), '.', -1), ''), '0'), 15, '0'),
      LPAD(COALESCE(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(s.software_version, '.', 3), '.', -1), ''), '0'), 15, '0'),
      LPAD(COALESCE(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(s.software_version, '.', 4), '.', -1), ''), '0'), 15, '0')
    ) AS v_s

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
    AND s.software_name NOT REGEXP '(KB|Q)[0-9]{6,}'
),

sv_norm AS (
  SELECT
    sv.sv_product,
    sv.sv_version,
    sv.sv_icondata,
    sv.sv_instlocation,
    sv.sv_lizenztyp,

    REGEXP_REPLACE(
      LOWER(REPLACE(sv.sv_product, '(x64)', '')),
      '[^a-z0-9]+',
      ''
    ) AS norm_p,

    CONCAT(
      LPAD(COALESCE(NULLIF(SUBSTRING_INDEX(sv.sv_version, '.', 1), ''), '0'), 15, '0'),
      LPAD(COALESCE(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(sv.sv_version, '.', 2), '.', -1), ''), '0'), 15, '0'),
      LPAD(COALESCE(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(sv.sv_version, '.', 3), '.', -1), ''), '0'), 15, '0'),
      LPAD(COALESCE(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(sv.sv_version, '.', 4), '.', -1), ''), '0'), 15, '0')
    ) AS v_sv

  FROM softwareversionen sv
  WHERE sv.sv_version IS NOT NULL
    AND sv.sv_version <> ''
),

matches AS (
  SELECT
    b.*,
    sv.sv_product,
    sv.sv_version,
    sv.sv_icondata,
    sv.sv_instlocation,
    sv.sv_lizenztyp,
    sv.v_sv,

    CASE
      WHEN b.norm_s = sv.norm_p THEN 3
      WHEN b.norm_s LIKE CONCAT('%', sv.norm_p, '%') THEN 2
      WHEN sv.norm_p LIKE CONCAT('%', b.norm_s, '%') THEN 1
      ELSE 0
    END AS score,

    LENGTH(sv.norm_p) AS plen

  FROM base b
  JOIN sv_norm sv
    ON b.norm_s <> ''
   AND sv.norm_p <> ''
   AND b.v_s < sv.v_sv
   AND (
        b.norm_s = sv.norm_p
        OR b.norm_s LIKE CONCAT('%', sv.norm_p, '%')
        OR sv.norm_p LIKE CONCAT('%', b.norm_s, '%')
   )
   AND NOT (
        LOWER(b.software_name) LIKE '%google chrome%'
        AND LOWER(sv.sv_product) REGEXP '(beta|dev|canary|chromium|google update|googleupdate)'
   )
),

ranked AS (
  SELECT
    m.*,
    ROW_NUMBER() OVER (
      PARTITION BY
        m.system_uuid,
        m.software_name,
        m.software_version
      ORDER BY
        m.score DESC,
        m.plen DESC,
        m.v_sv DESC
    ) AS rn
  FROM matches m
)

SELECT
  MAX(software_location) AS software_location,
  MAX(net_user_name) AS net_user_name,
  MAX(system_name) AS system_name,
  software_name,
  MAX(sv_product) AS sv_product,
  software_version,
  MAX(sv_version) AS sv_version,
  MAX(sv_icondata) AS sv_icondata,
  MAX(sv_lizenztyp) AS sv_lizenztyp,
  MIN(software_first_timestamp) AS software_first_timestamp,
  1 AS sv_newer

FROM ranked
WHERE rn = 1

GROUP BY
  software_name,
  software_version
  

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
