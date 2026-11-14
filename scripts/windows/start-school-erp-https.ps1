param(
    [Parameter(Mandatory=$true)][string]$CaddyConfig
)
$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root
if (!(Test-Path '.env')) { throw 'Missing .env. Complete ERP setup first.' }
if (!(Test-Path $CaddyConfig)) { throw 'Caddy config file not found.' }
if (!(Get-Command php -ErrorAction SilentlyContinue)) { throw 'PHP is not on PATH.' }
if (!(Get-Command caddy -ErrorAction SilentlyContinue)) { throw 'Caddy is not on PATH.' }
if (!(Get-Command npm -ErrorAction SilentlyContinue)) { throw 'npm is not on PATH.' }
$EnvText = Get-Content '.env' -Raw
if ($EnvText -notmatch '(?m)^TRUST_LOCAL_HTTPS_PROXY=true\s*$' -or $EnvText -notmatch '(?m)^SESSION_SECURE_COOKIE=true\s*$' -or $EnvText -notmatch '(?m)^APP_DEBUG=false\s*$') {
    throw 'Set TRUST_LOCAL_HTTPS_PROXY=true, SESSION_SECURE_COOKIE=true and APP_DEBUG=false in .env first.'
}
Write-Host 'Ensure the old 0.0.0.0:8000 server is stopped and firewall restricts 8443 to the school LAN.'
& caddy validate --config $CaddyConfig
if ($LASTEXITCODE -ne 0) { throw 'Caddy configuration validation failed.' }
& php artisan config:clear
if ($LASTEXITCODE -ne 0) { throw 'Laravel configuration cache could not be cleared.' }
Start-Process -FilePath 'php' -ArgumentList 'artisan serve --host=127.0.0.1 --port=8000' -WorkingDirectory $Root -WindowStyle Hidden
Start-Process -FilePath 'php' -ArgumentList 'artisan queue:work --sleep=3 --tries=4 --max-time=3600' -WorkingDirectory $Root -WindowStyle Hidden
Start-Process -FilePath 'php' -ArgumentList 'artisan schedule:work' -WorkingDirectory $Root -WindowStyle Hidden
Start-Process -FilePath 'npm' -ArgumentList 'start' -WorkingDirectory (Join-Path $Root 'whatsapp-connector') -WindowStyle Hidden
Start-Process -FilePath 'caddy' -ArgumentList @('run', '--config', ('"' + (Resolve-Path $CaddyConfig).Path + '"')) -WorkingDirectory $Root -WindowStyle Hidden
Write-Host 'Processes launched. Verify HTTPS and check service health; launch success does not guarantee they stayed running.'
