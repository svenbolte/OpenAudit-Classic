<?php
/**********************************************************************************************************
Module:	include.php

Description:
	This module is included by "index.php". Verifies authentication to the system and HTML to display the application header 
	and menu.

Recent Changes:

	[Nick Brown]	02/03/2009	Only a minor change - the "logout" link in the top right of the page now displays the user's 
	role (admin/user) as well as their name.
	[Nick Brown]	17/04/2009	Minor improvement to SQL query that retrieves audited system from DB
	[Nick Brown]	29/04/2009	Moved <link>s and <script>s into <head> from "admin_config.php". Minor changes to ensure 
	valid XHTML markup. Moved javascript functions into external file "include.js"
	[Nick Brown]	01/05/2009	Incldued "application_class.php" to provide access to the global $TheApp object. 
	[Chad Sikorra]	20/11/2009	Check the filename of the current page to determine what css/js to include

**********************************************************************************************************/
include_once "application_class.php";
include_once "include_config.php";
include_once "include_lang.php";
include_once "include_functions.php";
include "include_lenovo_warranty_functions.php"; // Added by Andrew Hull to allow us to grab Dell Warranty details from the Dell website


//die(var_dump($TheApp));

// Funktion für Software-Versionen online download and import
function svversionenimport($aftertime) {
	global $db;
	echo '<div class="messagebox" style="position:absolute;left:5px;top:500px">';
	$filename = dirname(__FILE__).'/wordpresssoftware.csv';
	
	$myserver = $_SERVER['SERVER_NAME'];
	if ( $myserver == 'pat14sv' ) {
		// PB Spezial Kellerserver direkt:
		$url = 'https://ssl.pbcs.de/dcounter/softwareverzeichnis.asp?action=woocom&code=a5b584050977ca2ece290de786cc35f6';
	} else {
		$url = 'https://tech-nachrichten.de/wp-content/uploads/csv/softwareverzeichnis.txt';
	}	
	if (file_exists($filename)) {
		echo substr($url,0,66).' | ';
		echo "Update: " . date ("d.m.Y H:i:s", filemtime($filename));
		
		$tdif = (int) (time()-filemtime($filename));
		echo ' | '. sprintf('%02d:%02d:%02d', $tdif/3600, floor($tdif/60)%60, $tdif%60);
		 // erst nach 5 Minuten wieder DB-Update herunterladen
		echo ' | '. $aftertime .' min.';
		if ($tdif > ( (int) $aftertime * 60 ) ) {  
			$arrContextOptions=array( "ssl"=>array( "verify_peer"=>false, "verify_peer_name"=>false, ), );  
			$source = file_get_contents($url, false, stream_context_create($arrContextOptions));

			// --- Alternative Methode ---
			// $ch = curl_init($url);
			// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			// $source = curl_exec($ch);
			// curl_close($ch);

			
			if (!empty($source) && substr($source,0,18)=='Datum;Rating;Ldfnr' ) file_put_contents($filename, $source); else echo ' Downloadfehler, verwende alte Datei zum Import!';
		}
	}
	// Datei worpresssoftware.csv in Datenbank einlesen
	echo ' | '.$filename;
	$flag = true;
	$file = fopen($filename, "r");
	// Tabelle vorher löschen
	$sql_all = "truncate table softwareversionen";
	$result_all = mysqli_query($db,$sql_all);
	// Daten schreiben in Tabelle
	while (($emapData = fgetcsv($file, 1000000, ";", escape: "")) !== FALSE) {
		if($flag) { $flag = false; continue; }
		if (isset($emapData[0])) {
			$emapData[5] = htmlentities($emapData[5]);
			// mb_convert_encoding($emapData[5], "HTML-ENTITIES", "UTF-8");
			//iconv( "UTF-8", "latin1Windows-1252",  );
			
			// emapdate 15 und 16 eintragen für supportmail und supporttel
						
			$sql_all = "INSERT into softwareversionen (sv_datum,sv_rating,sv_id,sv_product,sv_version,sv_bemerkungen,sv_vorinstall,sv_quelle,sv_lizenztyp,sv_lizenzgeber,sv_lizenzbestimmungen,sv_instlocation,sv_herstellerwebsite,sv_linkempf,sv_icondata,sv_supportmail,sv_supporttel)
	 values ('$emapData[0]','$emapData[1]','$emapData[2]','$emapData[3]','$emapData[4]','$emapData[5]','$emapData[6]','$emapData[7]','$emapData[8]','$emapData[9]','$emapData[10]','$emapData[11]','$emapData[12]','$emapData[13]','$emapData[14]','$emapData[15]','$emapData[16]')";
			$result_all = mysqli_query($db,$sql_all);
		}	
	}
	fclose($file);
	echo ' importiert</div>';
}	



