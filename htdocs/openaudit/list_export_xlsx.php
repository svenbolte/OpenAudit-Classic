<?php
// Excel-Datei erzeugen aus dem Export (XLXS Open-XML-Format)

include_once("include_config.php");
include_once("include_functions.php");
include_once("include_lang.php");
require_once ("simplexlsxgen.php");

//MySQL-Connect
$db=GetOpenAuditDbConnection() or die('Could not connect: ' . mysqli_error($db));
mysqli_select_db($db,$mysqli_database);

//Include the view-definition
if(isset($_REQUEST["view"]) AND $_REQUEST["view"]!=""){
    $include_filename = "list_viewdef_" . $_REQUEST["view"] . ".php";
}else{
    $include_filename = "list_viewdef_all_systems.php";
}
if(is_file($include_filename)){
    include_once($include_filename);
    $viewdef_array=$query_array;
}else{
    die("FATAL: Could not find view $include_filename");
}

    //Executing the Query
    $sql=urldecode($_REQUEST["sql"]);
    $result = mysqli_query($db,$sql);
    if(!$result) {die( "<br>".__("Fatal Error").":<br><br>".$sql."<br><br>".mysqli_error($db)."<br><br>" );};
    $this_page_count = mysqli_num_rows($result);

$csv_data = '';
$xlsx_data = array();

//Table head
foreach($viewdef_array["fields"] as $field) {
    if($field["show"]!="n" || $field["name"] =="sv_bemerkungen"){
        $csv_data .= '"'.$field["head"].'"';
        $csv_data .= ";";
    }
}

//	 hier erste Zeile des XLS Array füllen mit Überschriften
$xlsx_data = array();
$xlsx_data[] = explode(";",str_replace('"', '', rtrim($csv_data, ";")));

//Table body
$sumlcpu = 0;
$total_hard_drive_size = 0;
$total_system_memory = 0;
if ($myrow = mysqli_fetch_array($result)){
    $totals=0;
	do {
		$csv_data = '';
		foreach($query_array["fields"] as $field) {

               //Convert the array-values to local variables
				foreach($myrow as $key => $val) {
                   $$key=$val;
               }

			if ($field["head"]=="RAM") { $total_system_memory += (int) $myrow[$field["name"]]; }
			if ($field["head"]=="Größe C") { $total_hard_drive_size += (int) $myrow[$field["name"]]; }
			if ($field["head"]=="l cpu") { $sumlcpu += (int) $myrow[$field["name"]]; }
			if ( $field["head"]=="Anzahl") $totals += (int) $myrow[$field["name"]];
			if ( $field["show"]!="n" && isset($myrow[$field["name"]] ) ) {
				if ($field["name"]=='software_version' || $field["name"]=='sv_version') {
					if (empty($software_version)) $software_version='9999.0';
					if (empty($sv_version)) $sv_version='';
					if ( version_compare($sv_version, $software_version,'>')) $warnmarker= "X"; else $warnmarker="";
					$csv_data .= "\0".$myrow[$field["name"]];
					$csv_data .= '^^^';
				} else if ($field["name"]=='sv_newer') {
					$csv_data .= '"' . $warnmarker .'"';
					$csv_data .= '^^^';
				} else if ( (float) $myrow[$field["name"]] > 20000101000000) {
					$csv_data .= '"'.return_date_time($myrow[$field["name"]]).'"';
					$csv_data .= '^^^';
				} else {
					$csv_data .= '"'.$myrow[$field["name"]].'"';
					$csv_data .= '^^^';
				}
			} else if ( $field["show"]!="n" ) {
                $csv_data .= '""^^^';
			}
			if ( $field["show"]=="n" && ($field["name"]=="sv_bemerkungen" || $field["name"]=="software_comment")  ) {
				$csv_data .= html_entity_decode($myrow[$field["name"]] ?? '');
				$csv_data .= '^^^';
			}	
        }
		$xlsx_data[] = explode('^^^',str_replace('"', '', substr($csv_data, 0, -3)));
    } while ($myrow = mysqli_fetch_array($result));


// SUM line
$sumstyle = '<style bgcolor="#cccccc"><b>';
$csv_data = $sumstyle.($totals > 2 ? $totals : '').'</b>;';
if ($_REQUEST["view"] == 'all_systems_more' || $_REQUEST["view"] == 'all_servers') $csv_data .= $sumstyle.';'.$sumstyle.';';
$csv_data .= $sumstyle.$result->num_rows.'</b>;'.$sumstyle.'Totals ungefiltert</b>;';
$csv_data .= $sumstyle.($total_system_memory > 2 ? $total_system_memory : '').'</b>;'.$sumstyle.($total_hard_drive_size > 2 ? $total_hard_drive_size : '').'</b>;'.$sumstyle.'</b>;'.$sumstyle.($sumlcpu > 2 ? $sumlcpu : '').'</b>';
$xlsx_data[] = explode(";",'');
$xlsx_data[] = explode(";",$csv_data);


}

