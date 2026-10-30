OPENAUDIT CLASSIC
=================
Technische Dokumentation, Installation und Betrieb
Stand: 29.10.2026

1. ÜBERBLICK
------------
OpenAudit Classic ist eine quelloffene Lösung zur Inventarisierung von
Windows-PCs und Windows-Servern. Über WMI werden Hardware-, Software- und
Konfigurationsdaten erfasst, in einer MariaDB-Datenbank gespeichert und über
eine in PHP geschriebene Weboberfläche ausgewertet.

Ergänzend können mit Nmap Netzwerkgeräte wie Switches, Kameras und andere
IP-Geräte untersucht werden. Für einfache Softwareverteilung lässt sich
WPKG ergänzen. Das mitgelieferte Webserver-Umfeld kann optional auch für
ein kleines Intranet mit WordPress verwendet werden.

Projekt: https://github.com/svenbolte/Open-Audit-Classic
Fachartikel: https://tech-nachrichten.de/openaudit-classic-hard-software-inventar/

2. ENTSTEHUNG UND LIZENZ
------------------------
OpenAudit Classic ist ein Fork des früheren Open-Audit auf Basis des Standes
2010.08.31. Nach der Weiterentwicklung des ursprünglichen Projekts zu
Open-AudIT entstand dieser Fork, um den Quellcode unabhängig weiterzuführen
und an neuere PHP-, Datenbank- und Webserverversionen anzupassen. Hinzu
kamen weitere Auswertungen, Verbesserungen der WMI-Erkennung sowie die
Integration von Nmap und Paketerfassung.

Die ursprüngliche README bezeichnet den Fork als AGPLv3-lizenziert und
verweist auf GPL.txt im Verzeichnis htdocs/openaudit. Für die rechtlich
verbindlichen Lizenzbedingungen ist die mitgelieferte Lizenzdatei maßgeblich.
Beiträge zum Projekt sind willkommen.

3. ARCHITEKTUR UND KOMPONENTEN
-----------------------------
Serverplattform: Windows, mit Apache, PHP, MariaDB und phpMyAdmin.
Webanwendung: htdocs/openaudit (PHP-Quellcode).
Windows-Inventarisierung: WMI und zugehörige Scan-Skripte.
Netzwerk-Inventarisierung: Nmap mit Npcap (in älteren Unterlagen WinPcap).
Optionale Komponenten: WordPress für Intranet, WPKG für Softwareverteilung.

Die Original-README nennt MariaDB 10.11.x und PHP 8.3. Das vorliegende
Inno-Setup-Skript nennt für sein Installationspaket hingegen:
  Apache 2.4.68 x64
  MariaDB 10.11.19 x64 (LTS)
  PHP 8.4.27 x64, thread-safe
  phpMyAdmin 5.2.3
  Nmap 7.991 und Npcap 1.89
  MariaDB ODBC 3.2.9
  Visual-C++-Runtimes
  optional WordPress 7.1.3

Diese Angaben beschreiben unterschiedliche Dokumentations- bzw. Paketstände.
Die tatsächlich installierten Versionen sind auf dem Zielsystem zu prüfen.

Die Windows-kompilierten Basiskomponenten können aus dem XAMPP-Projekt
bezogen werden: https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/
Benötigt werden daraus Apache, MariaDB/MySQL, PHP und phpMyAdmin.

4. INSTALLATION MIT DEM VORLIEGENDEN SETUP
-----------------------------------------
Das Installationsskript openaudit-install.iss ist für Inno Setup 7.1.0
vorgesehen. Es verlangt Administratorrechte. Der Standard-Zielordner ist
{commonpf}\xampplite, üblicherweise unter Programme/Program Files.
Der tatsächlich verwendete Pfad kann von Windows und Systemarchitektur
abhängen.

Das Skript bietet zwei Installationskomponenten:
  - Nur OpenAudit Classic (typische Installation)
  - OpenAudit Classic mit WordPress (benutzerdefiniert)

