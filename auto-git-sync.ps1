param(
    [switch]$Watch,
    [int]$IntervalSeconds = 60
)

$ErrorActionPreference = "Continue"
$env:Path += ";$env:LOCALAPPDATA\Programs\Git\cmd;$env:LOCALAPPDATA\Programs\Git\mingw64\bin;C:\Program Files\GitHub CLI"
Set-Location "C:\xampp\htdocs\ODOL"

function Sync-OjsGit {
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Menjalankan Auto Git Pull dari GitHub..." -ForegroundColor Cyan
    git pull --rebase origin main 2>&1 | Out-Host

    # Backup database MySQL odol_jurnal
    if (Test-Path "C:\xampp\mysql\bin\mysqldump.exe") {
        & "C:\xampp\mysql\bin\mysqldump.exe" -u root odol_jurnal --result-file="C:\xampp\htdocs\ODOL\dbscripts\odol_jurnal_backup.sql" 2>$null
    }

    # Update build untuk Vercel jika localhost/ODOL aktif
    try {
        $ping = Invoke-WebRequest -Uri "http://localhost/ODOL" -UseBasicParsing -TimeoutSec 3 -ErrorAction Stop
        if ($ping.StatusCode -eq 200) {
            & "C:\xampp\htdocs\ODOL\build-vercel.ps1"
        }
    } catch {}

    $status = git status --porcelain
    if ($status) {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Perubahan terdeteksi, melakukan Auto Commit & Push ke GitHub..." -ForegroundColor Yellow
        git add -A
        git commit -m "Auto-sync OJS ODOL ($(Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))"
        git push origin main 2>&1 | Out-Host
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Sinkronisasi ke GitHub berhasil!" -ForegroundColor Green
    } else {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Repository sudah sinkron dengan GitHub." -ForegroundColor Green
    }
}

if ($Watch) {
    Write-Host "=== Mode Auto Git Pull & Push Aktif (Setiap $IntervalSeconds detik) ===" -ForegroundColor Green
    while ($true) {
        Sync-OjsGit
        Start-Sleep -Seconds $IntervalSeconds
    }
} else {
    Sync-OjsGit
}
