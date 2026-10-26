$ErrorActionPreference = 'Stop'

$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Este script debe ejecutarse como administrador.'
}

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$cloudflaredConfig = 'C:\ProgramData\cloudflared\config.yml'
$envFile = 'C:\xampp\htdocs\sistema_soporte\.env'

Copy-Item -LiteralPath $cloudflaredConfig -Destination "$cloudflaredConfig.before-servicio-gastosclaros-$stamp"

$config = [IO.File]::ReadAllText($cloudflaredConfig)
if ($config -notmatch 'hostname:\s*servicio\.gastosclaros\.app') {
    $fallback = '  - service: http_status:404'
    $ingress = @(
        '  - hostname: servicio.gastosclaros.app'
        '    service: http://127.0.0.1:80'
        '    originRequest:'
        '      httpHostHeader: servicio.gastosclaros.app'
        ''
    ) -join "`r`n"

    if (-not $config.Contains($fallback)) {
        throw 'No se encontró la regla final http_status:404 en config.yml.'
    }

    $config = $config.Replace($fallback, $ingress + $fallback)
    [IO.File]::WriteAllText($cloudflaredConfig, $config, (New-Object Text.UTF8Encoding($false)))
}

$env = [IO.File]::ReadAllText($envFile)
$env = [regex]::Replace($env, '(?m)^APP_URL=.*$', 'APP_URL=https://servicio.gastosclaros.app')
[IO.File]::WriteAllText($envFile, $env, (New-Object Text.UTF8Encoding($false)))

& 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\sistema_soporte\artisan' config:clear
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudo limpiar la caché de configuración de Laravel.'
}

Restart-Service -Name Apache2.4 -Force
Restart-Service -Name Cloudflared -Force

Get-Service Apache2.4, Cloudflared | Format-Table Name, Status, StartType -AutoSize
