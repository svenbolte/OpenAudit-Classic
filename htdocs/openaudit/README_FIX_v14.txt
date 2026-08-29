FIX v14 - Druckerinventar

Sollverhalten:
1. audit.ps1 liest alle Win32_Printer des Zielgeraets (localhost oder remote).
2. Keine Registry-Fallback-Drucker und keine Druckerfilter im PowerShell-Audit.
3. admin_pc_add_2.php speichert jeden gelieferten Drucker mit der UUID des gescannten Geraets.
4. Beim erfolgreichen Printer-Snapshot werden die bisherigen printer-Zeilen dieses Geraets geloescht und durch den aktuellen Bestand ersetzt. Keine Printer-History.
5. list_viewdef_all_printers.php zeigt alle printer-Zeilen.
6. list_viewdef_all_printers_ipv4.php zeigt nur freigegebene Drucker: ShareName nicht leer; Shared=True/1/Yes/Y wird zusaetzlich akzeptiert.
