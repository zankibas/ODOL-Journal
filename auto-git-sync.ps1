param(
    [switch]$Watch,
    [int]$IntervalSeconds = 60
)

$ErrorActionPreference = "Continue"
$env:Path += ";$env:LOCALAPPDATA\Programs\Git\cmd;$env:LOCALAPPDATA\Programs\Git\mingw64\bin;C:\Program Files\GitHub CLI;C:\Program Files (x86)\cloudflared;C:\Program Files\cloudflared"
Set-Location "C:\xampp\htdocs\ODOL"

function Start-NewTunnel([string]$logFile) {
    Get-Process cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 1
    if (Test-Path $logFile) { Remove-Item $logFile -Force -ErrorAction SilentlyContinue }
    $cfExe = "C:\Program Files (x86)\cloudflared\cloudflared.exe"
    if (-not (Test-Path $cfExe)) { $cfExe = "C:\Program Files\cloudflared\cloudflared.exe" }
    Start-Process -FilePath $cfExe -ArgumentList "tunnel --edge-ip-version 4 --url http://localhost:80 --logfile `"$logFile`"" -WindowStyle Hidden
    for ($i = 0; $i -lt 30; $i++) {
        Start-Sleep -Seconds 1
        if (Test-Path $logFile) {
            $content = Get-Content $logFile -Raw -ErrorAction SilentlyContinue
            if ($content -match "(https://[a-zA-Z0-9-]+\.trycloudflare\.com)") {
                $foundUrl = $matches[1]
                for ($j = 0; $j -lt 12; $j++) {
                    Start-Sleep -Seconds 1
                    $c = Get-Content $logFile -Raw -ErrorAction SilentlyContinue
                    if ($c -match "Registered tunnel connection") {
                        return $foundUrl
                    }
                }
                return $foundUrl
            }
        }
    }
    return $null
}

function Ensure-BackendConnector {
    # Pastikan Apache & MySQL XAMPP berjalan
    if (-not (Get-Process httpd -ErrorAction SilentlyContinue)) {
        Start-Process -FilePath "C:\xampp\apache\bin\httpd.exe" -WindowStyle Hidden
    }
    if (-not (Get-Process mysqld -ErrorAction SilentlyContinue)) {
        Start-Process -FilePath "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini" -WindowStyle Hidden
    }

    $logFile = "C:\xampp\htdocs\ODOL\tunnel.log"
    $cfProc = Get-Process cloudflared -ErrorAction SilentlyContinue
    $tunnelUrl = $null

    if ($cfProc -and (Test-Path $logFile)) {
        $content = Get-Content $logFile -Raw -ErrorAction SilentlyContinue
        if ($content -notmatch "Unauthorized: Tunnel not found" -and $content -match "(https://[a-zA-Z0-9-]+\.trycloudflare\.com)") {
            $candidateUrl = $matches[1]
            try {
                $test = Invoke-WebRequest -Uri "$candidateUrl/ODOL/index.php/journal" -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
                if ($test.StatusCode -eq 200) {
                    $tunnelUrl = $candidateUrl
                }
            } catch {
                $tunnelUrl = $null
            }
        }
    }

    if (-not $tunnelUrl) {
        $tunnelUrl = Start-NewTunnel $logFile
    }

    if ($tunnelUrl) {
        $cfgPath = "C:\xampp\htdocs\ODOL\api\tunnel_config.json"
        $newJson = "{`n  `"tunnelUrl`": `"$tunnelUrl`"`n}"
        $oldJson = if (Test-Path $cfgPath) { Get-Content $cfgPath -Raw } else { "" }
        if ($oldJson.Trim() -ne $newJson.Trim()) {
            [System.IO.File]::WriteAllText($cfgPath, $newJson, (New-Object System.Text.UTF8Encoding($false)))
        }
    }
}

function Sync-OjsGit {
    Ensure-BackendConnector

    # Backup database MySQL odol_jurnal
    if (Test-Path "C:\xampp\mysql\bin\mysqldump.exe") {
        & "C:\xampp\mysql\bin\mysqldump.exe" -u root odol_jurnal --result-file="C:\xampp\htdocs\ODOL\dbscripts\odol_jurnal_backup.sql" 2>$null
    }

    if (-not (Test-Path "C:\xampp\htdocs\ODOL\vercel_dist\ODOL\js\build.js")) {
        & "C:\xampp\htdocs\ODOL\build-vercel.ps1"
    }

    $status = git status --porcelain
    if ($status) {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Perubahan terdeteksi, melakukan Auto Commit & Push ke GitHub..." -ForegroundColor Yellow
        git add -A
        git commit -m "Auto-sync OJS ODOL ($(Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))" -q
        git pull --rebase origin main 2>&1 | Out-Host
        git push origin main 2>&1 | Out-Host
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Sinkronisasi ke GitHub & Vercel berhasil!" -ForegroundColor Green
    } else {
        git pull --rebase origin main 2>&1 | Out-Host
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Repository sudah sinkron dengan GitHub." -ForegroundColor Green
    }
}

if ($Watch) {
    Write-Host "=== Mode Auto Git Pull & Push + Vercel Backend Aktif (Setiap $IntervalSeconds detik) ===" -ForegroundColor Green
    while ($true) {
        Sync-OjsGit
        Start-Sleep -Seconds $IntervalSeconds
    }
} else {
    Sync-OjsGit
}
