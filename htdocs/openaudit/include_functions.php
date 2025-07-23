<?php
/**********************************************************************************************************
Module:	include_functions.php

Description:
		General purpose functions used throughout the application. aswell defines Server-Port for OA-Calls, default 888

Recent Changes:

	[Nick Brown]	02/03/2009	Added GetLdapConnectionsFromDb() , GetOpenAuditDbConnection() 
	and RedirectToUrl(). LogEvent() modified to use GetOpenAuditDbConnection()
	[Nick Brown]	11/03/2009	Change to GetLdapConnectionsFromDb()
	[Nick Brown]	23/03/2009	Change to GetAesKey()
	[Nick Brown]	03/04/2009	Added ConnectToOpenAuditDb() and ConvertBinarySidToSddl()
	[Nick Brown]	17/04/2009	Minor change to ConvertSpecialField()
	[Nick Brown]	24/04/2009	Minor change to  GetLdapConnectionsFromDb()
	[Nick Brown]	01/05/2009	Incldued "application_class.php" to provide access to the global $TheApp object. 
	GetAesKey() re-written to use $TheApp
	[Nick Brown]	06/05/2009	GetAesKey() modified
	[Nick Brown]	12/05/2009	GetAesKey() modified
	[Nick Brown]	19/08/2009	GetLdapConnectionsFromDb() modified
	[Edoardo]		22/06/2010	Minor additions to functions ConvertSpecialField() and determine_os()

**********************************************************************************************************/
require_once "application_class.php";
require_once "include_config.php";

// If webserver running on other port, please change server port here and in all audit.config files in the subdirs 
$oaserver_port = '888';

$enable_remote_management = 'y';

function return_unknown($something)
{
  if ($something == "") { $something = ""; } else {}
  if ($something == NULL) { $something = ""; } else {}
  return $something;
}

function get_headerbanner() {
	?>
	<div class="headerbanner"><div style="display:inline-block;backdrop-filter:blur(8px)">
	<a style="padding-left:6px;font-size:2.5em;color:#000;line-height:1em;text-shadow: -1px -1px 0 #fff, 1px -1px 0 #fff, -1px 1px 0 #fff, 1px 1px 0 #fff" href="index.php">OpenAudit Classic - 
	<?php echo strtoupper($_SERVER['SERVER_NAME']); ?>
	</a>
	</div>
	<?php
}

function ip_trans($ip)
{
// check that the string is valid for an IP, it must have at least one . in it
  if (($ip <> "") AND (!(is_null($ip))) AND (Substr_Count($ip,".")>0)){
   $myip = explode(".",$ip);
   $myip[0] = ltrim($myip[0], "0");
   if ($myip[0] == "") { $myip[0] = "0"; }
   if(isset($myip[1])) $myip[1] = ltrim($myip[1], "0");
   if (!isset($myip[1]) OR $myip[1] == "") { $myip[1] = "0"; }
   if(isset($myip[2])) $myip[2] = ltrim($myip[2], "0");
   if (!isset($myip[2]) OR $myip[2] == "") { $myip[2] = "0"; }
   if(isset($myip[3])) $myip[3] = ltrim($myip[3], "0");
   if (!isset($myip[3]) OR $myip[3] == "") { $myip[3] = "0"; }
   $ip = $myip[0] . "." . $myip[1] . "." . $myip[2] . "." . $myip[3];
  } else {
   $ip = " Not-Networked";
  }
  return $ip;
}

function ip_trans_to($ip)
{
  if (($ip <> "") AND (!(is_null($ip)))){
   $myip = explode(".",$ip);
   $ip = substr("000" . $myip[0], -3);
   if(isset($myip[1])){
     $myip[1] = substr("000" . $myip[1], -3);
     $ip = $ip . "." . $myip[1];}
   if(isset($myip[2])){
     $myip[2] = substr("000" . $myip[2], -3);
     $ip = $ip . "." . $myip[2];}
   if(isset($myip[3])){
     $myip[3] = substr("000" . $myip[3], -3);
     $ip = $ip . "." . $myip[3];}
  } else {
   $ip = " Not-Networked";
  }
  return $ip;
}

function url_clean($url)
{
$url_clean = str_replace ('%','%25',$url);
$url_clean = str_replace ('$','%24',$url_clean);
$url_clean = str_replace (' ','%20',$url_clean);
$url_clean = str_replace ('+','%2B',$url_clean);
$url_clean = str_replace ('&','%26',$url_clean);
$url_clean = str_replace (',','%2C',$url_clean);
$url_clean = str_replace ('/','%2F',$url_clean);
$url_clean = str_replace (':','%3A',$url_clean);
$url_clean = str_replace ('=','%3D',$url_clean);
$url_clean = str_replace ('?','%3F',$url_clean);
$url_clean = str_replace ('<','%3C',$url_clean);
$url_clean = str_replace ('>','%3E',$url_clean);
$url_clean = str_replace ('#','%23',$url_clean);
$url_clean = str_replace ('{','%7B',$url_clean);
$url_clean = str_replace ('}','%7D',$url_clean);
$url_clean = str_replace ('|','%7C',$url_clean);
$url_clean = str_replace ('\\','%5C',$url_clean);
$url_clean = str_replace ('^','%5E',$url_clean);
$url_clean = str_replace ('~','%7E',$url_clean);
$url_clean = str_replace ('[','%5B',$url_clean);
$url_clean = str_replace (']','%5D',$url_clean);
$url_clean = str_replace ('`','%60',$url_clean);
return $url_clean;
}

function return_date($timestamp) {
	$timestamp = substr($timestamp, 6, 2) . "." . substr($timestamp, 4, 2) . "." . substr($timestamp, 0, 4);
return $timestamp;
}

function return_date_time($timestamp) {
	$timestamp = substr($timestamp, 6, 2) . "." . substr($timestamp, 4, 2) . "." . substr($timestamp, 0, 4) . " " . substr($timestamp, 8, 2) . ":" . substr($timestamp, 10, 2);
return $timestamp;
}

function return_timestamp($timestamp) {
	$timestamp = strtotime(substr($timestamp, 0, 4) . "-" . substr($timestamp, 4, 2) . "-" . substr($timestamp, 6, 2) . " " . substr($timestamp, 8, 2) . ":" . substr($timestamp, 10, 2). ":" . substr($timestamp, 12, 2));
return $timestamp;
}

// Zeitdifferenz ermitteln und gestern/vorgestern/morgen schreiben: chartscodes, dedo, foldergallery, timeclock, w4-post-list
function human_timing($time)
{
    $time = time() - $time; // to get the time since that moment
    $tokens = array (
        31536000 => 'Jahr',
        2592000 => 'Monat',
        604800 => 'Woche',
        86400 => 'Tag',
        3600 => 'Stunde',
        // 60 => 'Min',
        // 1 => 'Sek'
    );
    $result = '';
    $counter = 1;
    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        if ($counter > 2) break;
        if ($text=='Woche' || $text=='Stunde') $plural="n "; else $plural="e ";
		$numberOfUnits = floor($time / $unit);
		$result .= "$numberOfUnits $text".($numberOfUnits > 1 ? $plural : ' ');
        $time -= $numberOfUnits * $unit;
        ++$counter;
    }
    return $result;
}


function return_unix_date_time($timestamp) {
  return date('d/m/y h:i:s a', $timestamp);
}

function adjustdate($years=0,$months=0,$days=0) {
  $todayyear=date('Y');
  $todaymonth=date('m');
  $todayday=date('d');
  return date("Ymd",mktime(0,0,0,$todaymonth+$months,$todayday+$days,$todayyear+ $years));
}


function alternate_tr_class(&$current_class) {
	$current_class = ($current_class == 'npb_highlight_row') ? '' : 'npb_highlight_row';
	return $current_class;
}

function modify_config($name, $value) {
  $SQL = "SELECT * FROM config WHERE config_name = '" . $name . "'";
  $result = mysqli_query($db,$SQL);

  if ($myrow = mysqli_fetch_array($result)){
    $SQL = "UPDATE `config` SET `config_value` = '" . $value . "' WHERE CONVERT( `config_name` USING utf8 ) = '" . $name . "' LIMIT 1 ;";
    $result = mysqli_query($db,$SQL);
  } else {
    $SQL = "INSERT INTO `config` ( `config_name` , `config_value` )
            VALUES (
            '" . $name . "', '" . $value . "'
            );";
    $result = mysqli_query($db,$SQL);
  }
}