$page = GetVarOrDefaultValue($page);

if ($page == "add_pc")
{
	$use_pass = "n";
	$_SESSION["username"] = "Anonymous";
	$_SESSION["role"] = "none";
}
else
{
	if (GetVarOrDefaultValue($use_https) == "y")
	{
		if ($_SERVER["SERVER_PORT"]!=443){RedirectToUrl("https://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);}
	}
  if (GetVarOrDefaultValue($use_ldap_login) == 'y') {include "include_ldap_login.php";}
}

if ($use_pass != "n") {
  // If there's no Authentication header, exit
  if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="PHP Secured"');
    exit('This page requires authentication');
  }
  // If the user name doesn't exist, exit
  if (!isset($users[$_SERVER['PHP_AUTH_USER']])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="PHP Secured"');
    exit('Unauthorized!');
  }
  // Is the password doesn't match the username, exit
  if ($users[$_SERVER['PHP_AUTH_USER']] != md5($_SERVER['PHP_AUTH_PW']))
  {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="PHP Secured"');
    exit('Unauthorized!');
  }
} else {}
?>

<!DOCTYPE html>
<html lang="de-DE">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Open-AudIT</title>
<link rel="icon" href="favicon.ico" type="image/x-icon"/>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/> 
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#e1e1e1">
<meta name="msapplication-navbutton-color" content="#e1e1e1">
<style>:root{--openauditcolor:<?php echo $accent_color ?>;--openauditcolorlite1:<?php echo $accent_color ?>1;--openauditcolorlite2:<?php echo $accent_color ?>2;--openauditcolorlite4:<?php echo $accent_color ?>3}
<?php
if ($accent_color == '#066') echo '.headerbanner {filter: hue-rotate(-15deg)}';
if ($accent_color == '#822') echo '.headerbanner {filter: grayscale(.8)}';
if ($accent_color == '#666') echo '.headerbanner {filter: grayscale(.8)}';
?>
</style>
<link media="screen" rel="stylesheet" type="text/css" href="default.css" />
<link rel="stylesheet" href="/openaudit/fonts/css/font-awesome.min.css" />
<link rel="stylesheet" media="print" type="text/css" href="defaultprint.css" />
<script type='text/javascript' src="javascript/ajax.js"></script>
<script type='text/javascript' src="javascript/include.js"></script>
<?php 
  // Used to only included pieces of jquery/jquery ui that the page needs.
  if(isset($JQUERY_UI)){
	echo '<script type="text/javascript" src="javascript/jquery/jquery.js"></script>'."\n";
	echo '<script type="text/javascript" src="javascript/jquery/jquery-bgiframe.js"></script>'."\n";
	echo '<link media="screen" rel="stylesheet" type="text/css" href="jquery-ui-theme.css" />'."\n";
	foreach($JQUERY_UI as $script) {
	  echo '<script type="text/javascript" src="javascript/jquery/jquery-ui-'.$script.'.js"></script>'."\n";
	  if ( file_exists('jquery-ui-'.$script.'.css') ) {
		echo '<link media="screen" rel="stylesheet" type="text/css" href="jquery-ui-'.$script.'.css" />'."\n";
	  }
	}
  }

  // Only include certain files if it's a page that needs it.
  switch(basename($_SERVER["PHP_SELF"])){
	case 'list.php':
	case 'system.php':
	case 'system_graphs.php':
	  echo '<script type="text/javascript" src="javascript/list-system.js"></script>'."\n";
	  break;
	case 'admin_config.php':
	  echo '<script type="text/javascript" src="javascript/PopupMenu.js"></script>'."\n".
		   '<script type="text/javascript" src="javascript/admin_config.js"></script>'."\n".
		   '<link media="screen" rel="stylesheet" type="text/css" href="admin_config.css" />'."\n";
	  break;
  }
?>
</head>
<body>
<?php

$pc = GetGETOrDefaultValue("pc", "");
$sub = GetGETOrDefaultValue("sub", "all");
$sort = GetGETOrDefaultValue("sort", "system_name");
$mac = $pc;

if ($page <> "setup"){
 
  $GLOBALS["db"] = GetOpenAuditDbConnection() or die('Could not connect: ' . mysqli_error($db));
  mysqli_select_db($db,$mysqli_database);
  $SQL = "SELECT config_value FROM config WHERE config_name = 'version'";
  $result = mysqli_query($db,$SQL);

  if ($myrow = mysqli_fetch_array($result)){
    $version = $myrow["config_value"];
  } else {}
} else {
  $version = "0.1.00";
}

get_headerbanner();

// Search box
echo "<div id=\"inforechts\"><form action=\"search.php\" method=\"get\">\n";
echo "<input size=\"25\" placeholder=\"Suchbegriff (Enter)\" name=\"search_field\" />\n";
// echo "<input style=\"width:auto;margin-top:0;font-family: FontAwesome\" value=\"&#xf002;\" type=\"submit\" />\n";
echo "</form>";
echo "</div>\n";

	if (isset($use_ldap_login) and ($use_ldap_login == 'y')) 
	{echo "<a class='npb_ldap_logout' href=\"ldap_logout.php\">".__("Logout ").$_SESSION["username"]." [".$_SESSION["role"]."]</a>";}
?>		
 </div>
 
  <table class=\"tftable\"  >
  <tr>
    <td style="width:170px;" rowspan="12" valign="top" id="nav">
      <ul id="primary-nav">
		<li><a href="index.php"><i class="fa fa-lg fa-home"></i> <?php echo strtoupper(__("Home")); ?></a></li>
<?php
if ($pc > "0") {
	// This query has less joins and is syntactically simpler than previous - 17/04/2009	[Nick Brown]
	$sql = "SELECT system_uuid, system_timestamp, system_name, system.net_ip_address, net_domain
					FROM system 
					JOIN network_card ON net_uuid = system_uuid
					WHERE (
					net_mac_address ='$pc'
					OR system_uuid = '$pc'
					OR system_name = '$pc'
					)
					LIMIT 1";
					
  $result = mysqli_query($db,$sql);
  $myrow = mysqli_fetch_array($result);
  $timestamp = $myrow["system_timestamp"];
  $GLOBAL["system_timestamp"]=$timestamp;
  $pc = $myrow["system_uuid"];
  $ip = $myrow["net_ip_address"];
  $name = $myrow['system_name'];
  $domain = $myrow['net_domain'];

  //Menu-Entries for the selected PC
  
  require_once("include_menu_array.php");
  echo "<li class=\"menuparent\">".
        "<a href=\"system.php?pc=$pc&amp;view=summary\">".
        "<span>…</span>".
        $name.
        "</a>\n";

   echo "<ul>\n";
    reset ($menue_array["machine"]);
	foreach($menue_array["machine"] as $key_1 => $topic_item) {
        if (isset($topic_item["class"])) {
          echo "<li class=\"".$topic_item["class"]."\">";
        } else {
          echo "<li>";
        }

        echo "<a href=\"".$topic_item["link"]."\"";
        if (isset($topic_item["css-class"])) {
          echo " class=\"".$topic_item["css-class"]."\"";
        }
        echo ">";
        if(isset($topic_item["childs"]) AND is_array($topic_item["childs"])){
          echo "<span><img src=\"images/spacer.gif\" height=\"16\" width=\"0\" alt=\"\" />…</span>";
        }
        echo '<i class="fa '.$topic_item["image"].'"></i>&nbsp;';
        echo __($topic_item["name"]);
        echo "</a>\n";

        if(isset($topic_item["childs"]) AND is_array($topic_item["childs"])){
          echo "<ul>\n";
          @reset ($topic_item["childs"]);

		foreach ($topic_item["childs"] as $key_2=>$child_item) {		
            echo "<li><a href=\"".$child_item["link"]."\"";
            if (isset($topic_item["title"])) {
              echo " title=\"".$topic_item["title"]."\"";
            }

			 if (strstr($child_item["image"], 'fa-')) {
				echo '><i class="fa '.$child_item["image"].'"></i> &nbsp;';
			  } else {	  
				echo "><img src=\"".$child_item["image"]."\" style=\"width:16px;height:16px;border:0\" alt=\"\" />&nbsp;";
			  }		


            // echo "><img src=\"".$child_item["image"]."\" />&nbsp;";
            echo __($child_item["name"]);
            echo "</a></li>\n";
          }
          echo "</ul>\n";
        }
        echo "</li>\n";
    
    }
    
   echo "</ul>\n";
  echo "</li>\n";
}
    //Normal Menu-Entries
    require_once("include_menu_array.php");

	reset ($menue_array["misc"]);
	foreach($menue_array["misc"] as $key_1 => $topic_item) {
        echo "<li class=\"".$topic_item["class"]."\">";
         echo "<a href=\"".$topic_item["link"]."\"";
          if(isset($topic_item["title"])) {
            echo " title=\"".$topic_item["title"]."\"";
          }
         echo ">";
          if(isset($topic_item['image']) AND $topic_item["image"]!=""){
			  if (strstr($topic_item["image"], 'fa-')) {
				  echo '<i class="fa fa-lg '.$topic_item["image"].'"></i> &nbsp;';
			  } else {
				  echo "<img src=\"".$topic_item["image"]."\" style=\"width:16px;height:16px;border:0\" alt=\"\" />&nbsp;";
			  }		
          }
          echo __($topic_item["name"]);
         echo "</a>";
        echo "<ul>\n";

        // Child Einträge
		if (isset($topic_item["childs"]) and is_array($topic_item["childs"])) {
            @reset ($topic_item["childs"]);
			foreach($topic_item["childs"] as $key_2 => $child_item) {
                echo "<li>";
                 echo "<a href=\"".$child_item["link"]."\" title=\"".$child_item["title"]."\">";
                  if(isset($child_item["childs"]) AND is_array($child_item["childs"])){
                      echo "<span>…</span>";
                  }
				  if (strstr($child_item["image"], 'fa-')) {
					  echo '<i class="fa '.$child_item["image"].'"></i> &nbsp;';
				  } else {	  
					  echo "<img src=\"".$child_item["image"]."\" style=\"width:16px;height:16px;border:0\" alt=\"\" />&nbsp;";
				  }		
                  echo __($child_item["name"]);
                 echo "</a>";
				 
                 if(isset($child_item["childs"]) AND is_array($child_item["childs"])) {
                    echo "<ul>\n";
                    @reset ($child_item["childs"]);
					foreach ($child_item["childs"] as $key_3=>$child_item_2) {
                        echo "<li>";
                         echo "<a href=\"".$child_item_2["link"]."\" title=\"".$child_item_2["title"]."\">";
						 if (strstr($child_item_2["image"], 'fa-')) {
							echo '<i class="fa '.$child_item_2["image"].'"></i> &nbsp;';
						  } else {	  
                            echo "<img src=\"".$child_item_2["image"]."\" style=\"width:16px;height:16px;border:0\" alt=\"\" />&nbsp;";
						  }		
                          echo __($child_item_2["name"]);
                         echo "</a>";
                        echo "</li>\n";
                    }
                    echo "</ul>\n";
                 }
                echo "</li>\n";
            }
        }

         echo "</ul>\n";
        echo "</li>\n";
    unset($topic_item["title"]);
    }
 echo "</ul>\n";
 echo "</td>\n";
?>
