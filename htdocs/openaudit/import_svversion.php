<?php
// importiert softwareversionen CSV Datei in Mysql Tabelle softwareversionen

include_once("include.php");

echo '</tr><tr><td> Datei wird heruntergeladen vom Webserver...und importiert, Status siehe im Kasten links unter dem Menü.
	Verfolgen Sie die Meldung in der Statusbox unter dem Menü und rufen dann eine beliebiege Seite danach auf.';

svversionenimport(1);    // nur maximal 1x pro Minute bei Aufruf aktualisieren

echo '</td></tr></table></body></html>';

?>