function get_config($name) {
  // check for cached result
  if(isset($configarray['$name']))
    return $configarray['$name'];

  $db=GetOpenAuditDbConnection();
  $SQL = "SELECT config_value FROM config WHERE config_name = '" . $name . "'";
  $result = mysqli_query($db,$SQL);

  if ($myrow = mysqli_fetch_array($result)){
    $configarray['$name'] = $myrow['config_value'];
    return $configarray['$name'];
  }

  // couldn't find that config...
  return "";
}

function versionCheck($dbversion, $latestversion) {
  $ver = explode(".",$dbversion);
  $lver = explode(".",$latestversion);

  if (($ver[0] < $lver[0]) OR
      ($ver[0] <= $lver[0] AND $ver[1] < $lver[1]) OR
      ($ver[0] <= $lver[0] AND $ver[1] <= $lver[1] AND $ver[2] < $lver[2])) {
    return TRUE;
  } else {
    return FALSE;
  }
}

/**********************************************************************************************************
Function Name:
	ConvertSpecialField
Description:
	Originally named "special_field_converting"
	Converts values from database to human-readable fields
Arguments:
	$myrow		[IN] [Array]		Database data row
	$field		[IN] [Array]		Fileld from $query_array, $Fields defined in the list_viewdef 
	$db			[IN] [Resource]	Reference to the Open Audit db
	$page		[IN] [String]		Name of PHP module (page) calling the function
Returns:	[String]	Formatted string
Change Log:
	10/09/2008		Function re-written based on original "special_field_converting" function	[Nick Brown]
	17/04/2009		Return "system_timestamp" as date-time rather than date	[Nick Brown]
**********************************************************************************************************/
function ConvertSpecialField($myrow, $field, $db, $page)
{
	if(!isset($field["name"])) {return "";}

	switch($field["name"])
	{
		case "system_os_name":
			return determine_os($myrow[$field["name"]]);
		case "net_dhcp_lease_obtained":
		case "net_dhcp_lease_expires":
		case "net_driver_date":
		case "ldap_computers_timestamp";
		case "ldap_users_timestamp";
			return return_date($myrow[$field["name"]]);
		case "net_speed":
			return 	strval($myrow[$field["name"]])." Mbps";
		case "software_first_timestamp":
		case "software_timestamp":
		case "system_first_timestamp":
		case "system_timestamp":
		case "other_first_timestamp":
		case "other_timestamp":
		case "motherboard_first_timestamp":
		case "motherboard_timestamp":
		case "onboard_first_timestamp":
		case "onboard_timestamp":
		case "hard_drive_first_timestamp":
		case "hard_drive_timestamp":
		case "partition_first_timestamp":
		case "partition_timestamp":
		case "scsi_controller_first_timestamp":
		case "scsi_controller_timestamp":
		case "optical_drive_first_timestamp":
		case "optical_drive_timestamp":
		case "floppy_first_timestamp":
		case "floppy_timestamp":
		case "tape_drive_first_timestamp":
		case "tape_drive_timestamp":
		case "processor_first_timestamp":
		case "processor_timestamp":
		case "bios_first_timestamp":
		case "bios_timestamp":
		case "memory_first_timestamp":
		case "memory_timestamp":
		case "net_first_timestamp":
		case "net_timestamp":
		case "video_first_timestamp":
		case "video_timestamp":
		case "monitor_first_timestamp":
		case "monitor_timestamp":
		case "sound_first_timestamp":
		case "sound_timestamp":
		case "keyboard_first_timestamp":
		case "keyboard_timestamp":
		case "mouse_first_timestamp":
		case "mouse_timestamp":
		case "modem_first_timestamp":
		case "modem_timestamp":
		case "battery_first_timestamp":
		case "battery_timestamp":
		case "printer_first_timestamp":
		case "printer_timestamp":
		case "usb_first_timestamp":
		case "usb_timestamp":
		case "system_audits_timestamp":
		case "system_last_boot":
		case "log_timestamp";
			return return_date_time($myrow[$field["name"]]);
		case "audit_log_timestamp":
		case "audit_log_time":
		case "ws_log_timestamp";
			return return_unix_date_time($myrow[$field["name"]]);
		case "system_system_type":
			if($page=="list") {return determine_img($myrow["system_os_name"],$myrow[$field["name"]]);
			} else {return $myrow[$field["name"]];}
		case "other_type":
			if($page=="list") {
				if (strlen($myrow[$field["name"]]) > 20) {$typebild="router";} else {$typebild = str_replace(" ","_",$myrow[$field["name"]]);}
				return " <img src=\"images/o_".$typebild.".png\" alt=\"".$myrow[$field["name"]]."\" title=\"".$myrow[$field["name"]]."\" width=\"16\" height=\"16\"/> ".$myrow[$field["name"]]."";
			} else {return $myrow[$field["name"]];}
		case "other_ip_address":
			return ip_trans($myrow[$field["name"]]);
		case "delete":
			return "<img src=\"images/button_delete_out.png\" id=\"button".rand(0,999999999)."\" width=\"58\" height=\"22\" border=\"0\" alt=\"\" />";
		case "ldap_user_status":
			return "<img src='../images/user_".$myrow[$field["name"]].".gif'>";			
		case "ldap_computer_status":
			return "<img src='../images/computer_".$myrow[$field["name"]].".gif'>";			
		case "startup_location":
			if(substr($myrow[$field["name"]],0,2) == "HK") return __("Registry");
		case "percentage":
		case "disk_percent":
			return $myrow[$field["name"]]." %";
		case "system_memory":
		case "video_adapter_ram":
		case "hard_drive_size":
		case "partition_size":
		case "partition_free_space":
			return number_format(($myrow[$field["name"]])/1024, 1, ',', '.')."G";
		case "partition_used_space":
			return number_format(($myrow[$field["name"]])/1024, 1, ',', '.')."G";
		case "total_memory":
		case "pagefile_initial_size":
		case "pagefile_max_size":
			return ($myrow[$field["name"]])." MB";
		case "evt_log_file_size":
		case "evt_log_max_file_size":
			return number_format($myrow[$field["name"]])." KB";
		case "video_current_number_colours":
			return (strlen(decbin(intval($myrow[$field["name"]])))+1)." Bit";
		case "video_current_refresh_rate":
			return $myrow[$field["name"]]." Hz";
		case "firewall_enabled_domain":
		case "firewall_enabled_standard":
		case "firewall_disablenotifications_standard":
		case "firewall_donotallowexceptions_standard":
		case "firewall_disablenotifications_domain":
		case "firewall_donotallowexceptions_domain":
			if ($myrow[$field["name"]]=="1") return __("Yes");
			if ($myrow[$field["name"]]=="0") return __("No");
			return __("Profile Not Detected");
		case "other_ip_address":
			return ($myrow["other_ip_address"]=="" AND !isset($_REQUEST["edit"])) ? "Not-Networked" : $myrow[$field["name"]];
		case "net_dhcp_server":
			return ($myrow[$field["name"]]=="none") ? __("No") : __("Yes")." / ".$myrow[$field["name"]];
		case "auth_enabled":
		case "auth_admin":
			if($myrow[$field["name"]]=="0") return __("No");
			if($myrow[$field["name"]]=="1") return __("Yes");
			return $myrow[$field["name"]];
		case "auth_hash":
			return "*****";
		case "other_linked_pc":
			if(!isset($_REQUEST["edit"]))
			{
				$result = mysqli_query($db,"SELECT system_name FROM system WHERE system_uuid='".$myrow[$field["name"]]."' AND system_uuid != '' ");
				return ($myrow3 = mysqli_fetch_array($result)) ? $myrow3["system_name"] : $myrow[$field["name"]];
			}
		case "monitor_uuid":
			if(!isset($_REQUEST["edit"]) OR (isset($_REQUEST["edit"]) AND isset($field["edit"]) AND $field["edit"]=="n"))
			{
				$result = mysqli_query($db,"SELECT system_name FROM system WHERE system_uuid = '".$myrow[$field["name"]]."' AND system_uuid != '' ");
				return ($myrow3 = mysqli_fetch_array($result)) ? $myrow3["system_name"] : $myrow[$field["name"]];
			}
		default:
			if(isset($myrow[$field["name"]])) return $myrow[$field["name"]];
	}	
	return "";
}

