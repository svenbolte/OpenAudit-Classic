; innosetuo 7.1.0 - Den Ordner oa-clientside-scan nach c:\temp kopieren und ISS Datei neu compilieren. Setup-exe in den oa-clientside ordner

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
Source: "C:\temp\oa-clientside-scan\*"; DestDir: "{app}"; Excludes: "openaudit-clientscan-setup.exe"; Flags: ignoreversion recursesubdirs createallsubdirs

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


; Aufruf: openaudit-clientscan-setup.exe /SERVER=meinserver
; Ohne /SERVER wird der Servername im Setup abgefragt.
; Der Port bleibt aus der vorhandenen audit.config erhalten (Standard :888).
[Code]
var
  ServerPage: TInputQueryWizardPage;
  ServerName: String;

function GetServerParameter: String;
var
  I: Integer;
  P: String;
begin
  Result := '';
  for I := 1 to ParamCount do
  begin
    P := ParamStr(I);
    if CompareText(Copy(P, 1, 8), '/SERVER=') = 0 then
    begin
      Result := Trim(Copy(P, 9, MaxInt));
      Exit;
    end;
  end;
end;

function ValidServer(const Value: String): Boolean;
var
  I: Integer;
begin
  Result := (Value <> '') and (Pos('://', Value) = 0) and
    (Pos('/', Value) = 0) and (Pos('\\', Value) = 0) and
    (Pos(':', Value) = 0) and (Pos('"', Value) = 0) and
    (Pos(' ', Value) = 0);
  for I := 1 to Length(Value) do
    if Ord(Value[I]) < 33 then Result := False;
end;

procedure InitializeWizard;
begin
  ServerName := GetServerParameter;
  if ServerName = '' then
  begin
    ServerPage := CreateInputQueryPage(wpSelectDir,
      'OpenAudit-Server', 'Server fuer den Clientside-Scan',
      'Bitte den Hostnamen oder die IP-Adresse des OpenAudit-Servers eingeben.');
    ServerPage.Add('Servername / IP:', False);
    ServerPage.Values[0] := 'pat14sv';
  end;
end;

function NextButtonClick(CurPageID: Integer): Boolean;
begin
  Result := True;
  if (ServerName = '') and (CurPageID = ServerPage.ID) then
  begin
    ServerName := Trim(ServerPage.Values[0]);
    if not ValidServer(ServerName) then
    begin
      MsgBox('Bitte einen gueltigen Hostnamen oder eine IP-Adresse ohne Protokoll und Port eingeben.', mbError, MB_OK);
      ServerName := '';
      Result := False;
    end;
  end;
end;

function InitializeSetup: Boolean;
var
  S: String;
begin
  Result := True;
  S := GetServerParameter;
  if (S <> '') and (not ValidServer(S)) then
  begin
    MsgBox('Ungueltiger /SERVER-Parameter: ' + S, mbError, MB_OK);
    Result := False;
  end;
end;

procedure PatchAuditConfig(const FileName: String);
var
  Lines: TArrayOfString;
  I: Integer;
  L, OldValue, ProtocolPort: String;
begin
  if not LoadStringsFromFile(FileName, Lines) then
  begin
    RaiseException('audit.config konnte nicht gelesen werden: ' + FileName);
  end;
  for I := 0 to GetArrayLength(Lines) - 1 do
  begin
    L := Trim(Lines[I]);
    if Pos('audit_host=', L) = 1 then
    begin
      OldValue := L;
      // Preserve the scheme and port of the packaged configuration.
      ProtocolPort := 'http://';
      if Pos('https://', Lowercase(OldValue)) > 0 then ProtocolPort := 'https://';
      if Pos(':888', OldValue) > 0 then ProtocolPort := ProtocolPort + ServerName + ':888'
      else ProtocolPort := ProtocolPort + ServerName;
      Lines[I] := 'audit_host="' + ProtocolPort + '"';
      if not SaveStringsToFile(FileName, Lines, False) then
        RaiseException('audit.config konnte nicht geschrieben werden: ' + FileName);
      Exit;
    end;
  end;
  RaiseException('audit_host fehlt in ' + FileName);
end;

procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then
  begin
    PatchAuditConfig(ExpandConstant('{app}\audit.config'));
    if FileExists(ExpandConstant('{app}\audit.config.push')) then
      PatchAuditConfig(ExpandConstant('{app}\audit.config.push'));
  end;
end;
