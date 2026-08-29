/**********************************************************************************************************
Module:	admin_config.js

Description:
	The code in this module is used by admin_config.php (which is the Admin -> Config page).
	It provides:
	- The functionality of the page navigation tab (to switch between config pages)
	- Functions that are called in response to GUI actions (popup menu, buttons etc)

Recent Changes:

	[Nick Brown]	01/05/2009
	Added CheckOpenSslStatus() function.
	[Chad Sikorra]	15/11/2009 Added SMTP functions
	[Chad Sikorra]	17/11/2009 Added audit functions. 'Save' now executes an ajax call before form submission
	
**********************************************************************************************************/
//debugger; 


/**********************************************************************************************************

	Jquery 'ready' function - things to execute when the DOM is ready

**********************************************************************************************************/

$(document).ready(function() {
	$("#npb_config_save_error").dialog({
		width: 350,
		bgiframe: true,
		draggable: false,
		resizable: false,
		autoOpen: false,
		dialogClass: 'ui-state-error',
		modal: true,
		title: 'Error Saving Configuration',
		buttons: { 'Ok' : function() { $('#npb_config_save_error').dialog('close'); } }, 
		position: ['center','middle']
	}).prev().addClass('ui-state-error');

	$("#admin_config").submit(function() { if ( !SaveConfiguration() ) { return false; } });
	$('a.tooltip').tooltip({
		'showURL' : false,
		'extraClass' : 'tooltip-style',
		'delay' : 0,
		'fade' : 250
	}).click( function() { return false; });
});

/**********************************************************************************************************
Function Name:
	SelectNavTab
Description:
	Called in response to one of the page navigation tabs being clicked. Ensures that the corresponding config page is 
	displayed.
Arguments:
	e	[IN] [object]	DOM object that fired the event
Returns:	None
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function SelectNavTab(e)
{			
	if(document.getElementById)
	{
		// Determine which tab has been selected
		var Id = new String(e.id); // e.id example npb_config_general_tab
		var aId = Id.split("_");
		var id = 'npb_config_' + aId[aId.length-2] + '_div'; // id of the DIV page to display e.g. config_general
		
		// Clear current tabs state
		// Get npb_tab_nav <UL> - get all ULs and loup thru
		oULs=document.getElementsByTagName("ul");
		for (var i = 0; i < oULs.length; i++)
		{if(oULs.item(i).className=='npb_tab_nav'){oUL=oULs.item(i)}}
		// Get all child <A> elements and set font to normal
		oAs=oUL.getElementsByTagName("a");
		for (var i = 0; i <oAs.length; i++){oAs.item(i).style.fontWeight='normal';};
		// Hide all config DIVS
		document.getElementById('npb_config_general_div').style.display="none";
		document.getElementById('npb_config_security_div').style.display="none";
		document.getElementById('npb_config_homepage_div').style.display="none";
		document.getElementById('npb_config_connections_div').style.display="none";
		document.getElementById('npb_config_save_div').style.display="none";
		
		// Now display selected DIV and highlight tab
		if(e)
		{
			e.style.fontWeight='bold';
			// Hide the "Save" button when the LDAP page is selected
			if (id != 'npb_config_connections_div') {document.getElementById('npb_config_save_div').style.display="block";}
			a=document.getElementById(id);
			a.style.display="block";
		}
	}
}

/**********************************************************************************************************

	GUI functions	- Functions that are called in response to GUI actions (popup menu, buttons etc)

**********************************************************************************************************/

// Define connection menu & path menu HTML 
connection_menu  = "<div id='popupmenu_id' style='display:none'>guid</div>";
connection_menu += "<a href='javascript://' OnClick='EditLdapConnection();'>Edit Connection</a>";
connection_menu += "<a href='javascript://' OnClick='DeleteLdapConnection();'>Delete Connection</a>";
connection_menu += "<a href='javascript://' OnClick='NewLdapPath();'>Add New Path</a>";

path_menu  = "<div id='popupmenu_id' style='display:none'>guid</div>";
path_menu += "<a href='javascript://' OnClick='EditLdapPath();'>Edit Path</a>";
path_menu += "<a href='javascript://' OnClick='DeleteLdapPath();'>Delete Path</a>";