function determine_os($os) {

//    $os_returned = __("version unknown");
    $os_returned = __($os);

    //Direct match
    $systems=array( "Windows XP"=>"Win XP",
                    "Windows NT"=>"Win NT",
                    "Windows 2000"=>"Win 2000",
                    "Server 2003"=>"2003 Server, Std",
                    "Microsoft(R) Windows(R) Server 2003, Web Edition"=>"2003 Server, Web",
                    "Microsoft(R) Windows(R) Server 2003, Standard Edition"=>"2003 Server, Std",
                    "Microsoft(R) Windows(R) Server 2003, Standard Edition R2"=>"2003 Server R2, Std",
                    "Microsoft(R) Windows(R) Server 2003, for Small Business Server"=>"2003 Server, SBS",
                    "Microsoft(R) Windows(R) Server 2003, for Small Business Server R2"=>"2003 Server R2, SBS",
                    "Microsoft(R) Windows(R) Server 2003, Enterprise Edition"=>"2003 Server, Ent",
                    "Microsoft(R) Windows(R) Server 2003, Enterprise Edition R2"=>"2003 Server R2, Ent",
                    "Microsoft(R) Windows(R) Server 2003, Data Center Edition"=>"2003 Server, Data",
                    "Microsoft(R) Windows(R) Server 2003, Data Center Edition R2"=>"2003 Server R2, Data",
                    "Microsoft(R) Windows(R) Server 2003, Standard x64 Edition"=>"2003 Server x64, Std",
                    "Microsoft(R) Windows(R) Server 2003, Standard x64 Edition R2"=>"2003 Server R2 x64, Std",
                    "Microsoft(R) Windows(R) Server 2003, Enterprise x64 Edition"=>"2003 Server x64, Ent",
                    "Microsoft(R) Windows(R) Server 2003, Enterprise x64 Edition R2"=>"2003 Server R2 x64, Ent",
                    "Microsoft(R) Windows(R) Server 2003 Web Edition"=>"2003 Server, Web",
                    "Microsoft(R) Windows(R) Server 2003 Standard Edition"=>"2003 Server, Std",
                    "Microsoft(R) Windows(R) Server 2003 for Small Business Server"=>"2003 Server, SBS",
                    "Microsoft(R) Windows(R) Server 2003 Enterprise Edition"=>"2003 Server, Ent",
                    "Microsoft(R) Windows(R) Server 2003 Data Center Edition"=>"2003 Server, Data",
                    "Microsoft(R) Windows(R) Server 2003 Standard x64 Edition"=>"2003 Server x64, Std",
                    "Microsoft(R) Windows(R) Server 2003 Enterprise x64 Edition"=>"2003 Server x64, Ent",
                    "Microsoft Windows XP Tablet PC Edition"=>"XP Tablet",
                    "Microsoft Windows XP Starter Edition"=>"XP Starter",
                    "Microsoft Windows XP Professional x64 Edition"=>"XP Pro 64",
                    "Microsoft Windows XP Professional"=>"XP Pro",
                    "Microsoft Windows XP Media Center Edition"=>"XP MCE",
                    "Microsoft Windows XP Home Edition"=>"XP Home",
                    "Microsoft Windows Powered"=>"Windows Powered",
                    "Microsoft Windows NT Workstation"=>"NT Workstation",
                    "Microsoft Windows NT Server"=>"NT Server",
                    "Microsoft Windows NT Enterprise Server"=>"NT Ent Server",
                    "Microsoft Windows Millenium Edition"=>"Win ME",
                    "Microsoft Windows ME"=>"Win ME",
                    "Microsoft Windows 98 Second Edition"=>"Win 98se",
                    "Microsoft Windows 98"=>"Win 98",
                    "Microsoft Windows 95"=>"Win 95",
                    "Microsoft Windows 2000 Server"=>"2000 Server",
                    "Microsoft Windows 2000 Professional"=>"2000 Pro",
                    "Microsoft Windows 2000 Advanced Server"=>"2000 Adv Server",
                    "Microsoft&#174 Windows Vista&#153 Ultimate"=>"Vista Ultimate",
                    "Microsoft&#174 Windows Vista&#153 Enterprise"=>"Vista Ent",
                    "Microsoft&#174 Windows Vista&#153 Business"=>"Vista Business",
					"Microsoft® Windows Vista™ Business"=>"Vista Business",
					"Microsoft&#174 Windows Server&#174 2008 Standard"=>"2008 Server, Std",
					"Microsoft Windows Server 2008 R2 Standard"=>"2008 Server R2, Std",
					"Microsoft Windows Server 2008 R2 Enterprise"=>"2008 Server R2, Ent",
					"Microsoft Windows 7 Enterprise"=>"Win 7 Ent",);
    reset ($systems);
foreach($systems as $key => $val) {
        if($os==$key){
           $os_returned=$val;

       }
    }

    //Substring match
    $systems_substr=array( "CentOS"=>"CentOS",
                           "Debian"=>"Debian",
                           "Fedora"=>"Fedora",
                           "Gentoo"=>"Gentoo",
                           "Mandrake"=>"Mandrake",
                           "Mandriva"=>"Mandriva",
                           "Novell"=>"Novell",
                           "Red Hat"=>"Red Hat",
                           "Slackware"=>"Slackware",
                           "Suse"=>"Suse",
                           "Ubuntu"=>"Ubuntu",);
    reset ($systems_substr);
	foreach($systems_substr as $key => $val) {
        if(substr_count($os,$key)){
            $os_returned=$val;
        }
    }

    return $os_returned;
}

function determine_img($os,$system_type) {

    $image="button_fail.png";
    $title=__("Unknown");

    if (strpos($os, "Windows")!== false) {
        $image="desktop.png";
        $title=$system_type . ' | ' . determine_os($os);
    }
    if (strpos($os, "Server")!== false) {
        $image="server.png";
        $title=$system_type . ' | ' . determine_os($os);
    }
    if (strpos($system_type, "Notebook")!== false or strpos($system_type, "Portable")!== false or strpos($system_type, "Laptop")!== false) {
        $image="laptop.png";
        $title=$system_type . ' | ' . determine_os($os);
    }
	if (strpos($system_type, "Tablet-PC")!== false) {
        $image="ultrabook.png";
        $title=$system_type . ' | ' . determine_os($os);
    }

    //Substring match
    $systems_substr=array( "CentOS"=>"CentOS",
                           "Debian"=>"Debian",
                           "Fedora"=>"Fedora",
                           "Gentoo"=>"Gentoo",
                           "Mandrake"=>"Mandrake",
                           "Mandriva"=>"Mandriva",
                           "Novell"=>"Novell",
                           "Red Hat"=>"Red Hat",
						   "RedHat"=>"Fedora",        //Fedora 13 Beta 
                           "Slackware"=>"Slackware",
                           "Suse"=>"Suse",
                           "SuSE"=>"SuSE",
                           "SUSE"=>"SUSE",
                           "Ubuntu"=>"Ubuntu",);
    reset ($systems_substr);
	foreach($systems_substr as $key => $val) {
        if(substr_count($os,$key)){
            $image="linux_".strtolower($val).".png";
            $title=$system_type . ' | ' . determine_os($os);
        }
    }
    $ret = "<img src=\"images/".$image."\" width=\"16\" height=\"16\" alt=\"".$title."\" title=\"".$title."\" /> ".$system_type;
    return $ret;
}

