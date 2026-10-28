<?php
/**********************************************************************************************************
Module:	admin_config_data.php

Description:
	Functions in this module are called by the AJAX objects (XMLRequestor & HTMLRequestor)  and return XML or HTML
	content back to the calling page ("admin_config.php")
		
Recent Changes:
	
	[Nick Brown]	17/03/2009	SaveLdapConnectionXml() & GetLdapConnectionXml now use GetAesKey()
	[Nick Brown]	29/04/2009	Minor changes to TestLdapConnectionHtml() , SaveLdapConnectionXml(),  
	GetLdapConnectionXml() and GetDefaultNC()
	[Nick Brown]	01/05/2009	New function GetOpenSslEnabled() ."application_class.php" now included to provide 
	access to the global $TheApp object
	[Nick Brown]	19/08/2009	New function GetLdapSchemaType(). Added support for Open LDAP in 
	TestLdapConnectionHtml()
	[Chad Sikorra]	15/11/2009	Added functions for SMTP connection

**********************************************************************************************************/
set_time_limit(60);
header( "Expires: Mon, 20 Dec 1998 01:00:00 GMT" );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: no-cache, must-revalidate" );
header( "Pragma: no-cache" );

include "application_class.php";
include "include_config.php";
include "include_lang.php";
include "include_functions.php";

error_reporting(0);

// Set up SQL connection 
$db=GetOpenAuditDbConnection();;
mysqli_select_db($db,$mysqli_database);

// Call data functions
switch($_GET["sub"])
{
	case "f1": exit(GetLdapConnectionsHtml($db));
	case "f2": exit(TestLdapConnectionHtml($db));
	case "f3": exit(SaveLdapConnectionXml($db));
	case "f4": exit(DeleteLdapConnectionHtml($db));
	case "f5": exit(GetLdapConnectionXml($db));
	case "f6": exit(GetDefaultNCXml($db));
	case "f7": exit(SaveLdapPathXml($db));
	case "f8": exit(GetLdapPathXml($db));
	case "f9": exit(DeleteLdapPathXml($db));
	case "f10": exit(GetOpenSslEnabled());
	case "f11": exit(GetSmtpConnectionHtml($db));
	case "f12": exit(TestSmtpConnectionHtml($db));
	case "f13": exit(SaveSmtpConnectionXml($db));
	case "f14": exit(DeleteSmtpConnectionHtml($db));
	case "f15": exit(GetSmtpConnectionXml($db));
	case "f16": exit(GetLdapEnabled());
	case "f17": exit(SaveConfigurationXml($db));
}

/**********************************************************************************************************
Function Name:
	GetLdapConnectionsHtml
Description:
	Retrieves configured LDAP connections and paths from the DB. Displays them as an HTML table.
Arguments:
	$db	[IN] [Resource]	DB connection	
Returns:		
	[String]	XML string containing the success status of the operation
Change Log:
	01/09/2008			New function	[Nick Brown]
	19/09/2008			Removed echo  statments and replaced with response string	[Nick Brown]
	22/09/2008			Renamed function [Nick Brown]
**********************************************************************************************************/
function GetLdapConnectionsHtml($db)
{	
	$sql  = "SELECT * FROM ldap_connections";	
	$result = mysqli_query($db,$sql);
	
	// Display results table
	$response = "<table class=\"tftable\" >";
	$response .= "<tr><th>LDAP Connections</th><th>LDAP Paths</th></tr>";
	if ($myrow = mysqli_fetch_array($result))
	{
		do
		{
			$response .= "<tr>";
			$response .= "<td><a id='".$myrow['ldap_connections_id']."' href=\"#\" onMouseover=\"ShowMenu(event,connection_menu);\" onMouseout=\"DelayHideMenu(event)\">";
			$response .= "<i class=\"fa fa-server\" aria-hidden=\"true\"></i>".$myrow['ldap_connections_name']."</a></td>";
			$response .= "<td>".GetLdapPathsHtml($myrow['ldap_connections_id'],$db)."</td>";
			$response .= "</tr>";
		}	while ($myrow = mysqli_fetch_array($result));
	}
	else
	{
		$response .= "<tr><td>No LDAP connections defined.</td><td>No LDAP paths defined.</td></tr>";
	}
	$response .= "</table>";

	return $response;
}

/**********************************************************************************************************
Function Name:
	GetLdapPathsHtml
Description:
	Retrieves all LDAP paths associated with the supplied LDAP connection.
	Returns them as an HTML list
Arg/uments:
	$ConnectionGuid	[IN] [String]		LDAP Connection GUID
	$db					[IN] [Resource]	DB connection	
Returns:		
	[String] HTML string containing the list of LDAP paths
Change Log:
	01/09/2008			New function	[Nick Brown]
	19/09/2008			Removed echo  statments and replaced with response string	[Nick Brown]
	22/09/2008			Function renamed	[Nick Brown]
**********************************************************************************************************/
function GetLdapPathsHtml($ConnectionGuid, $db)
{
	
	$sql  = "SELECT * FROM ldap_paths WHERE ldap_paths_connection_id='".$ConnectionGuid."'";	
	$result = mysqli_query($db,$sql);
	
	// Display results table
	if ($myrow = mysqli_fetch_array($result))
	{
		$response = "<ul>";
		do
		{
			$response .= "<li><a id='".$myrow['ldap_paths_id']."' href=\"#\" onMouseover=\"ShowMenu(event,path_menu);\" onMouseout=\"DelayHideMenu(event)\"><i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i>".$myrow['ldap_paths_dn']."</a></li>";
		}	while ($myrow = mysqli_fetch_array($result));
		$response .= "</ul>";
	}
	else {$response = "No LDAP paths defined - use \"Add New Path\" from the LDAP Connection drop-down menu on the left.";}

	return $response;
}

