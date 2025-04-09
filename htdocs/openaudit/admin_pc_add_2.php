<?php
/**********************************************************************************************************
Recent Changes:
[Edoardo]	21/05/2010	Filtered out MS Office virtual printers, if any, in the 'insert_printers' function.
[Edoardo]	27/05/2010	(by jpa) Filtered out Citrix virtual printers in the 'insert_printers' function.
[Edoardo]	28/05/2010	Modified function 'insert_harddrive()' to to add/update the 'hard_drive_predicted_failure' field.
[Edoardo]	31/05/2010	Added printer_driver_name in function 'insert_printer' - Suggested by jpa	
[Edoardo]	27/07/2010	(by jpa) Added 'system_os_arch' in function 'insert_system03'
[Edoardo]	07/08/2010	Fixed the 'insert_software()' function to update all fields
[Edoardo]	01/09/2010	Added 'users_lockout' in function 'insert_users()' and fixed updating of other dynamic fields in the 'users' table						
[PBMOD]		13.09.2024  ODBC DSNs and Config and odbc table added.				
**********************************************************************************************************/

$page = "add_pc";
// Increase the script timeout value
set_time_limit(200);

$system_name = "";
$timestamp = "";
$uuid = "";
$user_name = "";
$verbose = "";
$software_audit = "";
$verbose = "";

//Get current time
    $mtime = microtime();
//Split seconds and microseconds
    $mtime = explode(" ",$mtime);
//Create one value for start time
    $mtime = $mtime[1] + $mtime[0];
//Write start time into a variable
    $tstart = $mtime;
?>

<style type="text/css">

H1 {
  FONT-SIZE: 12pt;
  COLOR: #000000;
  LINE-HEIGHT: 16pt;
  FONT-FAMILY: "Trebuchet MS", Trebuchet, Arial, Helvetica, sans-serif
}

body {
  FONT-SIZE: 9pt;
  COLOR: #000000;
  LINE-HEIGHT: 12pt;
  FONT-FAMILY: Verdana, Geneva, Arial, Helvetica, sans-serif;
  TEXT-ALIGN: left;
  TEXT-DECORATION: none;
}
</style>

<?php

include "include_functions.php";

// Process the form
$db=GetOpenAuditDbConnection();

//$user_name = $_POST['user_name'];
//$timestamp = $_POST["timestamp"];
//$uuid = $_POST["uuid"];
//$software_audit = $_POST["software_audit"];
//$systemname = strtoupper($_POST["systemname"]);

//if (!is_null($_POST["verbose"])) {$verbose = $_POST["verbose"];} else {$verbose="off";}

$input = $_POST['add'];
$input = explode("\n", $input);

echo "Verbose: " . $verbose . "<br />";
foreach ($input as $split) {
  //Audit
  if (substr($split, 0, 5) == "audit"){
    $extended = explode('^^^',$split);
    $system_name = strtoupper(trim($extended[1]));
    $timestamp = trim($extended[2]);
    $uuid = trim($extended[3]);
    $user_name = trim($extended[4]);
    $verbose = trim($extended[5]);
    $software_audit = trim($extended[6]);
  }
}

$verbose = "y";

echo "User: " . $user_name . "<br />\n\n";
echo "Verbose: " . $verbose . "<br />\n\n";
echo "System: " . $system_name . "<br />\n\n";
echo "UUID: " . $uuid . "<br />\n\n";
echo "Timestamp: " . $timestamp . "<br />\n";
echo "Software Audit: " . $software_audit . "<br /><br />\n\n";

$net_timestamp = NULL;
$processor_timestamp = NULL;
$bios_timestamp = NULL;
$memory_timestamp = NULL;
$video_timestamp = NULL;
$monitor_timestamp = NULL;
$usb_timestamp = NULL;
$hard_drive_timestamp = NULL;
$partition_timestamp = NULL;
$scsi_controller_timestamp = NULL;
$scsi_device_timestamp = NULL;
$optical_drive_timestamp = NULL;
$floppy_timestamp = NULL;
$tape_drive_timestamp = NULL;
$keyboard_timetamp = NULL;
$battery_timestamp = NULL;
$modem_timestamp = NULL;
$mouse_timestamp = NULL;
$sound_timestamp = NULL;
$printer_timestamp = NULL;
$shares_timestamp = NULL;
$mapped_timestamp = NULL;
$groups_timestamp = NULL;
$users_timestamp = NULL;
$hfnet_timestamp = NULL;
$startup_timestamp = NULL;
$service_timestamp = NULL;
$bho_timestamp = NULL;
$software_timestamp = NULL;
$softwapps_timestamp = NULL;
$firewall_app_timestamp = NULL;
$port_timestamp = NULL;
$ms_keys_timestamp = NULL;
$iis_timestamp = NULL;
$iis_vd_timestamp = NULL;
$iis_ip_timestamp = NULL;
$iis_web_ext_timestamp = NULL;
$sched_task_timestamp = NULL;
$env_var_timestamp = NULL;
$evt_log_timestamp = NULL;
$ip_route_timestamp = NULL;
$pagefile_timestamp = NULL;
$motherboard_timestamp = NULL;
$onboard_timestamp = NULL;
$au_timestamp = NULL;

$count = 0;

# <HACK>
# The following turns off strict checking so incorrect
# datatypes can be inserted into fields!
$sql = "SET @@session.sql_mode=''";
$db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
# </HACK>

// Get the timestamp of the last audit
$sql = "SELECT MAX(system_audits_timestamp) AS timestamp FROM system_audits WHERE system_audits_uuid = '$uuid'";
if ($verbose == "y"){echo $sql . "<br />\n\n";}
$db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
$myrow = mysqli_fetch_array($result);
if ($myrow["timestamp"]) {$old_timestamp = $myrow["timestamp"];} else {$old_timestamp = "";}
$sql = "";
$software_timestamp = $old_timestamp;
$softwapps_timestamp = $old_timestamp;
$service_timestamp = $old_timestamp;

if ($software_timestamp >= $timestamp and $timestamp != "") {
  die("Cannot insert old data when newer data already exists in the database, sorry!");
}

//If a software audit is NOT performed - update the missing tables with the timestamp
if ($software_audit == "n") {
  $sql = "UPDATE software SET software_timestamp = '$timestamp' WHERE software_uuid = '$uuid' AND software_timestamp = '$old_timestamp'";
  $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
  $sql = "UPDATE softwapps SET software_timestamp = '$timestamp' WHERE software_uuid = '$uuid' AND software_timestamp = '$old_timestamp'";
  $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
  $sql = "UPDATE bho SET bho_timestamp = '$timestamp' WHERE bho_uuid = '$uuid' AND bho_timestamp = '$old_timestamp'";
  $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
  $sql = "UPDATE startup SET startup_timestamp = '$timestamp' WHERE startup_uuid = '$uuid' AND startup_timestamp = '$old_timestamp'";
  $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
  $sql = "UPDATE service SET service_timestamp = '$timestamp' WHERE service_uuid = '$uuid' AND service_timestamp = '$old_timestamp'";
  $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
  $sql = "UPDATE bho SET bho_timestamp = '$timestamp' WHERE bho_uuid = '$uuid' AND bho_timestamp = '$old_timestamp'";
  $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
echo "No software audit - timestamps updated.<br />";
} else {}

$sql = "SELECT count(system_audits_uuid) AS timestamp FROM system_audits WHERE system_audits_uuid = '$uuid'";
$db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
$myrow = mysqli_fetch_array($result);
if ($verbose == "y"){echo $myrow[0]. "Datensätze<br />\n\n";};

// Add to audit table for this uuid
$sql = "INSERT INTO system_audits (system_audits_uuid, system_audits_timestamp, system_audits_username) VALUES ('$uuid','$timestamp','$user_name')";
if ($verbose == "y"){echo $sql . "<br />\n\n";}
$db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
$sql = "";

if ($myrow[0] == 0) {
	// Insert an entry in the System table
	$sql = "INSERT INTO system (system_uuid, system_first_timestamp) VALUES ('$uuid','$timestamp')";
	if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
	$sql = "";
}	

// Update timestamp in the System table
$sql = "UPDATE system SET system_timestamp = '$timestamp' WHERE system_uuid = '$uuid'";
if ($verbose == "y"){echo $sql . "<br />\n\n";}
$db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql);
$sql = "";
//
foreach ($input as $split) {
// Strip unnecesary slashes if required. 
// if(get_magic_quotes_gpc()) {
//  $split = stripslashes($split);
// }
//
$split = mysqli_real_escape_string($db,$split);  
//Network
  if (substr($split, 0, 7) == "network")  { insert_network($split); }
  // First system submit - Initial insert
  if (substr($split, 0, 8) == "system01") { insert_system01($split); }
  // Second system submit
  if (substr($split, 0, 8) == "system02") { insert_system02($split); }
  // Third system submit
  if (substr($split, 0, 8) == "system03") { insert_system03($split); }
  // Processor
  if (substr($split, 0, 9) == "processor"){ insert_processor($split); }
  // BIOS info
  if (substr($split, 0, 4) == "bios")     { insert_bios($split); }
  // Memory
  if (substr($split, 0, 6) == "memory")   { insert_memory($split); }
  // Optional features
  if (substr($split, 0, 16) == "optionalfeatures")    { insert_optionalfeatures($split); }
  // Video
  if (substr($split, 0, 5) == "video")    { insert_video($split); }
  // Monitor - System Table
  if (substr($split, 0, 11) == "monitor_sys"){ insert_monitor($split); }
  // USB Devices
  if (substr($split, 0, 3) == "usb"){ insert_usb($split); }
  // Hard Drives
  if (substr($split, 0, 9) == "harddrive"){ insert_harddrive($split); }
  // Partitions
  // Note - we do not insert a new record if the only thing thats changed is the free space
  if (substr($split, 0, 9) == "partition"){ insert_partition($split); }
  // SCSI Controllers
  if (substr($split, 0, 15) == "scsi_controller"){insert_scsi_controller($split); }
  // SCSI Controllers
  if (substr($split, 0, 11) == "scsi_device"){insert_scsi_device($split); }
  // Optical Drives
  if (substr($split, 0, 7) == "optical"){insert_optical($split); }
  // Floppy Drive
  if (substr($split, 0, 6) == "floppy"){ insert_floppy($split); }
  // Tape Drive
  if (substr($split, 0, 4) == "tape"){ insert_tape($split); }
  // Keyboard
  if (substr($split, 0, 8) == "keyboard"){ insert_keyboard($split); }
  // Battery
  if (substr($split, 0, 7) == "battery"){ insert_battery($split); }
  // Modem
  if (substr($split, 0, 5) == "modem"){ insert_modem($split); }
  // Mouse
  if (substr($split, 0, 5) == "mouse"){ insert_mouse($split); }
  // Sound
  if (substr($split, 0, 5) == "sound"){ insert_sound($split); }
  // Printer
  if (substr($split, 0, 7) == "printer"){ insert_printer($split); }
  // Shares
  if (substr($split, 0, 6) == "shares"){insert_shares($split); }
  // Mapped Drives
  if (substr($split, 0, 6) == "mapped"){ insert_mapped($split); }
  // Local Groups
  if (substr($split, 0, 7) == "l_group"){ insert_group($split); }
  // Local Users
  if (substr($split, 0, 6) == "l_user"){ insert_user($split); }
  // Startup Programs
  if (substr($split, 0, 7) == "startup"){ insert_startup($split); }
  // Services
  if (substr($split, 0, 7) == "service"){ insert_service($split); }
  // IE Browser Helper Objects
  if (substr($split, 0, 6) == "ie_bho"){ insert_bho($split); }
  // Tenth system submit - AntiVirus Settings - XP SP2
  if (substr($split, 0, 8) == "system10"){ insert_system10($split); }
  // ODBC DSNs and Connections
  if (substr($split, 0, 4) == "odbc")     { insert_odbc($split); }
  //Software
  if (substr($split, 0, 8) == "software"){ insert_software($split); }
  //Softwapps
  if (substr($split, 0, 9) == "softwapps"){ insert_software_apps($split); }
  // Eleventh system submit - Firewall Settings - XP SP2
  if (substr($split, 0, 8) == "system11"){ insert_system11($split); }
  // Firewall Authorised Applications - XP SP2
  if (substr($split, 0, 8) == "fire_app"){ insert_fire_app($split); }
  // Firewall Authorised Ports - XP SP2
  if (substr($split, 0, 9) == "fire_port"){insert_fire_port($split); }
  // CD Keys
  if (substr($split, 0, 7) == "ms_keys"){ insert_ms_keys($split); }
  // Another system submit - IIS version
  if (substr($split, 0, 8) == "system12"){ insert_system12($split); }
  // IIS sites
  if (substr($split, 0, 5) == "iis_1"){ insert_iis_1($split); }
  // IIS - Virtual Directory
  if (substr($split, 0, 5) == "iis_2"){ insert_iis_2($split); }
  // IIS - IP addresses
  if (substr($split, 0, 5) == "iis_3"){ insert_iis_3($split); }
  // IIS - Web Service extensions
  if (substr($split, 0, 5) == "iis_4"){ insert_iis_4($split); }
  // Scheduled task
  if (substr($split, 0, 10) == "sched_task"){ insert_sched_task($split); }
  // Environment variable
  if (substr($split, 0, 7) == "env_var"){ insert_env_var($split); }
  // Event log settings
  if (substr($split, 0, 7) == "evt_log"){ insert_evt_log($split); }
  // IP route
  if (substr($split, 0, 8) == "ip_route"){ insert_ip_route($split); }
  // Pagefile
  if (substr($split, 0, 8) == "pagefile"){ insert_pagefile($split); }
  // Motherboard
  if (substr($split, 0, 11) == "motherboard"){ insert_motherboard($split); }
  // Onboard device
  if (substr($split, 0, 7) == "onboard"){ insert_onboard($split); }
  // Automatic Updating settings
  if (substr($split, 0, 8) == "auto_upd"){ insert_auto_upd($split); }
  //Elapsed Time
  //if (substr($split, 0, 12) == "elapsed_time"){ insert_elapsed_time($split); }


}

