<#
  Open-AudIT Nmap scanner - PowerShell rewrite of nmap.vbs

  This is a native PowerShell implementation, not a VBScript wrapper.
  It reads audit.config from the same directory as this script by default.

  Usage:
    powershell.exe -ExecutionPolicy Bypass -File .\nmap.ps1
    powershell.exe -ExecutionPolicy Bypass -File .\nmap.ps1 42
    powershell.exe -ExecutionPolicy Bypass -File .\nmap.ps1 -ConfigPath .\audit.config -NmapExe "C:\Program Files (x86)\xampplite\nmap\nmap.exe"
#>

[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [int]$IpAddressNumber,

    [string]$ConfigPath = $null,

    [string]$NmapExe = 'C:\Program Files (x86)\xampplite\nmap\nmap.exe'
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$script:Config = @{}
$script:StrComputer = ''
$script:ScriptDirectory = $null

if ($MyInvocation.MyCommand.Path) {
    $script:ScriptDirectory = Split-Path -Parent $MyInvocation.MyCommand.Path
}

if ([string]::IsNullOrWhiteSpace($script:ScriptDirectory)) {
    try {
        $script:ScriptDirectory = Split-Path -Parent $PSCommandPath
    }
    catch {
        $script:ScriptDirectory = $null
    }
}

if ([string]::IsNullOrWhiteSpace($script:ScriptDirectory)) {
    $script:ScriptDirectory = (Get-Location).Path
}

if ([string]::IsNullOrWhiteSpace($ConfigPath)) {
    $ConfigPath = Join-Path -Path $script:ScriptDirectory -ChildPath 'audit.config'
}
elseif (-not [System.IO.Path]::IsPathRooted($ConfigPath)) {
    $ConfigPath = Join-Path -Path $script:ScriptDirectory -ChildPath $ConfigPath
}


function Remove-InlineVbsComment {
    param([AllowEmptyString()][string]$Line = '')

    $inString = $false
    $result = New-Object System.Text.StringBuilder

    for ($i = 0; $i -lt $Line.Length; $i++) {
        $ch = $Line[$i]

        if ($ch -eq '"') {
            $inString = -not $inString
            [void]$result.Append($ch)
            continue
        }

        if (-not $inString -and $ch -eq "'") {
            break
        }

        [void]$result.Append($ch)
    }

    return $result.ToString().Trim()
}

function Split-VbsConcatExpression {
    param([AllowEmptyString()][string]$Expression = '')

    $parts = New-Object System.Collections.Generic.List[string]
    $inString = $false
    $current = New-Object System.Text.StringBuilder

    for ($i = 0; $i -lt $Expression.Length; $i++) {
        $ch = $Expression[$i]

        if ($ch -eq '"') {
            $inString = -not $inString
            [void]$current.Append($ch)
            continue
        }

        if (-not $inString -and $ch -eq '+') {
            $parts.Add($current.ToString().Trim())
            [void]$current.Clear()
            continue
        }

        [void]$current.Append($ch)
    }

    $parts.Add($current.ToString().Trim())
    return $parts
}

function Convert-VbsLiteralOrVariable {
    param([AllowEmptyString()][string]$Token = '')

    $tokenTrimmed = $Token.Trim()

    if ($tokenTrimmed.Length -ge 2 -and $tokenTrimmed.StartsWith('"') -and $tokenTrimmed.EndsWith('"')) {
        return $tokenTrimmed.Substring(1, $tokenTrimmed.Length - 2)
    }

    if ($tokenTrimmed -match '^(?i:true|false)$') {
        return [System.Convert]::ToBoolean($tokenTrimmed)
    }

    if ($tokenTrimmed -match '^-?\d+$') {
        return [int]$tokenTrimmed
    }

    if ($script:Config.ContainsKey($tokenTrimmed)) {
        return $script:Config[$tokenTrimmed]
    }

    # Keep unknown identifiers as empty strings, matching the permissive VBScript-style config behaviour.
    return ''
}

function Convert-VbsExpression {
    param([AllowEmptyString()][string]$Expression = '')

    $parts = @(Split-VbsConcatExpression -Expression $Expression)
    if ($parts.Count -gt 1) {
        $builder = New-Object System.Text.StringBuilder
        foreach ($part in $parts) {
            [void]$builder.Append([string](Convert-VbsLiteralOrVariable -Token $part))
        }
        return $builder.ToString()
    }

    return Convert-VbsLiteralOrVariable -Token $Expression
}

function Import-AuditConfig {
    param([Parameter(Mandatory = $true)][string]$Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "Config file not found: $Path"
    }

    foreach ($rawLine in Get-Content -LiteralPath $Path -Encoding Default) {
        if ($null -eq $rawLine) {
            continue
        }
        $line = Remove-InlineVbsComment -Line ([string]$rawLine)
        if ([string]::IsNullOrWhiteSpace($line)) {
            continue
        }

        if ($line -match '^\s*([A-Za-z_]\w*)\s*=\s*(.+?)\s*$') {
            $name = $matches[1]
            $expression = $matches[2]
            $script:Config[$name] = Convert-VbsExpression -Expression $expression
        }
    }
}

function Get-ConfigValue {
    param(
        [Parameter(Mandatory = $true)][string]$Name,
        $DefaultValue = $null
    )

    if ($script:Config.ContainsKey($Name)) {
        return $script:Config[$Name]
    }

    return $DefaultValue
}

function Convert-ToVbsBoolean {
    param($Value)

    if ($Value -is [bool]) {
        return $Value
    }

    if ($null -eq $Value) {
        return $false
    }

    switch -Regex ([string]$Value) {
        '^(?i:true|yes|y|1)$' { return $true }
        default { return $false }
    }
}

function Write-AuditEcho {
    param([Parameter(Mandatory = $true)][string]$Text)

    if ([string](Get-ConfigValue -Name 'verbose' -DefaultValue 'n') -eq 'y') {
        Write-Host $Text
    }

    if ([string](Get-ConfigValue -Name 'use_audit_log' -DefaultValue 'n') -eq 'y') {
        $auditLog = [string](Get-ConfigValue -Name 'this_audit_log' -DefaultValue '')
        if (-not [string]::IsNullOrWhiteSpace($auditLog) -and (Test-Path -LiteralPath $auditLog)) {
            $line = "{0},{1},'Audit Result - {2} - Completed OK.'" -f (Get-Date), $script:StrComputer, $Text
            Add-Content -LiteralPath $auditLog -Value $line -Encoding Default
        }
    }
}

function Convert-ToLegacyOpenAuditText {
    param([AllowEmptyString()][string]$InputText = '')

    $builder = New-Object System.Text.StringBuilder
    foreach ($ch in $InputText.ToCharArray()) {
        $code = [int][char]$ch
        if ($code -gt 128) {
            [void]$builder.Append('&#')
            [void]$builder.Append($code)
        }
        else {
            [void]$builder.Append($ch)
        }
    }

    return $builder.ToString()
}

function Convert-ToFormBody {
    param(
        [AllowEmptyString()][string]$Text = '',
        [Parameter(Mandatory = $true)][bool]$UseUtf8
    )

    if ($UseUtf8) {
        $encoded = [System.Uri]::EscapeDataString($Text)
    }
    else {
        $encoded = [System.Uri]::EscapeDataString((Convert-ToLegacyOpenAuditText -InputText $Text))
    }

    return "add=$encoded"
}

function Invoke-OpenAuditPost {
    param(
        [Parameter(Mandatory = $true)][string]$Url,
        [AllowEmptyString()][string]$Payload = ''
    )

    # Match the VBScript behaviour which ignored SSL certificate errors for ServerXMLHTTP.
    if (-not ([System.Management.Automation.PSTypeName]'TrustAllCertsPolicy').Type) {
        Add-Type @"
using System.Net;
using System.Security.Cryptography.X509Certificates;
public class TrustAllCertsPolicy : ICertificatePolicy {
    public bool CheckValidationResult(ServicePoint srvPoint, X509Certificate certificate, WebRequest request, int certificateProblem) {
        return true;
    }
}
"@
    }
    [System.Net.ServicePointManager]::CertificatePolicy = New-Object TrustAllCertsPolicy

    $useUtf8 = ([string](Get-ConfigValue -Name 'utf8' -DefaultValue 'n') -eq 'y')
    $body = Convert-ToFormBody -Text ($Payload + "`r`n") -UseUtf8:$useUtf8
    $headers = @{ 'Content-Type' = 'application/x-www-form-urlencoded' }

    try {
        $response = Invoke-WebRequest -Uri $Url -Method Post -Headers $headers -Body $body -UseBasicParsing
        return [pscustomobject]@{
            Success    = ($response.StatusCode -eq 200)
            StatusCode = [int]$response.StatusCode
            StatusText = $response.StatusDescription
            XmlObject  = 'Invoke-WebRequest'
            Error      = $null
        }
    }
    catch {
        $statusCode = 0
        $statusText = ''
        if ($_.Exception.Response) {
            try {
                $statusCode = [int]$_.Exception.Response.StatusCode
                $statusText = $_.Exception.Response.StatusDescription
            }
            catch {
                $statusText = ''
            }
        }

        return [pscustomobject]@{
            Success    = $false
            StatusCode = $statusCode
            StatusText = $statusText
            XmlObject  = 'Invoke-WebRequest'
            Error      = $_.Exception.Message
        }
    }
}

Import-AuditConfig -Path $ConfigPath

$script:StrComputer = [string](Get-ConfigValue -Name 'strComputer' -DefaultValue '')
$ipStart = [int](Get-ConfigValue -Name 'nmap_ip_start' -DefaultValue 1)
$ipEnd = [int](Get-ConfigValue -Name 'nmap_ip_end' -DefaultValue 254)

if ($PSBoundParameters.ContainsKey('IpAddressNumber')) {
    $script:StrComputer = [string]$IpAddressNumber
    $ipStart = $IpAddressNumber
    $ipEnd = $IpAddressNumber
}

$nmapSubnet = [string](Get-ConfigValue -Name 'nmap_subnet' -DefaultValue '')
$nonNmapPage = [string](Get-ConfigValue -Name 'non_nmap_page' -DefaultValue '')
$tmpCleanup = Convert-ToVbsBoolean (Get-ConfigValue -Name 'nmap_tmp_cleanup' -DefaultValue $true)

if ([string]::IsNullOrWhiteSpace($nmapSubnet)) {
    throw 'nmap_subnet is missing in audit.config.'
}

if ([string]::IsNullOrWhiteSpace($nonNmapPage)) {
    throw 'non_nmap_page is missing in audit.config.'
}

if (-not (Test-Path -LiteralPath $NmapExe)) {
    Write-Warning "nmap.exe was not found at '$NmapExe'. The script will still try to execute that path if available via redirection, but you may need to adjust -NmapExe."
}

for ($ip = $ipStart; $ip -le $ipEnd; $ip++) {
    if ($ip -eq 1000) {
        Write-Host 'bypassing 1000'
        continue
    }

    $timestamp = Get-Date -Format 'yyyyMMddHHmmss'
    $target = "$nmapSubnet$ip"
    $tempFile = Join-Path -Path ([System.IO.Path]::GetTempPath()) -ChildPath "nmap_${target}_${timestamp}.tmp"

    $nmapArgs = New-Object System.Collections.Generic.List[string]
    $nmapArgs.Add('--system-dns')

    if ([string](Get-ConfigValue -Name 'nmap_syn_scan' -DefaultValue 'n') -eq 'y') {
        $nmapArgs.Add('-sS')
    }

    if ([string](Get-ConfigValue -Name 'nmap_udp_scan' -DefaultValue 'n') -eq 'y') {
        $nmapArgs.Add('-sU')
    }

    if ([string](Get-ConfigValue -Name 'nmap_srv_ver_scan' -DefaultValue 'n') -eq 'y') {
        $nmapArgs.Add('-sV')
        $nmapArgs.Add('--version-intensity')
        $nmapArgs.Add([string](Get-ConfigValue -Name 'nmap_srv_ver_int' -DefaultValue 1))
    }

    $nmapArgs.Add('-O')
    $nmapArgs.Add('-v')
    $nmapArgs.Add('-oN')
    $nmapArgs.Add($tempFile)
    $nmapArgs.Add($target)

    $displayCommand = '"{0}" {1}' -f $NmapExe, (($nmapArgs | ForEach-Object { if ($_ -match '\s') { '"{0}"' -f $_ } else { $_ } }) -join ' ')
    Write-Host $displayCommand

    try {
        & $NmapExe @nmapArgs
        $exitCode = $LASTEXITCODE
        if ($exitCode -ne 0) {
            Write-AuditEcho "nmap exited with code $exitCode for $target"
        }

        if (-not (Test-Path -LiteralPath $tempFile)) {
            throw "Nmap output file was not created: $tempFile"
        }

        $scanText = Get-Content -LiteralPath $tempFile -Raw -Encoding Default
        $postResult = Invoke-OpenAuditPost -Url $nonNmapPage -Payload $scanText

        if (-not $postResult.Success) {
            $message = "Unable to send XML to server using {0} - HTTP Response: {1} ({2})" -f $postResult.XmlObject, $postResult.StatusCode, $postResult.StatusText
            if ($postResult.Error) {
                $message = "$message - Error $($postResult.Error)"
            }
            Write-AuditEcho $message
        }
        else {
            Write-AuditEcho ("XML sent to server using {0}: {1} ({2})" -f $postResult.XmlObject, $postResult.StatusCode, $postResult.StatusText)
        }
    }
    catch {
        Write-AuditEcho ("Error while processing $target - $($_.Exception.Message)")
        throw
    }
    finally {
        if ($tmpCleanup -and (Test-Path -LiteralPath $tempFile)) {
            Remove-Item -LiteralPath $tempFile -Force
        }
    }
}