/**********************************************************************************************************
Function Name:
	TestLdapConnectionHtml
Description:
	Attenpts to bind and authenticate to LDAP host using $_GET["ldap_connection_server"], $_GET["ldap_connection_user"] 
	and $_GET["ldap_connection_password"]	- Returns progress/result as HTML
Arguments:
	$db	[IN] [Resource]	DB connection	
Returns:		
	[String] HTML string containing the connection result
Change Log:
	01/09/2008			New function	[Nick Brown]
	19/09/2008			Removed echo  statments and replaced with response string	[Nick Brown]
	22/09/2008			Function renamed	[Nick Brown]
	29/04/2009			Added checking for valid Default Naming Context returned from GetDefaultNC()	[Nick Brown]	
	19/08/2009			Added call to GetLdapSchemaType() to support Open LDAP
**********************************************************************************************************/
function TestLdapConnectionHtml($db)
{	
	// Connect anonymously (to get default domain NC from rootDSE)
	$server = ($_GET['ldap_connection_use_ssl'] == 1) ? "ldaps://".$_GET["ldap_connection_server"] : $_GET["ldap_connection_server"];
	$l = ConnectToLdapServer($server);
	if (is_array($l))
	{
		$response .=  "!! Unable to bind to server !!<br />";
		$response .=  "Err Number: ".$l["number"]."<br />";
		$response .=  "Err String: ".$l["string"]."<br />";
		$response .=  "Check that server name is correct";
		return $response;
	}	
	$response .=  "Server connection successful<br />";

	// Get LDAP Schema
	$schema = GetLdapSchemaType($l);
	$response .=  "Schema: ".$schema."<br />";
	
	// AD schema can use UPN logon
	if($schema == "AD")
	{
		// Get default domain NC
		$domain_nc = GetDefaultNC($l,$schema);
		if (is_array($domain_nc))
		{
			$response .=  "!! Unable to obtain Default Naming Context !!<br />";
			$response .=  "Err Number: ".$l["number"]."<br />";
			$response .=  "Err String: ".$l["string"]."<br />";
			return $response;
		}	
		$response .=  "Default Naming Context: ".$domain_nc."<br />";

		// Convert default domain NC to DNS suffix string
		$user_dns_suffix = implode(".",explode(",DC=",substr($domain_nc,3)));
		$response .=  "User DNS Suffix: ".$user_dns_suffix."<br />";
		ldap_unbind($l);
		
		// Check if supplied user is already in UPN format - else append domain DNS suffix
		$ldap_user = isEmailAddress($_GET["ldap_connection_user"]) ? $_GET["ldap_connection_user"] : $_GET["ldap_connection_user"]."@".$user_dns_suffix;
	}
	else {$ldap_user = $_GET["ldap_connection_user"];}
	
	// Now try to bind using supplied credentials
	$l = ConnectToLdapServer($_GET["ldap_connection_server"],$ldap_user,$_GET["ldap_connection_password"]);
	if (is_array($l))
	{
		$response .=  "!! Unable to bind to server !!<br />";
		$response .=  "Err Number: ".$l["number"]."<br />";
		$response .=  "Err String: ".$l["string"]."<br />";
		$response .=  "Check that credentials are correct";
		return $response;
	}	
	ldap_unbind($l);
	$response .=  "LDAP bind successful<br />";

	return $response;
}

/**********************************************************************************************************
Function Name:
	GetLdapSchemaType
Description:
	Determines LDAP schema type from RootDSE of the LDAP server
Arguments:
	&$ldap	[IN] [RESOURCE]	LDAP resource link
Returns:
	[String]	schema type string "AD", "OpenLDAP", "UNKNOWN"
Change Log:
	18/08/2009			New function	[Nick Brown]
**********************************************************************************************************/
function GetLdapSchemaType(&$ldap)
{
	// Check for AD LDAP schema
	$sr = ldap_read($ldap,null,"(|(structuralObjectClass=*)(dsServiceName=*))",array("structuralObjectClass","dsServiceName"));
	$entries = ldap_get_entries($ldap, $sr);
	
	if(isset($entries[0]["structuralobjectclass"][0])) {return "OpenLDAP";}
	if(isset($entries[0]["dsservicename"][0])) {return "AD";}
	
	return "UNKNOWN";
}

/**********************************************************************************************************
Function Name:
	GetDefaultNC
Description:
	Reads and returns the DefaultNamingContext attribute from RootDSE of the LDAP server
Arguments:
	&$ldap	[IN] [RESOURCE]	LDAP resource link
Returns:
	[String]	defaultnamingcontext attribute value
Change Log:
	25/04/2008			New function	[Nick Brown]
	29/04/2009			Now returns error array if LDAP query fails	[Nick Brown]	
**********************************************************************************************************/
function GetDefaultNC(&$ldap, &$schema)
{
	if($schema =="OpenLDAP"){$prop = "namingcontexts";}
	if($schema =="AD"){$prop = "defaultnamingcontext";}
	
	$sr = ldap_read($ldap,null,"(".$prop."=*)",array($prop));
	$entries = ldap_get_entries($ldap, $sr);
	$DefaultNC = $entries[0][$prop][0];
	$errdata = array("number" => ldap_errno($l), "string" => ldap_error($l));
	if ($errdata["number"] !=0 ) return $errdata; else return $DefaultNC;
}

