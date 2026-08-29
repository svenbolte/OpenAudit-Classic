<?php
/**********************************************************************************************************
Module:	ad_audit_script.php
	
Description:
	Script to perform audit of LDAP sources - It is the *directory* being audited, not the computers

Change Control:

	[Nick Brown]	25/04/2008
	The PHP LDAP extension doesn't appear to support the LDAP pagedResultsControl control 
	(see RFC 2696 http://www.ietf.org/rfc/rfc2696) 
	which means that it's difficult to accurately pull data from larger AD systems 
	- because AD returns paged results with a page size of 1000 objects.
	There appears to be a resolution to this in OpenLDAP, but it doesn't appear to have filtered it's way through to the PHP 
	distributions:
	http://qaix.com/php-web-programming/412-755-php-dev-ldap-module-patch-adding-new-functionality-read.shtml
	http://64.233.183.104/search?q=cache:eSY4ZDytGL4J:moodle.org/mod/forum/discuss.php%3Fd%3D28791+ldap_parse_result+moodle&hl=en&ct=clnk&cd=1&gl=uk

	The relevant functions don't appear to be supported, which are:
	ldap_parse_result() - with additonal serverctrls argument
	ldap_ber_printf() 
	ldap_ber_scanf

	[Nick Brown]	17/03/2009
	AuditLdapPaths() modified to use GetAesKey() 

**********************************************************************************************************/
include "include_config.php";
include "include_lang.php";
include "include_functions.php";


// May need to ask admin to define AD environment size to ensure correct memory setting ?
// $ad_sizing=1; /* Up to 100 accounts - 8MB */
// $ad_sizing=2; /* Up to 1000 accounts - 16MB*/
//$ad_sizing=3; /* Up to 10,000 accounts - 24MB */
ini_set("memory_limit","24M");

define("LDAP_USER_FILTER","(samaccounttype=805306368)");
define("LDAP_COMPUTER_FILTER","(samaccounttype=805306369)");
$debugging=FALSE;
$err=FALSE;
error_reporting(0);

// Set up SQL connection 
$db=GetOpenAuditDbConnection();;
DebugEcho($db);
if ($db == FALSE)
{
	echo "Error: " . mysqli_errno() . ": " . mysqli_error($db);
	exit("MySQL login failed");
}
mysqli_select_db($db,$mysqli_database);
if ($db == FALSE)
{
	echo "Error: " . mysqli_errno($db) . ": " . mysqli_error($db);
	exit("Failed to connect to Open Audit database, $mysqli_database");
}

AuditLdapPaths();

// Close SQL connection
mysqli_close($db);

/**********************************************************************************************************
Function Name:
	AuditLdapPaths
Description:
	Audits each path in ldap_paths table by calling AuditSingleLdapPath()
Arguments: 	None
Returns:	None
Change Log:
	28/04/2008			New function	[Nick Brown]
	17/03/2009			Using GetAesKey() instead of GetVolumeLabel()	[Nick Brown]
**********************************************************************************************************/
function AuditLdapPaths()
{
	global $db;
	$aes_key = GetAesKey();
	$ldap_details=array();
	
	// Get paths info from db
	$sql =  "SELECT ldap_connections_server,AES_DECRYPT(ldap_connections_user,'".$aes_key."') AS ldap_user, AES_DECRYPT(ldap_connections_password,'".$aes_key."') AS ldap_password, ldap_connections_fqdn, ldap_paths_id, ldap_paths_dn ";
	$sql .= "FROM ldap_connections INNER JOIN ldap_paths on ldap_paths.ldap_paths_connection_id=ldap_connections.ldap_connections_id ";
	$sql .= "WHERE ldap_paths.ldap_paths_audit=1";
	$result = mysqli_query($db,$sql);
	if ($myrow = mysqli_fetch_array($result))
	{
		DebugEcho($myrow);
		// Loop thru all defined paths and audit
		do
		{
			$ldap_path_details["ldap_path_id"]=$myrow["ldap_paths_id"];
			$ldap_path_details["ldap_server"]=$myrow["ldap_connections_server"];
			// if ldap_user is not stored in UPN format, append DNS suffix to user name to make UPN
			if(isEmailAddress($myrow["ldap_user"])){$ldap_path_details["ldap_user"]=$myrow["ldap_user"];}
			else {$ldap_path_details["ldap_user"]=$myrow["ldap_user"]."@".$myrow["ldap_connections_fqdn"];}
			$ldap_path_details["ldap_password"]=$myrow["ldap_password"];
			$ldap_path_details["ldap_base_dn"]=$myrow["ldap_paths_dn"];
			
			// Got details - now audit this path
			AuditSingleLdapPath($ldap_path_details);
		}	while ($myrow = mysqli_fetch_array($result));
	}
}

