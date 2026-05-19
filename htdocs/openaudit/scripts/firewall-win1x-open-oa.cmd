echo **** Openaudit Firewall Enabler
netsh advfirewall firewall set rule group="Windows-Verwaltungsinstrumentation (WMI)" new enable=yes
netsh advfirewall firewall set rule group="remoteverwaltung" new enable=yes
netsh advfirewall firewall set rule name="Datei- und Druckerfreigabe (Echoanforderung - ICMPv4 eingehend)" new enable=yes