Zusätzlich sind Desktopverknüpfungen und der Import geplanter Aufgaben
wählbar. Der Import des PC-Scans ist vorausgewählt; der Nmap-Scan ist
optional. Die Aufgaben werden aus XML-Dateien im Skriptverzeichnis
htdocs\openaudit\scripts über schtasks.exe angelegt:
  Openaudit-PCScan
  Openaudit-NMAPScan

Beim Installationsstart versucht das Setup, die Dienste apache2.4 und mysql
zu stoppen. Vor Updates daher unbedingt Datenbank und Konfiguration sichern.

5. SCHRITTE NACH DER INSTALLATION
--------------------------------
Das Setup bietet folgende Nachinstallationsaktionen an:
  - SSL-Zertifikat mit apache\makecert2.cmd erzeugen
  - Zertifikat mit apache\oa-importcert.cmd importieren
  - Apache-Dienst mit apache_installservice-win10.cmd installieren/starten
  - MySQL/MariaDB-Dienst mit mysql_installservice-win10.cmd installieren/starten
  - Npcap installieren, soweit Nmap genutzt wird
  - benötigte Visual-C++-Runtimes installieren

Die Weboberfläche ist laut Installationsskript erreichbar unter:
  https://<Servername>:4443/openaudit

Die Startmenüverknüpfungen enthalten außerdem eine Konsole im Ordner
htdocs\openaudit\scripts, eine Verknüpfung zur Aufgabenplanung, ein
Werkzeug zum Erzeugen der PC-/IP-Liste und einen Zertifikatserneuerungsaufruf.

6. INVENTARISIERUNG UND SCANS
----------------------------
Windows-Systeme werden über WMI abgefragt. Voraussetzung sind passende
Berechtigungen, Erreichbarkeit sowie die erforderlichen Windows-Dienste
und Firewallfreigaben. Hardware-, Software- und Konfigurationsdaten
werden anschließend in der Datenbank abgelegt.

Für Netzgeräte dient Nmap als Ergänzung. Nmap-Ergebnisse sind von den
gewählten Scanparametern, der Netzwerkerreichbarkeit und den lokalen
Sicherheitsregeln abhängig.

Die Scan-Skripte und ihre Konfiguration befinden sich im Unterordner
htdocs\openaudit\scripts. Im Setup wird außerdem auf die Datei
pc_list_file.txt verwiesen. Die PC-/IP-Liste soll nach Möglichkeit über
die Weboberfläche erzeugt und nur ausnahmsweise manuell geändert werden.

Der Fachartikel beschreibt zusätzlich clientseitige und Offline-Scanwege
für Geräte, die vom zentralen Server nicht direkt inventarisiert werden
können. Die dafür benötigten Skripte und Einstellungen sind je nach
installiertem Projektstand zu prüfen.

7. WEB-OBERFLÄCHE UND AUSWERTUNGEN
---------------------------------
Die PHP-Weboberfläche stellt Inventarlisten für Hardware und Software
sowie zusätzliche Auswertungen bereit. Je nach Projektstand stehen
Such- und Sortierfunktionen, Statistiken und Exportmöglichkeiten zur
Verfügung. Die tatsächlich verfügbaren Ansichten richten sich nach den
installierten PHP-Dateien und der Datenbankstruktur.

8. BETRIEB, WARTUNG UND SICHERHEIT
---------------------------------
Regelmäßig sichern:
  - MariaDB-Datenbank
  - Webanwendung und lokale Anpassungen
  - Konfigurationsdateien und Scan-Ziellisten
  - geplante Aufgaben und erforderliche Zertifikate

Nach Installation oder Update prüfen:
  - Apache- und MariaDB-Dienste laufen
  - Weboberfläche ist über HTTPS erreichbar
  - Inventarisierungsaufgaben starten erfolgreich
  - Geräte und Software erscheinen aktuell in den Listen
  - Nmap und Npcap funktionieren, falls eingesetzt