/**********************************************************************************************************
Function Name:
	AuditSingleLdapPath
Description:
	Audits the LDAP path by calling SearchLdap() using the info provided by $ldap_path_details
Arguments:
	&$ldap_path_details	[IN] [Array]	LDAP path connection details
Returns:	None
Change Log:
	28/04/2008			New function	[Nick Brown]
	02/09/2008			Added error detection on call to ConnectToLdapServer [Nick Brown]
**********************************************************************************************************/
function AuditSingleLdapPath(&$ldap_path_details)
{
	global $db;
	
	DebugEcho($ldap_path_details);
	LogEvent("ldap_audit_script.php","AuditSingleLdapPath",$ldap_path_details["ldap_base_dn"]);
	echo "Auditing LDAP Path: ".$ldap_path_details["ldap_base_dn"]."<br>\n";
		
	// Authenticate
	$ldap = ConnectToLdapServer($ldap_path_details["ldap_server"], $ldap_path_details["ldap_user"], $ldap_path_details["ldap_password"]);
	if (is_array($ldap))
	{
		DebugEcho("AuditSingleLdapPath: ".$ldap_path_details["ldap_base_dn"]." : Failed to connect to server");
		LogEvent("ldap_audit_script.php","AuditSingleLdapPath",$ldap_path_details["ldap_base_dn"]." : Failed to connect to server");
		return;
	}
	
	$audit_timestamp=date("YmdHis");
	DebugEcho($audit_timestamp);
	
	// Perform user object search and get results
	echo "Auditing user accounts in: ".$ldap_path_details["ldap_base_dn"]."<br>\n";
	$ldap_filter=LDAP_USER_FILTER;
	$ldap_attributes=array("distinguisedname","cn","usnchanged","objectguid","description","department");
//	$ldap_attributes=array("cn,sn,c,l,st,title,postalcode,physicaldeliveryofficename,telephonenumber,givenname,distinguishedname,instancetype,whencreated,whenchanged,displayname,usncreated,usnchanged,co,department,company,streetaddress,name,objectguid,useraccountcontrol,badpwdcount,codepage,countrycode,badpasswordtime,lastlogoff,lastlogon,scriptpath,pwdlastset,primarygroupid,objectsid,accountexpires,logoncount,samaccountname,samaccounttype,userprincipalname,lockouttime,objectcategory,dscorepropagationdata,dscorepropagationdata,dscorepropagationdata,lastlogontimestamp,mail,manager");    
	$ldap_results=SearchLdap($ldap,$ldap_path_details["ldap_base_dn"],$ldap_filter,$ldap_attributes);
	// Update db, ldap_users table
	echo "Updating Users table ...<br>\n";
	Updateldap_usersTable($ldap_results, $ldap_path_details["ldap_path_id"], $audit_timestamp);
	DebugEcho("Total: ".$ldap_results["count"]);
	
	// Perform computer object search and get results
	echo "Auditing computer accounts in: ".$ldap_path_details["ldap_base_dn"]."<br>\n";
	$ldap_filter = LDAP_COMPUTER_FILTER;
	$ldap_attributes = array("distinguisedname","cn","usnchanged","objectguid","description","operatingSystem","operatingSystemServicePack");
//    $ldap_attributes=array("cn,distinguishedname,instancetype,whencreated,whenchanged,displayname,usncreated,usnchanged,name,objectguid,useraccountcontrol,badpwdcount,codepage,countrycode,badpasswordtime,lastlogoff,lastlogon,localpolicyflags,pwdlastset,primarygroupid,objectsid,accountexpires,logoncount,samaccountname,samaccounttype,operatingsystem,operatingsystemversion,operatingsystemservicepack,dnshostname,serviceprincipalname,serviceprincipalname,objectcategory,iscriticalsystemobject,lastlogontimestamp");
 	$ldap_results=SearchLdap($ldap,$ldap_path_details["ldap_base_dn"],$ldap_filter,$ldap_attributes);
	// Update db, ldap_computers table
	echo "Updating Computers table ...<br>\n";
	Updateldap_computersTable($ldap_results, $ldap_path_details["ldap_path_id"], $audit_timestamp);
	DebugEcho("Total: ".ReturnDataOrNull($ldap_results["count"]));

	// Disconnect LDAP
	ldap_unbind($ldap);

	// Finally update the ldap_paths table with the audit timestamp
	$sql="UPDATE ldap_paths SET ldap_paths_timestamp='".$audit_timestamp."' WHERE ldap_paths.ldap_paths_id='".$ldap_path_details["ldap_path_id"]."'";
	mysqli_query($db,$sql);
}