function insert_network ($split) {
    global $timestamp, $uuid, $verbose, $net_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Network</h2><br />";}
    $net_mac_address = trim($extended[1]);
    $net_description = trim($extended[2]);
    $net_dhcp_enabled = trim($extended[3]);
    $net_dhcp_server = trim($extended[4]);
    $net_dns_host_name = trim($extended[5]);
    $net_dns_server = trim($extended[6]);
    $net_dns_server_2 = trim($extended[7]);
    $net_ip_address = trim($extended[8]);
    $net_ip_subnet = trim($extended[9]);
    $net_wins_primary = trim($extended[10]);
    $net_wins_secondary = trim($extended[11]);
    $net_adapter_type = trim($extended[12]);
    $net_manufacturer = trim($extended[13]);
    $net_gateway = trim($extended[14]);
    $net_ip_enabled = trim($extended[15]);
    $net_index = trim($extended[16]);
    $net_service_name = trim($extended[17]);
    $net_dhcp_lease_obtained = trim($extended[18]);
    $net_dhcp_lease_expires = trim($extended[19]);
    $net_dns_server_3 = trim($extended[20]);
    $net_dns_domain = trim($extended[21]);
    $net_dns_domain_suffix = trim($extended[22]);
    $net_dns_domain_suffix_2 = trim($extended[23]);
    $net_dns_domain_suffix_3 = trim($extended[24]);
    $net_dns_domain_reg_enabled = trim($extended[25]);
    $net_dns_domain_full_reg_enabled = trim($extended[26]);
    $net_ip_address_2 = trim($extended[27]);
    $net_ip_subnet_2 = trim($extended[28]);
    $net_ip_address_3 = trim($extended[29]);
    $net_ip_subnet_3 = trim($extended[30]);
    $net_wins_lmhosts_enabled = trim($extended[31]);
    $net_netbios_options = trim($extended[32]);
    $net_gateway_metric = trim($extended[33]);
    $net_gateway_2 = trim($extended[34]);
    $net_gateway_metric_2 = trim($extended[35]);
    $net_gateway_3 = trim($extended[36]);
    $net_gateway_metric_3 = trim($extended[37]);
    $net_ip_metric = trim($extended[38]);
    $net_connection_id = trim($extended[39]);
    $net_connection_status = trim($extended[40]);
    $net_speed = trim($extended[41]);
    $net_driver_provider = trim($extended[42]);
    $net_driver_version = trim($extended[43]);
    $net_driver_date = trim($extended[44]);


    if (is_null($net_timestamp)) {
	$db=GetOpenAuditDbConnection();
      $sql  = "SELECT MAX(net_timestamp) FROM network_card WHERE net_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(net_timestamp)"]) {$net_timestamp = $myrow["MAX(net_timestamp)"];} else {$net_timestamp = "";}
    } else {}
    $sql  = "SELECT count(net_uuid) as count from network_card ";
    $sql .= "WHERE net_mac_address = '$net_mac_address' AND net_uuid = '$uuid' AND net_description = '$net_description' ";
    $sql .= "AND net_dhcp_enabled = '$net_dhcp_enabled' AND net_dns_host_name = '$net_dns_host_name' AND net_adapter_type = '$net_adapter_type' ";
    $sql .= "AND net_manufacturer = '$net_manufacturer' AND net_ip_enabled = '$net_ip_enabled' AND net_index = '$net_index' ";
    $sql .= "AND net_service_name = '$net_service_name' AND net_connection_id = '$net_connection_id' ";
    $sql .= "AND (net_timestamp = '$net_timestamp' OR net_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection();
	$db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New NIC or DHCP or TCP/IP status changed - Insert into database
      $sql  = "INSERT INTO network_card (";
      $sql .= "net_mac_address, net_uuid, net_ip_enabled, net_index, net_service_name, net_description, net_dhcp_enabled, net_dhcp_server, ";
      $sql .= "net_dhcp_lease_obtained, net_dhcp_lease_expires, net_dns_host_name, net_dns_server, net_dns_server_2, net_dns_server_3, ";
      $sql .= "net_dns_domain, net_dns_domain_suffix, net_dns_domain_suffix_2, net_dns_domain_suffix_3, net_dns_domain_reg_enabled, ";
      $sql .= "net_dns_domain_full_reg_enabled, net_ip_address, net_ip_subnet, net_ip_address_2, net_ip_subnet_2, net_ip_address_3, ";
      $sql .= "net_ip_subnet_3, net_wins_primary, net_wins_secondary, net_wins_lmhosts_enabled, net_netbios_options, net_adapter_type, ";
      $sql .= "net_manufacturer, net_connection_id, net_connection_status, net_speed, net_gateway, net_gateway_metric, net_gateway_2, ";
      $sql .= "net_gateway_metric_2, net_gateway_3, net_gateway_metric_3, net_ip_metric, net_driver_provider, net_driver_version, net_driver_date, ";
      $sql .= "net_timestamp, net_first_timestamp) VALUES (";
      $sql .= "'$net_mac_address', '$uuid', '$net_ip_enabled', '$net_index', '$net_service_name', '$net_description', '$net_dhcp_enabled', '$net_dhcp_server', ";
      $sql .= "'$net_dhcp_lease_obtained', '$net_dhcp_lease_expires', '$net_dns_host_name', '$net_dns_server', '$net_dns_server_2', '$net_dns_server_3', ";
      $sql .= "'$net_dns_domain', '$net_dns_domain_suffix', '$net_dns_domain_suffix_2', '$net_dns_domain_suffix_3', '$net_dns_domain_reg_enabled', ";
      $sql .= "'$net_dns_domain_full_reg_enabled', '$net_ip_address', '$net_ip_subnet', '$net_ip_address_2', '$net_ip_subnet_2', '$net_ip_address_3', ";
      $sql .= "'$net_ip_subnet_3', '$net_wins_primary', '$net_wins_secondary', '$net_wins_lmhosts_enabled', '$net_netbios_options', '$net_adapter_type', ";
      $sql .= "'$net_manufacturer', '$net_connection_id', '$net_connection_status', '$net_speed', '$net_gateway', '$net_gateway_metric', '$net_gateway_2', ";
      $sql .= "'$net_gateway_metric_2', '$net_gateway_3', '$net_gateway_metric_3', '$net_ip_metric', '$net_driver_provider', '$net_driver_version', '$net_driver_date', ";
      $sql .= "'$timestamp', '$timestamp') ";

      if ($verbose == "y"){echo $sql . "<br />\n\n";}
		$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - Update timestamp and dynamic fields
      $sql  = "UPDATE network_card SET ";
      $sql .= "net_timestamp = '$timestamp', net_dhcp_server = '$net_dhcp_server', net_dhcp_lease_obtained = '$net_dhcp_lease_obtained', ";
      $sql .= "net_dhcp_lease_expires = '$net_dhcp_lease_expires', net_dns_server = '$net_dns_server', net_dns_server_2 = '$net_dns_server_2', ";
      $sql .= "net_dns_server_3 = '$net_dns_server_3', net_dns_domain = '$net_dns_domain', net_dns_domain_suffix = '$net_dns_domain_suffix', ";
      $sql .= "net_dns_domain_suffix_2 = '$net_dns_domain_suffix_2', net_dns_domain_suffix_3 = '$net_dns_domain_suffix_3', net_dns_domain_reg_enabled = '$net_dns_domain_reg_enabled', ";
      $sql .= "net_dns_domain_full_reg_enabled = '$net_dns_domain_full_reg_enabled', net_ip_address = '$net_ip_address', net_ip_subnet = '$net_ip_subnet', ";
      $sql .= "net_ip_address_2 = '$net_ip_address_2', net_ip_subnet_2 = '$net_ip_subnet_2', net_ip_address_3 = '$net_ip_address_3', "; 
      $sql .= "net_ip_subnet_3 = '$net_ip_subnet_3', net_wins_primary = '$net_wins_primary', net_wins_secondary = '$net_wins_secondary', ";
      $sql .= "net_wins_lmhosts_enabled = '$net_wins_lmhosts_enabled', net_netbios_options = '$net_netbios_options', net_gateway = '$net_gateway', ";
      $sql .= "net_connection_status = '$net_connection_status', net_speed = '$net_speed', net_gateway_metric = '$net_gateway_metric', net_gateway_2 = '$net_gateway_2', ";
      $sql .= "net_gateway_metric_2 = '$net_gateway_metric_2', net_gateway_3 = '$net_gateway_3', net_gateway_metric_3 = '$net_gateway_metric_3', net_ip_metric = '$net_ip_metric', ";
      $sql .= "net_driver_provider = '$net_driver_provider', net_driver_version = '$net_driver_version', net_driver_date = '$net_driver_date' ";
      $sql .= "WHERE net_mac_address = '$net_mac_address' AND net_uuid = '$uuid' AND net_description = '$net_description' ";
      $sql .= "AND net_dhcp_enabled = '$net_dhcp_enabled' AND net_dns_host_name = '$net_dns_host_name' AND net_adapter_type = '$net_adapter_type' ";
      $sql .= "AND net_manufacturer = '$net_manufacturer' AND net_ip_enabled = '$net_ip_enabled' AND net_index = '$net_index' ";
      $sql .= "AND net_service_name = '$net_service_name' AND net_connection_id = '$net_connection_id' ";
      $sql .= "AND net_timestamp = '$net_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
   
  // Remove from the 'other' table if exists
  // First - get the id from the 'other' table - if it exists
  $other_id = '';
  $sql = "SELECT other_id FROM other WHERE other_mac_address = '$net_mac_address'";
  if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
  $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Check Other table Failed: ' . mysqli_error($db) . '<br />' . $sql);
  if ($myrow = mysqli_fetch_array($result)){$other_id = $myrow['other_id'];}
  if ($other_id <> ''){
    // It exists - so update the 'nmap_ports' table to the uuid/mac of the PC - not the other_id
    $sql = "UPDATE nmap_ports SET nmap_other_id = '$uuid' WHERE nmap_other_id = '$other_id'";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Update nmap_ports Failed: ' . mysqli_error($db) . '<br />' . $sql);
    // Now remove the entry from the 'other' table
    $sql = "DELETE FROM other WHERE other_mac_address = '$net_mac_address'";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Update nmap_ports Failed: ' . mysqli_error($db) . '<br />' . $sql);
  }
}


function insert_system01 ($split) {
    global $timestamp, $uuid, $verbose;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>System</h2><br />";}
    $net_ip_address = trim($extended[1]);
    $net_domain = trim($extended[2]);
    $net_user_name = trim($extended[3]);
    $net_client_site_name = trim($extended[4]);
    $net_domain_controller_address = trim($extended[5]);
    $net_domain_controller_name = trim($extended[6]);
    $sql  = "UPDATE system SET net_ip_address = '$net_ip_address', ";
    $sql .= "net_domain = '$net_domain', net_user_name = '$net_user_name', ";
    $sql .= "net_client_site_name = '$net_client_site_name', net_domain_controller_address = '$net_domain_controller_address', ";
    $sql .= "net_domain_controller_name = '$net_domain_controller_name' ";
    $sql .= "WHERE system_uuid = '$uuid' AND system_timestamp = '$timestamp'";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    //return();
  }

function insert_system02 ($split){
    global $timestamp, $uuid, $verbose;
    $extended = explode('^^^',$split);
    $system_model = trim($extended[1]);
    $system_name = strtoupper(trim($extended[2]));
    $system_num_processors = trim($extended[3]);
    $system_part_of_domain = trim($extended[4]);
    $system_primary_owner_name = trim($extended[5]);
    $system_system_type = trim($extended[6]);
    $system_memory = trim($extended[7]);
    $system_id_number = trim($extended[8]);
    $system_vendor = trim($extended[9]);
    $net_domain_role = trim($extended[10]);
    $time_caption = trim($extended[11]);
    $time_daylight = trim($extended[12]);
    $system_vcpu = trim($extended[13]);
    $system_lcpu = trim($extended[14]);
    $system_tpmver = trim($extended[15]);
    $tpm_init = trim($extended[16]);
    $tpm_password = trim($extended[17]);
    $sql  = "UPDATE system SET system_model = '$system_model', system_name = '$system_name', ";
    $sql .= "system_num_processors = '$system_num_processors', system_part_of_domain = '$system_part_of_domain', ";
    $sql .= "system_primary_owner_name = '$system_primary_owner_name', system_system_type = '$system_system_type', ";
    $sql .= "system_memory = '$system_memory', system_id_number = '$system_id_number', system_vendor = '$system_vendor', ";
    $sql .= "net_domain_role = '$net_domain_role', time_caption = '$time_caption', time_daylight = '$time_daylight', ";
    $sql .= "system_vcpu = '$system_vcpu', system_lcpu = '$system_lcpu', ";
    $sql .= "system_tpmver = '$system_tpmver', tpm_init = '$tpm_init', tpm_password = '$tpm_password' ";
    $sql .= "WHERE system_uuid = '$uuid' AND system_timestamp = '$timestamp'";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    return($system_name);
  }


function insert_system03 ($split){
    global $timestamp, $uuid, $verbose;
    $extended = explode('^^^',$split);
    $system_boot_device = trim($extended[1]);
    $system_build_number = trim($extended[2]);
    $system_os_type = trim($extended[3]);
    $system_os_name = trim($extended[4]);
    $system_country_code = return_country(trim($extended[5]));
    $system_description = trim($extended[6]);
    $date_system_install = trim($extended[7]);
    $system_organisation = trim($extended[8]);
    $system_language = return_language(trim($extended[9]));
    $system_registered_user = trim($extended[10]);
    $system_serial_number = trim($extended[11]);
    $system_service_pack = trim($extended[12]);
    $system_version = trim($extended[13]);
    $system_windows_directory = trim($extended[14]);
    $system_last_boot = trim($extended[15]);
    $system_os_arch = trim($extended[16]);
    $sql  = "UPDATE system SET system_boot_device = '$system_boot_device', system_build_number = '$system_build_number', ";
    $sql .= "system_os_type = '$system_os_type', system_os_name = '$system_os_name', ";
    $sql .= "system_country_code = '$system_country_code', system_description = '$system_description', ";
    $sql .= "date_system_install = '$date_system_install', system_organisation = '$system_organisation', ";
    $sql .= "system_language = '$system_language', system_registered_user = '$system_registered_user', ";
    $sql .= "system_serial_number = '$system_serial_number', system_service_pack = '$system_service_pack', ";
    $sql .= "system_version = '$system_version', system_windows_directory = '$system_windows_directory', ";
    $sql .= "system_last_boot = '$system_last_boot', system_os_arch = '$system_os_arch' ";
    $sql .= "WHERE system_uuid = '$uuid' AND system_timestamp = '$timestamp'";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
  }


function insert_processor ($split){
    global $timestamp, $uuid, $verbose, $processor_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Processor</h2><br />";}
    $processor_caption = trim($extended[1]);
    $processor_current_clock_speed = trim($extended[2]);
    $processor_current_voltage = trim($extended[3]);
    $processor_device_id = trim($extended[4]);
    $processor_ext_clock = trim($extended[5]);
    $processor_manufacturer = trim($extended[6]);
    $processor_max_clock_speed = trim($extended[7]);
    $processor_name = trim($extended[8]);
    $processor_power_management_supported = trim($extended[9]);
    $processor_socket_designation = trim($extended[10]);
    if (is_null($processor_timestamp)){
      $sql  = "SELECT MAX(processor_timestamp) FROM processor WHERE processor_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(processor_timestamp)"]) {$processor_timestamp = $myrow["MAX(processor_timestamp)"];} else {$processor_timestamp = "";}
    } else {}
    $sql  = "SELECT count(processor_device_id) as count FROM processor WHERE processor_uuid = '$uuid' AND ";
    $sql .= "processor_caption = '$processor_caption' AND processor_current_clock_speed = '$processor_current_clock_speed' AND ";
    $sql .= "processor_current_voltage = '$processor_current_voltage' AND processor_device_id = '$processor_device_id' AND ";
    $sql .= "processor_ext_clock = '$processor_ext_clock' AND processor_manufacturer = '$processor_manufacturer' AND ";
    $sql .= "processor_max_clock_speed = '$processor_max_clock_speed' AND processor_name = '$processor_name' AND ";
    $sql .= "processor_power_management_supported = '$processor_power_management_supported' AND ";
    $sql .= "processor_socket_designation = '$processor_socket_designation' AND (processor_timestamp = '$processor_timestamp' OR processor_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO processor (processor_uuid, processor_caption, processor_current_clock_speed, ";
      $sql .= "processor_current_voltage, processor_device_id, processor_ext_clock, ";
      $sql .= "processor_manufacturer, processor_max_clock_speed, processor_name, ";
      $sql .= "processor_power_management_supported, processor_socket_designation, processor_timestamp, processor_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$processor_caption', '$processor_current_clock_speed', ";
      $sql .= "'$processor_current_voltage', '$processor_device_id', '$processor_ext_clock', ";
      $sql .= "'$processor_manufacturer', '$processor_max_clock_speed', '$processor_name', ";
      $sql .= "'$processor_power_management_supported', '$processor_socket_designation', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql  = "UPDATE processor SET processor_timestamp = '$timestamp' WHERE processor_device_id = '$processor_device_id' AND ";
      $sql .= "processor_uuid = '$uuid' AND processor_timestamp = '$processor_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_bios ($split){
    global $timestamp, $uuid, $verbose, $bios_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>BIOS</h2><br />";}
    $bios_description = trim($extended[1]);
    $bios_manufacturer = trim($extended[2]);
    $bios_serial_number = trim($extended[3]);
    $bios_sm_bios_version = trim($extended[4]);
    $bios_version = trim($extended[5]);
    $bios_asset = trim($extended[6]);
    if (is_null($bios_timestamp)){
      $sql  = "SELECT MAX(bios_timestamp) FROM bios WHERE bios_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(bios_timestamp)"]) {$bios_timestamp = $myrow["MAX(bios_timestamp)"];} else {$bios_timestamp = "";}
    } else {}
    $sql  = "SELECT count(bios_uuid) as count FROM bios WHERE ";
    $sql .= "bios_description = '$bios_description' AND bios_manufacturer = '$bios_manufacturer' AND ";
    $sql .= "bios_serial_number = '$bios_serial_number' AND bios_sm_bios_version = '$bios_sm_bios_version' AND ";
    $sql .= "bios_version = '$bios_version' AND bios_asset_tag = '$bios_asset' AND (bios_timestamp = '$timestamp' OR bios_timestamp = '$bios_timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO bios (bios_uuid, bios_description, bios_manufacturer, bios_serial_number, ";
      $sql .= "bios_sm_bios_version, bios_version, bios_asset_tag, bios_first_timestamp, bios_timestamp) VALUES (";
      $sql .= "'$uuid', '$bios_description', '$bios_manufacturer', '$bios_serial_number', ";
      $sql .= "'$bios_sm_bios_version', '$bios_version', '$bios_asset', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE bios SET bios_timestamp = '$timestamp' WHERE bios_uuid = '$uuid' AND bios_timestamp = '$bios_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_memory ($split){
    global $timestamp, $uuid, $verbose, $memory_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Memory</h2><br />";}
    $memory_bank = trim($extended[1]);
    $memory_form_factor = trim($extended[2]);
    $memory_type = trim($extended[3]);
    $memory_detail = trim($extended[4]);
    $memory_capacity = trim($extended[5]);
    $memory_speed = trim($extended[6]);
    $memory_tag = trim($extended[7]);
    if (is_null($memory_timestamp)){
      $sql  = "SELECT MAX(memory_timestamp) FROM memory WHERE memory_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(memory_timestamp)"]) {$memory_timestamp = $myrow["MAX(memory_timestamp)"];} else {$memory_timestamp = "";}
    } else {}
    $sql  = "SELECT count(memory_bank) as count FROM memory WHERE ";
    $sql .= "memory_bank = '$memory_bank' AND memory_type = '$memory_type' AND ";
    $sql .= "memory_form_factor = '$memory_form_factor' AND memory_detail = '$memory_detail' AND ";
    $sql .= "memory_capacity = '$memory_capacity' AND memory_speed = '$memory_speed' AND memory_tag = '$memory_tag' AND ";
    $sql .= "memory_uuid = '$uuid' AND (memory_timestamp = '$memory_timestamp' OR memory_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO memory (memory_uuid, memory_bank, memory_type, memory_form_factor, ";
      $sql .= "memory_detail, memory_capacity, memory_speed, memory_tag, memory_timestamp, memory_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$memory_bank', '$memory_type', '$memory_form_factor', ";
      $sql .= "'$memory_detail', '$memory_capacity', '$memory_speed', '$memory_tag', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE memory SET memory_timestamp = '$timestamp' WHERE memory_bank = '$memory_bank' AND memory_tag = '$memory_tag' AND memory_uuid = '$uuid' AND memory_timestamp = '$memory_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_optionalfeatures ($split){
    global $timestamp, $uuid, $verbose;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Optional Features</h2><br />";}
    $optional_caption = trim($extended[1]);
    $optional_name = trim($extended[2]);
    $sql  = "DELETE FROM optionalfeatures WHERE opt_uuid = '$uuid' AND name = '$optional_name'";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Delete failed: ' . mysqli_error($db) . '<br />' . $sql);
    // Insert into database
	$sql =  "INSERT INTO optionalfeatures (opt_uuid, caption, name) VALUES ( ";
	$sql .= "'$uuid', '$optional_caption', '$optional_name' )";
	if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
  }

