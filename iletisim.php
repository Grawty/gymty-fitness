<?php
$pageTitle = 'İletişim';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $old['name'] = normalizeText(limitText((string) ($_POST['name'] ?? ''), 100));
    $old['email'] = limitText(trim((string) ($_POST['email'] ?? '')), 150);
    $old['subject'] = normalizeText(limitText((string) ($_POST['subject'] ?? ''), 150));
    $old['message'] = limitText(trim((string) ($_POST['message'] ?? '')), 5000);

    $limit = rateLimitCheck('contact:' . clientIp(), 5, 3600);

    if (!$limit['allowed']) {
        $error = 'Çok fazla mesaj gönderdiniz. Lütfen daha sonra tekrar deneyin.';
    } elseif ($old['name'] === '' || $old['email'] === '' || $old['subject'] === '' || $old['message'] === '') {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif (!isValidEmail($old['email'])) {
        $error = 'Geçerli bir e-posta adresi giriniz.';
    } elseif (mb_strlen($old['message'], 'UTF-8') < 10) {
        $error = 'Mesajınız en az 10 karakter olmalıdır.';
    } else {
        rateLimitHit('contact:' . clientIp(), 3600);

        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (sender_name, sender_email, subject, message) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$old['name'], $old['email'], $old['subject'], $old['message']]);

        setFlash('success', 'Mesajınız başarıyla gönderildi! En kısa sürede dönüş yapacağız.');
        redirect(BASE_URL . '/iletisim');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>BİZİMLE <span class="accent">İLETİŞİM</span></h1>
        <p>Hedeflerine giden yolda sana destek olmak için buradayız, hemen yaz.</p>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="contact-grid">
            <div>
                <?php if ($error): ?>
                    <div class="flash-message flash-error"
                        style="padding:16px;border-radius:8px;margin-bottom:24px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" data-validate>
                    <?php echo csrfField(); ?>
                    <div class="form-group">
                        <label for="name">Adınız *</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Ad Soyad"
                            value="<?php echo sanitize($old['name']); ?>" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label for="email">E-Posta *</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="ornek@email.com"
                            value="<?php echo sanitize($old['email']); ?>" maxlength="150" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Konu *</label>
                        <input type="text" id="subject" name="subject" class="form-control"
                            placeholder="Mesajınızın konusu" value="<?php echo sanitize($old['subject']); ?>"
                            maxlength="150" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Mesaj *</label>
                        <textarea id="message" name="message" class="form-control" placeholder="Mesajınızı buraya yazın..."
                            maxlength="5000" required><?php echo sanitize($old['message']); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Gönder
                    </button>
                </form>
            </div>

            <div class="contact-info-card">
                <div class="contact-info-item">
                    <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="contact-details">
                        <h4>Adres</h4>
                        <p>Nispetiye Mah. Aytar Cad. No: 44<br>
                            Elit Park Sitesi, B Blok, Zemin Kat<br>
                            34340 Beşiktaş / İstanbul</p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-icon"><i class="fas fa-phone"></i></div>
                    <div class="contact-details">
                        <h4>Telefon</h4>
                        <p>+90 (212) 000 00 00</p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                    <div class="contact-details">
                        <h4>E-Posta</h4>
                        <p>info@gymty.com</p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-icon"><i class="fas fa-clock"></i></div>
                    <div class="contact-details">
                        <h4>Çalışma Saatleri</h4>
                        <p>Pazartesi - Cuma: 06:00 - 23:00<br>Cumartesi: 08:00 - 21:00<br>Pazar: 09:00 - 18:00</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
