<?php
// Codeeinheit zum Batch Herunterladen von Favicons aus einer Liste von URLs und Ausgabe als dataURLs in Textdatei
// benötigt favicondownloader.php und favico-imagetourl.php im Ordner, sowie einen Unterordner favico

include_once("include.php");

	echo "<td style=\"vertical-align:top;width:100%\">\n";
	echo "<div class=\"main_each\">";
	echo "<table ><tr><td class=\"contenthead\">\n";
	echo 'Batch: HTML Browser Bookmarks nach CSV (Excel) exportieren</td></tr></table>';
	echo "<table ><tr><td style=\"padding:3em\">";

require 'favicondownloader.php';
use Vincepare\FaviconDownloader\FaviconDownloader;

function get_favicon($seitenurl) {
	// Find & download favicon
	$favicon = new FaviconDownloader($seitenurl);
	if (!$favicon->icoExists) {
		echo "<br><br>No favicon for ".$favicon->url;
		die(1);
	}
	echo "<h3>Favicon found : ".$favicon->icoUrl."</h3>";
	// Saving favicon to file
	$filename = dirname(__FILE__).DIRECTORY_SEPARATOR.'favico/favicon-'.time().'.'.$favicon->icoType;
	file_put_contents($filename, $favicon->icoData);
	echo "Saved to ".$filename."<br><br>";
	$fileshort = basename($filename);
	echo "Short filename ".$fileshort."<br><br>";
	echo "Details :<br>";
	$favicon->debug();
	return $fileshort;
}


    function geticongoogle($url, &$info = null) {
    $info = is_array($info) ? $info : [];

    // Ensure the URL is valid and includes a scheme
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (empty($scheme)) {
        $url = 'https://' . ltrim($url, '/');
    }

    // Google Favicon service – IMPORTANT: urlencode the target URL
    $api = 'https://t3.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=' . rawurlencode($url);

    $ch = curl_init($api);
    curl_setopt_array($ch, [
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        // Don't check SSL certificate to allow autosigned certificate
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);

    $content = curl_exec($ch);

    $info['curl_errno']     = curl_errno($ch);
    $info['curl_error']     = curl_error($ch);
    $info['http_code']      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $info['effective_url']  = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $info['redirect_count'] = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
    $info['content_type']   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    curl_close($ch);

    if ($info['curl_errno'] !== CURLE_OK || $info['http_code'] !== 200) {
        return false;
    }

    // Defensive: ensure we really got an image back, not HTML/error page.
    if (empty($info['content_type']) || stripos($info['content_type'], 'image/') !== 0) {
        return false;
    }

    if ($content === false || strlen($content) < 50) {
        return false;
    }

    return $content;
}


	function arraytofavicon() {
		// hier die URLs in den Array eintragen
		$urlarray = array (
			'https://gws.ms',
			'https://tech-nachrichten.de',
		);
		foreach ($urlarray as $singleurl) {
			$iconfilename = './favico/favicon-'.time().'-'.str_replace(".","-",parse_url($singleurl, PHP_URL_HOST)).'.png';
			$info = [];
			$bin = geticongoogle($singleurl, $info);
			if ($bin === false) {
			    echo '<br><br><b>Kein Icon geladen:</b> ' . htmlspecialchars($singleurl) . ' (HTTP=' . ($info['http_code'] ?? '?') . ', CT=' . ($info['content_type'] ?? '?') . ')';
			    continue;
			}
			file_put_contents($iconfilename, $bin);
			echo '<br><br>Icon: <img src="'.$iconfilename.'">';
			if (!isset($_GET['int'])) {
				$iconout[] = $iconfilename;
			} else {	
				// use library to get icon alternately
				$iconout[] = get_favicon($singleurl);
			}	
		}
	}


// Main Programm =url=https://sss.de oder array unten füllen--------------------------------------------

echo '<html><head><title>FAVICON herunterladen und als Bild und dataurl speichern</title></head><body>';
echo '<p>FAVICON URL to IMG and DATA-URL - Füllen Sie den Array im PHP-Code mit URLs oder geben<br>
	hinter dieser seite den Parameter ?url=https:/webeite.de an.<br>
	Mit dem zweiten Parameter &int=1 wird nicht der Google Favicon-Dienst, sondern eine interne Bibliothek benutzt</p>';

$iconout = array();

if (isset($_GET['url'])) {
	$rawurl = $_GET['url'];
	$scheme = parse_url($rawurl, PHP_URL_SCHEME);
	if (empty($scheme)) $rawurl = 'https://' . ltrim($rawurl, '/');
	echo '<strong>'.$rawurl.'</strong>';
	
	// Save image to folder and display it
	$iconfilename = './favico/favicon-'.time().'-'.str_replace(".","-",parse_url($rawurl, PHP_URL_HOST)).'.png';
	$info = [];
	$bin = geticongoogle($rawurl, $info);
	if ($bin === false) {
	    echo '<br><br><b>Kein Icon geladen.</b> HTTP=' . ($info['http_code'] ?? '?') . ' CT=' . ($info['content_type'] ?? '?') . ' Err=' . htmlspecialchars($info['curl_error'] ?? '');
	    die;
	}
	file_put_contents($iconfilename, $bin);
	echo '<br><br>Icon: <img src="'.$iconfilename.'">';
	if (!isset($_GET['int'])) {
		$iconout[] = $iconfilename;
	} else {	
		// use library to get icon alternately
		$iconout[] = get_favicon($rawurl);
	}	
} else {
	// Formular anzeigen
	echo '<form name="urlform" id="urlform">URL eingeben: <input name="url" id="url "type="text" length=80 style="width:50%">';
	echo '<br><input type="submit" value="Favicon holen" class="submit" style="width:100%">';
	die;
	// oder diese Funktion aufrufen
	arraytofavicon();
}	
	
// Umwandeln in DataURL Strings
require 'favico-imagetourl.php';
use imageToURI\imageToURI;
$images = new imageToURI();
echo $images->imageToURI( $iconout, './favico/0-output-dataUris.txt', false).'<br><br>';

?>