/**********************************************************************************************************
Function Name:
	SaveLdapConnectionXml
Description:
	Writes the supplied LDAP connection settings to the DB. Performs a test first to check if settings are valid:
	HTML reponse is stored in the <html> XML element
	Result status is stored in <result> XML element
Arguments:
	$db	[IN] [RESOURCE]	DB resource
Returns:
	[String]	XML string containing the HTML response and the test conenction status
Change Log:
	25/04/2008			New function	[Nick Brown]
	19/09/2008			Removed echo  statments and replaced with response string	[Nick Brown]
	17/03/2009			Using GetAesKey() instead of GetVolumeLabel()
	19/08/2009			Added call to GetLdapSchemaType(). Added support for Open LDAP
**********************************************************************************************************/
function SaveLdapConnectionXml($db)
{
	header("Content-type: text/xml");

	// Validate supplied details
	$html = TestLdapConnectionHtml();
	$testresult = (strpos($html,"LDAP bind successful") === false) ? "false" : "true";
	if($testresult != "true") return "<SaveLdapConnection><html>".$html."</html><result>".$testresult."</result></SaveLdapConnection>";
	
	// Connect anonymously to get default domain NC & config NC
	$l = ConnectToLdapServer($_GET["ldap_connection_server"]);
	$schema = GetLdapSchemaType($l);
		
	$nc = GetDefaultNC($l,$schema);
	$fqdn = implode(".",explode(",dc=",strtolower(substr($nc,3))));	
	
	if($schema == "AD")
	{
		$config_nc = GetConfigNC($l);
		ldap_unbind($l);

		// Authenticate and get domain NetBIOS name
		$ldap_user = isEmailAddress($_GET["ldap_connection_user"]) ? $_GET["ldap_connection_user"] : $_GET["ldap_connection_user"]."@".$fqdn;
		$l = ConnectToLdapServer($_GET["ldap_connection_server"],$ldap_user,$_GET["ldap_connection_password"]);
		$ldap_connection_name = GetDomainNetbios($l,"CN=Partitions,".$config_nc,$nc);
		ldap_unbind($l);
	}

	if($schema == "OpenLDAP")
	{
		$ldap_connection_name = strtoupper(substr($fqdn,0,strpos($fqdn,".")));
		$ldap_user = $_GET["ldap_connection_user"];
	}
	
	$aes_key = GetAesKey();
	if (isset($_GET["ldap_connection_id"]) and strlen($_GET["ldap_connection_id"]) > 0)
	{
		// UPDATE query - connection already exists so modify
		LogEvent("admin_config_data.php","SaveLdapConnectionXml","Edit Connection: ".$ldap_connection_name);
		$sql  = "UPDATE `ldap_connections` SET 
						`ldap_connections_nc`='".$nc."',
						`ldap_connections_fqdn`='".$fqdn."',
						`ldap_connections_server`='".$_GET["ldap_connection_server"]."',
						`ldap_connections_user`=AES_ENCRYPT('".$_GET["ldap_connection_user"]."','".$aes_key."'),
						`ldap_connections_password`=AES_ENCRYPT('".$_GET["ldap_connection_password"]."','".$aes_key."'),
						`ldap_connections_use_ssl`='".$_GET["ldap_connection_use_ssl"]."',
						`ldap_connections_name`='".$ldap_connection_name."' 	
						WHERE ldap_connections_id='".$_GET["ldap_connection_id"]."'";	
	}
	else
	{
		// INSERT query - new connection
		LogEvent("admin_config_data.php","SaveLdapConnectionXml","New Connection: ".$ldap_connection_name);
		$sql  = "INSERT INTO `ldap_connections` (
						`ldap_connections_nc`,
						`ldap_connections_fqdn`,
						`ldap_connections_server`,
						`ldap_connections_user`,
						`ldap_connections_password`,
						`ldap_connections_use_ssl`,
						`ldap_connections_name`,
						`ldap_connections_schema`) 	
						VALUES (
						'".$nc."',
						'".$fqdn."',
						'".$_GET["ldap_connection_server"]."', 
						AES_ENCRYPT('".$_GET["ldap_connection_user"]."','".$aes_key."'),
						AES_ENCRYPT('".$_GET["ldap_connection_password"]."','".$aes_key."'),
						'".$_GET["ldap_connection_use_ssl"]."',
						'".$ldap_connection_name."','".$schema."')";
	}
	$result = mysqli_query($db,$sql);
	//return "<SaveLdapConnection><html>".$html."</html><sql_query>".$sql."</sql_query><result>".$testresult."</result></SaveLdapConnection>";
	return "<SaveLdapConnection><html>".$html."</html><result>".$testresult."</result></SaveLdapConnection>";
}

/**********************************************************************************************************
Function Name:
	GetConfigNC
Description:
	Reads and returns the ConfigurationNamingContext attribute from RootDSE of the LDAP server
Arguments:
	&$ldap	[IN] [RESOURCE]	LDAP resource link
Returns:
	[String]	configurationnamingcontext attribute value
Change Log:
	25/04/2008			New function	[Nick Brown]
**********************************************************************************************************/
function GetConfigNC(&$ldap)
{
	$sr = ldap_read($ldap,null,"(configurationnamingcontext=*)",array("configurationnamingcontext"));
	$entries = ldap_get_entries($ldap, $sr);
	$ConfigNC = $entries[0]["configurationnamingcontext"][0];
	return $ConfigNC;
}

