# OpenAudit Classic

Open-Audit Classic ist eine quelloffene Software, die auf einem Windows Server betrieben, per WMI-Anfragen alle Windows PCs und Server mit
ihrer Hardware und Software und Konfiguration erfasst und in einer MySQL-Datenbank speichert.
Die Oberfläche ist komplett in PHP geschrieben und liegt in dieser Form vollumfänglich als Quellcode im Unterordner htdoc/openaudit.

Die notwendigen Basiskomponenten sind ebenfalls Quelloffen und in der Form als Windows-kompilierte Dateien im XAMPP for Windows Projekt
herunterladbar.

https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/

Aus dem Projekt werden nur Apache, Mysql (MariaDB), PHP und PHPMyadmin benötigt.

### Hinweis: Open-Audit ist nun Mariadb 10.11.x kompatibel (Supportende 16. Feb 2028) auf PHP8.3 umgestellt (Supportende 31.12.2027)

Für das Inventarisieren von SNMP- und Netzwerkgeräten (Switches, Kameras, Webserver, IRMCs) wird das ebenfalls quelloffene NMAP mit WinPCAP benötigt
Für eine auch häufig benötigte, einfache Softwareverteilung ist WPKG leicht zu implementieren.
Der Ordner htdocs/ lässt sich auch optimal für eine Entwicklerinstallation von Wordpress verwenden oder für ein kleines Intranet.

Ein optionales Setup-Paket lässt sich mit Innosetup erstellen. Es fügt diese Komponenten zu einem installierbaren Paket zusammen.

## Entstehungsgeschichte

in den 2000er Jahren wurde Open-Audit als Open Source Software erstellt und war komplett als Quellcode verfügbar.
Seit der Haupt-Entwickler von einer Firma übernommen wurde, wird dort das Projekt "Open-AudIT" als Freemium Modell mit AGPL Lizenz weiterentwickelt.
Weil damit Teile der Software Closed Source sind aufgrund einer grundlegenden Neuprogrammierung wurde die Weiterentwicklung eigener Vorstellungen damit komplizierter.
Daher habe ich mich entschlossen, auf Basis der Open-Audit Stands, der noch unter der echten GPL-Lizenz stammt, diesen zu "forken" und weiter zu entwickeln.
Das Resultat ist "OpenAudit Classic". 

Berücksichtigt wurden Anpassungen auf neue PHP-Versionen, MySQL und Apache, häufig benutzte zusätzliche Auswertungen, Erweiterungen in der WMI-Erkennung und vieles mehr
Auch die Integration von NMAP und WinPCap wurde auf den aktuellen Stand gebracht.

Mein OpenAudit Classic Fork ist AGPLv3 lizenziert --> GPL.txt im htdocs/openaudit Verzeichnis

-This Fork and further development on it is based on Open-Audit when 2010.08.31-


= Entwicklung =

Der Projekt-Fork ist unter Github zu finden. Beiträge und Weiterentwicklung jederzeit willkommen.


= Setup selbst erstellen =

Die notwendigen Basiskomponenten sind ebenfalls Quelloffen und in der Form als Windows-kompilierte Dateien im XAMPP for Windows Projekt
herunterladbar.

https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/

Aus dem Projekt werden nur Apache, Mysql (MariaDB), PHP und PHPMyadmin benötigt.

Für das Inventarisieren von SNMP- und Netzwerkgeräten (Switches, Kameras, Webserver, IRMCs) wird das ebenfalls quelloffene NMAP mit WinPCAP benötigt
Für eine auch häufig benötigte, einfache Softwareverteilung ist WPKG leicht zu implementieren.
Der Ordner htdocs/ lässt sich auch optimal für eine Entwicklerinstallation von Wordpress verwenden oder für ein kleines Intranet.

Ein optionales Setup-Paket lässt sich mit Innosetup erstellen. Es fügt diese Komponenten zu einem installierbaren Paket zusammen.
