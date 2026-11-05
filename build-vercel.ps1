$ErrorActionPreference = "Stop"
$baseDir = "C:\xampp\htdocs\ODOL"
$distDir = "$baseDir\vercel_dist"
$baseUrl = "http://localhost/ODOL"

Write-Host "Membangun paket static & routing OJS ODOL untuk Vercel (vercel_dist)..." -ForegroundColor Cyan

if (Test-Path $distDir) {
    Remove-Item $distDir -Recurse -Force
}
New-Item -ItemType Directory -Force -Path "$distDir\ODOL\styles" | Out-Null

# 1. Salin aset statis OJS yang dibutuhkan frontend
$assetFolders = @(
    "lib\pkp\styles",
    "plugins\themes\default",
    "templates\images",
    "js\build",
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

# 2. Unduh CSS dinamis OJS (stylesheet & font) dan ubah URL absolut ke relatif
$cssSheet = (Invoke-WebRequest -Uri "$baseUrl/index.php/journal/`$`$`$call`$`$`$/page/page/css?name=stylesheet" -UseBasicParsing).Content
$cssSheet = $cssSheet -replace 'https?://localhost/ODOL/', '/ODOL/' -replace '//localhost/ODOL/', '/ODOL/'
Set-Content -Path "$distDir\ODOL\styles\ojs-stylesheet.css" -Value $cssSheet -Encoding UTF8

$cssFont = (Invoke-WebRequest -Uri "$baseUrl/index.php/journal/`$`$`$call`$`$`$/page/page/css?name=font" -UseBasicParsing).Content
$cssFont = $cssFont -replace 'https?://localhost/ODOL/', '/ODOL/' -replace '//localhost/ODOL/', '/ODOL/'
Set-Content -Path "$distDir\ODOL\styles\ojs-font.css" -Value $cssFont -Encoding UTF8

# 3. Crawl seluruh halaman publik OJS ODOL secara rekursif (BFS)
$queue = New-Object System.Collections.Generic.Queue[string]
$visited = New-Object System.Collections.Generic.HashSet[string]

$seedPaths = @(
    "/index.php/journal",
    "/index.php/journal/index",
    "/index.php/journal/issue/current",
    "/index.php/journal/issue/archive",
    "/index.php/journal/about",
    "/index.php/journal/about/submissions",
    "/index.php/journal/about/editorialMasthead",
    "/index.php/journal/about/privacy",
    "/index.php/journal/about/contact",
    "/index.php/journal/search",
    "/index.php/journal/user/register",
    "/index.php/journal/login",
    "/index.php/journal/about/aboutThisPublishingSystem",
    "/index.php/journal/announcement"
)

foreach ($p in $seedPaths) {
    $queue.Enqueue($p)
    [void]$visited.Add($p)
}

function Convert-OjsHtml([string]$html) {
    $html = $html.Replace('http://localhost/ODOL/index.php/journal/$$$call$$$/page/page/css?name=stylesheet', '/ODOL/styles/ojs-stylesheet.css')
    $html = $html.Replace('https://localhost/ODOL/index.php/journal/$$$call$$$/page/page/css?name=stylesheet', '/ODOL/styles/ojs-stylesheet.css')
    $html = $html.Replace('http://localhost/ODOL/index.php/journal/$$$call$$$/page/page/css?name=font', '/ODOL/styles/ojs-font.css')
    $html = $html.Replace('https://localhost/ODOL/index.php/journal/$$$call$$$/page/page/css?name=font', '/ODOL/styles/ojs-font.css')
    $html = $html -replace 'https?://localhost/ODOL/', '/ODOL/'
    $html = $html -replace '//localhost/ODOL/', '/ODOL/'
    return $html
}

while ($queue.Count -gt 0) {
    $relPath = $queue.Dequeue()
    $fullUrl = "$baseUrl$relPath"
    try {
        $resp = Invoke-WebRequest -Uri $fullUrl -UseBasicParsing
        if ($resp.StatusCode -eq 200 -and $resp.Headers["Content-Type"] -match "text/html") {
            $rawHtml = $resp.Content
            # Temukan link internal tambahan (/ODOL/index.php/journal/...)
            $matches = [regex]::Matches($rawHtml, 'https?://localhost/ODOL(/index\.php/journal/[a-zA-Z0-9_/\-]+)')
            foreach ($m in $matches) {
                $foundPath = $m.Groups[1].Value.TrimEnd('/')
                if ($foundPath -notmatch 'gateway|`\$\$\$call\$\$\$|user/setLocale' -and -not $visited.Contains($foundPath)) {
                    [void]$visited.Add($foundPath)
                    $queue.Enqueue($foundPath)
                }
            }

            $cleanHtml = Convert-OjsHtml $rawHtml
            $outDir = Join-Path "$distDir\ODOL" ($relPath.TrimStart('/') -replace '/', '\')
            New-Item -ItemType Directory -Force -Path $outDir | Out-Null
            Set-Content -Path (Join-Path $outDir "index.html") -Value $cleanHtml -Encoding UTF8

            if ($relPath -eq "/index.php/journal") {
                Set-Content -Path "$distDir\index.html" -Value $cleanHtml -Encoding UTF8
                Set-Content -Path "$distDir\ODOL\index.html" -Value $cleanHtml -Encoding UTF8
            }
        }
    } catch {
        # Abaikan halaman yang error/404
    }
}

Write-Host "Berhasil mengekspor $($visited.Count) halaman OJS ODOL ke vercel_dist!" -ForegroundColor Green
