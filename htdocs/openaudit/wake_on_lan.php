<?php
// wake_on_lan.php sends WOL packet to mac address specified in database.
// Accepts hostname, mac and socket number, requires hostname and mac.
include_once("include.php");


$count_system_max="10000";

echo "<td>\n";
$hostname=$_GET["hostname"];
$mac=$_GET["mac"];
if (isset($_GET["socket_number"])) {$socket_number=$_GET["socket_number"];} else {$socket_number="12287";}
// Set the Search Button action
echo "<div class=\"main_each\">";
echo "<form action=\"search.php?sub=no\" method=\"post\">";
echo "<table class=\"tftable\"    class=\"content\">";


$this_error = '';
// Fire off the WOL magic packet.
$result = WakeOnLan($hostname,$mac,$socket_number,$this_error);
// Show the result
		echo "<td><i class='fa fa-circle-o' aria-hidden='true'></i><td><b>".__("Wake on LAN")."</b></td>";
//      Show Hostname
// tabellierung über tftable css
        echo "<tr><td>".__("Hostname").":</td><td>" .$hostname . "</td></tr>";
//      Show Target MAC address
// tabellierung über tftable css
        echo "<tr><td>".__("Mac Address").":</td><td>" .$mac. "</td></tr>";
//      Show Target Socket Number
// tabellierung über tftable css
        echo "<tr><td>".__("Socket Number").":</td><td>" .$socket_number. "</td></tr>";
//      Show Result
// tabellierung über tftable css
        echo "<tr><td>".__("Result").":</td><td>" .$result. "</td></tr>";
// tabellierung über tftable css
        echo "<tr><td>".__("NOTE:  Wake on LAN only works on the local LAN and not between networks.").":</td><td></td></tr>";           

echo "</div>\n";
echo "</table>";
echo "</td>\n";
echo "</td>\n";
echo "</body>\n";
echo "</html>\n";
?>