// Define smtp menu HTML
smtp_menu  = "<div id='popupmenu_id' style='display:none'>guid</div>";
smtp_menu += "<a href='javascript://' OnClick='EditSmtpConnection();'>Edit Connection</a>";
smtp_menu += "<a href='javascript://' OnClick='DeleteSmtpConnection();'>Delete Connection</a>";

/**********************************************************************************************************
Function Name:
	RefreshLdapConnectionsList
Description:
	Bit of a kludge to refresh connection list after an update - can't think of a better solution right now
Called by: DeleteLdapPath, SaveLdapPath, DeleteLdapConnection, SaveLdapPath, DeleteLdapConnection, SaveLdapConnection
Arguments:	None
Returns:	None
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function RefreshLdapConnectionsList()
{setTimeout('ListLdapConnections()', 1000);}

/**********************************************************************************************************
Function Name:
	RefreshSmtpConnectionList
Description:
	Called by: DeleteSmtpConnection, SaveSmtpConnection
Arguments:	None
Returns:	None
Change Log:
	10/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function RefreshSmtpConnectionList()
{setTimeout('ListSmtpConnections()', 1000);}

/**********************************************************************************************************
Function Name:
	ListLdapConnections
Description:
	Displays list of LDAP connections and associated LDAP paths. The 'npb_ldap_connections_div' DIV is populated with HTML returned
	from the server by the HttpRequestor object
Called by: RefreshLdapConnectionsList
Arguments:	None
Returns:	None
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function ListLdapConnections()
{
	var LdapConnections=new HttpRequestor('npb_ldap_connections_div');
	LdapConnections.send('admin_config_data.php?sub=f1');
}

/**********************************************************************************************************
Function Name:
	DeleteLdapPath
Description:
	Deletes the selected LDAP path. The uid of the path to delete is retrieved from the hidden 'uid' DIV. This is passed to the 
	server using the XmlRequestor object.
	
	Called in response to the "Delete Path" option on the LDAP path menu
Arguments:	None
Returns:	None
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function DeleteLdapPath()
{
	document.getElementById("npb_ldap_path_config_div").style.display = 'none';	
	document.getElementById("npb_ldap_connection_config_div").style.display = 'none';
	var ldap_path_id = document.getElementById("popupmenu_id").innerHTML;
	var pathxml = new XmlRequestor('admin_config_data.php?sub=f9&ldap_path_id=' + ldap_path_id);
	RefreshLdapConnectionsList();
}

/**********************************************************************************************************
Function Name:
	NewLdapPath
Description:
	Adds a new LDAP path to the selected LDAP connection. The uid of the selected connection is retrieved from the hidden 'uid' 
	DIV. This is passed to the server using the XmlRequestor object. The domain default NC retrieved from the returned XML.
	The 'npb_ldap_path_config_div' DIV is displayed and the path input is populated witht the domain default NC.
	
	Called in response to the "Add New Path" option on the LDAP connection menu
Arguments:	None
Returns: 	None
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function NewLdapPath()
{
	CloseConnectionDivs();
	document.getElementById("npb_ldap_path_config_div").style.display = 'block';	
	var ldap_path_connection_id = document.getElementById("popupmenu_id").innerHTML;
	var domainxml = new XmlRequestor('admin_config_data.php?sub=f6&ldap_connection_id=' + ldap_path_connection_id);
	document.getElementById("ldap_path_connection_id").value = ldap_path_connection_id;
	document.getElementById("ldap_path_dn").value = domainxml.GetValue("domain_nc");
	document.getElementById("ldap_path_audit").checked = true;
}

/**********************************************************************************************************
Function Name:
	SaveLdapPath
Description:
	Saves the LDAP path details to the db. Retrieves the settings from the HTML form and uses a XmlRequestor object to 
	post them to the server. The 'npb_ldap_path_config_div' DIV is then hidden.
	
	Called in response to the "Save" button on the 'npb_ldap_path_config_div' DIV
Arguments:	None
Returns: 	None
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function SaveLdapPath()
{
	var ldap_params = '&ldap_path_connection_id=' + document.getElementById("ldap_path_connection_id").value;
	ldap_params += '&ldap_path_id='+ document.getElementById("ldap_path_id").value;
	ldap_params += '&ldap_path_dn=' + escape(document.getElementById("ldap_path_dn").value);
	var ldap_path_audit_value = document.getElementById("ldap_path_audit").checked ? "1" : "0";
	ldap_params += '&ldap_path_audit=' + ldap_path_audit_value;
	var xmlpath = new XmlRequestor('admin_config_data.php?sub=f7' + ldap_params);
	document.getElementById('npb_ldap_path_config_div').style.display = 'none';
	RefreshLdapConnectionsList();
}

/**********************************************************************************************************
Function Name:
	EditLdapPath
Description:
	Displays the 'npb_ldap_path_config_div' DIV to edit the selected LDAP path. The uid of the selected path is retrieved from the 
	hidden 'uid' DIV. This uid is passed to an XmlRequestor object to retrieve the path settings from the server as XML string.
	
	Called in response to the "Edit Path" option on the LDAP path menu
Arguments:	None
Returns: 	None
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function EditLdapPath()
{
	CloseConnectionDivs();
	document.getElementById("npb_ldap_path_config_div").style.display = 'block';	
	var ldap_path_id = document.getElementById("popupmenu_id").innerHTML;
	var pathxml = new XmlRequestor('admin_config_data.php?sub=f8&ldap_path_id=' + ldap_path_id);
	document.getElementById("ldap_path_id").value = ldap_path_id;
	document.getElementById("ldap_path_dn").value = pathxml.GetValue("ldap_path_dn");
	document.getElementById("ldap_path_audit").checked = (pathxml.GetValue("ldap_path_audit") == "1") ? true : false;
}

/**********************************************************************************************************
Function Name:
	DeleteLdapConnection
Description:
	Deletes the selected LDAP connection. The uid of the selected connection is retrieved from the hidden 'uid' DIV. 
	This uid is passed to the server using an HttpRequestor object.
	
	Called in response to the "Delete Connection" option on the LDAP connection menu
Arguments:	None
Returns: 	None
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function DeleteLdapConnection()
{
	document.getElementById("npb_ldap_path_config_div").style.display = 'none';	
	document.getElementById("npb_ldap_connection_config_div").style.display = 'none';
	var ldap_connection_id = document.getElementById("popupmenu_id").innerHTML;
	var LdapDelete=new HttpRequestor('server_connection_results');
	LdapDelete.send('admin_config_data.php?sub=f4&ldap_connection_id=' + ldap_connection_id);
	RefreshLdapConnectionsList();
}

/**********************************************************************************************************
Function Name:
	EditLdapConnection
Description:
	Displays the 'npb_ldap_connection_config_div' DIV to edit the selected LDAP connection. The uid of the selected connection is 
	retrieved from the hidden 'uid' DIV.  This uid is passed to the server using an XmlRequestor object. The current conenection
	settings are extracted from the returned XML data.

	Called in response to the "Edit Connection" option on the LDAP connection menu
Arguments:	None
Returns: 	None
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function EditLdapConnection()
{
	CloseConnectionDivs();
	CheckLdapStatus();
	document.getElementById("npb_ldap_connection_config_div").style.display = 'block';
	document.getElementById("npb_connection_test_div").style.display = 'block';
	var ldap_connection_id = document.getElementById("popupmenu_id").innerHTML;
	var xmlconfig = new XmlRequestor('admin_config_data.php?sub=f5&ldap_connection_id=' + ldap_connection_id);
	document.getElementById("ldap_connection_id").value = ldap_connection_id;
	document.getElementById("ldap_connection_server").value = xmlconfig.GetValue("ldap_connection_server");
	document.getElementById("ldap_connection_user").value = xmlconfig.GetValue("ldap_connection_user");
	document.getElementById("ldap_connection_password").value = xmlconfig.GetValue("ldap_connection_password");
	document.getElementById("ldap_connection_use_ssl").checked = (xmlconfig.GetValue("ldap_connection_use_ssl") == "1") ? true : false;
}

/**********************************************************************************************************
Function Name:
	NewServerConnection
Description:
	Displays the right DIV for the connection type they want
	Called in response to the "Continue" button
Arguments:	None
Returns: 	None
Change Log:
	09/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function NewServerConnection()
{
	CloseConnectionDivs();
	document.getElementById("npb_choose_server_div").style.display = 'block';
}

/**********************************************************************************************************
Function Name:
	CloseConnectionDivs
Description:
	Hide any open connection config divs, clear the test results
	Called in reponse to 'cancel' button or when doing an edit/add
Arguments:	None
Returns: 	None
Change Log:
	10/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function CloseConnectionDivs()
{
	for (i = 0; i < document.getElementById("server_connection_type").length; i++)
	{
		var srvtype = document.getElementById("server_connection_type").options[i].value;
		document.getElementById("npb_" + srvtype + "_connection_config_div").style.display = 'none';
	} 
	document.getElementById("npb_connection_test_div").style.display = 'none';
	document.getElementById("npb_ldap_path_config_div").style.display = 'none';
	document.getElementById("npb_choose_server_div").style.display = 'none';
	document.getElementById("server_connection_results").innerHTML = '';
}

/**********************************************************************************************************
Function Name:
	ChooseServerConnection
Description:
	Displays the right DIV for the connection type they want
	Called in response to the "Continue" button
Arguments:	None
Returns: 	None
Change Log:
	09/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function ChooseServerConnection()
{
	document.getElementById("npb_choose_server_div").style.display = 'none';
	document.getElementById("npb_connection_test_div").style.display = 'block';

	switch (document.getElementById("server_connection_type").value)
	{
		case  "ldap": NewLdapConnection(); break;
		case  "smtp": NewSmtpConnection(); break;
	}

}

/**********************************************************************************************************
Function Name:
	NewLdapConnection
Description:
	Displays the 'npb_ldap_connection_config_div' DIV to enter the LDAP connection settings.
	Called in response to the "New Connection" button
Arguments:	None
Returns: 	None
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function NewLdapConnection()
{
	CheckLdapStatus();
	document.getElementById("npb_ldap_path_config_div").style.display = 'none';	
	document.getElementById("npb_ldap_connection_config_div").style.display = 'block';
	document.getElementById("ldap_connection_server").value = 'LDAP Server FQDN';
	document.getElementById("ldap_connection_user").value = 'LDAP user account';
	document.getElementById("ldap_connection_password").value = 'LDAP password';	
	
}

/**********************************************************************************************************
Function Name:
	SaveLdapConnection
Description:
	Uses a XmlRequestor object to post the values from the'npb_ldap_connection_config_div' DIV form controls to the server. 
	The returned XML contains a <result> element and a <html> element
	If result is "true" then hides the 'npb_ldap_connection_config_div' DIV.
	Else the text from the <html> element is displayed in the results pane 
	Called in response to the "Save" button on the 'npb_ldap_connection_config_div' DIV
Arguments:	None
Returns: 	None
Change Log:
	20/08/2008			New function	[Nick Brown]
	10/09/2008			Now uses XmlRequestor instead of HttpRequestor	[Nick Brown]
**********************************************************************************************************/
function SaveLdapConnection()
{
	var ldap_params = '&ldap_connection_server=' + document.getElementById("ldap_connection_server").value;
	ldap_params += '&ldap_connection_user=' + document.getElementById("ldap_connection_user").value;
	ldap_params += '&ldap_connection_password=' + document.getElementById("ldap_connection_password").value;
	var use_ssl_value = document.getElementById("ldap_connection_use_ssl").checked ? "1" : "0";
	ldap_params += '&ldap_connection_use_ssl=' + use_ssl_value;
	ldap_params += '&ldap_connection_id=' + document.getElementById("ldap_connection_id").value;
	var LdapSave = new XmlRequestor('admin_config_data.php?sub=f3' + ldap_params);
	if(LdapSave.ParseError != '')
	{
		document.getElementById("server_connection_results").innerHTML = LdapSave.ParseError.replace(/</g, "&lt;");
	}
	else
	{
		// Check returned XML for result
		if(LdapSave.GetValue("result")=="false")
		{
			// Failed - Get returned HTML from XML doc and display failure info
			html = new String(LdapSave.SerializeXmlNode(LdapSave.GetNode("html")));
			html = html.substring(6,html.length-7);
			document.getElementById("server_connection_results").innerHTML = html;
		}
		else
		{
			// Success - hide connection config div and refresh list
			CloseConnectionDivs();
			RefreshLdapConnectionsList();
		}
	}
}

