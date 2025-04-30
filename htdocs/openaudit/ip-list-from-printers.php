<?php
	include_once("include.php");

	echo "<td style=\"vertical-align:top;width:100%\">\n";
	echo "<div class=\"main_each\">";
	echo "<table><tr><td class=\"contenthead\">\n";
	echo 'IP-Liste aus Netzwerkdruckern bauen für IPScanner, Openaudit und für 00-kunde-netzwerk-ips.xlsx</td></tr></table>';
	echo "<table ><tr><td style=\"padding:2rem\">";

$sqltemp = "SELECT other_description, other_p_port_name, other_value, other_location 
            FROM other 
            WHERE other_type = 'printer' 
              AND other_p_port_name LIKE '%.%.%.%' 
            ORDER BY other_p_port_name"; 

$result_all = mysqli_query($db, $sqltemp);
set_time_limit(100);

if (!$result_all) {
    die("<br>Fatal Error:<br><br>" . $sqltemp . "<br><br>" . mysqli_error($db) . "<br><br>");
}

// Netzwerk-Gruppierung
$networks = [];

while ($row = mysqli_fetch_assoc($result_all)) {
    $ip = $row['other_p_port_name'];
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $parts = explode('.', $ip);
        if (count($parts) == 4) {
            $net = "{$parts[0]}.{$parts[1]}.{$parts[2]}.0/24";

            // Wenn Netzwerk noch nicht existiert, speichern wir den ersten Eintrag
            if (!isset($networks[$net])) {
                $networks[$net] = [
                    'network' => $net,
                    'value' => $row['other_value'],
                    'location' => $row['other_location']
                ];
            }
        }
    }
}

// HTML-Ausgabe Tabelle 1: Netzwerke mit erster Zuordnung
echo "<h2>Netzwerk-Zusammenfassung (/24)</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Netzwerk (x.y.z.0/24)</th><th>other_value (erster Eintrag)</th><th>other_location (erster Eintrag)</th></tr>";

foreach ($networks as $net => $data) {
    echo "<tr>";
    echo "<td>{$data['network']}</td>";
    echo "<td>{$data['value']}</td>";
    echo "<td>{$data['location']}</td>";
    echo "</tr>";
}
echo "</table>";

// Kommagetrennter String aller Netzwerke
$networkList = implode(', ', array_keys($networks));

echo "<h3>Liste der IP-Netze (/24) als String:</h3>";
echo "<p>$networkList</p>";

?>