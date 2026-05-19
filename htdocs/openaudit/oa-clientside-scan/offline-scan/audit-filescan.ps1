<#
.SYNOPSIS
    Searches files by drive and extension using WMI/CIM,
    shows scan progress, and exports the result as CSV.

.DESCRIPTION
    Default behavior without parameters:
      - local machine
      - drive c:
      - extension exe

    Output:
      Creates a semicolon-separated CSV file in the script directory:
        wmifilescan-COMPUTERNAME.csv
#>

[CmdletBinding()]
param(
    [string]$ComputerName = $env:COMPUTERNAME,
    [string]$Drive = "c:",
    [string]$Extension = "exe",
    [switch]$UseDcom
)

$localNames = @(
    $env:COMPUTERNAME.ToLowerInvariant(),
    "localhost",
    ".",
    "127.0.0.1",
    "::1"
)

$isLocal = $localNames -contains $ComputerName.ToLowerInvariant()

# Normalize extension
$Extension = $Extension.TrimStart(".")

# CIM_DataFile filter
$filter = "Drive='$Drive' AND Extension='$Extension'"

# Script directory
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path

# Output file
$outputFile = Join-Path `
    $scriptPath `
    ("wmifilescan-{0}.csv" -f $ComputerName)

Write-Host "ComputerName : $ComputerName"
Write-Host "Drive        : $Drive"
Write-Host "Extension    : $Extension"
Write-Host "Output File  : $outputFile"
Write-Host "Mode         : $(if ($isLocal) { 'Local CIM, no WinRM required' } elseif ($UseDcom) { 'Remote WMI/DCOM' } else { 'Remote CIM/WinRM' })"
Write-Host ""

try {

    if ($isLocal) {

        $query = Get-CimInstance `
            -ClassName CIM_DataFile `
            -Filter $filter `
            -ErrorAction Stop

    }
    elseif ($UseDcom) {

        $query = Get-WmiObject `
            -Class CIM_DataFile `
            -ComputerName $ComputerName `
            -Filter $filter `
            -ErrorAction Stop

    }
    else {

        $query = Get-CimInstance `
            -ClassName CIM_DataFile `
            -ComputerName $ComputerName `
            -Filter $filter `
            -ErrorAction Stop
    }

    $counter = 0
    $startTime = Get-Date

    $result = $query | ForEach-Object {

        $counter++

        if ($counter -eq 1 -or $counter % 25 -eq 0) {

            $elapsed = (Get-Date) - $startTime

            if ($elapsed.TotalSeconds -gt 0) {
                $rate = [math]::Round(
                    $counter / $elapsed.TotalSeconds,
                    2
                )
            }
            else {
                $rate = 0
            }

            Write-Progress `
                -Activity "Scanning $ComputerName" `
                -Status "$counter Dateien gefunden | $rate Dateien/Sekunde | Aktuell: $($_.Name)" `
                -PercentComplete -1
        }

        $_ | Select-Object `
            Name,
            FileSize,
            LastModified
    }

    $result |
        Export-Csv `
            -Path $outputFile `
            -NoTypeInformation `
            -Encoding UTF8 `
            -Delimiter ";"

    Write-Progress `
        -Activity "Scanning $ComputerName" `
        -Completed

    Write-Host ""
    Write-Host "Export completed:"
    Write-Host $outputFile
    Write-Host "Files exported: $counter"
}
catch {

    Write-Progress `
        -Activity "Scanning $ComputerName" `
        -Completed

    Write-Error $_
}
