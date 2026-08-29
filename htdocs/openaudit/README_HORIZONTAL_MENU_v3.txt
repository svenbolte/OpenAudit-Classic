OpenAudit Modern UI v3 - horizontale Navigation
================================================

Aenderungen:
- Linke Legacy-Navigation aus include.php entfernt.
- Neue horizontale Hauptnavigation unter dem Header.
- Keine "..."-Kennzeichnung mehr; Untermenues verwenden eindeutige Chevron-Symbole.
- Untermenues oeffnen nach unten statt seitlich.
- Dritte Menueebene bleibt im gleichen Dropdown eingerueckt und wird nicht als weiteres Flyout geoeffnet.
- Dropdowns haben eine viewport-begrenzte Hoehe mit internem Scrollen, damit Menuepunkte erreichbar bleiben.
- Die Navigationsleiste selbst verwendet kein Overflow-Clipping; bei kleinerer Breite bricht sie in weitere Zeilen um.
- Rechte Menuegruppen richten Dropdowns am rechten Rand aus, um ein Abschneiden ausserhalb des Viewports zu vermeiden.
- Bestehende Akzentfarbe aus den Settings wird fuer Hover/aktive Menuepunkte weiterverwendet.
- Die bereits in v2 enthaltenen Listen-Toolbar- und Tabellenlinien-Aenderungen bleiben enthalten.

Geaenderte zentrale Dateien fuer die Navigation:
- include.php
- default.css

Pruefung:
- PHP-Lint fuer alle PHP-Dateien im Hauptverzeichnis: keine Parse-/Syntaxfehler.