/**********************************************************************************************************
Function Name:
	TestLdapConnection
Description:
	Uses a HttpRequestor object to post the values from the'npb_ldap_connection_config_div' DIV form controls to the server. Which 
	tests the LDAP credentials and returns the results to the 'server_connection_results' DIV as HTML.

	Called in response to the "Test Connection" button on the 'npb_ldap_connection_config_div' DIV
Arguments:	None
Returns: 	None
Change Log:
	20/08/2008			New function	[Nick Brown]
	12/10/2009			Add missing GET var for ssl	[Chad Sikorra]
**********************************************************************************************************/
function TestLdapConnection()
{
	document.getElementById("server_connection_results").innerHTML = 'Testing connection ...';
	var LdapTest=new HttpRequestor('server_connection_results');
	var ldap_params = '&ldap_connection_server=' + document.getElementById("ldap_connection_server").value;
	ldap_params += '&ldap_connection_user=' + document.getElementById("ldap_connection_user").value;
	ldap_params += '&ldap_connection_password=' + document.getElementById("ldap_connection_password").value;
	var use_ssl_value = document.getElementById("ldap_connection_use_ssl").checked ? "1" : "0";
	ldap_params += '&ldap_connection_use_ssl=' + use_ssl_value;
	LdapTest.send('admin_config_data.php?sub=f2' + ldap_params);
}

