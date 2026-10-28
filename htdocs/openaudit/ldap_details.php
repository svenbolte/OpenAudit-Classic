<?php
/**********************************************************************************************************
Module:	ldap_details.php

Description:
	This module displays the user or computer LDAP details - linked to from the system summary page. The following form 
	variables are expected to be supplied to the page:
	$_GET("record_type") - user or computer
	$_GET("full_details") - Y or N whether full or partial LDAP details are returned
	$_GET("uuid") - Open Audit ID of the system
	
Change Control:

	[Nick Brown]	03/04/2009
	Re-wrote module from scratch

	[Nick Brown]	17/04/2009
	Minor change to GetImage(). Added support for $image_link_ldap_attribute and $human_readable_ldap_fields config
	options. Now using DisplayError() from "include_functions.php".
	
	[Nick Brown]	24/04/2009
	Added utf8_encode() to LDAP search filter strings

	[Chad Sikorra]	14/10/2009
	Format various date/time fields to be readable. Added FormatWindowsTimestamp and FormatAdObjectTime.
	
**********************************************************************************************************/
require_once "include.php";

$ldap_info = GetLdapConnection();

// Didn't get LDAP connection -  alert user & done
if ($ldap_info === False)
	{DisplayError(__("Cannot retrieve LDAP details as you have no LDAP connection defined for this domain."));}

// Connect (authenticate) to LDAP
$upn = isEmailAddress($ldap_info['user']) ? $ldap_info['user'] : $ldap_info['user']."@".$ldap_info['fqdn'];
$ldap = ConnectToLdapServer($ldap_info['server'],$upn,$ldap_info['password']);

// Get LDAP info
if($_GET["record_type"] == "computer")
{
	$sam_account_name = $ldap_info['system_name']."$";
	$attributes = ($_GET["full_details"] == "y") ? Array() : $computer_ldap_attributes;
}
else
{
	// Get user account name - user name *may* be in DOMAIN\ACCOUNT format or may not :-)
	$sam_account_name =(stripos($ldap_info["net_user_name"],"\\") !== FALSE) ? array_pop(explode("\\",$ldap_info["net_user_name"])) : $ldap_info["net_user_name"];;
	$attributes = ($_GET["full_details"] == "y") ? Array() : $user_ldap_attributes;
}
$filter = "(&(objectClass=".$_GET["record_type"].")(sAMAccountName=".$sam_account_name."))";
$sr = ldap_search($ldap, $ldap_info['nc'], utf8_encode($filter), $attributes);
$info = ldap_get_entries($ldap, $sr);

// Couldn't retrieve user or computer object from LDAP - alert user & done
if ($info == NULL)
	{DisplayError(__("Cannot retrieve LDAP details. The ").$_GET["record_type"].__(" object cannot be found in the LDAP source - ").$ldap_info["name"]);}

// ObjectSid is binary - need to use ldap_get_values_len() to ensure that it's correctly retrieved - only needed if retrieving full attributes
if ($_GET["full_details"] == "y")
{
	$entry = ldap_first_entry($ldap, $sr);
	$objectsid = ldap_get_values_len($ldap, $entry, "objectsid");
	$info[0]["objectsid"][0] = $objectsid[0];
}
// Sort by keys
ksort($info[0]);
?>

<!-- LDAP details header -->
<td>
<div class='ldap_details'>
<div>
<i class="fa fa-user" aria-hidden="true"></i>
<?php 
echo ($_GET["full_details"] == "y" ? __("Full") : __("Partial")) ;
echo __(' LDAP details for '). $info[0][$image_link_ldap_attribute][0]." [".$ldap_info["name"]."]";
?>
	<hr />
</div>
<!-- LDAP details table -->
<table class=\"tftable\" >
<tr><th><?php echo __("Attribute");?></th><th><?php echo __("Value");?></th></tr>

<?php
// Dump LDAP data into table
foreach ($info[0] as $key => $value)
{
	if(!is_numeric($key) && ($key != "count") && ($key != "dn")) 
	{
		array_shift($value);
		$val = FormatLdapValue($key, $value);
		echo "<tr class='".alternate_tr_class($tr_class)."'>";
		$key = $human_readable_ldap_fields ? __($key) : $key;
		echo "<td>".$key."</td><td>$val</td></tr>";
	}
}
echo "</table></div></td>";


