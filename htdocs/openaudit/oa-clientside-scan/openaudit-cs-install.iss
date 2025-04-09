; den Ordner oa-clientside-scan nach c:\temp kopieren und ISS Datei neu compilieren. Setup-exe in den oa-clientside ordner

#define MyAppName "Open-Audit Classic Clientside Scan"
#define MyDateString GetDateTimeString('yyyy/mm/dd', '.', '');
#define MyAppPublisher "OpenAudit Classic GPL3 Projekt"
#define MyAppURL "https://github.com/svenbolte/Open-Audit-Classic"
#define Inhalte "Open-Audit Classic Clientside Scan"

[Setup]
PrivilegesRequired=admin
AppID={{7E7C2AB4-BF69-462E-8AC5-A599A4A74132}
AppName={#MyAppName}
AppVersion={#MyDateString}
AppVerName={#MyAppName}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}
DefaultDirName={commonpf}\oa-clientside-scan
DisableDirPage=yes
LicenseFile=C:\temp\oa-clientside-scan\openaudit-clientside-howto.txt
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
OutputDir=c:\temp\
OutputBaseFilename=openaudit-clientscan-setup
SetupIconFile=C:\temp\oa-clientside-scan\openaudit_logo.ico
Compression=lzma2/Ultra
SolidCompression=true
WizardImageFile=C:\temp\oa-clientside-scan\openaudit_logo.bmp
WizardSmallImageFile=C:\temp\oa-clientside-scan\openaudit_logo.bmp
AppCopyright={#MyAppPublisher}
ShowLanguageDialog=no
InternalCompressLevel=Ultra
AppComments={#MyDateString}-{#Inhalte}
VersionInfoDescription={#MyDateString}-{#Inhalte}
UninstallDisplayIcon={app}\openaudit_logo.ico

[Languages]
Name: "german"; MessagesFile: "compiler:Languages\German.isl"

[Tasks]
Name: "desktopicon"; Description: "Desktop-Verknüpfungen erstellen"; GroupDescription: "{cm:AdditionalIcons}";

[Dirs]
Name: {app}; Permissions: users-full

[Files]
Source: "C:\temp\oa-clientside-scan\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\OpenAudit Clientsside Task (als Admin)"; Filename: "{sys}\schtasks.exe"; Parameters: "/RUN /TN Openaudit-Clientside-Scan"; WorkingDir: "{app}"; Comment: "als Admin einen Clientside Scan ausführen"; IconFilename: "{app}\openaudit_logo.ico";
Name: "{group}\OpenAudit Clientsside Scan"; Filename: "{app}\oaclientside.cmd"; WorkingDir: "{app}"; Comment: "als angemeldeter User einen Clientside Scan ausführen"; IconFilename: "{app}\openaudit_logo.ico";
Name: "{group}\OpenAudit cl Explorer (Ordner)"; Filename: "%windir%\explorer.exe"; Parameters: "/e,""{app}"" "; WorkingDir: "{app}"; IconFilename: "{app}\openaudit_logo.ico"; Comment: "Ordner mit scripts öffnen"
Name: "{commondesktop}\OpenAudit Clientsside Scan"; Filename: "{app}\oaclientside.cmd"; WorkingDir: "{app}"; Comment: "als administrator ausführen - einen Clientside Scan ausführen"; Tasks: desktopicon;
Name: "{commondesktop}\Aufgabenplanung"; Filename: "%windir%\system32\taskschd.msc"; Parameters: "/s"; Tasks: desktopicon; Comment: "OpenAudit Aufgabe auf Domain-admin umstellen"

[Run]
Filename: "{sys}\schtasks.exe"; Parameters: "/create /RU SYSTEM /XML ""{app}\Openaudit-Clientside-Scan.xml"" /TN Openaudit-Clientside-Scan"; Flags: runascurrentuser; Description: "OA Clientside Aufgabe importieren";

[UninstallRun]
Filename: "{sys}\schtasks.exe"; Parameters: "/delete /TN Openaudit-Clientside-Scan"; RunOnceId: "DELCLITASK"