/**********************************************************************************************************
Function Name:
	Updateldap_usersTable
Description:
	Update MySQL ldap_users table with updated audit info
Arguments:
	&$ldap_results			[IN]	[Array]		Results of LDAP query
	&$ldap_path_id		[IN]	[STRING]	ID of the "owning" LDAP path
	&$audit_timestamp	[IN]	[STRING]	Time of audit as string
Returns:	None.
Change Log:
	29/04/2008			New function	[Nick Brown]
**********************************************************************************************************/
function Updateldap_usersTable(&$ldap_results, &$ldap_path_id, &$audit_timestamp)
{
	global $db;
	
	foreach($ldap_results as $result)
	{
		if(is_array($result))
		{
			$guid=formatGUID($result["objectguid"][0]);
			
			// Create array for "insert" clause of SQL query
			$insert_data=array();
			$insert_data["ldap_users_guid"]=$guid;
			$insert_data["ldap_users_cn"]=$result["cn"][0];
			$insert_data["ldap_users_dn"]=$result["dn"];
			$insert_data["ldap_users_description"]=ReturnDataOrNull($result["description"][0]);
			$insert_data["ldap_users_department"]=ReturnDataOrNull($result["department"][0]);
			$insert_data["ldap_users_timestamp"]=$audit_timestamp;
			$insert_data["ldap_users_first_timestamp"]=$audit_timestamp;
			$insert_data["ldap_users_path_id"]=$ldap_path_id;
			
			// Create array for "update" clause of SQL query
			$update_data=array();
			$update_data["ldap_users_cn"]=$result["cn"][0];
			$update_data["ldap_users_dn"]=$result["dn"];
			$update_data["ldap_users_description"]=ReturnDataOrNull($result["description"][0]);
			$update_data["ldap_users_department"]=ReturnDataOrNull($result["department"][0]);
			$update_data["ldap_users_timestamp"]=$audit_timestamp;
			$update_data["ldap_users_path_id"]=$ldap_path_id;
			
			// Create SQL query
			$sql=ConstructSQLInsertQuery("ldap_users",$insert_data,$update_data);
			DebugEcho($sql);
			
			$mysqlresult = mysqli_query($db,$sql);
			DebugEcho($mysqlresult);
			if ($mysqlresult == FALSE) {echo "Error: " . mysqli_errno($db) . ": " . mysqli_error($db). "\n";}
			DebugEcho("*******************************************************************************");
		}
	}
}

