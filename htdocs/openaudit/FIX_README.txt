OpenAudit Patch 2026-10-27
1. system.php: missing view parameter handled safely.
2. audit.ps1 (all 3 copies): WMI printer detection with CIM fallback and safe failure handling.
3. admin_pc_add_2.php: printer rows matched precisely, values SQL escaped.

Deploy: back up existing installation and database, then copy updated files.
Test on Windows with an installed printer and inspect audit output for printer^^^ lines.
Note: live Windows/DB integration tests could not be run here.