function insert_video ($split){
    global $timestamp, $uuid, $verbose, $video_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Video</h2><br />";}
    $video_adapter_ram = trim($extended[1]);
    $video_caption = trim($extended[2]);
    $video_current_horizontal_res = trim($extended[3]);
    $video_current_number_colours = trim($extended[4]);
    $video_current_refresh_rate = trim($extended[5]);
    $video_current_vertical_res = trim($extended[6]);
    $video_description = trim($extended[7]);
    $video_driver_date = trim($extended[8]);
    $video_driver_version = trim($extended[9]);
    $video_max_refresh_rate = trim($extended[10]);
    $video_min_refresh_rate = trim($extended[11]);
    $video_device_id = trim($extended[12]);
    if (is_null($video_timestamp)){
      $sql  = "SELECT MAX(video_timestamp) FROM video WHERE video_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(video_timestamp)"]) {$video_timestamp = $myrow["MAX(video_timestamp)"];} else {$video_timestamp = "";}
    } else {}
    $sql  = "SELECT count(video_device_id) AS count FROM video WHERE video_uuid = '$uuid' AND ";
    $sql .= "video_adapter_ram = '$video_adapter_ram' AND video_caption = '$video_caption' AND video_current_horizontal_res = '$video_current_horizontal_res' AND ";
    $sql .= "video_current_number_colours = '$video_current_number_colours' AND video_current_refresh_rate = '$video_current_refresh_rate' AND ";
    $sql .= "video_current_vertical_res = '$video_current_vertical_res' AND video_description = '$video_description' AND ";
    $sql .= "video_driver_date = '$video_driver_date' AND video_max_refresh_rate = '$video_max_refresh_rate' AND ";
    $sql .= "video_min_refresh_rate = '$video_min_refresh_rate' AND video_device_id = '$video_device_id' AND ";
    $sql .= "(video_timestamp = '$video_timestamp' OR video_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql =  "INSERT INTO video (video_uuid, video_adapter_ram, video_caption, video_current_horizontal_res, ";
      $sql .= "video_current_number_colours, video_current_refresh_rate, video_current_vertical_res, ";
      $sql .= "video_description, video_driver_date, video_driver_version, video_max_refresh_rate, video_min_refresh_rate, ";
      $sql .= "video_device_id, video_timestamp, video_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$video_adapter_ram', '$video_caption', '$video_current_horizontal_res', ";
      $sql .= "'$video_current_number_colours', '$video_current_refresh_rate', '$video_current_vertical_res', ";
      $sql .= "'$video_description', '$video_driver_date', '$video_driver_version', '$video_max_refresh_rate', '$video_min_refresh_rate', ";
      $sql .= "'$video_device_id', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE video SET video_timestamp = '$timestamp' WHERE video_device_id = '$video_device_id' AND video_uuid = '$uuid' AND video_timestamp = '$video_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }

function insert_monitor ($split){
    global $timestamp, $uuid, $verbose, $monitor_timestamp;
    if ($verbose == "y"){echo "<h2>Monitor</h2><br />";}
    $extended = explode('^^^',$split);
    $monitor_manufacturer = trim($extended[1]);
    $monitor_deviceid = trim($extended[2]);
    $monitor_manufacture_date = trim($extended[3]);
    $monitor_model = trim($extended[4]);
    $monitor_serial = trim($extended[5]);
    $monitor_edid = trim($extended[6]);
    if (is_null($monitor_timestamp)){
      $sql  = "SELECT MAX(monitor_timestamp) FROM monitor WHERE monitor_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(monitor_timestamp)"]) {$monitor_timestamp = $myrow["MAX(monitor_timestamp)"];} else {$monitor_timestamp = "";}
    } else {}
    $sql  = "SELECT count(monitor_deviceid) AS count FROM monitor WHERE monitor_manufacturer = '$monitor_manufacturer' AND ";
    $sql .= "monitor_deviceid = '$monitor_deviceid' AND monitor_manufacture_date = '$monitor_manufacture_date' AND ";
    $sql .= "monitor_model = '$monitor_model' AND monitor_serial = '$monitor_serial' AND ";
    $sql .= "monitor_edid = '$monitor_edid' AND (monitor_timestamp = '$monitor_timestamp' OR monitor_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO monitor (monitor_uuid, monitor_manufacturer, monitor_deviceid, ";
      $sql .= "monitor_manufacture_date, monitor_model, monitor_serial, ";
      $sql .= "monitor_edid, monitor_timestamp, monitor_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$monitor_manufacturer', '$monitor_deviceid', ";
      $sql .= "'$monitor_manufacture_date', '$monitor_model', '$monitor_serial', ";
      $sql .= "'$monitor_edid', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE monitor SET monitor_timestamp = '$timestamp' WHERE monitor_serial = '$monitor_serial' AND monitor_uuid = '$uuid' AND monitor_timestamp = '$monitor_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }

function insert_usb ($split){
    global $timestamp, $uuid, $verbose, $usb_timestamp;
    if ($verbose == "y"){echo "<h2>USB Device</h2><br />";}
    $extended = explode('^^^',$split);
    $usb_caption = trim($extended[1]);
    $usb_description = trim($extended[2]);
    $usb_manufacturer = trim($extended[3]);
    $usb_device_id = trim($extended[4]);
    if (is_null($usb_timestamp)){
      $sql  = "SELECT MAX(usb_timestamp) FROM usb WHERE usb_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(usb_timestamp)"]) {$usb_timestamp = $myrow["MAX(usb_timestamp)"];} else {$usb_timestamp = "";}
    } else {}
    $sql  = "SELECT count(usb_uuid) AS count FROM usb WHERE usb_uuid = '$uuid' AND usb_caption = '$usb_caption' AND ";
    $sql .= "usb_description = '$usb_description' AND usb_manufacturer = '$usb_manufacturer' AND ";
    $sql .= "usb_device_id = '$usb_device_id' AND (usb_timestamp = '$usb_timestamp' OR usb_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO usb (usb_uuid, usb_caption, usb_description, usb_manufacturer, ";
      $sql .= "usb_device_id, usb_timestamp, usb_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$usb_caption', '$usb_description', '$usb_manufacturer', ";
      $sql .= "'$usb_device_id', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE usb SET usb_timestamp = '$timestamp' WHERE usb_device_id = '$usb_device_id' AND usb_uuid = '$uuid' AND usb_timestamp = '$usb_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_harddrive ($split){
    global $timestamp, $uuid, $verbose, $hard_drive_timestamp;
    if ($verbose == "y"){echo "<h2>Hard Drive</h2><br />";}
    $extended = explode('^^^',$split);
    $hard_drive_caption = trim($extended[1]);
    $hard_drive_index = trim($extended[2]);
    $hard_drive_interface_type = trim($extended[3]);
    $hard_drive_manufacturer = trim($extended[4]);
    $hard_drive_model = trim($extended[5]);
    $hard_drive_partitions = trim($extended[6]);
    $hard_drive_scsi_bus = trim($extended[7]);
    $hard_drive_scsi_logical_unit = trim($extended[8]);
    $hard_drive_scsi_port = trim($extended[9]);
    $hard_drive_size = trim($extended[10]);
    $hard_drive_pnpid = trim($extended[11]);
    $hard_drive_status = trim($extended[12]);
	$hard_drive_predicted_failure = trim($extended[13]);
    if (is_null($hard_drive_timestamp)){
      $sql = "SELECT MAX(hard_drive_timestamp) FROM hard_drive WHERE hard_drive_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(hard_drive_timestamp)"]) {$hard_drive_timestamp = $myrow["MAX(hard_drive_timestamp)"];} else {$hard_drive_timestamp = "";}
    } else {}
    $sql  = "SELECT count(hard_drive_uuid) AS count FROM hard_drive ";
	$sql .= "WHERE hard_drive_uuid = '$uuid' AND hard_drive_caption = '$hard_drive_caption' ";
    $sql .= "AND hard_drive_index = '$hard_drive_index' AND hard_drive_interface_type = '$hard_drive_interface_type' ";
    $sql .= "AND hard_drive_manufacturer = '$hard_drive_manufacturer' AND hard_drive_model = '$hard_drive_model' ";
    $sql .= "AND hard_drive_scsi_bus = '$hard_drive_scsi_bus' AND hard_drive_scsi_logical_unit = '$hard_drive_scsi_logical_unit' ";
    $sql .= "AND hard_drive_scsi_port = '$hard_drive_scsi_port' AND hard_drive_size = '$hard_drive_size' ";
    $sql .= "AND hard_drive_pnpid = '$hard_drive_pnpid' ";
	$sql .= "AND (hard_drive_timestamp = '$hard_drive_timestamp' OR hard_drive_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO hard_drive (hard_drive_uuid, hard_drive_caption, hard_drive_index, ";
      $sql .= "hard_drive_interface_type, hard_drive_manufacturer, hard_drive_model, ";
      $sql .= "hard_drive_partitions, hard_drive_scsi_bus, hard_drive_scsi_logical_unit, ";
      $sql .= "hard_drive_scsi_port, hard_drive_size, hard_drive_timestamp, ";
      $sql .= "hard_drive_first_timestamp, hard_drive_pnpid, hard_drive_status, ";
	  $sql .= "hard_drive_predicted_failure) VALUES (";
      $sql .= "'$uuid', '$hard_drive_caption', '$hard_drive_index', ";
      $sql .= "'$hard_drive_interface_type', '$hard_drive_manufacturer', '$hard_drive_model', ";
      $sql .= "'$hard_drive_partitions', '$hard_drive_scsi_bus', '$hard_drive_scsi_logical_unit', ";
      $sql .= "'$hard_drive_scsi_port', '$hard_drive_size', '$timestamp', ";
      $sql .= "'$timestamp', '$hard_drive_pnpid', '$hard_drive_status', ";
	  $sql .= "'$hard_drive_predicted_failure')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp and dynamic fields 
      $sql  = "UPDATE hard_drive SET ";
	  $sql .= "hard_drive_timestamp = '$timestamp', hard_drive_partitions = '$hard_drive_partitions', ";
	  $sql .= "hard_drive_status = '$hard_drive_status', hard_drive_predicted_failure = '$hard_drive_predicted_failure' ";
	  $sql .= "WHERE hard_drive_uuid = '$uuid' AND hard_drive_caption = '$hard_drive_caption' ";
      $sql .= "AND hard_drive_index = '$hard_drive_index' AND hard_drive_interface_type = '$hard_drive_interface_type' ";
      $sql .= "AND hard_drive_manufacturer = '$hard_drive_manufacturer' AND hard_drive_model = '$hard_drive_model' ";
      $sql .= "AND hard_drive_scsi_bus = '$hard_drive_scsi_bus' AND hard_drive_scsi_logical_unit = '$hard_drive_scsi_logical_unit' ";
      $sql .= "AND hard_drive_scsi_port = '$hard_drive_scsi_port' AND hard_drive_size = '$hard_drive_size' ";
      $sql .= "AND hard_drive_pnpid = '$hard_drive_pnpid' ";
	  $sql .= "AND hard_drive_timestamp = '$hard_drive_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_partition ($split){
        global $timestamp, $uuid, $verbose, $partition_timestamp;
        if ($verbose == "y"){echo "<h2>Partition</h2><br />";}
        $extended                    = explode('^^^',$split);
        $partition_bootable          = trim($extended[1]);
        $partition_boot_partition    = trim($extended[2]);
        $partition_device_id         = trim($extended[3]);
        $partition_disk_index        = trim($extended[4]);
        $partition_index             = trim($extended[5]);
        $partition_percent           = trim($extended[6]);
        $partition_primary_partition = trim($extended[7]);
        $partition_caption           = trim($extended[8]);
        $partition_file_system       = trim($extended[9]);
        $partition_free_space        = trim($extended[10]);
        $partition_size              = trim($extended[11]);
        $partition_volume_name       = trim($extended[12]);
        $partition_used_space        = trim($extended[13]);
        $partition_type		        = trim($extended[14]);
        $partition_bitlocker	    = trim($extended[15]);
        if (is_null($partition_timestamp)){
          $sql = "SELECT MAX(partition_timestamp) FROM `partition` WHERE partition_uuid = '$uuid'";
          if ($verbose == "y"){echo $sql . "<br />\n\n";}
          $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
          $myrow = mysqli_fetch_array($result);
          if ($myrow["MAX(partition_timestamp)"]) {$partition_timestamp = $myrow["MAX(partition_timestamp)"];} else {$partition_timestamp = "";}
        } else {}
        $sql  = "SELECT count(partition_uuid) AS count FROM `partition` WHERE partition_uuid = '$uuid' AND ";
        $sql .= "partition_caption = '$partition_caption' AND partition_file_system = '$partition_file_system' AND ";
        $sql .= "partition_size = '$partition_size' AND partition_volume_name = '$partition_volume_name' AND ";
        $sql .= "(partition_timestamp = '$partition_timestamp' OR partition_timestamp = '$timestamp')";
        if ($verbose == "y"){echo $sql . "<br />\n\n";}
        $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
        $myrow = mysqli_fetch_array($result);
        if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
        if ($myrow['count'] == "0"){
          // Insert into database
          $sql  = "INSERT into `partition` (partition_uuid, partition_bootable, partition_boot_partition, ";
          $sql .= "partition_device_id, partition_disk_index, partition_index, ";
          $sql .= "partition_primary_partition, partition_caption, partition_file_system, ";
          $sql .= "partition_free_space, partition_size, partition_volume_name, partition_used_space, partition_type, partition_bitlocker, ";
          $sql .= "partition_timestamp, partition_first_timestamp) VALUES (";
          $sql .= "'$uuid', '$partition_bootable', '$partition_boot_partition', ";
          $sql .= "'$partition_device_id', '$partition_disk_index', '$partition_index', ";
          $sql .= "'$partition_primary_partition', '$partition_caption', '$partition_file_system', ";
          $sql .= "'$partition_free_space', '$partition_size', '$partition_volume_name', '$partition_used_space', '$partition_type', '$partition_bitlocker', ";
          $sql .= "'$timestamp', '$timestamp')";
          if ($verbose == "y"){echo $sql . "<br />\n\n";}
          $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
        } else {
          // Already present in database - update timestamp & freespace & Win32_DiskPartition info
          $sql  = "update `partition` SET partition_timestamp = '$timestamp', partition_bootable = '$partition_bootable', ";
          $sql .= "partition_boot_partition = '$partition_boot_partition', partition_device_id = '$partition_device_id', ";
          $sql .= "partition_disk_index = '$partition_disk_index', partition_index = '$partition_index', ";
          $sql .= "partition_primary_partition = '$partition_primary_partition', ";
          $sql .= "partition_used_space = '$partition_used_space', ";
          $sql .= "partition_free_space = '$partition_free_space' WHERE partition_caption = '$partition_caption' AND ";
          $sql .= "partition_uuid = '$uuid' AND partition_timestamp = '$partition_timestamp'";
          if ($verbose == "y"){echo $sql . "<br />\n\n";}
          $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
        }
        $sql  = "INSERT INTO graphs_disk (disk_uuid, disk_timestamp, disk_letter, disk_percent) VALUES (";
        $sql .= "'$uuid', '$timestamp', '$partition_caption', '$partition_percent')";
        if ($verbose == "y"){echo "<br />" . $sql . "<br />\n\n";}
        $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
}

function insert_scsi_controller ($split) {
    global $timestamp, $uuid, $verbose, $scsi_controller_timestamp;
    if ($verbose == "y"){echo "<h2>SCSI Controller</h2><br />";}
    $extended = explode('^^^',$split);
    $scsi_controller_caption = trim($extended[1]);
    $scsi_controller_device_id = trim($extended[2]);
    $scsi_controller_manufacturer = trim($extended[3]);
    if (is_null($scsi_controller_timestamp)){
      $sql = "SELECT MAX(scsi_controller_timestamp) FROM scsi_controller WHERE scsi_controller_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(scsi_controller_timestamp)"]) {$scsi_controller_timestamp = $myrow["MAX(scsi_controller_timestamp)"];} else {$scsi_controller_timestamp = "";}
    } else {}
    $sql  = "SELECT count(scsi_controller_uuid) AS count FROM scsi_controller WHERE scsi_controller_uuid = '$uuid' AND ";
    $sql .= "scsi_controller_caption = '$scsi_controller_caption' AND scsi_controller_manufacturer = '$scsi_controller_manufacturer' AND ";
    $sql .= "scsi_controller_device_id = '$scsi_controller_device_id' AND (scsi_controller_timestamp = '$scsi_controller_timestamp' OR scsi_controller_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO scsi_controller (scsi_controller_uuid, scsi_controller_caption, scsi_controller_device_id, scsi_controller_manufacturer, ";
      $sql .= "scsi_controller_timestamp, scsi_controller_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$scsi_controller_caption', '$scsi_controller_device_id', '$scsi_controller_manufacturer', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE scsi_controller SET scsi_controller_timestamp = '$timestamp' WHERE scsi_controller_device_id = '$scsi_controller_device_id' AND scsi_controller_uuid = '$uuid' AND scsi_controller_timestamp = '$scsi_controller_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }

function insert_scsi_device ($split) {
    global $timestamp, $uuid, $verbose, $scsi_device_timestamp;
    if ($verbose == "y"){echo "<h2>SCSI Device</h2><br />";}
    $extended = explode('^^^',$split);

    $scsi_device_controller = trim($extended[1]);
    $first = explode("\'", $scsi_device_controller);
    if ( isset($first[1])){
    $scsi_device_controller = $first[1];
    $scsi_device_controller = str_replace("\\\\", "\\" , $scsi_device_controller);
    $scsi_device_controller = substr($scsi_device_controller, 0, (strlen($scsi_device_controller) -2));

    $scsi_device_device = trim($extended[2]);
    $second = explode("\'", $scsi_device_device);
    $scsi_device_device = $second[1];
    $scsi_device_device = str_replace("\\\\", "\\", $scsi_device_device);
    $scsi_device_device = substr($scsi_device_device, 0, (strlen($scsi_device_device) -2));

    echo $scsi_device_controller . "<br />" . $scsi_device_device . "<br />";

    if (is_null($scsi_device_timestamp)){
      $sql = "SELECT MAX(scsi_device_timestamp) FROM scsi_device WHERE scsi_device_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(scsi_device_timestamp)"]) {$scsi_device_timestamp = $myrow["MAX(scsi_device_timestamp)"];} else {$scsi_device_timestamp = "";}
    } else {}
    $sql  = "SELECT count(scsi_device_uuid) AS count FROM scsi_device WHERE scsi_device_uuid = '$uuid' AND ";
    $sql .= "scsi_device_controller = '$scsi_device_controller' AND scsi_device_device = '$scsi_device_device' AND ";
    $sql .= "(scsi_device_timestamp = '$scsi_device_timestamp' OR scsi_device_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO scsi_device (scsi_device_uuid, scsi_device_controller, scsi_device_device, ";
      $sql .= "scsi_device_timestamp, scsi_device_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$scsi_device_controller', '$scsi_device_device', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql  = "UPDATE scsi_device SET scsi_device_timestamp = '$timestamp' WHERE scsi_device_controller = '$scsi_device_controller' ";
      $sql .= "AND scsi_device_device = '$scsi_device_device' AND scsi_device_uuid = '$uuid' AND scsi_device_timestamp = '$scsi_device_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
   }
  }