/**********************************************************************************************************
Function Name:
	Updateldap_computersTable
Description:
	Update MySQL ldap_computers table with updated audit info
Arguments:
	&$ldap_results			[IN]	[Array]		Results of LDAP query
	&$ldap_path_id		[IN]	[STRING]	ID of the "owning" LDAP path
	&$audit_timestamp	[IN]	[STRING]	Time of audit as string	
Returns:	None.
Change Log:
	29/04/2008			New function	[Nick Brown]
**********************************************************************************************************/
function Updateldap_computersTable(&$ldap_results, &$ldap_path_id, &$audit_timestamp)
{
	global $db;
	
	foreach($ldap_results as $result)
	{
		if(is_array($result))
		{
			//var_dump($result);
			$guid=formatGUID($result["objectguid"][0]);

			// Create array for "insert" clause of SQL query			
			$insert_data=array();
			$insert_data["ldap_computers_guid"]=$guid;
			$insert_data["ldap_computers_cn"]=$result["cn"][0];
			$insert_data["ldap_computers_dn"]=$result["dn"];
			$insert_data["ldap_computers_description"] = ReturnDataOrNull($result["description"][0]);
			$insert_data["ldap_computers_os"] = ReturnDataOrNull($result["operatingsystem"][0]);
			$insert_data["ldap_computers_service_pack"] = ReturnDataOrNull($result["operatingsystemservicepack"][0]);
			$insert_data["ldap_computers_timestamp"] = $audit_timestamp;
			$insert_data["ldap_computers_first_timestamp"] = $audit_timestamp;
			$insert_data["ldap_computers_path_id"]=$ldap_path_id;
			
			// Create array for "update" clause of SQL query
			$update_data=array();
			$update_data["ldap_computers_cn"]=$result["cn"][0];
			$update_data["ldap_computers_dn"]=$result["dn"];
			$update_data["ldap_computers_description"]=ReturnDataOrNull($result["description"][0]);
			$update_data["ldap_computers_os"]=ReturnDataOrNull($result["operatingsystem"][0]);
			$update_data["ldap_computers_service_pack"]=ReturnDataOrNull($result["operatingsystemservicepack"][0]);
			$update_data["ldap_computers_timestamp"]=$audit_timestamp;
			$update_data["ldap_computers_path_id"]=$ldap_path_id;
			
			// Create SQL query
			$sql=ConstructSQLInsertQuery("ldap_computers",$insert_data,$update_data);
			DebugEcho($sql);
			
			$mysqlresult = mysqli_query($db,$sql);
			DebugEcho($mysqlresult);
			if ($mysqlresult == FALSE) {echo "Error: " . mysqli_errno($db) . ": " . mysqli_error($db). "\n";}
			DebugEcho("*******************************************************************************");
		}
	}
}


/**********************************************************************************************************
Function Name:
	SearchLdap
Description:
	A wrapper for the PagedLdapSearch function.  Sets high & low usn values then uses them in the call to PagedLdapSearch.
Arguments:	
	&$ldap_link		[IN] [RESOURCE]	LDAP resource link
	&$base_dn			[IN] [STRING]	Base DN for search start
	&$filter				[IN] [STRING]	LDAP filter
	&$attributes		[IN] [ARRAY/STRING]	LDAP attributes to be returned from search
Returns:
	ldap entries 		[ARRAY]
Change Log:
	25/04/2008			New function	[Nick Brown]
	28/04/2008			PagedLdapSearch now needs to be called with high/lowUSN values [Nick Brown]
**********************************************************************************************************/
function SearchLdap(&$ldap_link,&$base_dn,&$filter,&$attributes)
{
	$usn_low=0;
	$usn_high=GetHighestUsn($ldap_link);
	$results=array();
	
	PagedLdapSearch($ldap_link, $base_dn, $filter, $attributes, $usn_low, $usn_high, $results);
	return $results;
}

