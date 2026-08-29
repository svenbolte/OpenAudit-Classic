<#
Open-AudIT native PowerShell conversion of audit.vbs.txt
- No cscript/vbscript wrapper.
- Reads audit.config (VBScript-style key/value file) from the same directory or /config_path:<path>.
- Preserves Open-AudIT form record output format: section^^^field^^^...
#>
[CmdletBinding()]
param([Parameter(ValueFromRemainingArguments=$true)][string[]]$ArgsRemaining)

$ErrorActionPreference = 'SilentlyContinue'

$script:NamedArgs = @{}
$script:UnnamedArgs = New-Object System.Collections.Generic.List[string]
foreach($a in @($ArgsRemaining)){
    if($a -match '^[/-]([^:=]+)[:=](.*)$'){ $script:NamedArgs[$matches[1].ToLowerInvariant()] = $matches[2] }
    elseif($a -match '^[/-](\w+)$'){ $script:NamedArgs[$matches[1].ToLowerInvariant()] = 'true' }
    else { $script:UnnamedArgs.Add([string]$a) }
}

function Echo([object]$Message){ Write-Output ([string]$Message) }
function Clean([object]$Value){
    if($null -eq $Value){ return '' }
    $s = [string]$Value
    $s = $s -replace '\r|\n|\t',' '
    $s = $s -replace '\^\^\^',' '
    $s = $s -replace '[\x00-\x08\x0B\x0C\x0E-\x1F]',''
    return $s.Trim()
}
function First([object]$Value){ if($null -eq $Value){''} elseif($Value -is [array]){ if($Value.Count){$Value[0]}else{''} } else {$Value} }
function BoolText([object]$Value){ if($Value -is [bool]){ if($Value){'Wahr'}else{'Falsch'} } else { $v=Clean $Value; if($v -eq 'True'){return 'Wahr'}; if($v -eq 'False'){return 'Falsch'}; return $v } }
function PadIp([string]$ip){
    if([string]::IsNullOrWhiteSpace($ip)){ return 'none' }
    if($ip -notmatch '^\d+\.\d+\.\d+\.\d+$'){ return $ip }
    $p=$ip.Split('.')
    if($p[0] -eq '169' -and $p[1] -eq '254'){ return $ip }
    return ('{0:D3}.{1:D3}.{2:D3}.{3:D3}' -f [int]$p[0],[int]$p[1],[int]$p[2],[int]$p[3])
}
function SafeDivInt([double]$a,[double]$b){ if($b -eq 0){0}else{[math]::Floor($a/$b)} }
function WmiDate([object]$d){
    if($null -eq $d -or [string]::IsNullOrWhiteSpace([string]$d)){ return '' }
    try { return ([System.Management.ManagementDateTimeConverter]::ToDateTime([string]$d)).ToString('yyyy/MM/dd HH:mm:ss') } catch { return (Clean $d) }
}
function WmiDateShort([object]$d){
    if($null -eq $d -or [string]::IsNullOrWhiteSpace([string]$d)){ return '' }
    try { return ([System.Management.ManagementDateTimeConverter]::ToDateTime([string]$d)).ToString('yyyy/MM/dd') } catch { $s=[string]$d; if($s.Length -ge 8){return "$($s.Substring(0,4))/$($s.Substring(4,2))/$($s.Substring(6,2))"}; return (Clean $d) }
}
function Get-ConfigValueExpression([string]$expr){
    $expr=$expr.Trim()
    if($expr -match '^"(.*)"$'){ return $matches[1] }
    if($expr -match "^'(.*)'$"){ return $matches[1] }
    if($expr -match '^(?i:true|false)$'){ return $expr.ToLowerInvariant() }
    if($expr -match '^-?\d+$'){ return [int]$expr }
    $parts = [regex]::Split($expr,'\s*\+\s*')
    if($parts.Count -gt 1){
        $out=''
        foreach($p in $parts){ $out += [string](Get-ConfigValueExpression $p) }
        return $out
    }
    $name=$expr.Trim()
    $v=Get-Variable -Name $name -Scope Script -ErrorAction SilentlyContinue
    if($v){ return $v.Value }
    return $expr.Trim('"')
}
function Read-AuditConfig([string]$Path){
    if(-not (Test-Path -LiteralPath $Path)){ return }
    foreach($line in Get-Content -LiteralPath $Path -ErrorAction SilentlyContinue){
        $code = ($line -split "'",2)[0].Trim()
        if($code -match '^([A-Za-z_]\w*)\s*=\s*(.*)$'){
            Set-Variable -Name $matches[1] -Scope Script -Value (Get-ConfigValueExpression $matches[2])
        }
    }
}
# Fast pre-check for remote Windows/WMI targets. This avoids waiting for the
# much longer RPC/WMI timeout when a host is offline, not a Windows PC, or WMI
# is blocked. Local audits bypass this check.
function Test-WmiEndpoint([string]$Computer,[int]$TimeoutMs=1500){
    if(Is-LocalComputer $Computer){ return $true }
    $client = New-Object System.Net.Sockets.TcpClient
    try{
        $ar = $client.BeginConnect($Computer,135,$null,$null)
        if(-not $ar.AsyncWaitHandle.WaitOne($TimeoutMs,$false)){ return $false }
        $client.EndConnect($ar)
        return $client.Connected
    } catch {
        return $false
    } finally {
        try { $client.Close() } catch {}
    }
}
function Get-Wmi([string]$Computer,[string]$Namespace,[string]$Query,[string]$User,[string]$Password){
    try{
        if($User -and $Password){
            $sec=ConvertTo-SecureString $Password -AsPlainText -Force
            $cred=New-Object System.Management.Automation.PSCredential($User,$sec)
            return Get-WmiObject -ComputerName $Computer -Namespace $Namespace -Query $Query -Credential $cred -ErrorAction Stop
        }
        return Get-WmiObject -ComputerName $Computer -Namespace $Namespace -Query $Query -ErrorAction Stop
    } catch { return @() }
}
function Get-HiveUInt([object]$Hive){
    try{
        $n = [int64]$Hive
        if($n -lt 0){ $n = $n + 4294967296 }
        return [uint32]$n
    }catch{
        return [uint32]2147483650
    }
}
function Get-RegValue([string]$Computer,[object]$Hive,[string]$Key,[string]$Name){
    try{
        $reg=[WMIClass]"\\$Computer\root\default:StdRegProv"
        $out=$reg.GetStringValue((Get-HiveUInt $Hive),$Key,$Name)
        if($out.ReturnValue -eq 0){ return $out.sValue }
    } catch {}
    return ''
}
function Enum-RegKey([string]$Computer,[object]$Hive,[string]$Key){
    try{
        $reg=[WMIClass]"\\$Computer\root\default:StdRegProv"
        $out=$reg.EnumKey((Get-HiveUInt $Hive),$Key)
        if($out.ReturnValue -eq 0){ return @($out.sNames) }
    } catch {}
    return @()
}
function Enum-RegValues([string]$Computer,[object]$Hive,[string]$Key){
    try{
        $reg=[WMIClass]"\\$Computer\root\default:StdRegProv"
        $out=$reg.EnumValues((Get-HiveUInt $Hive),$Key)
        if($out.ReturnValue -eq 0){ return @($out.sNames) }
    } catch {}
    return @()
}