function insert_optical ($split) {
    global $timestamp, $uuid, $verbose, $optical_drive_timestamp;
    if ($verbose == "y"){echo "<h2>Optical Drive</h2><br />";}
    $extended = explode('^^^',$split);
    $optical_drive_caption = trim($extended[1]);
    $optical_drive_drive = trim($extended[2]);
    $optical_device_id = trim($extended[3]);
    if (is_null($optical_drive_timestamp)){
      $sql = "SELECT MAX(optical_drive_timestamp) FROM optical_drive WHERE optical_drive_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(optical_drive_timestamp)"]) {$optical_drive_timestamp = $myrow["MAX(optical_drive_timestamp)"];} else {$optical_drive_timestamp = "";}
    } else {}
    $sql  = "SELECT count(optical_drive_uuid) AS count FROM optical_drive WHERE optical_drive_uuid = '$uuid' AND ";
    $sql .= "optical_drive_caption = '$optical_drive_caption' AND optical_drive_drive = '$optical_drive_drive' AND ";
    $sql .= "optical_drive_device_id = '$optical_device_id' AND (optical_drive_timestamp = '$optical_drive_timestamp' OR optical_drive_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO optical_drive (optical_drive_uuid, optical_drive_caption, optical_drive_drive, optical_drive_device_id, ";
      $sql .= "optical_drive_timestamp, optical_drive_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$optical_drive_caption', '$optical_drive_drive', '$optical_device_id', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE optical_drive SET optical_drive_timestamp = '$timestamp' WHERE optical_drive_device_id = '$optical_device_id' AND optical_drive_uuid = '$uuid' AND optical_drive_timestamp = '$optical_drive_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_floppy ($split){
    global $timestamp, $uuid, $verbose, $floppy_timestamp;
    if ($verbose == "y"){echo "<h2>Floppy Drive</h2><br />";}
    $extended = explode('^^^',$split);
    $floppy_description = trim($extended[1]);
    $floppy_manufacturer = trim($extended[2]);
    $floppy_caption = trim($extended[3]);
    $floppy_device_id = trim($extended[4]);
    if (is_null($floppy_timestamp)){
      $sql = "SELECT MAX(floppy_timestamp) FROM floppy WHERE floppy_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(floppy_timestamp)"]) {$floppy_timestamp = $myrow["MAX(floppy_timestamp)"];} else {$floppy_timestamp = "";}
    } else {}
    $sql  = "SELECT count(floppy_uuid) AS count FROM floppy WHERE floppy_uuid = '$uuid' AND ";
    $sql .= "floppy_description = '$floppy_description' AND floppy_manufacturer = '$floppy_manufacturer' AND ";
    $sql .= "floppy_caption = '$floppy_caption' AND floppy_device_id = '$floppy_device_id' AND (floppy_timestamp = '$floppy_timestamp' OR floppy_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO floppy (floppy_uuid, floppy_description, ";
      $sql .= "floppy_manufacturer, floppy_caption, floppy_device_id, floppy_timestamp, floppy_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$floppy_description', ";
      $sql .= "'$floppy_manufacturer', '$floppy_caption', '$floppy_device_id', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE floppy SET floppy_timestamp = '$timestamp' WHERE floppy_device_id = '$floppy_device_id' AND floppy_uuid = '$uuid' AND floppy_timestamp = '$floppy_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }



function insert_tape ($split) {
    global $timestamp, $uuid, $verbose, $tape_drive_timestamp;
    if ($verbose == "y"){echo "<h2>Tape Drive</h2><br />";}
    $extended = explode('^^^',$split);
    $tape_drive_caption = trim($extended[1]);
    $tape_drive_description = trim($extended[2]);
    $tape_drive_manufacturer = trim($extended[3]);
    $tape_drive_name = trim($extended[4]);
    $tape_drive_device_id = trim($extended[5]);
    if (is_null($tape_drive_timestamp)){
      $sql = "SELECT MAX(tape_drive_timestamp) FROM tape_drive WHERE tape_drive_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(tape_drive_timestamp)"]) {$tape_drive_timestamp = $myrow["MAX(tape_drive_timestamp)"];} else {$tape_drive_timestamp = "";}
    } else {}
    $sql  = "SELECT count(tape_drive_uuid) AS count FROM tape_drive WHERE tape_drive_uuid = '$uuid' AND ";
    $sql .= "tape_drive_caption = '$tape_drive_caption' AND tape_drive_description = '$tape_drive_description' AND ";
    $sql .= "tape_drive_manufacturer = '$tape_drive_manufacturer' AND tape_drive_name = '$tape_drive_name' AND ";
    $sql .= "tape_drive_device_id = '$tape_drive_device_id' AND (tape_drive_timestamp = '$tape_drive_timestamp' OR tape_drive_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO tape_drive (tape_drive_uuid, tape_drive_caption, tape_drive_description, ";
      $sql .= "tape_drive_manufacturer, tape_drive_name, tape_drive_device_id, tape_drive_timestamp, tape_drive_first_timestamp ) VALUES (";
      $sql .= "'$uuid', '$tape_drive_caption', '$tape_drive_description', ";
      $sql .= "'$tape_drive_manufacturer', '$tape_drive_name', '$tape_drive_device_id', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE tape_drive SET tape_drive_timestamp = '$timestamp' WHERE tape_drive_device_id = '$tape_drive_device_id' AND tape_drive_uuid = '$uuid' AND tape_drive_timestamp = '$tape_drive_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }



function insert_keyboard ($split) {
    global $timestamp, $uuid, $verbose, $keyboard_timetamp;
    if ($verbose == "y"){echo "<h2>Keyboard</h2><br />";}
    $extended = explode('^^^',$split);
    $keyboard_caption = trim($extended[1]);
    $keyboard_description = trim($extended[2]);
    $keyboard_device_id = trim($extended[3]);
    if (is_null($keyboard_timetamp)){
      $sql = "SELECT MAX(keyboard_timestamp) FROM keyboard WHERE keyboard_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(keyboard_timestamp)"]) {$keyboard_timestamp = $myrow["MAX(keyboard_timestamp)"];} else {$keyboard_timestamp = "";}
    } else {}
    $sql  = "SELECT count(keyboard_uuid) AS count FROM keyboard WHERE keyboard_uuid = '$uuid' AND ";
    $sql .= "keyboard_description = '$keyboard_description' AND ";
    $sql .= "keyboard_device_id = '$keyboard_device_id' AND (keyboard_timestamp = '$keyboard_timestamp' OR keyboard_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO keyboard (keyboard_uuid, keyboard_description, keyboard_caption, keyboard_device_id, keyboard_timestamp, keyboard_first_timestamp ) VALUES (";
      $sql .= "'$uuid', '$keyboard_description', '$keyboard_caption', '$keyboard_device_id', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE keyboard SET keyboard_timestamp = '$timestamp' WHERE keyboard_device_id = '$keyboard_device_id' AND keyboard_uuid = '$uuid' AND keyboard_timestamp = '$keyboard_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_battery ($split){
    global $timestamp, $uuid, $verbose, $battery_timestamp;
    if ($verbose == "y"){echo "<h2>Battery</h2><br />";}
    $extended = explode('^^^',$split);
    $battery_description = trim($extended[1]);
    $battery_device_id = trim($extended[2]);
    if (is_null($battery_timestamp)){
      $sql = "SELECT MAX(battery_timestamp) FROM battery WHERE battery_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(battery_timestamp)"]) {$battery_timestamp = $myrow["MAX(battery_timestamp)"];} else {$battery_timestamp = "";}
    } else {}
    $sql  = "SELECT count(battery_uuid) AS count FROM battery WHERE battery_uuid = '$uuid' AND ";
    $sql .= "battery_description = '$battery_description' AND ";
    $sql .= "battery_device_id = '$battery_device_id' AND (battery_timestamp = '$battery_timestamp' OR battery_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO battery (battery_uuid, battery_description, battery_device_id, battery_timestamp, battery_first_timestamp ) VALUES (";
      $sql .= "'$uuid', '$battery_description', '$battery_device_id', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE battery SET battery_timestamp = '$timestamp' WHERE battery_device_id = '$battery_device_id' AND battery_uuid = '$uuid' AND battery_timestamp = '$battery_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_modem ($split) {
    global $timestamp, $uuid, $verbose, $modem_timestamp;
    if ($verbose == "y"){echo "<h2>Modem</h2><br />";}
    $extended = explode('^^^',$split);
    $modem_attached_to = trim($extended[1]);
    $modem_country_selected = trim($extended[2]);
    $modem_description = trim($extended[3]);
    $modem_device_type = trim($extended[4]);
    $modem_device_id = trim($extended[5]);
    if (is_null($modem_timestamp)){
      $sql = "SELECT MAX(modem_timestamp) FROM modem WHERE modem_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(modem_timestamp)"]) {$modem_timestamp = $myrow["MAX(modem_timestamp)"];} else {$modem_timestamp = "";}
    } else {}
    $sql  = "SELECT count(modem_uuid) AS count FROM modem WHERE modem_uuid = '$uuid' AND ";
    $sql .= "modem_attached_to = '$modem_attached_to' AND modem_country_selected = '$modem_country_selected' AND ";
    $sql .= "modem_description = '$modem_description' AND modem_device_type = '$modem_device_type' AND ";
    $sql .= "modem_device_id = '$modem_device_id' AND (modem_timestamp = '$modem_timestamp' OR modem_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO modem (modem_uuid, modem_attached_to, modem_country_selected, ";
      $sql .= "modem_description, modem_device_type, modem_device_id, modem_timestamp, modem_first_timestamp ) VALUES (";
      $sql .= "'$uuid', '$modem_attached_to', '$modem_country_selected', ";
      $sql .= "'$modem_description', '$modem_device_type', '$modem_device_id', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE modem SET modem_timestamp = '$timestamp' WHERE modem_device_id = '$modem_device_id' AND modem_uuid = '$uuid' AND modem_timestamp = '$modem_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_mouse ($split) {
    global $timestamp, $uuid, $verbose, $mouse_timestamp;
    if ($verbose == "y"){echo "<h2>Mouse</h2><br />";}
    $extended = explode('^^^',$split);
    $mouse_description = trim($extended[1]);
    $mouse_number_of_buttons = trim($extended[2]);
    $mouse_device_id = trim($extended[3]);
    $mouse_type = trim($extended[4]);
    $mouse_port = trim($extended[5]);
    if (is_null($mouse_timestamp)){
      $sql = "SELECT MAX(mouse_timestamp) FROM mouse WHERE mouse_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(mouse_timestamp)"]) {$mouse_timestamp = $myrow["MAX(mouse_timestamp)"];} else {$mouse_timestamp = "";}
    } else {}
    $sql  = "SELECT count(mouse_uuid) AS count FROM mouse WHERE mouse_uuid = '$uuid' AND ";
    $sql .= "mouse_description = '$mouse_description' AND ";
    $sql .= "mouse_device_id = '$mouse_device_id' AND (mouse_timestamp = '$mouse_timestamp' OR mouse_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO mouse (mouse_uuid, mouse_description, mouse_number_of_buttons, mouse_device_id, ";
      $sql .= "mouse_type, mouse_port, mouse_timestamp, mouse_first_timestamp ) VALUES (";
      $sql .= "'$uuid', '$mouse_description', '$mouse_number_of_buttons', '$mouse_device_id', ";
      $sql .= "'$mouse_type', '$mouse_port', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE mouse SET mouse_timestamp = '$timestamp' WHERE mouse_device_id = '$mouse_device_id' AND mouse_uuid = '$uuid' ";
      $sql .= "AND mouse_description = '$mouse_description' AND mouse_timestamp = '$mouse_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_sound ($split) {
    global $timestamp, $uuid, $verbose, $sound_timestamp;
    if ($verbose == "y"){echo "<h2>Sound</h2><br />";}
    $extended = explode('^^^',$split);
    $sound_manufacturer = trim($extended[1]);
    $sound_name = trim($extended[2]);
    $sound_device_id = trim($extended[3]);
    if (is_null($sound_timestamp)){
      $sql = "SELECT MAX(sound_timestamp) FROM sound WHERE sound_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(sound_timestamp)"]) {$sound_timestamp = $myrow["MAX(sound_timestamp)"];} else {$sound_timestamp = "";}
    } else {}
    $sql  = "SELECT count(sound_uuid) AS count FROM sound WHERE sound_uuid = '$uuid' AND ";
    $sql .= "sound_manufacturer = '$sound_manufacturer' AND sound_name = '$sound_name' AND ";
    $sql .= "sound_device_id = '$sound_device_id' AND (sound_timestamp = '$sound_timestamp' OR sound_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO sound (sound_uuid, sound_manufacturer, sound_name, sound_device_id, sound_timestamp, sound_first_timestamp ) VALUES (";
      $sql .= "'$uuid', '$sound_manufacturer', '$sound_name', '$sound_device_id', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE sound SET sound_timestamp = '$timestamp' WHERE sound_device_id = '$sound_device_id' AND sound_uuid = '$uuid' AND sound_timestamp = '$sound_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_printer ($split){
    global $timestamp, $uuid, $verbose, $printer_timestamp ,$old_timestamp, $system_name;
    if ($verbose == "y"){echo "<h2>Printer</h2><br />";}
    $extended = explode('^^^',$split);
    $printer_caption = trim($extended[1]);
    $printer_local = trim($extended[2]); //
    $printer_port_name = trim($extended[3]);
    $printer_shared = trim($extended[4]);
    $printer_share_name = trim($extended[5]);
    $printer_system_name = strtoupper(str_replace('\\','',trim($extended[6])));
    $printer_location = trim($extended[7]);
	$printer_driver_name = trim($extended[8]);
	$printer_comment = trim($extended[9]);
    $printer_name = NULL;
    //if (strpos($printer_system_name,'\\\\') !== false ) { $printer_system_name = substr($printer_system_name, 2); }

    if ((strpos($printer_caption,'PDF') !== false) OR (strpos($printer_caption,'__') !== false) OR 
		(strpos($printer_caption,'Microsoft') !== false) OR (strpos($printer_caption,'in session') !== false)) {
    // A pdf, Terminal Server, Citrix or MS Office printer - Not physical, not inserted.
    } else {
    // A physical printer - insert

    if (strpos($printer_port_name,'IP_') !== false ) {
      // Network Printer
      echo "Network Printer<br />\n";
      if (strpos($printer_caption,'\\') !== false ) { $printer_name = substr($printer_caption, 2); }
      $printer_ip = ip_trans_to(substr($printer_port_name, 3));
      $printer_network_name = $printer_ip;
 //     $printer_network_name = nslookup(substr($printer_port_name, 3));
      if ($printer_network_name == ""){ $printer_network_name = $printer_ip; }
      if (strpos($printer_network_name,'\\') !== false ) { $printer_network_name = substr($printer_network_name, 2);}
      $sql = "SELECT count(other_ip_address) AS count FROM other WHERE other_ip_address = '" . ip_trans_to($printer_ip) . "'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow['count'] == "0"){
        // Insert
        $sql  = "INSERT INTO other (other_ip_address, other_description, other_location, other_value, other_type, other_model, ";
        $sql .= "other_network_name, other_p_port_name, other_p_shared, other_p_share_name, ";
        $sql .= "other_timestamp, other_first_timestamp) VALUES (";
        $sql .= "'" . ip_trans_to($printer_ip) . "', '$printer_caption', '$printer_location', '$printer_comment', 'printer', '$printer_driver_name', ";
        $sql .= "'$printer_network_name', '$printer_port_name', '$printer_shared', '$printer_share_name', ";
        $sql .= "'$timestamp', '$timestamp')";
        if ($verbose == "y"){echo $sql . "<br />\n\n";}
        $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      } else {
        // Update
       $sql  = "UPDATE other SET other_timestamp = '$timestamp', other_p_port_name = '$printer_network_name', ";
       $sql .= "       other_location = '$printer_location', other_value = '$printer_comment', other_description = '$printer_caption', ";
       $sql .= "       other_p_shared = '$printer_shared', other_p_share_name = '$printer_share_name', ";
       $sql .= "       other_model = '$printer_driver_name' ";
       $sql .= "WHERE other_ip_address = '" . ip_trans_to($printer_ip) . "'";
       if ($verbose == "y"){echo $sql . "<br />\n\n";}
       $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      }
    } else {
      // Locally Attached Printer
      // Below is to determine if is REALLY a local printer
      // If not, the audit of the PC $printer_system_name will be relied
      // upon to detect and insert the printer.
      echo "Local Printer<br />\n";
      if (($printer_system_name == $system_name) AND ($printer_port_name !== "FILE:") AND
          ($printer_port_name !== "MSFAX:") AND ($printer_port_name !== "SHRFAX:") AND
          ($printer_port_name !== "BIPORT") AND (substr($printer_port_name,0,2) !== "TS") AND
          ($printer_port_name !== "SmarThruFaxPort") AND ($printer_port_name !== "CLIENT")) {
        $printer_timestamp = $old_timestamp;
        $sql  = "SELECT count(other_linked_pc) AS count FROM other WHERE other_linked_pc = '$uuid' AND ";
        $sql .= "other_description = '$printer_caption' AND other_p_port_name = '$printer_port_name' AND ";
        $sql .= "(other_timestamp = '$printer_timestamp' OR other_timestamp = '$timestamp')";
        if ($verbose == "y"){echo $sql . "<br />\n\n";}
        $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
        $myrow = mysqli_fetch_array($result);
        if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
        if ($myrow['count'] == "0"){
        // Insert into database
        $sql  = "INSERT INTO other (other_linked_pc, other_description, other_type, ";
        $sql .= "other_model, other_p_port_name, ";
        $sql .= "other_p_shared, other_p_share_name, ";
        $sql .= "other_network_name, other_location, other_value";
        $sql .= "other_timestamp, other_first_timestamp ) VALUES (";
        $sql .= "'$uuid', '$printer_caption', 'printer', ";
        $sql .= "'$printer_driver_name', '$printer_port_name',";
        $sql .= "'$printer_shared', '$printer_share_name', ";
        $sql .= "'$printer_system_name', '$printer_location', '$printer_comment', ";
        $sql .= "'$timestamp', '$timestamp')";
        if ($verbose == "y"){echo $sql . "<br />\n\n";}
        $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      } else {
        // Already present in database - update timestamp and dynamic values
        $sql =  "UPDATE other SET other_timestamp = '$timestamp', other_location = '$printer_location', other_value = '$printer_comment', ";
		$sql .= "                 other_p_shared = '$printer_shared', other_p_share_name = '$printer_share_name', ";
		$sql .= "                 other_model = '$printer_driver_name' ";
		$sql .= "WHERE other_linked_pc = '$uuid' AND other_description = '$printer_caption' AND other_p_port_name = '$printer_port_name' ";
		$sql .= "      AND other_timestamp = '$printer_timestamp'";
        if ($verbose == "y"){echo $sql . "<br />\n\n";}
        $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      }
    } // End of local printer
    } // End of IP detection in printer_port
    } // End of pdf printer
  }


