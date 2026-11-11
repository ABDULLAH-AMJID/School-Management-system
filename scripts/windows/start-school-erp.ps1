$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root
if (!(Test-Path ".env")) { throw "Missing .env. Complete installation first." }
Start-Process -FilePath "php" -ArgumentList "artisan serve --host=0.0.0.0 --port=8000" -WorkingDirectory $Root -WindowStyle Hidden
Start-Process -FilePath "php" -ArgumentList "artisan queue:work --sleep=3 --tries=4 --max-time=3600" -WorkingDirectory $Root -WindowStyle Hidden
Start-Process -FilePath "php" -ArgumentList "artisan schedule:work" -WorkingDirectory $Root -WindowStyle Hidden
$Connector = Join-Path $Root "whatsapp-connector"
Start-Process -FilePath "npm" -ArgumentList "start" -WorkingDirectory $Connector -WindowStyle Hidden
Write-Host "School ERP services started. Open http://SERVER-IP:8000 on the school LAN."
