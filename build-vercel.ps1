$ErrorActionPreference = "Stop"
$baseDir = "C:\xampp\htdocs\ODOL"
$distDir = "$baseDir\vercel_dist"

Write-Host "Menyiapkan aset statis OJS ODOL untuk CDN Vercel (vercel_dist)..." -ForegroundColor Cyan

if (Test-Path $distDir) {
    Remove-Item $distDir -Recurse -Force
}
New-Item -ItemType Directory -Force -Path "$distDir\ODOL" | Out-Null

# Salin aset statis OJS (CSS, JS, Font, Gambar, Themes) agar dimuat cepat di CDN Vercel,
# sementara seluruh route /ODOL/index.php/* (Login, Dashboard, Submissions, API) ditangani dinamis oleh api/__vercel_proxy.js
$assetFolders = @(
    "js",
    "styles",
    "lib\pkp\styles",
    "lib\pkp\js",
    "plugins\themes\default",
    "templates\images",
    "public"
)

foreach ($folder in $assetFolders) {
    $src = Join-Path $baseDir $folder
    $dst = Join-Path "$distDir\ODOL" $folder
    if (Test-Path $src) {
        New-Item -ItemType Directory -Force -Path (Split-Path $dst -Parent) | Out-Null
        Copy-Item -Path $src -Destination $dst -Recurse -Force
    }
}

if (Test-Path "$baseDir\favicon.ico") {
    Copy-Item "$baseDir\favicon.ico" "$distDir\favicon.ico" -Force
    Copy-Item "$baseDir\favicon.ico" "$distDir\ODOL\favicon.ico" -Force
}

Write-Host "Aset statis vercel_dist selesai disiapkan!" -ForegroundColor Green