/**********************************************************************************************************
Function Name:
	CheckLdapStatus
Description:
	Called when adding/editing an LDAP connection to see if LDAP extensions are enabled
	Toggles div visibility within npb_ldap_connection_config
Arguments:	None
Returns: 	None
Change Log:
	10/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function CheckLdapStatus()
{
	var LdapStatus = new XmlRequestor('admin_config_data.php?sub=f16');
	var state = LdapStatus.GetValue("result");
	document.getElementById("npb_ldap_disabled").style.display = (state != 'Y') ? 'block' : 'none';
	document.getElementById("npb_ldap_enabled").style.display  = (state == 'Y') ? 'block' : 'none';
}

/**********************************************************************************************************
Function Name:
	CheckOpenSslStatus
Description:
	Called when 'ldap_connection_use_ssl' checkbox is clicked. If checkbox has been checked,  then checks host server 
	OpenSSL configuration status. If SSL isn't configured then user is notified and checkbox is unchecked.
Arguments:
	checkObj	[IN] [object]	Checkbox object to toggle
Returns: 	None
Change Log:
	01/05/2009			New function	[Nick Brown]
	14/10/2009			Add object argument	[Chad Sikorra]
**********************************************************************************************************/
function CheckOpenSslStatus(checkObj)
{
	if (checkObj.checked)
	{
		var SslStatus = new XmlRequestor('admin_config_data.php?sub=f10');
		if(SslStatus.GetValue("result") != "Y")
		{
			var SslMsg = "The PHP configuration of your host doesn't appear to have the OpenSSL extension\n";
			SslMsg += "correctly configured. Please refer to the PHP documentation for details on how to do this.";
			alert(SslMsg);
			checkObj.checked = false;
		}
	}
}

