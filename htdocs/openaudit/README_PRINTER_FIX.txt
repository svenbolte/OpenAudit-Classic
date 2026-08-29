Printer inventory final fix

Replace these files on the Open-AudIT server/client tree:
- admin_pc_add_2.php
- list_viewdef_all_printers.php
- list_viewdef_all_printers_ipv4.php
- oa-clientside-scan/audit.ps1
- oa-clientside-scan/offline-scan/audit.ps1
- scripts/audit.ps1

Behavior:
- localhost and remote: query all Win32_Printer instances.
- No manufacturer, virtual-printer or port filtering.
- On a successful Win32_Printer query the DB printer set for that system UUID is replaced completely.
- All printers view: every current printer.
- network/shared view: non-empty share name, with Shared flag as fallback.
