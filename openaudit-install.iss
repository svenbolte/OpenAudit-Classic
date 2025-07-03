; Innosetup Compiler 6.4.3

#define MyAppName "Open-Audit Classic"
#define MyDateString GetDateTimeString('yyyy/mm/dd', '.', '');
#define MyAppPublisher "OpenAudit Classic GPL3 Projekt"
#define MyAppURL "https://github.com/svenbolte/Open-Audit-Classic"
#define Inhalte "Apache 2.4.63x64-VS17/libcurl8.12, MySQLMariaDB 10.11.13x64(LTS), PHP 8.4.10x64-thsafe, phpMyAdmin 5.2.2x64, NMap 7.97, NPCap 1.82 (für nmap), Wordpress 6.8.1, VC17Runtimes 07/25, MariaDB ODBC 3.2.6"

[Setup]
PrivilegesRequired=admin
AppID={{E3C99A13-491B-4DE8-A06B-E81AA391561B}
AppName={#MyAppName}
AppVersion={#MyDateString}
AppVerName={#MyAppName}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}
LicenseFile=C:\temp\xampplite\readme.txt
DefaultDirName={commonpf}\xampplite
DisableDirPage=yes
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
OutputDir=c:\temp\
OutputBaseFilename=openaudit-cl-setup
SetupIconFile=C:\temp\xampplite\openaudit_logo.ico
Compression=lzma2/Ultra
SolidCompression=true
WizardImageFile=C:\temp\xampplite\openaudit_logo.bmp
WizardSmallImageFile=C:\temp\xampplite\openaudit_logo.bmp
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
Name: "Aufgabepcscan"; Description: "P: Importieren der PC-Scan Aufgabe"; Flags: checkedonce;
Name: "AufgabeNMAPScan"; Description: "N: Aufgabe für NMAP-Scan importieren"; Flags: unchecked;

[Dirs]
Name: {app}; Permissions: users-full

[Files]
Source: "C:\temp\xampplite\*"; DestDir: "{app}"; Components: mitwordpress; Flags: ignoreversion recursesubdirs createallsubdirs
Source: "C:\temp\xampplite\*"; DestDir: "{app}"; Components: nuropenaudit; Excludes: "wordpress"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\OpenAudit cl Oberfläche SSL"; Filename: "https://{code:GetComputerName}:4443/openaudit"; Comment: "SSL Netzwerkverknüpfung zum Open-Audit-Server"
Name: "{group}\OpenAudit cl Oberfläche lokal"; Filename: "http://localhost:888/openaudit"; Comment: "HTTP Netzwerkverknüpfung zum Open-Audit-Server localhost"
Name: "{group}\OpenAudit cl Konsole"; Filename: "cmd.exe"; WorkingDir: "{app}\htdocs\openaudit\scripts"; Comment: "als angemeldeter User"
Name: "{group}\OpenAudit cl Konsole (Admin)"; Filename: "%windir%\system32\cmd.exe"; Parameters: "/k pushd ""{app}\htdocs\openaudit\scripts\"""; WorkingDir: "{app}\htdocs\openaudit\scripts"; IconFilename: "{app}\openaudit_logo.ico"; Comment: "mit elevated rights"
Name: "{group}\OpenAudit cl Explorer (Ordner)"; Filename: "%windir%\explorer.exe"; Parameters: "/e,""C:\Program Files (x86)\xampplite\htdocs\openaudit\scripts"" "; WorkingDir: "{app}\htdocs\openaudit\scripts"; IconFilename: "{app}\openaudit_logo.ico"; Comment: "Ordner mit scripts öffnen"
Name: "{group}\PC-IP-Listfile.txt manuell ändern"; Filename: "{app}\htdocs\openaudit\scripts\pc_list_file.txt";  Comment: "nur im Notfall, lässt sich besser über Oberfläche erzeugen"
Name: "{group}\SSL-Zertifikat erneuern"; Filename: "{app}\apache/makecert2.cmd"; Comment: "openssl Zertifikat für 2 Jahre"
Name: "{commondesktop}\OpenAudit cl Oberfläche"; Filename: "https://{code:GetComputerName}:4443/openaudit"; Tasks: desktopicon; Comment: "Netzwerkverknüpfung zum Open-Audit-Server"
Name: "{commondesktop}\OpenAudit cl Konsole"; Filename: "cmd.exe"; WorkingDir: "{app}\htdocs\openaudit\scripts"; Comment: "als angemeldeter User"; Tasks: desktopicon
Name: "{commondesktop}\PC-List-File erzeugen"; Filename: "https://{code:GetComputerName}:4443/openaudit/export-ipliste-4-openaudit.php"; Tasks: desktopicon; Comment: "Netzwerke eingeben und Liste erzeugen SSL"
Name: "{commondesktop}\Aufgabenplanung"; Filename: "%windir%\system32\taskschd.msc"; Parameters: "/s"; Tasks: desktopicon; Comment: "OpenAudit Aufgaben auf Domain-admin umstellen: PC-Scan und optional NMAP Scan bearbeiten"

[Run]
Filename: "{sys}\schtasks.exe"; Parameters: "/create /RU SYSTEM /XML ""{app}\htdocs\openaudit\scripts\Open-Audit PC Inventar taeglich.xml"" /TN Openaudit-PCScan"; Flags: runascurrentuser; Description: "PC Scan Aufgabe importieren"; Tasks: Aufgabepcscan; Check: NoRunSwitch
Filename: "{sys}\schtasks.exe"; Parameters: "/create /RU SYSTEM /XML ""{app}\htdocs\openaudit\scripts\Open-Audit NMAP Inventar taeglich.xml"" /TN Openaudit-NMAPScan"; Flags: runascurrentuser; Description: "NMAP Scan Aufgabe importieren"; Tasks: AufgabeNMAPScan; Check: NoRunSwitch
Filename: "{app}\vcruntimes\vc_redist.x64.exe"; Parameters: "/install /quiet /norestart"; Flags: waituntilterminated shellexec; StatusMsg: "Installing VC2019/X64 Redist for Apache"; Check: VC2017RedistNeedsInstall
Filename: "{app}\apache\makecert2.cmd"; Flags: shellexec postinstall runascurrentuser; Description: "Apache SSL Zertifikat auf Openaudit Server ausstellen"; Check: NoRunSwitch
Filename: "{app}\apache\oa-importcert.cmd"; Flags: shellexec postinstall runascurrentuser; Description: "Zertifikat in Browser importieren"; Check: NoRunSwitch
Filename: "{app}\apache\apache_installservice-win10.cmd"; Flags: shellexec postinstall runascurrentuser; Description: "Apache ab Win10 als Dienst und starten"; Check: NoRunSwitch
Filename: "{app}\mysql\mysql_installservice-win10.cmd"; Flags: shellexec postinstall runascurrentuser; Description: "MySQL ab Win10 als Dienst und starten"; Check: NoRunSwitch
Filename: "{app}\nmap\npcap-1.82.exe"; Flags: shellexec postinstall runascurrentuser; Description: "für NMAP benötigtes NPCap installieren"; Check: NoRunSwitch
Filename: "{app}\vcruntimes\vc_redist.x86.exe"; Parameters: "/q /norestart"; Flags: waituntilterminated shellexec postinstall; Description: "VC Runtime 2019 x86 für NMAP installieren"; StatusMsg: "Installing VC2019/x86 Redist for NMAP"; Check: VC2013RedistNeedsInstall

[Types]
Name: typical; Description: "Typical"; Flags: iscustom;
Name: custom; Description: "Custom";

[Components]
Name: nuropenaudit; Description: Nur Openaudit installieren; ExtraDiskSpaceRequired: 180000; Types: typical; Flags:exclusive;
Name: mitwordpress; Description: Openaudit und Wordpress für Intranet installieren; ExtraDiskSpaceRequired: 200000; Types: custom; Flags:exclusive;

[UninstallRun]
Filename: "{app}\apache\apache_uninstallservice-win10.cmd"; Flags: shellexec; RunOnceId: "DELAPACHE"
Filename: "{app}\mysql\mysql_uninstallservice-win10.cmd"; Flags: shellexec; RunOnceId: "DELMYSQL"

[Code]
function InitializeSetup(): boolean;
var
  ResultCode: integer;
begin
  // Launch Notepad and wait for it to terminate
  if Exec(ExpandConstant('{win}\system32\net.exe'), 'stop apache2.4', '', SW_SHOW,
     ewWaitUntilTerminated, ResultCode) then
  begin
  end;
  Result := True;
  if Exec(ExpandConstant('{win}\system32\net.exe'), 'stop mysql', '', SW_SHOW,
     ewWaitUntilTerminated, ResultCode) then
  begin
  end;
  Result := True;
end;

// Rechnernamen, auf dem installiert wird, herausfinden
function GetComputerName(Param: string): string;
begin
  Result := GetComputerNameString;
end; 

function VC2017RedistNeedsInstall: Boolean;
var 
  Version: String;
begin
  if (RegQueryStringValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\WOW6432Node\Microsoft\VisualStudio\14.0\VC\Runtimes\X64', 'Version', Version)) then
  begin
    // Is the installed version at least 14.29 ? 
    Log('VC Redist Version check : found ' + Version);
    Result := (CompareStr(Version, 'v14.42.34433.0')<0);
  end
  else 
  begin
    // Not even an old version installed
    Result := True;
  end;
end;

function VC2013RedistNeedsInstall: Boolean;
var 
  Version: String;
begin
  if (RegQueryStringValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\WOW6432Node\Microsoft\VisualStudio\14.0\VC\Runtimes\X86', 'Version', Version)) then
  begin
    // Is the installed version at least 14.29 ? 
    Log('VC Redist Version check : found ' + Version);
    Result := (CompareStr(Version, 'v14.42.34430.0')<0);
  end
  else 
  begin
    // Not even an old version installed
    Result := True;
  end;
end;

// Wenn parameter /NORUN gesetzt, werden die run befehle nicht ausgeführt bei postinstall
function NoRunSwitch: boolean;
var
  i: integer;
begin
  // Return TRUE to show the checkbox on the final page, return FALSE to hide it.
  Result := True; // In case there are no parameters
  for i := 1 to ParamCount do
  begin
    // Tweak the switch parsing to suit your needs here
    Result := not (UpperCase(ParamStr(i)) = '/NORUN');
    if not Result then break;
  end;
end;