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
    $cs=First (Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_ComputerSystem' $User $Password)
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
    Echo 'Printer Info'; foreach($x in @(Get-Wmi $Computer 'root\cimv2' 'Select * from Win32_Printer' $User $Password)){ Add-Fields 'printer' @($x.Caption,(BoolText $x.Local),$x.PortName,(BoolText $x.Shared),$x.ShareName,$x.SystemName,$x.Location,$x.DriverName,$x.Comment) 'Printer Info' }
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
if(-not $script:strComputer){$script:strComputer='.'}
if(-not $script:online){$script:online='yesxml'}
if(-not $script:software_audit){$script:software_audit='y'}
if(-not $script:uuid_type){$script:uuid_type='uuid'}
if(-not $script:verbose){$script:verbose='y'}
Audit $script:strComputer $script:strUser $script:strPass
exit 0

<#
Original VBScript retained below as commented migration reference only. It is not executed.
'***********************************************************************************************
' Open Audit                   
' Software and Hardware Inventory 
' Outputs into MySQL / MariaDB
' (c) Open-Audit.org 2003-2024    
' Licensed under the GPL          
'***********************************************************************************************

this_config_url = "%host_url%"
if (left(this_config_url,1) = "%") then
	this_config_url = "http://localhost:888/openaudit/list_export_config.php"
end if
'
'
' The above line will magically change from %host_url% to the correct server URL
' when the audit is launched or downloaded from the OA web interface (without a pre-existing local config file),
' thus causing the script to download its config from the correct place. 
'

Dim verbose
Public online
Dim strComputer
Dim mysql
Dim input_file
Dim email_to
Dim email_from
Dim email_failed
Dim email_server

Dim email_port
Dim email_auth
Dim email_user_id
Dim email_user_pwd 
Dim email_use_ssl 
Dim email_timeout 

Dim audit_local_domain
Dim local_domain
Dim sql
Dim comment
Dim net_mac_uuid

' Set this to a suitable value to ensure we dont allow the script to hang. 
Dim script_timeout
script_timeout = 1200 ' 20 mins = 1200 seconds, adjust as necessary
' Used to randomise a domain scan, to keep the traffic down to a dull roar over slow links.
Dim random_order
random_order = false


'
' (AJH) Moved the file read-write-append constants to here, they were defined much later.
'
Const ForReading = 1, ForWriting = 2, ForAppending = 8 

form_total = ""

' If specified, the wbemConnectFlagUseMaxWait flag prevents from hanging indefinitely if the connection cannot be established
' using the ConnectServer method of the SWbemLocator object.
Const wbemConnectFlagUseMaxWait = 128

' Used by the OpenDSObject method of the "WinNT:" GetObject call to pass alternate credentials
Const ADS_SECURE_AUTHENTICATION = 1
Const ADS_USE_ENCRYPTION = 2

' Find out the name of this script, usually audit.vbs but it depends where we were called form.
full_script_name = WScript.ScriptFullName
' Strip off the .vbs and the path, so we can create files with the same suffix. 
' No point in creating or overwriting audit.config if we aren't called audit.vbs
script_prefix = Left(full_script_name,(InStrRev(full_script_name,".vbs")-1))
script_prefix = Right(script_prefix,(len(script_prefix) - (InStrRev(WScript.ScriptFullName,"\"))))
' We also need the Path
sScriptPath=Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName,"\"))

dim filesys
Set filesys = CreateObject("Scripting.FileSystemObject")

' Look for an audit.config from command line arg config_path first
If WScript.Arguments.Named.Exists("config_path") then
  If not filesys.FileExists(Wscript.Arguments.Named("config_path")) then
    Wscript.Echo "Cannot find config: " & Wscript.Arguments.Named("config_path")
    Wscript.Quit(1)
  End If
  this_config = Wscript.Arguments.Named("config_path")
Else
  this_config = sScriptPath & script_prefix & ".config"
End If

'this_config = "audit.config"
this_audit_log = sScriptPath & script_prefix & "_log.csv"
' keep_audit_log = "y"
'
' look for audit.config and use that, if it doesn't exist, grab it from 
' the web server, if we cant do that, then use the internal defaults. 
' Finally modify the defaults depending on any command line switches 
'
'
' First check to see if we have no config file, if so lets see if we can grab one from the server
'

If not filesys.FileExists(this_config) and not WScript.Arguments.Named.Exists("cmd_args_only") then
'wscript.echo("Creating new config")
'
' This section takes a look at the local audit.config, and if there is none, it makes one from the server URL 
' The idea is to allow us to throw the audit.vbs file to a browser and have it grab the config it needs.
' We should only need to set one thing, namely the URL from which we will grab the remainder of the config.
'

' Now we open the web page where the remote config lives
Set WshShell = WScript.CreateObject("WScript.Shell")

Set http = CreateObject("Microsoft.XmlHttp")
' ...and we grab it..
http.open "GET",this_config_url, FALSE
http.send ""
'
Set config_file = CreateObject("Scripting.FileSystemObject")
Set our_config = config_file.OpenTextFile( this_config, ForWriting, True)
'... and post it to our local config. 
our_config.write http.responseText
our_config.close
End If 
' End of web config script. 
'
 '(this is a good point to break if testing the config)
'wscript.Quit(0)
' Below calls the file audit_include.vbs to setup the variables.
' 
'sScriptPath=Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName,"\"))
'ExecuteGlobal CreateObject("Scripting.FileSystemObject").OpenTextFile(sScriptPath & this_config).ReadAll


' It's possible to have only command line arguments, so see if there's a config to read
If filesys.FileExists(this_config) then
ExecuteGlobal CreateObject("Scripting.FileSystemObject").OpenTextFile(this_config).ReadAll

'
' Once run, we can delete the config. Can any calling scripts can use the config up until we exit the toplevel script. Not sure???
'
' Delete our config if requested. 
'
if keep_this_config = "n" then 
Set config_file = CreateObject("Scripting.FileSystemObject")
Set our_config = config_file.OpenTextFile( this_config, ForWriting, True)
    our_config.close
    config_file.DeleteFile this_config
    end if
end if



For Each arg in WScript.Arguments.Named
  Select Case Ucase(arg) 
    Case "?","HELP" Usage              
    Case "CMD_ARGS_ONLY","CONFIG_PATH" ' Nothing to do with these here
    Case "STRCOMPUTER"               strComputer = Wscript.Arguments.Named(arg)
    Case "STRUSER"                       strUser = Wscript.Arguments.Named(arg)
    Case "STRPASS"                       strPass = Wscript.Arguments.Named(arg)
    Case "ONLINE"                         online = Wscript.Arguments.Named(arg)
    Case "NON_IE_PAGE"               non_ie_page = Wscript.Arguments.Named(arg)
    Case "IE_FORM_PAGE"             ie_form_page = Wscript.Arguments.Named(arg)
    Case "IE_AUTO_SUBMIT"         ie_auto_submit = Wscript.Arguments.Named(arg)
    Case "IE_VISIBLE"                 ie_visible = Wscript.Arguments.Named(arg)
    Case "IE_SUBMIT_VERBOSE"   ie_submit_verbose = Wscript.Arguments.Named(arg)
    Case "SOFTWARE_AUDIT"         software_audit = Wscript.Arguments.Named(arg)
    Case "MONITOR_DETECT"         monitor_detect = Wscript.Arguments.Named(arg)
    Case "PRINTER_DETECT"         printer_detect = Wscript.Arguments.Named(arg)
    Case "UUID_TYPE"                   uuid_type = Wscript.Arguments.Named(arg)
    Case "VERBOSE"                       verbose = Wscript.Arguments.Named(arg)
    Case "INPUT_FILE"                 input_file = Wscript.Arguments.Named(arg)
    Case "AUDIT_LOCAL_DOMAIN" audit_local_domain = Wscript.Arguments.Named(arg)
    Case "LOCAL_DOMAIN"             local_domain = Wscript.Arguments.Named(arg)
    Case "DOMAIN_TYPE"               domain_type = Wscript.Arguments.Named(arg)
    Case "SCRIPT_NAME"               script_name = Wscript.Arguments.Named(arg)
    Case "NUMBER_OF_AUDITS"     number_of_audits = Wscript.Arguments.Named(arg)
    Case Else
      Wscript.Echo "Unknown argument: " & arg
      WScript.Quit(1)
  End Select
next

' Keep support for unnamed arguments...

' If any command line args given - use the first one as strComputer
If Wscript.Arguments.Unnamed.Count > 0 Then
  strComputer = wscript.arguments.unnamed(0)
end if
If Wscript.Arguments.Unnamed.Count > 1 Then
  strUser = wscript.arguments.unnamed(1)
end if
If Wscript.Arguments.Unnamed.Count > 2 Then
  strPass = wscript.arguments.unnamed(2)
end if

if online = "p" then
  Dim oIE
  Dim bWaitforChoice
  Dim ItemChosen
  Set oIE = CreateObject("InternetExplorer.Application")
  oIE.Visible = True
  oIE.Fullscreen = False
  oIE.Toolbar = True
  oIE.Statusbar = False
  oIE.Navigate("about:blank")
  oIE.document.ParentWindow.resizeto 800,600
  oIE.document.WriteLn "<html>"
  oIE.document.WriteLn "<head>"
  oIE.document.WriteLn "<title>Open Audit - Audit Result</title>"
  oIE.document.WriteLn "<style type=""text/css"">"
  oIE.document.WriteLn "body {"
  oIE.document.WriteLn " font-family: verdana;"
  oIE.document.WriteLn " font-size: 9pt;"
  oIE.document.WriteLn "}"
  oIE.document.WriteLn "h1,h2 {"
  oIE.document.WriteLn " font-family: Trebuchet MS;"
  oIE.document.WriteLn "}"
  oIE.document.WriteLn ".content {"
  oIE.document.WriteLn " position: relative;"
  oIE.document.WriteLn " width: 600px;"
  oIE.document.WriteLn " min-width: 700px;"
  oIE.document.WriteLn " margin: 0 0px 10px 0px;"
  oIE.document.WriteLn " border: 1px solid black;"
  oIE.document.WriteLn " background-color: white;"
  oIE.document.WriteLn " padding: 10px;"
  oIE.document.WriteLn " z-index: 3;"
  oIE.document.WriteLn " font-family: verdana;"
  oIE.document.WriteLn " font-size: 9pt;"
  oIE.document.WriteLn "}"
  oIE.document.WriteLn "</style>"
  oIE.document.WriteLn "</head>"
  oIE.document.WriteLn "<body>"
end if


''''''''''''''''''''''''''''''''''''
' Uncomment the 3 sections below to   '
'  have the script ask for a PC    '
'  to audit (name or IP)           '
''''''''''''''''''''''''''''''''''''
'strAnswer = InputBox("PC to run audit on:", "Audit Script")
'Wscript.Echo "Input PC Name: " & strAnswer
'strComputer = strAnswer

'strAnswer = InputBox("PC User:", "Audit Script")
'Wscript.Echo "Input PC User Name: " & strAnswer
'strUser = strAnswer

'strAnswer = InputBox("PC User Password:", "Audit Script")
'Wscript.Echo "Input Password: " & strAnswer
'strPass = strAnswer





''''''''''''''''''''''''''''''''''''
' Check that softwarefiles.xml     '
'  is correct                      '
'                                  '
''''''''''''''''''''''''''''''''''''

if (software_audit = "y" and software_file_audit = "y") then
  set xmlDoc=CreateObject("Microsoft.XMLDOM")
  xmlDoc.async="false"
  xmlDoc.validateOnParse="true"
  xmlDoc.load("softwarefiles.xml")

  if (xmlDoc.parseError.errorCode <> 0) then
    Echo("Error Code: " & xmlDoc.parseError.errorCode)
    Echo("Error Reason: " & xmlDoc.parseError.reason)
    Echo("Error Line: " & xmlDoc.parseError.srcText)
    Echo("Error Line Number: " & xmlDoc.parseError.line) & vbnewline
    WScript.quit
  end if
end if

''''''''''''''''''''''''''''''''''''''''
' Don't change the settings below here '
''''''''''''''''''''''''''''''''''''''''
Const HKEY_CLASSES_ROOT  = &H80000000
Const HKEY_CURRENT_USER  = &H80000001
Const HKEY_LOCAL_MACHINE = &H80000002
Const HKEY_USERS         = &H80000003
'Const ForAppending = 8


'''''''''''''''''''''''''''''
' Clear Failed Audits File  '
'''''''''''''''''''''''''''''
' Check if this_audit_log exists, and create it if need be.
' 28th Dec 2007 (AJH) Changed default behaviour, we used to clear this at the start of every run.
' Currently this file will grow forever, even if we set keep_audit_log <> "y".
'
' This is in order to ensure we see results, even if we bomb spectacularly
' Previously we just assumed we had a good audit if we didn't fail. This included the situation where we started
' an audit, but it never completed. 
' Simply clearing the log at the start is not going to work, since this will clear it every time the script calls itself. 
' We must clear it after the email is sent. 
' 
' Now we log the start, finish or no connection.
' A start but no finish is also a failure, just sort the file by field 2 first, 1 second and it should show every 
' start and finish, any missing finishes mean disaster. 
'''''''''''''''''''''''''''''
if use_audit_log = "y" then 
    Set objFSO = CreateObject("Scripting.FileSystemObject")
    If objFSO.FileExists(this_audit_log) Then
    Set objFile = objFSO.OpenTextFile(this_audit_log, ForAppending)
'  objFile.WriteLine
      objFile.Close
    Else
    Set objFile = objFSO.CreateTextFile(this_audit_log, ForAppending)
'  objFile.WriteLine
    objFile.WriteLine "TIME,MACHINE,RESULT"
    objFile.Close
    End If
End If
''''''''''''''''''''''''''''''''''''''''''''
' Check Local system build number
''''''''''''''''''''''''''''''''''''''''''''
Set objLocalWMIService = GetObject("winmgmts:root\cimv2")
Set colItems = objLocalWMIService.ExecQuery("Select * From Win32_OperatingSystem",,48)
For Each objItem in colItems
   LocalSystemBuildNumber = objItem.BuildNumber
Next

'''''''''''''''''''''''''''''
' Process the manual input  '
'''''''''''''''''''''''''''''
if strComputer <> "" then
  if (IsConnectible(strComputer, "", "")  OR (strComputer = ".")) then
    thisresult = IsWMIConnectible(strComputer,strUser,strPass)
    if thisresult = False then
      if use_audit_log = "y" then 
        Set objFSO = CreateObject("Scripting.FileSystemObject")
        Set objFile = objFSO.OpenTextFile(this_audit_log, 8)
        objFile.WriteLine "" & Now & "," & strComputer & ",Unable to connect to WMI. Error ="  & Err.Number & "-" & Err.Description
        objFile.Close
      end if
    end if
    if thisresult = True then
      if use_audit_log = "y" then 
         Set objFSO = CreateObject("Scripting.FileSystemObject")
      Set objFile = objFSO.OpenTextFile(this_audit_log, 8)
      objFile.WriteLine "" & Now & "," & strComputer & ",Able to connect to WMI. "
      objFile.Close
    End If
    
    Echo("" & Now & "," & strComputer & " - Able to connect to WMI. ")
    
    ' wscript.sleep 10000
    if strUser <> "" and strPass <> "" then
    ' Username & Password provided - assume not a domain local PC.
      Echo("Username and password provided - therefore assuming NOT a local domain PC.")
      Set wmiLocator = CreateObject("WbemScripting.SWbemLocator")
      Set wmiNameSpace = wmiLocator.ConnectServer( strComputer, "root\default", strUser, strPass, "", "", wbemConnectFlagUseMaxWait)
      Set oReg = wmiNameSpace.Get("StdRegProv")
      Set objWMIService = wmiLocator.ConnectServer(strComputer, "root\cimv2",strUser,strPass, "", "", wbemConnectFlagUseMaxWait)
      objWMIService.Security_.ImpersonationLevel = 3
      Set objWMIService2 = wmiLocator.ConnectServer(strComputer, "\root\WMI",strUser,strPass, "", "", wbemConnectFlagUseMaxWait)
      objWMIService2.Security_.ImpersonationLevel = 3
    end if
    if strUser = "" and strPass = "" then
    ' No Username & Password provided - assume a domain local PC
      Echo("No username and password provided - therefore assuming local domain PC.")
      Set oReg=GetObject("winmgmts:{impersonationLevel=impersonate}!\\" & strComputer & "\root\default:StdRegProv")
      Set objWMIService = GetObject("winmgmts:\\" & strComputer & "\root\cimv2")
	  Set objWMIService2 = GetObject("winmgmts:\\" & strComputer & "\root\WMI")
    end if
    if use_audit_log = "y" then 
        Set objFSO = CreateObject("Scripting.FileSystemObject")
        Set objFile = objFSO.OpenTextFile(this_audit_log, 8)
        objFile.WriteLine "" & Now & "," & strComputer  & ",Started"
        objFile.Close
    end if
    Audit (strComputer)
        if use_audit_log = "y" then 
            Set objFSO = CreateObject("Scripting.FileSystemObject")
            Set objFile = objFSO.OpenTextFile(this_audit_log, 8)
            objFile.WriteLine "" & Now & "," & strComputer  & ",Completed"
            objFile.Close
        end if
    end if
    
  else
    Echo(strComputer & " not available.")
    if use_audit_log = "y" then 
        Set objFSO = CreateObject("Scripting.FileSystemObject")
        Set objFile = objFSO.OpenTextFile(this_audit_log, 8)
        objFile.WriteLine "" & Now & "," & strComputer  & ",Failed not available" 
        objFile.Close
    end if
  end if
  wscript.quit
end if


''''''''''''''''''''''''''''''''''''''''
' Audit the local domain, if requested '
''''''''''''''''''''''''''''''''''''''''

' Read current script PID
' Skipping if local system is older than WinXp
' Check Build Number: Win2k-->2195, Win98-->2222, WinME-->3000, 
if (CInt(LocalSystemBuildNumber) > 2222 and not LocalSystemBuildNumber = "3000") then
  Set colItems = objLocalWMIService.ExecQuery("Select * From Win32_Process",,48)
  For Each objItem in colItems
    If InStr (objItem.CommandLine, WScript.ScriptName) <> 0 Then
      current_PID = objItem.ProcessId
    End If
  Next
End If

if audit_local_domain = "y" then
  domain_type = LCase(domain_type)
  if domain_type <> "nt" then 
  domain_type = "ldap"
  end if
  if domain_type = "nt" then
      comparray = GetDomainComputers(local_domain)
  end if
  if domain_type = "ldap" then
  Const ADS_SCOPE_SUBTREE = 2
  Set objConnection = CreateObject("ADODB.Connection")
  Set objCommand =   CreateObject("ADODB.Command")
  objConnection.Provider = "ADsDSOObject"
  objConnection.Open "Active Directory Provider"
  Set objCOmmand.ActiveConnection = objConnection
  objCommand.CommandText = "Select Name, Location from '" & local_domain & "' Where objectClass='computer'"
  objCommand.Properties("Page Size") = 1000
  objCommand.Properties("Searchscope") = ADS_SCOPE_SUBTREE
  objCommand.Properties("Sort On") = "name"
  Set objRecordSet = objCommand.Execute
  objRecordSet.MoveFirst
  
  totcomp = objRecordset.recordcount -1
  Redim comparray(totcomp) ' set array to computer count

  Do Until objRecordSet.EOF
    On Error Resume Next
    strComputer = objRecordSet.Fields("Name").Value
    comparray(count) = strComputer ' Feed computers into array
    count = count + 1
    Echo("Computer Name from ldap: " & strComputer)
    objRecordSet.MoveNext
   Loop
' Randomise the scan if asked. 
   if random_order <> false then
       Call ArrayShuffle(comparray)
       Echo ("Computer List Randomised")
   end if   
   
   num_running = HowMany
   Echo("Number of systems retrieved from ldap: " & (Ubound(comparray) +1))
   Echo("--------------")
  end if 
    For i = 0 To Ubound(comparray)
   '  For i = 118 To 128
    while num_running > number_of_audits
      Echo("Processes running (" & num_running & ") greater than number wanted (" & number_of_audits & ")")
      Echo("Therefore - sleeping for 4 seconds.")
      wscript.Sleep 4000
      num_running = HowMany
    wend
    if comparray(i) <> "" then
      Echo((i +1) & " of " & (Ubound(comparray) +1))
      Echo("Processes running: " & num_running)
      Echo("Next System: " & comparray(i))
      Echo("--------------")
      ' command1 = "cscript " & script_name & " " & comparray(i) ' wait forever. 
      'command1 = "cscript //T:" & script_timeout & " " & script_name & " " & comparray(i)
       command1 = "cscript //Nologo " & script_name & " " & comparray(i)
      set sh1=WScript.CreateObject("WScript.Shell")
      sh1.Run command1, 6, False
      set sh1 = nothing
      num_running = HowMany
    end if
  Next

  Echo("Domain Audit Completed")
  'Wscript.Quit
end if

'''''''''''''''''''''''''''''''''''
' Read the text file if requested '
'  and audit PCs within - line    '
'  by line                        '
'''''''''''''''''''''''''''''''''''
On Error Resume Next
if input_file <> "" then
  Set objFSO = CreateObject("Scripting.FileSystemObject")
  Set objTextFileReading = objFSO.OpenTextFile(input_file, 1)
  objTextFileReading.ReadAll
  dimarray = objTextFileReading.Line - 1
  Redim comparray(dimarray)
  Redim userarray(dimarray)
  Redim passarray(dimarray)
  objTextFileReading.close
  Set objTextFileReading = objFSO.OpenTextFile(input_file, 1)
  Do Until objTextFileReading.AtEndOfStream
    strString = objTextFileReading.ReadLine
    strSplit = split(strString, ",")
      comparray(count) = strSplit(0)
      userarray(count) = strSplit(1)
      passarray(count) = strSplit(2)
      count = count + 1
  Loop
  num_running = HowMany
  Echo("File " & input_file & " read into array.")
  Echo("Number of systems retrieved from file: " & (Ubound(comparray) +1))
  Echo("--------------")
  For i = 0 To Ubound(comparray)
    while num_running > number_of_audits
      Echo("Processes running (" & num_running & ") greater than number wanted (" & number_of_audits & ")")
      Echo("Therefore - sleeping for 4 seconds.")
      wscript.Sleep 4000
      num_running = HowMany
    wend
    if comparray(i) <> "" then
      Echo((i +1) & " of " & (Ubound(comparray) +1))
      Echo("Processes running: " & num_running)
      Echo("Next System: " & comparray(i))
      Echo("--------------")
      command1 = "cscript //Nologo " & script_name & " " & comparray(i) & " " & userarray(i) & " " & passarray(i)
      set sh1=WScript.CreateObject("WScript.Shell")
      sh1.Run command1, 6, False
      set sh1 = nothing
      num_running = HowMany
    end if
  Next
  Echo("PC list Audit Completed")
  'Wscript.Quit
end if

' Give the spawned scripts time to fail before emailing
' Use 60000ms = 60s = 1min
' We can't wait forever, so any audit taking >5 mins will fail to appear in the email.

i = 0
Do Until (i = 5 or end_of_audits = "true")
   end_of_audits = "true"
   num_running = HowMany - 1
   Set colItems = objLocalWMIService.ExecQuery("Select * From Win32_Process Where ProcessId <> '" & current_PID & "'",,48)
   For Each objItem in colItems
      If InStr (objItem.CommandLine, "cscript.exe") <> 0 Then
        end_of_audits = "false"
      End If
   Next
   If end_of_audits = "false"  Then
     Echo("Waiting 1 min for remaining " & num_running & " scripts to complete.")
     wscript.Sleep 60000
   End if
   i = i + 1
Loop

'if verbose = "y" then
'  wscript.echo "Some systems may have failed to audit. See " & this_audit_log & " for details."
'end if

''''''''''''''''''''''''''''''''''
' Send an email of audits results '
' if requested               '
''''''''''''''''''''''''''''''''''
If send_email Then
  if use_audit_log = "y" then 
    ' Open the file this_audit_log, read the contents and store in this_audit_log variable
    Set objFile = objFSO.OpenTextFile(this_audit_log, 1)
    email_failed = objFile.ReadAll
    objFile.Close
    end if
  if email_failed <> "" then
    On Error Resume Next
    Echo("Some systems may have failed to audit. See " & this_audit_log & " for details.")
    if use_audit_log = "y" then 
        Set objShell = WScript.CreateObject("WScript.Shell")
        this_folder = objShell.CurrentDirectory
        this_file = this_folder & "\" & this_audit_log
    end if
    Set objEmail = CreateObject("CDO.Message")
    objEmail.From = email_from
    objEmail.To   = email_to
    'objEmail.Sender   = email_sender
    objEmail.Subject = "Open-AudIT - Audit Results."
    objEmail.Textbody =  email_failed
    objEmail.Configuration.Fields.Item ("https://schemas.microsoft.com/cdo/configuration/sendusing") = 2
    objEmail.Configuration.Fields.Item ("https://schemas.microsoft.com/cdo/configuration/smtpserver") = email_server
    objEmail.Configuration.Fields.Item ("https://schemas.microsoft.com/cdo/configuration/smtpserverport") = email_port
    objEmail.Configuration.Fields.Item ("https://schemas.microsoft.com/cdo/configuration/smtpauthenticate") = email_auth
    objEmail.Configuration.Fields.Item ("https://schemas.microsoft.com/cdo/configuration/sendusername") = email_user_id
    objEmail.Configuration.Fields.Item ("https://schemas.microsoft.com/cdo/configuration/sendpassword") = email_user_pwd
    objEmail.Configuration.Fields.Item ("https://schemas.microsoft.com/cdo/configuration/smtpusessl") = email_use_ssl
    objEmail.Configuration.Fields.Item ("https://schemas.microsoft.com/cdo/configuration/smtpconnectiontimeout") = email_timeout
    objEmail.Configuration.Fields.Update
    if use_audit_log = "y" then 
        objEmail.AddAttachment this_file
    end if
    objEmail.Send
    if Err.Number <> 0 then
      Echo("Error sending email: " & Err.Description)
    else
      Echo("Email sent.")
    end if
    Err.Clear
  end if
End if 'send_email = "true"

'
' Now we can remove the log... if requested, this will ensure we keep things tidy.  
'
'

if keep_audit_log = "n" then 
    if use_audit_log = "y" then
        Set log_file = CreateObject("Scripting.FileSystemObject")
        Set our_log = config_file.OpenTextFile( this_audit_log, ForWriting, True)
        our_log.close
        log_file.DeleteFile this_audit_log
    end if
end if


'
' Nothing more to do so we quit
' Exit the script

wscript.quit

	Function nInstr(strSource,strFind,occurrence)
	 StartPosition = 1
	 For Findoccurrence = 1 to occurrence
	  FoundPosition = Instr(StartPosition,strSource,strFind)
	  StartPosition = FoundPosition + 1
	  If FoundPosition = 0 Then
	   Exit For
	  End If
	 Next
	 nInstr = FoundPosition
	End Function

function Audit(strComputer)
start_time = Timer
dim dt : dt = Now()
timestamp = Year(dt) & Right("0" & Month(dt),2) & Right("0" & Day(dt),2) & Right("0" & Hour(dt),2) & Right("0" & Minute(dt),2) & Right("0" & Second(dt),2)

'''''''''''''''''''''''''''
'   Who are we auditing   '
'''''''''''''''''''''''''''
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_ComputerSystem",,48)
For Each objItem in colItems
   system_name = objItem.Name
   domain = objItem.Domain
Next
Set colItems = objWMIService.ExecQuery("Select IPAddress from Win32_networkadapterconfiguration WHERE IPEnabled='TRUE'",,48)
For Each objItem in colItems
   system_ip = objItem.IPAddress(0)
Next
Set wshNetwork = WScript.CreateObject( "WScript.Network" )
user_name = wshNetwork.userName
Set colItems = objWMIService.ExecQuery("Select * from Win32_ComputerSystemProduct",,48)
For Each objItem in colItems
   system_id_number = clean(objItem.IdentifyingNumber)
   system_vendor = clean(objItem.Vendor)
   system_uuid = objItem.UUID
Next
Echo("System UUID: " & system_uuid)
Echo("System ID Num: " & clean(objItem.IdentifyingNumber))

''''''''''''''''''''''
' Neue system ID intelligent zusammenbasteln auch bei geklonten Maschinen
''''''''''''''''''''''

' 1. UUID aus Win32_ComputerSystemProduct
On Error Resume Next
Set colItems = objWMIService.ExecQuery("SELECT UUID FROM Win32_ComputerSystemProduct")
For Each objItem In colItems
    strUUID = Trim(objItem.UUID)
Next

' 2. BIOS Serial Number
Set colItems = objWMIService.ExecQuery("SELECT SerialNumber FROM Win32_BIOS")
For Each objItem In colItems
    strBIOS = Trim(objItem.SerialNumber)
Next

' 3. Baseboard Serial Number
Set colItems = objWMIService.ExecQuery("SELECT SerialNumber FROM Win32_BaseBoard")
For Each objItem In colItems
    strBoard = Trim(objItem.SerialNumber)
Next

' 4. MAC-Adresse der ersten aktiven Netzwerkkarte
Set colItems = objWMIService.ExecQuery("SELECT MACAddress, IPEnabled FROM Win32_NetworkAdapterConfiguration WHERE MACAddress IS NOT NULL AND IPEnabled = True")
For Each objItem In colItems
    If Len(strMAC) = 0 Then strMAC = Trim(objItem.MACAddress)
Next
On Error GoTo 0


' Systeminformationen sammeln für uuid 2026-01
Set colItems = objWMIService.ExecQuery("Select * from Win32_ComputerSystem",,48)
For Each objItem in colItems
   system_model = clean(objItem.Model)
   system_name = clean(objItem.Name)
   system_num_processors = clean(objItem.NumberOfProcessors)
   system_part_of_domain = clean(objItem.PartOfDomain)
   system_primary_owner_name = clean(objItem.PrimaryOwnerName)
   domain_role = clean(objItem.DomainRole)

   ' VM-Erkennung vorbereiten
   manufacturer = LCase(Trim(objItem.Manufacturer))
   model = LCase(Trim(objItem.Model))
Next

If manufacturer = "microsoft corporation" And model = "virtual machine" Then
    system_system_type = "VMH"
ElseIf InStr(model, "vmware") > 0 Or InStr(manufacturer, "vmware") > 0 Then
    system_system_type = "VMV"
End If

' Zusammensetzen der ID (MAC nur bei VMH oder VMV)
systemID = strUUID & "-" & strBIOS & "-" & strBoard

If system_system_type = "VMH" Or system_system_type = "VMV" Then
    If Len(strMAC) > 0 Then
        systemID = systemID & "-" & strMAC
    End If
End If

system_uuid = systemID

' Ausgabe
Echo("Unique System Identifikation:" & systemID)

Echo("PC name supplied: " & strComputer)
Echo("PC name from WMI: " & system_name)
full_system_name = LCase(system_name) & "." & LCase(domain)
Echo("User executing this script: " & user_name)
ns_ip = NSlookup(system_name)
Echo("IP: " & ns_ip)
if online = "p" then
  oIE.document.WriteLn "<h1>Open Audit</h1><br />"
end if

'''''''''''''''''''''''''''''''''
'' Double check WMI is working  '
'''''''''''''''''''''''''''''''''
'if ((UCase(strComputer) <> system_name) AND (strComputer <> ".") AND (strComputer <> full_system_name) AND (strComputer <> ns_ip) AND (strComputer <> system_ip)) then
' email_failed = email_failed & strComputer & ", " & VBcrlf
'  ie = nothing
'  exit function
' end if

'''''''''''''''''''''''''''''''''''''''
'   Setup for Offline file creation   '
'''''''''''''''''''''''''''''''''''''''
if online = "n" then
   Set objFSO = CreateObject("Scripting.FileSystemObject")
   Set objTextFile = objFSO.OpenTextFile (system_name & ".txt", ForAppending, True)
end if

'''''''''''''''''''''''''''
'   Network Information   '
'''''''''''''''''''''''''''

dim net_mac, net_ip_enabled, net_index, net_service_name, net_description, net_dhcp_enabled, net_dhcp_server
dim net_dhcp_lease_obtained, net_dhcp_lease_expires, net_dns_host_name, net_dns_server(2), net_dns_domain
dim net_dns_domain_suffix(2), net_dns_domain_reg_enabled, net_dns_domain_full_reg_enabled, net_ip(2)
dim net_ip_subnet(2), net_wins_primary, net_wins_secondary, net_wins_lmhosts_enabled, net_netbios_options
dim net_adapter_type, net_manufacturer, net_connection_id, net_connection_status, net_speed, net_gateway(2)
dim net_gateway_metric(2), net_ip_metric, net_ip_address, net_ip_mask, is_installed

comment = "Network Info"
Echo(comment)
On Error Resume Next

Set colItems = objWMIService.ExecQuery("Select * from Win32_NetworkAdapterConfiguration " _
   & "WHERE ServiceName<>'' AND ServiceName<>'AsyncMac' " _
   & "AND ServiceName<>'VMnetx' AND ServiceName<>'VMnetadapter' " _
   & "AND ServiceName<>'Rasl2tp' AND ServiceName<>'msloop' " _
   & "AND ServiceName<>'PptpMiniport' AND ServiceName<>'Raspti' " _
   & "AND ServiceName<>'NDISWan' AND ServiceName<>'NdisWan4' AND ServiceName<>'RasPppoe' " _
   & "AND ServiceName<>'tunmp' AND ServiceName<>'tunnel' AND ServiceName<>'VPCNetS2' " _
   & "AND ServiceName<>'RasSstp' AND ServiceName<>'NdisIP' AND Description<>'PPP Adapter.'",,48)
For Each objItem in colItems
   net_index = objItem.Index
   net_description = objItem.Description
   is_installed = "false"
   Set colItems2 = objWMIService.ExecQuery("Select * from Win32_NetworkAdapter WHERE Index='" & net_index & "'",,48)
   For Each objItem2 in colItems2
      if (not isnull(objItem2.Manufacturer) or objItem2.Manufacturer <> "") then
        ' Found a  currently installed NIC
        is_installed = "true"
        net_manufacturer = objItem2.Manufacturer
        net_adapter_type = objItem2.AdapterType
        net_connection_id = objItem2.NetConnectionId
        net_connection_status = objItem2.NetConnectionStatus
        if net_connection_status = "2" then
          ' Found a connected NIC: detecting link speed
          Set colItems3 = objWMIService2.ExecQuery("Select * from MSNdis_LinkSpeed ",,48)
          For Each objItem3 in colItems3
            if objItem3.InstanceName = net_description then net_speed = objItem3.NdisLinkSpeed  end if
          Next
        end if
        ' Find Driver version
        net_pnp_device_id = objItem2.PNPDeviceId
        Set colItems4 = objWMIService.ExecQuery("Select * from Win32_PNPSignedDriver WHERE DeviceClass = 'NET'",,48)
        For Each objItem4 in colItems4
          if objItem4.DeviceID = net_pnp_device_id then
            net_driver_provider = objItem4.DriverProviderName
            net_driver_version = objItem4.DriverVersion
            net_driver_date = Left(objItem4.DriverDate, 8)
          end if
        Next
      end if 'not isnull(objItem2.Manufacturer) or objItem2.Manufacturer <> ""
   Next
   if is_installed = "true" then
     net_mac = objItem.MACAddress
     net_ip_enabled = objItem.IPEnabled
     net_service_name = objItem.ServiceName
     net_dhcp_enabled = objItem.DHCPEnabled
     net_dhcp_server = objItem.DHCPServer
     net_dhcp_lease_obtained = Clean(objItem.DHCPLeaseObtained)
     net_dhcp_lease_expires = Clean(objItem.DHCPLeaseExpires)
     net_dns_host_name = objItem.DNSHostName
     For i = LBound(objItem.DNSServerSearchOrder) to UBound(objItem.DNSServerSearchOrder)
        if i > 2 then exit for End if
        net_dns_server(i) = objItem.DNSServerSearchOrder(i)
     Next
     net_dns_domain = objItem.DNSDomain
     For i = LBound(objItem.DNSDomainSuffixSearchOrder) to UBound(objItem.DNSDomainSuffixSearchOrder)
        if i > 2 then exit for End if
        net_dns_domain_suffix(i) = objItem.DNSDomainSuffixSearchOrder(i)
     Next
     net_dns_domain_reg_enabled = objItem.DomainDNSRegistrationEnabled
     net_dns_domain_full_reg_enabled = objItem.FullDNSRegistrationEnabled
     For i = LBound(objItem.IPAddress) to UBound(objItem.IPAddress)
        if i > 2 then exit for End if
        net_ip(i) = objItem.IPAddress(i)
     Next
     For i = LBound(objItem.IPSubnet) to UBound(objItem.IPSubnet)
        if i > 2 then exit for End if
        net_ip_subnet(i) = objItem.IPSubnet(i)
     Next
     net_wins_primary = objItem.WINSPrimaryServer
     net_wins_secondary = objItem.WINSSecondaryServer
     net_wins_secondary = objItem.WINSSecondaryServer
     net_wins_lmhosts_enabled = objItem.WINSEnableLMHostsLookup
     net_netbios_options = objItem.TcpipNetbiosOptions
     For i = LBound(objItem.DefaultIPGateway) to UBound(objItem.DefaultIPGateway)
        if i > 2 then exit for End if
        net_gateway(i) = objItem.DefaultIPGateway(i)
     Next
     For i = LBound(objItem.GatewayCostMetric) to UBound(objItem.GatewayCostMetric)
        if i > 2 then exit for End if
        net_gateway_metric(i) = objItem.GatewayCostMetric(i)
     Next
     net_ip_metric = objItem.IpConnectionMetric
     
     ' Below is to account for a NULL in various items or converting values
     if (isnull(net_mac) or net_mac = "") then net_mac = "unknown" End if
     if (isnull(net_ip_enabled) or net_ip_enabled = "") then net_ip_enabled = "unknown" End if
     if (isnull(net_description) or net_description = "") then net_description = "unknown" End if
     if (isnull(net_dhcp_enabled) or net_dhcp_enabled = "") then net_dhcp_enabled = "false" End if
     if (isnull(net_dhcp_server) or net_dhcp_server = "") then net_dhcp_server = "none" End if
     if (isnull(net_dns_host_name) or net_dns_host_name = "") then net_dns_host_name = "none" End if
     if (isnull(net_dns_domain) or net_dns_domain = "") then net_dns_domain = "none" End if
     if (isnull(net_dns_domain_reg_enabled) or net_dns_domain_reg_enabled = "") then net_dns_domain_reg_enabled = "false" End if
     if (isnull(net_dns_domain_full_reg_enabled) or net_dns_domain_full_reg_enabled = "") then net_dns_domain_full_reg_enabled = "false" End if
     if (isnull(net_wins_primary) or net_wins_primary = "") then net_wins_primary = "none" End if
     if (isnull(net_wins_secondary) or net_wins_secondary = "") then net_wins_secondary = "none" End if
     if (isnull(net_wins_lmhosts_enabled) or net_wins_lmhosts_enabled = "") then net_wins_lmhosts_enabled = "false"  End if
     Select Case net_netbios_options
        Case "0" net_netbios_options = "defaults"
        Case "1" net_netbios_options = "enabled"
        Case "2" net_netbios_options = "disabled"
        Case Else net_netbios_options = "unknown"
     End Select
     if (isnull(net_adapter_type) or net_adapter_type = "") then net_adapter_type = "unknown" End if
     if (isnull(net_connection_id) or net_connection_id = "") then net_connection_id = "unknown" End if
     Select Case net_connection_status
        Case "0"  net_connection_status = "Disconnected"
        Case "1"  net_connection_status = "Connecting"
        Case "2"  net_connection_status = "Connected"
        Case "3"  net_connection_status = "Disconnecting"
        Case "4"  net_connection_status = "Hardware not present"
        Case "5"  net_connection_status = "Hardware disabled"
        Case "6"  net_connection_status = "Hardware malfunction"
        Case "7"  net_connection_status = "Media disconnected"
        Case "8"  net_connection_status = "Authenticating"
        Case "9"  net_connection_status = "Authentication succeeded"
        Case "10" net_connection_status = "Authentication failed"
        Case "11" net_connection_status = "Invalid address"
        Case "12" net_connection_status = "Credentials required"
        Case Else net_connection_status = "unknown"
     End Select
     if (isnull(net_speed) or net_speed = "") then
       net_speed = "unknown"
     else  net_speed = int(net_speed)/10000 End if
     if (isnull(net_ip_metric) or net_ip_metric = "") then net_ip_metric = "unknown" End if
     For i = 0 to 2
        if (isnull(net_dns_server(i)) or net_dns_server(i) = "") then net_dns_server(i) = "none" End if
        if (isnull(net_dns_domain_suffix(i)) or net_dns_domain_suffix(i) = "") then net_dns_domain_suffix(i) = "none" End if
        if (isnull(net_ip(i)) or net_ip(i) = "") then net_ip(i) = "0.0.0.0" End if
        if (isnull(net_ip_subnet(i)) or net_ip_subnet(i) = "") then net_ip_subnet(i) = "none" End if
        if (isnull(net_gateway(i)) or net_gateway(i) = "") then net_gateway(i) = "none" End if
        if (isnull(net_gateway_metric(i)) or net_gateway_metric(i) = "") then net_gateway_metric(i) = "none" End if
     Next

     ' IP Address are padded with zeros so they sort properly
     MyIP = Split(net_ip(0), ".", -1, 1)
     if Not (MyIP(0) ="169" And MyIP(1) ="254") then
       MyIP(0) = right("000" & MyIP(0),3)
       MyIP(1) = right("000" & MyIP(1),3)
       MyIP(2) = right("000" & MyIP(2),3)
       MyIP(3) = right("000" & MyIP(3),3)
       net_ip(0) = MyIP(0) & "." & MyIP(1) & "." & MyIP(2) & "." & MyIP(3)
       ' The first detected IP address / Subnet mask become the system IP/Mask
       if (net_ip(0) <> "000.000.000.000" and net_ip_address = "") then
         net_ip_address = net_ip(0)
         net_ip_mask = net_ip_subnet(0)
       elseif net_ip(0) = "000.000.000.000" then net_ip(0) = "none" end if
     end if
     MyIP = Split(net_ip(1), ".", -1, 1)
     if Not (MyIP(0) ="169" And MyIP(1) ="254") then
       MyIP(0) = right("000" & MyIP(0),3)
       MyIP(1) = right("000" & MyIP(1),3)
       MyIP(2) = right("000" & MyIP(2),3)
       MyIP(3) = right("000" & MyIP(3),3)
       net_ip(1) = MyIP(0) & "." & MyIP(1) & "." & MyIP(2) & "." & MyIP(3)
       if net_ip(1) = "000.000.000.000" then net_ip(1) = "none" end if
     end if
     MyIP = Split(net_ip(2), ".", -1, 1)
     if Not (MyIP(0) ="169" And MyIP(1) ="254") then
       MyIP(0) = right("000" & MyIP(0),3)
       MyIP(1) = right("000" & MyIP(1),3)
       MyIP(2) = right("000" & MyIP(2),3)
       MyIP(3) = right("000" & MyIP(3),3)
       net_ip(2) = MyIP(0) & "." & MyIP(1) & "." & MyIP(2) & "." & MyIP(3)
       if net_ip(2) = "000.000.000.000" then net_ip(2) = "none" end if
     end if

     if net_dhcp_server <> "255.255.255.255" then
       form_input = "network^^^" & net_mac                    & "^^^" & net_description                 & "^^^" & net_dhcp_enabled         & "^^^" _
                                 & net_dhcp_server            & "^^^" & net_dns_host_name               & "^^^" & net_dns_server(0)        & "^^^" _
                                 & net_dns_server(1)          & "^^^" & net_ip(0)                       & "^^^" & net_ip_subnet(0)         & "^^^" _
                                 & net_wins_primary           & "^^^" & net_wins_secondary              & "^^^" & net_adapter_type         & "^^^" _
                                 & net_manufacturer           & "^^^" & net_gateway(0)                  & "^^^" & net_ip_enabled           & "^^^" _
                                 & net_index                  & "^^^" & net_service_name                & "^^^" & net_dhcp_lease_obtained  & "^^^" _
                                 & net_dhcp_lease_expires     & "^^^" & net_dns_server(2)               & "^^^" & net_dns_domain           & "^^^" _
                                 & net_dns_domain_suffix(0)   & "^^^" & net_dns_domain_suffix(1)        & "^^^" & net_dns_domain_suffix(2) & "^^^" _
                                 & net_dns_domain_reg_enabled & "^^^" & net_dns_domain_full_reg_enabled & "^^^" & net_ip(1)                & "^^^" _
                                 & net_ip_subnet(1)           & "^^^" & net_ip(2)                       & "^^^" & net_ip_subnet(2)         & "^^^" _
                                 & net_wins_lmhosts_enabled   & "^^^" & net_netbios_options             & "^^^" & net_gateway_metric(0)    & "^^^" _   
                                 & net_gateway(1)             & "^^^" & net_gateway_metric(1)           & "^^^" & net_gateway(2)           & "^^^" _
                                 & net_gateway_metric(2)      & "^^^" & net_ip_metric                   & "^^^" & net_connection_id        & "^^^" _ 
                                 & net_connection_status      & "^^^" & net_speed                       & "^^^" & net_driver_provider      & "^^^" _
                                 & net_driver_version         & "^^^" & net_driver_date                 & "^^^" 
       entry form_input,comment,objTextFile,oAdd,oComment
       form_input = ""
       erase net_dns_server
       erase net_dns_domain_suffix
       erase net_ip
       erase net_ip_subnet
       erase net_gateway
       erase net_gateway_metric
       ' The first valid MAC Address becomes the MAC_UUID
       if (net_mac <> "unknown" and net_mac_uuid = "") then net_mac_uuid = net_mac end if
     end if
   end if 'is_installed = "true"
Next

On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_ComputerSystem",,48)
For Each objItem in colItems
   net_domain = objItem.Domain
   net_user_name = objItem.UserName
Next

' Get domain NetBIOS name from domain DNS name
domain_dn="DC=" & Replace(net_domain,".",",DC=")
Set oTranslate = CreateObject("NameTranslate")
hr = oTranslate.Init (3, "")
hr = oTranslate.Set (1, domain_dn)
domain_nb = oTranslate.Get(3)
domain_nb = Left(domain_nb,Len(domain_nb)-1)

On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_NTDomain WHERE DomainName='" & domain_nb & "'",,48)
For Each objItem in colItems
  net_client_site_name = objItem.ClientSiteName
  net_domain_controller_address = objItem.DomainControllerAddress
  net_domain_controller_name = objItem.DomainControllerName
Next


If isnull(net_ip_address) Then net_ip_address = "" End If

' Changes to system user detection - ensures domain is prepended to username and additional support for Vista - 17/04/2009	[Nick Brown]
If isnull(net_domain) Then
	oReg.GetStringValue HKEY_LOCAL_MACHINE, "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon", "DefaultDomainName", net_domain
	If isnull(net_domain) Then net_domain = "" End If
End If

If isnull(net_user_name) Then
	oReg.GetStringValue HKEY_LOCAL_MACHINE, "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon", "DefaultUserName", net_user_name
  If isnull(net_user_name) Then
		oReg.GetStringValue HKEY_LOCAL_MACHINE, "SOFTWARE\Microsoft\Windows\CurrentVersion\Authentication\LogonUI", "LastLoggedOnUser", net_user_name
		If isnull(net_user_name) Then
			oReg.GetStringValue HKEY_LOCAL_MACHINE, "SOFTWARE\Microsoft\Windows\CurrentVersion\Authentication\LogonUI", "LastLoggedOnSAMUser", net_user_name
			If isnull(net_user_name) Then	net_user_name = ""
		End if
	Else
		If len(net_domain) > 0 Then net_user_name = net_domain & "\" & net_user_name
	End If
End If

if isnull(net_client_site_name) then net_client_site_name = "" end if
if isnull(net_domain_controller_address) then net_domain_controller_address = "" end if
if isnull(net_domain_controller_name) then net_domain_controller_name = "" end if

form_input = "system01^^^" & clean(net_ip_address) & "^^^" & clean(net_domain) _
                       & "^^^" & clean(Replace(net_user_name,"", "")) & "^^^" & clean(net_client_site_name) _
                       & "^^^" & clean(Replace(net_domain_controller_address, "\\", "")) & "^^^" & clean(Replace(net_domain_controller_name, "\\", "")) & "^^^"
entry form_input,comment,objTextFile,oAdd,oComment
form_input = ""

'''''''''''''''''=
' Make the UUID '
'''''''''''''''''
if uuid_type = "uuid" then
  ' Do nothing - system_uuid is the uuid already
end if

if uuid_type = "mac" then
  if net_mac_uuid <> "" then system_uuid = net_mac_uuid end if
end if

if uuid_type = "name" then
  if (system_name + "." + net_domain) <> "." then system_uuid = system_name + "." + net_domain end if
end if

' Defaults below here account for oddities
if ((isnull(system_uuid) OR system_uuid = "") AND (system_model <> "") AND (system_id_number <> "")) then system_uuid = system_model + "." + system_id_number end if
if  (isnull(system_uuid) OR system_uuid = "" OR system_uuid = ".") then system_uuid = system_name + "." + net_domain end if
if system_uuid = "00000000-0000-0000-0000-000000000000" then system_uuid = system_name + "." + domain end if
if system_uuid = "FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF" then system_uuid = system_name + "." + domain end if

form_input = ""
form_input = "audit^^^" & system_name & "^^^" & timestamp & "^^^" & system_uuid & "^^^" & user_name & "^^^" & ie_submit_verbose & "^^^" & software_audit & "^^^"
entry form_input,comment,objTextFile,oAdd,oComment

''''''''''''''''''''''''''''''''''''''''''''''''''''
'   System Information  & Timezone & TPM-Chip-Data '
''''''''''''''''''''''''''''''''''''''''''''''''''''
comment = "System Information"
Echo(comment)

On Error Resume Next

' Arbeitsspeicher ermitteln
Set colItems = objWMIService.ExecQuery("Select * from Win32_LogicalMemoryConfiguration",,48)
mem_count = 0
For Each objItem in colItems
   mem_count = mem_count + objItem.Capacity
Next

If mem_count > 0 Then
   mem_size = Int(mem_count /1024 /1024)
Else
   Set colItems = objWMIService.ExecQuery("Select * from Win32_LogicalMemoryConfiguration",,48)
   For Each objItem in colItems
      mem_size = objItem.TotalPhysicalMemory
   Next
   If IsEmpty(mem_size) Then
       Set colItems = objWMIService.ExecQuery("Select * from Win32_OperatingSystem",,48)
       For Each objItem in colItems
         mem_size = objItem.TotalVisibleMemorySize
       Next
   End If
   mem_size = Int(mem_size /1024)
End If

' Systeminformationen sammeln
Set colItems = objWMIService.ExecQuery("Select * from Win32_ComputerSystem",,48)
For Each objItem in colItems
   system_model = clean(objItem.Model)
   system_name = clean(objItem.Name)
   system_num_processors = clean(objItem.NumberOfProcessors)
   system_part_of_domain = clean(objItem.PartOfDomain)
   system_primary_owner_name = clean(objItem.PrimaryOwnerName)
   domain_role = clean(objItem.DomainRole)

   ' VM-Erkennung vorbereiten
   manufacturer = LCase(Trim(objItem.Manufacturer))
   model = LCase(Trim(objItem.Model))
Next

' Domain Role in Text umwandeln
If domain_role = "0" Then domain_role_text = "Standalone Workstation" End If
If domain_role = "1" Then domain_role_text = "Workstation" End If
If domain_role = "2" Then domain_role_text = "Standalone Server" End If
If domain_role = "3" Then domain_role_text = "Member Server" End If
If domain_role = "4" Then domain_role_text = "Backup Domain Controller" End If
If domain_role = "5" Then domain_role_text = "Primary Domain Controller" End If

' === VM-Erkennung ===
If manufacturer = "microsoft corporation" And model = "virtual machine" Then
    system_system_type = "VMH"
ElseIf InStr(model, "vmware") > 0 Or InStr(manufacturer, "vmware") > 0 Then
    system_system_type = "VMV"
Else
    ' ChassisType nur verwenden, wenn keine VM erkannt wurde
    Set colItems = objWMIService.ExecQuery("Select * from Win32_SystemEnclosure",,48)
    For Each objItem in colItems
       system_system_type = Join(objItem.ChassisTypes, ",")
    Next

    ' ChassisType in Klartext umwandeln
    If system_system_type = "1" Then system_system_type = "Other" End If
    If system_system_type = "2" Then system_system_type = "Unknown" End If
    If system_system_type = "3" Then system_system_type = "Desktop" End If
    If system_system_type = "4" Then system_system_type = "Low Profile Desktop" End If
    If system_system_type = "5" Then system_system_type = "Pizza Box" End If
    If system_system_type = "6" Then system_system_type = "Mini Tower" End If
    If system_system_type = "7" Then system_system_type = "Tower" End If
    If system_system_type = "8" Then system_system_type = "Portable" End If
    If system_system_type = "9" Then system_system_type = "Laptop" End If
    If system_system_type = "10" Then system_system_type = "Notebook" End If
    If system_system_type = "11" Then system_system_type = "Hand Held" End If
    If system_system_type = "12" Then system_system_type = "Docking Station" End If
    If system_system_type = "13" Then system_system_type = "All in One" End If
    If system_system_type = "14" Then system_system_type = "Sub Notebook" End If
    If system_system_type = "15" Then system_system_type = "Space-Saving" End If
    If system_system_type = "16" Then system_system_type = "Lunch Box" End If
    If system_system_type = "17" Then system_system_type = "Main System Chassis" End If
    If system_system_type = "18" Then system_system_type = "Expansion Chassis" End If
    If system_system_type = "19" Then system_system_type = "SubChassis" End If
    If system_system_type = "20" Then system_system_type = "Bus Expansion Chassis" End If
    If system_system_type = "21" Then system_system_type = "Peripheral Chassis" End If
    If system_system_type = "22" Then system_system_type = "Storage Chassis" End If
    If system_system_type = "23" Then system_system_type = "Rack Mount Chassis" End If
    If system_system_type = "24" Then system_system_type = "Sealed-Case PC" End If
    If system_system_type = "25" Then system_system_type = "Multi-system chassis" End If
    If system_system_type = "26" Then system_system_type = "Compact PCI" End If
    If system_system_type = "27" Then system_system_type = "Advanced TCA" End If
    If system_system_type = "28" Then system_system_type = "Blade" End If
    If system_system_type = "29" Then system_system_type = "Blade Enclosure" End If
    If system_system_type = "30" Then system_system_type = "Tablet" End If
    If system_system_type = "31" Then system_system_type = "Convertible" End If
    If system_system_type = "32" Then system_system_type = "Detachable" End If
    If system_system_type = "33" Then system_system_type = "IoT Gateway" End If
    If system_system_type = "34" Then system_system_type = "Embedded PC" End If
    If system_system_type = "35" Then system_system_type = "Mini PC" End If
    If system_system_type = "36" Then system_system_type = "Stick PC" End If
End If

' Zeitzoneninformationen
Set colItems = objWMIService.ExecQuery("Select * from Win32_TimeZone",,48)
For Each objItem in colItems
  tm_zone = clean(objItem.Caption)
  tm_daylight = clean(objItem.DaylightName)
Next

' Virtuelle und logische Prozessoren
Set colItems = objWMIService.ExecQuery("Select * from Win32_Processor",,48)
For Each objItem in colItems
   system_vcpu = clean(objItem.NumberOfCores)
   system_lcpu = clean(objItem.NumberOfLogicalProcessors)
Next


' TPM auslesen (nur als admin)
comment = "TPM auslesen..."
Echo(comment)
Set wmi1 = GetObject("winmgmts:{impersonationLevel=impersonate,authenticationLevel=pktPrivacy}!root\cimv2\Security\MicrosoftTPM")
set TPM = wmi1.ExecQuery("SELECT * FROM Win32_Tpm")
Set TPM_Import = TPM.ItemIndex(0)
system_tpmver = TPM_Import.SpecVersion
tpm_init = TPM_Import.IsEnabled_InitialValue
tpm_password = TPM_Import.IsOwned_InitialValue
Echo("TPM Version: " & system_tpmver)

form_input = "system02^^^" & trim(system_model) & "^^^" & system_name _
                  & "^^^" & system_num_processors & "^^^" & system_part_of_domain _
                  & "^^^" & system_primary_owner_name & "^^^" & system_system_type _
                  & "^^^" & mem_size & "^^^" & system_id_number _
                  & "^^^" & trim(system_vendor) & "^^^" & domain_role_text _
                  & "^^^" & tm_zone & "^^^" & tm_daylight & "^^^" & system_vcpu & "^^^" & system_lcpu _
				  & "^^^" & system_tpmver & "^^^" & tpm_init & "^^^" & tpm_password & "^^^"

entry form_input,comment,objTextFile,oAdd,oComment
form_input = ""

'''''''''''''''''''''''''''
'   Windows Information   '
'''''''''''''''''''''''''''
comment = "Windows Info"
Echo(comment)
On Error Resume Next

Set colItems = objWMIService.ExecQuery("Select * from Win32_OperatingSystem",,48)
For Each objItem in colItems
OSName = objItem.Caption

  OSArch = "32-bit"
'// begin addition for 64bit discovery
   if instr(Ucase(objItem.Caption),"X64") then OSArch = "64-bit"
   'super hack here, MS doesn't provide osarchitecture
   oReg.GetStringValue HKEY_LOCAL_MACHINE, "SOFTWARE\Wow6432Node\Microsoft\Windows NT\CurrentVersion\Winlogon", "Shell", win_shell
   if ( Len(Trim(win_shell)) ) then OSArch = "64-bit"   
'// end addition for 64bit discovery

if objItem.OSType = "16" then
     OSName = "Microsoft Windows 95"
   end if
   if objItem.OSType = "17" then
     OSName = "Microsoft Windows 98"
     if Instr(objItem.Name, "|") then
        OSName = Left(objItem.Name, Instr(objItem.Name, "|") - 1)
     else
        OSName = objItem.Name
     end if
   end if
   If InStr(objItem.OtherTypeDescription, "R2") Then
     OSName = OSName & " R2"
   End If
   OSInstall = objItem.InstallDate
   OSInstall = Left(OSInstall, 8)
   OSInstallYear = Left(OSInstall, 4)
   OSInstallMonth = Mid(OSInstall, 5, 2)
   OSInstallDay = Right(OSInstall, 2)
   OSInstall = OSInstallYear & "/" & OSInstallMonth & "/" & OSInstallDay
   OSType = objItem.OSType
   ServicePack = objItem.ServicePackMajorVersion
   OSLang = objItem.OSLanguage
   SystemBuildNumber = objItem.BuildNumber
   sys_version = objItem.Version
   system_description = clean(objItem.Description)
   OSCaption = objItem.Caption
   RegUser = clean(objItem.RegisteredUser)
   WinDir = clean(objItem.WindowsDirectory)
   RegOrg = clean(objItem.Organization)
   Country = objItem.CountryCode
   SerNum = objItem.SerialNumber
   OSSerPack = objItem.ServicePackMajorVersion & "." & objItem.ServicePackMinorVersion
   boot_device = clean(objItem.BootDevice)
   build_number = clean(objItem.BuildNumber)
   Version = objItem.Version
   LastBoot = Left(objItem.LastBootUpTime,InStr(objItem.LastBootUpTime,".") - 1)
Next
form_input = "system03^^^" & boot_device        & "^^^" & build_number & "^^^" & OSType  & "^^^" & OSName & "^^^" & Country _
                   & "^^^" & system_description & "^^^" & OSInstall    & "^^^" & RegOrg  & "^^^" & OSLang & "^^^" & RegUser _
                   & "^^^" & SerNum             & "^^^" & OSSerPack    & "^^^" & Version & "^^^" & WinDir & "^^^" & LastBoot _
                   & "^^^" & OSArch             & "^^^"
entry form_input,comment,objTextFile,oAdd,oComment
form_input = ""

if online = "p" then
    oIE.document.WriteLn "<div id=""content"">"
    oIE.document.WriteLn "<table border=""0"" cellpadding=""2"" cellspacing=""0"" class=""content"">"
    oIE.document.WriteLn "<tr><td colspan=""2""><b>Network Information</b></td></tr>"
    oIE.document.WriteLn "<tr><td width=""250"">System Name: </td><td>" & system_name & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Description: </td><td>" & system_description & "</td></tr>"
    oIE.document.WriteLn "<tr><td>MAC Address: </td><td>" & net_mac & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>IP Address: </td><td> " & net_ip_address & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Subnet: </td><td>" & net_ip_mask & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>DHCP Enabled: </td><td>" & net_dhcp_enabled & "</td></tr>"
    oIE.document.WriteLn "<tr><td>DHCP Server: </td><td>" & net_dhcp_server & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>WINS Server: </td><td>" & net_wins_primary & "</td></tr>"
    oIE.document.WriteLn "<tr><td>DNS Server: </td><td>" & net_dns_server & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>NIC Manufacturer: </td><td>" & net_manufacturer & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Description: </td><td>" & net_description & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Part of Domain: </td><td>" & system_part_of_domain & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Domain Role: </td><td>" & domain_role_text & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Domain: </td><td>" & net_domain & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Domain Site Name: </td><td>" & net_client_site_name & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Domain Controller Address: </td><td>" & Replace(net_domain_controller_address, "\\", "") & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Domain Controller Name: </td><td>" & Replace(net_domain_controller_name, "\\", "") & "</td></tr>"
    oIE.document.WriteLn "</table>"
    oIE.document.WriteLn "</div>"
    oIE.document.WriteLn "<br />"
    oIE.document.WriteLn "<div id=""content"">"
    oIE.document.WriteLn "<table border=""0"" cellpadding=""2"" cellspacing=""0"" class=""content"">"
    oIE.document.WriteLn "<tr><td colspan=""2""><b>System Information</b></td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>User Name: </td><td>" & Replace(net_user_name, "\\", "\") & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Date Audited: </td><td>" & date & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Time Zone: </td><td>" & tm_zone & "</td></tr>"
    oIE.document.WriteLn "<tr><td width=""250"">Registered Owner: </td><td>" & system_primary_owner_name & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>UUID: </td><td>" & system_uuid & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Model: </td><td>" & system_model & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Serial: </td><td>" & system_id_number & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Manufacturer: </td><td>" & trim(system_vendor) & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Chassis: </td><td>" & system_system_type & "</td></tr>"
    oIE.document.WriteLn "</table></div>"
    oIE.document.WriteLn "<br />"
    oIE.document.WriteLn "<div id=""content"">"
    oIE.document.WriteLn "<table border=""0"" cellpadding=""2"" cellspacing=""0"" class=""content"">"
    oIE.document.WriteLn "<tr><td colspan=""2""><b>Windows Information</b></td></tr>"
    oIE.document.WriteLn "<tr><td>OS Name: </td><td>" & OSName & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>OS Install Date: </td><td>" & OSInstall & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Registered User: </td><td>" & RegUser & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Registered Organisation: </td><td>" & RegOrg & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Country: </td><td>" & Country & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Language: </td><td>" & OSLang & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Serial Number: </td><td>" & SerNum & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Service Pack: </td><td>" & OSSerPack & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Windows Directory: </td><td>" & Country & "</td></tr>"
    oIE.document.WriteLn "</table></div>"
    oIE.document.WriteLn "<br style=""page-break-before:always;"" />"
    oIE.document.WriteLn "<div id=""content"">"
    oIE.document.WriteLn "<table border=""0"" cellpadding=""2"" cellspacing=""0"" class=""content"">"
    oIE.document.WriteLn "<tr><td colspan=""2""><b>Hardware</b></td></tr>"
end if

'''''''''''''''''''''''''''
'   Bios Information      '
'''''''''''''''''''''''''''
comment = "Bios Info"
Echo(comment)
On Error Resume Next

Set colSMBIOS = objWMIService.ExecQuery ("Select * from Win32_SystemEnclosure",,48)
For Each objSMBIOS in colSMBIOS
  if bios_asset = "" then
    bios_asset = objSMBIOS.SMBIOSAssetTag
  end if
Next

Set colItems = objWMIService.ExecQuery("Select * from Win32_BIOS",,48)
For Each objItem in colItems
   form_input = "bios^^^" & clean(objItem.Description) _
                     & "^^^" & clean(objItem.Manufacturer) _
                     & "^^^" & clean(objItem.SerialNumber) _
                     & "^^^" & clean(objItem.SMBIOSBIOSVersion) _
                     & "^^^" & clean(objItem.Version) _
                     & "^^^" & clean(bios_asset) & "^^^"
  entry form_input,comment,objTextFile,oAdd,oComment
  form_input = ""
  if online = "p" then
    oIE.document.WriteLn "<tr><td>BIOS Manufacturer: </td><td>" & clean(objItem.Manufacturer) & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>BIOS Version: </td><td>" & clean(objItem.Version) & "</td></tr>"
  end if
Next

'''''''''''''''''''''''''''
'   Processor Information '
'''''''''''''''''''''''''''
comment = "Processor Info"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_Processor",,48)
'count = 0
For Each objItem in colItems
  'count = count + 1
  'if count > int(system_num_processors) then
     'Exit For
  'end if
  Select Case clean(objItem.UpgradeMethod)
    Case "1"      cpu_socket = "Other"
    Case "2"      cpu_socket = "Unknown"
    Case "3"      cpu_socket = "Daughter Board"
    Case "4"      cpu_socket = "ZIF Socket"
    Case "5"      cpu_socket = "Replacement or Piggy Back"
    Case "6"      cpu_socket = "None"
    Case "7"      cpu_socket = "LIF Socket"
    Case "8"      cpu_socket = "Slot 1"
    Case "9"      cpu_socket = "Slot 2"
    Case "10"     cpu_socket = "370 Pin Socket"
    Case "11"     cpu_socket = "Slot A"
    Case "12"     cpu_socket = "Slot M"
    Case "13"     cpu_socket = "Socket 423"
    Case "14"     cpu_socket = "Socket A (462)"
    Case "15"     cpu_socket = "Socket 478"
    Case "16"     cpu_socket = "Socket 754"
    Case "17"     cpu_socket = "Socket 940"
    Case "18"     cpu_socket = "Socket 939"
    Case Default  cpu_socket = "Unknown"
  End Select
  cpu_socket =  cpu_socket & " (" & clean(objItem.SocketDesignation) & ")"
  form_input = "processor^^^" & clean(objItem.Caption)                  & "^^^" & clean(objItem.CurrentClockSpeed) & "^^^" _
                              & clean(objItem.CurrentVoltage)           & "^^^" & clean(objItem.DeviceID)          & "^^^" _
                              & clean(objItem.ExtClock)                 & "^^^" & clean(objItem.Manufacturer)      & "^^^" _
                              & clean(objItem.MaxClockSpeed)            & "^^^" & LTrim(clean(objItem.Name))       & "^^^" _
                              & clean(objItem.PowerManagementSupported) & "^^^" & cpu_socket                       & "^^^"
  entry form_input,comment,objTextFile,oAdd,oComment
  form_input = ""
  if online = "p" then
    oIE.document.WriteLn "<tr><td width=""250"">Processor: </td><td>" & clean(objItem.Caption) & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Processor Speed: </td><td>" & clean(objItem.MaxClockSpeed) & "</td></tr>"
  end if
Next

'''''''''''''''''''''''''''
'   Memory Information
'''''''''''''''''''''''''''
comment = "Memory Info"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select Capacity,DeviceLocator,FormFactor,MemoryType,TypeDetail,Speed,Tag FROM Win32_PhysicalMemory",,48)
mem_size = 0

For Each objItem in colItems
   Select Case objItem.FormFactor
     Case "1"  mem_formfactor = "Other"
     Case "2"  mem_formfactor = "SIP"
     Case "3"  mem_formfactor = "DIP"
     Case "4"  mem_formfactor = "ZIP"
     Case "5"  mem_formfactor = "SOJ"
     Case "6"  mem_formfactor = "Proprietary"
     Case "7"  mem_formfactor = "SIMM"
     Case "8"  mem_formfactor = "DIMM"
     Case "9"  mem_formfactor = "TSOP"
     Case "10" mem_formfactor = "PGA"
     Case "11" mem_formfactor = "RIMM"
     Case "12" mem_formfactor = "SODIMM"
     Case "13" mem_formfactor = "SRIMM"
     Case "14" mem_formfactor = "SMD"
     Case "15" mem_formfactor = "SSMP"
     Case "16" mem_formfactor = "QFP"
     Case "17" mem_formfactor = "TQFP"
     Case "18" mem_formfactor = "SOIC"
     Case "19" mem_formfactor = "LCC"
     Case "20" mem_formfactor = "PLCC"
     Case "21" mem_formfactor = "BGA"
     Case "22" mem_formfactor = "FPBGA"
     Case "23" mem_formfactor = "LGA"
     Case Else mem_formfactor = "Unknown"
   End Select

   Select Case objItem.MemoryType
     Case "1"  mem_detail = "Other"
     Case "2"  mem_detail = "DRAM"
     Case "3"  mem_detail = "Synchronous DRAM"
     Case "4"  mem_detail = "Cache DRAM"
     Case "5"  mem_detail = "EDO"
     Case "6"  mem_detail = "EDRAM"
     Case "7"  mem_detail = "VRAM"
     Case "8"  mem_detail = "SRAM"
     Case "9"  mem_detail = "RAM"
     Case "10" mem_detail = "ROM"
     Case "11" mem_detail = "Flash"
     Case "12" mem_detail = "EEPROM"
     Case "13" mem_detail = "FEPROM"
     Case "14" mem_detail = "EPROM"
     Case "15" mem_detail = "CDRAM"
     Case "16" mem_detail = "3DRAM"
     Case "17" mem_detail = "SDRAM"
     Case "18" mem_detail = "SGRAM"
     Case "19" mem_detail = "RDRAM"
     Case "20" mem_detail = "DDR"
     Case "21" mem_detail = "DDR-2"
     Case Else mem_detail = "Unknown"
   End Select

   Select Case objItem.TypeDetail
     Case "1"     mem_typedetail = "Reserved"
     Case "2"     mem_typedetail = "Other"
     Case "4"     mem_typedetail = "Unknown"
     Case "8"     mem_typedetail = "Fast-paged"
     Case "16"    mem_typedetail = "Static column"
     Case "32"    mem_typedetail = "Pseudo-static"
     Case "64"    mem_typedetail = "RAMBUS"
     Case "128"   mem_typedetail = "Synchronous"
     Case "256"   mem_typedetail = "CMOS"
     Case "512"   mem_typedetail = "EDO"
     Case "1024"  mem_typedetail = "Window DRAM"
     Case "2048"  mem_typedetail = "Cache DRAM"
     Case "4096"  mem_typedetail = "Non-volatile"
     Case Else    mem_typedetail = "Unknown"
   End Select

   mem_bank = objItem.DeviceLocator
   mem_size = int(objItem.Capacity /1024 /1024)
   mem_speed = clean(objItem.Speed)
   mem_tag = clean(objItem.Tag)

   form_input = "memory^^^" & mem_bank       & "^^^" & mem_formfactor & "^^^" & mem_detail & "^^^" _
                            & mem_typedetail & "^^^" & mem_size       & "^^^" & mem_speed  & "^^^" & mem_tag & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
     oIE.document.WriteLn "<tr><td>Memory Slot / Type: </td><td>" & mem_bank & " / " & mem_detail & "</td></tr>"
     oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Memory Size: </td><td>" & mem_size & "</td></tr>"
   end if
Next

If mem_size = 0 Then
   Set colItems = objWMIService.ExecQuery("Select * from Win32_LogicalMemoryConfiguration",,48)

   For Each objItem in colItems
      mem_size = objItem.TotalPhysicalMemory
   Next
   mem_size = int(mem_size /1024)

   form_input = "memory^^^" & "Unknown"  & "^^^" & "Unknown" & "^^^" & "Unknown" & "^^^" _
                            & "Unknown"  & "^^^" & mem_size  & "^^^" & "0"       & "^^^" & "Unknown" & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""

   if online = "p" then
      oIE.document.WriteLn "<tr><td>Memory Slot / Type: </td><td>" & mem_bank & " / " & mem_detail & "</td></tr>"
      oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Memory Size: </td><td>" & mem_size & "</td></tr>"
   end if
End If


'''''''''''''''''''''''''''''''''''''''''''''
'   Windows optional Features installed     '
'''''''''''''''''''''''''''''''''''''''''''''
comment = "Windows optional features installed"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("select * from Win32_OptionalFeature where installstate=1",,48)
For Each objItem in colItems
   form_input = "optionalfeatures^^^" & clean(objItem.Caption) & "^^^" & clean(objItem.Name) & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>OptFeature: </td><td>" & clean(objItem.Caption) & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Featurename: </td><td>" & clean(objItem.Name) & "</td></tr>"
   end if
Next



'''''''''''''''''''''''''''
'   Video Information     '
'''''''''''''''''''''''''''
comment = "Video Info"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_VideoController",,48)
For Each objItem in colItems
If (Instr(objItem.Caption, "vnc") = 0 AND Instr(objItem.Caption, "Innobec SideWindow") = 0) then
   LeftString = Left(objItem.DriverDate, 8)
   form_input = "video^^^" & int(objItem.AdapterRAM / 1024 / 1024)    & "^^^" _
                           & clean(objItem.Caption)                   & "^^^" & clean(objItem.CurrentHorizontalResolution) & "^^^" _
                           & clean(objItem.CurrentNumberOfColors)     & "^^^" & clean(objItem.CurrentRefreshRate)          & "^^^" _
                           & clean(objItem.CurrentVerticalResolution) & "^^^" & clean(objItem.Description)                 & "^^^" _
                           & Left(LeftString, 4) & "/" & Mid(LeftString, 5, 2) & "/" & Right(LeftString, 2)                & "^^^" _
                           & clean(objItem.DriverVersion)             & "^^^" & clean(objItem.MaxRefreshRate)              & "^^^" _
                           & clean(objItem.MinRefreshRate)            & "^^^" & clean(objItem.DeviceID)                    & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Video Card: </td><td>" & clean(objItem.Caption) & " mb</td></tr>"
    oIE.document.WriteLn "<tr><td>Video Memory: </td><td>" & int(objItem.AdapterRAM / 1024 / 1024) & "</td></tr>"
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Video Driver Date: </td><td>" & Left(LeftString, 4) & "/" & Mid(LeftString, 5, 2) & "/" & Right(LeftString, 2) & "</td></tr>"
    oIE.document.WriteLn "<tr><td>Video Driver Version: </td><td>" & clean(objItem.DriverVersion) & "</td></tr>"
   end if
end if
Next


'''''''''''''''''''''''
' Monitor Information '
'''''''''''''''''''''''
comment = "Monitor Info"
Echo(comment)
Dim strarrRawEDID()
Dim temp_model()
Dim temp_manuf()
intMonitorCount=0
Const HKLM = &H80000002
Const HKCU = &H80000001
Const HKUS = &H80000003
sBaseKey = "SYSTEM\CurrentControlSet\Enum\DISPLAY\"
iRC = oReg.EnumKey(HKLM, sBaseKey, arSubKeys)

For Each sKey In arSubKeys
  sBaseKey2 = sBaseKey & sKey & "\"
  iRC2 = oReg.EnumKey(HKLM, sBaseKey2, arSubKeys2)
  For Each sKey2 In arSubKeys2
    oReg.GetMultiStringValue HKLM, sBaseKey2 & sKey2 & "\", "HardwareID", sValue
    for tmpctr=0 to ubound(svalue)
      if lcase(left(svalue(tmpctr),8))="monitor\" then
        sBaseKey3 = sBaseKey2 & sKey2 & "\"
        iRC3 = oReg.EnumKey(HKLM, sBaseKey3, arSubKeys3)
            oReg.GetStringValue HKLM, sbasekey3, "DeviceDesc", tempmodel
            oReg.GetStringValue HKLM, sbasekey3, "Mfg", tempmanuf
			oReg.GetBinaryValue HKLM, sbasekey3 & "Device Parameters\", "EDID", arrintEDID
			strRawEDID = ""
			if VarType(arrintedid) <> 8204 then
             strRawEDID="EDID Not Available"
            Else
			  for each bytevalue in arrintedid
				if bytevalue>0 then
					strRawEDID = strRawEDID & chr(bytevalue)
				else 	
					strRawEDID = strRawEDID & "!"
				end if
			  Next
				 'echo strrawedid
			end If
			redim Preserve strarrRawEDID(intMonitorCount)
			strarrRawEDID(intMonitorCount)=strRawEDID
			redim Preserve temp_model(intMonitorCount)
			temp_model(intMonitorCount)=tempmodel
			redim Preserve temp_manuf(intMonitorCount)
			temp_manuf(intMonitorCount)=tempmanuf
            intMonitorCount=intMonitorCount+1
      end If
    Next
  Next
Next

dim arrMonitorInfo()
redim arrMonitorInfo(intMonitorCount-1,5)
dim location(3)

for tmpctr=0 to intMonitorCount-1
  if strarrRawEDID(tmpctr) <> "EDID Not Available" then
    location(0)=mid(strarrRawEDID(tmpctr),&H36+1,18)
    location(1)=mid(strarrRawEDID(tmpctr),&H48+1,18)
    location(2)=mid(strarrRawEDID(tmpctr),&H5a+1,18)
    location(3)=mid(strarrRawEDID(tmpctr),&H6c+1,18)
    strSerFind=chr(&H21) & chr(&H21) & chr(&H21) & chr(&Hff)
    strMdlFind=chr(&H21) & chr(&H21) & chr(&H21) & chr(&Hfc)
    intSerFoundAt=-1
    intMdlFoundAt=-1
    for findit = 0 to 3
      if instr(location(findit),strSerFind)>0 then
        intSerFoundAt=findit
      end If
      if instr(location(findit),strMdlFind)>0 then
        intMdlFoundAt=findit
      end If
    Next
    if intSerFoundAt<>-1 Then tmp=right(location(intSerFoundAt),14)
    if instr(tmp,chr(&H0a))>0 Then
      tmpser=trim(left(tmp,InStr(tmp,chr(&H0a))-1))
    Else
      tmpser=trim(tmp)
    end If
    if left(tmpser,1)=chr(&H21) Then
      tmpser=right(tmpser,len(tmpser)-1)
    Else
      tmpser="Serial Number Not Found in EDID data"
    end If

	if intMdlFoundAt<>-1 Then tmp=right(location(intMdlFoundAt),14)
    if instr(tmp,chr(&H0a))>0 Then
      tmpmdl=trim(left(tmp,InStr(tmp,chr(&H0a))-1))
    Else
      tmpmdl=trim(tmp)
    end If
    if left(tmpmdl,1)=chr(&H21) Then
      tmpmdl=right(tmpmdl,len(tmpmdl)-1)
    Else
      tmpmdl="Model Descriptor Not Found in EDID data"
    end If
	tmpmfgweek=asc(mid(strarrRawEDID(tmpctr),&H10+1,1))
    tmpmfgyear=(asc(mid(strarrRawEDID(tmpctr),&H11+1,1)))+1990
    tmpmdt=month(dateadd("ww",tmpmfgweek,datevalue("1/1/" & tmpmfgyear))) & "/" & tmpmfgyear
    tmpEDIDMajorVer=asc(mid(strarrRawEDID(tmpctr),&H12+1,1))
    tmpEDIDRev=asc(mid(strarrRawEDID(tmpctr),&H13+1,1))
    tmpver=chr(48+tmpEDIDMajorVer) & "." & chr(48+tmpEDIDRev)
    tmpEDIDMfg=mid(strarrRawEDID(tmpctr),&H08+1,2)
    Char1=33 : Char2=33 : Char3=33
    Byte1=asc(left(tmpEDIDMfg,1))
    Byte2=asc(right(tmpEDIDMfg,1))
    if (Byte1 and 64) > 0 then Char1=Char1+16
    if (Byte1 and 32) > 0 then Char1=Char1+8
    if (Byte1 and 16) > 0 then Char1=Char1+4
    if (Byte1 and 8) > 0 then Char1=Char1+2
    if (Byte1 and 4) > 0 then Char1=Char1+1
    if (Byte1 and 2) > 0 then Char2=Char2+16
    if (Byte1 and 1) > 0 then Char2=Char2+8
    if (Byte2 and 128) > 0 then Char2=Char2+4
    if (Byte2 and 64) > 0 then Char2=Char2+2
    if (Byte2 and 32) > 0 then Char2=Char2+1
    Char3=Char3+(Byte2 and 16)
    Char3=Char3+(Byte2 and 8)
    Char3=Char3+(Byte2 and 4)
    Char3=Char3+(Byte2 and 2)
    Char3=Char3+(Byte2 and 1)
    tmpmfg=chr(Char1+64) & chr(Char2+64) & chr(Char3+64)
    tmpEDIDDev1=hex(asc(mid(strarrRawEDID(tmpctr),&H0a+1,1)))
    tmpEDIDDev2=hex(asc(mid(strarrRawEDID(tmpctr),&H0b+1,1)))
    if len(tmpEDIDDev1)=1 then tmpEDIDDev1="0" & tmpEDIDDev1
    if len(tmpEDIDDev2)=1 then tmpEDIDDev2="0" & tmpEDIDDev2
    tmpdev=tmpEDIDDev2 & tmpEDIDDev1
    ' Accounts for model
    if (tmpmdl = "Model Descriptor Not Found in EDID data" AND temp_model(tmpctr) <> "") then tmpmdl = temp_model(tmpctr) end if
    if (tmpmdl = ""  AND temp_model(tmpctr) <> "") then tmpmdl = temp_model(tmpctr) end if
    if (tmpmdl = ""  AND temp_model(tmpctr) =  "") then tmpmdl = "Model Descriptor Not Found in EDID data"
    ' Account for serial
    if tmpser = "" then tmpser = "Serial Number Not Found in EDID data"
    ' Accounts for manufacturer
    if (temp_manuf <> "(Standard monitor types)" AND temp_manuf(tmpctr) <> "") then tmpmfg = temp_manuf(tmpctr)
    arrMonitorInfo(tmpctr,0)=tmpmfg
    arrMonitorInfo(tmpctr,1)=tmpdev
    arrMonitorInfo(tmpctr,2)=tmpmdt
    arrMonitorInfo(tmpctr,3)=tmpser
    arrMonitorInfo(tmpctr,4)=tmpmdl
    arrMonitorInfo(tmpctr,5)=tmpver

    man_id = clean(arrMonitorInfo(tmpctr,0))
    dev_id = clean(arrMonitorInfo(tmpctr,1))
    man_dt = clean(arrMonitorInfo(tmpctr,2))
    mon_sr = clean(arrMonitorInfo(tmpctr,3))
    mon_md = clean(arrMonitorInfo(tmpctr,4))
    mon_md = escape(mon_md)
    mon_md = replace(mon_md, "%00", "")
    mon_md = unescape(mon_md)
    mon_ed = clean(arrMonitorInfo(tmpctr,5))

     ' Inserts a 0 if month < 10
     temp_date = Split(man_dt, "/", -1, 1)
     temp_date(0) = right("0" & temp_date(0),2)
     man_dt = temp_date(0) & "/" & temp_date(1)
    if man_id <> "" then
      form_input = "monitor_sys^^^" & man_id & "^^^" & dev_id & "^^^" & man_dt & "^^^" _
                                    & mon_md & "^^^" & mon_sr & "^^^" & mon_ed & "^^^"
      entry form_input,comment,objTextFile,oAdd,oComment
      form_input = ""
      Echo(comment)
      if online = "p" then
        oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Monitor Manufacturer: </td><td>" & man_id & "</td></tr>"
        oIE.document.WriteLn "<tr><td>Monitor Model: </td><td>" & mon_md & "</td></tr>"
      end if
    end if
  end If
Next

''''''''''''''''''''''''
' USB Attached Devices '
''''''''''''''''''''''''
comment = "USB Devices"
Echo(comment)
Set colDevices = objWMIService.ExecQuery ("Select * From Win32_USBControllerDevice")
For Each objDevice in colDevices
  strDeviceName = objDevice.Dependent
  strQuotes = Chr(34)
  strDeviceName = Replace(strDeviceName, strQuotes, "")
  arrDeviceNames = Split(strDeviceName, "=")
  strDeviceName = arrDeviceNames(1)
  Set colUSBDevices = objWMIService.ExecQuery ("Select * From Win32_PnPEntity Where DeviceID = '" & strDeviceName & "'")
  For Each objUSBDevice in colUSBDevices
    if ((objUSBDevice.Description <> "USB Root Hub") and _
        (objUSBDevice.Description <> "HID-compliant mouse") and _
        (objUSBDevice.Description <> "Generic USB Hub") and _
        (objUSBDevice.Description <> "Generic volume") and _
        (objUSBDevice.Description <> "USB Mass Storage Device") and _
        (objUSBDevice.Description <> "HID-compliant device") and _
        (objUSBDevice.Description <> "USB Human Interface Device") and _
        (objUSBDevice.Description <> "HID Keyboard Device") and _
        (objUSBDevice.Description <> "USB Composite Device") and _
        (objUSBDevice.Description <> "HID-compliant consumer control device") and _
        (objUSBDevice.Description <> "USB Mass Storage Device") and _
        (objUSBDevice.Description <> "USB Printing Support")) then
      if name <> objUSBDevice.Caption then
        form_input = "usb^^^" & clean(objUSBDevice.Caption)      & "^^^" _
                              & clean(objUSBDevice.Description)  & "^^^" _
                              & clean(objUSBDevice.Manufacturer) & "^^^" _
                              & clean(objUSBDevice.DeviceID)     & "^^^"
        entry form_input,comment,objTextFile,oAdd,oComment
        form_input = ""
      end if
    end if
  Next
Next

'''''''''''''''''''''''''''
'   Hard Drive Information   '
'''''''''''''''''''''''''''
comment = "Hard Disk Info"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_DiskDrive",,48)

For Each objItem in colItems
   PredictFailure = ""
   If objItem.InterfaceType <> "USB" Then
     Set colItems2 = objWMIService2.ExecQuery("Select InstanceName,PredictFailure from MsStorageDriver_FailurePredictStatus ",,48)  
	 Err.Clear
     For Each objItem2 in colItems2
	   If Err.Number = 0 Then 
	     InstanceName = UCase(Left(objItem2.InstanceName, Len(objItem2.InstanceName)-2))
		 If InstanceName = objItem.PNPDeviceID Then
		   If objItem2.PredictFailure Then 
			 PredictFailure = "Yes"
		   Else
			 PredictFailure = "No"
		   End If
		 End If
	   End If	  
	 Next
   End If
   If PredictFailure = "" Then PredictFailure = "No Results"
   form_input = "harddrive^^^" _
     & clean(objItem.Caption)               & "^^^" & clean(objItem.Index)           & "^^^" & clean(objItem.InterfaceType) & "^^^" _
     & clean(objItem.Manufacturer)          & "^^^" & clean(objItem.Model)           & "^^^" & clean(objItem.Partitions)    & "^^^" _
     & clean(objItem.SCSIBus)               & "^^^" & clean(objItem.SCSILogicalUnit) & "^^^" & clean(objItem.SCSIPort)      & "^^^" _
     & clean(int(objItem.Size /1024 /1024)) & "^^^" & clean(objItem.PNPDeviceID)     & "^^^" & clean(objItem.Status)        & "^^^" _
     & PredictFailure                       & "^^^" 
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
     oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Hard Drive Type: </td><td>" & clean(objItem.InterfaceType) & "</td></tr>"
     oIE.document.WriteLn "<tr><td>Hard Drive Size: </td><td>" & clean(int(objItem.Size /1024 /1024)) & " mb</td></tr>"
     oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Hard Drive Model: </td><td>" & clean(objItem.Model) & "</td></tr>"
     oIE.document.WriteLn "<tr><td>Hard Drive Partitions: </td><td>" & clean(objItem.Partitions) & "</td></tr>"
   end if
Next
    '''''''''''''''''''''''''''
    '   Partition Information '
    '''''''''''''''''''''''''''
    comment = "Partition Info"
    Echo(comment)

    ' Get the LogicalDisk's Path
    strQueryFields = "DeviceID,Caption,FileSystem,FreeSpace,Size,VolumeName"
    Set objEnumLogicalDisk = objWMIService.ExecQuery _
      ("Select " & strQueryFields & " from Win32_LogicalDisk where DriveType = 3", "WQL", 0)
    ' Get the DiskPartition's path
    strQueryFields = "Bootable,BootPartition,DeviceID,DiskIndex,Index,PrimaryPartition,Type"
    Set objEnumDiskPartition = objWMIService.ExecQuery _
      ("Select " & strQueryFields & " from Win32_DiskPartition", "WQL", 0)

    For Each objItem in objEnumLogicalDisk
      on error resume next
      partition_caption = objItem.Caption
	  ' get Bitlocker status
	  Set objWMIServiceB = GetObject("winmgmts:\\" & strComputer & "\root\CIMV2\Security\MicrosoftVolumeEncryption")
	  Set objEnumDiskBitlocker = objWMIServiceB.ExecQuery("Select ProtectionStatus from Win32_EncryptableVolume where driveletter='" & partition_caption & "'",,48)
	  For Each blobjItem in objEnumDiskBitlocker
		ldbitlocker=blobjItem.ProtectionStatus
	  Next
      partition_file_system = objItem.FileSystem
      partition_free_space = 0
      partition_free_space = int(objItem.FreeSpace /1024 /1024)
      partition_size = 0
      partition_size = int(objItem.Size /1024 /1024)
      partition_used_space = 0
      partition_used_space = int(objItem.Size /1024 /1024) - int(objItem.FreeSpace /1024 /1024)
      partition_volume_name = objItem.VolumeName
      partition_percent = 0
      partition_percent = round(((partition_size - partition_free_space) / partition_size) * 100 ,0)
     
    ' Associate with Device_ID in Win32_DiskPartition using objLogicalDiskToPartition
      For Each objDiskPartition in objEnumDiskPartition
        ' This is expected to fail once in a while since we are concatonating a possible path to avoid hitting the floppy
        On Error Resume Next
        ' Associate the two sets
        Set objLogicalDiskToPartition = objWMIService.Get _
         (Fixpath(objItem.Path_.relpath,objDiskPartition.path_.relpath), 0)
        If Err.Number = 0 Then
		  partition_type = objDiskPartition.Type
		  echo( " Type: " & partition_type )  ' Neu
          partition_bootable = objDiskPartition.Bootable
          if isnull(partition_bootable) then partition_bootable = "False" end if
          partition_boot_partition = objDiskPartition.BootPartition
          if isnull(partition_boot_partition) then partition_boot_partition = "False" end if
          partition_device_id = objDiskPartition.DeviceID
          partition_disk_index = objDiskPartition.DiskIndex
          partition_index = objDiskPartition.Index
          partition_primary_partition = objDiskPartition.PrimaryPartition
          splitpath = split(objLogicalDiskToPartition.path_.relpath,"=")
		  LogicalDisk_DeviceID = ""
          LogicalDisk_DeviceID = splitpath(2)
          LogicalDisk_DeviceID = replace(LogicalDisk_DeviceID,"\","")
          LogicalDisk_DeviceID = replace(LogicalDisk_DeviceID,"""","")
		  LogicalDisk_Bitlocker = objEnumDiskBitlocker.ProtectionStatus
          Echo( " Bitlocker: " & ldbitlocker )  ' Neu
        Else
          Err.Clear
        End If
        On Error Resume Next
      Next
      ' END Associate with Device_ID in Win32_DiskPartition using objLogicalDiskToPartition
	  form_input = "partition^^^" & partition_bootable & "^^^"  & partition_boot_partition & "^^^" _
	  & partition_device_id         & "^^^" & partition_disk_index        & "^^^" _
	  & partition_index             & "^^^" & partition_percent           & "^^^" _
	  & partition_primary_partition & "^^^" & partition_caption           & "^^^" _
	  & partition_file_system       & "^^^" & partition_free_space        & "^^^" _
	  & partition_size              & "^^^" & partition_volume_name       & "^^^" & partition_used_space & "^^^" & partition_type & "^^^" & ldbitlocker & "^^^"
	  entry form_input,comment,objTextFile,oAdd,oComment
	  form_input = ""
    Next

'''''''''''''''''''''''''''''''''
'   SCSI Cards                  '
'''''''''''''''''''''''''''''''''
comment = "SCSI Cards"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_SCSIController",,48)
For Each objItem in colItems
   form_input = "scsi_controller^^^" & clean(objItem.Caption) & "^^^" & clean(objItem.DeviceID) & "^^^" & clean(objItem.Manufacturer) & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
     oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>SCSI Controller: </td><td>" & clean(objItem.Caption) & "</td></tr>"
     oIE.document.WriteLn "<tr><td>SCSI Controller Manufacturer: </td><td>" & clean(objItem.Manufacturer) & "</td></tr>"
   end if
Next

''''''''''''''''''
'   SCSI Devices '
''''''''''''''''''
comment = "SCSI Devices"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_SCSIControllerDevice",,48)
For Each objItem in colItems
  form_input = "scsi_device^^^" & clean(objItem.Antecedent) & "^^^" & clean(objItem.Dependent) & "^^^"
  entry form_input,comment,objTextFile,oAdd,oComment
  form_input = ""
  'wscript.echo "Device on " & objItem.Antecedent & "   is " & objItem.Dependent
Next

'''''''''''''''''''''''''''''''''
'   Optical Drive Information   '
'''''''''''''''''''''''''''''''''
comment = "Optical Drive Info"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_CDROMDrive",,48)
For Each objItem in colItems
   form_input = "optical^^^" & clean(objItem.Caption) & "^^^" & clean(objItem.Drive) & "^^^" & clean(objItem.DeviceID) & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
     oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Optical Drive: </td><td>" & clean(objItem.Drive) & "</td></tr>"
     oIE.document.WriteLn "<tr><td>Optical Drive Caption: </td><td>" & clean(objItem.Caption) & "</td></tr>"
   end if
Next

'''''''''''''''''''
'  Floppy Drives  '
'''''''''''''''''''
comment = "Floppy Drives"
Echo(comment)
Set colItems = objWMIService.ExecQuery("SELECT * FROM Win32_FloppyDrive",,48)
For Each objItem In colItems
   form_input = "floppy^^^" & clean(objItem.Description)  & "^^^" _
                            & clean(objItem.Manufacturer) & "^^^" _
                            & clean(objItem.Caption)      & "^^^" _
                            & clean(objItem.DeviceID)     & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
     oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Floppy Drive: </td><td>" & clean(objItem.Caption) & "</td></tr>"
   end if
Next

'''''''''''''''''''''''''''
'   Tape Information      '
'''''''''''''''''''''''''''
comment = "Tape Drive Info"
Echo(comment)
On Error Resume Next

Set colItems = objWMIService.ExecQuery("Select * from Win32_TapeDrive",,48)
For Each objItem in colItems
   form_input = "tape^^^" & clean(objItem.Caption)      & "^^^" & clean(objItem.Description) & "^^^" _
                          & clean(objItem.Manufacturer) & "^^^" & clean(objItem.Name) & "^^^" & clean(objItem.DeviceID) & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
     oIE.document.WriteLn "<tr><td>Tape Drive Description: </td><td>" & tape_desc & "</td></tr>"
     oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Tape Drive Manufacturer: </td><td>" & tape_man & "</td></tr>"
   end if
Next

'''''''''''''''''''''''''''
'   Keyboard Information  '
'''''''''''''''''''''''''''
comment = "Keyboard Info"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_Keyboard",,48)
For Each objItem in colItems
   form_input = "keyboard^^^" & clean(objItem.Caption) & "^^^" & clean(objItem.Description) & "^^^" & clean(objItem.DeviceID) & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
    oIE.document.WriteLn "<tr><td>Keyboard Description: </td><td>" & clean(objItem.Description) & "</td></tr>"
   end if
Next

'''''''''''''''''''''''''''
'   Battery Information   '
'''''''''''''''''''''''''''
comment = "Battery Info"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_Battery",,48)
For Each objItem in colItems
   form_input = "battery^^^" & clean(objItem.Description) & "^^^" & clean(objItem.DeviceID) & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
     oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Battery Description: </td><td>" & clean(objItem.Description) & "</td></tr>"
   end if
Next

'''''''''''''''''''''''''''
'   Modem Information     '
'''''''''''''''''''''''''''
comment = "Modem Info"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_POTSModem",,48)
For Each objItem in colItems
   form_input = "modem^^^" & clean(objItem.AttachedTo)  & "^^^" & clean(objItem.CountrySelected) & "^^^" _
                           & clean(objItem.Description) & "^^^" & clean(objItem.DeviceType)  & "^^^" _
                           & clean(objItem.DeviceID)    & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
     oIE.document.WriteLn "<tr><td>Modem Description: </td><td>" & clean(objItem.Description) & "</td></tr>"
   end if
Next

'''''''''''''''''''''''''''
'   Mouse Information     '
'''''''''''''''''''''''''''
comment = "Mouse Info"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_PointingDevice",,48)
For Each objItem in colItems
  mouse_type = objItem.PointingType
  if mouse_type = "1" then mouse_type = "Other" end if
  if mouse_type = "2" then mouse_type = "Unknown" end if
  if mouse_type = "3" then mouse_type = "Mouse" end if
  if mouse_type = "4" then mouse_type = "Track Ball" end if
  if mouse_type = "5" then mouse_type = "Track Point" end if
  if mouse_type = "6" then mouse_type = "Glide Point" end if
  if mouse_type = "7" then mouse_type = "Touch Pad" end if
  if mouse_type = "8" then mouse_type = "Touch Screen" end if
  if mouse_type = "9" then mouse_type = "Mouse - Optical Sensor" end if
  mouse_port = objItem.DeviceInterface
  if mouse_port = "1" then mouse_port = "Other" end if
  if mouse_port = "2" then mouse_port = "Unknown" end if
  if mouse_port = "3" then mouse_port = "Serial" end if
  if mouse_port = "4" then mouse_port = "PS/2" end if
  if mouse_port = "5" then mouse_port = "Infrared" end if
  if mouse_port = "6" then mouse_port = "HP-HIL" end if
  if mouse_port = "7" then mouse_port = "Bus mouse" end if
  if mouse_port = "8" then mouse_port = "ADB (Apple Desktop Bus)" end if
  if mouse_port = "160" then mouse_port = "Bus mouse DB-9" end if
  if mouse_port = "161" then mouse_port = "Bus mouse micro-DIN" end if
  if mouse_port = "162" then mouse_port = "USB" end if
  form_input = "mouse^^^" & clean(objItem.Description) & "^^^" _
                          & clean(objItem.NumberOfButtons) & "^^^" _
                          & clean(objItem.DeviceID) & "^^^" _
                          & mouse_type & "^^^" _
                          & mouse_port & "^^^"
  entry form_input,comment,objTextFile,oAdd,oComment
  form_input = ""
  if online = "p" then
    oIE.document.WriteLn "<tr bgcolor=""#F1F1F1""><td>Mouse Description: </td><td>" & clean(objItem.Description) & "</td></tr>"
  end if
Next

'''''''''''''''''''''''''''
'   Sound Information     '
'''''''''''''''''''''''''''
comment = "Sound Card Info"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_SoundDevice",,48)
For Each objItem in colItems
   form_input = "sound^^^" & clean(objItem.Manufacturer) & "^^^" & clean(objItem.Name) & "^^^" & clean(objItem.DeviceID) & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
   if online = "p" then
     oIE.document.WriteLn "<tr><td>Sound Description: </td><td>" & clean(objItem.Name) & "</td></tr>"
   end if
Next
sql = ""

' End of Hardware
if online = "p" then
  oIE.document.WriteLn "</table>"
  oIE.document.WriteLn "</div>"
  oIE.document.WriteLn "<br style=""page-break-before:always;"" />"
end if

'''''''''''''''''''''''''''
'   Printer Information   '
'''''''''''''''''''''''''''
comment = "Printer Info"
Echo(comment)
create_sql sql, objTextFile, database
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_Printer",,48)
For Each objItem in colItems
   if (objItem.Caption) then printer_caption = clean(objItem.Caption) else printer_caption = "" end if
   if (objItem.Default) then printer_default = clean(objItem.Default) else printer_default = "" end if
   if (objItem.DriverName) then printer_driver_name = clean(objItem.DriverName) else printer_driver_name = "" end if
   printer_horizontal_resolution = objItem.HorizontalResolution
   if (objItem.Local) then printer_local = clean(objItem.Local) else printer_local = "False" end if
   printer_port_name = clean(objItem.PortName)
   printer_shared = clean(objItem.Shared)
   printer_share_name = clean(objItem.ShareName)
   printer_comment = clean(objItem.comment)
   printer_vertical_resolution = objItem.VerticalResolution
   if (objItem.SystemName) then printer_system_name = clean(objItem.SystemName) else printer_system_name = "" end if
   if (objItem.Location) then printer_location = clean(objItem.Location) else printer_location = "" end if
     form_input = "printer^^^" _
     & printer_caption        & "^^^" & printer_local        & "^^^" _
     & printer_port_name      & "^^^" & printer_shared       & "^^^" _
     & printer_share_name     & "^^^" & printer_system_name  & "^^^" _
     & printer_location       & "^^^" & printer_driver_name  & "^^^" & printer_comment  & "^^^"
     entry form_input,comment,objTextFile,oAdd,oComment
     form_input = ""
Next

'''''''''''''''''''''''''''
'   Shares                '
'''''''''''''''''''''''''''
comment = "Share Info"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_Share",,48)
For Each objItem in colItems
   form_input = "shares^^^" & clean(objItem.Caption) & "^^^" & clean(objItem.Name) & "^^^" & clean(objItem.Path) & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
Next

'''''''''''''''''''''''''''
' Mapped Drives '
'''''''''''''''''''''''''''
' This commented code lists only current users's mapped drives

'if audit_location = "l" then
'  comment = "Mapped Drives Info"
'  Echo(comment)
'  On Error Resume Next
'  Set colItems = objWMIService.ExecQuery("Select * from Win32_LogicalDisk ",,48)
'  For Each objItem in colItems 
'    if Left(objItem.ProviderName,2)="\\" then
'      form_input = "mapped^^^" & clean(objItem.DeviceID)                            & "^^^" _
'                               & clean(objItem.FileSystem)                          & "^^^" _
'                               & int(Round(objItem.FreeSpace /1024 /1024 /1024 ,1)) & "^^^" _
'                               & clean(objItem.ProviderName)                        & "^^^" _
'                               & int(Round(objItem.Size /1024 /1024 /1024 ,1))      & "^^^"
'      entry form_input,comment,objTextFile,oAdd,oComment
'      form_input = ""
'    end if
'  Next
'end if

comment = "Mapped Drives Info"
Echo(comment)
On Error Resume Next

'Searching the registry for stored profiles 
strKeyPath = ""
oReg.EnumKey HKEY_USERS, strKeyPath, arrSubKeys
For Each subkey In arrSubKeys
  ' Filtering out some well-known SIDs
  Select Case subkey
    Case ".DEFAULT"
    Case "S-1-5-18" 'Local System
    Case "S-1-5-19" 'Local Service
    Case "S-1-5-20" 'Network service
    Case Else 
         If Instr(subkey, "_Classes") = 0 Then
           ' Checking query access rights on the subkey
           oReg.CheckAccess HKEY_USERS, subkey, &H0001, bHasQueryAccessRight
           If bHasQueryAccessRight = True Then
             ' Check Passed 
             'Searching for mapped drives    
             strKeyPath2 = subkey & "\Network"
             oReg.EnumKey HKEY_USERS, strKeyPath2, arrSubKeys2
             For Each subkey2 in arrSubKeys2
               If subkey2 <> "" Then
                 'Found mapped drive
                 'Searching for the username matching the SID
                 DeviceID = ""
                 ProviderName = ""
                 MapUserName = ""
                 MapUserDomain = ""
                 ConnectAs = ""
                 'Set colItems = objWMIService.ExecQuery("Select Name, Domain from Win32_UserAccount where SID = '" & subkey & "'",,48)
                 'If colItems <> "" Then 
                   ' Found local user
                   'For Each objItem in colItems
                     'MapUserName = objItem.Domain & "\" & objItem.Name
                   'Next
                 'End If
                 Set objItem = objWMIService.Get("Win32_SID.SID='" & subkey &  "'")
                 MapUserName = objItem.ReferencedDomainName & "\" & objItem.AccountName
                 If MapUserName = "" Then
                   'Searching the registry for domain user info
                   strKeyPath3 = subkey & "\Software\Microsoft\Windows\CurrentVersion\Explorer" 
                   oReg.GetStringValue HKEY_USERS, strKeyPath3, "Logon User name", MapUserName
                   strKeyPath4 = subkey & "\Volatile Environment" 
                   oReg.GetStringValue HKEY_USERS, strKeyPath4, "USERDNSDOMAIN", MapUserDomain
                   MapUserName = MapUserName & "@" & LCase(MapUserDomain)
                 End If
                 'Reading  mapped drive details
                 DeviceId = Ucase(subkey2)
                 strKeyPath5 = strKeyPath2 & "\" & subkey2
                 oReg.GetStringValue HKEY_USERS, strKeyPath5, "RemotePath", ProviderName
                 oReg.GetStringValue HKEY_USERS, strKeyPath5, "UserName", ConnectAs
                 FileSystem = ""
                 FreeSpace = 0
                 Size = 0
                 form_input = "mapped^^^" & DeviceID  & "^^^"  & FileSystem   & "^^^"  & FreeSpace  & "^^^"  & ProviderName  & "^^^"  _
                                          & Size      & "^^^"  & MapUserName  & "^^^"  & ConnectAs  & "^^^"
                 entry form_input,comment,objTextFile,oAdd,oComment
                 form_input = ""
               End If 'subkey2 <> ""
             Next 'subkey2 in arrSubKeys2
           End If ' bHasQueryAccessRight = True
         End If 'Instr(subkey, "_Classes") = 0
  End Select
Next ' subkey In arrSubKeys

'''''''''''''''''''''''''''
'   Local Groups          '
'''''''''''''''''''''''''''
if ((domain_role = "4") or (domain_role="5")) then
   Echo("Bypassing Local Groups - This is a domain controller.")
else
  comment = "Local Groups Info"
  Echo(comment)
  On Error Resume Next
  Set colItems = objWMIService.ExecQuery("Select * from Win32_Group where Domain = '" & system_name & "'",,48)
  For Each objItem in colItems
    users = ""
	If (strUser <> "" and strPass <> "") then
      Set objDSO = GetObject("WinNT:")
	  Set colGroups = objDSO.OpenDSObject("WinNT://" & system_name & "", strUser, strPass, ADS_USE_ENCRYPTION OR ADS_SECURE_AUTHENTICATION)
	Else
	  Set colGroups = GetObject("WinNT://" & system_name & "")
	End If
    colGroups.Filter = Array("group")
    For Each objGroup In colGroups
      if objGroup.Name = objItem.Name then
        For Each objUser in objGroup.Members
          if users = "" then
            users = objUser.Name
          else
            users = users & ", " & objUser.Name
          end if
        Next
      end if
    Next
    if users = "" then
      users = "No Members in this group."
    end if
    form_input = "l_group^^^" & clean(objItem.Description)  & "^^^" _
                              & clean(objItem.Name)         & "^^^" _
                              & users                       & "^^^" _
                              & clean(objItem.SID)          & "^^^"
    entry form_input,comment,objTextFile,oAdd,oComment
    form_input = ""
  Next
end if

'''''''''''''''''''''''''''
'   Local Users           '
'''''''''''''''''''''''''''
if ((domain_role = "4") or (domain_role="5")) then
  Echo("Bypassing Local Users - This is a domain controller.")
else
  comment = "Local Users Info"
  Echo(comment)
  On Error Resume Next
  Set colItems = objWMIService.ExecQuery("Select * from Win32_UserAccount where Domain = '" & system_name & "'",,48)
  For Each objItem in colItems
    form_input = "l_user^^^" & clean(objItem.Description)        & "^^^" _
                             & clean(objItem.Disabled)           & "^^^" _
                             & clean(objItem.FullName)           & "^^^" _
                             & clean(objItem.Name)               & "^^^" _
                             & clean(objItem.PasswordChangeable) & "^^^" _
                             & clean(objItem.PasswordExpires)    & "^^^" _
                             & clean(objItem.PasswordRequired)   & "^^^" _
                             & clean(objItem.SID)                & "^^^" _
							 & clean(objItem.Lockout)            & "^^^"
    entry form_input,comment,objTextFile,oAdd,oComment
    form_input = ""
  Next
end if


''''''''''''''''''''''''''''''''''''''''''''''
'   Scheduled Tasks information      '
''''''''''''''''''''''''''''''''''''''''''''''
comment = "Scheduled Tasks Info"
Echo(comment)
On Error resume next

Const wbemFlagReturnImmediately = &H10
Const wbemFlagForwardOnly = &H20

Set objItems = GetObject("winmgmts:{impersonationLevel=impersonate}!\\" & strComputer & "\Root\Microsoft\Windows\TaskScheduler").ExecQuery("" & _
"SELECT * FROM MSFT_ScheduledTask", "WQL", wbemFlagReturnImmediately + wbemFlagForwardOnly)

For Each objItem In objItems
	' &"*"& objItem.Description)
    sTaskName = clean(objItem.TaskName)
    sTaskState = clean(objItem.State)
    sNextRunTime = clean(objItem.Date)
    sCreator = clean(objItem.Author)
    sTaskPath = clean(objItem.TaskPath )
	form_input = "sched_task^^^" & sTaskName & "^^^" & sNextRunTime & "^^^" & sTaskPath & "^^^" & sLastRunTime & "^^^" & sLastResult _
						 & "^^^" & sCreator  & "^^^" & sSchedule    & "^^^" & sTaskToRun & "^^^" & sTaskState   & "^^^" & sRunAsUser  & "^^^"
	entry form_input,comment,objTextFile,oAdd,oComment
	form_input = ""
Next

'''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
'   System Environment Variables information      '
'''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
comment = "System Environment Variables Info"
Echo(comment)
On Error Resume Next   

Set colItems = objWMIService.ExecQuery("Select * from Win32_Environment where username = '<SYSTEM>'",,48)
For Each objItem in colItems
   form_input = "env_var^^^" & clean(objItem.Name) & "^^^" & clean(objItem.VariableValue) & "^^^" 
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
Next

'''''''''''''''''''''''''''''''''''''''
'   Event Logs information      '
'''''''''''''''''''''''''''''''''''''''
comment = "Event Logs Info"
Echo(comment)
On Error Resume Next   

Set colItems = objWMIService.ExecQuery("Select * from Win32_NTEventLogFile",,48)
For Each objItem in colItems
   LogName = clean(objItem.LogFileName)
   FileName = clean(objItem.Name)
   FileSize = clean(objItem.FileSize)/1024
   MaxFileSize = clean(objItem.MaxFileSize)/1024
   OverWritePolicy = clean(objItem.OverWritePolicy)
   Select Case OverWritePolicy
     Case "OutDated"    OverWritePolicy = "OutDated (after " & clean(objItem.OverwriteOutDated) & " days)"
     Case "WhenNeeded"  OverWritePolicy = "As Needed"
   End Select
   form_input = "evt_log^^^" & LogName & "^^^" & FileName & "^^^" & FileSize & "^^^" & MaxFileSize & "^^^" & OverWritePolicy & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
Next

'''''''''''''''''''''''''''''''''''''
'   Ip Routes information      '
'''''''''''''''''''''''''''''''''''''
comment = "Ip Routes Info"
Echo(comment)
On Error Resume Next   

Set colItems = objWMIService.ExecQuery("Select * from Win32_IP4RouteTable",,48)
For Each objItem in colItems
   Protocol = clean(objItem.Protocol)
   Select Case Protocol
        Case "1"  Protocol = "Other"
        Case "2"  Protocol = "Local"
        Case "3"  Protocol = "Netmgmt"
        Case "4"  Protocol = "icmp"
        Case "5"  Protocol = "egp"
        Case "6"  Protocol = "ggp"
        Case "7"  Protocol = "hello"
        Case "8"  Protocol = "rip"
        Case "9"  Protocol = "is-is"
        Case "10" Protocol = "es-is"
        Case "11" Protocol = "CiscoIgrp"
        Case "12" Protocol = "bbnSpfIgp"
        Case "13" Protocol = "ospf"
        Case "14" Protocol = "bgp"
        Case Else Protocol = "unknown"
   End Select
   RouteType = clean(objItem.Type)
   Select Case RouteType
        Case "1"  RouteType = "Other"
        Case "2"  RouteType = "Invalid"
        Case "3"  RouteType = "Direct"
        Case "4"  RouteType = "Indirect"
        Case Else RouteType = "unknown"
   End Select
   form_input = "ip_route^^^" & clean(objItem.Destination) & "^^^" & clean(objItem.Mask) & "^^^" & clean(objItem.Metric1) _
                      & "^^^" & clean(objItem.NextHop)     & "^^^" & Protocol            & "^^^" & RouteType & "^^^"
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
Next

''''''''''''''''''''''''''''''''''''
'   Pagefile information      '
''''''''''''''''''''''''''''''''''''
comment = "Pagefile Info"
Echo(comment)
On Error Resume Next   

Set colItems = objWMIService.ExecQuery("Select * from Win32_PageFile",,48)
For Each objItem in colItems
   form_input = "pagefile^^^" & clean(objItem.Name) & "^^^" & clean(objItem.InitialSize) & "^^^" & clean(objItem.MaximumSize) & "^^^" 
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
Next

'''''''''''''''''''''''''''''''''''''''''
'   Motherboard information      '
'''''''''''''''''''''''''''''''''''''''''
comment = "Motherboard Info"
Echo(comment)
On Error Resume Next   

Set colItems = objWMIService.ExecQuery("Select * from Win32_BaseBoard",,48)
For Each objItem in colItems
  Manufacturer = clean(objItem.Manufacturer)
  Product = clean(objItem.Product)
Next
' Counting CPU sockets 
Set colItems = objWMIService.ExecQuery("SELECT * FROM Win32_Processor",,48)
SocketDesignation = ""
CpuSockets = 0
For Each objItem In colItems
  If Instr(SocketDesignation, objItem.SocketDesignation) = 0 Then
    CpuSockets = CpuSockets + 1
    SocketDesignation = SocketDesignation & objItem.SocketDesignation
  End If
Next
' Counting RAM slots
Set colItems = objWMIService.ExecQuery("Select MemoryDevices FROM Win32_PhysicalMemoryArray ",,48)
For Each objItem in colItems
  MemorySlots = objItem.MemoryDevices
Next

form_input = "motherboard^^^"  & Manufacturer  & "^^^"  & Product  & "^^^"  & CpuSockets  & "^^^"  & MemorySlots  & "^^^"
entry form_input,comment,objTextFile,oAdd,oComment
form_input = ""

''''''''''''''''''''''''''''''''''''''''''''''
'   Onboard devices information      '
''''''''''''''''''''''''''''''''''''''''''''''
comment = "Onboard devices Info"
Echo(comment)
On Error Resume Next   

Set colItems = objWMIService.ExecQuery("Select * from Win32_OnBoardDevice",,48)
For Each objItem in colItems
   DeviceType = clean(objItem.DeviceType)
   Select Case DeviceType
        Case "1"  DeviceType = "Other"
        Case "2"  DeviceType = "Unknown"
        Case "3"  DeviceType = "Video"
        Case "4"  DeviceType = "SCSI Controller"
        Case "5"  DeviceType = "Ethernet"
        Case "6"  DeviceType = "Token Ring"
        Case "7"  DeviceType = "Sound"
        Case Else DeviceType = "Unknown"
   End Select 
   form_input = "onboard^^^" & clean(objItem.Description) & "^^^" & DeviceType & "^^^" 
   entry form_input,comment,objTextFile,oAdd,oComment
   form_input = ""
Next

'''''''''''''''''
'  AV Settings  '
'''''''''''''''''

' Skipping if audited system is not WinXp SP2+, Vista, W2k8 or Seven
if ((SystemBuildNumber = "2600" AND CInt(ServicePack) > 1) OR (CInt(SystemBuildNumber) >= 6000)) then
  comment = "AV - Security Center Settings"
  Echo(comment)
  ' Windows 8.1, 7 und älter
  Set objWMIService_AV = GetObject("winmgmts:\\" & strComputer & "\root\SecurityCenter")
  Set colItems = objWMIService_AV.ExecQuery("Select * from AntiVirusProduct")

	If Err = 0 Then
	  For Each objAntiVirusProduct In colItems
		av_prod = Clean(objAntiVirusProduct.companyName)
		av_disp = Clean(objAntiVirusProduct.displayName)
		av_vers = Clean(objAntiVirusProduct.versionNumber)
		av_up2d = Clean(objAntiVirusProduct.productUptoDate)
		av_date = Clean(objAntiVirusProduct.timestamp)
		If av_up2d Then
		  av_up2d = "True"
		Else
		  av_up2d = "False"
		End If
		
		form_input = "system10^^^" & av_prod  & "^^^"   & av_disp  & "^^^" _
								   & av_up2d  & "^^^"   & av_vers  & "^^^"    & av_date & "^^^"
		entry form_input,comment,objTextFile,oAdd,oComment
		form_input = ""
	  Next
	Else
		strMessage = "Error Description: " & Err.Description & vbCRLF
		Echo(strMessage)
		Err.Clear
	End If

	' Sec Center 2 ab Win10
	Set objWMIService_AV = GetObject("winmgmts:\\" & strComputer & "\root\SecurityCenter2")
	If Err.Number = -2147217394 Then
		Err.Clear
		Echo("WMI Class SecurityCenter2 not found")
	Else
		Set colItems2 = objWMIService_AV.ExecQuery("Select * from AntiVirusProduct")
		If Err = 0 Then
		  For Each objAntiVirusProduct In colItems2
			  PathToSignedProductExe = Replace(objAntiVirusProduct.PathToSignedProductExe,"\","\\")
			  echo ("Path " & PathToSignedProductExe)
			  echo ("Name " & objAntiVirusProduct.displayName)
			  Set colFiles = objWMIService.ExecQuery ("Select * from CIM_Datafile Where name = '" & PathToSignedProductExe & "'",,48)
				  For Each itemFile In colFiles  
					av_disp = Clean(itemFile.displayName)  
					av_date = Clean(itemFile.timestamp)  
					  if objAntiVirusProduct.ProductState = "266240" OR objAntiVirusProduct.ProductState = "397568" then  
						av_up2d = "True"
					  Else
						av_up2d = "False"
					  End If
				  Next
			  av_disp = Clean(objAntiVirusProduct.displayName)  
			  av_date = Clean(objAntiVirusProduct.timestamp)  
			  if objAntiVirusProduct.ProductState = "266240" OR objAntiVirusProduct.ProductState = "397568" then  
				av_up2d = "True"
			  Else
				av_up2d = "False"
			  End If
		  Next
		Else
			strMessage = "Error Description: " & Err.Description & vbCRLF
			Echo(strMessage)
			Err.Clear
		End If

		' Firewall in Manufacturer Spalte eintragen, wenn Seccenter 2
		Set colItems3 = objWMIService_AV.ExecQuery("Select * from FirewallProduct")
		If Err = 0 Then
			For Each objFirewallProduct In colItems3
				strMessage = objFirewallProduct.companyName & "   "
				strMessage = strMessage & objFirewallProduct.displayName & "   "
				strMessage = strMessage & objFirewallProduct.enabled & "   "
				strMessage = strMessage & objFirewallProduct.versionNumber
				av_prod  = "Windows Firewall " & Clean(strMessage)
			Next
		Else
			strMessage = "Error Description: " & Err.Description & vbCRLF
			Echo(strMessage)
			Err.Clear
		End If
	Echo("Firewall: " & strMessage) 
	av_prod  = "Windows Firewall " & Clean(strMessage)

    ' Daten an PHP übergeben
	form_input = "system10^^^" & av_prod  & "^^^"   & av_disp  & "^^^" _
                               & av_up2d  & "^^^"   & av_vers  & "^^^"   & av_date  & "^^^"
    entry form_input,comment,objTextFile,oAdd,oComment
    form_input = ""
end if     ' Fehlermeldung seccenter2?

end if    ' Neuer als xp sp2

if software_audit = "y" then
' software audit finishes further down the script

'''''''''''''''''''''''''''
' Software Files          '
'''''''''''''''''''''''''''

if software_file_audit = "y" then
 comment = "Software Files"
 Echo(comment)
 Dim softName,softVersion, softPublisher

 set rootNode =  xmlDoc.documentElement ' should be detect
 for each child in rootNode.childNodes   'should get each software
    softName = child.getAttribute("name")
    softVersion = ""
    softPublisher = ""

    Echo("Testing for " & child.nodeName & ":" & child.getAttribute("name"))
    if (getResultFromFileExpression(child.childNodes.Item(1))) then
       softPublisher = child.getAttribute("publisher")
       If (child.childNodes.Item(0).nodeName = "version") then
          softVersion = child.childNodes.Item(0).getAttribute("name")
       elseif (child.childNodes.Item(0).nodeName = "versionFile") then
          softVersion = ""
          On Error Resume Next
          softVersion = objWMIService.get("CIM_DataFile.Name='" & child.childNodes.Item(0).getAttribute("filename") & "'").Version
          On Error Goto 0
       end if


       form_input = "software^^^" & clean(softName) & "^^^" _
                               & clean(softVersion) & "^^^" _
                               & clean("") & "^^^" _
                               & clean("") & "^^^" _
                               & clean("") & "^^^" _
                               & clean(softPublisher) & "^^^" _
                               & clean("") & "^^^" _
                               & clean("") & "^^^" _
                               & clean("") & "^^^" _
                               & clean(" ")
       entry form_input,comment,objTextFile,oAdd,oComment
       form_input = ""
       Echo("Software detected")
       Echo("Name       :" & softName)
       Echo("Publisher  :" & softPublisher)
       Echo("Version    :" & softVersion)
    else
       Echo("Not Detected")
    end if
 next
end if

'''''''''''''''''''''''''''
'   Windows QFE Hotfixes  '
'''''''''''''''''''''''''''
comment = "Software Windows QFE Fixes"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_QuickFixEngineering",,48)
For Each objItem in colItems
		softname= objItem.Description & " (" & objItem.HotFixID & ")"
		softversion="1.0"
		softpublisher="Microsoft"
		installdate=objItem.InstalledOn
		softurl=objItem.Caption
       form_input = "software^^^" & clean(softname) & "^^^" _
                               & clean(softVersion) & "^^^" _
                               & clean("") & "^^^" _
                               & clean("") & "^^^" _
                               & clean(installdate) & "^^^" _
                               & clean(softPublisher) & "^^^" _
                               & clean("") & "^^^" _
                               & clean("") & "^^^" _
                               & clean(softurl) & "^^^" _
                               & clean("Hotfix")
       entry form_input,comment,objTextFile,oAdd,oComment
       form_input = ""
Next

'''''''''''''''''''''''''''
'   Startup Programs      '
'''''''''''''''''''''''''''
comment = "Startup Programs"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_StartupCommand",,48)
For Each objItem in colItems
  if objItem.Location <> "Startup" AND (objItem.User <> ".DEFAULT" OR objItem.User <> "NT AUTHORITY\SYSTEM") then
    form_input = "startup^^^" & objItem.Caption     & " ^^^" _
                              & objItem.Command     & " ^^^" _
                              & objItem.Description & " ^^^" _
                              & objItem.Location    & " ^^^" _
                              & objItem.Name        & " ^^^" _
                              & objItem.User        & "^^^"
    entry form_input,comment,objTextFile,oAdd,oComment
    form_input = ""
  end if
Next

'''''''''''''''''''''''''''
'   Services              '
'''''''''''''''''''''''''''
comment = "Services"
Echo(comment)
On Error Resume Next
Set colItems = objWMIService.ExecQuery("Select * from Win32_Service",,48)
For Each objItem in colItems
  form_input = "service^^^" & clean(objItem.Description) & " ^^^" & clean(objItem.DisplayName) & " ^^^" _
                            & clean(objItem.Name)        & " ^^^" & clean(objItem.PathName)    & " ^^^" _
                            & clean(objItem.Started)     & " ^^^" & clean(objItem.StartMode)   & " ^^^" _
                            & clean(objItem.State)       & " ^^^" & clean(objItem.StartName)   & " ^^^"
  entry form_input,comment,objTextFile,oAdd,oComment
  form_input = ""
  ' Searching for IIS services
  Select Case UCase(objItem.Name)
    Case "IISADMIN"   iis = "True"
    Case "W3SVC"      iis_w3svc = "True"
    Case "MSFTPSVC"   iis_ftpsvc = "True"
    Case "SMTPSVC"    iis_smtpsvc = "True"
    Case "NNTPSVC"    iis_nntpsvc = "True"
  End Select
Next

'''''''''''''''''''''''''''''
' IE Browser Helper Objects '
'''''''''''''''''''''''''''''
if (OSName <> "Microsoft Windows 95" AND OSName <> "Microsoft Windows 98") then
  comment = "Internet Explorer Browser Helper Objects"
  Echo(comment)
  if strUser <> "" and strPass <> "" then
    Set objWMIService_IE = wmiLocator.ConnectServer(strComputer, "root\cimv2\Applications\MicrosoftIE", strUser, strPass, "", "", wbemConnectFlagUseMaxWait)
    objWMIService_IE.Security_.ImpersonationLevel = 3
  else
    Set objWMIService_IE = GetObject("winmgmts:\\" & strComputer & "\root\cimv2\Applications\MicrosoftIE")
  end if
  Set colIESettings = objWMIService_IE.ExecQuery ("Select * from MicrosoftIE_Object")
  For Each strIESetting in colIESettings
    form_input = "ie_bho^^^" & clean(strIESetting.CodeBase)    & "^^^" _
                             & clean(strIESetting.Status)      & "^^^" _
                             & clean(strIESetting.ProgramFile) & "^^^"
    entry form_input,comment,objTextFile,oAdd,oComment
    form_input = ""
  Next
end if

'''''''''''''''''''''''''''
'   Installed Software    '
'''''''''''''''''''''''''''
if online = "p" then
    oIE.document.WriteLn "<div id=""content"">"
    oIE.document.WriteLn "<table border=""0"" cellpadding=""2"" cellspacing=""0"" class=""content"">"
    oIE.document.WriteLn "<tr><td colspan=""2""><b>Installed Software</b></td></tr>"
end if
On Error Resume Next

'//   Begin Replacement Softwareinventory 32 and 64 bit

Subhive="Software\Microsoft\Windows\CurrentVersion\Uninstall\" 
Set objCtx = CreateObject("WbemScripting.SWbemNamedValueSet")

objCtx.Add "__ProviderArchitecture", 32
objCtx.Add "__RequiredArchitecture", TRUE
Set objLocator = CreateObject("Wbemscripting.SWbemLocator")
Set objServices = objLocator.ConnectServer(strComputer, "root\default", strUser, strPass, "", "", wbemConnectFlagUseMaxWait, objCtx)
Set o64reg = objServices.Get("StdRegProv") 
comment = "Installed Software 32 Bit"
Echo(comment)

  Set Inparams = o64reg.Methods_("EnumKey").Inparameters
  Inparams.Hdefkey = HKLM
  Inparams.Ssubkeyname = subhive
  set Outparams = o64reg.ExecMethod_("EnumKey", Inparams,,objCtx) 
  For Each strSubKey In Outparams.snames 
    Set Inparams = o64reg.Methods_("GetStringValue").Inparameters
    Inparams.Hdefkey = HKLM
    Inparams.Ssubkeyname = Subhive & strSubKey

		Inparams.Svaluename = "DisplayName"
		set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
		if Outparams.SValue <> "" then
        version = ""
        uninstall_string = ""
        install_date = ""
        publisher = ""
        install_source = ""
        install_location = ""
        system_component = ""
        display_name = Outparams.SValue

        Inparams.Svaluename = "DisplayVersion"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        version = Outparams.SValue
        if (isnull(version)) then version = "" end if

        Inparams.Svaluename = "UninstallString"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        uninstall_string = Outparams.SValue
        if (isnull(uninstall_string)) then uninstall_string = "" end if

        Inparams.Svaluename = "InstallDate"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_date = Outparams.SValue
        if (isnull(install_date)) then install_date = "" end if

        Inparams.Svaluename = "Publisher"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        publisher = Outparams.SValue
        if (isnull(publisher)) then publisher = "" end if

        Inparams.Svaluename = "InstallSource"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_source = Outparams.SValue
        if (isnull(install_source)) then install_source = "" end if

        Inparams.Svaluename = "InstallLocation"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_location = Outparams.SValue
        if (isnull(install_location)) then install_location = "" end if

        Inparams.Svaluename = "SystemComponent"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        system_component = Outparams.SValue
        if (isnull(system_component)) then system_component = "" end if

        Inparams.Svaluename = "URLInfoAbout"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        software_url = Outparams.SValue
        if (isnull(software_url)) then software_url = "" end if

        Inparams.Svaluename = "Comments"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        software_comments = Outparams.SValue
        if (isnull(software_comments)) then software_comments = " " end if

        if online = "p" then
          software = software & display_name & vbcrlf
        end if

       form_input = "software^^^" & clean(display_name)      & " ^^^" _
                   & clean(version)           & " ^^^" _
                   & clean(install_location)  & " ^^^" _
                   & clean(uninstall_string)  & " ^^^" _
                   & clean(install_date)      & " ^^^" _
                   & clean(publisher)         & " ^^^" _
                   & clean(install_source)    & " ^^^" _
                   & clean(system_component)  & " ^^^" _
                   & clean(software_url)      &  "^^^" _
                   & clean(software_comments) & "^^^"
		entry form_input,comment,objTextFile,oAdd,oComment
        form_input = ""
      end If
Next 
'// Ende 32 Bit Desktopapps

'// 64-Bit Desktopapps pro Maschine
objCtx.Add "__ProviderArchitecture", 64
objCtx.Add "__RequiredArchitecture", TRUE
Set objLocator = CreateObject("Wbemscripting.SWbemLocator")
Set objServices = objLocator.ConnectServer(strComputer, "root\default", strUser, strPass, "", "", wbemConnectFlagUseMaxWait, objCtx)
Set o64reg = objServices.Get("StdRegProv") 
comment = "Installed Software 64 Bit"
Echo(comment)

  Set Inparams = o64reg.Methods_("EnumKey").Inparameters
  Inparams.Hdefkey = HKLM
  Inparams.Ssubkeyname = subhive
  set Outparams = o64reg.ExecMethod_("EnumKey", Inparams,,objCtx) 
  For Each strSubKey In Outparams.snames 
    Set Inparams = o64reg.Methods_("GetStringValue").Inparameters
    Inparams.Hdefkey = HKLM
    Inparams.Ssubkeyname = Subhive & strSubKey

		Inparams.Svaluename = "DisplayName"
		set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
		if Outparams.SValue <> "" then
        version = ""
        uninstall_string = ""
        install_date = ""
        publisher = ""
        install_source = ""
        install_location = ""
        system_component = ""
        display_name = Outparams.SValue

        Inparams.Svaluename = "DisplayVersion"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        version = Outparams.SValue
        if (isnull(version)) then version = "" end if

        Inparams.Svaluename = "UninstallString"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        uninstall_string = Outparams.SValue
        if (isnull(uninstall_string)) then uninstall_string = "" end if

        Inparams.Svaluename = "InstallDate"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_date = Outparams.SValue
        if (isnull(install_date)) then install_date = "" end if

        Inparams.Svaluename = "Publisher"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        publisher = Outparams.SValue
        if (isnull(publisher)) then publisher = "" end if

        Inparams.Svaluename = "InstallSource"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_source = Outparams.SValue
        if (isnull(install_source)) then install_source = "" end if

        Inparams.Svaluename = "InstallLocation"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_location = Outparams.SValue
        if (isnull(install_location)) then install_location = "" end if

        Inparams.Svaluename = "SystemComponent"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        system_component = Outparams.SValue
        if (isnull(system_component)) then system_component = "" end if

        Inparams.Svaluename = "URLInfoAbout"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        software_url = Outparams.SValue
        if (isnull(software_url)) then software_url = "" end if

        Inparams.Svaluename = "Comments"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        software_comments = Outparams.SValue
        if (isnull(software_comments)) then software_comments = " " end if

        if online = "p" then
          software = software & display_name & vbcrlf
        end if

       form_input = "software^^^" & clean(display_name)      & " ^^^" _
                   & clean(version)           & " ^^^" _
                   & clean(install_location)  & " ^^^" _
                   & clean(uninstall_string)  & " ^^^" _
                   & clean(install_date)      & " ^^^" _
                   & clean(publisher)         & " ^^^" _
                   & clean(install_source)    & " ^^^" _
                   & clean(system_component)  & " ^^^" _
                   & clean(software_url)      &  "^^^" _
                   & clean(software_comments) & "^^^"
		entry form_input,comment,objTextFile,oAdd,oComment
        form_input = ""
      end If
Next 

'//   End Softwareinventory 32 and 64 bit,  now HKCU 32 Bit Software installed in iser hive of logged in user

objCtx.Add "__ProviderArchitecture", 32
objCtx.Add "__RequiredArchitecture", TRUE
Set objLocator = CreateObject("Wbemscripting.SWbemLocator")
Set objServices = objLocator.ConnectServer(strComputer, "root\default", strUser, strPass, "", "", wbemConnectFlagUseMaxWait, objCtx)
Set o64reg = objServices.Get("StdRegProv") 
comment = "Installed Software 32 Bit (current user)"
Echo(comment)

  Set Inparams = o64reg.Methods_("EnumKey").Inparameters
  Inparams.Hdefkey = HKLM
  Inparams.Ssubkeyname = subhive
  set Outparams = o64reg.ExecMethod_("EnumKey", Inparams,,objCtx) 
  For Each strSubKey In Outparams.snames 
    Set Inparams = o64reg.Methods_("GetStringValue").Inparameters
    Inparams.Hdefkey = HKLM
    Inparams.Ssubkeyname = Subhive & strSubKey
		Inparams.Svaluename = "DisplayName"
		set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
		if Outparams.SValue <> "" then
        version = ""
        uninstall_string = ""
        install_date = ""
        publisher = ""
        install_source = ""
        install_location = ""
        system_component = ""
        display_name = Outparams.SValue
        Inparams.Svaluename = "DisplayVersion"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        version = Outparams.SValue
        if (isnull(version)) then version = "" end if

        Inparams.Svaluename = "UninstallString"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        uninstall_string = Outparams.SValue
        if (isnull(uninstall_string)) then uninstall_string = "" end if

        Inparams.Svaluename = "InstallDate"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_date = Outparams.SValue
        if (isnull(install_date)) then install_date = "" end if

        Inparams.Svaluename = "Publisher"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        publisher = Outparams.SValue
        if (isnull(publisher)) then publisher = "" end if

        Inparams.Svaluename = "InstallSource"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_source = Outparams.SValue
        if (isnull(install_source)) then install_source = "" end if

        Inparams.Svaluename = "InstallLocation"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_location = Outparams.SValue
        if (isnull(install_location)) then install_location = "" end if

        Inparams.Svaluename = "SystemComponent"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        system_component = Outparams.SValue
        if (isnull(system_component)) then system_component = "" end if

        Inparams.Svaluename = "URLInfoAbout"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        software_url = Outparams.SValue
        if (isnull(software_url)) then software_url = "" end if

        Inparams.Svaluename = "Comments"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        software_comments = Outparams.SValue
        if (isnull(software_comments)) then software_comments = " " end if

        if online = "p" then
          software = software & display_name & vbcrlf
        end if
       form_input = "software^^^" & clean(display_name)      & " ^^^" _
                   & clean(version)           & " ^^^" _
                   & clean(install_location)  & " ^^^" _
                   & clean(uninstall_string)  & " ^^^" _
                   & clean(install_date)      & " ^^^" _
                   & clean(publisher)         & " ^^^" _
                   & clean(install_source)    & " ^^^" _
                   & clean(system_component)  & " ^^^" _
                   & clean(software_url)      &  "^^^" _
                   & clean(software_comments) & "^^^"
		entry form_input,comment,objTextFile,oAdd,oComment
        form_input = ""
      end If
Next 

'// 64-Bit Current User Software
objCtx.Add "__ProviderArchitecture", 64
objCtx.Add "__RequiredArchitecture", TRUE
Set objLocator = CreateObject("Wbemscripting.SWbemLocator")
Set objServices = objLocator.ConnectServer(strComputer, "root\default", strUser, strPass, "", "", wbemConnectFlagUseMaxWait, objCtx)
Set o64reg = objServices.Get("StdRegProv") 
comment = "Installed Software 64 Bit (current user)"
Echo(comment)

  Set Inparams = o64reg.Methods_("EnumKey").Inparameters
  Inparams.Hdefkey = HKCU
  Inparams.Ssubkeyname = subhive
  set Outparams = o64reg.ExecMethod_("EnumKey", Inparams,,objCtx) 
  For Each strSubKey In Outparams.snames 
    Set Inparams = o64reg.Methods_("GetStringValue").Inparameters
    Inparams.Hdefkey = HKCU
    Inparams.Ssubkeyname = Subhive & strSubKey
		Inparams.Svaluename = "DisplayName"
		set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
		if Outparams.SValue <> "" then
        version = ""
        uninstall_string = ""
        install_date = ""
        publisher = ""
        install_source = ""
        install_location = ""
        system_component = ""
        display_name = Outparams.SValue
        Inparams.Svaluename = "DisplayVersion"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        version = Outparams.SValue
        if (isnull(version)) then version = "" end if

        Inparams.Svaluename = "UninstallString"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        uninstall_string = Outparams.SValue
        if (isnull(uninstall_string)) then uninstall_string = "" end if

        Inparams.Svaluename = "InstallDate"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_date = Outparams.SValue
        if (isnull(install_date)) then install_date = "" end if

        Inparams.Svaluename = "Publisher"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        publisher = Outparams.SValue
        if (isnull(publisher)) then publisher = "" end if

        Inparams.Svaluename = "InstallSource"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_source = Outparams.SValue
        if (isnull(install_source)) then install_source = "" end if

        Inparams.Svaluename = "InstallLocation"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_location = Outparams.SValue
        if (isnull(install_location)) then install_location = "" end if

        Inparams.Svaluename = "SystemComponent"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        system_component = Outparams.SValue
        if (isnull(system_component)) then system_component = "" end if

        Inparams.Svaluename = "URLInfoAbout"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        software_url = Outparams.SValue
        if (isnull(software_url)) then software_url = "" end if

        Inparams.Svaluename = "Comments"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        software_comments = Outparams.SValue
        if (isnull(software_comments)) then software_comments = " " end if
        if online = "p" then
          software = software & display_name & vbcrlf
        end if
       form_input = "software^^^" & clean(display_name)      & " ^^^" _
                   & clean(version)           & " ^^^" _
                   & clean(install_location)  & " ^^^" _
                   & clean(uninstall_string)  & " ^^^" _
                   & clean(install_date)      & " ^^^" _
                   & clean(publisher)         & " ^^^" _
                   & clean(install_source)    & " ^^^" _
                   & clean(system_component)  & " ^^^" _
                   & clean(software_url)      &  "^^^" _
                   & clean(software_comments) & "^^^"
		entry form_input,comment,objTextFile,oAdd,oComment
        form_input = ""
      end If
Next 

'//   End Softwareinventory Curruser 32 and 64 bit


'// Neu - inventory user apps 32 Bit (all users where software in profile)
Set objCtx = CreateObject("WbemScripting.SWbemNamedValueSet")
objCtx.Add "__ProviderArchitecture", 32
objCtx.Add "__RequiredArchitecture", TRUE
Set objLocator = CreateObject("Wbemscripting.SWbemLocator")
Set objServices = objLocator.ConnectServer(strComputer, "root\default", strUser, strPass, "", "", wbemConnectFlagUseMaxWait, objCtx)
Set o64reg = objServices.Get("StdRegProv") 
comment = "Installed Software 32 Bit (for each User)"
Echo(comment)

  Set Inparamu = o64reg.Methods_("EnumKey").Inparameters
  Inparamu.Hdefkey = HKUS
  Inparamu.Ssubkeyname = ""
  '   on error goto 0
  set Outparamu = o64reg.ExecMethod_("EnumKey", Inparamu,,objCtx) 
  For Each strSubUser In Outparamu.snames 
	  Subhive= strSubUser &"\Software\Microsoft\Windows\CurrentVersion\Uninstall\" 
	  ' echo(" - " & subhive)
	  Set Inparams = o64reg.Methods_("EnumKey").Inparameters
	  Inparams.Hdefkey = HKUS
	  Inparams.Ssubkeyname = subhive
	  set Outparams = o64reg.ExecMethod_("EnumKey", Inparams,,objCtx) 
    Set Inparams = o64reg.Methods_("GetStringValue").Inparameters
	For Each strSubKey In Outparams.snames 
		Inparams.Ssubkeyname = Subhive & strSubKey
		Inparams.Svaluename = "DisplayName"
		set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
		if Outparams.SValue <> "" then
        version = ""
        uninstall_string = ""
        install_date = ""
        publisher = ""
        install_source = ""
        install_location = ""
        system_component = ""
        display_name = Outparams.SValue
        Inparams.Svaluename = "DisplayVersion"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        version = Outparams.SValue
        if (isnull(version)) then version = "" end if

        Inparams.Svaluename = "UninstallString"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        uninstall_string = Outparams.SValue
        if (isnull(uninstall_string)) then uninstall_string = "" end if

        Inparams.Svaluename = "InstallDate"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_date = Outparams.SValue
        if (isnull(install_date)) then install_date = "" end if

        Inparams.Svaluename = "Publisher"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        publisher = Outparams.SValue
        if (isnull(publisher)) then publisher = "" end if

        Inparams.Svaluename = "InstallSource"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_source = Outparams.SValue
        if (isnull(install_source)) then install_source = "" end if

        Inparams.Svaluename = "InstallLocation"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        install_location = Outparams.SValue
        if (isnull(install_location)) then install_location = "" end if

        Inparams.Svaluename = "SystemComponent"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        system_component = Outparams.SValue
        if (isnull(system_component)) then system_component = "" end if

        Inparams.Svaluename = "URLInfoAbout"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        software_url = Outparams.SValue
        if (isnull(software_url)) then software_url = "" end if

        Inparams.Svaluename = "Comments"
        set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
        software_comments = Outparams.SValue
        if (isnull(software_comments)) then software_comments = " " end if

        if online = "p" then
          software = software & display_name & vbcrlf
        end if
       form_input = "software^^^" & clean(display_name)      & " ^^^" _
                   & clean(version)           & " ^^^" _
                   & clean(install_location)  & " ^^^" _
                   & clean(uninstall_string)  & " ^^^" _
                   & clean(install_date)      & " ^^^" _
                   & clean(publisher)         & " ^^^" _
                   & clean(install_source)    & " ^^^" _
                   & clean(system_component)  & " ^^^" _
                   & clean(software_url)      &  "^^^" _
                   & clean(software_comments) & "^^^"
		entry form_input,comment,objTextFile,oAdd,oComment
        form_input = ""
      end If
	Next 
  Next
	'// Ende 32 Bit User apps


'// inventory modern (metro) app names

Set objCtx = CreateObject("WbemScripting.SWbemNamedValueSet")

objCtx.Add "__ProviderArchitecture", 32
objCtx.Add "__RequiredArchitecture", TRUE
Set objLocator = CreateObject("Wbemscripting.SWbemLocator")
Set objServices = objLocator.ConnectServer(strComputer, "root\default", strUser, strPass, "", "", wbemConnectFlagUseMaxWait, objCtx)
Set o64reg = objServices.Get("StdRegProv") 
comment = "Installed Modern Apps 32 Bit per User"
Echo(comment)

  Set Inparamu = o64reg.Methods_("EnumKey").Inparameters
  Inparamu.Hdefkey = HKUS
  Inparamu.Ssubkeyname = ""
  '   on error goto 0
  set Outparamu = o64reg.ExecMethod_("EnumKey", Inparamu,,objCtx) 
  For Each strSubUser In Outparamu.snames 
	  if len(strSubUser)>40 and instr(strSubUser,"Classes")=0 then	
		  Subhive= strSubUser &"\Software\Classes\ActivatableClasses\Package\" 
		  ' echo(" - " & subhive)
		  Set Inparams = o64reg.Methods_("EnumKey").Inparameters
		  Inparams.Hdefkey = HKUS
		  Inparams.Ssubkeyname = subhive
		  set Outparams = o64reg.ExecMethod_("EnumKey", Inparams,,objCtx) 
		  For Each strSubKey In Outparams.snames 
				' echo(strSubKey)
				if instr(strSubKey,"NOPUBLISHERID")=0 then
					version = "" & mid(strSubkey,ninstr(strsubkey,"_",1)+1,ninstr(strsubkey,"_",2)-ninstr(strsubkey,"_",1)-1)
					uninstall_string = ""
					install_date = ""
					publisher = "Windows Modern App"
					install_source = ""
					install_location = ""
					system_component = ""
					display_name = ""& left(strSubkey,ninstr(strsubkey,"_",1)-1)
					software_comments = "" & mid(strSubkey,ninstr(strsubkey,"_",2)+1,ninstr(strsubkey,"_",3)-ninstr(strsubkey,"_",2)-1)
					if online = "p" then
					  software = software & display_name & vbcrlf
					end if
				   form_input = "softwapps^^^" & clean(display_name)      & " ^^^" _
							   & clean(version)           & " ^^^" _
							   & clean(install_location)  & " ^^^" _
							   & clean(uninstall_string)  & " ^^^" _
							   & clean(install_date)      & " ^^^" _
							   & clean(publisher)         & " ^^^" _
							   & clean(install_source)    & " ^^^" _
							   & clean(system_component)  & " ^^^" _
							   & clean(software_url)      &  "^^^" _
							   & clean(software_comments) & "^^^"
					' // echo (form_input)
					entry form_input,comment,objTextFile,oAdd,oComment
					form_input = ""
				end if
		  Next 
	  end if
  Next
	'// Ende 32 Bit Modern apps

' Include customer specific audits

ExecuteGlobal CreateObject("Scripting.FileSystemObject").OpenTextFile("audit_custom_software.inc").ReadAll

' Installed Codecs
comment = "Installed Media Codecs"
Echo(comment)
Set colItems = objWMIService.ExecQuery("SELECT * FROM Win32_CodecFile", , 48)
For Each objItem In colItems
  if clean(objItem.Manufacturer) <> "Microsoft Corporation" then
    form_input = "software^^^Codec - " & clean(objItem.Group) & " - " & clean(objItem.Filename) & "^^^" _
                                       & clean(objItem.Version) & "^^^" _
                                       & clean(objItem.Caption) & "^^^" _
                                       & " ^^^" _
                                       & clean(objItem.InstallDate) & "^^^" _
                                       & clean(objItem.Manufacturer) & "^^^" _
                                       & " ^^^" _
                                       & " ^^^" _
                                       & " ^^^" _
                                       & clean(objItem.Description) & "^^^"
    entry form_input,comment,objTextFile,oAdd,oComment
    form_input = ""
  end if
Next

comment = "MDAC/WDAC, DirectX, Media Player, IE and OE Versions"
Echo(comment)

' Add MDAC/WDAC to the Software Register
strKeyPath = "SOFTWARE\Microsoft\DataAccess"
strValueName = "Version"
oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,dac_version
if SystemBuildNumber <> "6000" then
  display_name = "MDAC"
else
  display_name = "Windows DAC"
end if
form_input = "software^^^" & display_name       & "^^^" _
                           & dac_version         & "^^^" _
                           & ""                 & "^^^" _
                           & ""                 & "^^^" _
                           & OSInstall          & "^^^" _
                           & "Microsoft Corporation^^^" _
                           & ""                 & "^^^" _
                           & ""                 & "^^^" _
                           & "https://msdn2.microsoft.com/en-us/data/default.aspx" & "^^^ "
entry form_input,comment,objTextFile,oAdd,oComment
form_input = ""


' Add DirectX to the Software Register
strKeyPath = "SOFTWARE\Microsoft\DirectX"
strValueName = "Version"
oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,dx_version
display_name = "DirectX"
if dx_version = "4.08.01.0810" then display_name = "DirectX 8.1" end if
if dx_version = "4.08.01.0881" then display_name = "DirectX 8.1" end if
if dx_version = "4.08.01.0901" then display_name = "DirectX 8.1a" end if
if dx_version = "4.08.01.0901" then display_name = "DirectX 8.1b" end if
if dx_version = "4.08.02.0134" then display_name = "DirectX 8.2" end if
if dx_version = "4.09.00.0900" then display_name = "DirectX 9" end if
if dx_version = "4.09.00.0901" then display_name = "DirectX 9a" end if
if dx_version = "4.09.00.0902" then display_name = "DirectX 9b" end if
if dx_version = "4.09.00.0903" then display_name = "DirectX 9c" end if
if dx_version = "4.09.00.0904" then display_name = "DirectX 9c" end if
form_input = "software^^^" & display_name       & "^^^" _
                           & dx_version         & "^^^" _
                           & ""                 & "^^^" _
                           & ""                 & "^^^" _
                           & OSInstall          & "^^^" _
                           & "Microsoft Corporation^^^" _
                           & ""                 & "^^^" _
                           & ""                 & "^^^" _
                           & "https://www.microsoft.com/windows/directx/" & "^^^ "
entry form_input,comment,objTextFile,oAdd,oComment
form_input = ""


' Add Windows Media Player to the Software Register
strKeyPath = "SOFTWARE\Microsoft\MediaPlayer\PlayerUpgrade"
strValueName = "PlayerVersion"
oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,wmp_version
form_input = "software^^^Windows Media Player^^^" _
                  & wmp_version         & "^^^" _
                  & ""                 & "^^^" _
                  & ""                 & "^^^" _
                  & OSInstall          & "^^^" _
                  & "Microsoft Corporation^^^" _
                  & ""                 & "^^^" _
                  & ""                 & "^^^" _
                  & "https://www.microsoft.com/windows/windowsmedia/default.aspx" & "^^^ "
entry form_input,comment,objTextFile,oAdd,oComment
form_input = ""


' Add IE to the Software Register
strKeyPath = "SOFTWARE\Microsoft\Internet Explorer"
strValueName = "Version"
oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,version_ie
form_input = "software^^^Internet Explorer^^^" _
                  & version_ie         & "^^^" _
                  & ""                 & "^^^" _
                  & ""                 & "^^^" _
                  & OSInstall          & "^^^" _
                  & "Microsoft Corporation^^^" _
                  & ""                 & "^^^" _
                  & ""                 & "^^^" _
                  & "https://www.microsoft.com/windows/ie/community/default.mspx" & "^^^ "
entry form_input,comment,objTextFile,oAdd,oComment
form_input = ""


' Add Outlook Express to the Software Register
Set colFiles = objWMIService.ExecQuery("Select * from CIM_Datafile Where Name = 'c:\\program files\\Outlook Express\\msimn.exe'",,48)
For Each objFile in colFiles
  form_input = "software^^^Outlook Express^^^" _
                & clean(objFile.Version)         & "^^^" _
                & ""                             & "^^^" _
                & ""                             & "^^^" _
                & OSInstall                      & "^^^" _
                & "Microsoft Corporation^^^" _
                & ""                             & "^^^" _
                & ""                             & "^^^" _
                & "https://support.microsoft.com/default.aspx?xmlid=fh;en-us;oex" & "^^^ "
  entry form_input,comment,objTextFile,oAdd,oComment
  form_input = ""
Next


' Add the OS to the Software Register

	if left(OSName,17) = "Microsoft Windows" then
		Set oReg = wmiNameSpace.Get("StdRegProv")
		winstrKeyPath = "SOFTWARE\Microsoft\Windows NT\CurrentVersion"
		winstrValueName = "BuildLabEx"
		winstr3ValueName = "UBR"
		winstr2ValueName = "ProductName"
		oReg.GetStringValue HKEY_LOCAL_MACHINE,winstrKeyPath,winstrValueName,windo_Version
		oReg.GetDWORDValue HKEY_LOCAL_MACHINE,winstrKeyPath,winstr3ValueName,win_subbuild
		echo ("Winver" & windo_Version)
		winsubbuild = "." & win_subbuild 
		windetailbuild = windo_Version & " - "
	else
		winsubbuild = ""
		windetailbuild = ""
	end if

form_input = "software^^^" & OSName             & "^^^" _
                           & sys_version & winsubbuild       & "^^^" _
                           & ""                 & "^^^" _
                           & ""                 & "^^^" _
                           & OSInstall          & "^^^" _
                           & "Microsoft Corporation^^^" _
                           & ""                 & "^^^" _
                           & ""                 & "^^^" _
                           & "https://www.microsoft.com/windows/default.mspx"& "^^^" _
                           & windetailbuild          & "^^^ "
entry form_input,comment,objTextFile,oAdd,oComment
form_input = ""

if online = "p" then
 split_software = split(software, vbcrlf, -1, 1)
 For n = 0 to ubound(split_software) -1
  For m = n+1 to ubound(split_software)
    if lcase(split_software(m)) < lcase(split_software(n)) then
      temp = split_software(m)
      split_software(m) = split_software(n)
      split_software(n) = temp
    end if
  Next
 Next
 for g = 1 to ubound(split_software)
  oIE.document.WriteLn "<tr><td>Package Name: </td><td>" & split_software(g) & "</td></tr>"
 next
  oIE.document.WriteLn "</table>"
  oIE.document.WriteLn "</div>"
  oIE.document.WriteLn "<br style=""page-break-before:always;"" />"
end if

' FireFox Extensions
comment = "Firefox Extensions"
Echo(comment)
folder = "c:\documents and settings"
dim folder_array()
dim folder_array_2()
i = 0
'Set objWMIService = GetObject("winmgmts:\\" & strComputer & "\root\cimv2")
Set colSubfolders = objWMIService.ExecQuery ("Associators of {Win32_Directory.Name='" & folder & "'} Where AssocClass = Win32_Subdirectory ResultRole = PartComponent")
redim folder_array(colSubFolders.count)
redim folder_array_2(colSubFolders.count)
For Each objFolder in colSubfolders
  folder = split(objFolder.Name,"\",-1,1)
  moz_folder = "\\" & system_name & "\c$\documents and settings" & "\" & folder(2) & "\application data\mozilla\firefox\profiles"
  Set objFSO = CreateObject("Scripting.FileSystemObject")
  If objFSO.FolderExists(moz_folder) Then
    folder_array(i) = objFolder.Name & "\application data\mozilla\firefox\profiles"
    folder_array_2(i) = moz_folder
    i = i + 1
  end if
Next
redim preserve folder_array(i - 1)
redim preserve folder_array_2(i -1)
For i = 0 to UBound(folder_array)
  'If don't want to redim preserve above, you could comment out and do something like
  'instead.
  'if folder_array(i) = "" then
  ' exit for
  'end if
  Set colSubfolders2 = objWMIService.ExecQuery ("Associators of {Win32_Directory.Name='" & folder_array(i) & "'} Where AssocClass = Win32_Subdirectory ResultRole = PartComponent")
  For Each objFolder2 in colSubfolders2
    split_folder = split(objFolder2.Name,"\",-1,1)
    'wscript.echo "Returned (local) directory"
    'wscript.echo objFolder2.Name
    'wscript.echo "--------------------------"
    moz_folder_2 = folder_array_2(i) & "\" & split_folder(7) & "\Extensions.rdf"
    moz_folder_3 = folder_array_2(i) & "\" & split_folder(7) & "\extensions\Extensions.rdf"
    'wscript.echo "Calculated remote filename"
    'wscript.echo moz_folder_2
    'wscript.echo "--------------------------"
    if objFSO.FileExists(moz_folder_2) then
      Set objTextFile = objFSO.OpenTextFile(moz_folder_2, 1)
      Do Until objTextFile.AtEndOfStream
        input_string = objTextFile.ReadLine
        MyPos = Instr(1, input_string, "<RDF:Description")
        if MyPos > 0 then
          Do Until objTextFile.AtEndOfStream
            input_string2 = objTextFile.ReadLine
            MyPos2 = Instr(1, input_string2, "</RDF:Description>")
            if MyPos2 > 0 then exit do
            MyArray = Split(input_string2, chr(34), -1, 1)
            if Instr(1, MyArray(0), "S1:version=") then version = MyArray(1)
            if Instr(1, MyArray(0), "S1:name=") then name = MyArray(1)
            if Instr(1, MyArray(0), "S1:description=") then description = MyArray(1)
            if Instr(1, MyArray(0), "S1:creator=") then creator = MyArray(1)
            if Instr(1, MyArray(0), "S1:homepageURL=") then homepage = MyArray(1)
          Loop
          'wscript.echo "--------------------"
          'wscript.echo "Name: Mozilla Firefox Extension - " & name
          'wscript.echo "Version: " & version
          'wscript.echo "Description: " & description
          'wscript.echo "Creator: " & creator
          'wscript.echo "Homepage: " & homepage
          if name <> "" then
            form_input = "software^^^Mozilla Firefox Extension - " & clean(name) & "^^^" _
                                      & clean(version) & "^^^" _
                                      & "^^^" _
                                      & "^^^" _
                                      & "^^^" _
                                      & clean(creator) & "^^^" _
                                      & "^^^" _
                                      & "^^^" _
                                      & clean(homepage) & "^^^" _
                                      & clean(description) & "^^^"
            entry form_input,comment,objTextFile,oAdd,oComment
          end if
          form_input = ""
          name = ""
          version = ""
          description = ""
          creator = ""
          homepage = ""
        end if
      Loop
    end if
    if objFSO.FileExists(moz_folder_3) then
      Set objTextFile = objFSO.OpenTextFile(moz_folder_3, 1)
      Do Until objTextFile.AtEndOfStream
        input_string = objTextFile.ReadLine
        MyPos = Instr(1, input_string, "<RDF:Description")
        if MyPos > 0 then
          Do Until objTextFile.AtEndOfStream
            input_string2 = objTextFile.ReadLine
            MyPos2 = Instr(1, input_string2, "</RDF:Description>")
            if MyPos2 > 0 then exit do
            MyArray = Split(input_string2, chr(34), -1, 1)
            if Instr(1, MyArray(0), "em:version=") then version = MyArray(1)
            if Instr(1, MyArray(0), "em:name=") then name = MyArray(1)
            if Instr(1, MyArray(0), "em:description=") then description = MyArray(1)
            if Instr(1, MyArray(0), "em:creator=") then creator = MyArray(1)
            if Instr(1, MyArray(0), "em:homepageURL=") then homepage = MyArray(1)
          Loop
          'wscript.echo "--------------------"
          'wscript.echo "Name: Mozilla Firefox Extension - " & name
          'wscript.echo "Version: " & version
          'wscript.echo "Description: " & description
          'wscript.echo "Creator: " & creator
          'wscript.echo "Homepage: " & homepage
          if name <> "" then
            form_input = "software^^^Mozilla Firefox Extension - " & clean(name) & "^^^" _
                                      & clean(version) & "^^^" _
                                      & "^^^" _
                                      & "^^^" _
                                      & "^^^" _
                                      & clean(creator) & "^^^" _
                                      & "^^^" _
                                      & "^^^" _
                                      & clean(homepage) & "^^^" _
                                      & clean(description) & "^^^"
            entry form_input,comment,objTextFile,oAdd,oComment
          end if
          form_input = ""
          name = ""
          version = ""
          description = ""
          creator = ""
          homepage = ""
        end if
      Loop
    end if
  Next
Next

end if
' End of software audit section



'''''''''''''''''''''''''''
'Windows Firewall Settings '
'''''''''''''''''''''''''''

' Skipping if audited system is not WinXp SP2+, W2k3 SP1+, Vista, W2k8 or Seven
if ((SystemBuildNumber = "2600" AND CInt(ServicePack) > 1) OR (SystemBuildNumber = "3790" AND CInt(ServicePack) > 0) OR (CInt(SystemBuildNumber) >= 6000)) then
  comment = "Windows Firewall Settings"
  Echo(comment)
  On Error Resume Next
  ' Domain Settings
  strKeyPath = "SYSTEM\CurrentControlSet\Services\SharedAccess\Parameters\FirewallPolicy\DomainProfile"
  strValueName = "EnableFirewall"
  oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,dm_EnFirewall
  if isnull(dm_EnFirewall) then dm_EnFirewall = "" end if
  strValueName = "DisableNotifications"
  oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,dm_DisNotifications
  if isnull(dm_DisNotifications) then dm_DisNotifications = "" end if
  strValueName = "DoNotAllowExceptions"
  oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,dm_DNExceptions
  if isnull(dm_DNExceptions) then dm_DNExceptions = "" end if
  ' Non-Domain Settings
  strKeyPath = "SYSTEM\CurrentControlSet\Services\SharedAccess\Parameters\FirewallPolicy\StandardProfile"
  strValueName = "EnableFirewall"
  oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,std_EnFirewall
  if isnull(std_EnFirewall) then std_EnFirewall = "" end if
  strValueName = "DisableNotifications"
  oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,std_DisNotifications
  if isnull(std_DisNotifications) then std_DisNotifications = "" end if
  strValueName = "DoNotAllowExceptions"
  oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,std_DNExceptions
  if isnull(std_DNExceptions) then std_DNExceptions = "" end if
  form_input = "system11^^^" & clean(dm_EnFirewall) & "^^^" _
                             & clean(dm_DisNotifications) & "^^^" _
                             & clean(dm_DNExceptions) & "^^^" _
                             & clean(std_EnFirewall) & "^^^" _
                             & clean(std_DisNotifications) & "^^^" _
                             & clean(std_DNExceptions) & "^^^"
  entry form_inpuroot\SecurityCenter2t,comment,objTextFile,oAdd,oComment
  form_input = ""
  strKeyPath = "SYSTEM\CurrentControlSet\Services\SharedAccess\Parameters\FirewallPolicy\StandardProfile\AuthorizedApplications\List"
  oReg.EnumValues HKEY_LOCAL_MACHINE,strKeyPath,arrSubKeys
  For Each subkey In arrSubKeys
    if subkey <> "" then
      oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,subKey,key
      value = Split(key, ":", -1, 1)
      if InStr(value(0),"%windir%") <> 0 Then
        application_path = clean(value(0))
        application_remote_address = clean(value(1))
        application_enabled = clean(value(2))
        application_name = clean(value(3))
      else
        application_path = value(0) & ":" & value(1)
        application_path = clean(application_path)
        application_remote_address = clean(value(2))
        application_enabled = clean(value(3))
        application_name = clean(value(4))
      end if
      form_input = "fire_app^^^" & application_name           & "^^^" _
                                 & application_path           & "^^^" _
                                 & application_remote_address & "^^^" _
                                 & application_enabled        & "^^^" _
                                 & "Standard"                 & "^^^"
      entry form_input,comment,objTextFile,oAdd,oComment
      form_input = ""
    end if
  Next
  strKeyPath = "SYSTEM\CurrentControlSet\Services\SharedAccess\Parameters\FirewallPolicy\DomainProfile\AuthorizedApplications\List"
  oReg.EnumValues HKEY_LOCAL_MACHINE,strKeyPath,arrSubKeys
  For Each subkey In arrSubKeys
    if subkey <> "" then
      oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,subKey,key
      value = Split(key, ":", -1, 1)
      if InStr(value(0),"%windir%") <> 0 Then
        application_path = clean(value(0))
        application_remote_address = clean(value(1))
        application_enabled = clean(value(2))
        application_name = clean(value(3))
      else
        application_path = value(0) & ":" & value(1)
        application_path = clean(application_path)
        application_remote_address = clean(value(2))
        application_enabled = clean(value(3))
        application_name = clean(value(4))
      end if
      form_input = "fire_app^^^" & application_name           & "^^^" _
                                 & application_path           & "^^^" _
                                 & application_remote_address & "^^^" _
                                 & application_enabled        & "^^^" _
                                 & "Domain"                   & "^^^"
      entry form_input,comment,objTextFile,oAdd,oComment
      form_input = ""
    end if
  Next
  strKeyPath = "SYSTEM\CurrentControlSet\Services\SharedAccess\Parameters\FirewallPolicy\StandardProfile\GloballyOpenPorts\List"
  oReg.EnumValues HKEY_LOCAL_MACHINE,strKeyPath,arrSubKeys
  For Each subkey In arrSubKeys
    if subkey <> "" then
      oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,subKey,key
      value = Split(key, ":", -1, 1)
      port_number = value(0)
      port_protocol = value(1)
      port_scope = value(2)
      port_enabled = value(3)
      form_input = "fire_port^^^" & port_number   & "^^^" _
                                  & port_protocol & "^^^" _
                                  & port_scope    & "^^^" _
                                  & port_enabled  & "^^^" _
                                  & "User"        & "^^^"
      entry form_input,comment,objTextFile,oAdd,oComment
    end if
  Next
  strKeyPath = "SYSTEM\CurrentControlSet\Services\SharedAccess\Parameters\FirewallPolicy\DomainProfile\GloballyOpenPorts\List"
  oReg.EnumValues HKEY_LOCAL_MACHINE,strKeyPath,arrSubKeys
  For Each subkey In arrSubKeys
    if subkey <> "" then
      oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,subKey,key
      value = Split(key, ":", -1, 1)
      port_number = value(0)
      port_protocol = value(1)
      port_scope = value(2)
      port_enabled = value(3)
      form_input = "fire_port^^^" & port_number   & "^^^" _
                                  & port_protocol & "^^^" _
                                  & port_scope    & "^^^" _
                                  & port_enabled  & "^^^" _
                                  & "Domain"      & "^^^"
      entry form_input,comment,objTextFile,oAdd,oComment
    end if
  Next
end if


comment = "CD Keys"
Echo(comment)

''''''''''''''''''''''''''''''''
'   MS CD Keys for Office 2007 '
''''''''''''''''''''''''''''''''
strKeyPath = "SOFTWARE\Microsoft\Office\12.0\Registration"
oReg.EnumKey HKEY_LOCAL_MACHINE, strKeyPath, arrSubKeys
For Each subkey In arrSubKeys
  name_2007 = get_sku_2007(subkey)
  release_type = get_release_type(subkey)
  edition_type = get_edition_type(subkey)
  path = strKeyPath & "\" & subkey
  strOffXPRU = "HKLM\" & path & "\DigitalProductId"
  subKey = "DigitalProductId"
  oReg.GetBinaryValue HKEY_LOCAL_MACHINE,path,subKey,key
  if IsNull(key) then
  else
    strOffXPRUKey=GetKey(key)
      form_input = "ms_keys^^^" & name_2007     & "^^^" _
                                & strOffXPRUKey & "^^^" _
                                & release_type  & "^^^" _
                                & edition_type  & "^^^" _
                                & "office_2007" & "^^^"
      entry form_input,comment,objTextFile,oAdd,oComment
      strOffXPRUKey = ""
      release_type = ""
      edition_type = ""
      form_input = ""
  end if
Next

'''''''''''''''''''''''''''''''''
'   MS CD Keys for Office 2013 32 Bit  '
'''''''''''''''''''''''''''''''''
strKeyPath = "SOFTWARE\Microsoft\Office\15.0\Registration"
oReg.EnumKey HKEY_LOCAL_MACHINE, strKeyPath, arrSubKeys
For Each subkey In arrSubKeys
  name_2013 = get_sku_2013(subkey)
  release_type = get_release_type(subkey)
  edition_type = get_edition_type(subkey)
  path = strKeyPath & "\" & subkey
  strOffXPRU = "HKLM\" & path & "\DigitalProductId"
  subKey = "DigitalProductId"
  oReg.GetBinaryValue HKEY_LOCAL_MACHINE,path,subKey,key
  if IsNull(key) then
  else
    strOffXPRUKey=GetKey(key)
	form_input = "ms_keys^^^" & name_2013     & "^^^" _
                                & strOffXPRUKey & "^^^" _
                                & release_type  & "^^^" _
                                & edition_type  & "^^^" _
                                & "office_2013" & "^^^"
      entry form_input,comment,objTextFile,oAdd,oComment
      strOffXPRUKey = ""
      release_type = ""
      edition_type = ""
      form_input = ""
  end if
Next


'''''''''''''''''''''''''''''''''
'   MS CD Keys for Office 2010 32 bit  '
'''''''''''''''''''''''''''''''''
strKeyPath = "SOFTWARE\Microsoft\Office\14.0\Registration"
oReg.EnumKey HKEY_LOCAL_MACHINE, strKeyPath, arrSubKeys
For Each subkey In arrSubKeys
  name_2010 = get_sku_2010(subkey)
  release_type = get_release_type(subkey)
  edition_type = get_edition_type(subkey)
  path = strKeyPath & "\" & subkey
  strOffXPRU = "HKLM\" & path & "\DigitalProductId"
  subKey = "DigitalProductId"
  oReg.GetBinaryValue HKEY_LOCAL_MACHINE,path,subKey,key
  if IsNull(key) then
  else
    strOffXPRUKey=GetKey(key)
	form_input = "ms_keys^^^" & name_2010     & "^^^" _
                                & strOffXPRUKey & "^^^" _
                                & release_type  & "^^^" _
                                & edition_type  & "^^^" _
                                & "office_2010" & "^^^"
      entry form_input,comment,objTextFile,oAdd,oComment
      strOffXPRUKey = ""
      release_type = ""
      edition_type = ""
      form_input = ""
  end if
Next

'''''''''''''''''''''''''''''''''''''''''''
'   MS CD Keys for Office 2013 from 64-bit'
'''''''''''''''''''''''''''''''''''''''''''

Subhive="SOFTWARE\Microsoft\Office\15.0\Registration" 
Set objCtx = CreateObject("WbemScripting.SWbemNamedValueSet")

objCtx.Add "__ProviderArchitecture", 64
objCtx.Add "__RequiredArchitecture", TRUE
Set objLocator = CreateObject("Wbemscripting.SWbemLocator")
Set objServices = objLocator.ConnectServer(strComputer, "root\default", strUser, strPass, "", "", wbemConnectFlagUseMaxWait, objCtx)
Set o64reg = objServices.Get("StdRegProv") 
comment = "Office 2013 Visio Project - 64 Bit"
Echo(comment)

  Set Inparams = o64reg.Methods_("EnumKey").Inparameters
  Inparams.Hdefkey = HKLM
  Inparams.Ssubkeyname = subhive
  set Outparams = o64reg.ExecMethod_("EnumKey", Inparams,,objCtx) 
  For Each strSubKey In Outparams.snames 
    key=null
	Set Inparams = o64reg.Methods_("GetStringValue").Inparameters
    Inparams.Hdefkey = HKLM
    Inparams.Ssubkeyname = Subhive & "\" & strSubKey
		Inparams.Svaluename = "DigitalProductID"
		set Outparams = o64reg.ExecMethod_("GetBinaryValue", Inparams,,objCtx)
		key=getkey2(Outparams.uValue)
	if IsNull(key) then
	else
		name_2013 = get_sku_2013(strsubkey)
		release_type = get_release_type(strsubkey)
		edition_type = get_edition_type(strsubkey)
		form_input = "ms_keys^^^" & name_2013     & "^^^" _
                                & Key & "^^^" _
                                & release_type  & "^^^" _
                                & edition_type  & "^^^" _
                                & "office_2013" & "^^^"
      entry form_input,comment,objTextFile,oAdd,oComment
      strOffXPRUKey = ""
      release_type = ""
      edition_type = ""
      form_input = ""
	end if	
  Next 


'''''''''''''''''''''''''''''''''''''''''''
'   MS CD Keys for Office 2010 from 64-bit'
'''''''''''''''''''''''''''''''''''''''''''

Subhive="SOFTWARE\Microsoft\Office\14.0\Registration" 
Set objCtx = CreateObject("WbemScripting.SWbemNamedValueSet")

objCtx.Add "__ProviderArchitecture", 64
objCtx.Add "__RequiredArchitecture", TRUE
Set objLocator = CreateObject("Wbemscripting.SWbemLocator")
Set objServices = objLocator.ConnectServer(strComputer, "root\default", strUser, strPass, "", "", wbemConnectFlagUseMaxWait, objCtx)
Set o64reg = objServices.Get("StdRegProv") 
comment = "Office 2010 Visio Project - 64 Bit"
Echo(comment)

  Set Inparams = o64reg.Methods_("EnumKey").Inparameters
  Inparams.Hdefkey = HKLM
  Inparams.Ssubkeyname = subhive
  set Outparams = o64reg.ExecMethod_("EnumKey", Inparams,,objCtx) 
  For Each strSubKey In Outparams.snames 
    key=null
	Set Inparams = o64reg.Methods_("GetStringValue").Inparameters
    Inparams.Hdefkey = HKLM
    Inparams.Ssubkeyname = Subhive & "\" & strSubKey
		Inparams.Svaluename = "DigitalProductID"
		set Outparams = o64reg.ExecMethod_("GetBinaryValue", Inparams,,objCtx)
		key=getkey(Outparams.uValue)
	if IsNull(key) then
	else
		name_2010 = get_sku_2010(strsubkey)
		release_type = get_release_type(strsubkey)
		edition_type = get_edition_type(strsubkey)
		form_input = "ms_keys^^^" & name_2010     & "^^^" _
                                & Key & "^^^" _
                                & release_type  & "^^^" _
                                & edition_type  & "^^^" _
                                & "office_2010" & "^^^"
      entry form_input,comment,objTextFile,oAdd,oComment
      strOffXPRUKey = ""
      release_type = ""
      edition_type = ""
      form_input = ""
	end if	
  Next 


''''''''''''''''''''''''''''''''
'   MS CD Keys for Office 2003 '
''''''''''''''''''''''''''''''''
strKeyPath = "SOFTWARE\Microsoft\Office\11.0\Registration"
oReg.EnumKey HKEY_LOCAL_MACHINE, strKeyPath, arrSubKeys
For Each subkey In arrSubKeys
  name_2003 = get_sku_2003(subkey)
  release_type = get_release_type(subkey)
  edition_type = get_edition_type(subkey)
  path = strKeyPath & "\" & subkey
  strOffXPRU = "HKLM\" & path & "\DigitalProductId"
  subKey = "DigitalProductId"
  oReg.GetBinaryValue HKEY_LOCAL_MACHINE,path,subKey,key
  if IsNull(key) then
  else
    strOffXPRUKey=GetKey(key)
      form_input = "ms_keys^^^" & name_2003     & "^^^" _
                                & strOffXPRUKey & "^^^" _
                                & release_type  & "^^^" _
                                & edition_type  & "^^^" _
                                & "office_2003" & "^^^"
      entry form_input,comment,objTextFile,oAdd,oComment
      strOffXPRUKey = ""
      release_type = ""
      edition_type = ""
      form_input = ""
  end if
Next


'''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
'   MS Product Keys for Windows XP, 2000, 2003, Vista, Win7 and 2008 (32-bit) '
'''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
IsOSXP = InStr(OSName, "Windows XP")
IsOS2K = InStr(OSName, "Windows 2000")
IsOS2K3 = InStr(OSName, "Server 2003")
IsOSVista = InStr(OSName, "Windows Vista")
IsOS2K8 = InStr(OSName, "2008")
IsOS7 = InStr(OSName, "Windows 7")
IsOSMicrosoft = CInt(IsOSXP + IsOS2K + IsOS2K3 + IsOSVista + IsOS2K8 + IsOS7)

if (IsOSMicrosoft > 0) then
  strXPKey=null
  path = "SOFTWARE\Microsoft\Windows NT\CurrentVersion"
  subKey = "DigitalProductId"
  oReg.GetBinaryValue HKEY_LOCAL_MACHINE,path,subKey,key
  strXPKey=GetKey(key)
  if IsNull(strXPKey) then
  else
    form_input = "ms_keys^^^" & OSName            & "^^^" _
                              & strXPKey          & "^^^" _
                              & SystemBuildNumber & "^^^" _
                              & Version           & "^^^" _
                              & "windows_xp"      & "^^^"
    entry form_input,comment,objTextFile,oAdd,oComment
    form_input = ""
  end if
end if

'''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
'   MS Product Keys for Windows XP, 2000, 2003, Vista, Win7 and 2008 (64-Bit query) '
'''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
Subhive="SOFTWARE\Microsoft\Windows NT\CurrentVersion" 
Set objCtx = CreateObject("WbemScripting.SWbemNamedValueSet")

objCtx.Add "__ProviderArchitecture", 64
objCtx.Add "__RequiredArchitecture", TRUE
Set objLocator = CreateObject("Wbemscripting.SWbemLocator")
Set objServices = objLocator.ConnectServer(strComputer, "root\default", strUser, strPass, "", "", wbemConnectFlagUseMaxWait, objCtx)
Set o64reg = objServices.Get("StdRegProv") 
comment = "Windows keys 64bit"
Echo(comment)
    key=null
	Set Inparams = o64reg.Methods_("GetStringValue").Inparameters
    Inparams.Hdefkey = HKLM
    Inparams.Ssubkeyname = Subhive
		Inparams.Svaluename = "DigitalProductID"
		set Outparams = o64reg.ExecMethod_("GetBinaryValue", Inparams,,objCtx)
		key=getkey(Outparams.uValue)
    prid=null
		Inparams.Svaluename = "ProductID"
		set Outparams = o64reg.ExecMethod_("GetStringValue", Inparams,,objCtx)
		prid=Outparams.SValue
	if IsNull(key) then
	else
		form_input = "ms_keys^^^" & OSName            & " (" & prid & ")^^^" _
								  & key	              & "^^^" _
								  & SystemBuildNumber & "^^^" _
								  & Version           & "^^^" _
								  & "windows_xp"      & "^^^"
		entry form_input,comment,objTextFile,oAdd,oComment
		form_input = ""
	end if
		key=null
		Inparams.Svaluename = "DigitalProductID4"
		set Outparams = o64reg.ExecMethod_("GetBinaryValue", Inparams,,objCtx)
		key=getkey(Outparams.uValue)
	if IsNull(key) then
	else
		form_input = "ms_keys^^^" & OSName            & " (ID4)^^^" _
								  & key	              & "^^^" _
								  & SystemBuildNumber & "^^^" _
								  & Version           & "^^^" _
								  & "windows_xp"      & "^^^"
		entry form_input,comment,objTextFile,oAdd,oComment
		form_input = ""
	end if	


'''''''''''''''''''''''''''
'   IIS Information       '
'''''''''''''''''''''''''''

If iis = "True" Then
  ' IISAdmin service installed
  comment = "IIS Info"
  Echo(comment)
  
  ' Checking if IIS WMI provider is available
  If CInt(SystemBuildNumber) >= 3790 Then
    ' System is W2k3+
    Set objWMIService_Root = GetObject("winmgmts:\\" & strComputer & "\root")
    Set colItems = objWMIService_Root.InstancesOf("__NAMESPACE")
    iis_wmi = "False"
    For Each objItem In colItems
      If objItem.Name = "MicrosoftIISv2" Then
        iis_wmi = "True"
      End If
    Next
  End If
  If iis_wmi Then 
    ' MicrosoftIISv2 WMI provider available
    If strUser <> "" and strPass <> "" Then
      Set objWMIService_IIS = wmiLocator.ConnectServer(strComputer, "\root\MicrosoftIISv2", strUser, strPass, "", "", wbemConnectFlagUseMaxWait)
      objWMIService_IIS.Security_.AuthenticationLevel = 6 'PacketPrivacy
    Else
      Set objWMIService_IIS = GetObject("winmgmts:{AuthenticationLevel=pktPrivacy}!\\" & strComputer & "\root\MicrosoftIISv2") 
    End If

    Set colItems = objWMIService_IIS.ExecQuery("SELECT * FROM IIsWebInfo",,48) 
    For Each objItem in colItems 
      iis_version = objItem.MajorIIsVersionNumber & "." & objItem.MinorIIsVersionNumber
    Next
    form_input = "system12^^^" & iis_version   & "^^^"   
    entry form_input,comment,objTextFile,oAdd,oComment
    form_input = ""

    If iis_w3svc Then
      ' Web service installed: retrieving settings
  
      Set colItems = objWMIService_IIS.ExecQuery("SELECT * FROM IIsWebServiceSetting",,48) 
      For Each objItem in colItems 
        For i = 0 to Ubound(objItem.WebSvcExtRestrictionList)
          If objItem.WebSvcExtRestrictionList(i).Access = 0 Then
            iis_web_ext_access = "Not allowed"
          Else
            iis_web_ext_access = "Allowed"
          End If
          iis_web_ext_path = objItem.WebSvcExtRestrictionList(i).FilePath
          iis_web_ext_desc = objItem.WebSvcExtRestrictionList(i).Description
          Select Case iis_web_ext_path
            Case "*.exe":  iis_web_ext_desc = "All unknown CGI extensions"
            Case "*.dll":  iis_web_ext_desc = "All unknown ISAPI extensions"
            Case Default:  
          End Select
          form_input = "iis_4^^^" & iis_web_ext_desc & "^^^" & iis_web_ext_path & "^^^" & iis_web_ext_access & "^^^" 
          entry form_input,comment,objTextFile,oAdd,oComment
          form_input = ""
        Next
      Next
    
      Set colItems = objWMIService_IIS.ExecQuery("Select * from IIsWebServerSetting",,48)
      For Each objItem in colItems
        ArgSiteIndex = objItem.Name
        ' Stripping out "w3svc/"
        SiteId = Mid(ArgSiteIndex, 7)
        iis_desc = objItem.ServerComment
        strQuery = "SELECT * FROM IIsLogModuleSetting WHERE LogModuleId = '" & objItem.LogPluginClsid & "'"
        Set colItems1 = objWMIService_IIS.ExecQuery(strQuery,,48) 
        For Each objItem1 in colItems1 
          LogFormat = Split(objItem1.Name, "/")
          iis_log_format = LogFormat(1)
        Next
        iis_log_dir = objItem.LogFileDirectory
        Select Case objItem.LogType
          Case 0:       iis_log_en = "Disabled"
          Case 1:       iis_log_en = "Enabled"
          Case Default: iis_log_en = "Undefined"
        End Select
        Select Case objItem.LogFilePeriod
          Case 0: If objItem.LogFileTruncateSize = -1 Then
                      iis_log_per = "Unlimited file size"
                  Else
                      iis_log_per = "When file size reaches " & (objItem.LogFileTruncateSize/1048576) & " MB"
                  End If 
          Case 1:       iis_log_per = "Daily"
          Case 2:       iis_log_per = "Weekly"
          Case 3:       iis_log_per = "Monthly"
          Case 4:       iis_log_per = "Hourly"
          Case Default: iis_log_per = "Undefined"
        End Select

        For i = 0 to Ubound(objItem.ServerBindings)
          If objItem.ServerBindings(i).IP = "" Then
            iis_ip = "All Unassigned"
          Else
            iis_ip =  objItem.ServerBindings(i).IP
          End If
          iis_port = objItem.ServerBindings(i).Port
          If objItem.ServerBindings(i).Hostname = "" Then
            iis_host = "none"
          Else
            iis_host = objItem.ServerBindings(i).Hostname
          End If
          form_input = "iis_3^^^" & SiteId    & "^^^" _
                                  & iis_ip    & "^^^" _
                                  & iis_port  & "^^^" _
                                  & iis_host  & "^^^"
          entry form_input,comment,objTextFile,oAdd,oComment
          form_input = ""
        Next

        For i = 0 to Ubound(objItem.SecureBindings)
          If objItem.SecureBindings(i).IP = "" Then
            iis_sec_ip = "No secure bindings"
          Else
            iis_sec_ip = objItem.SecureBindings(i).IP
          End If
          iis_sec_port = objItem.SecureBindings(i).Port
        Next

        strQuery = "SELECT * FROM IIsWebServer WHERE Name = '" & ArgSiteIndex & "'"
        Set colItems3 = objWMIService_IIS.ExecQuery(strQuery,,48)
        For Each objItem3 in colItems3
          Select Case objItem3.ServerState
            Case 1:       iis_site_state = "Starting"
            Case 2:       iis_site_state = "Running"
            Case 3:       iis_site_state = "Stopping"
            Case 4:       iis_site_state = "Stopped"
            Case 5:       iis_site_state = "Pausing"
            Case 6:       iis_site_state = "Paused"
            Case 7:       iis_site_state = "Continuing"
            Case Default: iis_site_state = "Unknown"
          End Select
        Next

        Set colItems2 = objWMIService_IIS.ExecQuery("SELECT * FROM IIsWebVirtualDirSetting",,48)
        For Each objItem2 in colItems2
          If (UCase(objItem2.Name) = ArgSiteIndex & "/ROOT") Then
            iis_path = objItem2.Path
            iis_site_app_pool = objItem2.AppPoolId
            iis_dir_browsing = objItem2.EnableDirBrowsing
            iis_site_anonymous_user = objItem2.AnonymousUserName
            iis_site_anonymous_auth = objItem2.AuthAnonymous
            iis_site_basic_auth = objItem2.AuthBasic
            iis_site_ntlm_auth = objItem2.AuthNTLM
            iis_site_ssl_en = objItem2.AccessSSL
            iis_site_ssl128_en = objItem2.AccessSSL128
            iis_def_doc = objItem2.DefaultDoc
          End If

          If (InStr(Ucase(objItem2.Name),ArgSiteIndex & "/ROOT/")) Then
            VirtualDir = Split(objItem2.Name, "/")
            iis_vd_name = VirtualDir(3)
            iis_vd_path = objItem2.Path
            form_input = "iis_2^^^" & SiteId       & "^^^" _
                                    & iis_vd_name  & "^^^" _
                                    & iis_vd_path  & "^^^"
            entry form_input,comment,objTextFile,oAdd,oComment
            form_input = ""
          End If
        Next

        form_input = "iis_1^^^" & SiteId                    & "^^^"   & iis_desc                  & "^^^" _
                                & iis_log_en                & "^^^"   & iis_log_dir               & "^^^" _
                                & iis_log_format            & "^^^"   & iis_log_per               & "^^^" _
                                & iis_path                  & "^^^"   & iis_dir_browsing          & "^^^" _
                                & iis_def_doc               & "^^^"   & iis_sec_ip                & "^^^" _
                                & iis_sec_port              & "^^^"   & iis_site_state            & "^^^" _
                                & iis_site_app_pool         & "^^^"   & iis_site_anonymous_user   & "^^^" _
                                & iis_site_anonymous_auth   & "^^^"   & iis_site_basic_auth       & "^^^" _
                                & iis_site_ntlm_auth        & "^^^"   & iis_site_ssl_en           & "^^^" _
                                & iis_site_ssl128_en        & "^^^"
        entry form_input,comment,objTextFile,oAdd,oComment
        form_input = ""
      Next ' objItem in colItems = objWMIService_IIS.ExecQuery("Select * from IIsWebServerSetting",,48)
    End If 'iis_w3svc
  Else
    ' IIS WMI provider not available: trying IIS ADSI provider
    ' Will work only if IIS is installed on the auditing host and inetinfo.exe is allowed on the audited host's firewall
    
    full_path = "\\" & system_name & "\admin$" & "\system32\inetsrv\inetinfo.exe"
    iis_version = Left(objFSO.GetFileVersion(full_path), 3)

    form_input = "system12^^^" & iis_version   & "^^^"   
    entry form_input,comment,objTextFile,oAdd,oComment
    form_input = ""

    If iis_w3svc Then
      ' Web service installed: retrieving settings
      Err.Clear
      Dim objWWW
      Set objWWW = GetObject("IIS://" & system_name & "/w3svc")
      ' Verify that the IIS ADSI provider is available
      If Err <> 0 Then
        '
      Else
        For Each WebSiteID in objWWW
          If IsNumeric(WebSiteID.Name) Then
            '  Initialize error checking
            On Error Resume Next
            ' Initialize variables
            Dim ArgPhysicalServer, ArgSiteIndex, ArgFilter, ArgVirtualDirectory
            Dim ArgsCounter, ArgNum
            Dim objWebServer, objWebRootDir, objWebLog, objWebFilter, objWebVirtualDir
            Dim BindingArray, strServerBinding, strSecureBinding
            Dim SecurityDescriptor, DiscretionaryAcl, IPSecurity
            Dim strPath, Item, Member, VirDirCounter, Counter

            '  Default values
            ArgNum = 0

            ArgPhysicalServer = system_name
            ArgSiteIndex = WebSiteID.Name

            ' Specify and bind to the administrative objects
            Set objWebServer = GetObject("IIS://" & ArgPhysicalServer & "/w3svc/" & ArgSiteIndex)
            Set objWebRootDir = GetObject("IIS://" & ArgPhysicalServer & "/w3svc/" & ArgSiteIndex & "/Root")

            ' do enumerate for this websiteID - will end if at end of function
            ' ----- Web Site Tab -------
            ' ---------------
            iis_desc = objWebServer.ServerComment
            iis_site_state = objWebServer.Status
            Select Case iis_site_state
              Case 1:       iis_site_state = "Starting"
              Case 2:       iis_site_state = "Running"
              Case 3:       iis_site_state = "Stopping"
              Case 4:       iis_site_state = "Stopped"
              Case 5:       iis_site_state = "Pausing"
              Case 6:       iis_site_state = "Paused"
              Case 7:       iis_site_state = "Continuing"
              Case Default: iis_site_state = "Unknown"
            End Select
            iis_site_anonymous_user = objWebServer.AnonymousUserName
            iis_site_anonymous_auth = objWebServer.AuthAnonymous
            iis_site_basic_auth = objWebServer.AuthBasic
            iis_site_ntlm_auth = objWebServer.AuthNTLM
            iis_site_ssl_en = objWebServer.AccessSSL
            iis_site_ssl128_en = objWebServer.AccessSSL128
            For Each Item in objWebServer.ServerBindings
              strServerBinding = Item
              BindingArray = Split(strServerBinding, ":", -1, 1)
              If BindingArray(0) = "" Then
                iis_ip = "All Unassigned"
              Else
                iis_ip =  BindingArray(0)
              End If
              iis_port =  BindingArray(1)
              If BindingArray(2) = "" Then
                iis_host = "None"
              Else
                iis_host = BindingArray(2)
              End If
              form_input = "iis_3^^^" & ArgSiteIndex & "^^^" _
                                      & iis_ip       & "^^^" _
                                      & iis_port     & "^^^" _
                                      & iis_host     & "^^^"
              entry form_input,comment,objTextFile,oAdd,oComment
              form_input = ""
            Next
            iis_sec_ip = "No Secure Bindings"
            iis_sec_port = ""
            For Each Item in objWebServer.SecureBindings
              strSecureBinding = Item
              BindingArray = Split(strSecureBinding, ":", -1, 1)
              If BindingArray(0) = "" Then
                iis_sec_ip = "All Unassigned"
              Else
                iis_sec_ip = BindingArray(0)
              End If
              iis_sec_port = BindingArray(1)
            Next
            If objWebServer.LogType = 0 Then
              iis_log_en =  "Disabled"
            Else
              iis_log_en =  "Enabled"
              Set objWebLog = GetObject("IIS://" & ArgPhysicalServer & "/logging")
              For Each Item in objWebLog
                If objWebServer.LogPluginCLSID = Item.LogModuleID Then
                  iis_log_format = Item.Name
                  objWebLog = Item.Name
                End If
              Next
              If objWebServer.LogFilePeriod = 0 Then
                If objWebServer.LogFileTruncateSize = -1 Then
                  iis_log_per = "Unlimited file size"
                Else
                  iis_log_per = "When file size reaches " & (objWebServer.LogfileTruncateSize/1048576) & " MB"
                End If
              End If
              If objWebServer.LogFilePeriod = 1 Then
                iis_log_per = "Daily"
              Else
                If objWebServer.LogFilePeriod = 2 Then
                  iis_log_per = "Weekly"
                Else
                  If objWebServer.LogFilePeriod =3 Then
                    iis_log_per = "Monthly"
                  End If
                End If
              End If
              iis_log_dir = objWebServer.LogFileDirectory
            End If
            ' ----- Home Directory Tab -------
            ' ----------------
            If objWebRootDir.HttpRedirect <> "" Then
              '
            Else
              strPath = objWebRootDir.Path
              strPath = Left(strPath, 2)
              iis_path = objWebRootDir.Path
              iis_dir_browsing =  objWebRootDir.EnableDirBrowsing
            End If
            ' ----- Documents Tab -------
            ' -----------------
            If objWebRootDir.EnableDefaultDoc = False Then
              iis_def_doc = "False"
            Else
              iis_def_doc = objWebRootDir.DefaultDoc
            End If
            form_input = "iis_1^^^" & ArgSiteIndex              & "^^^"   & iis_desc                  & "^^^" _
                                    & iis_log_en                & "^^^"   & iis_log_dir               & "^^^" _
                                    & iis_log_format            & "^^^"   & iis_log_per               & "^^^" _
                                    & iis_path                  & "^^^"   & iis_dir_browsing          & "^^^" _
                                    & iis_def_doc               & "^^^"   & iis_sec_ip                & "^^^" _
                                    & iis_sec_port              & "^^^"   & iis_site_state            & "^^^" _
                                    & iis_site_app_pool         & "^^^"   & iis_site_anonymous_user   & "^^^" _
                                    & iis_site_anonymous_auth   & "^^^"   & iis_site_basic_auth       & "^^^" _
                                    & iis_site_ntlm_auth        & "^^^"   & iis_site_ssl_en           & "^^^" _
                                    & iis_site_ssl128_en        & "^^^"
            entry form_input,comment,objTextFile,oAdd,oComment
            form_input = ""
            ' ------------------
            ' --- Enumerating Virtual Directories ----
            ' ------------------
            VirDirCounter = 0
            For Each Item in objWebRootDir
              If Item.Class = "IIsWebVirtualDir" Then
                ArgVirtualDirectory = Item.Name
                Set objWebVirtualDir = GetObject("IIS://" & ArgPhysicalServer & "/w3svc/" & ArgSiteIndex & "/Root/" & ArgVirtualDirectory)
                iis_vd_name = Item.Name
                iis_vd_path = objWebVirtualDir.Path
                form_input = "iis_2^^^" & ArgSiteIndex       & "^^^" _
                                        & clean(iis_vd_name) & "^^^" _
                                        & clean(iis_vd_path) & "^^^"
                entry form_input,comment,objTextFile,oAdd,oComment
                form_input = ""
                VirDirCounter = VirDirCounter + 1
              End If
            Next
          End If 'IsNumeric(WebSiteID.Name)
        next 'WebSiteID in objWWW
      End If 'Err <> 0
    End If 'iis_w3svc
  End If ' iis_wmi
Else
  ' End of iis = True
End If

'
'''''''''''''''''''''''''''
'ODBC Connections '
'''''''''''''''''''''''''''

' Skipping if audited system is not WinXp, W2k3, Vista, W2k8 or Seven
if ((SystemBuildNumber = "2600") OR (SystemBuildNumber = "3790") OR (CInt(SystemBuildNumber) >= 6000)) then
  comment = "ODBC Connections (64-Bit, System DSN only"
  Echo(comment)
  On Error Resume Next

  strKeyPath = "SOFTWARE\ODBC\ODBC.INI\ODBC Data Sources"
  oReg.EnumValues HKEY_LOCAL_MACHINE,strKeyPath,arrSubKeys

  For Each subkey In arrSubKeys
    comment = subkey
    Echo("Name: " & comment)
    odbc_name = subkey
    strKeyPath1 = "SOFTWARE\ODBC\ODBC.INI\" & subkey
    odsn = strKeyPath1
	Echo(odsn)
    oReg.EnumValues HKEY_LOCAL_MACHINE, strKeyPath1, arrValueNames, arrValueTypes

    detvalues = ""
	For i=0 To UBound(arrValueNames)
        oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath1,_
        arrValueNames(i),strValue
        ' Echo(arrValueNames(i) & ": " & strValue)
		detvalues = detvalues & arrValueNames(i) & ": " & strValue & " "
    Next

    form_input = "odbc^^^" & clean(odsn)      & " ^^^" & clean(detvalues) & "^^^"
	entry form_input,comment,objTextFile,oAdd,oComment
	form_input = ""

  Next

end if


'''''''''''''''''''''''''''
'Automatic Updating Settings '
'''''''''''''''''''''''''''

' Check if system is Win2k+. Build Number: Win2k-->2195, Win98-->2222, WinME-->3000
If (CInt(SystemBuildNumber) >= "2195" And Not SystemBuildNumber = "2222" And Not SystemBuildNumber = "3000") Then
  comment = "Automatic Updating Settings"
  Echo(comment)
  On Error Resume Next
  strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate\AU"
  strValueName = "NoAutoUpdate"
  oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,NoAutoUpdate
  Select Case Clean(NoAutoUpdate)
    Case "0"     au_gpo_configured = "True"
                 au_enabled = "True"
    Case "1"     au_gpo_configured = "True"
                 au_enabled = "False"
    Case Else    au_gpo_configured = "False"
                 au_enabled = ""
  End Select
  
  If (au_gpo_configured = "True" And au_enabled = "True") Then
    ' AU client is configured by a GPO and AU is enabled
    strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate\AU"
    strValueName = "AUOptions"
    oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,AUOptions
    Select Case AuOptions
      Case "2"       au_behaviour = "Notify before download and install"
      Case "3"       au_behaviour = "Automatically download and notify of installation"
      Case "4"       au_behaviour = "Automatic download and scheduled installation"
      Case "5"       au_behaviour = "Automatic Updates is required, but admins can configure it"
      Case Else      au_behaviour = "Unknown"
    End Select

    If AuOptions = "4" Then 
      ' Installation is scheduled
      strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate\AU"
      strValueName = "ScheduledInstallDay"
      oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,ScheduledInstallDay
      Select Case ScheduledInstallDay
        Case "0"     au_sched_install_day = "Every Day"
        Case "1"     au_sched_install_day = "Every Sunday"
        Case "2"     au_sched_install_day = "Every Monday"
        Case "3"     au_sched_install_day = "Every Tuesday"
        Case "4"     au_sched_install_day = "Every Wednesday"
        Case "5"     au_sched_install_day = "Every Thursday"
        Case "6"     au_sched_install_day = "Every Friday"
        Case "7"     au_sched_install_day = "Every Saturday"
        Case Else    au_sched_install_day = "Unknown"
      End select
  
      strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate\AU"
      strValueName = "ScheduledInstallTime"
      oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,ScheduledInstallTime
      au_sched_install_time = ScheduledInstallTime & ":00"
    End If
  End If ' au_gpo_configured And au_enabled
  
  If (au_gpo_configured = "False" Or AuOptions = "5") Then
    ' AU client is not configured by a GPO or a GPO lets admins choose settings: checking AU system properties
    strKeyPath = "SOFTWARE\Microsoft\Windows\CurrentVersion\WindowsUpdate\Auto Update"
    strValueName = "AUOptions"
    oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,AuOptions
    Select Case AuOptions
      Case "1"       au_behaviour = "Automatic Updating disabled"
      Case "2"       au_behaviour = "Notify before download and install"
      Case "3"       au_behaviour = "Automatically download and notify of installation"
      Case "4"       au_behaviour = "Automatic download and scheduled installation"
      Case Else      au_behaviour = "Unknown"
    End Select
    If AuOptions = "4" Then 
      strKeyPath = "SOFTWARE\Microsoft\Windows\CurrentVersion\WindowsUpdate\Auto Update"
      strValueName = "ScheduledInstallDay"
      oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,ScheduledInstallDay
      Select Case ScheduledInstallDay
        Case "0"     au_sched_install_day = "Every Day"
        Case "1"     au_sched_install_day = "Every Sunday"
        Case "2"     au_sched_install_day = "Every Monday"
        Case "3"     au_sched_install_day = "Every Tuesday"
        Case "4"     au_sched_install_day = "Every Wednesday"
        Case "5"     au_sched_install_day = "Every Thursday"
        Case "6"     au_sched_install_day = "Every Friday"
        Case "7"     au_sched_install_day = "Every Saturday"
        Case Else    au_sched_install_day = "Unknown"
      End select
      strKeyPath = "SOFTWARE\Microsoft\Windows\CurrentVersion\WindowsUpdate\Auto Update"
      strValueName = "ScheduledInstallTime"
      oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,ScheduledInstallTime
      au_sched_install_time = ScheduledInstallTime & ":00"
    End If
  End If 'Not au_gpo_configured Or AuOptions = "5"
  
  If (au_gpo_configured = "True" And au_enabled = "True") Then
    ' AU client is configured by a GPO and AU is enabled

    strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate\AU"
    strValueName = "UseWUServer"
    oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,UseWUServer
    au_use_wuserver = Clean(UseWUServer)
    Select Case au_use_wuserver
      Case "0"  au_use_wuserver = "False"
      Case "1"  au_use_wuserver = "True"
    End Select

    If au_use_wuserver = "True" Then
      strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate"
      strValueName = "WUServer"
      oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,WUServer
      au_wuserver = Clean(WUServer)
  
      strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate"
      strValueName = "WUStatusServer"
      oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,WUStatusServer
      au_wustatusserver = Clean(WUStatusServer)
    End If

    strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate"
    strValueName = "TargetGroup"
    oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,TargetGroup
    au_target_group = Clean(TargetGroup)

    strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate"
    strValueName = "ElevateNonAdmins"
    oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,ElevateNonAdmins
    au_elevate_nonadmins = Clean(ElevateNonAdmins)
    Select Case au_elevate_nonadmins
      Case "0" au_elevate_nonadmins = "False"
      Case "1" au_elevate_nonadmins = "True"      
    End Select

    strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate\AU"
    strValueName = "AutoInstallMinorUpdates"
    oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,AutoInstallMinorUpdates
    au_auto_install = Clean(AutoInstallMinorUpdates)
    Select Case au_auto_install
      Case "0" au_auto_install = "False"
      Case "1" au_auto_install = "True"
    End Select

    strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate\AU"
    strValueName = "DetectionFrequency"
    oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,DetectionFrequency
    au_detection_frequency = Clean(DetectionFrequency)

    strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate\AU"
    strValueName = "RebootRelaunchTimeout"
    oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,RebootRelaunchTimeout
    au_reboot_timeout = Clean(RebootRelaunchTimeout)

    strKeyPath = "Software\Policies\Microsoft\Windows\WindowsUpdate\AU"
    strValueName = "NoAutoRebootWithLoggedOnUsers"
    oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,NoAutoReboot
    au_noautoreboot = Clean(NoAutoReboot)
    Select Case au_noautoreboot
      Case "0"  au_noautoreboot = "False"
      Case "1"  au_noautoreboot = "True"
    End Select


  End If ' 2nd au_gpo_configured And au_enabled
  
  form_input = "auto_upd^^^" & au_gpo_configured      & "^^^"  & au_enabled              & "^^^"  & au_behaviour             & "^^^" _
                             & au_sched_install_day   & "^^^"  & au_sched_install_time   & "^^^"  & au_use_wuserver          & "^^^" _
                             & au_wuserver            & "^^^"  & au_wustatusserver       & "^^^"  & au_target_group          & "^^^" _
                             & au_elevate_nonadmins   & "^^^"  & au_auto_install         & "^^^"  & au_detection_frequency   & "^^^" _
                             & au_reboot_timeout      & "^^^"  & au_noautoreboot         & "^^^" 
  entry form_input,comment,objTextFile,oAdd,oComment
  form_input = ""

End If 'Win2k+






if online = "n" then
   objTextFile.Close
end if

end_time = Timer
elapsed_time = end_time - start_time
Echo("Audit.vbs Execution Time: " & int(elapsed_time) & " seconds.")


if online = "ie" then
  ie_time = Timer
  '''''''''''''''''''''''''''''''''''''''''
  ' Create an IE instance for output into '
  '''''''''''''''''''''''''''''''''''''''''
  Dim ie
  Set ie = CreateObject("InternetExplorer.Application")
  ie.navigate ie_form_page
  Do Until IE.readyState = 4 : WScript.sleep(200) : Loop
  if ie_visible = "y" then
    ie.visible= True
  else
    ie.visible = False
  end if
  Dim oUser
  Dim oPwd
  Dim oDoc
  Set oDoc = IE.document
  Set oAdd = oDoc.getElementById("add")
  '''''''''''''''''''''''''''''''''
  ' Output UUID & Timestamp to IE '
  '''''''''''''''''''''''''''''''''
  oAdd.value = oAdd.value + form_total + vbcrlf
  if ie_auto_submit = "y" then
    IE.Document.All("submit").Click
    Do Until IE.readyState = 4 : WScript.sleep(2000) : Loop
  end if

  if ie_auto_close = "y" then
    Do Until IE.readyState = 4 : WScript.sleep(5000) : Loop
    WScript.sleep(5000)
    ie.Quit
  end if

  end_time = Timer
  elapsed_time = end_time - ie_time
  Echo("IE Execution Time: " & int(elapsed_time) & " seconds.")

end if ' End of IE

if online = "yesxml" then
   url = non_ie_page
   Err.clear
   XmlObj = "ServerXMLHTTP"
   Set objHTTP = WScript.CreateObject("MSXML2.ServerXMLHTTP.3.0")
   objHTTP.SetOption 2, 13056  ' Ignore all SSL errors
   objHTTP.Open "POST", url, False
   objHTTP.setRequestHeader "Content-Type","application/x-www-form-urlencoded"
   if utf8 = "y" then
     objHTTP.Send "add=" + urlEncode(form_total + vbcrlf)
   else
     objHTTP.Send "add=" + escape(Deconstruct(form_total + vbcrlf))
   end if
   if (Err.Number <> 0 or objHTTP.status <> 200) then
     Err.clear
     XmlObj = "XMLHTTP"
     Set objHTTP = WScript.CreateObject("MSXML2.XMLHTTP")
     objHTTP.Open "POST", url, False
     objHTTP.setRequestHeader "Content-Type","application/x-www-form-urlencoded"
     if utf8 = "y" then
       objHTTP.Send "add=" + urlEncode(form_total + vbcrlf)
     else
       objHTTP.Send "add=" + escape(Deconstruct(form_total + vbcrlf))
     end if
   end if
	 Echo( "*** Open-Audit-Ziel-URL : " & url)
	 if (Err.Number <> 0 or objHTTP.status <> 200) then
		 Echo("Unable to send XML to server using " & XmlObj & " - HTTP Response: " & objHTTP.status & " (" & objHTTP.statusText & ") - Error " & Err.Number & " " & Err.Description)
	 else
		 Echo("XML sent to server using " & XmlObj & ": " & objHTTP.status & " (" & objHTTP.statusText & ")")
	 end if
     Err.clear
end if

if online = "p" then
  oIE.document.WriteLn "</div>"
end if

end_time = Timer
elapsed_time = end_time - start_time
Echo("Total Execution Time: " & int(elapsed_time) & " seconds.") 
WScript.sleep(2500)
' database.close conn

End Function

Function Deconstruct(strIn)
  strOut = ""
  For x = 1 to Len(strIn)
    If Asc(Mid(strIn,x,1)) > 128 Then
      strOut = strOut & "&#" & Asc(Mid(strIn,x,1))
    Else
      strOut = strOut & Mid(strIn,x,1)
    End If
  Next

  Deconstruct = strOut
End Function




Function HostDrives(sHost)
CONST LOCAL_DISK = 3
Dim Disks, Disk, aTmp(), i
Set Disks = objWMIService.ExecQuery ("Select * from Win32_LogicalDisk where DriveType=" & LOCAL_DISK)
ReDim aTmp(Disks.Count - 1)
i = -1
For Each Disk in Disks
   i = i + 1
   aTmp(i) = Disk.DeviceID
Next
HostDrives = aTmp
End Function




Function DrivePartition(sHost, sDrive)
Dim Associator, Associators
Set Associators = objWMIService.ExecQuery ("Associators of {Win32_LogicalDisk.DeviceID=""" & sDrive & """} WHERE ResultClass=CIM_DiskPartition")
On Error Resume Next
For Each Associator in Associators
   DrivePartition = Associator.Name
   If Err.Number <>0 then Err.Clear
Next
End Function

Function getKey(Key)
    Const KeyOffset = 52
    isWin8 = (Key(66) \ 6) And 1
    Key(66) = (Key(66) And &HF7) Or ((isWin8 And 2) * 4)
    i = 24
    Chars = "BCDFGHJKMPQRTVWXY2346789"
    Do
        Cur = 0
        X = 14
        Do
            Cur = Cur * 256
            Cur = Key(X + KeyOffset) + Cur
            Key(X + KeyOffset) = (Cur \ 24)
            Cur = Cur Mod 24
            X = X -1
        Loop While X >= 0
        i = i -1
        KeyOutput = Mid(Chars, Cur + 1, 1) & KeyOutput
        Last = Cur
    Loop While i >= 0
    If (isWin8 = 1) Then
        keypart1 = Mid(KeyOutput, 2, Last)
        insert = "N"
        KeyOutput = Replace(KeyOutput, keypart1, keypart1 & insert, 2, 1, 0)
        If Last = 0 Then KeyOutput = insert & KeyOutput
    End If
    a = Mid(KeyOutput, 1, 5)
    b = Mid(KeyOutput, 6, 5)
    c = Mid(KeyOutput, 11, 5)
    d = Mid(KeyOutput, 16, 5)
    e = Mid(KeyOutput, 21, 5)
    getKey = a & "-" & b & "-" & c & "-" & d & "-" & e
End Function


Function GetKey2(rpk)
Const rpkOffset=52:i=28
szPossibleChars="BCDFGHJKMPQRTVWXY2346789"
Do 'Rep1
  dwAccumulator=0 : j=14
  Do
    dwAccumulator=dwAccumulator*256
    dwAccumulator=rpk(j+rpkOffset)+dwAccumulator
    rpk(j+rpkOffset)=(dwAccumulator\24) and 255
    dwAccumulator=dwAccumulator Mod 24
    j=j-1
  Loop While j>=0
  i=i-1 :
  szProductKey=mid(szPossibleChars,dwAccumulator+1,1)&szProductKey
  if (((29-i) Mod 6)=0) and (i<>-1) then
    i=i-1 : szProductKey="-"&szProductKey
  End If
Loop While i>=0 'Goto Rep1
GetKey2=szProductKey
End Function

Function IsWMIConnectible(strComputer, strUser, strPass)
'
'Set objWMIService = GetObject("winmgmts:\\" & strComputer &"\root\cimv2") '(*)
Set objSWbemLocator = CreateObject("WbemScripting.SWbemLocator")
'Set objSWbemServices = objSWbemLocator.ConnectServer(strComputer, "root\cimv2", strUser, strPass, "", "", &h80)

' The ConnectServer call is guaranteed to return in 2 minutes or less.
' The wbemConnectFlagUseMaxWait flag prevents the script from hanging indefinitely if the connection cannot be established
Set objSWbemServices = objSWbemLocator.ConnectServer(strComputer, "root\cimv2", strUser, strPass, "", "", wbemConnectFlagUseMaxWait)
Set colSWbemObjectSet = objSWbemServices.InstancesOf("Win32_Service")
'
'For Each objSWbemObject In colSWbemObjectSet
'    Wscript.Echo "Name: " & objSWbemObject.Name
'Next
If Err.Number > 0 Then
'WScript.Echo strComputer & " - Unable to connect to WMI. Error ="  & Err.Number & "-" & Err.Description
Err.Clear
IsWMIConnectible = False
Else
' WScript.Echo strComputer & "Connect to WMI: OK!"
IsWMIConnectible = True
End If
'WScript.Echo strComputer & " - Unable to connect to WMI. Error ="  & Err.Number & "-" & Err.Description
Err.Clear

End Function

Function IsConnectible(sHost,iPings,iTO)
 if sHost = "." then
   IsConnectible = True
 else
   If iPings = "" Then iPings = 2
   If iTO = "" Then iTO = 750
    Set oShell = CreateObject("WScript.Shell")
   sProduct=UCase(oShell.RegRead("HKLM\SOFTWARE\Microsoft\Windows NT\CurrentVersion\ProductName"))
   If instr(sProduct, "VISTA")>0 Then
     Set oExCmd = oShell.Exec("ping -n " & iPings & " -w " & iTO & " " & sHost & " -4")
   Else
     Set oExCmd = oShell.Exec("ping -n " & iPings & " -w " & iTO & " " & sHost)
   End if
   Select Case InStr(UCase(oExCmd.StdOut.Readall),"TTL=")
   '    Select Case InStr(oExCmd.StdOut.Readall,"TTL=")
      Case 0 IsConnectible = False
      Case Else IsConnectible = True
    End Select
  end if
End Function

function get_sku_2013(subkey)
  vers = mid(subkey,11,4)
if vers = "0011" then vers_name = "Microsoft Office Professional Plus 2013" end if
if vers = "0012" then vers_name = "Microsoft Office Standard 2013" end if
if vers = "0013" then vers_name = "Microsoft Office Basic 2013" end if
if vers = "0014" then vers_name = "Microsoft Office Professional 2013" end if
if vers = "0015" then vers_name = "Microsoft Office Access 2013" end if
if vers = "0016" then vers_name = "Microsoft Office Excel 2013" end if
if vers = "0017" then vers_name = "Microsoft Office SharePoint Designer 2013" end if
if vers = "0018" then vers_name = "Microsoft Office PowerPoint 2013" end if
if vers = "0019" then vers_name = "Microsoft Office Publisher 2013" end if
if vers = "001A" then vers_name = "Microsoft Office Outlook 2013" end if
if vers = "001B" then vers_name = "Microsoft Office Word 2013" end if
if vers = "001C" then vers_name = "Microsoft Office Access Runtime 2013" end if
if vers = "0020" then vers_name = "Microsoft Office Compatibility Pack for Word, Excel, and PowerPoint 2013 File Formats" end if
if vers = "0026" then vers_name = "Microsoft Expression Web" end if
if vers = "0029" then vers_name = "Microsoft Office Excel 2013" end if
if vers = "002B" then vers_name = "Microsoft Office Word 2013" end if
if vers = "002E" then vers_name = "Microsoft Office Ultimate 2013" end if
if vers = "002F" then vers_name = "Microsoft Office Home and Student 2013" end if
if vers = "0030" then vers_name = "Microsoft Office Enterprise 2013" end if
if vers = "0031" then vers_name = "Microsoft Office Professional Hybrid 2013" end if
if vers = "0033" then vers_name = "Microsoft Office Personal 2013" end if
if vers = "0035" then vers_name = "Microsoft Office Professional Hybrid 2013" end if
if vers = "0037" then vers_name = "Microsoft Office PowerPoint 2013" end if
if vers = "003A" then vers_name = "Microsoft Office Project Standard 2013" end if
if vers = "003B" then vers_name = "Microsoft Office Project Professional 2013" end if
if vers = "0044" then vers_name = "Microsoft Office InfoPath 2013" end if
if vers = "0051" then vers_name = "Microsoft Office Visio Professional 2013" end if
if vers = "0052" then vers_name = "Microsoft Office Visio Viewer 2013" end if
if vers = "0053" then vers_name = "Microsoft Office Visio Standard 2013" end if
if vers = "0057" then vers_name = "Microsoft Office Visio Premium 2013" end if
if vers = "00A1" then vers_name = "Microsoft Office OneNote 2013" end if
if vers = "00A3" then vers_name = "Microsoft Office OneNote Home Student 2013" end if
if vers = "00A7" then vers_name = "Calendar Printing Assistant for Microsoft Office Outlook 2013" end if
if vers = "00A9" then vers_name = "Microsoft Office InterConnect 2013" end if
if vers = "00AF" then vers_name = "Microsoft Office PowerPoint Viewer 2013 (English)" end if
if vers = "00B0" then vers_name = "The Microsoft Save as PDF add-in" end if
if vers = "00B1" then vers_name = "The Microsoft Save as XPS add-in" end if
if vers = "00B2" then vers_name = "The Microsoft Save as PDF or XPS add-in" end if
if vers = "00BA" then vers_name = "Microsoft Office Groove 2013" end if
if vers = "00CA" then vers_name = "Microsoft Office Small Business 2013" end if
if vers = "00E0" then vers_name = "Microsoft Office Outlook 2013" end if
if vers = "10D7" then vers_name = "Microsoft Office InfoPath Forms Services" end if
if vers = "110D" then vers_name = "Microsoft Office SharePoint Server 2013" end if
get_sku_2013 = vers_name
end function


function get_sku_2010(subkey)
  vers = mid(subkey,11,4)
if vers = "0011" then vers_name = "Microsoft Office Professional Plus 2010" end if
if vers = "0012" then vers_name = "Microsoft Office Standard 2010" end if
if vers = "0013" then vers_name = "Microsoft Office Basic 2010" end if
if vers = "0014" then vers_name = "Microsoft Office Professional 2010" end if
if vers = "0015" then vers_name = "Microsoft Office Access 2010" end if
if vers = "0016" then vers_name = "Microsoft Office Excel 2010" end if
if vers = "0017" then vers_name = "Microsoft Office SharePoint Designer 2010" end if
if vers = "0018" then vers_name = "Microsoft Office PowerPoint 2010" end if
if vers = "0019" then vers_name = "Microsoft Office Publisher 2010" end if
if vers = "001A" then vers_name = "Microsoft Office Outlook 2010" end if
if vers = "001B" then vers_name = "Microsoft Office Word 2010" end if
if vers = "001C" then vers_name = "Microsoft Office Access Runtime 2010" end if
if vers = "0020" then vers_name = "Microsoft Office Compatibility Pack for Word, Excel, and PowerPoint 2010 File Formats" end if
if vers = "0026" then vers_name = "Microsoft Expression Web" end if
if vers = "0029" then vers_name = "Microsoft Office Excel 2010" end if
if vers = "002B" then vers_name = "Microsoft Office Word 2010" end if
if vers = "002E" then vers_name = "Microsoft Office Ultimate 2010" end if
if vers = "002F" then vers_name = "Microsoft Office Home and Student 2010" end if
if vers = "0030" then vers_name = "Microsoft Office Enterprise 2010" end if
if vers = "0031" then vers_name = "Microsoft Office Professional Hybrid 2010" end if
if vers = "0033" then vers_name = "Microsoft Office Personal 2010" end if
if vers = "0035" then vers_name = "Microsoft Office Professional Hybrid 2010" end if
if vers = "0037" then vers_name = "Microsoft Office PowerPoint 2010" end if
if vers = "003A" then vers_name = "Microsoft Office Project Standard 2010" end if
if vers = "003B" then vers_name = "Microsoft Office Project Professional 2010" end if
if vers = "0044" then vers_name = "Microsoft Office InfoPath 2010" end if
if vers = "0051" then vers_name = "Microsoft Office Visio Professional 2010" end if
if vers = "0052" then vers_name = "Microsoft Office Visio Viewer 2010" end if
if vers = "0053" then vers_name = "Microsoft Office Visio Standard 2010" end if
if vers = "0057" then vers_name = "Microsoft Office Visio Premium 2010" end if
if vers = "00A1" then vers_name = "Microsoft Office OneNote 2010" end if
if vers = "00A3" then vers_name = "Microsoft Office OneNote Home Student 2010" end if
if vers = "00A7" then vers_name = "Calendar Printing Assistant for Microsoft Office Outlook 2010" end if
if vers = "00A9" then vers_name = "Microsoft Office InterConnect 2010" end if
if vers = "00AF" then vers_name = "Microsoft Office PowerPoint Viewer 2010 (English)" end if
if vers = "00B0" then vers_name = "The Microsoft Save as PDF add-in" end if
if vers = "00B1" then vers_name = "The Microsoft Save as XPS add-in" end if
if vers = "00B2" then vers_name = "The Microsoft Save as PDF or XPS add-in" end if
if vers = "00BA" then vers_name = "Microsoft Office Groove 2010" end if
if vers = "00CA" then vers_name = "Microsoft Office Small Business 2010" end if
if vers = "00E0" then vers_name = "Microsoft Office Outlook 2010" end if
if vers = "10D7" then vers_name = "Microsoft Office InfoPath Forms Services" end if
if vers = "110D" then vers_name = "Microsoft Office SharePoint Server 2010" end if
get_sku_2010 = vers_name
end function


function get_sku_2007(subkey)
  vers = mid(subkey,11,4)
if vers = "0011" then vers_name = "Microsoft Office Professional Plus 2007" end if
if vers = "0012" then vers_name = "Microsoft Office Standard 2007" end if
if vers = "0013" then vers_name = "Microsoft Office Basic 2007" end if
if vers = "0014" then vers_name = "Microsoft Office Professional 2007" end if
if vers = "0015" then vers_name = "Microsoft Office Access 2007" end if
if vers = "0016" then vers_name = "Microsoft Office Excel 2007" end if
if vers = "0017" then vers_name = "Microsoft Office SharePoint Designer 2007" end if
if vers = "0018" then vers_name = "Microsoft Office PowerPoint 2007" end if
if vers = "0019" then vers_name = "Microsoft Office Publisher 2007" end if
if vers = "001A" then vers_name = "Microsoft Office Outlook 2007" end if
if vers = "001B" then vers_name = "Microsoft Office Word 2007" end if
if vers = "001C" then vers_name = "Microsoft Office Access Runtime 2007" end if
if vers = "0020" then vers_name = "Microsoft Office Compatibility Pack for Word, Excel, and PowerPoint 2007 File Formats" end if
if vers = "0026" then vers_name = "Microsoft Expression Web" end if
if vers = "0029" then vers_name = "Microsoft Office Excel 2007" end if
if vers = "002B" then vers_name = "Microsoft Office Word 2007" end if
if vers = "002E" then vers_name = "Microsoft Office Ultimate 2007" end if
if vers = "002F" then vers_name = "Microsoft Office Home and Student 2007" end if
if vers = "0030" then vers_name = "Microsoft Office Enterprise 2007" end if
if vers = "0031" then vers_name = "Microsoft Office Professional Hybrid 2007" end if
if vers = "0033" then vers_name = "Microsoft Office Personal 2007" end if
if vers = "0035" then vers_name = "Microsoft Office Professional Hybrid 2007" end if
if vers = "0037" then vers_name = "Microsoft Office PowerPoint 2007" end if
if vers = "003A" then vers_name = "Microsoft Office Project Standard 2007" end if
if vers = "003B" then vers_name = "Microsoft Office Project Professional 2007" end if
if vers = "0044" then vers_name = "Microsoft Office InfoPath 2007" end if
if vers = "0051" then vers_name = "Microsoft Office Visio Professional 2007" end if
if vers = "0052" then vers_name = "Microsoft Office Visio Viewer 2007" end if
if vers = "0053" then vers_name = "Microsoft Office Visio Standard 2007" end if
if vers = "00A1" then vers_name = "Microsoft Office OneNote 2007" end if
if vers = "00A3" then vers_name = "Microsoft Office OneNote Home Student 2007" end if
if vers = "00A7" then vers_name = "Calendar Printing Assistant for Microsoft Office Outlook 2007" end if
if vers = "00A9" then vers_name = "Microsoft Office InterConnect 2007" end if
if vers = "00AF" then vers_name = "Microsoft Office PowerPoint Viewer 2007 (English)" end if
if vers = "00B0" then vers_name = "The Microsoft Save as PDF add-in" end if
if vers = "00B1" then vers_name = "The Microsoft Save as XPS add-in" end if
if vers = "00B2" then vers_name = "The Microsoft Save as PDF or XPS add-in" end if
if vers = "00BA" then vers_name = "Microsoft Office Groove 2007" end if
if vers = "00CA" then vers_name = "Microsoft Office Small Business 2007" end if
if vers = "00E0" then vers_name = "Microsoft Office Outlook 2007" end if
if vers = "10D7" then vers_name = "Microsoft Office InfoPath Forms Services" end if
if vers = "110D" then vers_name = "Microsoft Office SharePoint Server 2007" end if
get_sku_2007 = vers_name
end function


function get_sku_2003(subkey)
  vers = mid(subkey,4,2)
if vers = "11" then vers_name = "Microsoft Office Professional Enterprise Edition 2003" end if
if vers = "12" then vers_name = "Microsoft Office Standard Edition 2003" end if
if vers = "13" then vers_name = "Microsoft Office Basic Edition 2003" end if
if vers = "14" then vers_name = "Microsoft Windows SharePoint Services 2.0" end if
if vers = "15" then vers_name = "Microsoft Office Access 2003" end if
if vers = "16" then vers_name = "Microsoft Office Excel 2003" end if
if vers = "17" then vers_name = "Microsoft Office FrontPage 2003" end if
if vers = "18" then vers_name = "Microsoft Office PowerPoint 2003" end if
if vers = "19" then vers_name = "Microsoft Office Publisher 2003" end if
if vers = "1A" then vers_name = "Microsoft Office Outlook Professional 2003" end if
if vers = "1B" then vers_name = "Microsoft Office Word 2003" end if
if vers = "1C" then vers_name = "Microsoft Office Access 2003 Runtime" end if
if vers = "1E" then vers_name = "Microsoft Office 2003 User Interface Pack" end if
if vers = "1F" then vers_name = "Microsoft Office 2003 Proofing Tools" end if
if vers = "23" then vers_name = "Microsoft Office 2003 Multilingual User Interface Pack" end if
if vers = "24" then vers_name = "Microsoft Office 2003 Resource Kit" end if
if vers = "26" then vers_name = "Microsoft Office XP Web Components" end if
if vers = "2E" then vers_name = "Microsoft Office 2003 Research Service SDK" end if
if vers = "44" then vers_name = "Microsoft Office InfoPath 2003" end if
if vers = "83" then vers_name = "Microsoft Office 2003 HTML Viewer" end if
if vers = "92" then vers_name = "Windows SharePoint Services 2.0 English Template Pack" end if
if vers = "93" then vers_name = "Microsoft Office 2003 English Web Parts and Components" end if
if vers = "A1" then vers_name = "Microsoft Office OneNote 2003" end if
if vers = "A4" then vers_name = "Microsoft Office 2003 Web Components" end if
if vers = "A5" then vers_name = "Microsoft SharePoint Migration Tool 2003" end if
if vers = "AA" then vers_name = "Microsoft Office PowerPoint 2003 Presentation Broadcast" end if
if vers = "AB" then vers_name = "Microsoft Office PowerPoint 2003 Template Pack 1" end if
if vers = "AC" then vers_name = "Microsoft Office PowerPoint 2003 Template Pack 2" end if
if vers = "AD" then vers_name = "Microsoft Office PowerPoint 2003 Template Pack 3" end if
if vers = "AE" then vers_name = "Microsoft Organization Chart 2.0" end if
if vers = "CA" then vers_name = "Microsoft Office Small Business Edition 2003" end if
if vers = "D0" then vers_name = "Microsoft Office Access 2003 Developer Extensions" end if
if vers = "DC" then vers_name = "Microsoft Office 2003 Smart Document SDK" end if
if vers = "E0" then vers_name = "Microsoft Office Outlook Standard 2003" end if
if vers = "E3" then vers_name = "Microsoft Office Professional Edition 2003 (with InfoPath 2003)" end if
if vers = "FF" then vers_name = "Microsoft Office 2003 Edition Language Interface Pack" end if
if vers = "F8" then vers_name = "Remove Hidden Data Tool" end if
if vers = "3B" then vers_name = "Microsoft Office Project Professional 2003" end if
if vers = "32" then vers_name = "Microsoft Office Project Server 2003" end if
if vers = "51" then vers_name = "Microsoft Office Visio Professional 2003" end if
if vers = "52" then vers_name = "Microsoft Office Visio Viewer 2003" end if
if vers = "53" then vers_name = "Microsoft Office Visio Standard 2003" end if
if vers = "5E" then vers_name = "Microsoft Office Visio 2003 Multilingual User Interface Pack" end if
if vers = "5F" then vers_name = "Microsoft Visual Studio .NET Enterprise Architect 2003" end if
if vers = "60" then vers_name = "Microsoft Visual Studio .NET Enterprise Developer 2003" end if
if vers = "61" then vers_name = "Microsoft Visual Studio .NET Professional 2003" end if
if vers = "62" then vers_name = "Microsoft Visual Basic .NET Standard 2003" end if
if vers = "63" then vers_name = "Microsoft Visual C# .NET Standard 2003" end if
if vers = "64" then vers_name = "Microsoft Visual C++ .NET Standard 2003" end if
if vers = "65" then vers_name = "Microsoft Visual J# .NET Standard 2003" end if
get_sku_2003 = vers_name
end function


function get_sku_xp(value)
vers = mid(value,4,2)
if vers = "11" then vers_name = "Microsoft Office XP Professional" end if
if vers = "12" then vers_name = "Microsoft Office XP Standard" end if
if vers = "13" then vers_name = "Microsoft Office XP Small Business" end if
if vers = "14" then vers_name = "Microsoft Office XP Web Server" end if
if vers = "15" then vers_name = "Microsoft Access 2002" end if
if vers = "16" then vers_name = "Microsoft Excel 2002" end if
if vers = "17" then vers_name = "Microsoft FrontPage 2002" end if
if vers = "18" then vers_name = "Microsoft PowerPoint 2002" end if
if vers = "19" then vers_name = "Microsoft Publisher 2002" end if
if vers = "1A" then vers_name = "Microsoft Outlook 2002" end if
if vers = "1B" then vers_name = "Microsoft Word 2002" end if
if vers = "1C" then vers_name = "Microsoft Access 2002 Runtime" end if
if vers = "1D" then vers_name = "Microsoft FrontPage Server Extensions 2002" end if
if vers = "1E" then vers_name = "Microsoft Office Multilingual User Interface Pack" end if
if vers = "1F" then vers_name = "Microsoft Office Proofing Tools Kit" end if
if vers = "20" then vers_name = "System Files Update" end if
if vers = "22" then vers_name = "unused" end if
if vers = "23" then vers_name = "Microsoft Office Multilingual User Interface Pack Wizard" end if
if vers = "24" then vers_name = "Microsoft Office XP Resource Kit" end if
if vers = "25" then vers_name = "Microsoft Office XP Resource Kit Tools (download from Web)" end if
if vers = "26" then vers_name = "Microsoft Office Web Components" end if
if vers = "27" then vers_name = "Microsoft Project 2002" end if
if vers = "28" then vers_name = "Microsoft Office XP Professional with FrontPage" end if
if vers = "29" then vers_name = "Microsoft Office XP Professional Subscription" end if
if vers = "2A" then vers_name = "Microsoft Office XP Small Business Edition Subscription" end if
if vers = "2B" then vers_name = "Microsoft Publisher 2002 Deluxe Edition" end if
if vers = "2F" then vers_name = "Standalone IME (JPN Only)" end if
if vers = "30" then vers_name = "Microsoft Office XP Media Content" end if
if vers = "31" then vers_name = "Microsoft Project 2002 Web Client" end if
if vers = "32" then vers_name = "Microsoft Project 2002 Web Server" end if
if vers = "33" then vers_name = "Microsoft Office XP PIPC1 (Pre Installed PC) (JPN Only)" end if
if vers = "34" then vers_name = "Microsoft Office XP PIPC2 (Pre Installed PC) (JPN Only)" end if
if vers = "35" then vers_name = "Microsoft Office XP Media Content Deluxe" end if
if vers = "3A" then vers_name = "Project 2002 Standard" end if
if vers = "3B" then vers_name = "Project 2002 Professional" end if
if vers = "51" then vers_name = "Microsoft Visio Professional 2002" end if
if vers = "5F" then vers_name = "Microsoft Visual Studio .NET Enterprise Architect 2003" end if
if vers = "60" then vers_name = "Microsoft Visual Studio .NET Enterprise Developer 2003" end if
if vers = "61" then vers_name = "Microsoft Visual Studio .NET Professional 2003" end if
if vers = "62" then vers_name = "Microsoft Visual Basic .NET Standard 2003" end if
if vers = "63" then vers_name = "Microsoft Visual C# .NET Standard 2003" end if
if vers = "64" then vers_name = "Microsoft Visual C++ .NET Standard 2003" end if
if vers = "65" then vers_name = "Microsoft Visual J# .NET Standard 2003" end if
get_sku_xp = vers_name
end function


function get_release_type(value)
vers = mid(value,2,1)
if vers = "0" then release_type = "Any release before Beta 1" end if
if vers = "1" then release_type = "Beta 1" end if
if vers = "2" then release_type = "Beta 2" end if
if vers = "3" then release_type = "RC0<BR/>" end if
if vers = "4" then release_type = "RC1/OEM Preview Release" end if
if vers = "5" then release_type = "Reserved - Not Defined by Microsoft" end if
if vers = "6" then release_type = "Reserved - Not Defined by Microsoft" end if
if vers = "7" then release_type = "Reserved - Not Defined by Microsoft" end if
if vers = "8" then release_type = "Reserved - Not Defined by Microsoft" end if
if vers = "9" then release_type = "RTM (first shipped version)" end if
if vers = "A" then release_type = "SR1 (unused if the product code is not changed after RTM)" end if
if vers = "B" then release_type = "SR2 (unused if the product code is not changed after RTM)" end if
if vers = "C" then release_type = "SR3 (unused if the product code is not changed after RTM)" end if
get_release_type = release_type
end function


function get_edition_type(value)
vers = mid(value,3,1)
if vers = "0" then release_type = "Enterprise" end if
if vers = "1" then release_type = "Retail/OEM" end if
if vers = "2" then release_type = "Trial" end if
get_edition_type = release_type
end function

function clean(value)
if isnull(value) then value = ""
'value = Replace(value, chr(34), "\'")
'value = Replace(value, chr(39), "\'")
value = Replace(value, vbCr, "")
value = Replace(value, vbLf, "")
'if right(value, 1) = "\" then
'  value = value + " "
'end if
clean = value
end function

function GetCrystalKey(rpk)
  GetCrystalKey = Mid(rpk,3,21)
End Function


Function NSlookup(sHost)
   ' Both IP address and DNS name is allowed
   ' Function will return the opposite
   Set oRE = New RegExp
   oRE.Pattern = "^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$"
   bInpIP = False
   If oRE.Test(sHost) Then
       bInpIP = True
   End If
   Set oShell = CreateObject("Wscript.Shell")
   Set oFS = CreateObject("Scripting.FileSystemObject")
   sTemp = oShell.ExpandEnvironmentStrings("%TEMP%")
   sTempFile = sTemp & "\" & oFS.GetTempName
   'Run NSLookup via Command Prompt
   'Dump results into a temp text file
    oShell.Run "%ComSpec% /c nslookup.exe " & sHost & " >" & sTempFile, 0, True
   'Open the temp Text File and Read out the Data
   Set oTF = oFS.OpenTextFile(sTempFile)
   'Parse the text file
   Do While Not oTF.AtEndOfStream
       sLine = Trim(oTF.Readline)
       If LCase(Left(sLine, 5)) = "name:" Then
           sData = Trim(Mid(sLine, 6))
           If Not bInpIP Then
               'Next line will be IP address(es)
               'Line can be prefixed with "Address:" or "Addresses":
               aLine = Split(oTF.Readline, ":")
               sData = Trim(aLine(1))
           End If
           Exit Do
       End If
   Loop
   'Close it
   oTF.Close
   'Delete It
   oFS.DeleteFile sTempFile
   If Lcase(TypeName(sData)) = LCase("Empty") Then
       NSlookup = ""
   Else
       NSlookup = sData
   End If
End Function

Function HowMany()
  Dim Proc1,Proc2,Proc3
  CheckForHungWMI()
  Set Proc1 = GetObject("winmgmts:{impersonationLevel=impersonate}!\\.\root\cimv2")
  Set Proc2 = Proc1.ExecQuery("select * from win32_process" )
  HowMany=0
  For Each Proc3 in Proc2
    If LCase(Proc3.Caption) = "cscript.exe" Then
      HowMany=HowMany + 1
    End If
  Next
End Function

sub entry(form_input, comment,objTextFile,oAdd,oComment)
if form_input <> "" then
  if online = "n" then
    objTextFile.WriteLine(form_input)
  end if
  if online = "ie" or online = "yesxml" then
    form_total = form_total + form_input + vbcrlf
  end if
end if
end sub

Sub CheckForHungWMI()

    ' Get the current date in UTC format
    Set dtmStart = CreateObject("WbemScripting.SWbemDateTime")
    dtmStart.SetVarDate Now, True

    ' Subtract the script_timeout value
    dtmNew = DateAdd("s", (script_timeout * -1), dtmStart.GetVarDate(True))

    ' Convert our dtmNew time back to UTC format, since that's the format needed for the WMIService query, below.
    Set dtmTarget = CreateObject("WbemScripting.SWbemDateTime")
    dtmTarget.SetVarDate dtmNew, True

    Set objWMIService = GetObject("winmgmts:{impersonationLevel=impersonate}!\\.\root\cimv2")
   
    ' Pull a list of all processes that are over (script_timeout) seconds old
    Set colProcesses = objWMIService.ExecQuery _
        ("Select * from Win32_Process WHERE CreationDate < '" & dtmTarget & "'")

    For each objProcess in colProcesses
        ' Look for cscript.exe processes only
        if objProcess.Name = "cscript.exe" then
          ' Look for audit.vbs processes with the //Nologo cmd line option. 
          ' NOTE: The //Nologo cmd line option should NOT be used to start the initial audit, or it will kill itself off after script_timeout seconds
          'if InStr(objProcess.CommandLine, "//Nologo") and InStr(objProcess.CommandLine, "audit.vbs") then
		  if InStr(objProcess.CommandLine, "//Nologo") and InStr(objProcess.CommandLine, script_name) then
            ' The command line looks something like this: "C:\WINDOWS\system32\cscript.exe" //Nologo audit.vbs S0259W11
            ' Get the position of the auditing script name in the command line, and add its lenght +1 to get to the start of the workstation name
            'position = InStr(objProcess.CommandLine, "audit.vbs") + 10
			position = InStr(objProcess.CommandLine, script_name) + Len(script_name) +1
            affectedComputer = Mid(objProcess.CommandLine,position)
            Echo("" & Now & "," & affectedComputer & " - Hung Process Killed. ")
            LogKilledAudit("Hung Process Killed for machine: " & affectedComputer)
            objProcess.Terminate
          end if
        end if
    Next

End Sub


function getResultFromFileExpression(node)
  Dim result,child
  for each child in node.childNodes

     'Check if the tag is reconised
     if (not(child.nodeName = "file" or child.nodeName="and" or child.nodeName="or" or child.nodeName="xor" or child.nodeName="not")) then
        Err.Raise 1, "getResultFromFileExpression", "Unknown tag: " & child.nodeName
     end if

     ' If the result is currently empty then create one
     if (isEmpty(result)) then
        if (child.nodeName = "file") then
          result = fileExists(child)
        else
          result = getResultFromFileExpression(child)
        end if

        if (node.nodeName = "not") then
           result = not result
        end if

     'If the result isn't empty and nodeName is incorrect then raise error
     elseif (node.nodeName = "not" or node.nodeName = "test" or node.nodeName = "file") then
        Err.Raise 2, "getResultFromFileExpression", "Incorrect nesting within the node: " & node.nodeName

     'and
     elseif (node.nodeName = "and") then
        if (child.nodeName = "file") then
          result = result and fileExists(child)
        else
          result = result and getResultFromFileExpression(child)
        end if


     'or
     elseif (node.nodeName = "or") then
        if (child.nodeName = "file") then
          result = result or fileExists(child)
        else
          result = result or getResultFromFileExpression(child)
        end if

     'xor
     elseif (node.nodeName = "xor") then
        if (child.nodeName = "file") then
          result = result xor fileExists(child)
        else
          result = result xor getResultFromFileExpression(child)
        end if

     'root node
     elseif (node.nodeName = "test") then
        if (child.nodeName = "file") then
           'Shouldn't be able to get here
           Err.Raise 2, "getResultFromFileExpression", "Incorrect nesting within the node: " & node.nodeName
        else
           result = getResultFromFileExpression(child)
        end if

     end if

     'Shortcut
     if (node.nodeName = "and" and result = false) then
        getResultFromFileExpression = false
        exit function
     elseif (node.nodeName = "or" and result = true) then
        getResultFromFileExpression = true
        exit function
     end if

  next
  getResultFromFileExpression = result
end function

function fileExists(aNode)
  if (aNode.nodeName <>"file") then
     Err.Raise 3, "fileExists", "Incorrect node type passed to function: " & node.nodeName
  end if

  sFilename = aNode.getAttribute("filename")
  Set colFiles = objWMIService.ExecQuery("Select Name,Version,Manufacturer,FileSize from CIM_DataFile where Name = '" & sFilename & "'")
  if (colFiles.Count=0) then
        fileExists = false
     else
         For Each objFile in colFiles

             if (isNull(aNode.getAttribute("size"))) then
                'Don't test filesize
             else
                 if (aNode.getAttribute("size") <> objFile.FileSize) then
                    fileExists = false
                    exit function
                 else
                    'filesize matches
                 end if
             end if

             if (isNull(aNode.getAttribute("version"))) then
                'Don't need to test version
             else
                 if (aNode.getAttribute("version") <> objFile.version) then
                    fileExists = false
                    exit function
                 else
                     'Version matches
                 end if
             end if
         next
         fileExists = true
     end if 
end function

Function urlEncode(sString)
  Dim nIndex, aCode, theString
  Set theString = CreateObject("ADODB.Stream")
  theString.Type = 2 'Binary?
  theString.Open
  theString.Position = 0 
    
  For nIndex = 1 to Len(sString)
    aCode = AscW(Mid(sString,nIndex,1))

    'convert from twos complement
    If aCode < 0 Then
      aCode = 65536 + aCode
    End If

    If ((aCode >= 48 and aCode <= 57) or (aCode >= 65 and aCode <=90) or (aCode >= 97 and aCode <= 122)) then
      'Alphanumerics
      theString.WriteText Chr(aCode)
    elseif (aCode = 45 or aCode = 46 or aCode = 95 or aCode = 126) then
      'Following characters: - / . / _ / ~
      theString.WriteText Chr(aCode)
    elseif (aCode < 16) then
      theString.WriteText "%0" & Hex(aCode)
    elseif (aCode < 128) then
      theString.WriteText "%" & Hex(aCode)
    elseif (aCode < 2048) then
      theString.WriteText "%" & hex(((aCode) \ 2^6) or 192)
      theString.WriteText "%" & hex(((aCode and 63)) or 128)
    elseif (aCode < 65536) then
      theString.WriteText "%" & hex(((aCode) \ 2^12) or 224)
      theString.WriteText "%" & hex(((aCode and 4032) \ 2^6) or 128)
      theString.WriteText "%" & hex(((aCode and 63)) or 128)
    end if
  Next
  
  theString.position = 0
  urlEncode = theString.ReadText()

End Function

Function FixPath(ByRef sPathDisk, ByRef sPathPart)
  Fixpath = "Win32_LogicalDiskToPartition.Antecedent=" & chr(34) & _
    Replace(sPathPart,chr(34), "\" & chr(34)) & chr(34) & "," & _
    "Dependent=" & chr(34) & Replace(sPathDisk,chr(34), "\" & _
    chr(34)) & chr(34)
End Function

'-------------------------------------------------------------------------------
' Function:     GetDomainComputers
' Description:  Returns a listing of NT Computer Accounts for a given domain
' Parameters:   ByVal strDomain - Name of an NT Domain to retrieve the
'                list of Computer from.
' Returns:      Variant array of NT Computer names for the specified domain.
'-------------------------------------------------------------------------------
Function GetDomainComputers(ByVal local_domain)
   Dim objIADsContainer          ' ActiveDs.IADsDomain
   Dim objIADsComputer           ' ActiveDs.IADsComputer
   Dim vReturn                   ' Variant
   
   ' connect to the computer.
   Set objIADsContainer = GetObject(local_domain)

   ' set the filter to retrieve only objects of class Computer
   objIADsContainer.Filter = Array("Computer")

   ReDim vReturn(0)
   For Each objIADsComputer In objIADsContainer
      If Trim(vReturn(0)) <> "" Then
         ReDim Preserve vReturn(UBound(vReturn) + 1)
      End If
      vReturn(UBound(vReturn)) = objIADsComputer.Name
   Next
   
   GetDomainComputers = vReturn
   Set objIADsComputer = Nothing
   Set objIADsContainer = Nothing
End Function

 Function CSVParser(CSVDataToProcess)

   'Declaring variables for text delimiter and text qualifyer
    Dim TextDelimiter, TextQualifyer
   'Declaring the variables used in determining action to be taken
    Dim ProcessQualifyer, NewRecordCreate
   'Declaring variables dealing with input string
    Dim CharMaxNumber, CharLocation, CharCurrentVal, CharCounter, CharStorage
   'Declaring variables that handle array duties
    Dim CSVArray(), CSVArrayCount
   'Setting default values for various variables
   '<- Text delimiter is a comma
    TextDelimiter = ","
   '<- Chr(34) is the ascii code for "
    TextQualifyer = Chr(34)
   '<- Determining how record should be processed
    ProcessQualifyer = False
   '<- Calculating no. of characters in variable
    CharMaxNumber = Len(CSVDataToProcess)
   '<- Determining how to handle record at different
   '   stages of operation
   '   0 = Don't create new record
   '   1 = Write data to existing record
   '   2 = Close record and open new one
    NewRecordCreate = 0
   '<- Priming the array counter
    CSVArrayCount = 0
   '<- Initializing the array
    Redim Preserve CSVArray(CSVArrayCount)
   '<- Record character counter
    CharCounter = 0

   'Starting the main loop
    For CharLocation = 1 to CharMaxNumber
      'Retrieving the next character in sequence from CSVDataToProcess
       CharCurrentVal = Mid(CSVDataToProcess, CharLocation, 1)
      'This will figure out if the record uses a text qualifyer or not
       If CharCurrentVal = TextQualifyer And CharCounter = 0 Then
         ProcessQualifyer = True
         CharCurrentVal = ""
       End If
      'Advancing the record 'letter count' counter
       CharCounter = CharCounter + 1
      'Choosing data extraction method (text qualifyer or no text qualifyer)
       If ProcessQualifyer = True Then
          'This section handles records with a text qualifyer and text delimiter
          'It is also handles the special case scenario, where the qualifyer is
          'part of the data.  In the CSV file, a double quote represents a single
          'one  ie.  "" = "
           If Len(CharStorage) <> 0 Then
              If CharCurrentVal = TextDelimiter Then
                 CharStorage = ""
                 ProcessQualifyer = False
                 NewRecordCreate = 2
              Else
                 CharStorage = ""
                 NewRecordCreate = 1
              End If
           Else
              If CharCurrentVal = TextQualifyer Then
                 CharStorage = CharStorage & CharCurrentVal
                 NewRecordCreate = 0
              Else
                 NewRecordCreate = 1
              End If
           End If
      'This section handles a regular CSV record.. without the text qualifyer
       Else
           If CharCurrentVal = TextDelimiter Then
              NewRecordCreate = 2
           Else
              NewRecordCreate = 1
           End If
       End If
      'Writing the data to the array
       Select Case NewRecordCreate
        'This section just writes the info to the array
         Case 1
           CSVArray(CSVArrayCount) = CSVArray(CSVArrayCount) & CharCurrentVal
        'This section closes the current record and creates a new one
         Case 2
           CharCounter = 0
           CSVArrayCount = CSVArrayCount + 1
           Redim Preserve CSVArray(CSVArrayCount)
       End Select
    Next
   'Finishing Up
    CSVParser = CSVArray

 End Function

 Function Echo(sText)
	If verbose = "y" then 
    wscript.echo sText
    End If
    ' Also add to Audit Log 
    if use_audit_log = "y" then 
    Set objFSO = CreateObject("Scripting.FileSystemObject")
        If objFSO.FileExists(this_audit_log) Then
        Set objFile = objFSO.OpenTextFile(this_audit_log, ForAppending)
'        objFile.WriteLine
        objFile.WriteLine "" & Now & "," & strComputer & ",'Audit Result - "  & sText & " - Completed OK.'"
        objFile.Close
        End If
    End if
    
 End Function

Sub ArrayShuffle(arr)
    Dim index
    Dim newIndex
    Dim firstIndex
    Dim itemCount
    Dim tmpValue
   
    firstIndex = LBound(arr)
    itemCount = UBound(arr) - LBound(arr) + 1
   
    For index = UBound(arr) To LBound(arr) + 1 Step -1
        ' evaluate a random index from LBound to INDEX
        newIndex = firstIndex + Int(Rnd * itemCount)
        ' swap the two items
        tmpValue = arr(index)
        arr(index) = arr(newIndex)
        arr(newIndex) = tmpValue
        ' prepare for next iteration
        itemCount = itemCount - 1
    Next
   
End Sub

Function LogKilledAudit(txt)
   on error resume next
   dim Today, YYYYmmdd, fp, txtarr, txtline, todaystr
   today=Now
   logfilename="killed_audits.log"
   todaystr=datepart("yyyy", today)&"/"&_
         right("00"&datepart("m", today), 2)&"/"&_
         right("00"&datepart("d", today), 2)&" "&_
         right("00"&datepart("h", today), 2)&":"&_
         right("00"&datepart("n", today), 2)&":"&_
         right("00"&datepart("s", today), 2)
   Set objFSO = CreateObject("Scripting.FileSystemObject")
   set fp=objFSO.OpenTextFile(logfilename, 8, true)
   If err<>0 then wscript.echo err.number&" "&err.description
   txtarr=Split(txt, vbcrlf)
   txt=""
   For each txtline in txtarr
     txtline=trim(txtline)
     if txtline<>"" then
       txt=txt&todaystr&" - "&txtline&vbcrlf
     End if
   Next

   WScript.Echo(left(txt, len(txt)-2))
   fp.write txt
   fp.Close
   set fp=Nothing

   LogKilledAudit=True
End Function 

Sub Usage
    Wscript.Echo "Recognized audit.config named arguments:" & vbcrlf &_
                 "   strComputer     strUser        strPass" & vbcrlf &_
                 "   non_ie_page     online         ie_form_page" & vbcrlf &_
                 "   ie_auto_submit  ie_visible     ie_submit_verbose" & vbcrlf &_
                 "   software_audit  monitor_detect printer_detect" & vbcrlf &_
                 "   uuid_type       verbose        number_of_audits" & vbcrlf &_
                 "   local_domain    domain_type    audit_local_domain" & vbcrlf &_
                 "   script_name     input_file" & vbcrlf &_
                 vbcrlf &_
                 "Additional recognized named arguments:" & vbcrlf &_
                 "   /config_path:<path>   The complete path to an audit.config to use" & vbcrlf &_
                 "   /cmd_args_only        Do not use an audit.config. Only use named arguments" & vbcrlf &_
                 vbcrlf &_
                 "Recognized unnamed arguments:" & vbcrlf &_
                 "   hostname   (First argument )" & vbcrlf &_
                 "   username   (Second argument)" & vbcrlf &_
                 "   password   (Third argument )"
    Wscript.Quit(0)
End Sub

#>
