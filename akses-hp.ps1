Write-Host ''
Write-Host 'AKSES WEBSITE SMK DARI HP' -ForegroundColor Cyan
Write-Host 'Nyalakan Laragon / Apache dan sambungkan HP ke Wi-Fi yang sama.'
Write-Host ''
$lanAdapters = Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -and $_.NetAdapter.Status -eq 'Up' }
foreach ($adapter in $lanAdapters) {
    foreach ($address in $adapter.IPv4Address) {
        Write-Host ($adapter.InterfaceAlias + ': http://' + $address.IPAddress + ':8080') -ForegroundColor Green
    }
}
if (-not $lanAdapters) { Write-Host 'Belum ada koneksi jaringan aktif.' -ForegroundColor Yellow }
Write-Host ''
Write-Host 'Jalankan file ini lagi setelah pindah Wi-Fi untuk melihat alamat terbaru.'
Write-Host 'Laptop harus tetap menyala. Hindari guest Wi-Fi yang memisahkan perangkat.'
