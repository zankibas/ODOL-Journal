$ErrorActionPreference = "SilentlyContinue"
Stop-Process -Name "cloudflared" -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 1

$logFile = "C:\xampp\htdocs\ODOL\tunnel.log"
if (Test-Path $logFile) { Remove-Item $logFile -Force -ErrorAction SilentlyContinue }

$cfExe = "C:\Program Files (x86)\cloudflared\cloudflared.exe"
if (-not (Test-Path $cfExe)) {
    $cfExe = "C:\Program Files\cloudflared\cloudflared.exe"
}

Start-Process -FilePath $cfExe -ArgumentList "tunnel --url http://localhost:80 --logfile `"$logFile`"" -WindowStyle Hidden

$tunnelUrl = $null
for ($i = 0; $i -lt 30; $i++) {
    Start-Sleep -Seconds 1
    if (Test-Path $logFile) {
        $content = Get-Content $logFile -Raw
        if ($content -match "(https://[a-zA-Z0-9-]+\.trycloudflare\.com)") {
            $tunnelUrl = $matches[1]
            break
        }
    }
}

if ($tunnelUrl) {
    Write-Output "TUNNEL_URL: $tunnelUrl"
    $cfgPath = "C:\xampp\htdocs\ODOL\api\tunnel_config.json"
    $json = "{`n  `"tunnelUrl`": `"$tunnelUrl`"`n}"
    Set-Content -Path $cfgPath -Value $json -Encoding UTF8

    for ($j = 0; $j -lt 15; $j++) {
        Start-Sleep -Seconds 1
        $c = Get-Content $logFile -Raw -ErrorAction SilentlyContinue
        if ($c -match "Registered tunnel connection") {
            Write-Output "TUNNEL_READY"
            break
        }
    }
} else {
    Write-Output "FAILED_TO_GET_TUNNEL"
}