function insert_shares ($split) {
    global $timestamp, $uuid, $verbose, $shares_timestamp;
    if ($verbose == "y"){echo "<h2>Share</h2><br />";}
    $extended = explode('^^^',$split);
    $shares_caption = trim($extended[1]);
    $shares_name = trim($extended[2]);
    $shares_path = trim($extended[3]);
    if (is_null($shares_timestamp)){
      $sql = "SELECT MAX(shares_timestamp) FROM shares WHERE shares_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(shares_timestamp)"]) {$shares_timestamp = $myrow["MAX(shares_timestamp)"];} else {$shares_timestamp = "";}
    } else {}
    $sql  = "SELECT count(shares_uuid) AS count FROM shares WHERE shares_uuid = '$uuid' AND ";
    $sql .= "shares_caption = '$shares_caption' AND shares_name = '$shares_name' AND ";
    $sql .= "shares_path = '$shares_path' AND (shares_timestamp = '$shares_timestamp' OR shares_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO shares (shares_uuid, shares_caption, shares_name, ";
      $sql .= "shares_path, shares_timestamp, shares_first_timestamp ) VALUES (";
      $sql .= "'$uuid', '$shares_caption', '$shares_name', ";
      $sql .= "'$shares_path', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE shares SET shares_timestamp = '$timestamp' WHERE shares_name = '$shares_name' AND shares_uuid = '$uuid' AND shares_timestamp = '$shares_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_mapped ($split){
    global $timestamp, $uuid, $verbose, $mapped_timestamp;
    if ($verbose == "y"){echo "<h2>Mapped Drive</h2><br />";}
    $extended = explode('^^^',$split);
    $mapped_device_id = trim($extended[1]);
    $mapped_file_system = trim($extended[2]);
    $mapped_free_space = trim($extended[3]);
    $mapped_provider_name = trim($extended[4]);
    $mapped_size = trim($extended[5]);
    $mapped_username = trim($extended[6]);
    $mapped_connect_as = trim($extended[7]);
    if (is_null($mapped_timestamp)){
      $sql = "SELECT MAX(mapped_timestamp) FROM mapped WHERE mapped_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(mapped_timestamp)"]) {$mapped_timestamp = $myrow["MAX(mapped_timestamp)"];} else {$mapped_timestamp = "";}
    } else {}
    $sql  = "SELECT count(mapped_uuid) AS count FROM mapped WHERE mapped_uuid = '$uuid' AND ";
    $sql .= "mapped_device_id = '$mapped_device_id' AND mapped_username = '$mapped_username' AND ";
    $sql .= "(mapped_timestamp = '$mapped_timestamp' OR mapped_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New mapped drive - Insert into database
      $sql  = "INSERT INTO mapped (mapped_uuid, mapped_device_id, mapped_file_system, mapped_free_space, mapped_provider_name, ";
      $sql .= "mapped_size, mapped_username, mapped_connect_as, mapped_timestamp, mapped_first_timestamp ) VALUES (";
      $sql .= "'$uuid', '$mapped_device_id', '$mapped_file_system', '$mapped_free_space', '$mapped_provider_name', ";
      $sql .= "'$mapped_size', '$mapped_username', '$mapped_connect_as', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp and dynamic values
      $sql  = "UPDATE mapped SET mapped_file_system = '$mapped_file_system', mapped_free_space = '$mapped_free_space', ";
      $sql .= "mapped_provider_name = '$mapped_provider_name', mapped_size = '$mapped_size', mapped_connect_as = '$mapped_connect_as', ";
      $sql .= "mapped_timestamp = '$timestamp' WHERE mapped_uuid = '$uuid' AND ";
      $sql .= "mapped_device_id = '$mapped_device_id' AND mapped_username = '$mapped_username' AND mapped_timestamp = '$mapped_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_group ($split) {
    global $timestamp, $uuid, $verbose, $groups_timestamp;
    if ($verbose == "y"){echo "<h2>Local Group</h2><br />";}
    $extended = explode('^^^',$split);
    $groups_description = trim($extended[1]);
    $groups_name = trim($extended[2]);
    $groups_members = trim($extended[3]);
    $groups_sid = trim($extended[4]);
    if (is_null($groups_timestamp)){
      $sql = "SELECT MAX(groups_timestamp) FROM groups WHERE groups_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(groups_timestamp)"]) {$groups_timestamp = $myrow["MAX(groups_timestamp)"];} else {$groups_timestamp = "";}
    } else {}
    $sql  = "SELECT count(groups_uuid) AS count FROM groups WHERE groups_uuid = '$uuid' AND ";
    $sql .= "groups_name = '$groups_name' AND ";
    $sql .= "groups_members = '$groups_members' AND groups_sid = '$groups_sid' AND ";
    $sql .= "(groups_timestamp = '$groups_timestamp' OR groups_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO groups (groups_uuid, groups_name, groups_members, groups_sid, ";
      $sql .= "groups_timestamp, groups_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$groups_name', '$groups_members', '$groups_sid', ";
      $sql .= "'$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE groups SET groups_timestamp = '$timestamp' WHERE groups_sid = '$groups_sid' AND groups_uuid = '$uuid' AND groups_timestamp = '$groups_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
    $sql = "INSERT IGNORE INTO groups_details (gd_name, gd_description) VALUES ('$groups_name', '$groups_description')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
  }


function insert_user ($split) {
    global $timestamp, $uuid, $verbose, $users_timestamp;
    if ($verbose == "y"){echo "<h2>Local User</h2><br />";}
    $extended = explode('^^^',$split);
    $users_description = trim($extended[1]);
    $users_disabled = trim($extended[2]);
    $users_full_name = trim($extended[3]);
    $users_name = trim($extended[4]);
    $users_password_changeable = trim($extended[5]);
    $users_password_expires = trim($extended[6]);
    $users_password_required = trim($extended[7]);
    $users_sid = trim($extended[8]);
	$users_lockout = trim($extended[9]);
    if (is_null($users_timestamp)){
      $sql = "SELECT MAX(users_timestamp) FROM users WHERE users_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(users_timestamp)"]) {$users_timestamp = $myrow["MAX(users_timestamp)"];} else {$users_timestamp = "";}
    } else {}
    $sql  = "SELECT count(users_uuid) AS count FROM users ";
    $sql .= "WHERE users_uuid = '$uuid' AND users_sid = '$users_sid' AND ";
    $sql .= "(users_timestamp = '$users_timestamp' OR users_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New user - Insert into database
      $sql  = "INSERT INTO users (users_uuid, users_disabled, users_full_name, users_name, ";
      $sql .= "users_password_changeable, users_password_expires, users_password_required, users_sid, users_lockout, ";
      $sql .= "users_timestamp, users_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$users_disabled', '$users_full_name', '$users_name', ";
      $sql .= "'$users_password_changeable', '$users_password_expires', '$users_password_required', '$users_sid', '$users_lockout', ";
      $sql .= "'$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp and dynamic values
      $sql  = "UPDATE users SET users_disabled = '$users_disabled', users_full_name = '$users_full_name', ";
	  $sql .= "users_name = '$users_name', users_password_changeable = '$users_password_changeable', ";
	  $sql .= "users_password_expires = '$users_password_expires', users_password_required = '$users_password_required', ";
	  $sql .= "users_lockout = '$users_lockout', users_timestamp = '$timestamp' ";
	  $sql .= "WHERE users_uuid = '$uuid' AND users_sid = '$users_sid' AND users_timestamp = '$users_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
    $sql = "INSERT IGNORE INTO users_detail (ud_name, ud_description) VALUES ('$users_name', '$users_description')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
  }



function insert_startup ($split) {
    global $timestamp, $uuid, $verbose, $startup_timestamp;
    if ($verbose == "y"){echo "<h2>Startup</h2><br />";}
    $extended = explode('^^^',$split);
    $startup_caption = trim($extended[1]);
    $startup_command = trim($extended[2]);
    $startup_description = trim($extended[3]);
    $startup_location = trim($extended[4]);
    $startup_name = trim($extended[5]);
    $startup_user = trim($extended[6]);
    if (is_null($startup_timestamp)) {
      $sql = "SELECT MAX(startup_timestamp) FROM startup WHERE startup_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(startup_timestamp)"]) {$startup_timestamp = $myrow["MAX(startup_timestamp)"];} else {$startup_timestamp = "";}
      } else {}
    $sql  = "SELECT count(startup_uuid) AS count FROM startup WHERE startup_uuid = '$uuid' AND ";
    $sql .= "startup_caption = '$startup_caption' AND startup_command = '$startup_command' AND ";
    $sql .= "startup_description = '$startup_description' AND startup_location = '$startup_location' AND ";
    $sql .= "startup_name = '$startup_name' AND startup_user = '$startup_user' AND (startup_timestamp = '$startup_timestamp' OR startup_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO startup (startup_uuid, startup_caption, startup_command, startup_description, ";
      $sql .= "startup_location, startup_name, startup_user, ";
      $sql .= "startup_timestamp, startup_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$startup_caption', '$startup_command', '$startup_description', ";
      $sql .= "'$startup_location', '$startup_name', '$startup_user', ";
      $sql .= "'$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE startup SET startup_timestamp = '$timestamp' WHERE startup_name = '$startup_name' AND startup_uuid = '$uuid' AND startup_timestamp = '$startup_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_service ($split) {
    global $timestamp, $uuid, $verbose, $service_timestamp, $count;
    if ($verbose == "y"){echo "<h2>Service</h2><br />";}
    $extended = explode('^^^',$split);
    $count = $count + 1;
    $service_description = trim($extended[1]);
    $service_display_name = trim($extended[2]);
    $service_name = trim($extended[3]);
    $service_path_name = trim($extended[4]);
    $service_started = trim($extended[5]);
    $service_start_mode = trim($extended[6]);
    $service_state = trim($extended[7]);
	$service_start_name = trim($extended[8]);
    $sql  = "UPDATE service SET ";
    $sql .= "service_timestamp = '$timestamp', service_count = '$count', service_path_name = '$service_path_name', ";
    $sql .= "service_started = '$service_started', service_start_mode = '$service_start_mode', ";
    $sql .= "service_state = '$service_state',  service_start_name = '$service_start_name' ";
    $sql .= "WHERE service_uuid = '$uuid' AND service_name = '$service_name' AND service_display_name = '$service_display_name' ";
    $sql .= "AND (service_timestamp = '$service_timestamp' OR service_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Update Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $affected = mysqli_affected_rows($db);
    if ($verbose == "y"){echo "Affected Rows: $affected<br />\n\n";}
    if (mysqli_affected_rows($db) == 0) {
      // Insert into database
      $sql  = "INSERT INTO service (service_uuid, service_display_name, service_name, ";
      $sql .= "service_path_name, service_started, service_start_mode, service_state, ";
      $sql .= "service_count, service_start_name, service_timestamp, service_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$service_display_name', '$service_name', ";
      $sql .= "'$service_path_name', '$service_started', '$service_start_mode', '$service_state', ";
      $sql .= "'$count', '$service_start_name', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      if ($verbose == "y"){echo "Affected Rows: " . mysqli_affected_rows($db) . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {}
    $sql = "INSERT IGNORE INTO service_details (sd_display_name, sd_description) VALUES ('$service_display_name', '$service_description')";
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
  }


function insert_bho ($split) {
    global $timestamp, $uuid, $verbose, $bho_timestamp;
    if ($verbose == "y"){echo "<h2>Browser Helper Object</h2><br />";}
    $extended = explode('^^^',$split);
    $bho_code_base = trim($extended[1]);
    $bho_status = trim($extended[2]);
    $bho_program_file = trim($extended[3]);
    if (is_null($bho_timestamp)) {
      $sql = "SELECT MAX(bho_timestamp) FROM browser_helper_objects WHERE bho_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(bho_timestamp)"]) {$bho_timestamp = $myrow["MAX(bho_timestamp)"];} else {$bho_timestamp = "";}
      } else {}
    $sql  = "SELECT count(bho_uuid) AS count FROM browser_helper_objects WHERE bho_uuid = '$uuid' AND ";
    $sql .= "bho_code_base = '$bho_code_base' AND bho_status = '$bho_status' AND ";
    $sql .= "bho_program_file = '$bho_program_file' AND (bho_timestamp = '$bho_timestamp' OR bho_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO browser_helper_objects (bho_uuid, bho_code_base, bho_status, bho_program_file, ";
      $sql .= "bho_timestamp, bho_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$bho_code_base', '$bho_status', '$bho_program_file', ";
      $sql .= "'$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE browser_helper_objects SET bho_timestamp = '$timestamp' WHERE bho_code_base = '$bho_code_base' AND bho_uuid = '$uuid' AND bho_timestamp = '$bho_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
}


function insert_system10 ($split) {
    global $timestamp, $uuid, $verbose;
    $extended = explode('^^^',$split);
    $virus_manufacturer = trim($extended[1]);
    $virus_name = trim($extended[2]);
    $virus_uptodate = trim($extended[3]);
    $virus_version = trim($extended[4]);
    $virus_date = trim($extended[5]);
    $sql  = "UPDATE system SET virus_manufacturer = '$virus_manufacturer', ";
    $sql .= "virus_name = '$virus_name', virus_uptodate = '$virus_uptodate', virus_version = '$virus_date' ";
    $sql .= "WHERE system_uuid = '$uuid' AND system_timestamp = '$timestamp'";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
}


function insert_odbc ($split){
    global $timestamp, $uuid, $verbose, $odbc_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>ODBC DSNs</h2><br />";}
    $odbc_dsn = trim($extended[1]);
    $odbc_config = trim($extended[2]);
    if (is_null($odbc_timestamp)){
      $sql  = "SELECT MAX(odbc_timestamp) FROM odbc WHERE odbc_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(odbc_timestamp)"]) {$bios_timestamp = $myrow["MAX(odbc_timestamp)"];} else {$odbc_timestamp = "";}
    } else {}
    $sql  = "SELECT count(odbc_uuid) as count FROM odbc WHERE ";
    $sql .= "odbc_dsn = '$odbc_dsn' AND ";
    $sql .= "(odbc_timestamp = '$timestamp' OR odbc_timestamp = '$odbc_timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection();
	$result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO odbc (odbc_uuid, odbc_dsn, odbc_config, ";
      $sql .= "odbc_first_timestamp, odbc_timestamp) VALUES (";
      $sql .= "'$uuid', '$odbc_dsn', '$odbc_config', ";
      $sql .= "'$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE odbc SET odbc_timestamp = '$timestamp' WHERE odbc = '$uuid' AND bios_timestamp = '$odbc_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
	$db=GetOpenAuditDbConnection();
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }




function insert_software ($split) {
    global $timestamp, $uuid, $verbose, $software_timestamp, $count;
    $count = $count + 1;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Software.</h2><br />";}
    $software_name = trim($extended[1]);
    $software_version = trim($extended[2]);
    $software_location = trim($extended[3]);
    $software_uninstall = trim($extended[4]);
    $software_install_date = trim($extended[5]);
    $software_publisher = trim($extended[6]);
    $software_install_source = trim($extended[7]);
    $software_system_component = trim($extended[8]);
    $software_url = trim($extended[9]);
    $software_comments = trim($extended[10]);
    $sql  = "UPDATE software SET software_timestamp = '$timestamp', software_count = '$count', software_version = '$software_version', ";
    $sql .= "software_location = '$software_location', software_uninstall = '$software_uninstall', software_install_date = '$software_install_date', ";
	$sql .= "software_publisher = '$software_publisher', software_install_source = '$software_install_source', software_system_component = '$software_system_component', ";
	$sql .= "software_url = '$software_url', software_comment = '$software_comments' ";
    $sql .= "WHERE software_uuid = '$uuid' AND ";
    $sql .= "software_name = '$software_name' AND ";
    $sql .= "(software_timestamp = '$software_timestamp' OR software_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $affected = mysqli_affected_rows($db);
    if ($verbose == "y"){echo "Affected Rows: " . $affected . "<br />\n\n";}
    if ($affected == "0") {
      //Insert a new record
      $sql  = "INSERT INTO software (software_uuid, software_name, software_version, ";
      $sql .= "software_location, software_uninstall, software_install_date, software_publisher, ";
      $sql .= "software_install_source, software_system_component, software_url, software_comment, software_count, software_timestamp, software_first_timestamp ) VALUES (";
      $sql .= "'$uuid','$software_name','$software_version', ";
      $sql .= "'$software_location','$software_uninstall','$software_install_date','$software_publisher',";
      $sql .= "'$software_install_source','$software_system_component','$software_url','$software_comments','$count','$timestamp','$timestamp')";
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      if ($verbose == "y"){echo "Affected Rows: " . mysqli_affected_rows($db) . "<br />\n\n";}
    } else {}
  }

function insert_software_apps ($split) {
    global $timestamp, $uuid, $verbose, $software_timestamp, $count;
    $count = $count + 1;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Softwapps.</h2><br />";}
    $software_name = trim($extended[1]);
    $software_version = trim($extended[2]);
    $software_location = trim($extended[3]);
    $software_uninstall = trim($extended[4]);
    $software_install_date = trim($extended[5]);
    $software_publisher = trim($extended[6]);
    $software_install_source = trim($extended[7]);
    $software_system_component = trim($extended[8]);
    $software_url = trim($extended[9]);
    $software_comments = trim($extended[10]);
    $sql  = "UPDATE softwapps SET software_timestamp = '$timestamp', software_count = '$count', software_version = '$software_version', ";
    $sql .= "software_location = '$software_location', software_uninstall = '$software_uninstall', software_install_date = '$software_install_date', ";
	$sql .= "software_publisher = '$software_publisher', software_install_source = '$software_install_source', software_system_component = '$software_system_component', ";
	$sql .= "software_url = '$software_url', software_comment = '$software_comments' ";
    $sql .= "WHERE software_uuid = '$uuid' AND ";
    $sql .= "software_name = '$software_name' AND ";
    $sql .= "(software_timestamp = '$software_timestamp' OR software_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $affected = mysqli_affected_rows($db);
    if ($verbose == "y"){echo "Affected Rows: " . $affected . "<br />\n\n";}
    if ($affected == "0") {
      //Insert a new record
      $sql  = "INSERT INTO softwapps (software_uuid, software_name, software_version, ";
      $sql .= "software_location, software_uninstall, software_install_date, software_publisher, ";
      $sql .= "software_install_source, software_system_component, software_url, software_comment, software_count, software_timestamp, software_first_timestamp ) VALUES (";
      $sql .= "'$uuid','$software_name','$software_version', ";
      $sql .= "'$software_location','$software_uninstall','$software_install_date','$software_publisher',";
      $sql .= "'$software_install_source','$software_system_component','$software_url','$software_comments','$count','$timestamp','$timestamp')";
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      if ($verbose == "y"){echo "Affected Rows: " . mysqli_affected_rows($db) . "<br />\n\n";}
    } else {}
  }


  
