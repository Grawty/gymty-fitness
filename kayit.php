<?php
$pageTitle = 'Üye Ol';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/panel');
}

$error = '';
$old = ['full_name' => '', 'tc_no' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $old['full_name'] = normalizeText(limitText((string) ($_POST['full_name'] ?? ''), 100));
    $old['tc_no'] = trim((string) ($_POST['tc_no'] ?? ''));
    $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    $phone = preg_replace('/\s+/', '', $old['phone']) ?? '';

    if ($old['full_name'] === '' || $old['tc_no'] === '' || $password === '') {
        $error = 'Lütfen zorunlu alanları doldurun.';
    } elseif (mb_strlen($old['full_name'], 'UTF-8') < 3) {
        $error = 'Lütfen ad ve soyadınızı tam olarak girin.';
    } elseif (!isValidTcNo($old['tc_no'])) {
        $error = 'Geçerli bir T.C. Kimlik Numarası giriniz.';
    } elseif (!isValidPhone($phone)) {
        $error = 'Telefon numarası 05XX XXX XX XX biçiminde olmalıdır.';
    } elseif (mb_strlen($password, 'UTF-8') < 8) {
        $error = 'Şifre en az 8 karakter olmalıdır.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $error = 'Şifre en az bir harf ve bir rakam içermelidir.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        $registerLimit = rateLimitCheck('register:' . clientIp(), 10, 3600);

        if (!$registerLimit['allowed']) {
            $error = 'Çok fazla kayıt denemesi yapıldı. Lütfen daha sonra tekrar deneyin.';
        } else {
            rateLimitHit('register:' . clientIp(), 3600);

            $check = $pdo->prepare('SELECT id FROM users WHERE tc_no = ? LIMIT 1');
            $check->execute([$old['tc_no']]);

            if ($check->fetch()) {
                $error = 'Bu T.C. Kimlik Numarası zaten kayıtlı.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    "INSERT INTO users (full_name, tc_no, phone, password, role) VALUES (?, ?, ?, ?, 'member')"
                );
                $stmt->execute([$old['full_name'], $old['tc_no'], $phone, $hashedPassword]);

                $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([(int) $pdo->lastInsertId()]);
                $user = $stmt->fetch();

                if ($user) {
                    loginUser($user);
                    setFlash('success', 'Kayıt başarılı! Hoş geldiniz, ' . $user['full_name'] . '!');
                    redirect(BASE_URL . '/panel');
                }

                $error = 'Kayıt oluşturulamadı. Lütfen tekrar deneyin.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <h2>AİLEYE KATIL</h2>
        <p class="auth-subtitle">Hedeflerine ulaşmak için hemen ücretsiz kaydını oluştur</p>

        <?php if ($error): ?>
            <div class="flash-message flash-error" style="margin-bottom:20px;padding:12px 16px;border-radius:8px;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo sanitize($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="full_name">Ad Soyad *</label>
                <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Ad Soyad"
                    value="<?php echo sanitize($old['full_name']); ?>" maxlength="100" autocomplete="name" required>
            </div>
            <div class="form-group">
                <label for="tc_no">T.C. Kimlik Numarası *</label>
                <input type="text" id="tc_no" name="tc_no" class="form-control" placeholder="11 haneli T.C. Numaranız"
                    value="<?php echo sanitize($old['tc_no']); ?>" required maxlength="11" pattern="\d{11}"
                    inputmode="numeric" autocomplete="off" title="11 haneli TC Kimlik Numarası giriniz">
            </div>
            <div class="form-group">
                <label for="phone">Telefon</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="0532 000 0000"
                    value="<?php echo sanitize($old['phone']); ?>" maxlength="15" autocomplete="tel">
            </div>
            <div class="form-group">
                <label for="password">Şifre * <small style="color:var(--text-muted);">(min. 8 karakter, harf ve
                        rakam)</small></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
                    autocomplete="new-password" minlength="8" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Şifre Tekrar *</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                    placeholder="••••••••" autocomplete="new-password" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-user-plus"></i> Kayıt Ol
            </button>
        </form>

        <div class="auth-footer">
            Zaten bir hesabın var mı? <a href="<?php echo sanitize(BASE_URL); ?>/giris">Giriş Yap</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