/**********************************************************************************************************
Function Name:
	GetDomainGUID
Description:
	Reads and returns the objectGuid attribute of the domain object
Arguments:
	&$ldap			[IN] [RESOURCE]	LDAP resource link
	$DomainDN	[IN] [String]			Domain distinguished name
Returns:
	[String]	objectguid attribute value
Change Log:
	25/04/2008			New function	[Nick Brown]
**********************************************************************************************************/
function GetDomainGUID(&$ldap, $DomainDN)
{
	$sr = ldap_read($ldap,$DomainDN,"(objectClass=domain)",array("objectguid"));
	$entries = ldap_get_entries($ldap, $sr);
	$guid = formatGUID($entries[0]["objectguid"][0]);
	return $guid;
}

/**********************************************************************************************************
Function Name:
	GetDomainNetbios
Description:
	Reads and returns the NetBIOS name of the domain from within the configuration naming context container
Arguments:
	&$ldap			[IN] [RESOURCE]	LDAP resource link
	$ConfigNC		[IN] [String]				Domain distinguished name
	$DomainDN	[IN] [String]			Domain distinguished name
Returns:
	[String]	nETBIOSName attribute value
Change Log:
	25/04/2008			New function	[Nick Brown]
**********************************************************************************************************/
function GetDomainNetbios(&$ldap,$ConfigNC,$DomainDN)
{
	$sr = ldap_search($ldap,$ConfigNC,"(nCName=$DomainDN)",array("nETBIOSName"));
	$entries = ldap_get_entries($ldap, $sr);
	$netbios = $entries[0]["netbiosname"][0];
	return $netbios;
}

/**********************************************************************************************************
Function Name:
	DeleteLdapConnectionHtml
Description:
	Deletes all references to LDAP connection from DB, including realated LDAP paths, users and computers
Arguments:
	$db	[IN] [RESOURCE]	DB resource
Returns:
	[String]	HTML string 
Change Log:
	25/04/2008			New function	[Nick Brown]
	19/09/2008			Removed echo  statments and replaced with response string	[Nick Brown]
	25/09/2008			Added queries to ensure that related LDAP users and computers are deleted [Nick Brown]
**********************************************************************************************************/
function DeleteLdapConnectionHtml($db)
{
	LogEvent("admin_config_data.php","DeleteLdapConnectionHtml","Delete Connection: ".$_GET["ldap_connection_id"]);

	$response = $_GET["ldap_connection_id"]."<br />";

	// Delete LDAP users that are related to this connection  GUID
	$sql  = "DELETE ldap_users 
	FROM ldap_connections, ldap_paths, ldap_users
	WHERE ldap_paths.ldap_paths_connection_id=ldap_connections.ldap_connections_id
	AND ldap_users.ou_id=ldap_paths.ldap_paths_id
	AND ldap_connections.ldap_connections_id='".$_GET["ldap_connection_id"]."'";
	$result= mysqli_query($db,$sql);
	
	// Delete LDAP computers that are related to this connection GUID
	$sql  = "DELETE ldap_computers 
	FROM ldap_connections, ldap_paths, ldap_computers 
	WHERE ldap_paths.ldap_paths_connection_id=ldap_connections.ldap_connections_id
	AND ldap_computers.ldap_computers_path_id=ldap_paths.ldap_paths_id
	AND ldap_connections.ldap_connections_id='".$_GET["ldap_connection_id"]."'";
	$result= mysqli_query($db,$sql);

	// Delete LDAP paths related to this connection GUID
	$sql  = "DELETE ldap_connections, ldap_paths 
	FROM ldap_connections, ldap_paths 
	WHERE ldap_paths.ldap_paths_connection_id=ldap_connections.ldap_connections_id
	AND ldap_connections.ldap_connections_id='".$_GET["ldap_connection_id"]."'";
	$result= mysqli_query($db,$sql);
	
	// Delete LDAP connection
	$sql  = "DELETE FROM ldap_connections 
	WHERE ldap_connections.ldap_connections_id='".$_GET["ldap_connection_id"]."'";
	$result= mysqli_query($db,$sql);

	$response .= "LDAP connection deleted.";
	return $response;
}

/**********************************************************************************************************
Function Name:
	GetLdapConnectionXml
Description:
	Gets LDAP connection details from the DB for an LDAP connection defined by $_GET["ldap_connection_id"]
	Returns an XML string containing the info	
Arguments:
	$db	[IN] [RESOURCE]	DB resource
Returns:
	[String]	XML string containing connection details 
Change Log:
	25/04/2008			New function	[Nick Brown]
	19/09/2008			Removed echo  statments and replaced with response string	[Nick Brown]
	17/03/2009			Using GetAesKey() instead of GetVolumeLabel()	[Nick Brown]
**********************************************************************************************************/
function GetLdapConnectionXml($db)
{
	header("Content-type: text/xml");
	$aes_key = GetAesKey();

	$sql = "SELECT ldap_connections_server, 
					AES_DECRYPT(ldap_connections_user,'".$aes_key."') AS ldap_user, 
					AES_DECRYPT(ldap_connections_password,'".$aes_key."') AS ldap_password, 
					ldap_connections_use_ssl 
					FROM ldap_connections 
					WHERE ldap_connections_id='".$_GET["ldap_connection_id"]."'";
	$result = mysqli_query($db,$sql);
	
	// Return results  as xml
	$response = "<connections>";
	if ($myrow = mysqli_fetch_array($result))
	{
		//print_r($myrow);
		do
		{
			$response .= "<connection>";
			$response .= "<ldap_connection_server>".$myrow['ldap_connections_server']."</ldap_connection_server>";
			$response .= "<ldap_connection_user>".$myrow['ldap_user']."</ldap_connection_user>";
			$response .= "<ldap_connection_password>".$myrow['ldap_password']."</ldap_connection_password>";
			$response .= "<ldap_connection_use_ssl>".$myrow['ldap_connections_use_ssl']."</ldap_connection_use_ssl>";
			$response .= "</connection>";
		}	while ($myrow = mysqli_fetch_array($result));
	}
	$response .= "</connections>";

	return $response;
}

