<?php
// PHP Tool zum Convertieren von CSV-Dateien mit verschienenen Delimitern nach UTF-8 und EXCEL (xlsx)
// sammelt alle .csv im Ordner "./in" auf und erstellt jeweils eine xlsx Datei im "./out" Ordner


function delete_files($fmask) {
	$csvroot = realpath($_SERVER['DOCUMENT_ROOT']).dirname($_SERVER['PHP_SELF']);
	array_map('unlink', glob($csvroot.'/in/*.'.$fmask));
	header("Location: csvxls.php"); 
	exit();
}

function utf8_converter($array) {
	array_walk_recursive($array, function(&$item, $key){
        if(!mb_detect_encoding($item, 'utf-8', true)){
			//echo mb_detect_encoding($item);
            $item = mb_convert_encoding($item, 'UTF-8', 'UTF-16LE');
			// $item = iconv($in_charset = 'UTF-16LE//IGNORE' , $out_charset = 'UTF-8' , $item);
        }
    }); 
    return $array;
}

function detectDelimiter($csvFile) {
    $delimiters = array(
        ';' => 0,
        ',' => 0,
        "\t" => 0,
        "|" => 0
    );
    $handle = fopen($csvFile, "r");
    $firstLine = fgets($handle);
    fclose($handle); 
    foreach ($delimiters as $delimiter => &$count) {
        $count = count(str_getcsv($firstLine, $delimiter, escape: ""));
    }
    return array_search(max($delimiters), $delimiters);
}

// PHP function to read CSV to array
function csvToArray($csv) {
	$trenner = detectDelimiter($csv);
    // create file handle to read CSV file
    $csvToRead = fopen($csv, 'r');
    // read CSV file using comma as delimiter
    while (! feof($csvToRead)) {
        $csvArray[] = fgetcsv($csvToRead, 1000, $trenner, escape: "");
    }
    fclose($csvToRead);
    return $csvArray;
}

// Main program
if (isset($_GET['delete_files'])) delete_files('csv');

require_once 'simplexlsxgen.php';
include_once("include.php");

$csvroot = realpath($_SERVER['DOCUMENT_ROOT']).dirname($_SERVER['PHP_SELF']);

	echo "<td style=\"vertical-align:top;width:100%\">\n";
	echo "<div class=\"main_each\">";
	echo "<table ><tr><td class=\"contenthead\">\n";
	echo 'Batch: CSV-Dateien nach XLSX konvertieren</td></tr></table>';
	echo "<table ><tr><td>";

echo '<ol style="line-height:22px"><li>.csv Dateien in den Ordner <b>'.$csvroot.'/in</b> legen,</li><li><a href="csvxls.php">diese Seite neu laden</a>,</li><li>.xslx Excel-Dateien aus Ordner <b>'.$csvroot.'/out</b> entnehmen oder über Links unten herunterladen.</li>';
echo '<li><i class="fa fa-trash" style="color:tomato"></i> <a href="csvxls.php?delete_files=1">Import-Ordner leeren</a></li></ol>';
$xlsdir='./in/';
if ($handle = opendir($xlsdir)) {
	$ctr=0;
	echo '<table class="tftable">';
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != ".." && strtolower(substr($file, strrpos($file, '.') + 1)) == 'csv') {
			// do something with $filename
			if ( !is_dir( $xlsdir.'/'.$file ) ) {    // only if folder not exists
				$csvArray = csvToArray($xlsdir.$file);
				// echo '<pre>'; print_r($csvArray); echo '</pre>';
				$withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
				$xlsx = Shuchkin\SimpleXLSXGen::fromArray( utf8_converter($csvArray) );
				$xlsx->saveAs('./out/'.$withoutExt.'.xlsx'); // or saveAs('filename.xlsx') or $xlsx_content = (string) $xlsx 

			}	
			$Serverbaseurl = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].':888' . substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], "/"));
			$filedate = date('d.m.Y', filemtime($xlsdir.'/'.$file) );
			$ctr++;
			echo '<tr><td style="padding:8px">Konvertiert: #'.$ctr.'</td><td style="padding:8px">'.$file.'</td><td style="padding:8px"><a href="'.$Serverbaseurl.'/out/'.$withoutExt.'.xlsx">'.$withoutExt. '.xlsx</a></td><td style="padding:8px">'.$filedate.'</td></tr>';
		}
	}
	closedir($handle);
	echo '</table>';
}

echo '</td></tr></table></body></html>';
?>