function insert_system11 ($split) {
    global $timestamp, $uuid, $verbose;
    if ($verbose == "y"){echo "<h2>Firewall Settings</h2><br />";}
    $extended = explode('^^^',$split);
    $firewall_enabled_domain = trim($extended[1]);
    $firewall_disablenotifications_domain = trim($extended[2]);
    $firewall_donotallowexceptions_domain = trim($extended[3]);
    $firewall_enabled_standard = trim($extended[4]);
    $firewall_disablenotifications_standard = trim($extended[5]);
    $firewall_donotallowexceptions_standard = trim($extended[6]);
    $sql  = "UPDATE system SET firewall_enabled_domain = '$firewall_enabled_domain', ";
    $sql .= "firewall_disablenotifications_domain = '$firewall_disablenotifications_domain', ";
    $sql .= "firewall_donotallowexceptions_domain = '$firewall_donotallowexceptions_domain', ";
    $sql .= "firewall_enabled_standard = '$firewall_enabled_standard', ";
    $sql .= "firewall_disablenotifications_standard = '$firewall_disablenotifications_standard', ";
    $sql .= "firewall_donotallowexceptions_standard = '$firewall_donotallowexceptions_standard' ";
    $sql .= "WHERE system_uuid = '$uuid' AND system_timestamp = '$timestamp'";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
  }

function insert_fire_app ($split) {
    global $timestamp, $uuid, $verbose, $firewall_app_timestamp;
    if ($verbose == "y"){echo "<h2>Firewall Application</h2><br />";}
    $extended = explode('^^^',$split);
    $firewall_app_name = trim($extended[1]);
    $firewall_app_executable = trim($extended[2]);
    $firewall_app_remote_address = trim($extended[3]);
    $firewall_app_enabled = trim($extended[4]);
    $firewall_app_profile = trim($extended[5]);
    if (is_null($firewall_app_timestamp)) {
      $sql = "SELECT MAX(firewall_app_timestamp) FROM firewall_auth_app WHERE firewall_app_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(firewall_app_timestamp)"]) {$firewall_app_timestamp = $myrow["MAX(firewall_app_timestamp)"];} else {$firewall_app_timestamp = "";}
      } else {}
    $sql  = "SELECT count(firewall_app_uuid) AS count FROM firewall_auth_app WHERE firewall_app_uuid = '$uuid' AND ";
    $sql .= "firewall_app_name = '$firewall_app_name' AND firewall_app_executable = '$firewall_app_executable' AND ";
    $sql .= "firewall_app_remote_address = '$firewall_app_remote_address' AND firewall_app_enabled = '$firewall_app_enabled' AND ";
    $sql .= "(firewall_app_timestamp = '$firewall_app_timestamp' OR firewall_app_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO firewall_auth_app (firewall_app_uuid, firewall_app_name, firewall_app_executable, ";
      $sql .= "firewall_app_remote_address, firewall_app_enabled, firewall_app_profile, ";
      $sql .= "firewall_app_timestamp, firewall_app_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$firewall_app_name', '$firewall_app_executable', ";
      $sql .= "'$firewall_app_remote_address', '$firewall_app_enabled', '$firewall_app_profile', ";
      $sql .= "'$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql  = "UPDATE firewall_auth_app SET firewall_app_timestamp = '$timestamp' WHERE firewall_app_name = '$firewall_app_name' AND ";
      $sql .= "firewall_app_uuid = '$uuid' AND firewall_app_timestamp = '$firewall_app_timestamp' AND firewall_app_profile = '$firewall_app_profile'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_fire_port ($split) {
    global $timestamp, $uuid, $verbose, $port_timestamp;
    if ($verbose == "y"){echo "<h2>Firewall Port</h2><br />";}
    $extended = explode('^^^',$split);
    $port_number = trim($extended[1]);
    $port_protocol = trim($extended[2]);
    $port_scope = trim($extended[3]);
    $port_enabled = trim($extended[4]);
    $port_profile = trim($extended[5]);
    if (is_null($port_timestamp)) {
      $sql = "SELECT MAX(port_timestamp) FROM firewall_ports WHERE port_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(port_timestamp)"]) {$port_timestamp = $myrow["MAX(port_timestamp)"];} else {$port_timestamp = "";}
      } else {}
    $sql  = "SELECT count(port_uuid) AS count FROM firewall_ports WHERE port_uuid = '$uuid' AND ";
    $sql .= "port_number = '$port_number' AND port_protocol = '$port_protocol' AND ";
    $sql .= "port_scope = '$port_scope' AND port_enabled = '$port_enabled' AND ";
    $sql .= "(port_timestamp = '$port_timestamp' OR port_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO firewall_ports (port_uuid, port_number, port_protocol, ";
      $sql .= "port_scope, port_enabled, port_profile, ";
      $sql .= "port_timestamp, port_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$port_number', '$port_protocol', ";
      $sql .= "'$port_scope', '$port_enabled', '$port_profile', ";
      $sql .= "'$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE firewall_ports SET port_timestamp = '$timestamp' WHERE port_number = '$port_number' AND ";
      $sql .= "port_uuid = '$uuid' AND port_timestamp = '$port_timestamp' AND port_scope = '$port_scope' AND port_enabled = '$port_enabled'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }

function insert_ms_keys ($split) {
    global $timestamp, $uuid, $verbose, $ms_keys_timestamp;
    if ($verbose == "y"){echo "<h2>CD Key</h2><br />";}
    $extended = explode('^^^',$split);
    $ms_keys_name = trim($extended[1]);
    $ms_keys_cd_key = trim($extended[2]);
    $ms_keys_release = trim($extended[3]);
    $ms_keys_edition = trim($extended[4]);
    $ms_keys_key_type = trim($extended[5]);
    if (is_null($ms_keys_timestamp)) {
      $sql = "SELECT MAX(ms_keys_timestamp) FROM ms_keys WHERE ms_keys_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(ms_keys_timestamp)"]) {$ms_keys_timestamp = $myrow["MAX(ms_keys_timestamp)"];} else {$ms_keys_timestamp = "";}
      } else {}
    $sql  = "SELECT count(ms_keys_uuid) AS count FROM ms_keys WHERE ms_keys_uuid = '$uuid' AND ";
    $sql .= "ms_keys_name = '$ms_keys_name' AND ms_keys_cd_key = '$ms_keys_cd_key' AND ";
    $sql .= "ms_keys_release = '$ms_keys_release' AND ms_keys_edition = '$ms_keys_edition' AND ms_keys_key_type = '$ms_keys_key_type' AND ";
    $sql .= "(ms_keys_timestamp = '$ms_keys_timestamp' OR ms_keys_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO ms_keys (ms_keys_uuid, ms_keys_name, ms_keys_cd_key, ";
      $sql .= "ms_keys_release, ms_keys_edition, ms_keys_key_type, ";
      $sql .= "ms_keys_timestamp, ms_keys_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$ms_keys_name', '$ms_keys_cd_key', ";
      $sql .= "'$ms_keys_release', '$ms_keys_edition', '$ms_keys_key_type', ";
      $sql .= "'$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE ms_keys SET ms_keys_timestamp = '$timestamp' WHERE ms_keys_name = '$ms_keys_name' AND ";
      $sql .= "ms_keys_uuid = '$uuid' AND ms_keys_timestamp = '$ms_keys_timestamp' AND ms_keys_cd_key = '$ms_keys_cd_key' AND ms_keys_key_type = '$ms_keys_key_type'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }

function insert_system12 ($split) {
    global $timestamp, $uuid, $verbose;
    if ($verbose == "y"){echo "<h2>IIS version</h2><br />";}
    $extended = explode('^^^',$split);
    $iis_version = trim($extended[1]);
    $sql  = "UPDATE system SET iis_version = '$iis_version' ";
    $sql .= "WHERE system_uuid = '$uuid' AND system_timestamp = '$timestamp'";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
  }

function insert_iis_1 ($split) {
    global $timestamp, $uuid, $verbose, $iis_timestamp;
    if ($verbose == "y"){echo "<h2>IIS</h2><br />";}
    $extended = explode('^^^',$split);
    $iis_site = trim($extended[1]);
    $iis_description = trim($extended[2]);
    $iis_logging_enabled = trim($extended[3]);
    $iis_logging_dir = trim($extended[4]);
    $iis_logging_format = trim($extended[5]);
    $iis_logging_time_period = trim($extended[6]);
    $iis_home_directory = trim($extended[7]);
    $iis_directory_browsing = trim($extended[8]);
    $iis_default_documents = trim($extended[9]);
    $iis_secure_ip = trim($extended[10]);
    $iis_secure_port = trim($extended[11]);
    $iis_site_state = trim($extended[12]);
    $iis_site_app_pool = trim($extended[13]);
    $iis_site_anonymous_user = trim($extended[14]);
    $iis_site_anonymous_auth = trim($extended[15]);
    $iis_site_basic_auth = trim($extended[16]);
    $iis_site_ntlm_auth = trim($extended[17]);
    $iis_site_ssl_en = trim($extended[18]);
    $iis_site_ssl128_en = trim($extended[19]);
    if (is_null($iis_timestamp)) {
      $sql = "SELECT MAX(iis_timestamp) FROM iis WHERE iis_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(iis_timestamp)"]) {$iis_timestamp = $myrow["MAX(iis_timestamp)"];} else {$iis_timestamp = "";}
      } else {}
    $sql  = "SELECT count(iis_uuid) AS count FROM iis ";
    $sql .= "WHERE iis_uuid = '$uuid' AND iis_site = '$iis_site' AND ";
    $sql .= "(iis_timestamp = '$iis_timestamp' OR iis_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New site: insert into database
      $sql  = "INSERT INTO iis (iis_uuid, iis_site, iis_description, ";
      $sql .= "iis_logging_enabled, iis_logging_dir, iis_logging_format, ";
      $sql .= "iis_logging_time_period , iis_home_directory, iis_directory_browsing, ";
      $sql .= "iis_default_documents, iis_secure_ip, iis_secure_port, ";
      $sql .= "iis_site_state, iis_site_app_pool, iis_site_anonymous_user, ";
      $sql .= "iis_site_anonymous_auth , iis_site_basic_auth, iis_site_ntlm_auth, ";
      $sql .= "iis_site_ssl_en, iis_site_ssl128_en, ";
      $sql .= "iis_timestamp, iis_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$iis_site', '$iis_description', ";
      $sql .= "'$iis_logging_enabled', '$iis_logging_dir', '$iis_logging_format', ";
      $sql .= "'$iis_logging_time_period' , '$iis_home_directory', '$iis_directory_browsing', ";
      $sql .= "'$iis_default_documents', '$iis_secure_ip', '$iis_secure_port', ";
      $sql .= "'$iis_site_state', '$iis_site_app_pool', '$iis_site_anonymous_user', ";
      $sql .= "'$iis_site_anonymous_auth', '$iis_site_basic_auth', '$iis_site_ntlm_auth', ";
      $sql .= "'$iis_site_ssl_en' , '$iis_site_ssl128_en', ";
      $sql .= "'$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Site already present in database - update timestamp and dynamic fields
      $sql  = "UPDATE iis SET iis_timestamp = '$timestamp', iis_description = '$iis_description', ";
      $sql .= "iis_logging_enabled = '$iis_logging_enabled', iis_logging_dir = '$iis_logging_dir', ";
      $sql .= "iis_logging_format = '$iis_logging_format', iis_logging_time_period = '$iis_logging_time_period', ";
      $sql .= "iis_home_directory = '$iis_home_directory', iis_directory_browsing = '$iis_directory_browsing', ";
      $sql .= "iis_default_documents = '$iis_default_documents', iis_secure_ip = '$iis_secure_ip', ";
      $sql .= "iis_secure_port = '$iis_secure_port', iis_site_state = '$iis_site_state', ";
      $sql .= "iis_site_app_pool = '$iis_site_app_pool', iis_site_anonymous_user = '$iis_site_anonymous_user', ";
      $sql .= "iis_site_anonymous_auth = '$iis_site_anonymous_auth', iis_site_basic_auth = '$iis_site_basic_auth', ";
      $sql .= "iis_site_ntlm_auth = '$iis_site_ntlm_auth', iis_site_ssl_en = '$iis_site_ssl_en', ";
      $sql .= "iis_site_ssl128_en = '$iis_site_ssl128_en' ";
      $sql .= "WHERE iis_site = '$iis_site' AND iis_uuid = '$uuid' AND iis_timestamp = '$iis_timestamp' ";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_iis_2 ($split) {
    global $timestamp, $uuid, $verbose, $iis_vd_timestamp;
    if ($verbose == "y"){echo "<h2>IIS - Virtual Directory</h2><br />";}
    $extended = explode('^^^',$split);
    $iis_vd_site = trim($extended[1]);
    $iis_vd_name = trim($extended[2]);
    $iis_vd_path = trim($extended[3]);
    if (is_null($iis_vd_timestamp)) {
      $sql = "SELECT MAX(iis_vd_timestamp) FROM iis_vd WHERE iis_vd_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(iis_vd_timestamp)"]) {$iis_vd_timestamp = $myrow["MAX(iis_vd_timestamp)"];} else {$iis_vd_timestamp = "";}
      } else {}
    $sql  = "SELECT count(iis_vd_uuid) AS count FROM iis_vd WHERE iis_vd_uuid = '$uuid' AND ";
    $sql .= "iis_vd_site = '$iis_vd_site' AND iis_vd_name = '$iis_vd_name' AND ";
    $sql .= "iis_vd_path = '$iis_vd_path' AND ";
    $sql .= "(iis_vd_timestamp = '$iis_vd_timestamp' OR iis_vd_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO iis_vd (iis_vd_uuid, iis_vd_site, iis_vd_name, ";
      $sql .= "iis_vd_path, iis_vd_timestamp, iis_vd_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$iis_vd_site', '$iis_vd_name', ";
      $sql .= "'$iis_vd_path', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE iis_vd SET iis_vd_timestamp = '$timestamp' WHERE iis_vd_site = '$iis_vd_site' AND ";
      $sql .= "iis_vd_uuid = '$uuid' AND iis_vd_timestamp = '$iis_vd_timestamp' AND ";
      $sql .= "iis_vd_name = '$iis_vd_name' AND iis_vd_path = '$iis_vd_path'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }


function insert_iis_3 ($split) {
    global $timestamp, $uuid, $verbose, $iis_ip_timestamp;
    if ($verbose == "y"){echo "<h2>IIS - Site IP</h2><br />";}
    $extended = explode('^^^',$split);
    $iis_ip_site = trim($extended[1]);
    $iis_ip_ip_address = trim($extended[2]);
    $iis_ip_port = trim($extended[3]);
    $iis_ip_host_header = trim($extended[4]);
    if (is_null($iis_ip_timestamp)) {
      $sql = "SELECT MAX(iis_ip_timestamp) FROM iis_ip WHERE iis_ip_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(iis_ip_timestamp)"]) {$iis_ip_timestamp = $myrow["MAX(iis_ip_timestamp)"];} else {$iis_ip_timestamp = "";}
      } else {}
    $sql  = "SELECT count(iis_ip_uuid) AS count FROM iis_ip WHERE iis_ip_uuid = '$uuid' AND ";
    $sql .= "iis_ip_site = '$iis_ip_site' AND (iis_ip_ip_address = '$iis_ip_ip_address' OR iis_ip_ip_address = '<All Unassigned>') AND ";
    $sql .= "iis_ip_port = '$iis_ip_port' AND iis_ip_host_header = '$iis_ip_host_header' AND";
    $sql .= "(iis_ip_timestamp = '$iis_ip_timestamp' OR iis_ip_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // Insert into database
      $sql  = "INSERT INTO iis_ip (iis_ip_uuid, iis_ip_site, iis_ip_ip_address, ";
      $sql .= "iis_ip_port, iis_ip_host_header, iis_ip_timestamp, iis_ip_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$iis_ip_site', '$iis_ip_ip_address', ";
      $sql .= "'$iis_ip_port', '$iis_ip_host_header', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp
      $sql = "UPDATE iis_ip SET iis_ip_timestamp = '$timestamp' WHERE iis_ip_site = '$iis_ip_site' AND ";
      $sql .= "iis_ip_uuid = '$uuid' AND iis_ip_timestamp = '$iis_ip_timestamp' AND ";
      $sql .= "iis_ip_ip_address = '$iis_ip_ip_address' AND iis_ip_port = '$iis_ip_port' AND iis_ip_host_header = '$iis_ip_host_header'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }

function insert_iis_4 ($split) {
    global $timestamp, $uuid, $verbose, $iis_web_ext_timestamp;
    if ($verbose == "y"){echo "<h2>IIS - Web Service Extension</h2><br />";}
    $extended = explode('^^^',$split);
    $iis_web_ext_desc = trim($extended[1]);
    $iis_web_ext_path = trim($extended[2]);
    $iis_web_ext_access = trim($extended[3]);
    if (is_null($iis_web_ext_timestamp)) {
      $sql = "SELECT MAX(iis_web_ext_timestamp) FROM iis_web_ext WHERE iis_web_ext_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(iis_web_ext_timestamp)"]) {$iis_web_ext_timestamp = $myrow["MAX(iis_web_ext_timestamp)"];} else {$iis_web_ext_timestamp = "";}
      } else {}
    $sql  = "SELECT count(iis_web_ext_uuid) AS count FROM iis_web_ext ";
    $sql .= "WHERE iis_web_ext_uuid = '$uuid' AND iis_web_ext_desc = '$iis_web_ext_desc' AND iis_web_ext_path = '$iis_web_ext_path' AND ";
    $sql .= "(iis_web_ext_timestamp = '$iis_web_ext_timestamp' OR iis_web_ext_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New web service extension: insert into database
      $sql  = "INSERT INTO iis_web_ext (iis_web_ext_uuid, iis_web_ext_path, iis_web_ext_desc, ";
      $sql .= "iis_web_ext_access, iis_web_ext_timestamp, iis_web_ext_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$iis_web_ext_path', '$iis_web_ext_desc', ";
      $sql .= "'$iis_web_ext_access', '$timestamp', '$timestamp')";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - update timestamp and access
      $sql  = "UPDATE iis_web_ext SET iis_web_ext_timestamp = '$timestamp', iis_web_ext_access = '$iis_web_ext_access' ";
      $sql .= "WHERE iis_web_ext_uuid = '$uuid' AND iis_web_ext_timestamp = '$iis_web_ext_timestamp' AND ";
      $sql .= "iis_web_ext_path = '$iis_web_ext_path' AND iis_web_ext_desc = '$iis_web_ext_desc' ";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
  }
  