/**********************************************************************************************************
Function Name:
	ListSmtpConnections
Description:
	Displays list of SMTP connections. The 'npb_smtp_connection_div' DIV is populated with HTML returned
	from the server by the HttpRequestor object
Called by: RefreshSmtpConnectionList
Arguments:	None
Returns:	None
Change Log:
	20/08/2008			New function	[Nick Brown]
**********************************************************************************************************/
function ListSmtpConnections()
{
	var SmtpConnections=new HttpRequestor('npb_smtp_connection_div');
	SmtpConnections.send('admin_config_data.php?sub=f11');
}

/**********************************************************************************************************
Function Name:
	SaveSmtpConnection
Description:
	Uses a XmlRequestor object to post the values from the'npb_smtp_connection_config_div' DIV form controls to the server. 
	The returned XML contains a <result> element and a <html> element
	If result is "true" then hides the 'npb_smtp_connection_config_div' DIV.
	Else the text from the <html> element is displayed in the results pane 
	Called in response to the "Save" button on the 'npb_smtp_connection_config_div' DIV
Arguments:	None
Returns: 	None
Change Log:
	20/08/2008			New function	[Chad Sikorra]
**********************************************************************************************************/
function SaveSmtpConnection()
{
	var smtp_params = '&smtp_connection_server=' + document.getElementById("smtp_connection_server").value;
	smtp_params += '&smtp_connection_port=' + document.getElementById("smtp_connection_port").value;
	smtp_params += '&smtp_connection_from=' + document.getElementById("smtp_connection_from").value;
	smtp_params += '&smtp_connection_user=' + document.getElementById("smtp_connection_user").value;
	smtp_params += '&smtp_connection_password=' + document.getElementById("smtp_connection_password").value;
	smtp_params += '&smtp_connection_realm=' + document.getElementById("smtp_connection_realm").value;
	smtp_params += '&smtp_connection_auth=' + document.getElementById("smtp_connection_auth").checked;
	smtp_params += '&smtp_connection_use_ssl=' +  document.getElementById("smtp_connection_use_ssl").checked;
	smtp_params += '&smtp_connection_start_tls=' +  document.getElementById("smtp_connection_use_ssl").checked;
	smtp_params += '&smtp_connection_security=' +  document.getElementById("smtp_connection_security").value;
	smtp_params += '&smtp_connection_id=' + document.getElementById("smtp_connection_id").value;
	var SmtpSave = new XmlRequestor('admin_config_data.php?sub=f13' + smtp_params);
	if(SmtpSave.ParseError != '')
	{
		document.getElementById("server_connection_results").innerHTML = SmtpSave.ParseError.replace(/</g, "&lt;");
	}
	else
	{
		// Check returned XML for result
		if(SmtpSave.GetValue("result")=="false")
		{
			// Failed - Get returned HTML from XML doc and display failure info
			html = new String(SmtpSave.SerializeXmlNode(SmtpSave.GetNode("html")));
			html = html.substring(6,html.length-7);
			document.getElementById("server_connection_results").innerHTML = html;
		}
		else
		{
			// Success - hide connection config div and refresh list
      CloseConnectionDivs();
			RefreshSmtpConnectionList();
		}
	}
}