function determine_dia_img($os,$system_type) {

    if (is_file("images/o_".$system_type.".png")){
    $image="o_".$system_type.".png";
    $title=__("$system_type");
    }
    else
    {
    $system_type= str_replace(" ","_",$system_type);
    $image="o_".$system_type.".png";
//    $image="button_fail.png";
    $title=__("Unknown");
    }
    if (!is_file("images/o_".$system_type.".png")){
    $image="button_fail.png";
    } else {}

    $image="button_fail.png";
    $title=__("Unknown");

    if (strpos($os, "Windows")!== false) {
        $image="desktop.png";
        $title=determine_os($os);
    }
    if (strpos($os, "Server")!== false) {
        $image="server.png";
        $title=determine_os($os);
    }
    if (strpos($system_type, "Notebook")!== false or strpos($system_type, "Portable")!== false or strpos($system_type, "Laptop")!== false) {
        $image="laptop.png";
        $title=determine_os($os);
    }
	if (strpos($system_type, "Tablet-PC")!== false) {
        $image="ultrabook.png";
        $title=$system_type . ' | ' . determine_os($os);
    }

    //Substring match
    $systems_substr=array( "CentOS"=>"CentOS",
                           "Debian"=>"Debian",
                           "Fedora"=>"Fedora",
                           "Gentoo"=>"Gentoo",
                           "Mandrake"=>"Mandrake",
                           "Mandriva"=>"Mandriva",
                           "Novell"=>"Novell",
                           "Red Hat"=>"Red Hat",
                           "Slackware"=>"Slackware",
                           "Suse"=>"Suse",
                           "SuSE"=>"SuSE",
                           "SUSE"=>"SUSE",
                           "Ubuntu"=>"Ubuntu",);
    reset ($systems_substr);
	foreach($systems_substr as $key => $val) {
        if(substr_count($os,$key)){
            $image="linux_".strtolower($val).".png";
            $title=determine_os($os);
        }
    }

    $ret = $image;
    return $ret;

}

function determine_inkscape_img($os,$system_type) {

// Assume we dont know what this is
    $image_folder="images";

    $image="button_fail.png";
    $title=__("Unknown");

// Now we try to find out..

// Does the system_type map to a local PNG
    if (is_file($image_folder."/o_".$system_type.".png")){
    $image="o_".$system_type.".png";
    $title=__("$system_type");
    }
    else
    {
    $system_type= str_replace(" ","_",$system_type);
    $image="o_".$system_type.".png";
//    $image="button_fail.png";
    $title=__("Unknown ".$system_type);
    }
    if (!is_file($image_folder."/o_".$system_type.".png")){
    $image="button_fail.png";
    } else {}

// Does the os map to a local PNG
    if (is_file($image_folder."/o_".$os.".png")){
    $image="o_".$os.".png";
    $title=__("$os");
    }
    else
    {
    $os= str_replace(" ","_",$os);
    $image="o_".$os.".png";
//    $image="button_fail.png";
    $title=__("Unknown ".$os);
    }
    if (!is_file($image_folder."/o_".$os.".png")){
    $image="button_fail.png";
    } else {}

// Lets see if we can work it out from the OS
//
    $image="button_fail.png";
    $title=__("Unknown");

    if (strpos($os, "Windows")!== false) {
        $image="desktop.png";
        $title=determine_os($os);
    }
    if (strpos($os, "Server")!== false) {
        $image="server.png";
        $title=determine_os($os);
    }
    if (strpos($system_type, "Notebook")!== false or strpos($system_type, "Portable")!== false or strpos($system_type, "Laptop")!== false) {
        $image="laptop.png";
        $title=determine_os($os);
    }
	if (strpos($system_type, "Tablet-PC")!== false) {
        $image="ultrabook.png";
        $title=$system_type . ' | ' . determine_os($os);
    }

    //Substring match
    $systems_substr=array( "CentOS"=>"CentOS",
                           "Debian"=>"Debian",
                           "Fedora"=>"Fedora",
                           "Gentoo"=>"Gentoo",
                           "Mandrake"=>"Mandrake",
                           "Mandriva"=>"Mandriva",
                           "Novell"=>"Novell",
                           "Red Hat"=>"Red Hat",
                           "Slackware"=>"Slackware",
                           "Suse"=>"Suse",
                           "SuSE"=>"SuSE",
                           "SUSE"=>"SUSE",
                           "Ubuntu"=>"Ubuntu",);
    reset ($systems_substr);
	foreach($systems_substr as $key => $val) {
        if(substr_count($os,$key)){
            $image="linux_".strtolower($val).".png";
            $title=determine_os($os);
        }
    }
// If we got here, we must have a .png image, even if it is not what we want.
// So now we will look to see if we can find a scaleable image to give is a better looking output
// In other words, lets take the name of the .png, and replace it with a suitable Tango .svg if it exists.
/*
if (is_file($image_folder."\dell-ultrasharp.svg")){
    switch($image){
    case "laptop.png" :
        $image = "computer-laptop-dell-inspiron.svg";
        break;
    case "computer.png" :
        $image = "computer-dell-dimension-E521.svg";
        break;
    case "network-server.png" :
        $image = "dell-ultrasharp.svg";
        break;
    }

   }
*/
    $ret = $image;
    return $ret;

}






//Integrating Search-Values in the SQL-Query (WHERE)
function sql_insert_search($sql_query, $filter){

    //Generating the WHERE-Clause
    $sql_where =" ( 1 ";
    @reset($filter);
	foreach ($filter as $filter_var=>$filter_val) {
        if($filter_val!=""){
            //Delete all "-" if the Searchbox is a timestamp
            if(strpos($filter_var, "timestamp")!== false) { $filter_val=str_replace("-","",$filter_val); }
            $sql_where.= " AND ".$filter_var." LIKE '%".$filter_val."%' ";
            $filter_query=1;
        }
    }
    $sql_where.=" ) ";

    //Searching the WHERE, walking through the statement
    $brackets=0;
    $pos_where=0;
    //Check for WHERE
    if(strpos(strtoupper($sql_query),"WHERE")){
        for ($c=0; $c<strlen($sql_query); $c++) {
            if ($sql_query[$c] =='('){
                ++$brackets;
            }elseif ($sql_query[$c] ==')'){
                --$brackets;
            }
            if($brackets==0 AND substr(strtoupper($sql_query),$c+1,5)=="WHERE" ){
                $pos_where=$c+6;
            }
        }
    }

    //IF there's no WHERE, check for GROUP BY
    //Searching the GROUP BY, walking through the statement
    if($pos_where==0){
        $brackets=0;
        $pos_groupby=0;
        //Check for GROUP BY
        if(strpos(strtoupper($sql_query),"GROUP BY")){
            for ($c=0; $c<strlen($sql_query); $c++) {
                if ($sql_query[$c] =='('){
                    ++$brackets;
                }elseif ($sql_query[$c] ==')'){
                    --$brackets;
                }
                if($brackets==0 AND substr(strtoupper($sql_query),$c+1,8)=="GROUP BY" ){
                    $pos_groupby=$c;
                }
            }
        }

        //Check for JOIN
        $brackets=0;
        $pos_join=0;
        if(strpos(strtoupper($sql_query),"JOIN")){
            for ($c=0; $c<strlen($sql_query); $c++) {
                if ($sql_query[$c] =='('){
                    ++$brackets;
                }elseif ($sql_query[$c] ==')'){
                    --$brackets;
                }
                if($brackets==0 AND substr(strtoupper($sql_query),$c+1,4)=="JOIN" ){
                    $pos_join=$c;
                }
            }
        }
    }

    //Insert search after WHERE
    if($pos_where>0){
        $sql_query = substr($sql_query,0,$pos_where).$sql_where." AND ".substr($sql_query,$pos_where);
    //or Insert search before GROUP BY
    }elseif($pos_groupby>0 AND $pos_join==0 AND $pos_where>0){
        $sql_query = substr($sql_query,0,$pos_groupby).$sql_where.substr($sql_query,$pos_groupby);
    //or before GROUP BY with WHERE
    }elseif($pos_groupby>0 AND $pos_join==0 AND $pos_where==0){
        $sql_query = substr($sql_query,0,$pos_groupby)." WHERE ".$sql_where.substr($sql_query,$pos_groupby);
    //or before GROUP BY with AND
    }elseif($pos_groupby>0 AND $pos_join>0){
        $sql_query = substr($sql_query,0,$pos_groupby)." AND ".$sql_where.substr($sql_query,$pos_groupby);
    //or at the end
    }else{
        $sql_query = $sql_query." WHERE ".$sql_where;
    }

    return $sql_query;

}

 // check whether input is a valid email address