/**********************************************************************************************************
Function Name:
	GetImage
Description:
	Returns image file name - image to be displayed with the LDAP details. If image file exists that matches user account name,
	then this filname is returned else default user or computer account image filenames are used. 
Arguments:
	$name	[IN]	[STRING]	user or computer samaccountname value (or user full name [AJH]) 
Returns:
	Image file name/path	[STRING]
Change Log:
	06/04/2009			New function	[Nick Brown]
    15/04/2009          Updated. Made things a bit more defensive, added a default_file$ image.
                        Fixed up a PHP bug which could claim the image file existed even if it didn't.
                        Also defined exactly what the "record_type" was from $_GET as it assumed we
                        were assuming if not user, must be computer, however I managed to break this ;¬) 
                        Image for user is now back to "Full Name.jpg" was "firstame.jpg" but wont work for 
                        most users.  [AJH]
	17/04/2009			Minor change. Removed a few lines of redundant code.	[Nick Brown]
**********************************************************************************************************/
function GetImage($name) { return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23777' d='M4 4h16v16H4z'/%3E%3C/svg%3E"; }

/**********************************************************************************************************
Function Name:
	FormatLdapValue
Description:
	Applies formatting to specific LDAP values (or types of values)
Arguments:
	$name	[IN]	[STRING]		LDAP attribute name
	$value	[IN]	[VARIANT]		LDAP attribute value	
Returns:
	Formatted LDAP value string	[STRING]
Change Log:
	03/04/2009			New function	[Nick Brown]
**********************************************************************************************************/
function FormatLdapValue(&$name, &$value)
{
	$known_binary_fields = Array("ciscoecsbuumlocationobjectid","msexchmailboxsecuritydescriptor","msexchrecordedname",
		"sidhistory","userparameters","logonhours","replicationsignature");
	$win_ts_fields = Array('accountexpires','lastlogon','lastlogoff','pwdlast','badpasswordtime','lastlogontimestamp','pwdlastset');
	if (preg_grep("/^$name$/",$known_binary_fields)) {return "[Binary Data]";}
	if (preg_match("/guid$/i", $name)) {return formatGUID($value[0]);}
	if (preg_match("/sid$/i", $name)) {return ConvertBinarySidToSddl($value[0]);}
	if (count($value)>1) {return "<ul><li>".implode("</li><li>",$value)."</li></ul>";}
	// Per MSDN, if accountexpires equals 9223372036854775807 or 0, it never expires 
	if ($name=='accountexpires' && ($value[0]==0 || $value[0]==9223372036854775807)){return "Never Expires";}
	if (preg_grep("/^$name$/",$win_ts_fields)) {return FormatWindowsTimestamp($value[0]);}
	if ($name=='whenchanged' || $name=='whencreated') {return FormatAdObjectTime($value[0]);}
	return $value[0];
}

/**********************************************************************************************************
Function Name:
	GetLdapConnection
Description:
	Determine if we have an LDAP connection defined for the user or computer domain and return connection details
Arguments: None
Returns:
	LDAP connection details		[ARRAY]
Change Log:
	03/04/2009			New function	[Nick Brown]
**********************************************************************************************************/
function GetLdapConnection()
{
	$db = ConnectToOpenAuditDb();

	// Get domain that we need to connect to - user and computer may be different domains
	$sql = "SELECT system_name, net_domain, net_user_name FROM system WHERE system_uuid = '".$_GET["uuid"]."'";
	$result = mysqli_query($db,$sql);
	$system = mysqli_fetch_array($result);
	// Get user domain - user name *may* be in DOMAIN\ACCOUNT format or may not :-)
	if ($_GET["record_type"] == "user")
	{
		$domain =(stripos($system["net_user_name"],"\\") !== FALSE) ? array_shift(explode("\\",$system["net_user_name"])) : $system["net_domain"];;
	}
	else {$domain = $system["net_domain"];}
	
	// Now get ldap connection info for that domain, if any ...
	$aeskey = GetAesKey();	
	$sql = "SELECT ldap_connections_server as server, ldap_connections_nc as nc, 
					ldap_connections_fqdn  as fqdn, ldap_connections_name as name, 
					AES_DECRYPT(`ldap_connections_user`,'".$aeskey."') as user, 
					AES_DECRYPT(`ldap_connections_password`,'".$aeskey."') as password 
					FROM ldap_connections
					WHERE ldap_connections_fqdn = '$domain' OR ldap_connections_name = '$domain'";			
	$result = mysqli_query($db,$sql);
	$ldap_info = (($ldap = mysqli_fetch_array($result)) === FALSE) ? FALSE : array_merge($system, $ldap);
	mysqli_close($db);
	
	return $ldap_info;
}

/**********************************************************************************************************
Function Name:
	FormatWindowsTimestamp
Description:
	Convert an Windows timestamp to a Unix timestamp, format it, and return a readable date
	Method gleaned from a post at: http://php.net/manual/en/function.ldap-get-entries.php
Arguments:
	$win_ts	[IN]	[INTEGER]		Windows timestamp - nanoseconds since January 1st, 1601.
Returns:
	A readable date formatted by date()		[String]
Change Log:
	14/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function FormatWindowsTimestamp($win_ts)
{
	if($win_ts==0){return "Unknown";}
	$win_secs = substr($win_ts,0,strlen($win_ts)-7); // divide by 10,000,000 to get seconds
	$ts       = ($win_secs - 11644473600);           // 1.1.1601 -> 1.1.1970 : difference in seconds

	return date("D M j Y, g:i:s A", $ts);
}

/**********************************************************************************************************
Function Name:
	FormatAdObjectTime
Description:
	Format the time for an AD object creation/modification time property
	It is in the format of: YYYYMMDDHHIISS.TZ (ISO 8601 Format)
Arguments:
	$ts	[IN]	[STRING]		Time for the AD object, in the above format
Returns:
	A readable date formatted by date()		[String]
Change Log:
	14/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function FormatAdObjectTime($ts)
{
	$time = gmmktime(substr($ts,8,2),substr($ts,10,2),substr($ts,12,2),substr($ts,4,2),substr($ts,6,2),substr($ts,0,4));
	return date("D M j Y, g:i:s A", $time);
}

?>
?>