Sicherheitshinweise zum mitgelieferten Installationsskript:
  - Das Setup vergibt im Zielordner die Berechtigung users-full.
    Für produktive Systeme sollten Schreibrechte gezielt eingeschränkt
    und nur notwendigen Konten bzw. Verzeichnissen gewährt werden.
  - Die geplanten Scanaufgaben werden zunächst mit /RU SYSTEM angelegt.
    Berechtigungen und Ausführungskontext nach der Installation prüfen.
  - Zertifikate, Firewallregeln und Zugriff auf die Weboberfläche prüfen.
  - Inventardaten und administrative Zugangsdaten geschützt aufbewahren.
  - Bei einer Deinstallation entfernt das Setup die Apache- und
    MariaDB-Dienste über mitgelieferte Skripte.

9. SETUP-PAKET SELBST ERSTELLEN
------------------------------
Zum Paketbau das Skript openaudit-install.iss mit Inno Setup kompilieren.
Das vorliegende Skript erwartet als Paketquelle:
  C:\temp\xampplite\
und verwendet für Ausgabedateien:
  C:\temp\

Es referenziert unter anderem die Dateien openaudit_logo.ico,
openaudit_logo.bmp, die Installationsskripte, Npcap und Visual-C++-Pakete.
Vor dem Kompilieren alle Dateipfade und vorhandenen Paketversionen prüfen.
Die Setup-Ausgabedatei trägt den Basisnamen openaudit-cl-setup.

10. FEHLERBEHEBUNG
-----------------
Keine oder unvollständige Windows-Inventardaten:
  Erreichbarkeit, WMI-Berechtigungen, Firewall, Aufgabenplanung und
  Protokolle der Scan-Skripte prüfen.

Keine Nmap-Ergebnisse:
  Zielnetze, Nmap/Npcap-Installation, Aufgabenplanung und
  Netzwerkfreigaben prüfen.

Weboberfläche nicht erreichbar:
  Apache-Dienst, Port 4443, HTTPS-Konfiguration und Zertifikat prüfen.

SQL-Fehler in Listen oder Filtern:
  MariaDB-Fehlermeldung, konkrete SQL-Abfrage und betroffenen Filter
  prüfen. Insbesondere bei Abfragen mit WITH/CTE muss die Filterbedingung
  an der syntaktisch korrekten Stelle stehen. Eine reine PHP-Syntaxprüfung
  ersetzt keinen Funktionstest mit MariaDB.

11. UPDATE UND WIEDERHERSTELLUNG
---------------------------------
Die vollstaendige Update-Anleitung ist auch separat in oaupdates.txt
enthalten.

WICHTIG - VERSIONEN VOR DEM 01.08.2026
---------------------------------------
Fuer alle OpenAudit-Classic-Versionen vor dem 01.08.2026 ist eine
vollstaendige Neuinstallation und Neueinrichtung erforderlich.
Ein Update ueber die bestehende Installation nach Abschnitt 2 oder 3
ist fuer diese Altversionen nicht vorgesehen.

Vorgehen bei Altversionen vor dem 01.08.2026:
1. Vorhandene Konfigurationen bei Bedarf separat sichern und pruefen,
   ob sie noch aktuell sind.
2. OpenAudit Classic vollstaendig deinstallieren.
3. Verbliebene Dateien und Unterordner unter xampplite entfernen.
   Vor dem Loeschen sicherstellen, dass dort keine anderweitig
   benoetigten Daten oder Anwendungen liegen.
4. Die OpenAudit-Aufgaben in der Windows-Aufgabenplanung entfernen,
   insbesondere Openaudit-PCScan und Openaudit-NMAPScan, soweit vorhanden.
5. OpenAudit Classic neu installieren und vollstaendig neu einrichten.