/**********************************************************************************************************
Function Name:
	GetDefaultNCXml
Description:
	Gets the "ldap_connections_nc" value from the DB for an LDAP connection defined by $_GET["ldap_connection_id"]
	Returns an XML string containing the requested info
Arguments:
	$db	[IN] [Resource]	DB connection	
Returns:		
	[String]	XML string containing the requested info
Change Log:
	01/09/2008			New function	[Nick Brown]
	19/09/2008			Removed echo  statments and replaced with response string	[Nick Brown]
**********************************************************************************************************/
function GetDefaultNCXml($db)
{
	header("Content-type: text/xml");
	$sql  = "SELECT ldap_connections_nc FROM ldap_connections WHERE ldap_connections_id='".$_GET["ldap_connection_id"]."'";
	$result = mysqli_query($db,$sql);
	if ($myrow = mysqli_fetch_array($result)){$response = "<connection><domain_nc>".$myrow['ldap_connections_nc']."</domain_nc></connection>";}

	return $response;
}

/**********************************************************************************************************
Function Name:
	SaveLdapPathXml
Description:
	Saves LDAP path info defined by $_GET["ldap_path_id"]) to the DB
Arguments:
	$db	[IN] [Resource]	DB connection	
Returns:		
	[String]	XML string containing the success status of the operation
Change Log:
	01/09/2008			New function	[Nick Brown]
	19/09/2008			Removed echo  statments and replaced with response string	[Nick Brown]
**********************************************************************************************************/
function SaveLdapPathXml($db)
{
	if (isset($_GET["ldap_path_id"]) and strlen($_GET["ldap_path_id"]) > 0)
	{
		LogEvent("admin_config_data.php","SaveLdapPathXml","Edit Path: ".$_GET["ldap_path_id"]);
		$sql = "UPDATE `ldap_paths` SET ldap_paths_dn='".$_GET["ldap_path_dn"]."', ldap_paths_audit=".$_GET["ldap_path_audit"]." WHERE ldap_paths_id=".$_GET["ldap_path_id"];
		$result = mysqli_query($db,$sql);
	}
	else
	{
		LogEvent("admin_config_data.php","SaveLdapPathXml", "New Path: ".$_GET["ldap_path_dn"]);
		$sql =  "INSERT INTO `ldap_paths` (`ldap_paths_dn`, `ldap_paths_connection_id`, `ldap_paths_audit`) ";
		$sql .= "VALUES ('".$_GET["ldap_path_dn"]."','".$_GET["ldap_path_connection_id"]."',".$_GET["ldap_path_audit"].")";
		$result = mysqli_query($db,$sql);
	}
	$response =  "<SaveLdapPath><query>".$sql."</query><result>";
	$response .=  mysqli_error($db);
	$response .=  "</result></SaveLdapPath>";

	return $response;
}

/**********************************************************************************************************
Function Name:
	GetLdapPathXml
Description:
	Obtains LDAP path info defined by $_GET["ldap_path_id"]) from the DB
Arguments:
	$db	[IN] [Resource]	DB connection	
Returns:		
	[String]	XML string containing the success status of the operation
Change Log:
	01/09/2008			New function	[Nick Brown]
	19/09/2008			Removed echo  statments and replaced with response string	[Nick Brown]
**********************************************************************************************************/
function GetLdapPathXml($db)
{
	header("Content-type: text/xml");

	$sql  = "SELECT ldap_paths_dn, ldap_paths_audit FROM ldap_paths WHERE ldap_paths_id=".$_GET["ldap_path_id"];
	$result = mysqli_query($db,$sql);
	
	// Return results  as xml
	$response =  "<paths>";
	if ($myrow = mysqli_fetch_array($result))
	{
		do
		{
			$response .=  "<path>";
			$response .=  "<ldap_path_dn>".$myrow['ldap_paths_dn']."</ldap_path_dn>";
			$response .=  "<ldap_path_audit>".$myrow['ldap_paths_audit']."</ldap_path_audit>";
			$response .=  "</path>";
		}	while ($myrow = mysqli_fetch_array($result));
	}
	$response .=  "</paths>";

	return $response;
}

