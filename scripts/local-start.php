<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

$project = dirname(__DIR__);
chdir($project);

if (! is_file($project.'/vendor/autoload.php')) {
    fwrite(STDERR, "Dependensi Laravel belum ada. Ekstrak seluruh isi ZIP siap jalan sebelum menjalankan PURO.\n");
    exit(1);
}
require $project.'/vendor/autoload.php';

function runSetup(array $command, string $failure, array $environment = []): void
{
    $process = new Process($command, null, $environment, null, 120);
    $process->run(static function (string $type, string $data): void {
        fwrite($type === Process::ERR ? STDERR : STDOUT, $data);
    });
    if (! $process->isSuccessful()) {
        throw new RuntimeException($failure);
    }
}

function freePort(int $preferred): int
{
    for ($candidate = $preferred; $candidate <= $preferred + 19; $candidate++) {
        if ($listener = @stream_socket_server('tcp://127.0.0.1:'.$candidate)) {
            fclose($listener);

            return $candidate;
        }
    }
    $listener = @stream_socket_server('tcp://127.0.0.1:0');
    if ($listener === false) {
        throw new RuntimeException('Tidak dapat memilih port lokal.');
    }
    $address = stream_socket_get_name($listener, false);
    fclose($listener);

    return (int) substr(strrchr($address, ':'), 1);
}

function postgresRunning(string $pgCtl, string $data): bool
{
    return is_file($data.'/PG_VERSION') && (new Process([$pgCtl, '-D', $data, 'status']))->run() === 0;
}

$result = 0;
$server = null;
$lock = null;
$stopDatabase = false;
$stopRequested = false;
$signal = static function () use (&$stopRequested): void {
    $stopRequested = true;
};
if (function_exists('sapi_windows_set_ctrl_handler')) {
    sapi_windows_set_ctrl_handler($signal);
} elseif (function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGINT, $signal);
    pcntl_signal(SIGTERM, $signal);
}

