<?php

require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/rate_limit.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hareketsizlik süresi dolduysa oturumu kapat; session_destroy kullanmıyoruz
// çünkü kullanıcıya gösterilecek flash mesajına ihtiyacımız var.
if (!empty($_SESSION['user_id'])) {
    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

    if ($lastActivity > 0 && (time() - $lastActivity) > SESSION_IDLE_TIMEOUT) {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_tc'], $_SESSION['user_role'], $_SESSION['last_activity']);
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Uzun süre işlem yapmadığınız için oturumunuz sona erdi. Lütfen tekrar giriş yapın.',
        ];
    } else {
        $_SESSION['last_activity'] = time();
    }
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirect(string $url): void
{
    $url = str_replace(["\r", "\n"], '', $url);

    header('Location: ' . $url);
    exit;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Bu sayfayı görüntülemek için giriş yapmalısınız.');
        redirect(BASE_URL . '/giris');
    }
}

function requireAdmin(): void
{
    if (!isLoggedIn() || !isAdmin()) {
        setFlash('error', 'Bu alana erişim yetkiniz yok.');
        redirect(BASE_URL . '/giris');
    }
}

function sanitize(string $input): string
{
    return htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function currentPage(): string
{
    return basename($_SERVER['PHP_SELF'] ?? '', '.php');
}

function setFlash(string $type, string $message): void
{
    $allowedTypes = ['success', 'error', 'warning', 'info'];

    $_SESSION['flash'] = [
        'type' => in_array($type, $allowedTypes, true) ? $type : 'info',
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return $flash;
    }

    return null;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . sanitize(csrfToken()) . '">';
}

function verifyCsrf(?string $token = null): bool
{
    $token ??= $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($token)
        && $token !== ''
        && $sessionToken !== ''
        && hash_equals($sessionToken, $token);
}

function requireCsrf(): void
{
    if (!verifyCsrf()) {
        http_response_code(419);
        exit('Oturum doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.');
    }
}

function loginUser(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_tc'] = $user['tc_no'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();

    clearRememberCookie();

    // Flash mesajı gösterebilmek için temiz bir oturum aç
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): ?string
{
    $decoded = base64_decode(strtr($data, '-_', '+/'), true);

    return $decoded === false ? null : $decoded;
}

function rememberCookieOptions(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

// İmza şifre özetini de kapsadığı için şifre değişince eski çerez geçersiz olur.
function createRememberToken(array $user): string
{
    $expires = time() + REMEMBER_TTL;
    $payload = (int) $user['id'] . ':' . $expires;
    $signature = hash_hmac('sha256', $payload . ':' . $user['password'], APP_KEY);

    return base64UrlEncode($payload . ':' . $signature);
}

function rememberUser(array $user): void
{
    setcookie(REMEMBER_COOKIE, createRememberToken($user), rememberCookieOptions(time() + REMEMBER_TTL));
}

function clearRememberCookie(): void
{
    if (isset($_COOKIE[REMEMBER_COOKIE])) {
        setcookie(REMEMBER_COOKIE, '', rememberCookieOptions(time() - 3600));
        unset($_COOKIE[REMEMBER_COOKIE]);
    }
}

function attemptRememberLogin(PDO $pdo): void
{
    if (isLoggedIn() || empty($_COOKIE[REMEMBER_COOKIE])) {
        return;
    }

    $raw = base64UrlDecode((string) $_COOKIE[REMEMBER_COOKIE]);

    if ($raw === null) {
        clearRememberCookie();
        return;
    }

    $parts = explode(':', $raw);

    if (count($parts) !== 3) {
        clearRememberCookie();
        return;
    }

    [$userId, $expires, $signature] = $parts;

    if (!ctype_digit($userId) || !ctype_digit($expires) || (int) $expires < time()) {
        clearRememberCookie();
        return;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        clearRememberCookie();
        return;
    }

    $expected = hash_hmac('sha256', $userId . ':' . $expires . ':' . $user['password'], APP_KEY);

    if (!hash_equals($expected, $signature)) {
        clearRememberCookie();
        return;
    }

    loginUser($user);
    rememberUser($user);
}

// auth.php her zaman db.php'den sonra yüklendiği için $pdo burada hazır.
// Korumalı sayfalar yönlendirme yapmadan önce oturumu geri yüklemek gerekir.
if (isset($pdo) && $pdo instanceof PDO) {
    attemptRememberLogin($pdo);
}