function isEmailAddress($value) {
return
eregi('^([a-z0-9])+([.a-z0-9_-])*@([a-z0-9_-])+(.[a-z0-9_-]+)*.([a-z]{2,6})$', $value);
}

function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function WakeOnLan($hostname, $mac,$socket_number,$this_error) {

$address_bytes = explode(':', $mac);
//Convert mac address to string of six bytes.
$full_hw_addr = '';
for ($hw_address_bytes=0; $hw_address_bytes < 6; $hw_address_bytes++) $full_hw_addr .= chr(hexdec($address_bytes[$hw_address_bytes]));

$packet_header='';

// Create magic header of six &HFF bytes
for ($magic_bytes=0;$magic_bytes<6;$magic_bytes++){
$packet_header = $packet_header.CHR(255);
}

// Add 16 copies of mac address to magic header.
for ($mac_copies = 0; $mac_copies <= 16; $mac_copies++){
$packet_header = $packet_header.$full_hw_addr ;
}
//echo " Packet length = ". strlen($packet_header);
// Send it to the broadcast address using UDP

$create_magic_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
if ($create_magic_socket  == false)
{
$this_error =  "Error: Could not create a socket.";
$this_error = $this_error."-Error Reported ".socket_last_error($create_magic_socket)." ... " . socket_strerror(socket_last_error($create_magic_socket));
}
else
{
       $sock_data = socket_set_option($create_magic_socket, SOL_SOCKET, SO_BROADCAST, 1); //Set
{
$this_error = "Error: Could not broadcast to socket";
}
$broadcast = "255.255.255.255";
$this_connection = socket_sendto($create_magic_socket, $packet_header, strlen($packet_header), 0, $broadcast, $socket_number);
socket_close($create_magic_socket);
$this_error = "Success: Wake on LAN sent ".$this_connection ." bytes to ".$broadcast;
}
 return $this_error;
}

function isGUID($value) {
return
strlen($value) == '16';
}

/**********************************************************************************************************
Function Name:
	formatGUID
Description:
	Returns an character string representation of a binary GUID
Arguments:
	$ByteArray		[IN]	Binary GUID
Returns:
	[String]	GUID as a string
Change Log:
	20/08/2008			New function (replacing previous function of same name)	 [Nick Brown]
**********************************************************************************************************/
function formatGUID($ByteArray)
{
	$s = bin2hex(substr($ByteArray, 3, 1));
	$s .= bin2hex(substr($ByteArray, 2, 1));
	$s .= bin2hex(substr($ByteArray, 1, 1));
	$s .= bin2hex(substr($ByteArray, 0, 1));
	$s .= "-" ;
	$s .= bin2hex(substr($ByteArray, 5, 1));
	$s .= bin2hex(substr($ByteArray, 4, 1));
	$s .= "-";
	$s .= bin2hex(substr($ByteArray, 7, 1));
	$s .= bin2hex(substr($ByteArray, 6, 1));
	$s .= "-";
	$s .= bin2hex(substr($ByteArray, 8, 1));
	$s .= bin2hex(substr($ByteArray, 9, 1));
	$s .= "-";
	$s .= bin2hex(substr($ByteArray, 10, 1));
	$s .= bin2hex(substr($ByteArray, 11, 1));
	$s .= bin2hex(substr($ByteArray, 12, 1));
	$s .= bin2hex(substr($ByteArray, 13, 1));
	$s .= bin2hex(substr($ByteArray, 14, 1));
	$s .= bin2hex(substr($ByteArray, 15, 1));
  return $s;
}

function isSID($value) {
return
strpos( $value, "sid") <> 0 ;
}

function formatSID($value) {
$hex_string='S-';
for ($sid_bytes = 0; $sid_bytes<= strlen($value); $sid_bytes++){
$hex_string = $hex_string.bin2hex(substr($value,$sid_bytes, 1));
if (($sid_bytes == '0') or ($sid_bytes == '1') or ($sid_bytes == '3')or ($sid_bytes == '9')) {
$hex_string = $hex_string."-";
        }
    }
    return $hex_string;
}

/**********************************************************************************************************
Function Name:
	GetAesKey
Description:
	Creates a string to be used as an AES encryption key
	For Windows systems we get the serial number of the C: volume
	For Linux we get the UUID of the "first" disk
	If you're rubbish at regular expressions like me, these sites may help
	http://regexlib.com/RETester.aspx
	http://www.regular-expressions.info/tutorial.html/
Arguments:	None
Returns:
	[String]	Key
Change Log:
	16/03/2009			New function	[Nick Brown]
	23/03/2009			Added additional testing for OS type		[Nick Brown]
	05/05/2009			Now uses $TheApp	[Nick Brown]
	06/05/2009			Windows regex modified to handle internazionalization	[Nick Brown]
	12/05/2009			Better handling of failure to execute shell_exec()	[Nick Brown]
**********************************************************************************************************/
function GetAesKey()
{
	global $TheApp;
	
	$AesKey = "openaudit";
	$err_level = error_reporting(0); 
	switch ($TheApp->OS)
	{
		case "Windows":
			preg_match("/\b[0-9a-fA-F]{4}-[0-9a-fA-F]{4}\b/", shell_exec('vol c:'), $m);
			$AesKey = (strlen($m[0]) > 0) ? $m[0] : "openaudit";
			break;
		case "Linux":
			$shellout = shell_exec("ls /dev/disk/by-uuid");
			$list = preg_split("/[\s,]+/", trim($shellout));
			sort($list);
			$AesKey = (strlen($list[0]) > 0) ? $list[0] : "openaudit";
			break;
	}
	error_reporting($err_level); 
	return $AesKey;
}

/**********************************************************************************************************
Function Name:
	GetVolumeLabel
Description:
	Gets the volume label of the requested drive (Don't have linux solution for this yet)
Arguments:
	$drive		[IN]	[String]		Drive letter
Returns:
	[String]	volume label of drive
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function GetVolumeLabel($drive)
{
  // Try to grab the volume name - error is expected at the moment on Linux systems - supress error reporting
	$err_level = error_reporting(0); 
  if (preg_match('#Volume Serial Number[a-zA-Z]* is (.*)\n#i', shell_exec('vol '.$drive.':'), $m))
	{$volname = $m[1];}	else {$volname = 'openaudit';}
	error_reporting($err_level); 
	return $volname;
}

/**********************************************************************************************************
Function Name:
	LogEvent
Description:
	Logs Event to Logs table in DB
Arguments:
	$calling_module	[IN]	[String]			name of the module calling this function	
	$calling_function	[IN]	[String]			name of the function calling this function	
	$message			[IN]	[String]			Event log message
	$severity			[IN]	[INTEGER]		Severity value (1=low, 5=high)
Returns:
	None
Change Log:
	20/08/2008			New function	[Nick Brown]
	20/09/2008			Added additional arguments to the function [Nick Brown]
	13/10/2008			Renamed function [Nick Brown]
	02/03/2009			Now using GetOpenAuditDbConnection() to get DB conenction rather than global $db
**********************************************************************************************************/
function LogEvent($calling_module, $calling_function, $message, $severity=3)
{
	global $max_log_entries;
	
	// Set up SQL connection 
	$db = GetOpenAuditDbConnection();

	$timestamp = date("YmdHis");
	// Add new 	log entry
	$sql  = "INSERT INTO `log` (`log_timestamp`,`log_module`,`log_function`,`log_message`,`log_severity`) ";
	$sql .= "VALUES ('".$timestamp."','".$calling_module."','".$calling_function."','".$message."','".$severity."')";
	mysqli_query($db,$sql);

	// Purge old entries
	do
	{
		// Is log size greater than $max_log_entries?
		$count = (int) mysqli_result(mysqli_query($db,"SELECT COUNT(*) as cnt FROM `log`"),0);
		if($count > $max_log_entries) 
		{
			// Get the oldest log entry
			$log_id = mysqli_result(mysqli_query($db,"SELECT log_id, log_timestamp FROM `log` ORDER BY log_timestamp ASC LIMIT 1"),0);
			// Delete it
			mysqli_query($db,"DELETE FROM `log` WHERE log_id=".$log_id);
		}
	} while ($count > $max_log_entries);
	
	mysqli_close($db);
}

