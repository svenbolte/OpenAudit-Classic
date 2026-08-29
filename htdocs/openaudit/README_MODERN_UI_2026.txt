Open-AudIT UI modernization / PHP 8.4 cleanup
Date: 2026-08-29

Changed centrally (no per-page CSS overlay patches):
- default.css: rebuilt global UI styling for navigation, header, tables, forms, buttons, cards and responsive layout.
- admin_config.css: rebuilt Settings layout and Color Theme controls.
- include.php: validated/normalized accent color, generated RGB/alpha theme variables, dynamic browser theme-color, fixed malformed legacy table markup.
- admin_config.php: Color Theme now supports a real #RRGGBB color picker/text value with validation and presets.
- javascript/admin_config.js: synchronizes picker, hex input and preset swatches.
- include_config_defaults.php: normalized default accent color to #004477.

PHP 8.4 compatibility fixes found by full root lint:
- ldap_audit_script.php: removed obsolete call-time pass-by-reference syntax and updated ldap_search argument call.
- list_viewdef_keys_for_software.php: removed invalid isset()/error-suppression construct.
- system_graphs_pie.php: replaced invalid placeholder arguments in imagefilledarc() with complete parameters.

Existing Android app CSV import in this ZIP already uses explicit fgetcsv escape parameters.
Store links remain supported.

Verification:
- Every PHP file in the Open-AudIT tree was checked with PHP 8.4 syntax lint.

V8 cleanup:
- Header banner is now a fixed 76px light surface with no background image or gradient.
- Accent picker and hex input save robustly; the native picker posts independently and the page reloads after save so the new theme is visible immediately.
- Homepage audited-systems bar gradient now derives from the saved accent color instead of the legacy teal hard-code.
- Removed the unused back_chipsatz.jpg banner image.
