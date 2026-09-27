$ErrorActionPreference = 'Stop'
$tunnelExe = 'C:/Windows/System32/OpenSSH/ssh.exe'
$tunnelLog = Join-Path $PSScriptRoot 'storage/logs/public-tunnel.log'
$tunnelPidFile = Join-Path $PSScriptRoot 'storage/public-tunnel.pid'
if (Test-Path $tunnelPidFile) {
    $previousProcess = Get-Process -Id ([int](Get-Content $tunnelPidFile)) -ErrorAction SilentlyContinue
    if ($previousProcess -and $previousProcess.Path -eq $tunnelExe.Replace('/','\')) {
        Select-String -Path $tunnelLog -Pattern 'https://[a-z0-9]+\.lhr\.life' | Select-Object -Last 1 | ForEach-Object { $_.Matches.Value }
        exit
    }
}
$tunnelProcess = Start-Process -FilePath $tunnelExe -ArgumentList @('-T','-o','StrictHostKeyChecking=accept-new','-o','BatchMode=yes','-o','ServerAliveInterval=30','-o','ExitOnForwardFailure=yes','-R','80:127.0.0.1:8080','nokey@localhost.run') -RedirectStandardOutput $tunnelLog -RedirectStandardError (Join-Path $PSScriptRoot 'storage/logs/public-tunnel-error.log') -WindowStyle Hidden -PassThru
$tunnelProcess.Id | Set-Content $tunnelPidFile
Write-Host 'Tunnel sedang dimulai. Laptop dan Laragon harus tetap menyala.'
for ($attempt=0; $attempt -lt 45; $attempt++) {
    if (Test-Path $tunnelLog) {
        $publicLink = Select-String -Path $tunnelLog -Pattern 'https://[a-z0-9]+\.lhr\.life' | Select-Object -Last 1
        if ($publicLink) { Write-Host $publicLink.Matches.Value -ForegroundColor Green; exit }
    }
    Start-Sleep -Seconds 1
}
Write-Host ('Link belum tersedia. Periksa ' + $tunnelLog)
