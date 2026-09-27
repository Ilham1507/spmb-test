$pidFile = Join-Path $PSScriptRoot 'storage/public-tunnel.pid'
if (Test-Path $pidFile) {
    $tunnelProcess = Get-Process -Id ([int](Get-Content $pidFile)) -ErrorAction SilentlyContinue
    if ($tunnelProcess -and $tunnelProcess.Path -eq 'C:\Windows\System32\OpenSSH\ssh.exe') {
        Stop-Process -Id $tunnelProcess.Id
        Write-Host 'Akses publik dihentikan. Akses lokal tetap aktif.'
    }
}