/**********************************************************************************************************
Function Name:
	DeleteLdapPathXml
Description:
	Deletes the LDAP path defined by $_GET["ldap_path_id"]) from the DB
	Also deletes users and computers owned by the LDAP path
Arguments:
	$db	[IN] [Resource]	DB connection	
Returns:		
	[String]	XML string containing the success status of the operation
Change Log:
	01/09/2008			New function	[Nick Brown]
	19/09/2008			Removed echo  statments and replaced with response string	[Nick Brown]
**********************************************************************************************************/
function DeleteLdapPathXml($db)
{
	header("Content-type: text/xml");
	LogEvent("admin_config_data.php","DeleteLdapPathXml", "Path: ".$_GET["ldap_path_id"]);

	$response = "<DeleteLdapPath><result>";

	// Delete LDAP users that are related to this connection  GUID
	$sql  = "DELETE ldap_users 
	FROM ldap_paths, ldap_users
	WHERE ldap_users.ou_id=ldap_paths.ldap_paths_id
	AND ldap_paths.ldap_paths_id='".$_GET["ldap_path_id"]."'";
	$result = mysqli_query($db,$sql);
	
	// Delete LDAP computers that are related to this connection GUID
	$sql  = "DELETE ldap_computers 
	FROM ldap_paths, ldap_computers
	WHERE ldap_computers.ldap_computers_path_id=ldap_paths.ldap_paths_id
	AND ldap_paths.ldap_paths_id='".$_GET["ldap_path_id"]."'";
	$result = mysqli_query($db,$sql);

	// Delete LDAP path defined by $_GET["uid"]
	$sql  = "DELETE FROM ldap_paths	WHERE ldap_paths.ldap_paths_id='".$_GET["ldap_path_id"]."'";
	$result = mysqli_query($db,$sql);
	
	$response .= $result."</result></DeleteLdapPath>";
	return $response;
}

/**********************************************************************************************************
Function Name:
	GetLdapEnabled
Description:	Called to determine if PHP LDAP extensions are enabled
Arguments:	None
Returns:		
	[String]	XML response
Change Log:
	10/11/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function GetLdapEnabled()
{
	global $TheApp;
	
	header("Content-type: text/xml");
	
	$response  = "<GetLdapEnabled><result>";
	$response .= $TheApp->LdapEnabled ? "Y" : "N";
	$response .= "</result></GetLdapEnabled>";

	return $response;
}

/**********************************************************************************************************
Function Name:
	GetOpenSslEnabled
Description:
Arguments:	None
Returns:		
	[String]	XML response
Change Log:
	01/05/2009			New function	[Nick Brown]
**********************************************************************************************************/
function GetOpenSslEnabled()
{
	global $TheApp;
	
	header("Content-type: text/xml");
	
	$response  = "<GetOpenSslEnabled><result>";
	$response .= $TheApp->OpenSslEnabled ? "Y" : "N";
	$response .= "</result></GetOpenSslEnabled>";

	return $response;
}

