$ErrorActionPreference = 'Stop'

$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Este script debe ejecutarse como administrador.'
}

$workspace = 'C:\xampp\htdocs\sistema_soporte'
$adapter = Get-NetAdapter | Where-Object {
    $_.Status -eq 'Up' -and (
        $_.InterfaceDescription -match 'Wi-Fi|Wireless|802\.11' -or
        $_.Name -match 'Wi-Fi|Wireless'
    )
} | Sort-Object ifIndex | Select-Object -First 1

if (-not $adapter) {
    throw 'No se encontró un adaptador Wi-Fi activo.'
}

$current = Get-DnsClientServerAddress -InterfaceIndex $adapter.ifIndex -AddressFamily IPv4
$backup = [ordered]@{
    created_at = (Get-Date).ToString('o')
    interface_alias = $adapter.Name
    interface_index = $adapter.ifIndex
    previous_ipv4_dns = @($current.ServerAddresses)
}
$backup | ConvertTo-Json | Set-Content -LiteralPath "$workspace\scripts\dns-backup.json" -Encoding UTF8

Set-DnsClientServerAddress -InterfaceIndex $adapter.ifIndex -ServerAddresses @('1.1.1.1', '1.0.0.1')
Clear-DnsClientCache

$updated = Get-DnsClientServerAddress -InterfaceIndex $adapter.ifIndex -AddressFamily IPv4
@(
    "INTERFACE=$($adapter.Name)"
    "IFINDEX=$($adapter.ifIndex)"
    "DNS=$($updated.ServerAddresses -join ',')"
) | Set-Content -LiteralPath "$workspace\scripts\dns-change-result.txt" -Encoding UTF8
