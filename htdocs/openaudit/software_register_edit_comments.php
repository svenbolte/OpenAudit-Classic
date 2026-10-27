<?php
$page = "";
$extra = "";
$software = "";
$count = 0;
include "include.php";

if (isset($_GET['id'])){$id = $_GET['id'];}else{header("Location: software_register.php?pc=");}

echo "<td style=\"vertical-align:top;width:100%\">\n";
echo "<div class=\"main_each\">";
$sql = "select * from software_register WHERE software_reg_id = '$id'";
$result = mysqli_query($db,$sql);
if ($myrow = mysqli_fetch_array($result)){
    echo "<table class=\"tftable\"    width=\"700\" class=\"content\">";
    echo "<tr><td colspan=\"4\" class=\"contenthead\">Software License Register.<br />&nbsp;</td></tr>\n";
    echo "<tr>";
    echo "<td><b>&nbsp;&nbsp;Package&nbsp;&nbsp;</b></td>";
    echo "<td align=\"center\"><b>&nbsp;&nbsp;Purchased&nbsp;&nbsp;</b></td>";
    echo "<td align=\"center\"><b>&nbsp;&nbsp;Used&nbsp;&nbsp;</b></td>";
    echo "<td align=\"center\"><b>&nbsp;&nbsp;Audit&nbsp;&nbsp;</b></td>";
    echo "</tr>";
    do {
	  $sql3 = "SELECT SUM(license_purchase_number) AS number_purchased FROM software_licenses WHERE license_software_id = '" . $myrow["software_reg_id"] . "'";
	  $result3 = mysqli_query($db,$sql3);
	  $myrow3 = mysqli_fetch_array($result3);
	  
	  $sql4 = "SELECT count(software_name) AS number_used FROM software WHERE software_name = '" . $myrow["software_title"] . "'";
	  $result4 = mysqli_query($db,$sql4);
	  $myrow4 = mysqli_fetch_array($result4);
	  
	  if ($myrow3["number_purchased"] == "") { $number_purchased = 0; } else { $number_purchased = $myrow3["number_purchased"]; }
	  if ($myrow4["number_used"] == "") { $number_used = 0; } else { $number_used = $myrow4["number_used"]; }
	  settype($number_purchased, "integer");
	  settype($number_used, "integer");
	  $number_audit = $number_purchased - $number_used;

	  $font = "<font>";
	  if ($number_audit < "0") { $font = "<font color=\"red\">";}
	  if ($number_audit == "0") { $font = "<font color=\"blue\">";}
	  if ($number_audit > "0") { $font = "<font color=\"green\">";}
	  
      $count = $count + 1;

      echo "<tr>";
      echo "<td>" . $myrow["software_title"] . "&nbsp;&nbsp;</td>";
      echo "<td align=\"center\">" . $number_purchased . "</td>";
      echo "<td align=\"center\">" . $number_used . "</td>";
      echo "<td align=\"center\">" . $font . $number_audit . "</font></td>";
      echo "</tr>";
      echo "</table>";
	  echo "<form action=\"software_register_edit_comments_2.php?id=$id\" method=\"post\">";
	  echo "<table class=\"tftable\"  width=\"700\" class=\"content\">";
      echo "<tr>";
      echo "<td style=\"vertical-align:top;width:100%\"><br /><textarea rows=\"4\" name=\"comments\" cols=\"60\">" . $myrow["software_comments"] . "</textarea></td>";
      echo "</tr>";
      echo "<tr>";
	  echo "<td><input name=\"Submit\" value=\"Submit Comment\" type=\"submit\" /></td>";
	  echo "</tr>";
      echo "</table>";
	  echo "</form>";
    } while ($myrow = mysqli_fetch_array($result));
} else {}


$sql = "SELECT * FROM software_licenses WHERE license_comments <> 'OA initial license' AND license_software_id = '" . $_GET["id"] . "'";
$result = mysqli_query($db,$sql);
if ($myrow = mysqli_fetch_array($result)){
  echo "<table class=\"tftable\" >\n";
  echo "<tr>\n";
  echo "  <td colspan=\"7\" class=\"contenthead\"><br />Software Licenses Purchased.</td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "  <td width=\"10%\" align=\"center\"><b>Purchase Date</b></td>\n";
  echo "  <td width=\"10%\" align=\"center\"><b>Number Purchased</b></td>\n";
  echo "  <td width=\"10%\" align=\"center\"><b>Vendor</b></td>\n";
  echo "  <td width=\"10%\" align=\"center\"><b>Cost Each</b></td>\n";
  echo "  <td width=\"10%\" align=\"center\"><b>License Type</b></td>\n";
  echo "  <td width=\"10%\">&nbsp;</td>\n";
  echo "  <td width=\"40%\"><b>Comments</b></td>\n";
  echo "</tr>\n";
  do {
    echo "<tr>\n";
    echo "  <td align=\"center\">" . substr($myrow["license_purchase_date"],0,10) . "</td>\n";
    echo "  <td align=\"center\">" . $myrow["license_purchase_number"] . "</td>\n";
    echo "  <td align=\"center\">" . $myrow["license_purchase_vendor"] . "</td>\n";
    echo "  <td align=\"center\">" . $myrow["license_purchase_cost_each"] . "</td>\n";
    echo "  <td align=\"center\">" . $myrow["license_purchase_type"] . "</td>\n";
    echo "  <td align=\"center\"><a href=\"software_register_del_license.php?id=" . $myrow["license_id"] . "&amp;id2=" . $_GET["id"] . "\" onclick=\"return confirm('Do you really want to DELETE this license ?','software_register_del_license.php?id=" . $myrow["license_id"] . "&id2=" . $_GET["id"] . "')\">Delete</a></td>\n";
    echo "  <td>" . $myrow["license_comments"] . "</td>\n";
    echo "</tr>\n";
  } while ($myrow = mysqli_fetch_array($result));
  echo "</table>";
} else {}

echo "</div>\n";
echo "</td>\n";

echo "</body>\n";
echo "</html>\n";
?>
