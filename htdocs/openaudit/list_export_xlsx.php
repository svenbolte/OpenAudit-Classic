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
$xlsx_data[] = explode(";",str_replace('"', '', $csv_data));

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
		$xlsx_data[] = explode('^^^',str_replace('"', '', $csv_data));
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
$xlsx = Shuchkin\SimpleXLSXGen::fromArray( $xlsx_data );
$xlsx->downloadAs($filename); // or saveAs('filename.xlsx') or $xlsx_content = (string) $xlsx 
exit();
?>
