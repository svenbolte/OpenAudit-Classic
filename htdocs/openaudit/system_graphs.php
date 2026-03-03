<?php
$page = "graphs";
include "include.php";

echo "<td style=\"vertical-align:top;width:100%\">\n";
echo "<div class=\"main_each\">";
echo "<table class=\"tftable\"    >\n";
echo "<tr><td class=\"contenthead\" colspan=\"4\">".__("Partition Usage for") ." ". $myrow["net_ip_address"] . " - " . $myrow["system_name"] . "</td></tr>";
$disk_letter_old = "";
$sql = "SELECT * FROM graphs_disk WHERE disk_uuid = '$pc' ORDER BY disk_letter, disk_timestamp";
$result = mysqli_query($db,$sql);
echo "<tr><td style=\"vertical-align:top;width:100%\">";
if ($myrow = mysqli_fetch_array($result)){
  do {
    if ($myrow['disk_letter'] == $disk_letter_old){
    } else {
      
      echo "<hr /></td></tr>";
      echo "<tr><td>".__("Drive").": " . preg_replace ("/:/", "", $myrow['disk_letter']) . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;      ";
      $sql2 = "select * FROM `partition` where partition_uuid = '$pc' and partition_caption = '" . $myrow['disk_letter'] . "'";
      $result2 = mysqli_query($db,$sql2);
      $myrow2 = mysqli_fetch_array($result2);
      echo __("Partition Size") . ": " . number_format($myrow2['partition_size']) . " MB&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
      echo __("Current Free Space") .": " . number_format($myrow2['partition_free_space']) . "&nbsp;MB&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
      $used = number_format($myrow2['partition_size'] - $myrow2['partition_free_space']);
      echo __("Current Disk Used") . ": " . $used . " MB</td></tr>";
     if (isset($myrow2['partition_size']) and  (($myrow2['partition_size']) >0.001)){ 
     $percent_free = round((($myrow2['partition_free_space'])/(($myrow2['partition_size'] ))*100),$round_to_decimal_places);
        } else {
        $percent_free  = 0;
        }
      echo "<tr><td>";
      echo "<img src=\"system_graphs_pie.php?disk_percent=".$percent_free."&width=140&height=140\" alt\"\"/>";
      echo "<img src=\"images/graph_side.gif\" alt=\"\" />";

    }
    $disk_percent = $myrow['disk_percent'];
    $disk_time = date("d M Y H:i:s", strtotime($myrow['disk_timestamp']));
    
    if (isset($myrow2['partition_size']) and  (($myrow2['partition_size']) >0.001)){ 
    
    $disk_free_warn = (($myrow2['partition_size'] -$partition_free_space)/($myrow2['partition_size']))*100;
    } else {
    $disk_percent = 100;
    $disk_free_warn = 100;
    }
    


    if ($disk_free_warn > 100){
        $disk_free_warn = 100;
    } else {
          if ($disk_free_warn < 0){
        $disk_free_warn = 0;
        }
    }
            
    echo "<img src=\"system_graphs_image.php?disk_percent=" . $disk_percent . "&disk_free_warn=".$disk_free_warn."\" alt=\"".__("Partition").": " . preg_replace ("/:/", "", $myrow['disk_letter']) . "--\r";
    echo __("Percent Used").": " . $disk_percent . "% \n";
    echo __("Timestamp").": " . $disk_time  . "\" title=\"".__("Partition").": " . preg_replace (":", "", $myrow['disk_letter']) . ": ";
    echo __("Percent Used").": " . $disk_percent . "% ";
    echo __("Timestamp").": " . $disk_time  . "\" />";
    $disk_letter_old = $myrow['disk_letter'];
  }
while ($myrow = mysqli_fetch_array($result));
} else {}
echo "</td></tr>";

echo "</table>\n";
echo "</div>\n";
echo "</td>\n";

include "include_export_modal.php"; 
echo "</body>\n";
echo "</html>\n";


function FncChangeTimestamp ($svDate, $svDateOutput)
  {
    $year  = substr($svDate,0,4);
    $month = substr($svDate,5,2);
    $day   = substr($svDate,8,2);
    $hour  = substr($svDate,11,2);
    $minute= substr($svDate,14,2);
    $sec   = substr($svDate,17,2);
    $svDateOutput = preg_replace ("YYYY", $year, $svDateOutput);
    $svDateOutput = preg_replace ("MM", $month, $svDateOutput);
    $svDateOutput = preg_replace ("DD", $day, $svDateOutput);
    $svDateOutput = preg_replace ("hh", $hour, $svDateOutput);
    $svDateOutput = preg_replace ("mm", $minute, $svDateOutput);
    $svDateOutput = preg_replace ("ss", $sec, $svDateOutput);
    return $svDateOutput;
  };
?>
