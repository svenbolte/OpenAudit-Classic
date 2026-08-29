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
function svversionenimport($aftertime, $returnDetails = false) {
    global $db;

    $startedAt = microtime(true);
    $filename = dirname(__FILE__).'/wordpresssoftware.csv';
    $messages = array();
    $myserver = $_SERVER['SERVER_NAME'] ?? '';
    $url = ($myserver == 'pat14sv')
        ? 'https://wp.pbcs.de/wp-content/uploads/csv/softwareverzeichnis.csv'
        : 'https://tech-nachrichten.de/wp-content/uploads/csv/softwareverzeichnis.csv';

    $details = array(
        'ok' => true,
        'source_url' => $url,
        'local_file' => basename($filename),
        'local_exists' => file_exists($filename),
        'local_timestamp' => file_exists($filename) ? filemtime($filename) : null,
        'download_attempted' => false,
        'download_updated' => false,
        'download_error' => '',
        'imported' => 0,
        'duration' => 0.0,
        'messages' => array()
    );

    if (file_exists($filename)) {
        $tdif = (int)(time() - filemtime($filename));
        $messages[] = 'Softwarekatalog: ' . date('d.m.Y H:i:s', filemtime($filename));

        if ($tdif > ((int)$aftertime * 60)) {
            $details['download_attempted'] = true;
            $arrContextOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false));
            $source = @file_get_contents($url, false, stream_context_create($arrContextOptions));
            if (!empty($source) && substr($source, 0, 15) == 'Datum;Rating;id') {
                if (file_put_contents($filename, $source) !== false) {
                    clearstatcache(true, $filename);
                    $details['download_updated'] = true;
                    $details['local_timestamp'] = filemtime($filename);
                    $messages[] = 'Online-Katalog aktualisiert';
                } else {
                    $details['ok'] = false;
                    $details['download_error'] = 'Heruntergeladener Katalog konnte lokal nicht gespeichert werden.';
                    $messages[] = 'Download erhalten, lokales Speichern fehlgeschlagen';
                }
            } else {
                $details['download_error'] = 'Download fehlgeschlagen oder die CSV-Kopfzeile ist ungueltig.';
                $messages[] = 'Download fehlgeschlagen - lokaler Katalog wird verwendet';
            }
        } else {
            $messages[] = 'Lokaler Katalog ist noch aktuell - kein Download erforderlich';
        }
    } else {
        $messages[] = 'Lokaler Softwarekatalog fehlt';
        $details['download_attempted'] = true;
        $arrContextOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false));
        $source = @file_get_contents($url, false, stream_context_create($arrContextOptions));
        if (!empty($source) && substr($source, 0, 15) == 'Datum;Rating;id' && file_put_contents($filename, $source) !== false) {
            clearstatcache(true, $filename);
            $details['local_exists'] = true;
            $details['local_timestamp'] = filemtime($filename);
            $details['download_updated'] = true;
            $messages[] = 'Online-Katalog neu heruntergeladen';
        } else {
            $details['ok'] = false;
            $details['download_error'] = 'Es ist kein lokaler Katalog vorhanden und der Download ist fehlgeschlagen.';
        }
    }

    if (!file_exists($filename)) {
        $details['ok'] = false;
        $details['messages'] = $messages;
        $details['duration'] = microtime(true) - $startedAt;
        return $returnDetails ? $details : implode(' | ', $messages);
    }

    $file = fopen($filename, 'r');
    if ($file === false) {
        $messages[] = 'Katalog konnte nicht geoeffnet werden';
        $details['ok'] = false;
        $details['messages'] = $messages;
        $details['duration'] = microtime(true) - $startedAt;
        return $returnDetails ? $details : implode(' | ', $messages);
    }

    if (!mysqli_query($db, 'TRUNCATE TABLE softwareversionen')) {
        fclose($file);
        $messages[] = 'Datenbanktabelle konnte nicht geleert werden: ' . mysqli_error($db);
        $details['ok'] = false;
        $details['messages'] = $messages;
        $details['duration'] = microtime(true) - $startedAt;
        return $returnDetails ? $details : implode(' | ', $messages);
    }

    $flag = true;
    $count = 0;
    while (($emapData = fgetcsv($file, 1000000, ';', '"', '')) !== false) {
        if ($flag) { $flag = false; continue; }
        if (!isset($emapData[0])) { continue; }
        for ($i = 0; $i <= 16; $i++) {
            if (!isset($emapData[$i])) $emapData[$i] = '';
        }
        $emapData[5] = htmlentities((string)$emapData[5], ENT_QUOTES, 'UTF-8');
        for ($i = 0; $i <= 16; $i++) {
            $emapData[$i] = mysqli_real_escape_string($db, (string)$emapData[$i]);
        }
        $sql_all = "INSERT INTO softwareversionen (sv_datum,sv_rating,sv_id,sv_product,sv_version,sv_bemerkungen,sv_vorinstall,sv_quelle,sv_lizenztyp,sv_lizenzgeber,sv_lizenzbestimmungen,sv_instlocation,sv_herstellerwebsite,sv_linkempf,sv_icondata,sv_supportmail,sv_supporttel)
            VALUES ('$emapData[0]','$emapData[1]','$emapData[2]','$emapData[3]','$emapData[4]','$emapData[5]','$emapData[6]','$emapData[7]','$emapData[8]','$emapData[9]','$emapData[10]','$emapData[11]','$emapData[12]','$emapData[13]','$emapData[14]','$emapData[15]','$emapData[16]')";
        if (mysqli_query($db, $sql_all)) {
            $count++;
        } else {
            $details['ok'] = false;
            $messages[] = 'Mindestens ein Datensatz konnte nicht importiert werden';
        }
    }
    fclose($file);

    $details['imported'] = $count;
    $details['local_exists'] = true;
    $details['local_timestamp'] = filemtime($filename);
    $messages[] = $count . ' Eintraege importiert';
    $details['messages'] = $messages;
    $details['duration'] = microtime(true) - $startedAt;

    return $returnDetails ? $details : implode(' | ', $messages);
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
<?php
// Normalize old 3-digit values and validate custom theme colors from Settings.
$themeAccent = isset($accent_color) ? trim((string)$accent_color) : '#004477';
if (preg_match('/^#([0-9a-fA-F]{3})$/', $themeAccent, $m)) {
    $themeAccent = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $themeAccent)) {
    $themeAccent = '#004477';
}
$themeAccent = strtolower($themeAccent);
function oaHexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return array(hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
}
list($themeR, $themeG, $themeB) = oaHexToRgb($themeAccent);
?>
<meta name="theme-color" content="<?php echo htmlspecialchars($themeAccent, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="msapplication-navbutton-color" content="<?php echo htmlspecialchars($themeAccent, ENT_QUOTES, 'UTF-8'); ?>">
<style>
:root {
  --openauditcolor: <?php echo $themeAccent; ?>;
  --openaudit-rgb: <?php echo $themeR . ', ' . $themeG . ', ' . $themeB; ?>;
  --openauditcolorlite1: rgba(<?php echo $themeR . ', ' . $themeG . ', ' . $themeB; ?>, .06);
  --openauditcolorlite2: rgba(<?php echo $themeR . ', ' . $themeG . ', ' . $themeB; ?>, .10);
  --openauditcolorlite4: rgba(<?php echo $themeR . ', ' . $themeG . ', ' . $themeB; ?>, .16);
  --oa-accent-soft: rgba(<?php echo $themeR . ', ' . $themeG . ', ' . $themeB; ?>, .12);
  --oa-accent-border: rgba(<?php echo $themeR . ', ' . $themeG . ', ' . $themeB; ?>, .28);
}
</style>
<link media="screen" rel="stylesheet" type="text/css" href="default.css" />
<link rel="stylesheet" href="/openaudit/fonts/fontawesomeplus.min.css" />
<link rel="stylesheet" media="print" type="text/css" href="defaultprint.css" />
<script type='text/javascript' src="javascript/ajax.js"></script>
<?php 

  // Only include certain files if it's a page that needs it.
  switch(basename($_SERVER["PHP_SELF"])){
	case 'admin_config.php':
	  echo '<script type="text/javascript" src="javascript/admin_config.js"></script>'."\n".
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

$oaBannerStatus = '';
if (basename($_SERVER['PHP_SELF'] ?? '') === 'list.php' && isset($_REQUEST['view']) && str_contains((string)$_REQUEST['view'], 'software')) {
    $oaBannerStatus = svversionenimport(120);
}

get_headerbanner();
if ($oaBannerStatus !== '') {
    echo '<div class="oa-banner-status" role="status">'.htmlspecialchars($oaBannerStatus, ENT_QUOTES, 'UTF-8').'</div>';
}

// Search box
echo "<div id=\"inforechts\"><form action=\"search.php\" method=\"get\">\n";
echo "<input size=\"25\" placeholder=\"Suchbegriff (Enter)\" name=\"search_field\" />\n";
echo "</form>";
echo "</div>\n";

	if (isset($use_ldap_login) and ($use_ldap_login == 'y')) 
	{echo "<a class='npb_ldap_logout' href=\"ldap_logout.php\">".__("Logout ").$_SESSION["username"]." [".$_SESSION["role"]."]</a>";}
?>		
 </div>
 
<?php
// Modern horizontal navigation. Submenus expand downwards; third-level entries
// stay inside the same dropdown so they cannot be clipped by the viewport.
require_once("include_menu_array.php");

function oaMenuIcon($item, $large = false) {
    if (!isset($item['image']) || $item['image'] === '') return '';
    $image = $item['image'];
    if (strstr($image, 'fa-')) {
        return '<i class="fa '.($large ? 'fa-lg ' : '').htmlspecialchars($image, ENT_QUOTES, 'UTF-8').'" aria-hidden="true"></i>';
    }
    return '<img class="oa-menu-icon" src="'.htmlspecialchars($image, ENT_QUOTES, 'UTF-8').'" alt="">';
}

function oaRenderMenuChildren($children) {
    if (!is_array($children) || empty($children)) return;
    echo '<div class="oa-dropdown-grid">';
    foreach ($children as $child) {
        $hasGrandchildren = isset($child['childs']) && is_array($child['childs']) && !empty($child['childs']);
        echo '<div class="oa-dropdown-group">';
        echo '<a class="oa-dropdown-link'.($hasGrandchildren ? ' has-children' : '').'" href="'.htmlspecialchars($child['link'], ENT_QUOTES, 'UTF-8').'"';
        if (!empty($child['title'])) echo ' title="'.htmlspecialchars($child['title'], ENT_QUOTES, 'UTF-8').'"';
        echo '>'.oaMenuIcon($child).'<span>'.htmlspecialchars(__($child['name']), ENT_QUOTES, 'UTF-8').'</span>';
        if ($hasGrandchildren) echo '<i class="fa fa-angle-down oa-sub-indicator" aria-hidden="true"></i>';
        echo '</a>';
        if ($hasGrandchildren) {
            echo '<div class="oa-third-level">';
            foreach ($child['childs'] as $grandchild) {
                echo '<a href="'.htmlspecialchars($grandchild['link'], ENT_QUOTES, 'UTF-8').'"';
                if (!empty($grandchild['title'])) echo ' title="'.htmlspecialchars($grandchild['title'], ENT_QUOTES, 'UTF-8').'"';
                echo '>'.oaMenuIcon($grandchild).'<span>'.htmlspecialchars(__($grandchild['name']), ENT_QUOTES, 'UTF-8').'</span></a>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
}
?>
<nav class="oa-topnav" aria-label="Hauptnavigation">
  <button class="oa-mobile-menu-toggle" type="button" aria-expanded="false" aria-controls="oa-primary-menu"><i class="fa fa-bars" aria-hidden="true"></i><span>Menü</span></button>
  <div class="oa-topnav-inner" id="oa-primary-menu">
    <a class="oa-topnav-home" href="index.php"><i class="fa fa-home" aria-hidden="true"></i><span><?php echo strtoupper(__("Home")); ?></span></a>
<?php
if ($pc > "0") {
    $sql = "SELECT system_uuid, system_timestamp, system_name, system.net_ip_address, net_domain
            FROM system
            JOIN network_card ON net_uuid = system_uuid
            WHERE (net_mac_address ='$pc' OR system_uuid = '$pc' OR system_name = '$pc')
            LIMIT 1";
    $result = mysqli_query($db, $sql);
    if ($result && ($myrow = mysqli_fetch_array($result))) {
        $timestamp = $myrow['system_timestamp'];
        $GLOBAL['system_timestamp'] = $timestamp;
        $pc = $myrow['system_uuid'];
        $ip = $myrow['net_ip_address'];
        $name = $myrow['system_name'];
        $domain = $myrow['net_domain'];
        // Reload menu array because machine links depend on the resolved $pc value.
        include("include_menu_array.php");
        echo '<div class="oa-topnav-item oa-device-menu">';
        echo '<a class="oa-topnav-trigger" href="system.php?pc='.urlencode($pc).'&amp;view=summary"><i class="fa fa-desktop" aria-hidden="true"></i><span>'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</span><i class="fa fa-chevron-down oa-chevron" aria-hidden="true"></i></a>';
        echo '<div class="oa-dropdown oa-dropdown-wide">';
        echo '<div class="oa-dropdown-title">'.htmlspecialchars(__('Device'), ENT_QUOTES, 'UTF-8').': '.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</div>';
        oaRenderMenuChildren($menue_array['machine']);
        echo '</div></div>';
    }
}

// Reload global menu after the optional machine-specific include.
include("include_menu_array.php");
foreach ($menue_array['misc'] as $topic_item) {
    $hasChildren = isset($topic_item['childs']) && is_array($topic_item['childs']) && !empty($topic_item['childs']);
    echo '<div class="oa-topnav-item'.($hasChildren ? ' has-dropdown' : '').'">';
    echo '<a class="oa-topnav-trigger" href="'.htmlspecialchars($topic_item['link'], ENT_QUOTES, 'UTF-8').'"';
    if (!empty($topic_item['title'])) echo ' title="'.htmlspecialchars($topic_item['title'], ENT_QUOTES, 'UTF-8').'"';
    echo '>'.oaMenuIcon($topic_item, true).'<span>'.htmlspecialchars(__($topic_item['name']), ENT_QUOTES, 'UTF-8').'</span>';
    if ($hasChildren) echo '<i class="fa fa-chevron-down oa-chevron" aria-hidden="true"></i>';
    echo '</a>';
    if ($hasChildren) {
        echo '<div class="oa-dropdown">';
        echo '<div class="oa-dropdown-title">'.htmlspecialchars(__($topic_item['name']), ENT_QUOTES, 'UTF-8').'</div>';
        oaRenderMenuChildren($topic_item['childs']);
        echo '</div>';
    }
    echo '</div>';
}
?>
  </div>
</nav>
<script>
(function () {
  var nav = document.querySelector('.oa-topnav');
  if (!nav) return;
  var mobileToggle = nav.querySelector('.oa-mobile-menu-toggle');

  function closeSubmenus(except) {
    nav.querySelectorAll('.oa-topnav-item.is-open').forEach(function (item) {
      if (item !== except) item.classList.remove('is-open');
    });
  }

  if (mobileToggle) {
    mobileToggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-mobile-open');
      mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (!open) closeSubmenus();
    });
  }

  nav.addEventListener('click', function (event) {
    var trigger = event.target.closest('.oa-topnav-trigger');
    if (!trigger) return;
    var item = trigger.parentNode;
    var dropdown = item.querySelector(':scope > .oa-dropdown');
    if (!dropdown) return;

    var mobile = window.matchMedia('(max-width: 900px)').matches;
    if (mobile || !item.classList.contains('is-open')) {
      event.preventDefault();
      var willOpen = !item.classList.contains('is-open');
      closeSubmenus(item);
      item.classList.toggle('is-open', willOpen);
    }
  });

  document.addEventListener('click', function (event) {
    if (!nav.contains(event.target) && !window.matchMedia('(max-width: 900px)').matches) closeSubmenus();
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeSubmenus();
      nav.classList.remove('is-mobile-open');
      if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'false');
    }
  });

  window.addEventListener('resize', function () {
    if (!window.matchMedia('(max-width: 900px)').matches) {
      nav.classList.remove('is-mobile-open');
      if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'false');
    }
  });
})();
</script>
<table class="tftable oa-app-layout">
<tr>