/**********************************************************************************************************
Function Name:
	GetSmtpConnectionHtml
Description:
	Retrieves configured SMTP connection from the DB. Displays it as an HTML table.
Arguments:
	$db	[IN] [Resource]	DB connection	
Returns:		
	[String]	HTML table of the SMTP connection
Change Log:
	14/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function GetSmtpConnectionHtml($db)
{	
	$sql  = "SELECT * FROM smtp_connection LIMIT 1";
	$result = mysqli_query($db,$sql);
	
	// Display results table
	$response = "<table class=\"tftable\" >";
	$response .= "<tr><th>SMTP Connection</th><th><center>SMTP Port</center></th> <th><center>Authentication</center></th><th><center>SSL Enabled</center></th></tr>";
	if ($myrow = mysqli_fetch_array($result))
	{
		$auth = ($myrow['smtp_connection_auth']    == 1) ? 'Yes' : 'No';
		$ssl  = ($myrow['smtp_connection_use_ssl'] == 1) ? 'Yes' : 'No';
		$response .= "<tr>";
		$response .= "<td><a id='".$myrow['smtp_connection_id']."' href=\"#\" onMouseover=\"ShowMenu(event,smtp_menu);\" onMouseout=\"DelayHideMenu(event)\">";
		$response .= "<i class=\"fa fa-envelope\" aria-hidden=\"true\"></i>".$myrow['smtp_connection_server']."</a></td>";
		$response .= "<td class=\"npb-smtp-data\">".$myrow['smtp_connection_port']."</td>";
		$response .= "<td class=\"npb-smtp-data\">".$auth."</td>";
		$response .= "<td class=\"npb-smtp-data\">".$ssl."</td>";
		$response .= "</tr>";
	}
	else
	{
		$response .= "<tr>
		<td><input type=\"hidden\" id=\"smtp_table_empty\">No SMTP connection defined.</td>
		<td></td>
		<td></td>
		<td></td>
		</tr>";
	}
	$response .= "</table>";

	return $response;
}

/**********************************************************************************************************
Function Name:
	TestSmtpConnectionHtml
Description:
	Attenpts to send an email via SMTP - Returns progress/result as HTML
Arguments:
	$db	[IN] [Resource]	DB connection	
Returns:		
	[String] HTML string containing the connection result
Change Log:
	09/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function TestSmtpConnectionHtml($db)
{
    return "SMTP test unavailable: legacy mail library removed.";
}

/**********************************************************************************************************
Function Name:
	SaveSmtpConnectionXml
Description:
	Writes the supplied SMTP connection settings to the DB. Checks if settings are valid:
	HTML reponse is stored in the <html> XML element
	Result status is stored in <result> XML element
Arguments:
	$db	[IN] [RESOURCE]	DB resource
Returns:
	[String]	XML string containing the HTML response and if any settings are not valid
Change Log:
	10/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function SaveSmtpConnectionXml($db)
{
	header("Content-type: text/xml");

	$auth      = ( $_GET["smtp_connection_auth"]      == "true" ) ? '1' : '0';
	$use_ssl   = ( $_GET["smtp_connection_use_ssl"]   == "true" ) ? '1' : '0';
	$start_tls = ( $_GET["smtp_connection_start_tls"] == "true" ) ? '1' : '0';

	// Validate supplied details
	if($auth == 1 and (empty($_GET["smtp_connection_user"]) or empty($_GET["smtp_connection_password"])))
	{
		$errorlist .= "If you select authentication, you need a username and password<br />"; 
	}
	if(empty($_GET["smtp_connection_server"]) or empty($_GET["smtp_connection_port"]))
	{
		$errorlist .= "The SMTP server/port cannot be blank<br />"; 
	}
	if (!preg_match("/^[1-9]([0-9]+)?$/",$_GET["smtp_connection_port"]))
	{
		$errorlist .= "The SMTP port must be a number<br />";
	}
	if(!isEmailAddress($_GET["smtp_connection_from"]))
	{
		$errorlist .= "Please enter a valid email address for the 'From' field";
	}

	if(isset($errorlist)) return "<SaveSmtpConnection><html>$errorlist</html><result>false</result></SaveSmtpConnection>";

	$aes_key = GetAesKey();
	if (isset($_GET["smtp_connection_id"]) and strlen($_GET["smtp_connection_id"]) > 0)
	{
		// UPDATE query - connection already exists so modify
		LogEvent("admin_config_data.php","SaveSmtpConnectionXml","Edit Connection: ".$_GET["smtp_connection_server"]);
		$sql  = "UPDATE `smtp_connection` SET 
						`smtp_connection_auth`='".$auth."',
						`smtp_connection_port`='".$_GET["smtp_connection_port"]."',
						`smtp_connection_server`='".$_GET["smtp_connection_server"]."',
						`smtp_connection_user`=AES_ENCRYPT('".$_GET["smtp_connection_user"]."','".$aes_key."'),
						`smtp_connection_password`=AES_ENCRYPT('".$_GET["smtp_connection_password"]."','".$aes_key."'),
						`smtp_connection_realm`='".$_GET["smtp_connection_realm"]."',
						`smtp_connection_use_ssl`='".$use_ssl."',
						`smtp_connection_start_tls`='".$start_tls."',
						`smtp_connection_from`='".$_GET["smtp_connection_from"]."',
						`smtp_connection_security`='".$_GET["smtp_connection_security"]."'
						WHERE smtp_connection_id='".$_GET["smtp_connection_id"]."'";	
	}
	else
	{
		// INSERT query - new connection
		LogEvent("admin_config_data.php","SaveSmtpConnectionXml","New Connection: ".$_GET["smtp_connection_server"]);
		$sql  = "INSERT INTO `smtp_connection` (
						`smtp_connection_auth`,
						`smtp_connection_port`,
						`smtp_connection_server`,
						`smtp_connection_from`,
						`smtp_connection_security`,
						`smtp_connection_user`,
						`smtp_connection_password`,
						`smtp_connection_realm`,
						`smtp_connection_start_tls`,
						`smtp_connection_use_ssl`)
						VALUES (
						'".$auth."', 
						'".$_GET["smtp_connection_port"]."', 
						'".$_GET["smtp_connection_server"]."', 
						'".$_GET["smtp_connection_from"]."', 
						'".$_GET["smtp_connection_security"]."', 
						AES_ENCRYPT('".$_GET["smtp_connection_user"]."','".$aes_key."'),
						AES_ENCRYPT('".$_GET["smtp_connection_password"]."','".$aes_key."'),
						'".$_GET["smtp_connection_realm"]."','".$start_tls."','".$use_ssl."')";
	}
	mysqli_query($db,$sql);
	return "<SaveSmtpConnection><html></html><result>true</result></SaveSmtpConnection>";
}

/**********************************************************************************************************
Function Name:
	DeleteSmtpConnectionHtml
Description:
	Deletes the SMTP connection from the DB
Arguments:
	$db	[IN] [RESOURCE]	DB resource
Returns:	None
Change Log:
	10/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function DeleteSmtpConnectionHtml($db)
{
	LogEvent("admin_config_data.php","DeleteSmtpConnectionHtml","Delete SMTP Connection: ".$_GET["smtp_connection_id"]);
	$sql  = "DELETE FROM smtp_connection WHERE smtp_connection_id='".$_GET["smtp_connection_id"]."'";
	mysqli_query($db,$sql);
}

/**********************************************************************************************************
Function Name:
	GetSmtpConnectionXml
Description:
	Gets the SMTP connection details from the DB by $_GET["smtp_connection_id"]
	Returns an XML string containing the info	
Arguments:
	$db	[IN] [RESOURCE]	DB resource
Returns:
	[String]	XML string containing connection details
Change Log:
	10/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function GetSmtpConnectionXml($db)
{
	header("Content-type: text/xml");
	$aes_key = GetAesKey();

	$sql = "SELECT smtp_connection_server, 
			AES_DECRYPT(smtp_connection_user,'".$aes_key."') AS smtp_user,
			AES_DECRYPT(smtp_connection_password,'".$aes_key."') AS smtp_password,
			smtp_connection_use_ssl, smtp_connection_auth, smtp_connection_port,
			smtp_connection_from, smtp_connection_start_tls, smtp_connection_security,
			smtp_connection_realm
		FROM smtp_connection
		WHERE smtp_connection_id='".$_GET["smtp_connection_id"]."'";
	$result = mysqli_query($db,$sql);
	
	if ($myrow = mysqli_fetch_array($result))
	{
		$response .= "<connection>";
		$response .= "<smtp_connection_server>".$myrow['smtp_connection_server']."</smtp_connection_server>";
		$response .= "<smtp_connection_user>".$myrow['smtp_user']."</smtp_connection_user>";
		$response .= "<smtp_connection_password>".$myrow['smtp_password']."</smtp_connection_password>";
		$response .= "<smtp_connection_realm>".$myrow['smtp_connection_realm']."</smtp_connection_realm>";
		$response .= "<smtp_connection_use_ssl>".$myrow['smtp_connection_use_ssl']."</smtp_connection_use_ssl>";
		$response .= "<smtp_connection_start_tls>".$myrow['smtp_connection_start_tls']."</smtp_connection_start_tls>";
		$response .= "<smtp_connection_security>".$myrow['smtp_connection_security']."</smtp_connection_security>";
		$response .= "<smtp_connection_port>".$myrow['smtp_connection_port']."</smtp_connection_port>";
		$response .= "<smtp_connection_auth>".$myrow['smtp_connection_auth']."</smtp_connection_auth>";
		$response .= "<smtp_connection_from>".$myrow['smtp_connection_from']."</smtp_connection_from>";
		$response .= "</connection>";
	}

	return $response;
}

/**********************************************************************************************************
Function Name:
	SaveConfiguration
Description:
	Verify form before saving. Writes some admin config options to the DB before saving to include_config.php. 
Arguments:
	$db	[IN] [RESOURCE]	DB resource
Returns:
	[String]	XML string containing the HTML response and if any settings are not valid
Change Log:
	01/12/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function SaveConfigurationXml($db)
{
	header("Content-type: text/xml");

	$errors      = array();
	$config_file = "include_config.php";
	global $TheApp;

	$runas_service = ( $_GET["audit_runas_service"] == "on" ) ? '1' : '0';
	$script_only   = ( $_GET["audit_script_only"]   == "on" ) ? '1' : '0';

	$audit_cfg = GetAuditSettingsFromDb();

	$xml_result = "<SaveConfiguration>";

	// *************** Check include_config.php file ***************************************
	if ( !is_writeable($config_file) )
	{ 
		array_push($errors, "Cannot write to configuration file \"$config_file\"");
	}

	// *************** Check Audit settings ************************************************
	if ( is_null($audit_cfg) )
	{ 
		array_push($errors, "No audit settings in the database. Upgrade your database first.");
	}
	if (empty($_GET['audit_base_url'])) 
	{
		array_push($errors,"The base URL for audits cannot be blank"); 
	} 
	if ( $script_only != $audit_cfg['script_only'] && $audit_cfg['active'] )
	{
		array_push($errors,"Stop the Web-Schedule service before choosing to enable/disable script only use");
	}
	if ( $TheApp->OS == 'Windows' )
	{
		if ( $runas_service != $audit_cfg['service_enabled'] && $audit_cfg['active'] )
		{
			array_push($errors,"Stop the Web-Schedule service before changing service management");
		}
		if ( $service_enable == 1 ) 
		{
			if ( $audit_cfg['service_name'] != $_GET['audit_service_name'] and $audit_cfg['active'] ) 
			{
				array_push($errors,"Stop the Web-Schedule service before changing service names");
			}
		}
	}
	if ( $TheApp->OS == 'Windows' && $runas_service && empty($_GET['audit_service_name']) ) 
	{
		array_push($errors,"The service name cannot be left blank when enabled");
	}
	if ( !preg_match("/^[1-9]([0-9]+)?$/",$_GET['audit_poll_interval']) ) 
	{
		array_push($errors,"The polling interval must be a number with no leading zeros");
	}

	// *************** Check MySQL settings ************************************************
	if (empty($_GET['mysqli_server_post'])) 
	{
		array_push($errors,"You must declare a MySQL Server"); 
	} 
	if (empty($_GET['mysqli_database_post'])) 
	{
		array_push($errors,"You must declare a MySQL Database"); 
	}
	if (empty($_GET['mysqli_user_post']))
	{
		array_push($errors,"You must declare a MySQL Username"); 
	}
	if (empty($_GET['mysqli_password_post']))
	{
		array_push($errors,"You must declare a MySQL Password"); 
	}

	// *************** Return any errors ************************************************
	if(count($errors)>0)
	{
		foreach ( $errors as $error ) $xml_result = $xml_result . "<error>$error</error>";
		$xml_result = $xml_result . '<result>false</result></SaveConfiguration>';
		return $xml_result;
	}

	$db  = GetOpenAuditDbConnection();
	$sql = "UPDATE audit_settings 
		SET audit_settings_service_name='{$_GET['audit_service_name']}',
		audit_settings_script_only='$script_only',
		audit_settings_runas_service='$runas_service',
		audit_settings_base_url='{$_GET['audit_base_url']}',
		audit_settings_interval='{$_GET['audit_poll_interval']}'";

	$status = ( mysqli_query($db,$sql)  ) ? 'true' : 'false';
	$xml_result .= ( $status == 'false' ) ? '<error>Failed to save settings to the database: '
		. mysqli_error($db) . '</error>' : '';

	return $xml_result . "<result>$status</result></SaveConfiguration>";
}

?>