function insert_sched_task ($split) {
    global $timestamp, $uuid, $verbose, $sched_task_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Scheduled task</h2><br />";}
    $sched_task_name = trim($extended[1]);
    $sched_task_next_run = trim($extended[2]);
    $sched_task_status = trim($extended[3]);  // path
    $sched_task_last_run = trim($extended[4]);
    $sched_task_last_result = trim($extended[5]);
    $sched_task_creator = trim($extended[6]);
    $sched_task_schedule = trim($extended[7]);
    $sched_task_task = trim($extended[8]);
    $sched_task_state = trim($extended[9]);
    $sched_task_runas = trim($extended[10]);

    if (is_null($sched_task_timestamp)) {
      $sql  = "SELECT MAX(sched_task_timestamp) FROM scheduled_task WHERE sched_task_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(sched_task_timestamp)"]) {$sched_task_timestamp = $myrow["MAX(sched_task_timestamp)"];} else {$sched_task_timestamp = "";}
    } else {}
    $sql  = "SELECT count(sched_task_uuid) as count from scheduled_task ";
    $sql .= "WHERE sched_task_uuid = '$uuid' AND sched_task_name = '$sched_task_name' AND sched_task_creator = '$sched_task_creator' AND sched_task_schedule = '$sched_task_schedule'";
    $sql .= "AND (sched_task_timestamp = '$sched_task_timestamp' OR sched_task_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New task - Insert into database
      $sql  = "INSERT INTO scheduled_task (";
      $sql .= "sched_task_uuid, sched_task_name, sched_task_next_run, sched_task_status, sched_task_last_run, sched_task_last_result, ";
      $sql .= "sched_task_creator, sched_task_schedule, sched_task_task, sched_task_state, sched_task_runas, ";
      $sql .= "sched_task_timestamp, sched_task_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$sched_task_name', '$sched_task_next_run', '$sched_task_status', '$sched_task_last_run', '$sched_task_last_result', ";
      $sql .= "'$sched_task_creator', '$sched_task_schedule', '$sched_task_task', '$sched_task_state', '$sched_task_runas', ";
      $sql .= "'$timestamp', '$timestamp') ";

      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - Update timestamp and dynamic fields
      $sql  = "UPDATE scheduled_task SET ";
      $sql .= "sched_task_timestamp = '$timestamp', sched_task_next_run = '$sched_task_next_run', sched_task_status = '$sched_task_status', ";
      $sql .= "sched_task_last_run = '$sched_task_last_run', sched_task_last_result = '$sched_task_last_result', ";
      $sql .= "sched_task_task = '$sched_task_task', sched_task_state = '$sched_task_state', sched_task_runas = '$sched_task_runas' ";
      $sql .= "WHERE sched_task_uuid = '$uuid' AND sched_task_name = '$sched_task_name' AND sched_task_creator = '$sched_task_creator' AND sched_task_schedule = '$sched_task_schedule' ";
      $sql .= "AND sched_task_timestamp = '$sched_task_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
   
}

function insert_env_var ($split) {
    global $timestamp, $uuid, $verbose, $env_var_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Environment variable</h2><br />";}
    $env_var_name = trim($extended[1]);
    $env_var_value = trim($extended[2]);

    if (is_null($env_var_timestamp)) {
      $sql  = "SELECT MAX(env_var_timestamp) FROM environment_variable WHERE env_var_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(env_var_timestamp)"]) {$env_var_timestamp = $myrow["MAX(env_var_timestamp)"];} else {$env_var_timestamp = "";}
    } else {}
    $sql  = "SELECT count(env_var_uuid) as count from environment_variable ";
    $sql .= "WHERE env_var_uuid = '$uuid' AND env_var_name = '$env_var_name' ";
    $sql .= "AND (env_var_timestamp = '$env_var_timestamp' OR env_var_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New environment variable - Insert into database
      $sql  = "INSERT INTO environment_variable (";
      $sql .= "env_var_uuid, env_var_name, env_var_value, ";
      $sql .= "env_var_timestamp, env_var_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$env_var_name', '$env_var_value', ";
      $sql .= "'$timestamp', '$timestamp') ";

      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - Update timestamp and value
      $sql  = "UPDATE environment_variable SET ";
      $sql .= "env_var_timestamp = '$timestamp', env_var_value = '$env_var_value' ";
      $sql .= "WHERE env_var_uuid = '$uuid' AND env_var_name = '$env_var_name' ";
      $sql .= "AND env_var_timestamp = '$env_var_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
   
}

function insert_evt_log ($split) {
    global $timestamp, $uuid, $verbose, $evt_log_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Event log</h2><br />";}
    $evt_log_name = trim($extended[1]);
    $evt_log_file_name = trim($extended[2]);
    $evt_log_file_size = trim($extended[3]);
    $evt_log_max_file_size = trim($extended[4]);
    $evt_log_overwrite = trim($extended[5]);

    if (is_null($evt_log_timestamp)) {
      $sql  = "SELECT MAX(evt_log_timestamp) FROM event_log WHERE evt_log_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(evt_log_timestamp)"]) {$evt_log_timestamp = $myrow["MAX(evt_log_timestamp)"];} else {$evt_log_timestamp = "";}
    } else {}
    $sql  = "SELECT count(evt_log_uuid) as count from event_log ";
    $sql .= "WHERE evt_log_uuid = '$uuid' AND evt_log_name = '$evt_log_name' AND evt_log_file_name = '$evt_log_file_name' ";
    $sql .= "AND (evt_log_timestamp = '$evt_log_timestamp' OR evt_log_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New event log - Insert into database
      $sql  = "INSERT INTO event_log (";
      $sql .= "evt_log_uuid, evt_log_name, evt_log_file_name, evt_log_file_size, evt_log_max_file_size, evt_log_overwrite, ";
      $sql .= "evt_log_timestamp, evt_log_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$evt_log_name', '$evt_log_file_name', '$evt_log_file_size', '$evt_log_max_file_size', '$evt_log_overwrite', ";
      $sql .= "'$timestamp', '$timestamp') ";

      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - Update timestamp and dynamic fields
      $sql  = "UPDATE event_log SET ";
      $sql .= "evt_log_timestamp = '$timestamp', evt_log_file_size = '$evt_log_file_size', ";
      $sql .= "evt_log_max_file_size = '$evt_log_max_file_size', evt_log_overwrite = '$evt_log_overwrite' ";
      $sql .= "WHERE evt_log_uuid = '$uuid' AND evt_log_name = '$evt_log_name' AND evt_log_file_name = '$evt_log_file_name' ";
      $sql .= "AND evt_log_timestamp = '$evt_log_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
   
}

function insert_ip_route ($split) {
    global $timestamp, $uuid, $verbose, $ip_route_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>IP route</h2><br />";}
    $ip_route_destination = trim($extended[1]);
    $ip_route_mask = trim($extended[2]);
    $ip_route_metric = trim($extended[3]);
    $ip_route_next_hop = trim($extended[4]);
    $ip_route_protocol = trim($extended[5]);
    $ip_route_type = trim($extended[6]);

    if (is_null($ip_route_timestamp)) {
      $sql  = "SELECT MAX(ip_route_timestamp) FROM ip_route WHERE ip_route_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(ip_route_timestamp)"]) {$ip_route_timestamp = $myrow["MAX(ip_route_timestamp)"];} else {$ip_route_timestamp = "";}
    } else {}
    $sql  = "SELECT count(ip_route_uuid) as count from ip_route ";
    $sql .= "WHERE ip_route_uuid = '$uuid' AND ip_route_destination = '$ip_route_destination' AND ip_route_mask = '$ip_route_mask' ";
    $sql .= "AND (ip_route_timestamp = '$ip_route_timestamp' OR ip_route_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New route - Insert into database
      $sql  = "INSERT INTO ip_route (";
      $sql .= "ip_route_uuid, ip_route_destination, ip_route_mask, ip_route_metric, ip_route_next_hop, ip_route_protocol, ip_route_type, ";
      $sql .= "ip_route_timestamp, ip_route_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$ip_route_destination', '$ip_route_mask', '$ip_route_metric', '$ip_route_next_hop', '$ip_route_protocol', '$ip_route_type', ";
      $sql .= "'$timestamp', '$timestamp') ";

      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - Update timestamp and dynamic fields
      $sql  = "UPDATE ip_route SET ";
      $sql .= "ip_route_timestamp = '$timestamp', ip_route_metric = '$ip_route_metric', ip_route_next_hop = '$ip_route_next_hop', ";
      $sql .= "ip_route_protocol = '$ip_route_protocol', ip_route_type = '$ip_route_type' ";
      $sql .= "WHERE ip_route_uuid = '$uuid' AND ip_route_destination = '$ip_route_destination' AND ip_route_mask = '$ip_route_mask' ";
      $sql .= "AND ip_route_timestamp = '$ip_route_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
   
}

function insert_pagefile ($split) {
    global $timestamp, $uuid, $verbose, $pagefile_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Pagefile</h2><br />";}
    $pagefile_name = trim($extended[1]);
    $pagefile_initial_size = trim($extended[2]);
    $pagefile_max_size = trim($extended[3]);

    if (is_null($pagefile_timestamp)) {
      $sql  = "SELECT MAX(pagefile_timestamp) FROM pagefile WHERE pagefile_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(pagefile_timestamp)"]) {$pagefile_timestamp = $myrow["MAX(pagefile_timestamp)"];} else {$pagefile_timestamp = "";}
    } else {}
    $sql  = "SELECT count(pagefile_uuid) as count from pagefile ";
    $sql .= "WHERE pagefile_uuid = '$uuid' AND pagefile_name = '$pagefile_name' ";
    $sql .= "AND (pagefile_timestamp = '$pagefile_timestamp' OR pagefile_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New pagefile - Insert into database
      $sql  = "INSERT INTO pagefile (";
      $sql .= "pagefile_uuid, pagefile_name, pagefile_initial_size, pagefile_max_size, ";
      $sql .= "pagefile_timestamp, pagefile_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$pagefile_name', '$pagefile_initial_size', '$pagefile_max_size', ";
      $sql .= "'$timestamp', '$timestamp') ";

      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - Update timestamp and dynamic fields
      $sql  = "UPDATE pagefile SET ";
      $sql .= "pagefile_timestamp = '$timestamp', pagefile_initial_size = '$pagefile_initial_size', pagefile_max_size = '$pagefile_max_size'  ";
      $sql .= "WHERE pagefile_uuid = '$uuid' AND pagefile_name = '$pagefile_name' ";
      $sql .= "AND pagefile_timestamp = '$pagefile_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
   
}

function insert_motherboard ($split) {
    global $timestamp, $uuid, $verbose, $motherboard_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Motherboard</h2><br />";}
    $motherboard_manufacturer = trim($extended[1]);
    $motherboard_product = trim($extended[2]);
    $motherboard_cpu_sockets = trim($extended[3]);
    $motherboard_memory_slots = trim($extended[4]);

    if (is_null($motherboard_timestamp)) {
      $sql  = "SELECT MAX(motherboard_timestamp) FROM motherboard WHERE motherboard_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(motherboard_timestamp)"]) {$motherboard_timestamp = $myrow["MAX(motherboard_timestamp)"];} else {$motherboard_timestamp = "";}
    } else {}
    $sql  = "SELECT count(motherboard_uuid) as count from motherboard ";
    $sql .= "WHERE motherboard_uuid = '$uuid' AND motherboard_manufacturer = '$motherboard_manufacturer' AND motherboard_product = '$motherboard_product' ";
    $sql .= "AND motherboard_cpu_sockets = '$motherboard_cpu_sockets' AND motherboard_memory_slots = '$motherboard_memory_slots' ";
    $sql .= "AND (motherboard_timestamp = '$motherboard_timestamp' OR motherboard_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New motherboard - Insert into database
      $sql  = "INSERT INTO motherboard (";
      $sql .= "motherboard_uuid, motherboard_manufacturer, motherboard_product, motherboard_cpu_sockets, motherboard_memory_slots, ";
      $sql .= "motherboard_timestamp, motherboard_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$motherboard_manufacturer', '$motherboard_product', '$motherboard_cpu_sockets', '$motherboard_memory_slots', ";
      $sql .= "'$timestamp', '$timestamp') ";

      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - Update timestamp 
      $sql  = "UPDATE motherboard SET ";
      $sql .= "motherboard_timestamp = '$timestamp' ";
      $sql .= "WHERE motherboard_uuid = '$uuid' AND motherboard_manufacturer = '$motherboard_manufacturer' AND motherboard_product = '$motherboard_product' ";
      $sql .= "AND motherboard_cpu_sockets = '$motherboard_cpu_sockets' AND motherboard_memory_slots = '$motherboard_memory_slots' ";
      $sql .= "AND motherboard_timestamp = '$motherboard_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
   
}

function insert_onboard ($split) {
    global $timestamp, $uuid, $verbose, $onboard_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Onboard device</h2><br />";}
    $onboard_description = trim($extended[1]);
    $onboard_type = trim($extended[2]);

    if (is_null($onboard_timestamp)) {
      $sql  = "SELECT MAX(onboard_timestamp) FROM onboard_device WHERE onboard_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(onboard_timestamp)"]) {$onboard_timestamp = $myrow["MAX(onboard_timestamp)"];} else {$onboard_timestamp = "";}
    } else {}
    $sql  = "SELECT count(onboard_uuid) as count from onboard_device ";
    $sql .= "WHERE onboard_uuid = '$uuid' AND onboard_description = '$onboard_description' AND onboard_type = '$onboard_type' ";
    $sql .= "AND (onboard_timestamp = '$onboard_timestamp' OR onboard_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New onboard device - Insert into database
      $sql  = "INSERT INTO onboard_device (";
      $sql .= "onboard_uuid, onboard_description, onboard_type, ";
      $sql .= "onboard_timestamp, onboard_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$onboard_description', '$onboard_type', ";
      $sql .= "'$timestamp', '$timestamp') ";

      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - Update timestamp 
      $sql  = "UPDATE onboard_device SET ";
      $sql .= "onboard_timestamp = '$timestamp' ";
      $sql .= "WHERE onboard_uuid = '$uuid' AND onboard_description = '$onboard_description' AND onboard_type = '$onboard_type' ";
      $sql .= "AND onboard_timestamp = '$onboard_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
   
}

function insert_auto_upd ($split) {
    global $timestamp, $uuid, $verbose, $au_timestamp;
    $extended = explode('^^^',$split);
    if ($verbose == "y"){echo "<h2>Automatic Updating</h2><br />";}
    $au_gpo_configured = trim($extended[1]);
    $au_enabled = trim($extended[2]);
    $au_behaviour = trim($extended[3]);
    $au_sched_install_day = trim($extended[4]);
    $au_sched_install_time = trim($extended[5]);
    $au_use_wuserver = trim($extended[6]);
    $au_wuserver = trim($extended[7]);
    $au_wustatusserver = trim($extended[8]);
    $au_target_group = trim($extended[9]);
    $au_elevate_nonadmins = trim($extended[10]);
    $au_auto_install = trim($extended[11]);
    $au_detection_frequency = trim($extended[12]);
    $au_reboot_timeout = trim($extended[13]);
    $au_noautoreboot = trim($extended[14]);

    if (is_null($au_timestamp)) {
      $sql  = "SELECT MAX(au_timestamp) FROM auto_updating WHERE au_uuid = '$uuid'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
      $myrow = mysqli_fetch_array($result);
      if ($myrow["MAX(au_timestamp)"]) {$au_timestamp = $myrow["MAX(au_timestamp)"];} else {$au_timestamp = "";}
    } else {}
    $sql  = "SELECT count(au_uuid) as count from auto_updating ";
    $sql .= "WHERE au_uuid = '$uuid' ";
    $sql .= "AND (au_timestamp = '$au_timestamp' OR au_timestamp = '$timestamp')";
    if ($verbose == "y"){echo $sql . "<br />\n\n";}
    $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    $myrow = mysqli_fetch_array($result);
    if ($verbose == "y"){echo "Count: " . $myrow['count'] . "<br />\n\n";}
    if ($myrow['count'] == "0"){
      // New auto updating setting - Insert into database
      $sql  = "INSERT INTO auto_updating (";
      $sql .= "au_uuid, au_gpo_configured, au_enabled, au_behaviour, au_sched_install_day, au_sched_install_time, ";
      $sql .= "au_use_wuserver, au_wuserver, au_wustatusserver, au_target_group, au_elevate_nonadmins, au_auto_install, ";
      $sql .= "au_detection_frequency, au_reboot_timeout, au_noautoreboot, ";
      $sql .= "au_timestamp, au_first_timestamp) VALUES (";
      $sql .= "'$uuid', '$au_gpo_configured', '$au_enabled', '$au_behaviour', '$au_sched_install_day', '$au_sched_install_time', ";
      $sql .= "'$au_use_wuserver', '$au_wuserver', '$au_wustatusserver', '$au_target_group', '$au_elevate_nonadmins', '$au_auto_install', ";
      $sql .= "'$au_detection_frequency', '$au_reboot_timeout', '$au_noautoreboot', ";
      $sql .= "'$timestamp', '$timestamp') ";

      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    } else {
      // Already present in database - Update timestamp and all fields
      $sql  = "UPDATE auto_updating SET ";
      $sql .= "au_timestamp = '$timestamp', au_gpo_configured = '$au_gpo_configured', au_enabled = '$au_enabled', ";
      $sql .= "au_behaviour = '$au_behaviour', au_sched_install_day = '$au_sched_install_day', au_sched_install_time = '$au_sched_install_time', ";
      $sql .= "au_use_wuserver = '$au_use_wuserver', au_wuserver = '$au_wuserver', au_wustatusserver = '$au_wustatusserver', ";
      $sql .= "au_target_group = '$au_target_group', au_elevate_nonadmins = '$au_elevate_nonadmins', au_auto_install = '$au_auto_install', ";
      $sql .= "au_detection_frequency = '$au_detection_frequency', au_reboot_timeout = '$au_reboot_timeout', au_noautoreboot = '$au_noautoreboot' ";
      $sql .= "WHERE au_uuid = '$uuid' ";
      $sql .= "AND au_timestamp = '$au_timestamp'";
      if ($verbose == "y"){echo $sql . "<br />\n\n";}
      $db=GetOpenAuditDbConnection(); $result = mysqli_query($db,$sql) or die ('Insert Failed: ' . mysqli_error($db) . '<br />' . $sql);
    }
   
}