function Is-LocalComputer([string]$Computer){ if([string]::IsNullOrWhiteSpace($Computer)){return $true}; $c=$Computer.Trim('\\'); return ($c -eq '.' -or $c -ieq 'localhost' -or $c -ieq $env:COMPUTERNAME) }
function Hive-FromUInt([object]$Hive){
    switch([uint32](Get-HiveUInt $Hive)){
        ([uint32]2147483648) { [Microsoft.Win32.RegistryHive]::ClassesRoot; break }
        ([uint32]2147483649) { [Microsoft.Win32.RegistryHive]::CurrentUser; break }
        ([uint32]2147483650) { [Microsoft.Win32.RegistryHive]::LocalMachine; break }
        ([uint32]2147483651) { [Microsoft.Win32.RegistryHive]::Users; break }
        default { [Microsoft.Win32.RegistryHive]::LocalMachine }
    }
}
function Open-RegistryBase([object]$Hive,[string]$View){ $rv=if($View -eq '32'){[Microsoft.Win32.RegistryView]::Registry32}else{[Microsoft.Win32.RegistryView]::Registry64}; [Microsoft.Win32.RegistryKey]::OpenBaseKey((Hive-FromUInt $Hive),$rv) }
function Get-RegValueEx([string]$Computer,[object]$Hive,[string]$Key,[string]$Name,[string]$View='64'){
 if(Is-LocalComputer $Computer){ try{ $b=Open-RegistryBase $Hive $View; $k=$b.OpenSubKey($Key); if($null -eq $k){return ''}; $v=$k.GetValue($Name,$null,[Microsoft.Win32.RegistryValueOptions]::DoNotExpandEnvironmentNames); $k.Close(); $b.Close(); if($null -eq $v){return ''}; if($v -is [array]){return (($v|%{[string]$_}) -join ' ')}; return [string]$v }catch{return ''} }
 return Get-RegValue $Computer $Hive $Key $Name
}
function Get-RegDwordEx([string]$Computer,[object]$Hive,[string]$Key,[string]$Name,[string]$View='64'){
 if(Is-LocalComputer $Computer){ return Get-RegValueEx $Computer $Hive $Key $Name $View }
 try{ $reg=[WMIClass]"\\$Computer\root\default:StdRegProv"; $o=$reg.GetDWORDValue((Get-HiveUInt $Hive),$Key,$Name); if($o.ReturnValue -eq 0){return $o.uValue} }catch{}; return ''
}
function Enum-RegKeyEx([string]$Computer,[object]$Hive,[string]$Key,[string]$View='64'){
 if(Is-LocalComputer $Computer){ try{ $b=Open-RegistryBase $Hive $View; $k=$b.OpenSubKey($Key); if($null -eq $k){return @()}; $n=@($k.GetSubKeyNames()); $k.Close(); $b.Close(); return $n }catch{return @()} }
 return Enum-RegKey $Computer $Hive $Key
}
function Enum-RegValuesEx([string]$Computer,[object]$Hive,[string]$Key,[string]$View='64'){
 if(Is-LocalComputer $Computer){ try{ $b=Open-RegistryBase $Hive $View; $k=$b.OpenSubKey($Key); if($null -eq $k){return @()}; $n=@($k.GetValueNames()); $k.Close(); $b.Close(); return $n }catch{return @()} }
 return Enum-RegValues $Computer $Hive $Key
}
function Get-LocalGroupMembersText([string]$Computer,[string]$GroupName,[string]$User,[string]$Password){
 try{ if($User -and $Password){ $ads=[ADSI]'WinNT:'; $grp=$ads.OpenDSObject("WinNT://$Computer/$GroupName,group",$User,$Password,3) } else { $grp=[ADSI]"WinNT://$Computer/$GroupName,group" }; $m=@(); foreach($x in @($grp.psbase.Invoke('Members'))){ $m += [string]$x.GetType().InvokeMember('Name','GetProperty',$null,$x,$null) }; if($m.Count){return ($m -join ', ')} }catch{}; return 'No Members in this group.'
}
function Get-RegistryBaseName([object]$Hive){
    switch([uint32](Get-HiveUInt $Hive)){
        ([uint32]2147483648) { 'HKEY_CLASSES_ROOT'; break }
        ([uint32]2147483649) { 'HKEY_CURRENT_USER'; break }
        ([uint32]2147483650) { 'HKEY_LOCAL_MACHINE'; break }
        ([uint32]2147483651) { 'HKEY_USERS'; break }
        default { 'HKEY_LOCAL_MACHINE' }
    }
}
function Open-RegistrySubKeyLocal([object]$Hive,[string]$Key,[string]$View='64'){
    try{
        $base = Open-RegistryBase $Hive $View
        $sub = $base.OpenSubKey($Key.Trim('\\'))
        return @($base,$sub)
    }catch{ return @($null,$null) }
}
function New-SoftwareSeenKey([string]$DisplayName,[string]$Version,[string]$UninstallString,[string]$InstallLocation){
    return ((Clean $DisplayName) + '|' + (Clean $Version) + '|' + (Clean $UninstallString) + '|' + (Clean $InstallLocation)).ToLowerInvariant()
}
function Add-SoftwareRecordFields([object[]]$Fields,[string]$Comment){
    if($null -eq $script:SoftwareSeen){ $script:SoftwareSeen = @{} }
    $dn = [string]$Fields[0]
    if([string]::IsNullOrWhiteSpace($dn)){ return $false }
    $key = New-SoftwareSeenKey $Fields[0] $Fields[1] $Fields[3] $Fields[2]
    if($script:SoftwareSeen.ContainsKey($key)){ return $false }
    $script:SoftwareSeen[$key] = $true
    $null = Add-Fields 'software' $Fields $Comment
    return $true
}
function Add-SoftwareRegistryRecord([object]$Key,[string]$Comment){
    if($null -eq $Key){ return $false }
    try { $displayName = [string]$Key.GetValue('DisplayName',$null,[Microsoft.Win32.RegistryValueOptions]::DoNotExpandEnvironmentNames) } catch { $displayName = '' }
    if([string]::IsNullOrWhiteSpace($displayName)){ return $false }
    $get = {
        param($n)
        try{
            $v = $Key.GetValue($n,$null,[Microsoft.Win32.RegistryValueOptions]::DoNotExpandEnvironmentNames)
            if($null -eq $v){ return '' }
            if($v -is [array]){ return (($v | ForEach-Object {[string]$_}) -join ' ') }
            return [string]$v
        }catch{ return '' }
    }
    $comments = & $get 'Comments'
    if([string]::IsNullOrWhiteSpace($comments)){ $comments = '' }
    return (Add-SoftwareRecordFields @(
        $displayName,
        (& $get 'DisplayVersion'),
        (& $get 'InstallLocation'),
        (& $get 'UninstallString'),
        (& $get 'InstallDate'),
        (& $get 'Publisher'),
        (& $get 'InstallSource'),
        (& $get 'SystemComponent'),
        (& $get 'URLInfoAbout'),
        $comments
    ) $Comment)
}
function Get-RegSoftwareValue([string]$Computer,[object]$Hive,[string]$Key,[string]$Name){
    if(Is-LocalComputer $Computer){ return (Get-RegValueEx $Computer $Hive $Key $Name '64') }
    return (Get-RegValue $Computer $Hive $Key $Name)
}
function Add-InstalledSoftwareFromExplicitRegistryPath([string]$Computer,[object]$Hive,[string]$Root,[string]$Comment){
    try { Write-Host $Comment } catch {}
    $added = 0
    $rootClean = $Root.Trim('\')
    if(Is-LocalComputer $Computer){
        $pair = Open-RegistrySubKeyLocal $Hive $rootClean '64'
        $base = $pair[0]; $rootKey = $pair[1]
        try{
            if($null -ne $rootKey){
                foreach($name in @($rootKey.GetSubKeyNames())){
                    $sub = $null
                    try{
                        $sub = $rootKey.OpenSubKey($name)
                        if(Add-SoftwareRegistryRecord $sub $Comment){ $added++ }
                    }catch{} finally { if($null -ne $sub){ $sub.Close() } }
                }
            }
        } finally { if($null -ne $rootKey){ $rootKey.Close() }; if($null -ne $base){ $base.Close() } }
        return $added
    }

    foreach($sub in @(Enum-RegKey $Computer $Hive $rootClean)){
        $key="$rootClean\$sub"
        $dn=Get-RegSoftwareValue $Computer $Hive $key 'DisplayName'
        if([string]::IsNullOrWhiteSpace($dn)){ continue }
        $comments = Get-RegSoftwareValue $Computer $Hive $key 'Comments'
        if([string]::IsNullOrWhiteSpace($comments)){ $comments='' }
        if(Add-SoftwareRecordFields @(
            $dn,
            (Get-RegSoftwareValue $Computer $Hive $key 'DisplayVersion'),
            (Get-RegSoftwareValue $Computer $Hive $key 'InstallLocation'),
            (Get-RegSoftwareValue $Computer $Hive $key 'UninstallString'),
            (Get-RegSoftwareValue $Computer $Hive $key 'InstallDate'),
            (Get-RegSoftwareValue $Computer $Hive $key 'Publisher'),
            (Get-RegSoftwareValue $Computer $Hive $key 'InstallSource'),
            (Get-RegSoftwareValue $Computer $Hive $key 'SystemComponent'),
            (Get-RegSoftwareValue $Computer $Hive $key 'URLInfoAbout'),
            $comments
        ) $Comment){ $added++ }
    }
    return $added
}
function Invoke-SoftwareInventorySource([string]$Computer,[object]$Hive,[string]$Root,[string]$View,[string]$Comment){
    # Kept for compatibility with older call sites, but no longer relies on Registry32 redirection.
    $effectiveRoot = $Root.Trim('\')
    if($View -eq '32' -and $effectiveRoot -notmatch '(?i)\\WOW6432Node\\'){
        if($effectiveRoot -ieq 'SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall'){
            $effectiveRoot = 'SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall'
        } elseif($effectiveRoot -imatch '^(S-1-5-[^\\]+)\\Software\\Microsoft\\Windows\\CurrentVersion\\Uninstall$'){
            $effectiveRoot = $matches[1] + '\Software\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall'
        }
    }
    $raw = @(Add-InstalledSoftwareFromExplicitRegistryPath $Computer $Hive $effectiveRoot $Comment)
    $nums = @($raw | Where-Object { $_ -is [int] -or $_ -is [long] -or $_ -is [uint32] -or ([string]$_ -match '^\d+$') })
    if($nums.Count -gt 0){ return [int]$nums[-1] }
    return 0
}
function Get-LoadedUserSids([string]$Computer){
    $sids = New-Object System.Collections.Generic.List[string]
    foreach($sid in @(Enum-RegKeyEx $Computer 2147483651 '' '64')){
        if($sid -match '^S-1-5-21-' -and $sid -notmatch '_Classes$' -and -not $sids.Contains($sid)){ $sids.Add($sid) }
    }
    return @($sids)
}
function Add-UserHiveSoftwareForSid([string]$Computer,[string]$Sid,[string]$CommentSuffix){
    $total = 0
    $native = "$Sid\Software\Microsoft\Windows\CurrentVersion\Uninstall"
    $wow = "$Sid\Software\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall"
    $total += Invoke-SoftwareInventorySource $Computer 2147483651 $native '64' ("Installed Software 64 Bit (HKU $CommentSuffix)")
    $total += Add-InstalledSoftwareFromExplicitRegistryPath $Computer 2147483651 $wow ("Installed Software 32 Bit (HKU $CommentSuffix)")
    return $total
}
function Add-TemporarilyLoadedUserProfileSoftware([string]$Computer){
    # GitHub Desktop and similar per-user installers are often only below HKCU/HKU.
    # If the target profile is not loaded, HKU enumeration cannot see it. On local audits
    # with administrative rights, load NTUSER.DAT temporarily and scan both native and WOW6432Node Uninstall keys.
    if(-not (Is-LocalComputer $Computer)){ return 0 }
    $added = 0
    foreach($prof in @(Get-WmiObject -Class Win32_UserProfile -Filter "Special=False" -ErrorAction SilentlyContinue)){
        $sid = [string]$prof.SID
        if([string]::IsNullOrWhiteSpace($sid) -or $sid -notmatch '^S-1-5-21-'){ continue }
        if((Get-LoadedUserSids $Computer) -contains $sid){ continue }
        $ntUser = Join-Path ([string]$prof.LocalPath) 'NTUSER.DAT'
        if(-not (Test-Path -LiteralPath $ntUser)){ continue }
        $tempName = ('OA_AUDIT_' + ($sid -replace '[^A-Za-z0-9]','_'))
        $loaded = $false
        try{
            $p = Start-Process -FilePath "$env:SystemRoot\System32\reg.exe" -ArgumentList @('load',("HKU\$tempName"),$ntUser) -Wait -PassThru -WindowStyle Hidden -ErrorAction SilentlyContinue
            if($p.ExitCode -eq 0){ $loaded = $true }
        }catch{ $loaded = $false }
        if($loaded){
            try{
                $added += Add-UserHiveSoftwareForSid $Computer $tempName ("temporarily loaded profile $sid")
            } finally {
                try{ [gc]::Collect(); [gc]::WaitForPendingFinalizers() }catch{}
                try{ Start-Process -FilePath "$env:SystemRoot\System32\reg.exe" -ArgumentList @('unload',("HKU\$tempName")) -Wait -WindowStyle Hidden -ErrorAction SilentlyContinue | Out-Null }catch{}
            }
        }
    }
    return $added
}

function Get-SoftwarePropValue([object]$Props,[string]$Name){
    try{
        $p = $Props.PSObject.Properties[$Name]
        if($null -eq $p){ return '' }
        $v = $p.Value
        if($null -eq $v){ return '' }
        if($v -is [array]){ return (($v | ForEach-Object {[string]$_}) -join ' ') }
        return [string]$v
    }catch{ return '' }
}
function Add-SoftwareRecordFromRegistryProviderItem([object]$Item,[string]$Comment){
    try{ $props = Get-ItemProperty -LiteralPath $Item.PSPath -ErrorAction SilentlyContinue }catch{ return $false }
    if($null -eq $props){ return $false }
    $dn = Get-SoftwarePropValue $props 'DisplayName'
    if([string]::IsNullOrWhiteSpace($dn)){ $dn = Get-SoftwarePropValue $props 'QuietDisplayName' }
    if([string]::IsNullOrWhiteSpace($dn)){ return $false }
    $comments = Get-SoftwarePropValue $props 'Comments'
    if([string]::IsNullOrWhiteSpace($comments)){ $comments = Get-SoftwarePropValue $props 'HelpLink' }
    return (Add-SoftwareRecordFields @(
        $dn,
        (Get-SoftwarePropValue $props 'DisplayVersion'),
        (Get-SoftwarePropValue $props 'InstallLocation'),
        (Get-SoftwarePropValue $props 'UninstallString'),
        (Get-SoftwarePropValue $props 'InstallDate'),
        (Get-SoftwarePropValue $props 'Publisher'),
        (Get-SoftwarePropValue $props 'InstallSource'),
        (Get-SoftwarePropValue $props 'SystemComponent'),
        (Get-SoftwarePropValue $props 'URLInfoAbout'),
        $comments
    ) $Comment)
}
function Add-SoftwareFromRegistryProviderRoot([string]$Root,[string]$Comment){
    $added = 0
    try{
        if(-not (Test-Path -LiteralPath $Root)){ return 0 }
        foreach($item in @(Get-ChildItem -LiteralPath $Root -ErrorAction SilentlyContinue)){
            if(Add-SoftwareRecordFromRegistryProviderItem $item $Comment){ $added++ }
        }
    }catch{}
    return $added
}
function Add-RegistryProviderSoftwareDeepScan([string]$Computer){
    # Sicherheitsnetz: nutzt den PowerShell Registry-Provider direkt. Dadurch werden Eintraege gefunden,
    # die in der klassischen .NET RegistryView/StdRegProv-Logik auf manchen Systemen fehlen.
    if(-not (Is-LocalComputer $Computer)){ return 0 }
    $added = 0
    Echo 'Installed Software Registry Provider Deep Scan'
    $roots = @(
        'Registry::HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall',
        'Registry::HKEY_LOCAL_MACHINE\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall',
        'Registry::HKEY_CURRENT_USER\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall',
        'Registry::HKEY_CURRENT_USER\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall'
    )
    foreach($r in $roots){ $added += Add-SoftwareFromRegistryProviderRoot $r 'Installed Software Registry Provider Deep Scan' }

    try{
        foreach($sidKey in @(Get-ChildItem -LiteralPath 'Registry::HKEY_USERS' -ErrorAction SilentlyContinue)){
            $sid = Split-Path -Leaf $sidKey.Name
            if($sid -notmatch '^S-1-5-21-' -or $sid -match '_Classes$'){ continue }
            $added += Add-SoftwareFromRegistryProviderRoot ("Registry::HKEY_USERS\$sid\Software\Microsoft\Windows\CurrentVersion\Uninstall") "Installed Software Registry Provider HKU $sid"
            $added += Add-SoftwareFromRegistryProviderRoot ("Registry::HKEY_USERS\$sid\Software\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall") "Installed Software Registry Provider HKU WOW6432Node $sid"
            $added += Add-SoftwareFromRegistryProviderRoot ("Registry::HKEY_USERS\$sid\Software\Classes\VirtualStore\MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall") "Installed Software Registry Provider HKU VirtualStore $sid"
        }
    }catch{}
    return $added
}
function Add-AppDataSoftwareHeuristic([string]$Computer){
    # Nur Zusatz, kein Ersatz: findet typische per-user Installer, falls sie keinen lesbaren Uninstall-Key liefern.
    if(-not (Is-LocalComputer $Computer)){ return 0 }
    $added = 0
    Echo 'Installed Software AppData Heuristic Scan'
    try{
        foreach($u in @(Get-ChildItem -LiteralPath 'C:\Users' -Force -ErrorAction SilentlyContinue)){
            if(-not $u.PSIsContainer){ continue }
            if($u.Name -in @('Public','Default','Default User','All Users')){ continue }
            $candidates = @(
                @('GitHub Desktop', 'GitHub', (Join-Path $u.FullName 'AppData\Local\GitHubDesktop')),
                @('GitHub Desktop', 'GitHub', (Join-Path $u.FullName 'AppData\Local\Programs\GitHub Desktop')),
                @('Microsoft Dynamics NAV', 'Microsoft', (Join-Path $u.FullName 'AppData\Local\Microsoft\Dynamics NAV')),
                @('Microsoft Dynamics NAV', 'Microsoft', (Join-Path $u.FullName 'AppData\Roaming\Microsoft\Dynamics NAV'))
            )
            foreach($c in $candidates){
                $name=$c[0]; $publisher=$c[1]; $path=$c[2]
                if(Test-Path -LiteralPath $path){
                    $version=''
                    try{
                        $exe = Get-ChildItem -LiteralPath $path -Recurse -Filter '*.exe' -ErrorAction SilentlyContinue | Select-Object -First 1
                        if($exe){ $version = [System.Diagnostics.FileVersionInfo]::GetVersionInfo($exe.FullName).ProductVersion }
                    }catch{}
                    if(Add-SoftwareRecordFields @($name,$version,$path,'','',$publisher,'','','','Detected from AppData') 'Installed Software AppData Heuristic Scan'){ $added++ }
                }
            }
        }
    }catch{}
    return $added
}

function Add-AllInstalledSoftwareInventory([string]$Computer){
    $script:SoftwareSeen = @{}
    $total = 0
    # Do not rely on Registry32 redirection for per-user hives. Enumerate explicit native and WOW6432Node paths.
    $total += Invoke-SoftwareInventorySource $Computer 2147483650 'SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall' '64' 'Installed Software 64 Bit'
    $total += Add-InstalledSoftwareFromExplicitRegistryPath $Computer 2147483650 'SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall' 'Installed Software 32 Bit'

    $total += Invoke-SoftwareInventorySource $Computer 2147483649 'SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall' '64' 'Installed Software 64 Bit (current user)'
    $total += Add-InstalledSoftwareFromExplicitRegistryPath $Computer 2147483649 'SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall' 'Installed Software 32 Bit (current user)'

    try { Write-Host 'Installed Software 32/64 Bit (for each loaded User Hive)' } catch {}
    foreach($sid in @(Get-LoadedUserSids $Computer)){
        $total += Add-UserHiveSoftwareForSid $Computer $sid $sid
    }
    $total += Add-TemporarilyLoadedUserProfileSoftware $Computer
    $total += Add-RegistryProviderSoftwareDeepScan $Computer
    $total += Add-AppDataSoftwareHeuristic $Computer
    if($total -eq 0){ Echo 'Installed Software inventory produced 0 entries - registry access or profile hives may be unavailable.' }
}
function Decode-DigitalProductId([byte[]]$d){ if($null -eq $d -or $d.Length -lt 67){return ''}; $chars='BCDFGHJKMPQRTVWXY2346789'.ToCharArray(); $key=New-Object char[] 25; for($i=24;$i -ge 0;$i--){$cur=0; for($j=14;$j -ge 0;$j--){$cur=($cur*256) -bxor $d[$j+52]; $d[$j+52]=[math]::Floor($cur/24); $cur=$cur%24}; $key[$i]=$chars[$cur]}; $s=-join $key; return $s.Substring(0,5)+'-'+$s.Substring(5,5)+'-'+$s.Substring(10,5)+'-'+$s.Substring(15,5)+'-'+$s.Substring(20,5) }
function Get-RegBinaryLocal([object]$Hive,[string]$Key,[string]$Name,[string]$View='64'){ try{ $pair=Open-RegistrySubKeyLocal $Hive $Key $View; $b=$pair[0]; $k=$pair[1]; if($k){$v=$k.GetValue($Name); $k.Close(); $b.Close(); return [byte[]]$v}; if($b){$b.Close()} }catch{}; return $null }

function Get-RegBinaryLocalSafe([object]$Hive,[string]$Key,[string]$Name,[string]$View='64'){
    try{
        $pair=Open-RegistrySubKeyLocal $Hive $Key $View
        $b=$pair[0]; $k=$pair[1]
        if($k){ $v=$k.GetValue($Name); $k.Close(); if($b){$b.Close()}; return $v }
        if($b){$b.Close()}
    }catch{}
    return $null
}
function Get-FileVersionInfoSafe([string]$Path){
    try{ if(Test-Path -LiteralPath $Path){ return [System.Diagnostics.FileVersionInfo]::GetVersionInfo($Path) } }catch{}
    return $null
}
function Add-SpecialSoftwareInventory([string]$Computer,[object]$Os){
    if(-not (Is-LocalComputer $Computer)){ return }
    Echo 'Installed Media Codecs'
    $sys = Join-Path $env:WINDIR 'system32'
    $codec = Join-Path $sys 'L3CODECA.ACM'
    $cvi = Get-FileVersionInfoSafe $codec
    if($cvi){
        $dt=''
        try{ $dt=(Get-Item -LiteralPath $codec).LastWriteTime.ToString('yyyyMMddHHmmss.000000zzz').Replace(':','') }catch{}
        Add-Fields 'software' @('Codec - Audio - l3codeca',$cvi.FileVersion,$codec,'',$dt,$cvi.CompanyName,'','','',$cvi.FileDescription) 'Installed Media Codecs'
    }
    Echo 'MDAC/WDAC, DirectX, Media Player, IE and OE Versions'
    $install = WmiDateShort $Os.InstallDate
    $cv='SOFTWARE\Microsoft\Windows NT\CurrentVersion'
    $buildLab = Get-RegValueEx $Computer 2147483650 $cv 'BuildLabEx' '64'
    $ubr = Get-RegDwordEx $Computer 2147483650 $cv 'UBR' '64'
    $build = Get-RegValueEx $Computer 2147483650 $cv 'CurrentBuild' '64'
    $displayVersion = Get-RegValueEx $Computer 2147483650 $cv 'DisplayVersion' '64'
    $prod = Clean $Os.Caption
    if([string]::IsNullOrWhiteSpace($prod)){ $prod=Get-RegValueEx $Computer 2147483650 $cv 'ProductName' '64' }
    $winVer = Clean $Os.Version
    if($build -and $ubr -match '^\d+$'){ $winVer = "$build.$ubr" }
    Add-Fields 'software' @('MDAC','6.3.9600.16384','','',$install,'Microsoft Corporation','','','https://msdn2.microsoft.com/en-us/data/default.aspx',' ') 'MDAC/WDAC, DirectX, Media Player, IE and OE Versions'
    Add-Fields 'software' @('DirectX 9c','4.09.00.0904','','',$install,'Microsoft Corporation','','','https://www.microsoft.com/windows/directx/',' ') 'MDAC/WDAC, DirectX, Media Player, IE and OE Versions'
    Add-Fields 'software' @('Windows Media Player','','','',$install,'Microsoft Corporation','','','https://www.microsoft.com/windows/windowsmedia/default.aspx',' ') 'MDAC/WDAC, DirectX, Media Player, IE and OE Versions'
    $ieVer = Get-RegValueEx $Computer 2147483650 'SOFTWARE\Microsoft\Internet Explorer' 'svcVersion' '64'
    if([string]::IsNullOrWhiteSpace($ieVer)){ $ieVer = Get-RegValueEx $Computer 2147483650 'SOFTWARE\Microsoft\Internet Explorer' 'Version' '64' }
    Add-Fields 'software' @('Internet Explorer',$ieVer,'','',$install,'Microsoft Corporation','','','https://www.microsoft.com/windows/ie/community/default.mspx',' ') 'MDAC/WDAC, DirectX, Media Player, IE and OE Versions'
    Add-Fields 'software' @($prod,$winVer,'','',$install,'Microsoft Corporation','','','https://www.microsoft.com/windows/default.mspx',(($buildLab+' - '+$displayVersion).Trim())) 'MDAC/WDAC, DirectX, Media Player, IE and OE Versions'
}
function Convert-EdidManufacturer([byte[]]$Edid){
    if($null -eq $Edid -or $Edid.Length -lt 10){ return '' }
    $word = ($Edid[8] -shl 8) -bor $Edid[9]
    $c1 = [char]((($word -shr 10) -band 31) + 64)
    $c2 = [char]((($word -shr 5) -band 31) + 64)
    $c3 = [char](($word -band 31) + 64)
    return "$c1$c2$c3"
}
function Get-EdidDescriptorText([byte[]]$Edid,[byte]$Tag){
    if($null -eq $Edid -or $Edid.Length -lt 128){ return '' }
    foreach($off in 54,72,90,108){
        if($Edid[$off] -eq 0 -and $Edid[$off+1] -eq 0 -and $Edid[$off+2] -eq 0 -and $Edid[$off+3] -eq $Tag){
            $bytes = $Edid[($off+5)..($off+17)] | Where-Object { $_ -ne 0 -and $_ -ne 10 -and $_ -ne 13 }
            if($bytes){ return ([System.Text.Encoding]::ASCII.GetString([byte[]]$bytes)).Trim() }
        }
    }
    return ''
}
function Add-MonitorInventoryFromRegistry([string]$Computer){
    Echo 'Monitor Info'
    $base='SYSTEM\CurrentControlSet\Enum\DISPLAY'
    $added=0
    foreach($mfgKey in @(Enum-RegKeyEx $Computer 2147483650 $base '64')){
        foreach($inst in @(Enum-RegKeyEx $Computer 2147483650 "$base\$mfgKey" '64')){
            $key="$base\$mfgKey\$inst"
            $hw = Get-RegValueEx $Computer 2147483650 $key 'HardwareID' '64'
            if($hw -and ([string]$hw).ToLowerInvariant().IndexOf('monitor\') -lt 0){ continue }
            $desc = Get-RegValueEx $Computer 2147483650 $key 'DeviceDesc' '64'
            $mfg = Get-RegValueEx $Computer 2147483650 $key 'Mfg' '64'
            $edid = Get-RegBinaryLocalSafe 2147483650 "$key\Device Parameters" 'EDID' '64'
            $manId=''; $devId=''; $manDate=''; $model=''; $serial=''; $ver=''
            if($edid -and $edid.Length -ge 128){
                $manId = Convert-EdidManufacturer $edid
                $devId = ('{0:X2}{1:X2}' -f $edid[11],$edid[10])
                $week=[int]$edid[16]; $year=[int]$edid[17]+1990
                if($week -gt 0){ try{ $d=(Get-Date -Year $year -Month 1 -Day 1).AddDays(($week*7)-1); $manDate=$d.ToString('MM/yyyy') }catch{ $manDate=('01/{0}' -f $year) } } else { $manDate=('01/{0}' -f $year) }
                $serial = Get-EdidDescriptorText $edid 0xFF
                $model = Get-EdidDescriptorText $edid 0xFC
                $ver = ('{0}.{1}' -f $edid[18],$edid[19])
            }
            if([string]::IsNullOrWhiteSpace($model)){ $model=$desc }
            if([string]::IsNullOrWhiteSpace($serial)){ $serial='Serial Number Not Found in EDID data' }
            if($mfg -and $mfg -ne '(Standard monitor types)'){ $manId=$mfg }
            if([string]::IsNullOrWhiteSpace($manId)){ $manId=$mfgKey }
            if($manId){ Add-Fields 'monitor_sys' @($manId,$devId,$manDate,$model,$serial,$ver) 'Monitor Info'; $added++ }
        }
    }
    if($added -eq 0){
        foreach($mon in @(Get-Wmi $Computer 'root\wmi' 'Select * from WmiMonitorID' '' '')){ $man=([string]::Join('',(@($mon.ManufacturerName)|Where-Object{$_}|ForEach-Object{[char]$_}))); $nam=([string]::Join('',(@($mon.UserFriendlyName)|Where-Object{$_}|ForEach-Object{[char]$_}))); $ser=([string]::Join('',(@($mon.SerialNumberID)|Where-Object{$_}|ForEach-Object{[char]$_}))); Add-Fields 'monitor_sys' @($man,'','',$nam,$ser,'') 'Monitor Info' }
    }
}

function Add-WindowsKeys([string]$Computer){ if(-not (Is-LocalComputer $Computer)){return}; Echo 'CD Keys'; $cv='SOFTWARE\Microsoft\Windows NT\CurrentVersion'; $name=Get-RegValueEx $Computer 2147483650 $cv 'ProductName' '64'; try{ $osCap=(First(Get-Wmi $Computer 'root\cimv2' 'Select Caption from Win32_OperatingSystem' '' '')).Caption; if($osCap){$name=Clean $osCap} }catch{}; $pid=Get-RegValueEx $Computer 2147483650 $cv 'ProductId' '64'; $build=Get-RegValueEx $Computer 2147483650 $cv 'CurrentBuild' '64'; $exp=Get-RegValueEx $Computer 2147483650 $cv 'ExperiencePackVersion' '64'; $key=Decode-DigitalProductId (Get-RegBinaryLocal 2147483650 $cv 'DigitalProductId' '64'); if($key){Add-Fields 'ms_keys' @(($name+' ('+$pid+')'),$key,$build,$exp,'windows_xp') 'CD Keys'}; Echo 'Windows keys 64bit'; $key4=Decode-DigitalProductId (Get-RegBinaryLocal 2147483650 $cv 'DigitalProductId4' '64'); if($key4){Add-Fields 'ms_keys' @(($name+' (ID4)'),$key4,$build,$exp,'windows_xp') 'Windows keys 64bit'} }
function Add-ModernApps([string]$Computer){
    Echo 'Installed Modern Apps 32 Bit per User'
    $added = 0
    if(Is-LocalComputer $Computer){
        foreach($sid in Enum-RegKeyEx $Computer 2147483651 '' '64'){
            if($sid.Length -le 40 -or $sid -match 'Classes'){ continue }
            $pkgRoot = "$sid\Software\Classes\ActivatableClasses\Package"
            $pair = Open-RegistrySubKeyLocal 2147483651 $pkgRoot '32'
            $base = $pair[0]; $rootKey = $pair[1]
            try{
                if($null -eq $rootKey){ continue }
                foreach($pkg in @($rootKey.GetSubKeyNames())){
                    if($pkg -match 'NOPUBLISHERID'){ continue }
                    $parts = $pkg -split '_'
                    if($parts.Count -lt 3){ continue }
                    Add-Fields 'softwapps' @($parts[0],$parts[1],'','','','Windows Modern App','','','',$parts[2]) 'Installed Modern Apps 32 Bit per User'
                    $added++
                }
            } finally { if($null -ne $rootKey){$rootKey.Close()}; if($null -ne $base){$base.Close()} }
        }
    } else {
        foreach($sid in Enum-RegKey $Computer 2147483651 ''){
            if($sid.Length -le 40 -or $sid -match 'Classes'){ continue }
            foreach($pkg in Enum-RegKey $Computer 2147483651 "$sid\Software\Classes\ActivatableClasses\Package"){
                if($pkg -match 'NOPUBLISHERID'){ continue }
                $parts = $pkg -split '_'
                if($parts.Count -lt 3){ continue }
                Add-Fields 'softwapps' @($parts[0],$parts[1],'','','','Windows Modern App','','','',$parts[2]) 'Installed Modern Apps 32 Bit per User'
                $added++
            }
        }
    }
    if($added -eq 0){ Echo 'Installed Modern Apps inventory produced 0 entries.' }
}
function Add-ODBC([string]$Computer){ Echo 'ODBC Connections (64-Bit, System DSN only'; $root='SOFTWARE\ODBC\ODBC.INI'; foreach($dsn in Enum-RegValuesEx $Computer 2147483650 "$root\ODBC Data Sources" '64'){ Echo ('Name: '+$dsn); Echo ("$root\$dsn"); $parts=@(); foreach($v in Enum-RegValuesEx $Computer 2147483650 "$root\$dsn" '64'){ $parts += ($v+': '+(Get-RegValueEx $Computer 2147483650 "$root\$dsn" $v '64')) }; if($parts.Count){ Add-Fields 'odbc' @(("$root\$dsn "),(($parts -join ' ')+' ')) 'ODBC Connections (64-Bit, System DSN only' } } }
function Add-AutoUpdate([string]$Computer){ Echo 'Automatic Updating Settings'; $key='SOFTWARE\Microsoft\Windows\CurrentVersion\WindowsUpdate\Auto Update'; $no=Get-RegDwordEx $Computer 2147483650 $key 'NoAutoUpdate' '64'; $au=Get-RegDwordEx $Computer 2147483650 $key 'AUOptions' '64'; $enabled=if([string]$no -eq '1'){'False'}else{'True'}; $mode=switch([string]$au){'2'{'Notify before download'}'3'{'Download and notify'}'4'{'Automatic'}default{'Unknown'}}; Add-Fields 'auto_upd' @('', $enabled, $mode,'','','','','','','','','') 'Automatic Updating Settings' }
function Add-BrowserAndFirewallSettings([string]$Computer){ Echo 'Internet Explorer Browser Helper Objects'; foreach($sub in Enum-RegKeyEx $Computer 2147483650 'SOFTWARE\Microsoft\Windows\CurrentVersion\Explorer\Browser Helper Objects' '64'){ Add-Fields 'browser_helper' @($sub) 'Internet Explorer Browser Helper Objects' }; $os=First(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_OperatingSystem' '' ''); Add-SpecialSoftwareInventory $Computer $os; Echo 'Firefox Extensions'; Echo 'Windows Firewall Settings'; $dm=Get-RegDwordEx $Computer 2147483650 'SYSTEM\CurrentControlSet\Services\SharedAccess\Parameters\FirewallPolicy\DomainProfile' 'EnableFirewall' '64'; $std=Get-RegDwordEx $Computer 2147483650 'SYSTEM\CurrentControlSet\Services\SharedAccess\Parameters\FirewallPolicy\StandardProfile' 'EnableFirewall' '64'; Add-Fields 'system11' @($dm,'','','',$std,'','','') 'Windows Firewall Settings' }


function Write-AnsiLine([string]$Path,[string]$Line){
    try{
        [System.IO.File]::AppendAllText($Path, $Line + "`r`n", [System.Text.Encoding]::Default)
    } catch {
        Add-Content -LiteralPath $Path -Value $Line -Encoding Default
    }
}
function Entry([string]$Line,[string]$Comment){
    if([string]::IsNullOrWhiteSpace($Line)){ return }
    $script:form_total += $Line + "`r`n"
    if($script:online -eq 'n'){
        Write-AnsiLine $script:offline_file $Line
    }
    if($script:verbose -eq 'y' -and $Comment){ Echo $Comment }
}
function ConvertTo-FormUrlEncodedValue([string]$Value){
    if($null -eq $Value){ return '' }
    # [Uri]::EscapeDataString() fails on large audit payloads in Windows PowerShell/.NET
    # ("Invalid URI: The Uri string is too long"). WebUtility.UrlEncode has no such URI-size limit.
    try { return [System.Net.WebUtility]::UrlEncode($Value) } catch {}
    try {
        Add-Type -AssemblyName System.Web -ErrorAction SilentlyContinue
        return [System.Web.HttpUtility]::UrlEncode($Value)
    } catch {}
    # Last-resort RFC/form-url-encoder, intentionally streaming character-by-character.
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($Value)
    $sb = New-Object System.Text.StringBuilder
    foreach($b in $bytes){
        if(($b -ge 65 -and $b -le 90) -or ($b -ge 97 -and $b -le 122) -or ($b -ge 48 -and $b -le 57) -or $b -in 45,46,95,126){ [void]$sb.Append([char]$b) }
        elseif($b -eq 32){ [void]$sb.Append('+') }
        else { [void]$sb.Append('%'); [void]$sb.Append($b.ToString('X2')) }
    }
    return $sb.ToString()
}
function Send-AuditResults{
    if($script:online -notin @('ie','yesxml','y','yes')){ return }
    $target = if($script:online -eq 'yesxml' -and $script:non_ie_page){$script:non_ie_page}else{$script:non_ie_page}
    if([string]::IsNullOrWhiteSpace($target)){ return }
    try{
        $wc=New-Object System.Net.WebClient
        $wc.Encoding = [System.Text.Encoding]::UTF8
        $wc.Headers['Content-Type']='application/x-www-form-urlencoded; charset=UTF-8'
        $payload='add=' + (ConvertTo-FormUrlEncodedValue ($script:form_total + "`r`n"))
		Echo ("**** Sending audit data to: " + $target)
        [void]$wc.UploadString($target,'POST',$payload)
        Echo "Audit data submitted to $target"
    } catch { Echo ("Error submitting audit data: " + $_.Exception.Message) }
}
function Add-Fields([string]$Prefix,[object[]]$Fields,[string]$Comment){
    Entry ($Prefix + '^^^' + (($Fields | ForEach-Object { Clean $_ }) -join '^^^') + '^^^') $Comment
}
function DomainRoleText([object]$r){
    switch([string]$r){'0'{'Standalone Workstation'}'1'{'Workstation'}'2'{'Standalone Server'}'3'{'Member Server'}'4'{'Backup Domain Controller'}'5'{'Primary Domain Controller'}default{'Unknown'}}
}
function ChassisText([object]$v){
    $n=[string](First $v)
    switch($n){'1'{'Other'}'2'{'Unknown'}'3'{'Desktop'}'4'{'Low Profile Desktop'}'5'{'Pizza Box'}'6'{'Mini Tower'}'7'{'Tower'}'8'{'Portable'}'9'{'Laptop'}'10'{'Notebook'}'11'{'Hand Held'}'12'{'Docking Station'}'13'{'All in One'}'14'{'Sub Notebook'}'15'{'Space-Saving'}'16'{'Lunch Box'}'17'{'Main System Chassis'}'18'{'Expansion Chassis'}'19'{'SubChassis'}'20'{'Bus Expansion Chassis'}'21'{'Peripheral Chassis'}'22'{'Storage Chassis'}'23'{'Rack Mount Chassis'}'24'{'Sealed-Case PC'}'25'{'Multi-system chassis'}'26'{'Compact PCI'}'27'{'Advanced TCA'}'28'{'Blade'}'29'{'Blade Enclosure'}'30'{'Tablet'}'31'{'Convertible'}'32'{'Detachable'}'33'{'IoT Gateway'}'34'{'Embedded PC'}'35'{'Mini PC'}'36'{'Stick PC'}default{$n}}
}
function NetConnectionStatus([object]$v){switch([string]$v){'0'{'Disconnected'}'1'{'Connecting'}'2'{'Connected'}'3'{'Disconnecting'}'4'{'Hardware not present'}'5'{'Hardware disabled'}'6'{'Hardware malfunction'}'7'{'Media disconnected'}'8'{'Authenticating'}'9'{'Authentication succeeded'}'10'{'Authentication failed'}'11'{'Invalid address'}'12'{'Credentials required'}default{'unknown'}}}
function MouseType([object]$v){switch([string]$v){'1'{'Other'}'2'{'Unknown'}'3'{'Mouse'}'4'{'Track Ball'}'5'{'Track Point'}'6'{'Glide Point'}'7'{'Touch Pad'}'8'{'Touch Screen'}'9'{'Mouse - Optical Sensor'}default{Clean $v}}}
function MousePort([object]$v){switch([string]$v){'1'{'Other'}'2'{'Unknown'}'3'{'Serial'}'4'{'PS/2'}'5'{'Infrared'}'6'{'HP-HIL'}'7'{'Bus mouse'}'8'{'ADB (Apple Desktop Bus)'}'160'{'Bus mouse DB-9'}'161'{'Bus mouse micro-DIN'}'162'{'USB'}default{Clean $v}}}
function MemoryFormFactor([object]$v){switch([string]$v){'1'{'Other'}'2'{'SIP'}'3'{'DIP'}'4'{'ZIP'}'5'{'SOJ'}'6'{'Proprietary'}'7'{'SIMM'}'8'{'DIMM'}'9'{'TSOP'}'10'{'PGA'}'11'{'RIMM'}'12'{'SODIMM'}'13'{'SRIMM'}'14'{'SMD'}'15'{'SSMP'}'16'{'QFP'}'17'{'TQFP'}'18'{'SOIC'}'19'{'LCC'}'20'{'PLCC'}'21'{'BGA'}'22'{'FPBGA'}'23'{'LGA'}default{'Unknown'}}}
function MemoryTypeText([object]$v){switch([string]$v){'1'{'Other'}'2'{'DRAM'}'3'{'Synchronous DRAM'}'4'{'Cache DRAM'}'5'{'EDO'}'6'{'EDRAM'}'7'{'VRAM'}'8'{'SRAM'}'9'{'RAM'}'10'{'ROM'}'11'{'Flash'}'12'{'EEPROM'}'13'{'FEPROM'}'14'{'EPROM'}'15'{'CDRAM'}'16'{'3DRAM'}'17'{'SDRAM'}'18'{'SGRAM'}'19'{'RDRAM'}'20'{'DDR'}'21'{'DDR-2'}'24'{'DDR3'}'26'{'DDR4'}'34'{'DDR5'}default{'Unknown'}}}
function Audit([string]$Computer,[string]$User,[string]$Password){
    $script:form_total=''
    $timestamp=Get-Date -Format 'yyyyMMddHHmmss'
    Echo "PC name supplied: $Computer"

    # Fail fast before starting WMI/RPC. Port 135 is the RPC endpoint mapper
    # used by classic remote WMI. Hosts that are offline/non-Windows or block
    # WMI are skipped after a short timeout instead of the normal long wait.
    $wmiProbeTimeoutMs = 1500
    if(-not (Test-WmiEndpoint $Computer $wmiProbeTimeoutMs)){
        Echo "No reachable Windows/WMI endpoint on $Computer after ${wmiProbeTimeoutMs}ms - skipping host."
        return
    }

    # A failed WMI connection must abort only this host. The caller can then
    # continue with the next entry from input_file.
    $cs=First (Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_ComputerSystem' $User $Password)
    if($null -eq $cs -or [string]::IsNullOrWhiteSpace([string]$cs.Name)){
        Echo "WMI connection to $Computer failed - skipping host."
        return
    }
    $os=First (Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_OperatingSystem' $User $Password)
    $csp=First (Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_ComputerSystemProduct' $User $Password)
    $bios=First (Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_BIOS' $User $Password)
    $bb=First (Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_BaseBoard' $User $Password)
    $system_name=Clean $cs.Name; if(-not $system_name){$system_name=$Computer}
    $domain=Clean $cs.Domain
    $user_name=[System.Security.Principal.WindowsIdentity]::GetCurrent().Name
    $firstNic=First (Get-Wmi $Computer 'root\cimv2' "SELECT MACAddress FROM Win32_NetworkAdapterConfiguration WHERE MACAddress IS NOT NULL AND IPEnabled = True" $User $Password)
    $system_type=''
    $manufacturer=([string]$cs.Manufacturer).ToLowerInvariant(); $model=([string]$cs.Model).ToLowerInvariant()
    if($manufacturer -eq 'microsoft corporation' -and $model -eq 'virtual machine'){$system_type='VMH'} elseif($manufacturer -like '*vmware*' -or $model -like '*vmware*'){$system_type='VMV'} else { $enc=First(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_SystemEnclosure' $User $Password); $system_type=ChassisText $enc.ChassisTypes }
    $system_uuid = (Clean $csp.UUID) + '-' + (Clean $bios.SerialNumber) + '-' + (Clean $bb.SerialNumber)
    if($system_type -in @('VMH','VMV') -and $firstNic.MACAddress){ $system_uuid += '-' + (Clean $firstNic.MACAddress) }
    if($script:uuid_type -eq 'mac' -and $firstNic.MACAddress){$system_uuid=Clean $firstNic.MACAddress}
    if($script:uuid_type -eq 'name'){$system_uuid=($system_name+'.'+$domain).Trim('.')}
    Echo "System UUID: $system_uuid"
    Echo "Unique System Identifikation:$system_uuid"
    Echo "PC name from WMI: $system_name"
    Echo "User executing this script: $user_name"
    $script:offline_file = Join-Path $script:sScriptPath ($system_name + '.txt')
	Echo ("**** OpenAudit Classic offline output file: " + $script:offline_file)
	
    if($script:online -eq 'n'){ if(Test-Path $script:offline_file){Remove-Item $script:offline_file -Force} }

    Echo 'Network Info'
    $netIpAddress=''; $netIpMask=''; $netMacUuid=''
    $nics=Get-Wmi $Computer 'root\cimv2' "Select * from Win32_NetworkAdapterConfiguration WHERE ServiceName<>'' AND ServiceName<>'AsyncMac' AND ServiceName<>'VMnetx' AND ServiceName<>'VMnetadapter' AND ServiceName<>'Rasl2tp' AND ServiceName<>'msloop' AND ServiceName<>'PptpMiniport' AND ServiceName<>'Raspti' AND ServiceName<>'NDISWan' AND ServiceName<>'NdisWan4' AND ServiceName<>'RasPppoe' AND ServiceName<>'tunmp' AND ServiceName<>'tunnel' AND ServiceName<>'VPCNetS2' AND ServiceName<>'RasSstp' AND ServiceName<>'NdisIP' AND Description<>'PPP Adapter.'" $User $Password
    foreach($nic in @($nics)){
        $ad=First(Get-Wmi $Computer 'root\cimv2' ("Select * from Win32_NetworkAdapter WHERE Index='{0}'" -f $nic.Index) $User $Password)
        if($null -eq $ad){continue}
        $drv=First(Get-Wmi $Computer 'root\cimv2' ("Select * from Win32_PNPSignedDriver WHERE DeviceClass = 'NET' AND DeviceID = '{0}'" -f ([string]$ad.PNPDeviceID).Replace('\','\\')) $User $Password)
        $ip0=PadIp ([string](First $nic.IPAddress)); $mask0=Clean (First $nic.IPSubnet)
        if($ip0 -ne 'none' -and -not $netIpAddress){$netIpAddress=$ip0;$netIpMask=$mask0}
        if($nic.MACAddress -and -not $netMacUuid){$netMacUuid=$nic.MACAddress}
        Add-Fields 'network' @($nic.MACAddress,$nic.Description,(BoolText $nic.DHCPEnabled),$nic.DHCPServer,$nic.DNSHostName,(First $nic.DNSServerSearchOrder),(@($nic.DNSServerSearchOrder)[1]),$ip0,$mask0,$nic.WINSPrimaryServer,$nic.WINSSecondaryServer,$ad.AdapterType,$ad.Manufacturer,(First $nic.DefaultIPGateway),(BoolText $nic.IPEnabled),$nic.Index,$nic.ServiceName,(WmiDate $nic.DHCPLeaseObtained),(WmiDate $nic.DHCPLeaseExpires),(@($nic.DNSServerSearchOrder)[2]),$nic.DNSDomain,(First $nic.DNSDomainSuffixSearchOrder),(@($nic.DNSDomainSuffixSearchOrder)[1]),(@($nic.DNSDomainSuffixSearchOrder)[2]),(BoolText $nic.DomainDNSRegistrationEnabled),(BoolText $nic.FullDNSRegistrationEnabled),(PadIp ([string](@($nic.IPAddress)[1]))),(@($nic.IPSubnet)[1]),(PadIp ([string](@($nic.IPAddress)[2]))),(@($nic.IPSubnet)[2]),(BoolText $nic.WINSEnableLMHostsLookup),$nic.TcpipNetbiosOptions,(First $nic.GatewayCostMetric),(@($nic.DefaultIPGateway)[1]),(@($nic.GatewayCostMetric)[1]),(@($nic.DefaultIPGateway)[2]),(@($nic.GatewayCostMetric)[2]),$nic.IpConnectionMetric,$ad.NetConnectionId,(NetConnectionStatus $ad.NetConnectionStatus),$ad.Speed,$drv.DriverProviderName,$drv.DriverVersion,(WmiDateShort $drv.DriverDate)) 'Network Info'
    }
    $netUser=Clean $cs.UserName
    if(-not $netUser){ $netUser=Get-RegValue $Computer 2147483650 'SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon' 'DefaultUserName' }
    Add-Fields 'system01' @($netIpAddress,$domain,$netUser,'','','') 'Network Info'
    Add-Fields 'audit' @($system_name,$timestamp,$system_uuid,$user_name,$script:ie_submit_verbose,$script:software_audit) 'Audit Info'

    Echo 'System Information'
    $memKb=0; if($os.TotalVisibleMemorySize){$memKb=[int64]$os.TotalVisibleMemorySize}
    $tz=First(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_TimeZone' $User $Password)
    $cpu=First(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_Processor' $User $Password)
    $tpm=First(Get-Wmi $Computer 'root\cimv2\Security\MicrosoftTPM' 'Select * from Win32_Tpm' $User $Password)
    if(($null -eq $tpm) -or [string]::IsNullOrWhiteSpace([string]$tpm.SpecVersion)){
        $tpm=First(Get-Wmi $Computer 'root\CIMV2\Security\MicrosoftTpm' 'Select * from Win32_Tpm' $User $Password)
    }
    $tpmSpec = Clean $tpm.SpecVersion
    $tpmEnabled = BoolText $tpm.IsEnabled_InitialValue
    $tpmOwned = BoolText $tpm.IsOwned_InitialValue
    if(Is-LocalComputer $Computer){
        try{
            $gt = Get-Tpm -ErrorAction Stop
            if([string]::IsNullOrWhiteSpace($tpmSpec)){ $tpmSpec = Clean $gt.SpecVersion }
            if([string]::IsNullOrWhiteSpace($tpmEnabled)){ $tpmEnabled = BoolText $gt.TpmReady }
            if([string]::IsNullOrWhiteSpace($tpmOwned)){ $tpmOwned = BoolText $gt.TpmOwned }
        } catch {}
    }
    Add-Fields 'system02' @($cs.Model,$system_name,$cs.NumberOfProcessors,(BoolText $cs.PartOfDomain),$cs.PrimaryOwnerName,$system_type,(SafeDivInt $memKb 1024),$csp.IdentifyingNumber,$csp.Vendor,(DomainRoleText $cs.DomainRole),$tz.Caption,$tz.DaylightName,$cpu.NumberOfCores,$cpu.NumberOfLogicalProcessors,$tpmSpec,$tpmEnabled,$tpmOwned) 'System Information'

    Echo 'Windows Info'
    $arch=if($os.OSArchitecture){$os.OSArchitecture}elseif($os.Caption -match 'x64'){'64-bit'}else{'32-bit'}
    $lastBoot=WmiDate $os.LastBootUpTime
    Add-Fields 'system03' @($os.BootDevice,$os.BuildNumber,$os.OSType,$os.Caption,$os.CountryCode,$os.Description,(WmiDateShort $os.InstallDate),$os.Organization,$os.OSLanguage,$os.RegisteredUser,$os.SerialNumber,("$($os.ServicePackMajorVersion).$($os.ServicePackMinorVersion)"),$os.Version,$os.WindowsDirectory,$lastBoot,$arch) 'Windows Info'

    Echo 'Bios Info'
    $enc=First(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_SystemEnclosure' $User $Password)
    Add-Fields 'bios' @($bios.Description,$bios.Manufacturer,$bios.SerialNumber,$bios.SMBIOSBIOSVersion,$bios.Version,$enc.SMBIOSAssetTag) 'Bios Info'
    Echo 'Processor Info'
    foreach($p in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_Processor' $User $Password)){ Add-Fields 'processor' @($p.Caption,$p.CurrentClockSpeed,$p.CurrentVoltage,$p.DeviceID,$p.ExtClock,$p.Manufacturer,$p.MaxClockSpeed,$p.Name,(BoolText $p.PowerManagementSupported),$p.SocketDesignation) 'Processor Info' }
    Echo 'Memory Info'
    $mems=Get-Wmi $Computer 'root\cimv2' 'Select * FROM Win32_PhysicalMemory' $User $Password
    foreach($m in @($mems)){ Add-Fields 'memory' @($m.DeviceLocator,(MemoryFormFactor $m.FormFactor),(MemoryTypeText $m.MemoryType),$m.TypeDetail,(SafeDivInt ([double]$m.Capacity) 1048576),$m.Speed,$m.Tag) 'Memory Info' }
    if(@($mems).Count -eq 0){ Add-Fields 'memory' @('Unknown','Unknown','Unknown','Unknown',(SafeDivInt $memKb 1024),'0','Unknown') 'Memory Info' }
    Echo 'Windows optional features installed'
    foreach($f in @(Get-Wmi $Computer 'root\cimv2' 'select * from Win32_OptionalFeature where installstate=1' $User $Password)){ Add-Fields 'optionalfeatures' @($f.Caption,$f.Name) 'Windows optional features installed' }
    Echo 'Video Info'
    foreach($v in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_VideoController' $User $Password)){ if($v.Caption -notmatch 'vnc|Innobec SideWindow'){ Add-Fields 'video' @((SafeDivInt ([double]$v.AdapterRAM) 1048576),$v.Caption,$v.CurrentHorizontalResolution,$v.CurrentNumberOfColors,$v.CurrentRefreshRate,$v.CurrentVerticalResolution,$v.Description,(WmiDateShort $v.DriverDate),$v.DriverVersion,$v.MaxRefreshRate,$v.MinRefreshRate,$v.DeviceID) 'Video Info' } }
    Add-MonitorInventoryFromRegistry $Computer
    Echo 'USB Devices'
    # Non-blocking USB inventory: avoid one WMI query per USBControllerDevice link.
    # Some devices/drivers hang on Win32_PnPEntity lookups by exact DeviceID. Use a few bulk
    # prefix queries instead and de-duplicate locally so the audit can continue after USB.
    $usbSeen = @{}
    $usbSkipDescriptions = @(
        'USB Root Hub','HID-compliant mouse','Generic USB Hub','Generic volume',
        'USB Mass Storage Device','HID-compliant device','USB Human Interface Device',
        'HID Keyboard Device','USB Composite Device','HID-compliant consumer control device',
        'USB Printing Support'
    )
    $usbQueries = @(
        "Select * From Win32_PnPEntity Where DeviceID Like 'USB%'",
        "Select * From Win32_PnPEntity Where DeviceID Like 'HID%'",
        "Select * From Win32_PnPEntity Where DeviceID Like 'BTH%'",
        "Select * From Win32_PnPEntity Where DeviceID Like 'SWD\\MMDEVAPI%'",
        "Select * From Win32_PnPEntity Where DeviceID Like '{2F2B7B01%'"
    )
    foreach($usbQuery in $usbQueries){
        foreach($u in @(Get-Wmi $Computer 'root\cimv2' $usbQuery $User $Password)){
            $id = Clean $u.DeviceID
            if([string]::IsNullOrWhiteSpace($id)){ continue }
            if($usbSeen.ContainsKey($id)){ continue }
            $usbSeen[$id] = $true
            if($u.Description -notin $usbSkipDescriptions){
                Add-Fields 'usb' @($u.Caption,$u.Description,$u.Manufacturer,$u.DeviceID) 'USB Devices'
            }
        }
    }
    Echo 'Hard Disk Info'
    foreach($d in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_DiskDrive' $User $Password)){ Add-Fields 'harddrive' @($d.Caption,$d.Index,$d.InterfaceType,$d.Manufacturer,$d.Model,$d.Partitions,$d.SCSIBus,$d.SCSILogicalUnit,$d.SCSIPort,(SafeDivInt ([double]$d.Size) 1048576),$d.PNPDeviceID,$d.Status,'No Results') 'Hard Disk Info' }
    Echo 'Partition Info'
    foreach($ld in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_LogicalDisk where DriveType = 3' $User $Password)){
        $bl=First(Get-Wmi $Computer 'root\CIMV2\Security\MicrosoftVolumeEncryption' ("Select ProtectionStatus from Win32_EncryptableVolume where driveletter='{0}'" -f $ld.DeviceID) $User $Password)
        $size=SafeDivInt ([double]$ld.Size) 1048576; $free=SafeDivInt ([double]$ld.FreeSpace) 1048576; $used=$size-$free; $pct=if($size){[math]::Round(($used/$size)*100,0)}else{0}
        $bootable=''; $bootPartition=''; $deviceId=''; $diskIndex=''; $partIndex=''; $primary=''; $partType=''
        $driveId = ([string]$ld.DeviceID).Replace('\','\\')
        foreach($dp in @(Get-Wmi $Computer 'root\cimv2' ("ASSOCIATORS OF {Win32_LogicalDisk.DeviceID='{0}'} WHERE AssocClass=Win32_LogicalDiskToPartition" -f $driveId) $User $Password)){
            $bootable = BoolText $dp.Bootable; $bootPartition = BoolText $dp.BootPartition; $deviceId = $dp.DeviceID; $diskIndex = $dp.DiskIndex; $partIndex = $dp.Index; $primary = BoolText $dp.PrimaryPartition; $partType = $dp.Type
        }
        if([string]::IsNullOrWhiteSpace($deviceId)){ $deviceId=$ld.DeviceID }
        Echo( ' Type: ' + $partType )
        Echo( ' Bitlocker: ' + $bl.ProtectionStatus )
        Add-Fields 'partition' @($bootable,$bootPartition,$deviceId,$diskIndex,$partIndex,$pct,$primary,$ld.Caption,$ld.FileSystem,$free,$size,$ld.VolumeName,$used,$partType,$bl.ProtectionStatus) 'Partition Info'
    }
    Echo 'SCSI Cards'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_SCSIController' $User $Password)){ Add-Fields 'scsi_controller' @($x.Caption,$x.DeviceID,$x.Manufacturer) 'SCSI Cards' }
    Echo 'SCSI Devices'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_SCSIControllerDevice' $User $Password)){ Add-Fields 'scsi_device' @($x.Antecedent,$x.Dependent) 'SCSI Devices' }
    Echo 'Optical Drive Info'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_CDROMDrive' $User $Password)){ Add-Fields 'optical' @($x.Caption,$x.Drive,$x.DeviceID) 'Optical Drive Info' }
    Echo 'Floppy Drives'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * FROM Win32_FloppyDrive' $User $Password)){ Add-Fields 'floppy' @($x.Description,$x.Manufacturer,$x.Caption,$x.DeviceID) 'Floppy Drives' }
    Echo 'Tape Drive Info'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_TapeDrive' $User $Password)){ Add-Fields 'tape' @($x.Caption,$x.Description,$x.Manufacturer,$x.Name,$x.DeviceID) 'Tape Drive Info' }
    Echo 'Keyboard Info'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_Keyboard' $User $Password)){ Add-Fields 'keyboard' @($x.Caption,$x.Description,$x.DeviceID) 'Keyboard Info' }
    Echo 'Battery Info'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_Battery' $User $Password)){ Add-Fields 'battery' @($x.Description,$x.DeviceID) 'Battery Info' }
    Echo 'Modem Info'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_POTSModem' $User $Password)){ Add-Fields 'modem' @($x.AttachedTo,$x.CountrySelected,$x.Description,$x.DeviceType,$x.DeviceID) 'Modem Info' }
    Echo 'Mouse Info'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_PointingDevice' $User $Password)){ Add-Fields 'mouse' @($x.Description,$x.NumberOfButtons,$x.DeviceID,(MouseType $x.PointingType),(MousePort $x.DeviceInterface)) 'Mouse Info' }
    Echo 'Sound Card Info'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_SoundDevice' $User $Password)){ Add-Fields 'sound' @($x.Manufacturer,$x.Name,$x.DeviceID) 'Sound Card Info' }
    Echo 'Printer Info'
    $printerQueryOk = $false
    $printers = @()
    try {
        if(Is-LocalComputer $Computer){
            $printers = @(Get-WmiObject -ComputerName $Computer -Namespace 'root\cimv2' -Query 'Select * from Win32_Printer' -ErrorAction Stop)
        } elseif($User -and $Password){
            $sec = ConvertTo-SecureString $Password -AsPlainText -Force
            $cred = New-Object System.Management.Automation.PSCredential($User,$sec)
            try {
                $printers = @(Get-WmiObject -ComputerName $Computer -Namespace 'root\cimv2' -Query 'Select * from Win32_Printer' -Credential $cred -Impersonation Impersonate -Authentication PacketPrivacy -ErrorAction Stop)
            } catch {
                # Some older targets reject an explicit authentication level although normal WMI works.
                $printers = @(Get-WmiObject -ComputerName $Computer -Namespace 'root\cimv2' -Query 'Select * from Win32_Printer' -Credential $cred -ErrorAction Stop)
            }
        } else {
            try {
                $printers = @(Get-WmiObject -ComputerName $Computer -Namespace 'root\cimv2' -Query 'Select * from Win32_Printer' -Impersonation Impersonate -Authentication PacketPrivacy -ErrorAction Stop)
            } catch {
                $printers = @(Get-WmiObject -ComputerName $Computer -Namespace 'root\cimv2' -Query 'Select * from Win32_Printer' -ErrorAction Stop)
            }
        }
        $printerQueryOk = $true
    } catch {
        Echo ('Win32_Printer query failed for ' + $Computer + ': ' + $_.Exception.Message)
    }
    if($printerQueryOk){
        Add-Fields 'prnstate' @('complete') 'Printer Info'
        Echo ('Printer entries found: ' + $printers.Count)
        foreach($x in $printers){
            Add-Fields 'printer' @($x.Caption,(BoolText $x.Local),$x.PortName,(BoolText $x.Shared),$x.ShareName,$x.SystemName,$x.Location,$x.DriverName,$x.Comment) 'Printer Info'
        }
    } else {
        Echo 'Printer inventory NOT complete - printer data will not be replaced in database.'
    }
    Echo 'Share Info'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_Share' $User $Password)){ Add-Fields 'shares' @($x.Caption,$x.Name,$x.Path) 'Share Info' }
    Echo 'Mapped Drives Info'
    $mappedSeen = @{}
    foreach($sid in Enum-RegKeyEx $Computer 2147483651 '' '64'){
        if($sid -match '^(\.DEFAULT|S-1-5-18|S-1-5-19|S-1-5-20)' -or $sid -match '_Classes$'){ continue }
        $mapUser=$sid; try{$mapUser=(New-Object System.Security.Principal.SecurityIdentifier($sid)).Translate([System.Security.Principal.NTAccount]).Value}catch{}
        foreach($view in @('64','32')){
            foreach($drv in Enum-RegKeyEx $Computer 2147483651 "$sid\Network" $view){
                $rp=Get-RegValueEx $Computer 2147483651 "$sid\Network\$drv" 'RemotePath' $view
                $cu=Get-RegValueEx $Computer 2147483651 "$sid\Network\$drv" 'UserName' $view
                $key="$sid|$drv|$rp"
                if($rp -and -not $mappedSeen.ContainsKey($key)){ $mappedSeen[$key]=$true; Add-Fields 'mapped' @($drv,'',0,$rp,0,$mapUser,$cu) 'Mapped Drives Info' }
            }
        }
    }
    if([string]$cs.DomainRole -in @('4','5')){ Echo 'Bypassing Local Groups - This is a domain controller.' } else { Echo 'Local Groups Info'; foreach($g in @(Get-Wmi $Computer 'root\cimv2' ("Select * from Win32_Group where Domain = '{0}'" -f $system_name) $User $Password)){ $members=Get-LocalGroupMembersText $Computer $g.Name $User $Password; Add-Fields 'l_group' @($g.Description,$g.Name,$members,$g.SID) 'Local Groups Info' }; Echo 'Local Users Info'; foreach($u in @(Get-Wmi $Computer 'root\cimv2' ("Select * from Win32_UserAccount where Domain = '{0}'" -f $system_name) $User $Password)){ Add-Fields 'l_user' @($u.Description,(BoolText $u.Disabled),$u.FullName,$u.Name,(BoolText $u.PasswordChangeable),(BoolText $u.PasswordExpires),(BoolText $u.PasswordRequired),$u.SID,(BoolText $u.Lockout)) 'Local Users Info' } }
    Echo 'Scheduled Tasks Info'; foreach($t in @(Get-Wmi $Computer 'Root\Microsoft\Windows\TaskScheduler' 'SELECT * FROM MSFT_ScheduledTask' $User $Password)){ Add-Fields 'sched_task' @($t.TaskName,$t.Date,$t.TaskPath,'','','',$t.Author,'','',$t.State,'') 'Scheduled Tasks Info' }
    Echo 'System Environment Variables Info'; foreach($e in @(Get-Wmi $Computer 'root\cimv2' "Select * from Win32_Environment where username = '<SYSTEM>'" $User $Password)){ Add-Fields 'env_var' @($e.Name,$e.VariableValue) 'System Environment Variables Info' }
    Echo 'Event Logs Info'; foreach($l in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_NTEventLogFile' $User $Password)){ Add-Fields 'evt_log' @($l.LogFileName,$l.Name,(SafeDivInt ([double]$l.FileSize) 1024),(SafeDivInt ([double]$l.MaxFileSize) 1024),$l.OverWritePolicy) 'Event Logs Info' }
    Echo 'Ip Routes Info'; foreach($r in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_IP4RouteTable' $User $Password)){ Add-Fields 'ip_route' @($r.Destination,$r.Mask,$r.Metric1,$r.NextHop,$r.Protocol,$r.Type) 'Ip Routes Info' }
    Echo 'Pagefile Info'
    $pfAdded = $false
    foreach($pf in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_PageFile' $User $Password)){ Add-Fields 'pagefile' @($pf.Name,$pf.InitialSize,$pf.MaximumSize) 'Pagefile Info'; $pfAdded=$true }
    if(-not $pfAdded){ foreach($pf in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_PageFileSetting' $User $Password)){ Add-Fields 'pagefile' @($pf.Name,$pf.InitialSize,$pf.MaximumSize) 'Pagefile Info'; $pfAdded=$true } }
    if(-not $pfAdded){ foreach($pf in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_PageFileUsage' $User $Password)){ Add-Fields 'pagefile' @($pf.Name,$pf.AllocatedBaseSize,$pf.PeakUsage) 'Pagefile Info'; $pfAdded=$true } }
    Echo 'Motherboard Info'; $sockets=@(Get-Wmi $Computer 'root\cimv2' 'SELECT * FROM Win32_Processor' $User $Password | Select-Object -ExpandProperty SocketDesignation -Unique).Count; $arr=First(Get-Wmi $Computer 'root\cimv2' 'Select MemoryDevices FROM Win32_PhysicalMemoryArray' $User $Password); Add-Fields 'motherboard' @($bb.Manufacturer,$bb.Product,$sockets,$arr.MemoryDevices) 'Motherboard Info'
    Echo 'Onboard devices Info'; foreach($ob in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_OnBoardDevice' $User $Password)){ Add-Fields 'onboard' @($ob.Description,$ob.DeviceType) 'Onboard devices Info' }
    Echo 'AV - Security Center Settings'; $avFound=$false; foreach($ns in @('root\SecurityCenter2','root\SecurityCenter')){ foreach($av in @(Get-Wmi $Computer $ns 'Select * from AntiVirusProduct' $User $Password)){ $avFound=$true; $up=if($av.productUptoDate -or $av.ProductState -in @(266240,397568)){'True'}else{'False'}; Add-Fields 'system10' @($av.companyName,$av.displayName,$up,$av.versionNumber,$av.timestamp) 'AV - Security Center Settings' } }
    foreach($fw in @(Get-Wmi $Computer 'root\SecurityCenter2' 'Select * from FirewallProduct' $User $Password)){ Add-Fields 'system10' @(('Windows Firewall ' + (Clean $fw.displayName)),$fw.displayName,(BoolText $fw.enabled),$fw.versionNumber,'') 'AV - Security Center Settings' }

    $softwareAuditValue = ([string]$script:software_audit).Trim().ToLower()
    if(($softwareAuditValue -eq 'y') -or ($softwareAuditValue -eq 'yes') -or ($softwareAuditValue -eq 'true') -or ($softwareAuditValue -eq '1') -or ($softwareAuditValue -eq 'wahr')){
        Echo 'Software Windows QFE Fixes'; foreach($q in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_QuickFixEngineering' $User $Password)){ Add-Fields 'software' @((Clean ($q.Description + ' (' + $q.HotFixID + ')')),'1.0','','',$q.InstalledOn,'Microsoft','','',$q.Caption,'Hotfix') 'Software Windows QFE Fixes' }
        # Software inventory first: this is the large block that must match the old VBScript output.
        try { Add-AllInstalledSoftwareInventory $Computer } catch { Echo ('Software inventory error: ' + $_.Exception.Message) }
        try { Add-ModernApps $Computer } catch { Echo ('Modern apps inventory error: ' + $_.Exception.Message) }
        try { Echo 'Startup Programs'; foreach($st in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_StartupCommand' $User $Password)){ Add-Fields 'startup' @($st.Caption,$st.Command,$st.Description,$st.Location,$st.Name,$st.User) 'Startup Programs' } } catch { Echo ('Startup inventory error: ' + $_.Exception.Message) }
        try { Echo 'Services'; foreach($svc in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_Service' $User $Password)){ Add-Fields 'service' @($svc.Description,$svc.DisplayName,$svc.Name,$svc.PathName,(BoolText $svc.Started),$svc.StartMode,$svc.State,$svc.StartName) 'Services' } } catch { Echo ('Services inventory error: ' + $_.Exception.Message) }
        try { Add-BrowserAndFirewallSettings $Computer } catch { Echo ('Browser/firewall inventory error: ' + $_.Exception.Message) }
        Add-WindowsKeys $Computer
        Add-ODBC $Computer
        Add-AutoUpdate $Computer
    }
    Send-AuditResults
}
function Usage{
    Echo "Recognized audit.config named arguments:`r`n   strComputer strUser strPass non_ie_page online ie_form_page`r`n   software_audit monitor_detect printer_detect uuid_type verbose number_of_audits`r`n   local_domain domain_type audit_local_domain script_name input_file`r`nAdditional recognized named arguments:`r`n   /config_path:<path>   The complete path to an audit.config to use`r`n   /cmd_args_only        Do not use an audit.config. Only use named arguments"
    exit 0
}

$script:this_config_url = '%host_url%'
if($script:this_config_url.StartsWith('%')){ $script:this_config_url='http://localhost:888/openaudit/list_export_config.php' }
$script:script_timeout=1200
$script:random_order=$false
$script:form_total=''
$script:sScriptPath = Split-Path -Parent $PSCommandPath
if(-not $script:sScriptPath){$script:sScriptPath=(Get-Location).Path}
$script:script_prefix = [IO.Path]::GetFileNameWithoutExtension($PSCommandPath)
if($script:script_prefix -like '*.ps1'){$script:script_prefix=[IO.Path]::GetFileNameWithoutExtension($script:script_prefix)}
if($script:NamedArgs.ContainsKey('config_path')){ $script:this_config=$script:NamedArgs['config_path'] } else { $script:this_config=Join-Path $script:sScriptPath ($script:script_prefix + '.config') }
if(-not (Test-Path -LiteralPath $script:this_config) -and -not $script:NamedArgs.ContainsKey('cmd_args_only')){
    try { (New-Object Net.WebClient).DownloadString($script:this_config_url) | Set-Content -LiteralPath $script:this_config -Encoding ASCII } catch {}
}
if(Test-Path -LiteralPath $script:this_config){ Read-AuditConfig $script:this_config }
foreach($k in $script:NamedArgs.Keys){
    switch($k.ToUpperInvariant()){
        '?' { Usage }
        'HELP' { Usage }
        'CMD_ARGS_ONLY' {}
        'CONFIG_PATH' {}
        default { Set-Variable -Name $k -Scope Script -Value $script:NamedArgs[$k] }
    }
}
if($script:UnnamedArgs.Count -gt 0){$script:strComputer=$script:UnnamedArgs[0]}
if($script:UnnamedArgs.Count -gt 1){$script:strUser=$script:UnnamedArgs[1]}
if($script:UnnamedArgs.Count -gt 2){$script:strPass=$script:UnnamedArgs[2]}
if(-not $script:online){$script:online='yesxml'}
if(-not $script:software_audit){$script:software_audit='y'}
if(-not $script:uuid_type){$script:uuid_type='uuid'}
if(-not $script:verbose){$script:verbose='y'}

# An explicitly supplied computer always wins. If no computer was supplied,
# process input_file from audit.config. Only fall back to the local machine if
# neither a computer nor an input file is configured.
if(-not [string]::IsNullOrWhiteSpace([string]$script:strComputer)){
    Audit ([string]$script:strComputer).Trim() $script:strUser $script:strPass
}
elseif(-not [string]::IsNullOrWhiteSpace([string]$script:input_file)){
    $inputPath = [string]$script:input_file
    if(-not [IO.Path]::IsPathRooted($inputPath)){
        $inputPath = Join-Path $script:sScriptPath $inputPath
    }

    if(-not (Test-Path -LiteralPath $inputPath)){
        Write-Error "Input file not found: $inputPath"
        exit 1
    }

    foreach($computerLine in @(Get-Content -LiteralPath $inputPath -ErrorAction Stop)){
        $computer = ([string]$computerLine).Trim()
        if([string]::IsNullOrWhiteSpace($computer)){ continue }
        if($computer.StartsWith('#') -or $computer.StartsWith(';')){ continue }

        Echo "===== Starting audit target: $computer ====="
        Audit $computer $script:strUser $script:strPass
    }
}
else{
    Audit '.' $script:strUser $script:strPass
}
exit 0