/**********************************************************************************************************
Function Name:
	NewSmtpConnection
Description:
	Displays the 'npb_smtp_connection_config_div' DIV to enter the SMTP connection settings.
	Called in response to the "Continue" button
Arguments:	None
Returns: 	None
Change Log:
	09/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function NewSmtpConnection()
{
	document.getElementById("npb_smtp_connection_config_div").style.display = 'block';

	if ( !document.getElementById("smtp_table_empty") )
	{
		document.getElementById("npb_connection_test_div").style.display = 'none';
		document.getElementById("npb_smtp_exists").style.display = 'block';
		document.getElementById("npb_smtp_connection_form").style.display = 'none';
	}
	else
	{
		document.getElementById("npb_smtp_exists").style.display = 'none';
		document.getElementById("npb_smtp_connection_form").style.display = 'block';
		document.getElementById("smtp_connection_email").value = '';
		document.getElementById("smtp_connection_server").value = 'SMTP Server Name/IP';
		document.getElementById("smtp_connection_from").value = 'Open-AudIT@mydomain.com';
		document.getElementById("smtp_connection_auth").checked = false;
		document.getElementById("smtp_connection_use_ssl").checked = false;
		document.getElementById("smtp_connection_port").value = '25';
		document.getElementById("smtp_connection_user").value = 'SMTP user account';
		document.getElementById("smtp_connection_password").value = 'SMTP user password';	
	}

	ToggleSmtpAuth(document.getElementById("smtp_connection_auth"));
}

/**********************************************************************************************************
Function Name:
	TestSmtpConnection
Description:
	Uses a HttpRequestor object to post the values from the'npb_smtp_connection_config_div' DIV form controls to the server. Which 
	tests SMTP by sending an email and sends the results to the 'server_connection_results' DIV as HTML.

	Called in response to the "Test Connection" button on the 'npb_smtp_connection_config_div' DIV
Arguments:	None
Returns: 	None
Change Log:
	20/08/2008			New function	[Chad Sikorra]
**********************************************************************************************************/
function TestSmtpConnection()
{
	document.getElementById("server_connection_results").innerHTML = 'Testing SMTP ...';
	var SmtpTest=new HttpRequestor('server_connection_results');
	var smtp_params = '&smtp_connection_server=' + document.getElementById("smtp_connection_server").value;
	smtp_params += '&smtp_connection_user=' + document.getElementById("smtp_connection_user").value;
	smtp_params += '&smtp_connection_password=' + document.getElementById("smtp_connection_password").value;
	smtp_params += '&smtp_connection_realm=' + document.getElementById("smtp_connection_realm").value;
	smtp_params += '&smtp_connection_port=' + document.getElementById("smtp_connection_port").value;
	smtp_params += '&smtp_connection_from=' + document.getElementById("smtp_connection_from").value;
	smtp_params += '&smtp_connection_email=' + document.getElementById("smtp_connection_email").value;
	smtp_params += '&smtp_connection_security=' + document.getElementById("smtp_connection_security").value;
	smtp_params += '&smtp_connection_auth=' + document.getElementById("smtp_connection_auth").checked;
	smtp_params += '&smtp_connection_use_ssl=' + document.getElementById("smtp_connection_use_ssl").checked;
	smtp_params += '&smtp_connection_start_tls=' + document.getElementById("smtp_connection_start_tls").checked;
	SmtpTest.send('admin_config_data.php?sub=f12' + smtp_params);
}

