<?php
$pageTitle = 'Giriş Yap';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? BASE_URL . '/admin/' : BASE_URL . '/panel');
}

$error = '';
$tcNo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $tcNo = trim((string) ($_POST['tc_no'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // Kaba kuvvet koruması: hem hesap hem IP bazlı sınır
    $accountKey = 'login:account:' . hash('sha256', $tcNo);
    $ipKey = 'login:ip:' . clientIp();

    $accountLimit = rateLimitCheck($accountKey, 5, 900);
    $ipLimit = rateLimitCheck($ipKey, 20, 900);

    if (!$accountLimit['allowed'] || !$ipLimit['allowed']) {
        $retryAfter = max($accountLimit['retry_after'], $ipLimit['retry_after']);
        $minutes = max(1, (int) ceil($retryAfter / 60));

        $error = "Çok fazla başarısız giriş denemesi yaptınız. Lütfen {$minutes} dakika sonra tekrar deneyin.";
    } elseif ($tcNo === '' || $password === '') {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif (!preg_match('/^\d{11}$/', $tcNo)) {
        // Eski kayıtlarla uyum için girişte sağlama algoritması değil, biçim kontrol edilir
        $error = 'T.C. Kimlik Numarası veya şifre hatalı.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE tc_no = ? LIMIT 1');
        $stmt->execute([$tcNo]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Algoritma güncellendiyse şifre özetini yenile
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $update->execute([$newHash, $user['id']]);
                $user['password'] = $newHash;
            }

            loginUser($user);

            if (!empty($_POST['remember_me'])) {
                rememberUser($user);
            }

            rateLimitClear($accountKey);
            rateLimitClear($ipKey);

            setFlash('success', 'Hoş geldiniz, ' . $user['full_name'] . '!');
            redirect($user['role'] === 'admin' ? BASE_URL . '/admin/' : BASE_URL . '/panel');
        }

        rateLimitHit($accountKey, 900);
        rateLimitHit($ipKey, 900);

        $error = 'T.C. Kimlik Numarası veya şifre hatalı.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <h2>GİRİŞ YAP</h2>
        <p class="auth-subtitle">Hazırsan, antrenmana kaldığın yerden devam et</p>

        <?php if ($error): ?>
            <div class="flash-message flash-error" style="margin-bottom:20px;padding:12px 16px;border-radius:8px;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo sanitize($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="tc_no">T.C. Kimlik Numarası</label>
                <input type="text" id="tc_no" name="tc_no" class="form-control" placeholder="11 haneli T.C. Numaranız"
                    value="<?php echo sanitize($tcNo); ?>" required maxlength="11" pattern="\d{11}"
                    inputmode="numeric" autocomplete="username" title="11 haneli TC Kimlik Numarası giriniz">
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
                    autocomplete="current-password" required>
            </div>
            <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" id="remember_me" name="remember_me"
                    style="width:auto; margin:0; cursor:pointer;">
                <label for="remember_me" style="margin:0; font-weight:normal; cursor:pointer;">Beni Hatırla</label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Giriş Yap
            </button>
        </form>

        <div class="auth-footer">
            Henüz üye değil misin? <a href="<?php echo sanitize(BASE_URL); ?>/kayit">Aramıza Katıl</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