echo "<a href=\"javascript:window.close()\" name=\"clicktoclose\">Close</a>";

//Get current time as we did at start
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];
//Store end time in a variable
    $tend = $mtime;
//Calculate the difference
    $totaltime = ($tend - $tstart);
//Output result
    echo "<br />&nbsp;<br />Page was generated in " . round($totaltime,2) . " seconds !";

function nslookup ($ip) {
 $host = split('Name:',`nslookup $ip`);
 return ( trim (isset($host[1]) ? str_replace ("\n".'Address:  '.$ip, '', $host[1]) : $ip));
}

function return_country($country)
{
$country_formatted = "Unknown";
if ($country == "335"){$country_formatted="Albania";}
if ($country == "213"){$country_formatted="Algeria";}
if ($country == "684"){$country_formatted="American Samoa";}
if ($country == "33") {$country_formatted="Andorra";}
if ($country == "809"){$country_formatted="Anguilla";}
if ($country == "672"){$country_formatted="Antarctica";}
if ($country == "809"){$country_formatted="Antigua And Barbuda";}
if ($country == "54") {$country_formatted="Argentina";}
if ($country == "297"){$country_formatted="Aruba";}
if ($country == "61") {$country_formatted="Australia";}
if ($country == "43") {$country_formatted="Austria";}
if ($country == "809"){$country_formatted="Bahamas";}
if ($country == "809"){$country_formatted="Barbados";}
if ($country == "32") {$country_formatted="Belgium";}
if ($country == "501"){$country_formatted="Belize";}
if ($country == "229"){$country_formatted="Benin";}
if ($country == "809"){$country_formatted="Bermuda";}
if ($country == "591"){$country_formatted="Bolivia";}
if ($country == "267"){$country_formatted="Botswana";}
if ($country == "55") {$country_formatted="Brazil";}
if ($country == "673"){$country_formatted="Brunei Darussalam";}
if ($country == "237"){$country_formatted="Cameroon";}
if ($country == "1")  {$country_formatted="Canada";}
if ($country == "238"){$country_formatted="Cape Verde Islands";}
if ($country == "809"){$country_formatted="Cayman Islands";}
if ($country == "56") {$country_formatted="Chile";}
if ($country == "86") {$country_formatted="China";}
if ($country == "57") {$country_formatted="Colombia";}
if ($country == "242"){$country_formatted="Congo, People's Republic of Congo";}
if ($country == "506"){$country_formatted="Costa Rica";}
if ($country == "225"){$country_formatted="Cote D'Ivoire (Ivory Coast)";}
if ($country == "357"){$country_formatted="Cyprus";}
if ($country == "420"){$country_formatted="Czech Republic";}
if ($country == "45") {$country_formatted="Denmark";}
if ($country == "253"){$country_formatted="Djibouti";}
if ($country == "809"){$country_formatted="Dominica";}
if ($country == "809"){$country_formatted="Dominican Republic";}
if ($country == "593"){$country_formatted="Ecuador";}
if ($country == "20") {$country_formatted="Egypt";}
if ($country == "503"){$country_formatted="El Salvador";}
if ($country == "251"){$country_formatted="Ethiopia";}
if ($country == "298"){$country_formatted="Faroe Islands";}
if ($country == "679"){$country_formatted="Fiji";}
if ($country == "358"){$country_formatted="Finland";}
if ($country == "33") {$country_formatted="France";}
if ($country == "596"){$country_formatted="France, Metropolitan";}
if ($country == "594"){$country_formatted="French Guiana";}
if ($country == "689"){$country_formatted="French Polynesia";}
if ($country == "241"){$country_formatted="Gabon";}
if ($country == "220"){$country_formatted="Gambia";}
if ($country == "49") {$country_formatted="Germany";}
if ($country == "233"){$country_formatted="Ghana";}
if ($country == "350"){$country_formatted="Gibraltar";}
if ($country == "30") {$country_formatted="Greece";}
if ($country == "299"){$country_formatted="Greenland";}
if ($country == "809"){$country_formatted="Grenada";}
if ($country == "590"){$country_formatted="Guadeloupe";}
if ($country == "671"){$country_formatted="Guam";}
if ($country == "502"){$country_formatted="Guatemala";}
if ($country == "224"){$country_formatted="Guinea";}
if ($country == "592"){$country_formatted="Guyana";}
if ($country == "509"){$country_formatted="Haiti";}
if ($country == "504"){$country_formatted="Honduras";}
if ($country == "852"){$country_formatted="Hong Kong Special Administrative Region of China";}
if ($country == "36") {$country_formatted="Hungary";}
if ($country == "354"){$country_formatted="Iceland";}
if ($country == "91") {$country_formatted="India";}
if ($country == "62") {$country_formatted="Indonesia";}
if ($country == "98") {$country_formatted="Iran (Islamic Republic Of)";}
if ($country == "964"){$country_formatted="Iraq";}
if ($country == "964"){$country_formatted="Ireland";}
if ($country == "972"){$country_formatted="Israel";}
if ($country == "39") {$country_formatted="Italy";}
if ($country == "809"){$country_formatted="Jamaica";}
if ($country == "81") {$country_formatted="Japan";}
if ($country == "962"){$country_formatted="Jordan";}
if ($country == "254"){$country_formatted="Kenya";}
if ($country == "686"){$country_formatted="Kiribati";}
if ($country == "82") {$country_formatted="Korea, Democratic People's Republic Of";}
if ($country == "965"){$country_formatted="Kuwait";}
if ($country == "961"){$country_formatted="Lebanon";}
if ($country == "266"){$country_formatted="Lesotho";}
if ($country == "231"){$country_formatted="Liberia";}
if ($country == "218"){$country_formatted="Libyan Arab Jamahiriya";}
if ($country == "41") {$country_formatted="Liechtenstein";}
if ($country == "352"){$country_formatted="Luxembourg";}
if ($country == "853"){$country_formatted="Macao (Macau) Special Administrative Region of China";}
if ($country == "38") {$country_formatted="Macedonia, The Former Yugoslav Republic Of";}
if ($country == "265"){$country_formatted="Malawi";}
if ($country == "60") {$country_formatted="Malaysia";}
if ($country == "960"){$country_formatted="Maldives";}
if ($country == "223"){$country_formatted="Mali Republic";}
if ($country == "356"){$country_formatted="Malta";}
if ($country == "692"){$country_formatted="Marshall Islands";}
if ($country == "230"){$country_formatted="Mauritius";}
if ($country == "52") {$country_formatted="Mexico";}
if ($country == "691"){$country_formatted="Micronesia, Federated States of";}
if ($country == "33") {$country_formatted="Monaco";}
if ($country == "809"){$country_formatted="Montserrat";}
if ($country == "212"){$country_formatted="Morocco";}
if ($country == "264"){$country_formatted="Namibia";}
if ($country == "674"){$country_formatted="Nauru";}
if ($country == "977"){$country_formatted="Nepal";}
if ($country == "31") {$country_formatted="Netherlands";}
if ($country == "599"){$country_formatted="Netherlands Antilles";}
if ($country == "809"){$country_formatted="Nevis";}
if ($country == "687"){$country_formatted="New Caledonia";}
if ($country == "64") {$country_formatted="New Zealand";}
if ($country == "505"){$country_formatted="Nicaragua";}
if ($country == "227"){$country_formatted="Niger";}
if ($country == "234"){$country_formatted="Nigeria";}
if ($country == "47") {$country_formatted="Norway";}
if ($country == "968"){$country_formatted="Oman";}
if ($country == "92") {$country_formatted="Pakistan";}
if ($country == "507"){$country_formatted="Panama";}
if ($country == "675"){$country_formatted="Papua New Guinea";}
if ($country == "595"){$country_formatted="Paraguay";}
if ($country == "51") {$country_formatted="Peru";}
if ($country == "63") {$country_formatted="Philippines";}
if ($country == "48") {$country_formatted="Poland";}
if ($country == "351"){$country_formatted="Portugal";}
if ($country == "974"){$country_formatted="Qatar";}
if ($country == "262"){$country_formatted="R�nion Island";}
if ($country == "40") {$country_formatted="Romania";}
if ($country == "250"){$country_formatted="Rwanda";}
if ($country == "39") {$country_formatted="San Marino";}
if ($country == "966"){$country_formatted="Saudi Arabia";}
if ($country == "221"){$country_formatted="Senegal";}
if ($country == "248"){$country_formatted="Seychelles Islands";}
if ($country == "232"){$country_formatted="Sierra Leone";}
if ($country == "65") {$country_formatted="Singapore";}
if ($country == "421"){$country_formatted="Slovakia (Slovak Republic)";}
if ($country == "677"){$country_formatted="Solomon Islands";}
if ($country == "27") {$country_formatted="South Africa";}
if ($country == "34") {$country_formatted="Spain";}
if ($country == "94") {$country_formatted="Sri Lanka";}
if ($country == "597"){$country_formatted="Suriname";}
if ($country == "268"){$country_formatted="Swaziland";}
if ($country == "46") {$country_formatted="Sweden";}
if ($country == "41") {$country_formatted="Switzerland";}
if ($country == "963"){$country_formatted="Syrian Arab Republic";}
if ($country == "886"){$country_formatted="Taiwan, Province Of China";}
if ($country == "255"){$country_formatted="Tanzania, United Republic Of";}
if ($country == "66") {$country_formatted="Thailand";}
if ($country == "228"){$country_formatted="Togo";}
if ($country == "676"){$country_formatted="Tonga";}
if ($country == "809"){$country_formatted="Trinidad And Tobago";}
if ($country == "216"){$country_formatted="Tunisia";}
if ($country == "90") {$country_formatted="Turkey";}
if ($country == "809"){$country_formatted="Turks And Caicos Islands";}
if ($country == "256"){$country_formatted="Uganda";}
if ($country == "971"){$country_formatted="United Arab Emirates";}
if ($country == "44") {$country_formatted="United Kingdom";}
if ($country == "1")  {$country_formatted="United States";}
if ($country == "598"){$country_formatted="Uruguay";}
if ($country == "39") {$country_formatted="Vatican City State (Holy See)";}
if ($country == "58") {$country_formatted="Venezuela";}
if ($country == "967"){$country_formatted="Yemen Arab Republic";}
if ($country == "38") {$country_formatted="Yugoslavia";}
if ($country == "243"){$country_formatted="Zaire";}
if ($country == "260"){$country_formatted="Zambia";}
if ($country == "263"){$country_formatted="Zimbabwe";}
return $country_formatted;
}

function return_language($language)
{
$lang = "Unknown";
if ($language == "1"){$lang="Arabic";}
if ($language == "4"){$lang="Chinese";}
if ($language == "9"){$lang="English";}
if ($language == "1025"){$lang="Arabic  Saudi Arabia";}
if ($language == "1026"){$lang="Bulgarian";}
if ($language == "1027"){$lang="Catalan";}
if ($language == "1028"){$lang="Chinese  Taiwan";}
if ($language == "1029"){$lang="Czech";}
if ($language == "1030"){$lang="Danish";}
if ($language == "1031"){$lang="German  Germany";}
if ($language == "1032"){$lang="Greek";}
if ($language == "1033"){$lang="English  United States";}
if ($language == "1034"){$lang="Spanish  Traditional Sort";}
if ($language == "1035"){$lang="Finnish";}
if ($language == "1036"){$lang="French  France";}
if ($language == "1037"){$lang="Hebrew";}
if ($language == "1038"){$lang="Hungarian";}
if ($language == "1039"){$lang="Icelandic";}
if ($language == "1040"){$lang="Italian  Italy";}
if ($language == "1041"){$lang="Japanese";}
if ($language == "1042"){$lang="Korean";}
if ($language == "1043"){$lang="Dutch  Netherlands";}
if ($language == "1044"){$lang="Norwegian  Bokmal";}
if ($language == "1045"){$lang="Polish";}
if ($language == "1046"){$lang="Portuguese  Brazil";}
if ($language == "1047"){$lang="Rhaeto-Romanic";}
if ($language == "1048"){$lang="Romanian";}
if ($language == "1049"){$lang="Russian";}
if ($language == "1050"){$lang="Croatian";}
if ($language == "1051"){$lang="Slovak";}
if ($language == "1052"){$lang="Albanian";}
if ($language == "1053"){$lang="Swedish";}
if ($language == "1054"){$lang="Thai";}
if ($language == "1055"){$lang="Turkish";}
if ($language == "1056"){$lang="Urdu";}
if ($language == "1057"){$lang="Indonesian";}
if ($language == "1058"){$lang="Ukrainian";}
if ($language == "1059"){$lang="Belarusian";}
if ($language == "1060"){$lang="Slovenian";}
if ($language == "1061"){$lang="Estonian";}
if ($language == "1062"){$lang="Latvian";}
if ($language == "1063"){$lang="Lithuanian";}
if ($language == "1065"){$lang="Persion";}
if ($language == "1066"){$lang="Vietnamese";}
if ($language == "1069"){$lang="Basque";}
if ($language == "1070"){$lang="Serbian";}
if ($language == "1071"){$lang="Macedonian (FYROM)";}
if ($language == "1072"){$lang="Sutu";}
if ($language == "1073"){$lang="Tsonga";}
if ($language == "1074"){$lang="Tswana";}
if ($language == "1076"){$lang="Xhosa";}
if ($language == "1077"){$lang="Zulu";}
if ($language == "1078"){$lang="Afrikaans";}
if ($language == "1080"){$lang="Faeroese";}
if ($language == "1081"){$lang="Hindi";}
if ($language == "1082"){$lang="Maltese";}
if ($language == "1084"){$lang="Gaelic";}
if ($language == "1085"){$lang="Yiddish";}
if ($language == "1086"){$lang="Malay  Malaysia";}
if ($language == "2049"){$lang="Arabic  Iraq";}
if ($language == "2052"){$lang="Chinese  PRC";}
if ($language == "2055"){$lang="German  Switzerland";}
if ($language == "2057"){$lang="English  United Kingdom";}
if ($language == "2058"){$lang="Spanish  Mexico";}
if ($language == "2060"){$lang="French  Belgium";}
if ($language == "2064"){$lang="Italian  Switzerland";}
if ($language == "2067"){$lang="Dutch  Belgium";}
if ($language == "2068"){$lang="Norwegian  Nynorsk";}
if ($language == "2070"){$lang="Portuguese  Portugal";}
if ($language == "2072"){$lang="Romanian  Moldova";}
if ($language == "2073"){$lang="Russian  Moldova";}
if ($language == "2074"){$lang="Serbian  Latin";}
if ($language == "2077"){$lang="Swedish  Finland";}
if ($language == "3073"){$lang="Arabic  Egypt";}
if ($language == "3076"){$lang="Chinese  Hong Kong SAR";}
if ($language == "3079"){$lang="German  Austria";}
if ($language == "3081"){$lang="English  Australia";}
if ($language == "3082"){$lang="Spanish  International Sort";}
if ($language == "3084"){$lang="French  Canada";}
if ($language == "3098"){$lang="Serbian  Cyrillic";}
if ($language == "4097"){$lang="Arabic  Libya";}
if ($language == "4100"){$lang="Chinese  Singapore";}
if ($language == "4103"){$lang="German  Luxembourg";}
if ($language == "4105"){$lang="English  Canada";}
if ($language == "4106"){$lang="Spanish  Guatemala";}
if ($language == "4108"){$lang="French  Switzerland";}
if ($language == "5121"){$lang="Arabic  Algeria";}
if ($language == "5127"){$lang="German  Liechtenstein";}
if ($language == "5129"){$lang="English  New Zealand";}
if ($language == "5130"){$lang="Spanish  Costa Rica";}
if ($language == "5132"){$lang="French  Luxembourg";}
if ($language == "6145"){$lang="Arabic  Morocco";}
if ($language == "6153"){$lang="English  Ireland";}
if ($language == "6154"){$lang="Spanish  Panama";}
if ($language == "7169"){$lang="Arabic  Tunisia";}
if ($language == "7177"){$lang="English  South Africa";}
if ($language == "7178"){$lang="Spanish  Dominican Republic";}
if ($language == "8193"){$lang="Arabic  Oman";}
if ($language == "8201"){$lang="English  Jamaica";}
if ($language == "8202"){$lang="Spanish  Venezuela";}
if ($language == "9217"){$lang="Arabic  Yemen";}
if ($language == "9226"){$lang="Spanish  Colombia";}
if ($language == "10241"){$lang="Arabic  Syria";}
if ($language == "10249"){$lang="English  Belize";}
if ($language == "10250"){$lang="Spanish  Peru";}
if ($language == "11265"){$lang="Arabic  Jordan";}
if ($language == "11273"){$lang="English  Trinidad";}
if ($language == "11274"){$lang="Spanish  Argentina";}
if ($language == "12289"){$lang="Arabic  Lebanon";}
if ($language == "12298"){$lang="Spanish  Ecuador";}
if ($language == "13313"){$lang="Arabic  Kuwait";}
if ($language == "13322"){$lang="Spanish  Chile";}
if ($language == "14337"){$lang="Arabic  U.A.E.";}
if ($language == "14346"){$lang="Spanish  Uruguay";}
if ($language == "15361"){$lang="Arabic  Bahrain";}
if ($language == "15370"){$lang="Spanish  Paraguay";}
if ($language == "16385"){$lang="Arabic  Qatar";}
if ($language == "16394"){$lang="Spanish  Bolivia";}
if ($language == "17418"){$lang="Spanish  El Salvador";}
if ($language == "18442"){$lang="Spanish  Honduras";}
if ($language == "19466"){$lang="Spanish  Nicaragua";}
if ($language == "20490"){$lang="Spanish  Puerto Rico";}
return $lang;
}

?>