/**********************************************************************************************************
Function Name:
	GetOpenAuditDbConnection
Description:
	Authenticates and connects to the Open Audit database
Arguments: None
Returns: 		[resource]	MySQL link identifier 
Change Log:
	02/03/2009			New function	[Nick Brown]
	09/03/2009			Passing  value of TRUE for mysqli_connect "new_link"  argument
**********************************************************************************************************/
function GetOpenAuditDbConnection()
{
	static $sql_link = null;

	if ($sql_link === null) {
		global $mysqli_server, $mysqli_user, $mysqli_password, $mysqli_database;

		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		try {
			$sql_link = mysqli_connect($mysqli_server, $mysqli_user, $mysqli_password, $mysqli_database);
		} catch (mysqli_sql_exception $e) {
			error_log("DB connection failed: " . $e->getMessage());
			die("Datenbankverbindung fehlgeschlagen.");
		}
	}

	return $sql_link;
}

/**********************************************************************************************************
Function Name:
	GetPOSTOrDefaultValue
Description:
	Checks whether $_POST value is defined. If it is, returns the value. If not, returns $default
Arguments:
	$var			[IN] [string]	$_POST array key name
	$default		[IN] [String]	Default value to return if array value not set
Returns:	[String]	$_POST value or $default value
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function GetPOSTOrDefaultValue($var, $default)
{if (isset($_POST[$var])) return $_POST[$var]; else return $default;}

/**********************************************************************************************************
Function Name:
	GetGETOrDefaultValue
Description:
	Checks whether $_GET value is defined. If it is, returns the value. If not, returns $default
Arguments:
	$var			[IN] [string]	$_GET array key name
	$default		[IN] [String]	Default value to return if array value not set
Returns:	[String]	$_GET value or $default value
Change Log:
	26/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function GetGETOrDefaultValue($var, $default)
{if (isset($_GET[$var])) return $_GET[$var]; else return $default;}

/**********************************************************************************************************
Function Name:
	GetVarOrDefaultValue
Description:
	Checks whether $var is defined. If it is, returns it's value. If not, returns $default
Arguments:
	&$var		[IN] [string]	variable
	$default		[IN] [String]	Default value to return if variable not set
Returns:	[String]	$var value or $default value
Change Log:
	26/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function GetVarOrDefaultValue(&$var, $default="")
{if (isset($var)) return $var; else return $default;}

/**********************************************************************************************************
Function Name:
	ConnectToLdapServer
Description:
	Connects and authenticates to LDAP server
Arguments:
	ldap_server			[IN]	[STRING]	ldap server host name
	$ldap_user			[IN]	[STRING]	user name for authentication
	$ldap_password		[IN]	[STRING]	user password for authentication
Returns:
	LDAP link				[RESOURCE]  if succesful, or...
	LDAP error				[ARRAY]  if not.
Change Log:
	25/04/2008			New function	[Nick Brown]
	02/09/2008			Added error detection [Nick Brown]
	08/09/2008			Added anonymous bind support [Nick Brown]
**********************************************************************************************************/
function ConnectToLdapServer($ldap_server, $ldap_user, $ldap_password)
{
	$l = ldap_connect($ldap_server);
	ldap_set_option($l,LDAP_OPT_PROTOCOL_VERSION,3);
	ldap_set_option($l,LDAP_OPT_SIZELIMIT, 1000);
	ldap_set_option($l, LDAP_OPT_REFERRALS, 0);

	if ($ldap_user == NULL) ldap_bind($l); // Anonymous bind
	else ldap_bind($l,$ldap_user,$ldap_password); // Auth bind

	$errdata = array("number" => ldap_errno($l), "string" => ldap_error($l));
	if ($errdata["number"] !=0 ) return $errdata; else return $l;
}

/**********************************************************************************************************
Function Name:
	GetLdapConnectionsFromDb
Description:
	Get list of LDAP connections (domains)  from db and return in array
Arguments:
Returns:
	LDAP Connections				[ARRAY]  
Change Log:
	24/02/2009			New function	[Nick Brown]
	11/03/2009			Connection username and password now included in returned array	[Nick Brown]
	17/03/2009			Using GetAesKey() instead of GetVolumeLabel()	[Nick Brown]
	24/04/2009			Added "ldap_connections_use_ssl" value to returned array	[Nick Brown]
	19/08/2009			Added support for Open LDAP schema	[Nick Brown]
**********************************************************************************************************/
function GetLdapConnectionsFromDb()
{
	global $mysqli_server,$mysqli_user,$mysqli_password,$mysqli_database;
	
	$db=GetOpenAuditDbConnection();;
	mysqli_select_db($db,$mysqli_database);
	
	$aes_key = GetAesKey();
	
	$sql = "SELECT ldap_connections_id,AES_DECRYPT(ldap_connections_user,'".$aes_key."') AS ldap_user, 
					AES_DECRYPT(ldap_connections_password,'".$aes_key."') AS ldap_password, ldap_connections_use_ssl,
					ldap_connections_server, ldap_connections_fqdn, ldap_connections_name, ldap_connections_nc, 
					ldap_connections_schema 
					FROM ldap_connections ";
	
	$result = mysqli_query($db,$sql);
	if ($myrow = mysqli_fetch_array($result))
	{
		$ldap_connections = array();
		do
		{
			$id = $myrow["ldap_connections_id"];
			$ldap_connections[$id] = Array();
			$ldap_connections[$id]["id"] = $myrow["ldap_connections_id"];
			$ldap_connections[$id]["server"] = $myrow["ldap_connections_server"];
			$ldap_connections[$id]["user"] = $myrow["ldap_user"];
			$ldap_connections[$id]["password"] = $myrow["ldap_password"];
			$ldap_connections[$id]["use_ssl"] = $myrow["ldap_connections_use_ssl"];
			$ldap_connections[$id]["name"] = $myrow["ldap_connections_name"];
			$ldap_connections[$id]["fqdn"] = $myrow["ldap_connections_fqdn"];
			$ldap_connections[$id]["nc"] = $myrow["ldap_connections_nc"];
			$ldap_connections[$id]["schema"] = $myrow["ldap_connections_schema"];
		}
		while ($myrow = mysqli_fetch_array($result));
	}
	mysqli_close($db);
	return $ldap_connections;
}

/**********************************************************************************************************
Function Name:
	RedirectToUrl
Description:
	Sends a header back to the browser to redirect to the supplied URL
Arguments:
	$url		[STRING]	Redirect URL
Returns:	None
Change Log:
	24/02/2009			New function	[Nick Brown]
**********************************************************************************************************/
function RedirectToUrl($url)
{
	header('Location: '.$url);
	exit;
}

/**********************************************************************************************************
Function Name:
	ConnectToOpenAuditDb
Description:
	Opens connection to Open Audit MySql database
Arguments:	None
Returns:
	MySql link id	[RESOURCE]
Change Log:
	03/04/2009			New function	[Nick Brown]
**********************************************************************************************************/
function ConnectToOpenAuditDb()
{
	global $mysqli_server, $mysqli_user, $mysqli_password, $mysqli_database;
	$db=GetOpenAuditDbConnection();;
	mysqli_select_db($db,$mysqli_database);
	return $db;
}

