<?php
$query_array=array(
"headline"=>__("Drucker je Computer (ohne UNC)"),
"sql"=>"SELECT sy.system_uuid,sy.system_name,sy.net_ip_address,sy.net_domain,sy.system_os_name,sy.system_vendor,sy.system_model,sy.system_system_type,sy.system_timestamp,
COALESCE(pr.drucker,0) AS drucker,COALESCE(pr.freigegeben,0) AS freigegeben,COALESCE(pr.treiber,0) AS treiber,COALESCE(pr.ports,0) AS ports,COALESCE(pr.standorte,0) AS standorte,LEFT(COALESCE(pr.namen,''),500) AS namen,LEFT(COALESCE(pr.portnamen,''),350) AS portnamen
FROM system sy LEFT JOIN
(SELECT other_linked_pc,COUNT(*) AS drucker,
SUM(CASE WHEN LOWER(TRIM(COALESCE(other_p_shared,''))) IN ('true','1','yes','y','ja') THEN 1 ELSE 0 END) AS freigegeben,
COUNT(DISTINCT NULLIF(TRIM(other_model),'')) AS treiber,COUNT(DISTINCT NULLIF(TRIM(other_p_port_name),'')) AS ports,
COUNT(DISTINCT NULLIF(TRIM(other_location),'')) AS standorte,
GROUP_CONCAT(DISTINCT other_description ORDER BY other_description SEPARATOR ', ') AS namen,
GROUP_CONCAT(DISTINCT other_p_port_name ORDER BY other_p_port_name SEPARATOR ', ') AS portnamen
FROM other WHERE other_type='printer'
AND LEFT(TRIM(COALESCE(other_description,'')),2)<>CHAR(92,92)
AND LEFT(TRIM(COALESCE(other_p_port_name,'')),2)<>CHAR(92,92)
GROUP BY other_linked_pc) pr ON pr.other_linked_pc=sy.system_uuid",
"sort"=>"drucker",
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
"80"=>array("name"=>"drucker","head"=>__("Installierte Drucker"),"show"=>"y","link"=>"n","search"=>"n"),
"90"=>array("name"=>"freigegeben","head"=>__("Freigegeben"),"show"=>"y","link"=>"n","search"=>"n"),
"100"=>array("name"=>"treiber","head"=>__("Treiber"),"show"=>"y","link"=>"n","search"=>"n"),
"110"=>array("name"=>"ports","head"=>__("Anschluesse"),"show"=>"y","link"=>"n","search"=>"n"),
"120"=>array("name"=>"standorte","head"=>__("Standorte"),"show"=>"y","link"=>"n","search"=>"n"),
"130"=>array("name"=>"namen","head"=>__("Drucker (Auszug)"),"show"=>"y","link"=>"n","search"=>"n"),
"140"=>array("name"=>"portnamen","head"=>__("Ports (Auszug)"),"show"=>"y","link"=>"n","search"=>"n"),
"150"=>array("name"=>"system_timestamp","head"=>__("Letztes Audit"),"show"=>"y","link"=>"n","search"=>"n")
));
?>
