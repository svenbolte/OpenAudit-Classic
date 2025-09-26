<?php
include_once("include_config.php");
include_once("include_functions.php");
include_once("include_lang.php");

// Umlaute für Excel
function convertToWindowsCharset($string) {
  $charset = mb_detect_encoding($string ?? '',"UTF-8, ISO-8859-1, ISO-8859-15",true);
  $string = mb_convert_encoding($string ?? '', "Windows-1252", $charset);
  $string = html_entity_decode($string ?? '');
  return $string;
}

// If they selected to email the report, this page is called via AJAX, so set some headers
// then check if SMTP is enabled
if(isset($_GET["email_list"])){
  require("include_email_functions.php");
  error_reporting(0);

  header( "Expires: Mon, 20 Dec 1998 01:00:00 GMT" );
  header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
  header( "Cache-Control: no-cache, must-revalidate" );
  header( "Pragma: no-cache" );
  header("Content-type: text/xml;charset=ISO-8859-1");

  $email =& GetEmailObject();

  if ( is_null($email) ){ exit("<pdfsend><smtpstatus>disabled</smtpstatus></pdfsend>"); }
}

//MySQL-Connect
$db=GetOpenAuditDbConnection() or die('Could not connect: ' . mysqli_error($db));
mysqli_select_db($db,$mysqli_database);

//Include the view-definition
if(isset($_REQUEST["view"]) AND $_REQUEST["view"]!=""){
    $include_filename = "list_viewdef_".$_REQUEST["view"].".php";
}else{
    $include_filename = "list_viewdef_all_systems.php";
}
if(is_file($include_filename)){
    include_once($include_filename);
    $viewdef_array=$query_array;
}else{
    die("FATAL: Could not find view $include_filename");
}

    //Executing the Qeuery
    $sql=urldecode($_REQUEST["sql"]);
    $result = mysqli_query($db,$sql);
    if(!$result) {die( "<br>".__("Fatal Error").":<br><br>".$sql."<br><br>".mysqli_error($db)."<br><br>" );};
    $this_page_count = mysqli_num_rows($result);

$csv_data = '';

//Table head
foreach($viewdef_array["fields"] as $field) {
    if($field["show"]!="n" || $field["name"] =="sv_bemerkungen"){
        $csv_data .= '"'.convertToWindowsCharset($field["head"]).'"';
        $csv_data .= ";";
    }
}
$csv_data .= "\r\n";

//Table body
$sumlcpu = 0;
$total_hard_drive_size = 0;
$total_system_memory = 0;
$totals=0;

if ($myrow = mysqli_fetch_array($result)) {
	do {
		foreach($query_array["fields"] as $field) {
			if ($field["head"]=="RAM") { $total_system_memory += (int) $myrow[$field["name"]]; }
			if ($field["head"]=="Größe C") { $total_hard_drive_size += (int) $myrow[$field["name"]]; }
			if ($field["head"]=="l cpu") { $sumlcpu += (int) $myrow[$field["name"]]; }
			if ( $field["head"]=="Anzahl") $totals += (int) $myrow[$field["name"]];
			if ( $field["show"]!="n" && isset($myrow[$field["name"]] ) ) {
				if ($field["name"]=='software_version' || $field["name"]=='sv_version') {
					$csv_data .= '"=""'.convertToWindowsCharset($myrow[$field["name"]]).'"""';
					$csv_data .= ';';
				} else if ( (float) $myrow[$field["name"]] > 20000101000000) {
					$csv_data .= '"'.return_date_time($myrow[$field["name"]]).'"';
					$csv_data .= ';';
				} else {
					$csv_data .= '"'.convertToWindowsCharset($myrow[$field["name"]]).'"';
					$csv_data .= ';';
				}
			} else if ( $field["show"]!="n" ) {
                $csv_data .= '"";';
			}
			if (  $field["name"]=="sv_bemerkungen") {
				$csv_data .= '"'.convertToWindowsCharset($myrow[$field["name"]]).'"';
				$csv_data .= ';';
			}	
        }
        $csv_data .= "\r\n";
    } while ($myrow = mysqli_fetch_array($result));

	// SUM line
	$csv_data .= "\r\n";
	$csv_data .= ($totals > 2 ? $totals : '').';';
	if ($_REQUEST["view"] == 'all_systems_more' || $_REQUEST["view"] == 'all_servers') $csv_data .= ';;';
	$csv_data .= $result->num_rows.';'.'Totals ungefiltert;';
	$csv_data .= ($total_system_memory > 2 ? $total_system_memory : '').';'.($total_hard_drive_size > 2 ? $total_hard_drive_size : '').';;'.($sumlcpu > 2 ? $sumlcpu : '');
	$csv_data .= "\r\n";
}

// set the filename if specified
$filename = (isset($_GET["filename"])) ? $_GET["filename"] . '-' . $_REQUEST["view"] . '.csv' : 'export-' . $_REQUEST["view"] .'.csv';

if (!isset($_GET["email_list"])){
 header("Content-type: application/vnd.ms-excel");
 header("Content-Disposition: attachment; filename=\"$filename\"");
  echo "\xEF\xBB\xBF"; //UTF-8 BOM
  exit("$csv_data");
}
else {
  $username = (isset($_GET["username"])) ? $_GET["username"] : "Unknown";
  $time     = date("F j, Y, g:i a");

  $variables = array(
    '{filename}'  => $filename,
    '{filetype}'  => 'CSV',
    '{username}'  => $username,
    '{timestamp}' => $time
  );

  $subject = "Open-AudIT CSV Report";
  $html    = ParseEmailTemplate($variables,'./emails/export_file.html');

  $attachment = array(
   "Data"         => $csv_data,
   "Name"         => $filename,
   "Content-Type" => "application/vnd.ms-excel",
   "Disposition"  => "attachment"
  );

  $result = SendHtmlEmail($subject,$html,$_GET["email_list"],$email,array($attachment),null);

  $xml  = "<csvsend>";
  $xml .= "<smtpstatus>enabled</smtpstatus>";
  $xml .= "<result>";
  $xml .= (count($result) == 0) ? 'true' : 'false';
  $xml .= "</result>";
  foreach($result as $address){ $xml .= "<email>$address</email>"; }
  $xml .= "</csvsend>";

  exit("$xml");
}
?>
