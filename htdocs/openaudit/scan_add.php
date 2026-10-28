<?php
$page = "";
$extra = "";
$software = "";
$count = -1;
if (isset($_GET['software'])) {$software = $_GET['software'];} else {}
if (isset($_GET['sort'])) {$sort = $_GET['sort'];} else {$sort= "system_name";}
include "include.php";

echo "<td style=\"vertical-align:top;width:100%\">\n";
echo "<div class=\"main_each\">";
echo "<div id=\"ajaxTest\"> </div>\n";
#echo "<form name=\"nmap_form\" type=\"post\">\n";
echo "<table class=\"tftable\"    >\n";
echo "<tr>\n";
echo "  <td class=\"contenthead\" colspan=\"5\">Network Monitoring.<br />&nbsp;</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td width=\"15%\"><b>Host</b></td>\n";
echo "<td align=\"center\" width=\"15%\"><b>IP Address</b></td>\n";
echo "<td align=\"center\" width=\"15%\"><b>Type</b></td>\n";
echo "<td align=\"center\" width=\"15%\"><b>Detail</b></td>\n";
echo "<td align=\"center\" width=\"25%\"><b>Time</b></td>\n";
echo "<td align=\"center\" width=\"15%\"><b>Submit</b></td>\n";
echo "</tr>\n";

#$sql = "SELECT system_uuid, system_name, network_card.net_ip_address FROM system, network_card WHERE system_uuid = net_uuid ORDER BY system_name";
$sql = "SELECT system_uuid, system_name, net_ip_address FROM system ORDER BY system_name";
$db=GetOpenAuditDbConnection() or die("Could not connect");
mysqli_select_db($db,$mysqli_database) or die("Could not select database");
$result = mysqli_query($db,$sql);
if ($myrow = mysqli_fetch_array($result)){
  do{
    
    echo "<tr>\n";
    echo " <td>" . $myrow['system_name'] . "</td>\n";
    echo " <td align=\"center\"><input type=\"text\" id=\"ip_" . $myrow['system_uuid'] . "\" name=\"ip_" . $myrow['system_uuid'] . "\" value=\"" . ip_trans($myrow['net_ip_address']) . "\" size=\"12\" /></td>\n";
    echo " <td align=\"center\">\n";
    echo "  <select id=\"type_" . $myrow['system_uuid'] . "\"> name=\"type_" . $myrow['system_uuid'] . "\">\n";
    echo "   <option value=\"port\" selected>PortScan</option>\n";
    echo "  </select>\n";
    echo " </td>\n";
    echo " <td align=\"center\"><input type=\"text\" id=\"detail_" . $myrow['system_uuid'] . "\" name=\"detail_" . $myrow['system_uuid'] . "\" value=\"25\" size=\"4\" /></td>\n";
    echo " <td align=\"center\">\n";
    echo "  <select id=\"time_" . $myrow['system_uuid'] . "\"> name=\"time_" . $myrow['system_uuid'] . "\">\n";
    echo "   <option value=\"1\">1</option>\n";
    echo "   <option value=\"5\" selected>5</option>\n";
    echo "   <option value=\"10\">10</option>\n";
    echo "   <option value=\"15\">15</option>\n";
    echo "   <option value=\"20\">20</option>\n";
    echo "   <option value=\"30\">30</option>\n";
    echo "   <option value=\"45\">45</option>\n";
    echo "   <option value=\"60\">60</option>\n";
    echo "  </select>\n";
    echo " </td>\n";
    echo " <td align=\"center\"><div id=\"sub_" . $myrow['system_uuid'] . "\"><a href=\"#\" onclick=\"sendRequest('" . $myrow["system_uuid"] . "');\"><i class=\"fa fa-check-circle\" aria-hidden=\"true\"></i></a></div></td>\n";
    echo "</tr>\n";
  } while ($myrow = mysqli_fetch_array($result));
}    






echo "</table>";
#echo "</form>\n";
echo "</div>";
echo "</td>";


?>
<script language="javascript" TYPE="text/javascript">

function createRequestObject() {
  var req;
  if(window.XMLHttpRequest){
    // Firefox, Safari, Opera...
    req = new XMLHttpRequest();
  } else if(window.ActiveXObject) {
    // Internet Explorer 5+
    req = new ActiveXObject("Microsoft.XMLHTTP");
    } else {
      // There is an error creating the object,
      // just as an old browser is being used.
      alert('Problem creating the XMLHttpRequest object');
    }
  return req;
}

// Make the XMLHttpRequest object
var http = createRequestObject();

function sendRequest(act) {
  // Open PHP script for requests
  type = document.getElementById('type_'+act).value;
  time = document.getElementById('time_'+act).value;
  detail = document.getElementById('detail_'+act).value;
  ip = document.getElementById('ip_'+act).value;
  act=act+'&ip='+ip;
  act=act+'&time='+time;
  act=act+'&detail='+detail;
  act=act+'&type='+type;
  http.open('get', 'scan_add_ajax.php?uuid='+act);
  http.onreadystatechange = handleResponse;
  http.send(null);
}

function handleResponse() {
  if(http.readyState == 4 && http.status == 200){
    // Text returned FROM the PHP script
    var response = http.responseText;
    if(response) {
      // UPDATE ajaxTest content
      document.getElementById('sub_'+response).innerHTML = "added";
    }
  }
}

/*
function handleResponse() {
  if(http.readyState == 4 && http.status == 200){
    // Text returned FROM the PHP script
    var response = http.responseText;
    if(response) {
      // UPDATE ajaxTest content
      document.getElementById(response).innerHTML = "Added";
    }
  }
}
*/
</script>
<?php
echo "</body>";
echo "</html>";
?>
