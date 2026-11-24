$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$project = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '../..')).Path
$state = Join-Path $project '.puro-local'
$runtime = Join-Path $state 'runtime'
$phpIni = Join-Path $state 'php.ini'
$additionalIni = Join-Path $state 'ini-scan'
$requiredDlls = @('curl', 'fileinfo', 'mbstring', 'openssl', 'pdo_pgsql', 'zip')

function Install-Runtime {
    param($Name, $Url, $Sha256, $Destination, $RequiredFile, $StripPrefix = '')

    if (Test-Path -LiteralPath (Join-Path $Destination $RequiredFile) -PathType Leaf) { return }
    Write-Host "Mengunduh $Name. Ini hanya dilakukan saat runtime belum ada."
    $temporary = Join-Path $runtime ([Guid]::NewGuid().ToString('N'))
    $archive = Join-Path $temporary 'runtime.zip'
    $unpacked = Join-Path $temporary 'unpacked'
    [IO.Directory]::CreateDirectory($unpacked) | Out-Null

    try {
        Invoke-WebRequest -UseBasicParsing -Uri $Url -OutFile $archive -TimeoutSec 600
        if ((Get-FileHash -LiteralPath $archive -Algorithm SHA256).Hash -ne $Sha256) {
            throw "Checksum $Name berbeda. Unduhan dibatalkan agar bisa dicoba lagi."
        }
        if ($StripPrefix) {
            # PostgreSQL's distribution also includes pgAdmin. Install only the server tree.
            Add-Type -AssemblyName System.IO.Compression.FileSystem
            $zip = [IO.Compression.ZipFile]::OpenRead($archive)
            try {
                foreach ($entry in $zip.Entries) {
                    $nameInZip = $entry.FullName.Replace('\', '/')
                    if (-not $nameInZip.StartsWith($StripPrefix, [StringComparison]::OrdinalIgnoreCase)) { continue }
                    $relative = $nameInZip.Substring($StripPrefix.Length)
                    if ($relative -notmatch '^(bin|lib|share)/' -and $relative -notmatch '^(LICENSE|COPYRIGHT|NOTICE)(\.[^/]+)?$') { continue }
                    if (-not $entry.Name) { continue }
                    $extracted = [IO.Path]::GetFullPath((Join-Path $unpacked $relative))
                    if (-not $extracted.StartsWith("$unpacked\", [StringComparison]::OrdinalIgnoreCase)) {
                        throw "$Name memuat path yang tidak valid."
                    }
                    [IO.Directory]::CreateDirectory([IO.Path]::GetDirectoryName($extracted)) | Out-Null
                    [IO.Compression.ZipFileExtensions]::ExtractToFile($entry, $extracted, $false)
                }
            } finally {
                $zip.Dispose()
            }
        } else {
            Expand-Archive -LiteralPath $archive -DestinationPath $unpacked
        }
        if (-not (Test-Path -LiteralPath (Join-Path $unpacked $RequiredFile) -PathType Leaf)) {
            throw "Struktur arsip $Name tidak sesuai."
        }
        [IO.Directory]::Move($unpacked, $Destination)
    } finally {
        Remove-Item -LiteralPath $temporary -Recurse -Force -ErrorAction SilentlyContinue
    }
}

function Test-PuroPhp {
    param([string] $Candidate)
    if (-not (Test-Path -LiteralPath $Candidate -PathType Leaf)) { return $false }
    try {
        $version = @(& $Candidate -n -v 2>$null)
        if ($LASTEXITCODE -ne 0 -or ($version -join ' ') -notmatch '^PHP 8\.(?:[2-9]|\d{2,})\.\d+ .*\(cli\)') { return $false }
        $builtIn = @(& $Candidate -n -m 2>$null)
        if ($LASTEXITCODE -ne 0) { return $false }
        $extensionDir = Join-Path ([IO.Path]::GetDirectoryName($Candidate)) 'ext'
        $caFile = Join-Path $PSScriptRoot 'ca-certificates.pem'
        $lines = @(
            "extension_dir=`"$($extensionDir.Replace('\','/'))`"",
            "curl.cainfo=`"$($caFile.Replace('\','/'))`"",
            "openssl.cafile=`"$($caFile.Replace('\','/'))`"",
            'upload_max_filesize=16M',
            'post_max_size=40M',
            'max_file_uploads=65',
            'memory_limit=256M',
            'expose_php=Off'
        )
        foreach ($module in $requiredDlls) {
            if ($builtIn -contains $module) { continue }
            if (-not (Test-Path -LiteralPath (Join-Path $extensionDir "php_$module.dll") -PathType Leaf)) { return $false }
            $lines += "extension=$module"
        }
        [IO.File]::WriteAllLines($phpIni, [string[]] $lines, [Text.UTF8Encoding]::new($false))
        $loaded = @(& $Candidate -c $phpIni -m 2>$null)
        if ($LASTEXITCODE -ne 0) { return $false }
        foreach ($module in ($requiredDlls + @('PDO', 'dom', 'xml', 'ctype', 'tokenizer'))) {
            if ($loaded -notcontains $module) { return $false }
        }
        return $true
    } catch {
        return $false
    }
}

function Find-Postgres17 {
    $candidates = @(Join-Path $runtime 'postgresql\bin')
    $onPath = Get-Command pg_ctl.exe -CommandType Application -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($onPath) { $candidates += [IO.Path]::GetDirectoryName($onPath.Source) }
    $candidates += (Join-Path $env:ProgramFiles 'PostgreSQL\17\bin')
    $candidates += @(Get-ChildItem -Path 'C:\laragon\bin\postgresql' -Directory -ErrorAction SilentlyContinue |
        ForEach-Object { Join-Path $_.FullName 'bin' })
    foreach ($bin in ($candidates | Select-Object -Unique)) {
        $control = Join-Path $bin 'pg_ctl.exe'
        if (-not (Test-Path -LiteralPath $control -PathType Leaf) -or
            -not (Test-Path -LiteralPath (Join-Path $bin 'initdb.exe') -PathType Leaf) -or
            -not (Test-Path -LiteralPath (Join-Path $bin 'postgres.exe') -PathType Leaf)) { continue }
        try {
            $version = @(& $control --version 2>$null) -join ' '
            if ($LASTEXITCODE -eq 0 -and $version -match '\(PostgreSQL\)\s+17\.') { return $bin }
        } catch {
            continue
        }
    }
    return $null
}

try {
    if (-not [Environment]::Is64BitOperatingSystem) {
        throw 'Paket Windows ini memerlukan Windows 64-bit dengan dukungan untuk program x64.'
    }
    [IO.Directory]::CreateDirectory($additionalIni) | Out-Null
    [IO.Directory]::CreateDirectory($runtime) | Out-Null
    $env:PHP_INI_SCAN_DIR = $additionalIni
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    $lockPath = Join-Path $state 'runtime.lock'
    try {
        $lock = [IO.File]::Open($lockPath, [IO.FileMode]::OpenOrCreate, [IO.FileAccess]::ReadWrite, [IO.FileShare]::None)
    } catch {
        throw 'Setup PURO sudah berjalan. Lihat terminal yang dibuka sebelumnya.'
    }
    try {
        $portablePhp = Join-Path $runtime 'php\php.exe'
        $candidates = @($portablePhp)
        $onPath = Get-Command php.exe -CommandType Application -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($onPath) { $candidates += $onPath.Source }
        $candidates += @(Get-ChildItem -Path 'C:\laragon\bin\php' -Directory -ErrorAction SilentlyContinue |
            Sort-Object Name -Descending | ForEach-Object { Join-Path $_.FullName 'php.exe' })
        $php = $candidates | Select-Object -Unique | Where-Object { Test-PuroPhp $_ } | Select-Object -First 1
        if (-not $php) {
            Install-Runtime 'PHP 8.4 x64' `
                'https://downloads.php.net/~windows/releases/archives/php-8.4.28-nts-Win32-vs17-x64.zip' `
                'c1f9280588b7b6ad7b518969022f0b0175f106fab9d0639ad0e4ce7cd47ac1ed' `
                (Join-Path $runtime 'php') 'php.exe'
            if (-not (Test-PuroPhp $portablePhp)) {
                throw 'PHP portabel tidak dapat dijalankan. Cek Microsoft Visual C++ Redistributable x64 di README.'
            }
            $php = $portablePhp
        }

        $pgBin = Find-Postgres17
        if (-not $pgBin) {
            Install-Runtime 'PostgreSQL 17 x64' `
                'https://get.enterprisedb.com/postgresql/postgresql-17.12-1-windows-x64-binaries.zip' `
                'a2cd8a774389347b70be7748ac4a37ef43cb6ee539c5fc3e85ae264b15e4a04c' `
                (Join-Path $runtime 'postgresql') 'bin\pg_ctl.exe' 'pgsql/'
            $pgBin = Find-Postgres17
            if (-not $pgBin) {
                throw 'PostgreSQL portabel tidak dapat dijalankan. Cek Microsoft Visual C++ Redistributable x64 di README.'
            }
        }
    } finally {
        $lock.Dispose()
    }

    $env:PHPRC = $phpIni
    & $php -c $phpIni (Join-Path $project 'scripts/local-start.php') $pgBin
    exit $LASTEXITCODE
} catch {
    Write-Host ""
    Write-Host "Setup PURO terhenti. $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