try {
    $files = new Filesystem;
    $state = $project.'/.puro-local';
    $files->ensureDirectoryExists($state);
    $lock = fopen($state.'/app.lock', 'c');
    if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('PURO sudah berjalan dari folder ini. Lihat terminal sebelumnya.');
    }

    $bin = $argv[1] ?? '';
    if (! $bin || ! extension_loaded('pdo_pgsql')) {
        throw new RuntimeException('Runtime belum disiapkan. Jalankan Mulai-PURO.cmd dari folder proyek.');
    }
    $suffix = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';
    $pgCtl = $bin.'/pg_ctl'.$suffix;
    $initDb = $bin.'/initdb'.$suffix;
    $data = $state.'/postgres';
    $envPath = $project.'/.env.local';
    $databaseLog = $state.'/postgres.log';
    $webLog = $state.'/laravel-console.log';

    if (! is_file($envPath)) {
        if (is_file($data.'/PG_VERSION')) {
            throw new RuntimeException('.env.local hilang. Pakai file .env.local dari instalasi ini untuk membuka database lama.');
        }
        $template = file_get_contents($project.'/.env.example');
        $template = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), $template);
        $template = preg_replace('/^DB_PASSWORD=.*$/m', 'DB_PASSWORD='.bin2hex(random_bytes(24)), $template);
        $template = str_replace(['PURO_DEMO_MODE=false', 'APP_DEBUG=true', 'DB_PORT=5432'], ['PURO_DEMO_MODE=true', 'APP_DEBUG=false', 'DB_PORT=55432'], $template);
        $files->replace($envPath, $template, 0600);
    }

    $config = Dotenv::parse(file_get_contents($envPath));
    $key = $config['APP_KEY'] ?? '';
    $password = $config['DB_PASSWORD'] ?? '';
    if (! str_starts_with($key, 'base64:') || strlen(base64_decode(substr($key, 7), true) ?: '') !== 32 ||
        $password === '' || strpbrk($password, "\r\n") !== false) {
        throw new RuntimeException('APP_KEY atau DB_PASSWORD di .env.local tidak valid. Periksa konfigurasi paket.');
    }

    if (! is_file($data.'/PG_VERSION')) {
        echo "Menyiapkan database PostgreSQL lokal milik PURO.\n";
        $initialData = $state.'/postgres-init-'.bin2hex(random_bytes(8));
        $passwordFile = tempnam($state, 'pg-pw-');
        if ($passwordFile === false) {
            throw new RuntimeException('Tidak dapat menulis konfigurasi database.');
        }
        try {
            if (file_put_contents($passwordFile, $password."\n", LOCK_EX) === false) {
                throw new RuntimeException('Tidak dapat menyimpan konfigurasi database.');
            }
            runSetup(
                [$initDb, '-D', $initialData, '--username=puro', '--encoding=UTF8', '--no-locale', '--auth=scram-sha-256', '--pwfile='.$passwordFile],
                'PostgreSQL gagal membuat database. Lihat pesan initdb di atas.'
            );
            if (! rename($initialData, $data)) {
                throw new RuntimeException('Folder database lokal tidak dapat disiapkan.');
            }
        } finally {
            unlink($passwordFile);
            $files->deleteDirectory($initialData);
        }
    }

    $stopDatabase = true;
    if (postgresRunning($pgCtl, $data)) {
        $pidFile = file($data.'/postmaster.pid', FILE_IGNORE_NEW_LINES);
        $databasePort = (int) ($pidFile[3] ?? 0);
        if ($databasePort < 1 || $databasePort > 65535) {
            throw new RuntimeException('Port PostgreSQL lokal tidak bisa dibaca.');
        }
    } else {
        $databasePort = freePort(55432);
        runSetup(
            [$pgCtl, '-D', $data, '-l', $databaseLog, '-w', '-t', '45', '-o', "-h 127.0.0.1 -p {$databasePort} -c unix_socket_directories=", 'start'],
            'PostgreSQL gagal berjalan. Buka .puro-local/postgres.log untuk melihat penyebabnya.'
        );
    }

    $pdo = new PDO("pgsql:host=127.0.0.1;port={$databasePort};dbname=postgres;connect_timeout=5", 'puro', $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if (! $pdo->query("SELECT 1 FROM pg_database WHERE datname = 'purocare'")->fetchColumn()) {
        $pdo->exec('CREATE DATABASE purocare OWNER puro');
    }
    unset($pdo);

    $httpPort = freePort(8000);
    $url = 'http://127.0.0.1:'.$httpPort;
    $environment = array_replace($config, [
        'APP_ENV' => 'local',
        'APP_DEBUG' => 'false',
        'APP_URL' => $url,
        'DB_CONNECTION' => 'pgsql',
        'DB_URL' => false,
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => (string) $databasePort,
        'DB_DATABASE' => 'purocare',
        'DB_USERNAME' => 'puro',
        'DB_SSLMODE' => 'disable',
        'PURO_DEMO_MODE' => 'true',
    ]);
    $php = [PHP_BINARY];
    if ($ini = php_ini_loaded_file()) {
        array_push($php, '-c', $ini);
    }
    $artisan = [...$php, 'artisan'];

    echo "Menyiapkan Laravel dan akun demo.\n";
    runSetup([...$artisan, 'config:clear', '--no-interaction'], 'Cache konfigurasi Laravel tidak dapat disiapkan.', $environment);
    runSetup([...$artisan, 'migrate', '--seed', '--force', '--no-interaction'], 'Migrasi Laravel gagal. Lihat pesan di atas.', $environment);
    $publicStorage = $project.'/public/storage';
    if (! is_dir($publicStorage)) {
        runSetup([...$artisan, 'storage:link', '--no-interaction'], 'Laravel gagal menghubungkan folder unggahan.', $environment);
    }
    clearstatcache(true, $publicStorage);
    $storageTarget = realpath($project.'/storage/app/public');
    if ($storageTarget === false || realpath($publicStorage) !== $storageTarget) {
        throw new RuntimeException('Folder public/storage tidak terhubung ke storage/app/public. Ekstrak ZIP ke folder lokal Windows.');
    }

    $sync = [...$artisan, 'puro:knowledge-sync', '--no-interaction'];
    if (trim($config['GEMINI_API_KEY'] ?? '') !== '') {
        echo "Mengindeks panduan dengan Gemini.\n";
        $sync[] = '--embed';
    }
    try {
        runSetup($sync, 'Indeks panduan gagal. Periksa dokumen atau API key Gemini.', $environment);
    } catch (RuntimeException $error) {
        // The customer can still order and use the admin handoff if Gemini is unavailable.
        echo $error->getMessage()." Aplikasi tetap dijalankan.\n";
    }

    if (! $stopRequested) {
        $router = $project.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
        $server = new Process([...$php, '-S', '127.0.0.1:'.$httpPort, $router], $project.'/public', $environment, null, null);
        $server->start(static function (string $type, string $data) use ($webLog): void {
            file_put_contents($webLog, $data, FILE_APPEND | LOCK_EX);
        });

        $ready = false;
        for ($attempt = 0; $attempt < 70 && $server->isRunning() && ! $stopRequested; $attempt++) {
            if ($connection = @stream_socket_client('tcp://127.0.0.1:'.$httpPort, $code, $message, 0.1)) {
                fclose($connection);
                $ready = true;
                break;
            }
            usleep(100000);
        }
        if (! $ready && ! $stopRequested) {
            throw new RuntimeException('Web PURO belum bisa dibuka. Periksa .puro-local/laravel-console.log.');
        }
        if ($ready) {
            echo "\nPURO siap dibuka.\nCustomer  {$url}\nStaf      {$url}/staff/login\n";
            echo "Demo      superadmin / Demo123!\n\nBiarkan jendela ini terbuka. Tekan Ctrl+C untuk berhenti.\n";
        }
        while (! $stopRequested && $server->isRunning()) {
            $server->clearOutput();
            $server->clearErrorOutput();
            usleep(250000);
        }
        if (! $stopRequested && $server->getExitCode() !== 0) {
            throw new RuntimeException('Server web berhenti. Periksa .puro-local/laravel-console.log.');
        }
    }
} catch (Throwable $error) {
    fwrite(STDERR, "\nSetup PURO terhenti. ".$error->getMessage()."\n");
    $result = 1;
} finally {
    if ($server?->isRunning()) {
        $server->stop(2);
    }
    if ($stopDatabase && postgresRunning($pgCtl, $data)) {
        $stop = new Process([$pgCtl, '-D', $data, '-w', '-t', '15', '-m', 'fast', 'stop']);
        $stop->run();
        if (! $stop->isSuccessful()) {
            fwrite(STDERR, "PostgreSQL belum berhenti. Jendela mulai berikutnya bisa memakai database ini kembali.\n");
        }
    }
    if (is_resource($lock)) {
        fclose($lock);
    }
}

exit($result);
