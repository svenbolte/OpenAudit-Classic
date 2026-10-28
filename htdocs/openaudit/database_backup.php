<?php
//    Open Audit Backup Database
//    backup to folder with current timestamp. 
include "include.php";
//$this_page="https://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']; 
//echo "<meta name=\"refresh\" content=\"10;".$this_page."\">";
//
//function microtime_float()
//{
//    list($usec, $sec) = explode(" ", microtime());
//    return ((float)$usec + (float)$sec);
//}



$newline = "\r\n";
$page = "database_backup.php";
 
// This is a bit crude... FIXME I need to find a more interactive method for this, rather than just leaving the script for 5 mins, and then timing out if no result. 
set_time_limit(300); 
$backup = '';

echo "<td style=\"vertical-align:top;width:100%\">$newline";
echo "<div class=\"main_each\">$newline";
echo "<table>$newline";
echo "  <tr><td class=\"contenthead\">".__("Backing up the Database")."</td></tr>";
echo "  <tr><td colspan=\"3\"><hr /></td></tr>";
echo "<tr><td>".__("The following tables were found")."</td><td>".__("Length")."</td><td>".__("Connectable")."</td></tr>";
echo "  <tr><td colspan=\"3\"><hr /></td></tr>";
$today = date("d-m-Y G.i.s");
$backup_dir = './backup/';

if (!file_exists($backup_dir)) {
   mkdir($backup_dir);
} 
define ('Name','Name');
define ('Auto_increment','Auto_increment');

$backup_filename = $backup_dir.'OA Backup'.$today.'.sql';
$handle = fopen($backup_filename, "w");
$table_len=strlen($backup);

$tab_status = mysqli_query($db,"SHOW TABLE STATUS");
while($all = mysqli_fetch_assoc($tab_status)):
   $tbl_stat[$all[Name]] = $all[Auto_increment];
endwhile;
unset($backup);

//$tables = mysqli_list_tables($mysqli_database); Depreciated... use SHOW TABLES FROM instead... 
$tables = mysqli_query($db,"SHOW TABLES FROM $mysqli_database");

$date_time = date('l dS \of F Y h:i:s A');

$backup = '';
$url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$backup .= "-- -------------------http://www.open-audit.org//-------------------$newline";
$backup .= "-- --------------------------------------------------------$newline";
$backup .= "-- ----------  $date_time   -----------$newline";
$backup .= "-- ----------  ".__("Open Audit Database Backup")."  -----------$newline";
$backup .= "-- ----Database Name --".$mysqli_database."----$newline";
$backup .= "-- Created by .. $url --$newline";
$backup .= "-- --------------------------------------------------------$newline";

$file_len=strlen($backup);

while($tabs = mysqli_fetch_row($tables)):
   $backup .= "--$newline-- ".__("Table structure for")." `$tabs[0]`".$newline."--".$newline."DROP TABLE IF EXISTS `$tabs[0]`;".$newline."CREATE TABLE IF NOT EXISTS `$tabs[0]` (".$newline;
   
   $res = mysqli_query($db,"SHOW CREATE TABLE $tabs[0]");
   //echo "<tr><td>". __($tabs[0])."</td><td>$tabs[0]</td></tr>";
    
    $table_len=strlen($backup)-$file_len;
    $file_len=strlen($backup);
    echo "<tr><td>$tabs[0]</td><td>".$table_len." ".__("Bytes")."</td><td><i class=\"fa fa-check-circle\" aria-hidden=\"true\"></i></td></tr>";
   // tabellierung über tftable css
   while($all = mysqli_fetch_assoc($res)):
       $str = str_replace("CREATE TABLE `$tabs[0]` (", "", $all['Create Table']);
       $str = str_replace(",", ", ".$newline."", $str);
       $str2 = str_replace("`) ) TYPE=MyISAM ", "`)".$newline." ) TYPE=MyISAM ", $str);
       $backup .= $str2.";".$newline;
       //" AUTO_INCREMENT=".$tbl_stat[$tabs[0]].";".$newline.$newline;
   endwhile;
   $backup .= "--$newline-- ".__("All Data from table")." `$tabs[0]`".$newline."--".$newline.$newline;
   $data = mysqli_query($db,"SELECT * FROM $tabs[0]");
   while($dt = mysqli_fetch_row($data)):
       $backup .= "INSERT INTO `$tabs[0]` VALUES('$dt[0]'";
       for($i=1; $i<sizeof($dt); $i++):
           $backup .= ", '".addslashes($dt[$i])."'";
       endfor;
       $backup .= ");".$newline;
   endwhile;
   $backup .= $newline."-- ----------------".__("End of table")."----------------------------".$newline;
endwhile;
$backup .= $newline."-- ----------------".__("End of backup")."----------------------------".$newline.$newline;
// Let's make sure the file exists and is writable first.

if (is_writable($backup_filename)) {

   if (!$handle = fopen($backup_filename, 'w+')) {
         echo "<tr><td>".__("Could not open file (").$backup_filename.")</td></tr>";
         exit;
   }

   // Write $somecontent to our opened file.
   if (fwrite($handle, $backup) === FALSE) {
       echo "<tr><td>".__("Could not write to file (").$backup_filename.")</td></tr>";
       exit;
   }
  $database_length = strlen($backup);
//      // tabellierung über tftable css
   echo "  <tr><td colspan=\"3\"><hr /></td></tr>";
   echo "<tr><td class=\"contentsubtitle\">".__("Success, wrote ").$database_length." ".__("bytes to file").$backup_filename."</td><td></td><td></td></tr>";
  
   fclose($handle);

   
//     // tabellierung über tftable css
   echo "<tr><td class=\"contentsubtitle\">".__("Backup Completed")."</td><td></td><td></td></tr>";
   echo "<tr><td>".__("Database Backed up"). "</td></tr>";
} else {
   echo "<tr><td>".__("The file $backup_filename is not writable")."</td></tr>";
}

echo "</tr></td></table>\n";



echo "</body>\n</html>\n";



?>
