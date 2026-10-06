$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

& php artisan migrate:status --no-interaction --no-ansi *> $null
if ($LASTEXITCODE -ne 0) {
    Write-Error 'Laravel could not connect to its configured database. Start the database service and check the local database configuration.'
    exit 1
}

$adbPath = Join-Path $env:LOCALAPPDATA 'Android\Sdk\platform-tools\adb.exe'
if (-not (Test-Path $adbPath)) {
    Write-Error 'Android Debug Bridge was not found. Install Android platform-tools to forward the API to your device.'
    exit 1
}

$adbDevices = @(& $adbPath devices)
if ($LASTEXITCODE -ne 0) {
    Write-Error 'Could not inspect Android devices. Check the Android platform-tools installation.'
    exit 1
}

$connectedDevices = @(
    $adbDevices |
        Select-Object -Skip 1 |
        Where-Object { $_ -match '^\S+\s+device$' }
)
if ($connectedDevices.Count -ne 1) {
    Write-Error 'Connect exactly one authorized Android device or emulator, then run the mobile app again.'
    exit 1
}

$deviceSerial = ($connectedDevices[0] -split '\s+')[0]
& $adbPath -s $deviceSerial reverse tcp:8000 tcp:8000 *> $null
if ($LASTEXITCODE -ne 0) {
    Write-Error 'Could not forward the Laravel API to the Android device. Reconnect and authorize USB debugging.'
    exit 1
}

$healthUrl = 'http://127.0.0.1:8000/api/mobile/tesda'
try {
    $response = Invoke-WebRequest -Uri $healthUrl -UseBasicParsing -TimeoutSec 3
    if ($response.StatusCode -eq 200) {
        Write-Output 'TUKLAS_MOBILE_API_READY'
        exit 0
    }
} catch {
    # Start the local API below when there is no healthy server on port 8000.
}

Write-Output 'Starting the Laravel API for Tuklas Mobile.'
& php artisan serve --host=0.0.0.0 --port=8000
exit $LASTEXITCODE