// set the filename if specified
$filename = (isset($_GET["filename"])) ? $_GET["filename"] . '-' . $_REQUEST["view"] . '.xlsx' : 'export-' . $_REQUEST["view"] .'.xlsx';
// Excel tables require nonempty, unique column headings.
$usedHeaders = array();
foreach ($xlsx_data[0] as $i => $heading) {
    $heading = trim(strip_tags((string)$heading));
    if ($heading === '') $heading = 'Spalte' . ($i + 1);
    $base = $heading;
    $suffix = 2;
    while (isset($usedHeaders[(function_exists('mb_strtolower') ? mb_strtolower($heading, 'UTF-8') : strtolower($heading))])) {
        $heading = $base . '_' . $suffix++;
    }
    $usedHeaders[(function_exists('mb_strtolower') ? mb_strtolower($heading, 'UTF-8') : strtolower($heading))] = true;
    $xlsx_data[0][$i] = $heading;
}
$xlsx = Shuchkin\SimpleXLSXGen::fromArray( $xlsx_data );
// Keep SimpleXLSXGen; add an OOXML table with Excel's Medium 2 style.
$tempFile = tempnam(sys_get_temp_dir(), 'oa_xlsx_');
$xlsx->saveAs($tempFile);
$zip = new ZipArchive();
if ($zip->open($tempFile) !== true) {
    @unlink($tempFile);
    die('Excel export could not be opened.');
}
$columns = count($xlsx_data[0]);
// Exclude the separate totals/blank rows from the formatted table.
$rows = max(1, count($xlsx_data) - ($this_page_count > 0 ? 2 : 0));
if ($columns > 0) {
    $columnLetter = '';
    for ($n = $columns; $n > 0; $n = intdiv($n - 1, 26)) {
        $columnLetter = chr(65 + ($n - 1) % 26) . $columnLetter;
    }
    $ref = 'A1:' . $columnLetter . $rows;
    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $relNs = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    $pkgNs = 'http://schemas.openxmlformats.org/package/2006/relationships';
    $table = new DOMDocument('1.0', 'UTF-8');
    $table->formatOutput = false;
    $root = $table->createElementNS($ns, 'table');
    $root->setAttribute('id', '1');
    $root->setAttribute('name', 'OpenAuditExport');
    $root->setAttribute('displayName', 'OpenAuditExport');
    $root->setAttribute('ref', $ref);
    $root->setAttribute('totalsRowShown', '0');
    $table->appendChild($root);
    $filter = $table->createElementNS($ns, 'autoFilter');
    $filter->setAttribute('ref', $ref);
    $root->appendChild($filter);
    $cols = $table->createElementNS($ns, 'tableColumns');
    $cols->setAttribute('count', (string)$columns);
    $used = array();
    foreach ($xlsx_data[0] as $i => $heading) {
        $heading = trim(strip_tags((string)$heading));
        if ($heading === '') $heading = 'Spalte ' . ($i + 1);
        $base = $heading;
        $suffix = 2;
        while (isset($used[$heading])) $heading = $base . ' ' . $suffix++;
        $used[$heading] = true;

        $col = $table->createElementNS($ns, 'tableColumn');
        $col->setAttribute('id', (string)($i + 1));
        $col->setAttribute('name', $heading);
        $cols->appendChild($col);
    }
    $root->appendChild($cols);
    $style = $table->createElementNS($ns, 'tableStyleInfo');
    foreach (array('name'=>'TableStyleMedium2', 'showFirstColumn'=>'0', 'showLastColumn'=>'0', 'showRowStripes'=>'1', 'showColumnStripes'=>'0') as $key=>$value) $style->setAttribute($key, $value);
    $root->appendChild($style);
    $zip->addFromString('xl/tables/table1.xml', $table->saveXML());

    $sheet = new DOMDocument();
    $sheet->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
    $sheetRoot = $sheet->documentElement;
    $parts = $sheet->createElementNS($ns, 'tableParts');
    $parts->setAttribute('count', '1');
    $part = $sheet->createElementNS($ns, 'tablePart');
    $part->setAttributeNS($relNs, 'r:id', 'rIdOpenAuditTable1');
    $parts->appendChild($part);
    $sheetRoot->appendChild($parts);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet->saveXML());

    $relsPath = 'xl/worksheets/_rels/sheet1.xml.rels';
    $rels = new DOMDocument('1.0', 'UTF-8');
    if ($zip->locateName($relsPath) !== false) $rels->loadXML($zip->getFromName($relsPath));
    else $rels->appendChild($rels->createElementNS($pkgNs, 'Relationships'));
    $rel = $rels->createElementNS($pkgNs, 'Relationship');
    $rel->setAttribute('Id', 'rIdOpenAuditTable1');
    $rel->setAttribute('Type', $relNs . '/table');
    $rel->setAttribute('Target', '../tables/table1.xml');
    $rels->documentElement->appendChild($rel);
    $zip->addFromString($relsPath, $rels->saveXML());

    $types = new DOMDocument();
    $types->loadXML($zip->getFromName('[Content_Types].xml'));
    // Worksheet relationship files need the standard .rels content type.
    // SimpleXLSXGen may declare only its existing .rels files via Overrides.
    $typesNs = 'http://schemas.openxmlformats.org/package/2006/content-types';
    $hasRelsDefault = false;
    foreach ($types->documentElement->getElementsByTagNameNS($typesNs, 'Default') as $defaultType) {
        if (strtolower($defaultType->getAttribute('Extension')) === 'rels') {
            $hasRelsDefault = true;
            break;
        }
    }
    if (!$hasRelsDefault) {
        $relsDefault = $types->createElementNS($typesNs, 'Default');
        $relsDefault->setAttribute('Extension', 'rels');
        $relsDefault->setAttribute('ContentType', 'application/vnd.openxmlformats-package.relationships+xml');
        $types->documentElement->insertBefore($relsDefault, $types->documentElement->firstChild);
    }
    $override = $types->createElementNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Override');
    $override->setAttribute('PartName', '/xl/tables/table1.xml');
    $override->setAttribute('ContentType', 'application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml');
    $types->documentElement->appendChild($override);
    $zip->addFromString('[Content_Types].xml', $types->saveXML());
}
$zip->close();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . str_replace(array('"', "\r", "\n"), '', $filename) . '"');
header('Content-Length: ' . filesize($tempFile));
readfile($tempFile);
@unlink($tempFile); 
exit();
?>
