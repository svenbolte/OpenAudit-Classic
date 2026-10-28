<?php
$query_array=array(
"headline"=>__("Softwarevielfalt je Computer"),
"sql"=>"SELECT sy.system_uuid,sy.system_name,sy.net_ip_address,sy.net_domain,sy.system_os_name,sy.system_vendor,sy.system_model,sy.system_system_type,sy.system_timestamp,
COALESCE(sw.programme,0) AS programme,COALESCE(sw.versionen,0) AS versionen,COALESCE(sw.eintraege,0) AS eintraege,COALESCE(sw.publisher,0) AS publisher,COALESCE(sw.ohne_version,0) AS ohne_version,LEFT(COALESCE(sw.beispiele,''),500) AS beispiele
FROM system sy LEFT JOIN
(SELECT software_uuid,software_timestamp,COUNT(DISTINCT NULLIF(TRIM(software_name),'')) AS programme,
COUNT(DISTINCT CONCAT(COALESCE(software_name,''),CHAR(31),COALESCE(software_version,''))) AS versionen,
COUNT(*) AS eintraege,COUNT(DISTINCT NULLIF(TRIM(software_publisher),'')) AS publisher,
SUM(CASE WHEN TRIM(COALESCE(software_version,''))='' THEN 1 ELSE 0 END) AS ohne_version,
GROUP_CONCAT(DISTINCT software_name ORDER BY software_name SEPARATOR ', ') AS beispiele
FROM software GROUP BY software_uuid,software_timestamp) sw
ON sw.software_uuid=sy.system_uuid AND sw.software_timestamp=sy.system_timestamp",
"sort"=>"programme",
"dir"=>"DESC",
"get"=>array("file"=>"system.php","title"=>__("Go to System"),"var"=>array("pc"=>"%system_uuid","view"=>"summary")),
"fields"=>array(
"10"=>array("name"=>"system_name","head"=>__("Computer"),"show"=>"y","link"=>"y","search"=>"n"),
"20"=>array("name"=>"net_ip_address","head"=>__("IP"),"show"=>"y","link"=>"n","search"=>"n"),
"30"=>array("name"=>"net_domain","head"=>__("Domaene"),"show"=>"y","link"=>"n","search"=>"n"),
"40"=>array("name"=>"system_os_name","head"=>__("Betriebssystem"),"show"=>"y","link"=>"n","search"=>"n"),
"50"=>array("name"=>"system_vendor","head"=>__("Hersteller"),"show"=>"y","link"=>"n","search"=>"n"),
"60"=>array("name"=>"system_model","head"=>__("Modell"),"show"=>"y","link"=>"n","search"=>"n"),
"70"=>array("name"=>"system_system_type","head"=>__("Systemtyp"),"show"=>"y","link"=>"n","search"=>"n"),
"80"=>array("name"=>"programme","head"=>__("Programme"),"show"=>"y","link"=>"n","search"=>"n"),
"90"=>array("name"=>"versionen","head"=>__("Name-Version-Kombinationen"),"show"=>"y","link"=>"n","search"=>"n"),
"100"=>array("name"=>"eintraege","head"=>__("Softwareeintraege"),"show"=>"y","link"=>"n","search"=>"n"),
"110"=>array("name"=>"publisher","head"=>__("Publisher"),"show"=>"y","link"=>"n","search"=>"n"),
"120"=>array("name"=>"ohne_version","head"=>__("Ohne Versionsangabe"),"show"=>"y","link"=>"n","search"=>"n"),
"130"=>array("name"=>"beispiele","head"=>__("Programme (Auszug)"),"show"=>"y","link"=>"n","search"=>"n"),
"140"=>array("name"=>"system_timestamp","head"=>__("Letztes Audit"),"show"=>"y","link"=>"n","search"=>"n")
));
?>
