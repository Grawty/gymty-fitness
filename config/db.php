<?php

require_once __DIR__ . '/env.php';

loadEnv(dirname(__DIR__) . '/.env');

define('APP_ENV', env('APP_ENV', 'production') ?? 'production');
define('APP_DEBUG', APP_ENV === 'local');
define('APP_TIMEZONE', env('APP_TIMEZONE', 'Europe/Istanbul') ?? 'Europe/Istanbul');

$appKey = (string) env('APP_KEY', '');

if ($appKey === '') {
    http_response_code(500);
    exit('Yapılandırma hatası: APP_KEY tanımlı değil. .env.example dosyasını .env olarak kopyalayıp APP_KEY değerini doldurun.');
}

define('APP_KEY', $appKey);

// Alt klasör kurulumlarında BASE_URL'i otomatik bulur
function detectBaseUrl(): string
{
    $appRoot = str_replace('\\', '/', (string) realpath(dirname(__DIR__)));
    $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

    $docRoot = rtrim($docRoot, '/');

    if ($docRoot !== '' && $appRoot !== '' && str_starts_with($appRoot, $docRoot)) {
        return rtrim(substr($appRoot, strlen($docRoot)), '/');
    }

    return '';
}

$baseUrl = trim((string) env('BASE_URL', ''));

define('BASE_URL', rtrim($baseUrl !== '' ? $baseUrl : detectBaseUrl(), '/'));

date_default_timezone_set(APP_TIMEZONE);

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', APP_DEBUG ? '1' : '0');

if (!APP_DEBUG) {
    set_exception_handler(function (Throwable $e): void {
        error_log('[GYMTY] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        http_response_code(500);
        exit('Beklenmeyen bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
    });
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Cross-Origin-Opener-Policy: same-origin');
}

define('SESSION_NAME', 'gymty_session');
define('SESSION_IDLE_TIMEOUT', (int) (env('SESSION_IDLE_TIMEOUT', '7200') ?? 7200));
define('REMEMBER_COOKIE', 'gymty_remember');
define('REMEMBER_TTL', 60 * 60 * 24 * 30);

// session_start() çağrılmadan önce yapılandırılmalı
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', (string) max(1440, SESSION_IDLE_TIMEOUT));

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name(SESSION_NAME);
}

define('DB_HOST', env('DB_HOST', 'localhost') ?? 'localhost');
define('DB_NAME', env('DB_NAME', '') ?? '');
define('DB_USER', env('DB_USER', '') ?? '');
define('DB_PASS', env('DB_PASS', '') ?? '');
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4') ?? 'utf8mb4');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ]);
} catch (PDOException $e) {
    error_log('[GYMTY] Veritabanı bağlantı hatası: ' . $e->getMessage());

    http_response_code(503);
    exit('Veritabanı bağlantı hatası. Lütfen daha sonra tekrar deneyin.');
}
