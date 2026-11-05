$ErrorActionPreference = "Continue"
$env:Path += ";$env:LOCALAPPDATA\Programs\Git\cmd;$env:LOCALAPPDATA\Programs\Git\mingw64\bin;C:\Program Files\GitHub CLI;C:\Program Files (x86)\cloudflared;C:\Program Files\cloudflared"
Set-Location "C:\xampp\htdocs\ODOL"

$logFile = "C:\xampp\htdocs\ODOL\tunnel.log"
$cfgPath = "C:\xampp\htdocs\ODOL\api\tunnel_config.json"
$cfExe = "C:\Program Files (x86)\cloudflared\cloudflared.exe"
if (-not (Test-Path $cfExe)) { $cfExe = "C:\Program Files\cloudflared\cloudflared.exe" }

function Check-And-Push-Url($url) {
    if (-not $url) { return }
    $currentJson = if (Test-Path $cfgPath) { Get-Content $cfgPath -Raw } else { "" }
    $newJson = "{`n  `"tunnelUrl`": `"$url`"`n}"
    if ($currentJson.Trim() -ne $newJson.Trim()) {
        [System.IO.File]::WriteAllText($cfgPath, $newJson, (New-Object System.Text.UTF8Encoding($false)))
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] New Tunnel URL detected: $url. Syncing to GitHub..." -ForegroundColor Yellow
        git add api/tunnel_config.json
        git commit -m "Auto-update tunnel URL: $url" -q
        git push origin main 2>&1 | Out-Host
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Pushed to GitHub successfully!" -ForegroundColor Green
    }
}

function Ensure-Local-Services {
    if (-not (Get-Process httpd -ErrorAction SilentlyContinue)) {
        Start-Process -FilePath "C:\xampp\apache\bin\httpd.exe" -WindowStyle Hidden
    }
    if (-not (Get-Process mysqld -ErrorAction SilentlyContinue)) {
        Start-Process -FilePath "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini" -WindowStyle Hidden
    }
}

Write-Host "=== OJS ODOL Backend & Tunnel Daemon Started ===" -ForegroundColor Green

while ($true) {
    Ensure-Local-Services

    $cfProc = Get-Process cloudflared -ErrorAction SilentlyContinue
    $tunnelHealthy = $false
    $currentUrl = $null

    if (Test-Path $cfgPath) {
        try {
            $parsed = Get-Content $cfgPath -Raw | ConvertFrom-Json
            $currentUrl = $parsed.tunnelUrl
        } catch {}
    }

    if ($cfProc -and $currentUrl) {
        try {
            $resp = Invoke-WebRequest -Uri "$currentUrl/ODOL/index.php/journal" -UseBasicParsing -TimeoutSec 6 -ErrorAction Stop
            if ($resp.StatusCode -eq 200) {
                $tunnelHealthy = $true
            }
        } catch {
            $tunnelHealthy = $false
        }
    }

    if (-not $tunnelHealthy) {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Tunnel disconnected or unhealthy. Restarting cloudflared..." -ForegroundColor Magenta
        Get-Process cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 1
        if (Test-Path $logFile) { Remove-Item $logFile -Force -ErrorAction SilentlyContinue }
        
        Start-Process -FilePath $cfExe -ArgumentList "tunnel --edge-ip-version 4 --url http://localhost:80 --logfile `"$logFile`"" -WindowStyle Hidden

        for ($i = 0; $i -lt 30; $i++) {
            Start-Sleep -Seconds 1
            if (Test-Path $logFile) {
                $content = Get-Content $logFile -Raw -ErrorAction SilentlyContinue
                if ($content -match "(https://[a-zA-Z0-9-]+\.trycloudflare\.com)") {
                    $newUrl = $matches[1]
                    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Tunnel obtained: $newUrl" -ForegroundColor Cyan
                    # Wait for connection to register
                    Start-Sleep -Seconds 4
                    Check-And-Push-Url $newUrl
                    break
                }
            }
        }
    }

    Start-Sleep -Seconds 30
}