/**********************************************************************************************************
Function Name:
	DeleteSmtpConnection
Description:
	Deletes the SMTP connection. The uid of the selected connection is retrieved from the hidden 'uid' DIV. 
	This uid is passed to the server using an HttpRequestor object.
	
	Called in response to the "Delete Connection" option on the SMTP menu
Arguments:	None
Returns: 	None
Change Log:
	10/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function DeleteSmtpConnection()
{
	document.getElementById("npb_smtp_connection_config_div").style.display = 'none';
	var smtp_connection_id = document.getElementById("popupmenu_id").innerHTML;
	var SmtpDelete=new HttpRequestor('server_connection_results');
	SmtpDelete.send('admin_config_data.php?sub=f14&smtp_connection_id=' + smtp_connection_id);
	RefreshSmtpConnectionList();
}

/**********************************************************************************************************
Function Name:
	EditSmtpConnection
Description:
	Displays the 'npb_smtp_connection_config_div' DIV to edit the selected SMTP connection. The uid of the selected connection is 
	retrieved from the hidden 'uid' DIV.  This uid is passed to the server using an XmlRequestor object. The current conenection
	settings are extracted from the returned XML data.

	Called in response to the "Edit Connection" option on the SMTP connection menu
Arguments:	None
Returns: 	None
Change Log:
	10/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function EditSmtpConnection()
{
	CloseConnectionDivs();
	document.getElementById("npb_smtp_exists").style.display = 'none';
	document.getElementById("npb_smtp_connection_form").style.display = 'block';
	document.getElementById("npb_smtp_connection_config_div").style.display = 'block';
	document.getElementById("npb_connection_test_div").style.display = 'block';
	var smtp_connection_id = document.getElementById("popupmenu_id").innerHTML;
	var xmlconfig = new XmlRequestor('admin_config_data.php?sub=f15&smtp_connection_id=' + smtp_connection_id);
	document.getElementById("smtp_connection_id").value = smtp_connection_id;
	document.getElementById("smtp_connection_server").value = xmlconfig.GetValue("smtp_connection_server");
	document.getElementById("smtp_connection_port").value = xmlconfig.GetValue("smtp_connection_port");
	document.getElementById("smtp_connection_from").value = xmlconfig.GetValue("smtp_connection_from");
	document.getElementById("smtp_connection_user").value = xmlconfig.GetValue("smtp_connection_user");
	document.getElementById("smtp_connection_password").value = xmlconfig.GetValue("smtp_connection_password");
	document.getElementById("smtp_connection_use_ssl").checked = (xmlconfig.GetValue("smtp_connection_use_ssl") == "1") ? true : false;
	document.getElementById("smtp_connection_auth").checked = (xmlconfig.GetValue("smtp_connection_auth") == "1") ? true : false;
	ToggleSmtpAuth(document.getElementById("smtp_connection_auth"));
}

/**********************************************************************************************************
Function Name:
	ToggleSmtpAuth
Description:
	Disable/Enable username/password fields based on if authentication is selected for SMTP
	Called in response to adding,editing a SMTP connection or clicking on the checkbox
Arguments:
	obj	[IN] [object]	Checkbox object to toggle
Returns: 	None
Change Log:
	10/10/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function ToggleSmtpAuth(obj)
{
	toggleState = ( obj.checked ) ? false : true ;
	document.getElementById("smtp_connection_user").disabled = toggleState;
	document.getElementById("smtp_connection_password").disabled = toggleState;
	document.getElementById("smtp_connection_realm").disabled = toggleState;
	document.getElementById("smtp_connection_security").disabled = toggleState;
	document.getElementById("smtp_connection_start_tls").disabled = toggleState;
}

/**********************************************************************************************************
Function Name:
	SaveConfiguration
Description:
	Called when the "Save" button is clicked. Saves certain pieces to the DB first,
	then actually submits the form if there were no issues 
Arguments:	None
Returns:	None
Change Log:
	04/12/2009			New function	[Chad Sikorra]
**********************************************************************************************************/
function SaveConfiguration()
{
	var config_params = '&' + $('#admin_config').serialize();
	var ConfigSave = new XmlRequestor('admin_config_data.php?sub=f17' + config_params);
	$('#npb_config_save_error').html('');

	// Check returned XML for result, list errors if any
	if(ConfigSave.GetValue("result")=="false")
	{
		var error_list;
		error_list  = '<p><span class="ui-icon ui-icon-alert npb-save-alert">'
		error_list += '</span><strong>Please correct the following errors</strong></p>';
		error_list += '<div id="npb-save-error-div"><ul id="npb-save-error-list">';
		$(ConfigSave.XmlDomObject).find('error').each(function(){
			error_list += '<li>' + $(this).text() + '</li>';
		});
		error_list += '</ul></div>';
		$('#npb_config_save_error').append(error_list);
		$("#npb_config_save_error").dialog('open');
		return false;
	}
	else
	{
		return true;
	}
}

/* Color theme: one native picker and one validated hex field. */
(function () {
    function normalizeHex(value) {
        value = String(value || '').trim();
        if (/^#[0-9a-f]{6}$/i.test(value)) return value.toLowerCase();
        if (/^#[0-9a-f]{3}$/i.test(value)) {
            return ('#' + value[1] + value[1] + value[2] + value[2] + value[3] + value[3]).toLowerCase();
        }
        return null;
    }

    function initAccentPicker() {
        var picker = document.getElementById('accent_color_picker');
        var input = document.getElementById('accent_color');
        if (!picker || !input) return;

        function apply(value) {
            var hex = normalizeHex(value);
            if (!hex) return false;
            picker.value = hex;
            input.value = hex;
            input.setCustomValidity('');
            return true;
        }

        picker.addEventListener('input', function () { apply(picker.value); });
        input.addEventListener('input', function () {
            var valid = apply(input.value);
            input.setCustomValidity(valid ? '' : 'Bitte einen Hex-Farbwert wie #2563eb eingeben.');
        });
        input.addEventListener('blur', function () { apply(input.value); });

        apply(input.value);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAccentPicker);
    } else {
        initAccentPicker();
    }
})();