Es wird ausdruecklich empfohlen, weder eine alte Datenbanksicherung
zurueckzuspielen noch den bisherigen Ordner mysql\data zu uebernehmen.
Die Dateien scripts\audit.config,
scripts\pc_list_file.txt und
oa-clientside-scan\audit.config koennen dagegen uebernommen werden,
sofern ihre Inhalte noch aktuell sind. Die Einstellungen muessen vor
Wiederverwendung auf Gueltigkeit und Kompatibilitaet geprueft werden.

Die folgenden regulaeren Updateverfahren gelten nur fuer Installationen
ab dem 01.08.2026 und setzen eine passende Versionskompatibilitaet voraus.

1. VORBEREITUNG UND DATENSICHERUNG
---------------------------------
1. Installierte Versionen, Dienste, Zielpfade und eigene Anpassungen notieren.
2. In der OpenAudit-Classic-Administrationsoberflaeche eine Datenbanksicherung
   erstellen und die Sicherungsdatei ausserhalb des Installationsordners ablegen.
3. Dienste mit Administratorrechten stoppen:
   C:\Program Files (x86)\xampplite\stopservices.cmd
4. Zusaetzlich bei gestopptem MariaDB-Dienst das komplette Verzeichnis sichern:
   C:\Program Files (x86)\xampplite\mysql\data\
5. Folgende drei Dateien separat sichern:
   C:\Program Files (x86)\xampplite\htdocs\openaudit\scripts\audit.config
   C:\Program Files (x86)\xampplite\htdocs\openaudit\scripts\pc_list_file.txt
   C:\Program Files (x86)\xampplite\htdocs\openaudit\oa-clientside-scan\audit.config
6. Weitere eigene Aenderungen, Zertifikate, Apache-/PHP-/MariaDB-Konfiguration
   und geplante Aufgaben bei Bedarf ebenfalls sichern.
7. Sicherungen auf Vollstaendigkeit und Wiederherstellbarkeit pruefen.

WICHTIG: Ein Verzeichniskopie von mysql\data ist kein Ersatz fuer einen
konsistenten logischen Datenbankexport. Datenverzeichnisse nur bei gestopptem
Dienst sichern oder zurueckkopieren. Bei Versionswechseln von MariaDB ist
eine direkte Rueckkopie nicht generell sicher oder kompatibel.

2. VARIANTE A - NUR OPENAUDIT-PROGRAMMCODE AKTUALISIEREN
------------------------------------------------------
1. Das passende openaudit.zip von tech-nachrichten.de herunterladen.
2. Herkunft, Version und Paketinhalt vor dem Einspielen kontrollieren.
3. ZIP-Datei entpacken und die Programmdateien nach
   C:\Program Files (x86)\xampplite\htdocs\openaudit\
   kopieren; bestehende Dateien nach Pruefung ueberschreiben.
4. Die drei gesicherten Dateien aus Abschnitt 1 wiederherstellen und
   insbesondere geaenderte Konfigurationsoptionen mit der neuen Version
   abgleichen, statt neue Einstellungen blind zu ueberschreiben.
5. Dienste starten und die Pruefungen in Abschnitt 5 durchfuehren.

3. VARIANTE B - OPENAUDIT-CLASSIC-SETUP ERNEUT AUSFUEHREN
-------------------------------------------------------
1. Das passende OpenAudit-Classic-Setup von tech-nachrichten.de beziehen.
2. Datensicherung aus Abschnitt 1 abschliessen.
3. Setup mit Administratorrechten ueber der bestehenden Installation
   ausfuehren und gewaehlte Komponenten/Optionen kontrollieren.
4. Nach Abschluss die Dienste erneut mit stopservices.cmd stoppen, bevor
   Dateien aus der Sicherung zurueckkopiert werden.
5. Nur wenn fuer dieselbe kompatible MariaDB-Version erforderlich und
   getestet: gesichertes mysql\data bei gestopptem Dienst zurueckkopieren.
   Bei Datenbankversionswechseln stattdessen den vorgesehenen
   Dump-/Restore- und Upgrade-Weg verwenden.
