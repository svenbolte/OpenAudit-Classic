<?php
$query_array=array("headline"=>__("AVV-Liste - Gesamte Software"),
                   "sql"=>"
WITH software_clean AS (
  SELECT
    s.software_name,
    s.software_version,
    s.software_publisher,
    s.software_url,
    s.software_comment,
    s.software_first_timestamp,

    REGEXP_REPLACE(
      LOWER(REPLACE(s.software_name, '(x64)', '')),
      '[^a-z0-9]+',
      ''
    ) AS software_name_clean

  FROM system sy
  JOIN software s
    ON s.software_uuid = sy.system_uuid
   AND s.software_timestamp = sy.system_timestamp

  WHERE s.software_name NOT LIKE '%hotfix%'
    AND s.software_name NOT LIKE '%Service Pack%'
    AND s.software_name NOT LIKE '% Edge Update%'
    AND s.software_name NOT LIKE '%MUI (%'
    AND s.software_name NOT LIKE '%Proofing %'
    AND s.software_name NOT LIKE '%Language%'
    AND s.software_name NOT LIKE '%Korrektur%'
    AND s.software_name NOT LIKE '%linguisti%'
    AND s.software_name NOT REGEXP 'SP[1-4]{1,}'
    AND s.software_name NOT REGEXP '(KB|Q)[0-9]{6,}'
),

sv_clean AS (
  SELECT
    sv_product,
    sv_bemerkungen,
    sv_lizenztyp,
    sv_version,
    sv_instlocation,
    sv_icondata,
    sv_lizenzgeber,
    sv_herstellerwebsite,
    sv_supportmail,
    sv_supporttel,

    REGEXP_REPLACE(
      LOWER(REPLACE(sv_product, '(x64)', '')),
      '[^a-z0-9]+',
      ''
    ) AS sv_product_clean

  FROM softwareversionen
),

matched AS (
  SELECT *
  FROM (
    SELECT
      sc.*,

      sv.sv_bemerkungen,
      sv.sv_lizenztyp,
      sv.sv_version,
      sv.sv_instlocation,
      sv.sv_icondata,
      sv.sv_lizenzgeber,
      sv.sv_herstellerwebsite,
      sv.sv_supportmail,
      sv.sv_supporttel,

      ROW_NUMBER() OVER (
        PARTITION BY sc.software_name
        ORDER BY LENGTH(sv.sv_product) ASC
      ) AS rn

    FROM software_clean sc
    LEFT JOIN sv_clean sv
      ON sc.software_name_clean LIKE CONCAT('%', sv.sv_product_clean, '%')
      OR sv.sv_product_clean LIKE CONCAT('%', sc.software_name_clean, '%')
  ) x
  WHERE rn = 1
)

SELECT
  software_name,
  MAX(sv_bemerkungen)        AS sv_bemerkungen,
  MAX(sv_lizenztyp)          AS sv_lizenztyp,
  MAX(sv_version)            AS sv_version,
  MAX(sv_instlocation)       AS sv_instlocation,
  MAX(sv_icondata)           AS sv_icondata,
  MAX(software_version)      AS software_version,
  MAX(sv_lizenzgeber)        AS sv_lizenzgeber,
  MAX(sv_herstellerwebsite)  AS sv_herstellerwebsite,
  MAX(sv_supportmail)        AS sv_supportmail,
  MAX(sv_supporttel)         AS sv_supporttel,
  MAX(software_publisher)    AS software_publisher,
  MAX(software_url)          AS software_url,
  MAX(software_comment)      AS software_comment,
  MIN(software_first_timestamp) AS software_first_timestamp,
  1 AS sv_newer

FROM matched
GROUP BY software_name
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
                   "fields"=>array(

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

								  "48"=>array("name"=>"sv_lizenzgeber",
                                               "head"=>__("Lizenzgeber"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

								  "48"=>array("name"=>"sv_herstellerwebsite",
                                               "head"=>__("Herst-Website"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

								  "50"=>array("name"=>"sv_supportmail",
                                               "head"=>__("Supportmail"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

								  "55"=>array("name"=>"sv_supporttel",
                                               "head"=>__("Supporttel"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

								   "56"=>array("name"=>"software_comment",
                                               "head"=>__("Comment"),
                                               "show"=>"y",
                                               "link"=>"n",
											   "search"=>"y",
                                              ),

								   "57"=>array("name"=>"sv_lizenztyp",
                                               "head"=>__("Lizenztyp"),
                                               "show"=>"y",
                                               "link"=>"n",
											   "search"=>"y",
                                              ),

								  "80"=>array("name"=>"software_first_timestamp",
                                               "head"=>__("First installed"),
                                               "show"=>"y",
                                               "link"=>"n",
                                              ),

							  ),
                  );
?>