/**********************************************************************************************************
Function Name:
	PagedLdapSearch
Description:
	A wrapper for the PHP ldap_search function. Whenever  ldap_search returns more than 1000 objects, the search is
	split into two by calculating new page_usn_low & page_usn_high values and calling PagedLdapSearch again with these
	values. This is done recursively until the search returns less than 1000 objects.
	This is a workaround is necessary becuase AD returns only 1000 objects in a page and the PHP LDAP extension doesn't 
	support paged results.
Arguments:	
	&$ldap_link			[IN] [RESOURCE]	LDAP resource link
	&$base_dn				[IN] [STRING]	Base DN for search start
	&$filter					[IN] [STRING]	LDAP filter
	&$attributes			[IN] [ARRAY/STRING]		LDAP attributes to be returned from search
	&$page_usn_low		[IN] [INTEGER]	Low USN value - LDAP search filter is modified to return objects that are >= this value  
	&$page_usn_high		[IN] [INTEGER]	High USN value - LDAP search filter is modified to return objects that are <= this value
	&$results				[OUT] [ARRAY]	returned ldap entries
Returns:
	None
Change Log:
	25/04/2008			New function	[Nick Brown]
	28/04/2008			Now using usnchanged value to limit search results [Nick Brown]
**********************************************************************************************************/
function PagedLdapSearch(&$ldap_link,&$base_dn,&$filter,&$attributes,&$page_usn_low,&$page_usn_high,&$results)
{
	global $err;
	$full_ldap_filter="(&".$filter."(usnchanged>=".$page_usn_low.")(usnchanged<=".$page_usn_high."))";
	DebugEcho($full_ldap_filter);
	
	set_error_handler('HandleError');
	$search_results=ldap_search($ldap_link, $base_dn, $full_ldap_filter, $attributes, 0, 1000);
	restore_error_handler();
	
	if($err)
	{
		// This search was too big - halve search scope using usn values 
		$err=FALSE;
		// Search "lower" half
		$new_page_usn_high=((int)(($page_usn_high-$page_usn_low)/2)) + $page_usn_low;
		PagedLdapSearch($ldap_link,$base_dn,$filter,$attributes,$page_usn_low,$new_page_usn_high,$results);
		// Search "higher" half
		$new_page_usn_low=$new_page_usn_high+1;
		PagedLdapSearch($ldap_link,$base_dn,$filter,$attributes,$new_page_usn_low,$page_usn_high,$results);
	}
	else
	{
		// Search was OK - return entrries
		$entries=ldap_get_entries($ldap_link, $search_results);
		//DebugEcho($entries);
		if(count($entries)>0) 
		{
			$results=array_merge($results,$entries);
			$results["count"]=count($results)-1;
		}
	}
}

/**********************************************************************************************************
Function Name:
	HandleError
Description:
	Error handler callback function, set by set_error_handler()
Arguments:
	$errno			[IN]	[INTEGER]	Error level
	$errstr			[IN]	[STRING]	Error message
	$errfile			[IN]	[STRING]	Name of file that error was raised in
	$errline			[IN]	[INTEGER]	Line number that error was raised at
	$errcontext	[IN]	[ARRAY]		Active symbol table (every variable that existed in the scope the error was triggered in)
Returns:
	FALSE			[BOOLEAN]
Change Log:
	25/04/2008			New function	[Nick Brown]
**********************************************************************************************************/
function HandleError( $errno, $errstr, $errfile, $errline, $errcontext)
{
	global $err;
	DebugEcho("*** Error: ".$errstr);
	$err=TRUE;
	return FALSE;
}