6. Die drei gesicherten OpenAudit-Konfigurationsdateien zurueckspielen,
   dabei neue Standardeinstellungen mit den alten Werten vergleichen.
7. Dienste starten und Abschnitt 5 abarbeiten.

4. OPTIONALE KOMPONENTENUPDATES (GETRENNT PRUEFEN)
------------------------------------------------
Diese Updates sind keine Voraussetzung fuer ein reines OpenAudit-Codeupdate.
Vor jedem Austausch Version, Architektur, Abhaengigkeiten und Kompatibilitaet
pruefen; Konfiguration und Installationsverzeichnis sichern und Dienste
stoppen. Nicht mehrere Komponenten gleichzeitig ohne Zwischenpruefung
aktualisieren.

PHP 8.4.x:
  Passendes Windows-x64-ZIP in der Thread-Safe-Variante beschaffen.
  Dateien im Ordner C:\Program Files (x86)\xampplite\php\ ersetzen.
  php.ini, Erweiterungen und Apache-Anbindung vorher sichern und danach
  auf Kompatibilitaet pruefen.

Apache 2.4.x:
  Passendes x64-Paket von Apache Lounge beschaffen und entpacken.
  Die bisherige Apache-Konfiguration erhalten; den neuen conf-Ordner nicht
  ungeprueft uebernehmen. Binaerdateien in
  C:\Program Files (x86)\xampplite\apache\ aktualisieren.
  Module, Dienstkonfiguration, Zertifikate und Ports pruefen.

MariaDB 10.11.x:
  Passendes x64-ZIP-Paket beziehen. Vor dem Austausch unbedingt logischen
  Datenbankexport und gesicherte Konfiguration bereithalten. Programmdateien
  unter C:\Program Files (x86)\xampplite\mysql\ nur gemaess
  MariaDB-Upgradevorgaben austauschen; mysql\data nicht ueberschreiben.
  Upgrade-Schritte und Datenbankkompatibilitaet anschliessend pruefen.

5. ABSCHLUSSPRUEFUNG UND ROLLBACK
--------------------------------
- Apache- und MariaDB-Dienste laufen fehlerfrei.
- Weboberflaeche ist ueber die konfigurierte HTTPS-Adresse erreichbar
  (laut README standardmaessig https://<Servername>:4443/openaudit).
- Datenbankverbindung, Anmeldung, Inventarlisten und Suchfunktionen testen.
- PC-/WMI-Scan und gegebenenfalls Nmap-Scan sowie Aufgabenplanung pruefen.
- scripts\audit.config und pc_list_file.txt auf korrekte Werte pruefen.
- Fuer clientseitige Scans den audit_host in
  oa-clientside-scan\audit.config kontrollieren. Der bisher dokumentierte
  Beispielwert lautet audit_host="http://pat14sv:888"; er ist
  installationsspezifisch und nicht pauschal zu uebernehmen.
- Protokolle auf Fehler pruefen und Ergebnis dokumentieren.

Bei Problemen: Dienste stoppen, die zuvor gesicherten Programmdateien und
Konfigurationen passend zur Ausgangsversion wiederherstellen und die
Datenbank ueber einen geprueften, versionskompatiblen Wiederherstellungsweg
zuruecksetzen. Danach Dienste starten und erneut testen.

12. QUELLEN UND DOKUMENTATIONSSTAND
----------------------------------
Diese README vereinigt und überarbeitet Informationen aus:
  - der ursprünglichen readme.txt von OpenAudit Classic
  - dem Installationsskript openaudit-install.iss
  - https://tech-nachrichten.de/openaudit-classic-hard-software-inventar/
  - der bereitgestellten Update-Notiz oa-updateoa.txt

Die Versionsnummern und Setup-Optionen in Kapitel 3 bis 5 stammen aus dem
bereitgestellten Installationsskript. Bei Abweichungen zur installierten
Software sind die tatsächlich vorhandenen Dateien und Einstellungen
maßgeblich.
