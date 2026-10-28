<?php
/**********************************************************************************************************
Module:	show_license.php

Description:
	Displays the GPL license from GPL.TXT

Recent Changes:

	[Nick Brown]	29/04/2009
	Removed reference to $validate - doesn't appear to be used anywhere in the application.
	
**********************************************************************************************************/

$page = "";
$extra = "";
$software = "";
$count = 0;
$total_rows = 0;
$latest_version = "08.12.10";

// Check for config, otherwise run setup
//@(include_once "include_config.php") OR die(header("Location: setup.php"));  // Modified by Nick Brown - don't want to actually include the file yet
if(!file_exists("include_config.php"))exit(header("Location: setup.php")); // Nick Brown - alternative method
include "include.php";



$software = GetGETOrDefaultValue("software","");
$sort = GetGETOrDefaultValue("sort","system_name");

echo "<td style=\"vertical-align:top;width:100%\">\n";
echo "<div class=\"main_each\">";
echo "<table ><tr><td class=\"contenthead\">\n";
echo __("License GPL").'</td></tr></table>';
echo '<table ><tr><td>';

// Now show the specified License in an iframe.
if(isset($license_text) AND $license_text!="") {
// We can alter things here if the file doesn't exist or whatever
// currently do nothing
} else {
	// If no license specified, use the gpl.txt file. 
	$license_text = "gpl.txt";
}
echo "<iframe class=\"main_each\" SRC=\"".$license_text."\" width=\"90%\" height=\"600\" 
framespacing=0 frameborder=no border=0 scrolling=auto name=license_frame longdesc=\"http://www.gnu.org/licenses/licenses.html#GPL\"></iframe>";

echo "<br><center>Open Audit License (".$license_text.") </center><br>";

if(isset($license_text) AND $license_text="gpl.txt") {
echo "<i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i>\n";              
// We can alter things here if the file doesn't exist or whatever
// currently do nothing
} else {
 //
}

//gplv3-88x31.png
echo "</td></tr></table>";
?>