/**********************************************************************************************************
Function Name:
	GetHighestUsn
Description:
	Reads and returns the HighestCommittedUSN attribute from RootDSE of the LDAP server
Arguments:
	&$ldap_link			[IN] [RESOURCE]	LDAP resource link
Returns:
	HighestCommittedUSN attribute value	[INTEGER]
Change Log:
	25/04/2008			New function	[Nick Brown]
**********************************************************************************************************/
function GetHighestUsn(&$ldap)
{
	$sr=ldap_read($ldap,null,"(highestcommittedusn=*)",array("highestcommittedusn"));
	$entries=ldap_get_entries($ldap, $sr);
	return (int)$entries[0]["highestcommittedusn"][0];
}

/**********************************************************************************************************
Function Name:
	ConstructSQLInsertQuery
Description:
	Creates a "safe" sql query string that handles empty values. If $SQLUpdateData is specified, the constructed query will include a
	"ON DUPLICATE KEY UPDATE" clause to update fields
Arguments:
	$SQLData	[IN]	[ARRAY/STRING]	Key/values representing row field names/values
	$Table		[IN] 	[STRING]	SQL table name
	$SQLUpdateData	[IN]	[STRING]	[OPTIONAL]	Key/values representing row field names/values
Returns:
	Sql query string	[STRING]
Change Log:
	29/04/2008			New function	[Nick Brown]
	15/09/2008			Added code to escape cahracters ' & "[Nick Brown]
**********************************************************************************************************/
function ConstructSQLInsertQuery($Table,$SQLInsertData,$SQLUpdateData)
{ 
	// Create fields and values string for the insert clause
	$keys=array_keys($SQLInsertData); 
	$InsertFields="";
	$InsertValues="";

	//var_dump($SQLInsertData);
	foreach($keys as $key) 
	{ 
		if(isset($SQLInsertData["$key"])) 
		{ 
			$InsertFields.="$key,";
			$value = str_replace("'","\\'",$SQLInsertData[$key]);
			$value = str_replace("\"","\\\"",$value); 			
			$InsertValues.="'$value',";
		}
	}
	$InsertFields=rtrim($InsertFields,","); 
	$InsertValues=rtrim($InsertValues,","); 

	// Check if $SQLUpdateData was provided
	if(empty($SQLUpdateData))
		{$query="INSERT INTO $Table ($InsertFields) VALUES ($InsertValues)";}
	else
	// Create fields and values string for the update clause
	{
		$keys=array_keys($SQLUpdateData);
		$Update="";

		foreach($keys as $key) 
		{ 
			if(isset($SQLUpdateData["$key"])) 
			{
				$value = str_replace("'","\\'",$SQLUpdateData[$key]);
				$value = str_replace("\"","\\\"",$value); 
				$Update.="$key='$value',";
			}
		}
		$Update=rtrim($Update,","); 

		$query="INSERT INTO $Table ($InsertFields) VALUES ($InsertValues) ON DUPLICATE KEY UPDATE $Update";
	}
	return $query; 
}


/**********************************************************************************************************
Function Name:
	ReturnDataOrNull
Description:
	Returns value of $Data  or null value where $Data is unset
Arguments:
	$Data	[IN] [Array]
Returns:
	null
Change Log:
	29/04/2008			New function	[Nick Brown]
	15/09/2008			Function renamed [Nick Brown]
**********************************************************************************************************/
function ReturnDataOrNull(&$Data)
{
	return (isset($Data)) ? $Data : null;
}

/**********************************************************************************************************
Function Name:
	DebugEcho
Description:
	If debugging is enabled, outputs text to stdout
Arguments:
	$text		[IN] [STRING]	text to output
Returns:	None
Change Log:
	25/04/2008			New function	[Nick Brown]
**********************************************************************************************************/
function DebugEcho($Info)
{
	global $debugging;
	if($debugging) 
	{
		if (is_string($Info)){echo $Info."\n";}
		else
		{
			echo "DebugEcho: \n";
			var_dump($Info);
		}
	}
}

// ******************** Scratchpad *************************************************************************/
/* 
Creating Scheduled Tasks
http://www.microsoft.com/technet/scriptcenter/guide/sas_man_rsxs.mspx?mfr=true
*/
?>