/**********************************************************************************************************
Function Name:
	ConvertBinarySidToSddl
Description:
	Takes a SID as returned from LDAP - binary SID stored as a string - and converts to a SDDL string
	See http://blogs.msdn.com/oldnewthing/archive/2004/03/15/89753.aspx
Arguments:
	$binary_sid		[STRING] 	Binary SID (as a string)
Returns:
	SDDL				[STRING]  
Change Log:
	24/02/2009			New function	[Nick Brown]
	03/04/2009			Moved from "include_ldap_login_functions.php" to "include_functions.php"
**********************************************************************************************************/
function ConvertBinarySidToSddl(&$binary_sid)
{
	// Convert string to an array
	$sid = array();
	$binary_sid = bin2hex($binary_sid);
	for ($i = 0; $i < strlen($binary_sid); $i = $i + 2) {$sid[] = $binary_sid[$i].$binary_sid[$i+1];}

	$sid_revision = hexdec($sid[0]);
	$num_authorities = hexdec($sid[1]);
	$nt_authority = hexdec($sid[2].$sid[3].$sid[4].$sid[5].$sid[6].$sid[7]);
	$delegate_auths = array();
	
	// Get delegate authorities
	for($i=0; $i<$num_authorities; $i++)
	{
		$j = ($i * 4) + 7;
		$delegate_auths[$i] = strval(hexdec($sid[$j+4].$sid[$j+3].$sid[$j+2].$sid[$j+1]));
	}
	$delegate_auths_string = implode("-", $delegate_auths);
	$sddl = "S-".$sid_revision."-".$nt_authority."-".$delegate_auths_string;
	
	return $sddl;
}

/**********************************************************************************************************
Function Name:
	DisplayError
Description:
	Displays an error message, includes right column code code to complete HTML output and quits
Arguments:
	$error_msg		[STRING] 	Error message to be displayed
Returns:	None
Change Log:
	17/04/2009			New function	[Nick Brown]
**********************************************************************************************************/
function DisplayError($error_msg)
{
	global $show_tips;
	
	echo "<td><div class='error'><img src='images/emblem_important.png'/>";
	echo $error_msg."</div></td>";
	
	die;
}
function base36_convert($numstring)
{
   $result = base_convert($numstring,36,10);
    return $result;
}

/**********************************************************************************************************
Function Name:
	GetSmtpConnectionFromDb
Description:
	Get SMTP connection from db and return as an array
Arguments:
Returns:
	SMTP Connection				[ARRAY]  
Change Log:
	14/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function GetSmtpConnectionFromDb(){
  global $mysqli_server,$mysqli_user,$mysqli_password,$mysqli_database;

  $db=GetOpenAuditDbConnection();;
  mysqli_select_db($db,$mysqli_database);

  $aes_key = GetAesKey();

  $sql = "SELECT AES_DECRYPT(smtp_connection_user,'$aes_key') AS smtp_user,
                 AES_DECRYPT(smtp_connection_password,'$aes_key') AS smtp_password,
                 smtp_connection_use_ssl, smtp_connection_server, smtp_connection_start_tls,
                 smtp_connection_security, smtp_connection_from, smtp_connection_auth,
                 smtp_connection_port, smtp_connection_realm  
          FROM smtp_connection LIMIT 1";

  $result = mysqli_query($db,$sql);
  if ($myrow = mysqli_fetch_array($result)){
    $smtp = array();

    $smtp["host"]           = $myrow["smtp_connection_server"];
    $smtp["port"]           = $myrow["smtp_connection_port"];
    $smtp["user"]           = $myrow["smtp_user"];
    $smtp["password"]       = $myrow["smtp_password"];
    $smtp["from"]           = $myrow["smtp_connection_from"];
    $smtp["use_ssl"]        = $myrow["smtp_connection_use_ssl"];
    $smtp["realm"]          = $myrow["smtp_connection_realm"];
    $smtp["security"]       = $myrow["smtp_connection_security"];
    $smtp["start_tls"]      = $myrow["smtp_connection_start_tls"];
    $smtp["authentication"] = $myrow["smtp_connection_auth"];
  }
  else {
    $smtp = null;
  }
  mysqli_close();

  return $smtp;
}

/**********************************************************************************************************
Function Name:
  GetAuditSettingsFromDb
Description:
  Get the general settings for the web-schedule service from the database.
Arguments:
Returns:
  Web-schedule settings from the DB, or null [ARRAY]  
Change Log:
  04/12/2009 New function [Chad Sikorra]
**********************************************************************************************************/
function GetAuditSettingsFromDb(){
  global $mysqli_server,$mysqli_user,$mysqli_password,$mysqli_database;

  $db=GetOpenAuditDbConnection();;
  mysqli_select_db($db,$mysqli_database);

  $sql = "SELECT * FROM audit_settings LIMIT 1";

  $result = mysqli_query($db,$sql);
  if ($myrow = mysqli_fetch_array($result)){
    $audit = array();

    $audit["service_name"]    = $myrow["audit_settings_service_name"];
    $audit["service_enabled"] = $myrow["audit_settings_runas_service"];
    $audit["poll_interval"]   = $myrow["audit_settings_interval"];
    $audit["script_only"]     = $myrow["audit_settings_script_only"];
    $audit["base_url"]        = $myrow["audit_settings_base_url"];
    $audit["active"]          = $myrow["audit_settings_active"];
    $audit["pid"]             = $myrow["audit_settings_pid"];
  }
  else {
    $audit = null;
  }
  mysqli_close();

  return $audit;
}

/**********************************************************************************************************
Function Name:
  GetUrlPath
Description:
  Get the base URL for where Open-AudIT is. It can be used to craft email URLS and is also passed
  to the audit.pl script to execute a php page on the server to check for/send emails
Arguments: 
  None
Returns:
  URL path, minus the currently executing script [STRING]  
Change Log:
  05/12/2009 New function [Chad Sikorra]
**********************************************************************************************************/
function GetUrlPath(){
   $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
   $host      = $_SERVER['SERVER_NAME'];
   // append the port number if it isn't a standard one
   $port      = ( $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 ) ?
                ':'.$_SERVER['SERVER_PORT'] : '';
   // account for PHP_SELF returning only a slash if OA is installed at the root
   $directory = ( preg_match("/\/$/",dirname($_SERVER['PHP_SELF'])) ) ?
                dirname($_SERVER['PHP_SELF']) :
                dirname($_SERVER['PHP_SELF']) . '/';

   $url = $protocol . '://' . $host . $port . $directory;

   return $url;
}

/**********************************************************************************************************
Function Name:
  GetAuditSchedulesFromDb
Description:
  Get the all audit schedules from the database.
Arguments: None
Returns:
  Audit schedule settings from the DB, or null [ARRAY]  
Change Log:
  04/12/2009 New function [Chad Sikorra]
**********************************************************************************************************/
function GetAuditSchedulesFromDb(){
  global $mysqli_server,$mysqli_user,$mysqli_password,$mysqli_database;

  $db=GetOpenAuditDbConnection();;
  mysqli_select_db($db,$mysqli_database);

  $sql = "SELECT * FROM audit_schedules";

  $result = mysqli_query($db,$sql);
  if ($myrow = mysqli_fetch_array($result))
  {
    $cfg = array();
    do
    {
      $id = $myrow["audit_schd_id"];
      $cfg[$id] = Array();
      $cfg[$id]["name"]            = $myrow["audit_schd_name"];
      $cfg[$id]["active"]          = $myrow["audit_schd_active"];
      $cfg[$id]["updated"]         = $myrow["audit_schd_updated"];
      $cfg[$id]["last_run"]        = $myrow["audit_schd_last_run"];
      $cfg[$id]["next_run"]        = $myrow["audit_schd_next_run"];
      $cfg[$id]["log_disabled"]    = $myrow["audit_schd_log_disable"];
      $cfg[$id]["email_subject"]   = $myrow["audit_schd_email_subject"];
      $cfg[$id]["email_replyto"]   = $myrow["audit_schd_email_replyto"];
      $cfg[$id]["email_template"]  = $myrow["audit_schd_email_template"];
      $cfg[$id]["email_list"]      = $myrow["audit_schd_email_list"];
      $cfg[$id]["email_logo"]      = $myrow["audit_schd_email_logo"];
      $cfg[$id]["email_log"]       = $myrow["audit_schd_email_log"];
      $cfg[$id]["config_id"]       = $myrow["audit_schd_cfg_id"];
      $cfg[$id]["type"]            = $myrow['audit_schd_type'];
      $cfg[$id]["minute"]          = $myrow['audit_schd_strt_min'];
      $cfg[$id]["hour"]            = $myrow['audit_schd_strt_hr'];
      $cfg[$id]["week_days"]       = $myrow['audit_schd_wk_days'];
      $cfg[$id]["months"]          = $myrow['audit_schd_mth_months'];
      $cfg[$id]["hour_frequency"]  = $myrow['audit_schd_hr_frq_hr'];
      $cfg[$id]["hour_start"]      = $myrow['audit_schd_hr_strt_hr'];
      $cfg[$id]["hour_end"]        = $myrow['audit_schd_hr_end_hr'];
      $cfg[$id]["daily_frequency"] = $myrow['audit_schd_dly_frq'];
      $cfg[$id]["between_hours"]   = $myrow['audit_schd_hr_between'];
      $cfg[$id]["minute_frequency"]= $myrow['audit_schd_hr_frq_min' ];
      $cfg[$id]["minute_start"]    = $myrow['audit_schd_hr_strt_min'];
      $cfg[$id]["month_day"]       = $myrow['audit_schd_mth_day'];

      # Some exceptions...
      $min_start = ( $cfg[$id]["type"] == 'hourly' && $cfg[$id]["between_hours"]) ?
        $cfg[$id]['minute_start'] : $cfg[$id]['minute'];
      $min_start = ( $cfg[$id]["type"] == 'hourly' && !$cfg[$id]["between_hours"]) ?
        $cfg[$id]['minute_frequency'] : $min_start;
      $hours     = ( $cfg[$id]["between_hours"] ) ?
        "{$cfg[$id]["hour_start"]}-{$cfg[$id]["hour_end"]}" : "*";

      switch($cfg[$id]["type"]){
        case "weekly": $cfg[$id]["cron_line"]="{$cfg[$id]["minute"]} {$cfg[$id]["hour"]} * * {$cfg[$id]["week_days"]}"; break;
        case "hourly": $cfg[$id]["cron_line"]="$min_start $hours/{$cfg[$id]["hour_frequency"]} * * *"; break;
        case "daily":  $cfg[$id]["cron_line"]="{$cfg[$id]["minute"]} {$cfg[$id]["hour"]} */{$cfg[$id]["daily_frequency"]} * *";break;
        case "monthly":$cfg[$id]["cron_line"]="{$cfg[$id]["minute"]} {$cfg[$id]["hour"]} {$cfg[$id]["month_day"]} {$cfg[$id]["months"]} *";break;
        case "crontab":$cfg[$id]["cron_line"]=$myrow["audit_schd_cron_line"];break;
      }
    }
    while ($myrow = mysqli_fetch_array($result));
  }
  else
  {
    $cfg = null;
  }
  mysqli_close();

  return $cfg;
}

/**********************************************************************************************************
Function Name:
  GetAuditConfigurationsFromDb
Description:
  Get the all audit configurations from the database.
Arguments: None
Returns:
  Audit configuration settings from the DB, or null [ARRAY]  
Change Log:
  04/12/2009 New function [Chad Sikorra]
**********************************************************************************************************/
function GetAuditConfigurationsFromDb(){
  global $mysqli_server,$mysqli_user,$mysqli_password,$mysqli_database;
  $aes_key = GetAesKey();

  $db=GetOpenAuditDbConnection();;
  mysqli_select_db($db,$mysqli_database);

  $sql = "SELECT * FROM audit_configurations";

  $result = mysqli_query($db,$sql);
  if ($myrow = mysqli_fetch_array($result))
  {
    $config = array();
    do
    {
      $id = $myrow["audit_cfg_id"];

      $sql = "SELECT AES_DECRYPT(audit_cfg_ldap_user,'".$aes_key."') AS ldap_user, 
                     AES_DECRYPT(audit_cfg_ldap_pass,'".$aes_key."') AS ldap_password,
                     AES_DECRYPT(audit_cfg_audit_user,'".$aes_key."') AS audit_user,
                     AES_DECRYPT(audit_cfg_audit_pass,'".$aes_key."') AS audit_password
              FROM audit_configurations WHERE audit_cfg_id='$id'";

      $r_cred = mysqli_query($db,$sql);
      $cred   = mysqli_fetch_array($r_cred);

      $config[$id] = Array();
      $config[$id]["name"]                = $myrow["audit_cfg_name"];
      $config[$id]["action"]              = $myrow["audit_cfg_action"];
      $config[$id]["type"]                = $myrow["audit_cfg_type"];
      $config[$id]["os"]                  = $myrow["audit_cfg_os"];
      $config[$id]["max_audits"]          = $myrow["audit_cfg_max_audits"];
      $config[$id]["wait_time"]           = $myrow["audit_cfg_wait_time"];
      $config[$id]["windows_uuid"]        = $myrow['audit_cfg_win_uuid'];
      $config[$id]["windows_url"]         = $myrow['audit_cfg_win_url'];
      $config[$id]["windows_software"]    = $myrow['audit_cfg_win_sft'];
      $config[$id]["vbs_path"]            = $myrow['audit_cfg_win_vbs'];
      $config[$id]["linux_url"]           = $myrow['audit_cfg_lin_url'];
      $config[$id]["linux_software"]      = $myrow['audit_cfg_lin_sft'];
      $config[$id]["linux_software_list"] = $myrow['audit_cfg_sft_lst'];
      $config[$id]["software_list_only"]  = $myrow['audit_cfg_lin_sft_lst'];
      $config[$id]["enable_logging"]      = $myrow['audit_cfg_log_enable'];
      $config[$id]["command_list"]        = $myrow['audit_cfg_command_list'];
      $config[$id]["pc_list"]             = $myrow['audit_cfg_pc_list'];
      $config[$id]["command_ids"]         = $myrow['audit_cfg_cmd_list'];
      $config[$id]["command_interact"]    = $myrow['audit_cfg_command_interact'];
      $config[$id]["local_user"]          = $myrow['audit_cfg_audit_local'];
      $config[$id]["ip_start"]            = $myrow['audit_cfg_ip_start'];
      $config[$id]["ip_end"]              = $myrow['audit_cfg_ip_end'];
      $config[$id]["filter"]              = $myrow['audit_cfg_filter'];
      $config[$id]["filter_case"]         = $myrow['audit_cfg_filter_case'];
      $config[$id]["filter_inverse"]      = $myrow['audit_cfg_filter_inverse'];
      $config[$id]["audit_use_conn"]      = $myrow['audit_cfg_audit_use_conn'];
      $config[$id]["audit_conn"]          = $myrow['audit_cfg_audit_conn'];
      $config[$id]["ldap_use_conn"]       = $myrow['audit_cfg_ldap_use_conn'];
      $config[$id]["ldap_path"]           = $myrow['audit_cfg_ldap_path'];
      $config[$id]["ldap_page"]           = $myrow['audit_cfg_ldap_page'];
      $config[$id]["ldap_server"]         = $myrow['audit_cfg_ldap_server'];
      $config[$id]["ldap_conn"]           = $myrow['audit_cfg_ldap_conn'];
      $config[$id]["remote_command_path"] = $myrow['audit_cfg_com_path'];
      $config[$id]["nmap_path"]           = $myrow['audit_cfg_nmap_path'];
      $config[$id]["mysqli_ids"]           = $myrow['audit_cfg_mysqli_ids'];
      $config[$id]["nmap_url"]            = $myrow['audit_cfg_nmap_url'];
      $config[$id]["nmap_intensity"]      = $myrow['audit_cfg_nmap_int'];
      $config[$id]["nmap_tcp_scan"]       = $myrow['audit_cfg_nmap_tcp_syn'];
      $config[$id]["nmap_udp_scan"]       = $myrow['audit_cfg_nmap_udp'];
      $config[$id]["nmap_detect_service"] = $myrow['audit_cfg_nmap_srv'];
      $config[$id]["audit_user"]          = $cred['audit_user'];
      $config[$id]["audit_pass"]          = $cred['audit_password'];
      $config[$id]["ldap_user"]           = $cred['ldap_user'];
      $config[$id]["ldap_pass"]           = $cred['ldap_password'];
    }
    while ($myrow = mysqli_fetch_array($result));
  }
  else
  {
    $config = null;
  }
  mysqli_close();

  return $config;
}

?>
