<?php
$pageTitle = 'Güvenli Ödeme';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    setFlash('warning', 'Üyelik satın almak için giriş yapmalısınız.');
    redirect(BASE_URL . '/giris');
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_memberships WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE()");
$stmt->execute([$userId]);
if ((int) $stmt->fetchColumn() > 0) {
    setFlash('warning', 'Zaten aktif bir aboneliğiniz bulunmaktadır.');
    redirect(BASE_URL . '/panel');
}

$planId = isset($_GET['plan']) ? (int) $_GET['plan'] : 0;

$stmt = $pdo->prepare('SELECT * FROM memberships WHERE id = ?');
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    setFlash('error', 'Geçersiz paket seçimi.');
    redirect(BASE_URL . '/paketler');
}

$price = (float) $plan['price'];

$durations = [1 => 0, 3 => 8, 6 => 15, 12 => 25];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $selectedDuration = isset($_POST['duration']) ? (int) $_POST['duration'] : 1;

    if (!array_key_exists($selectedDuration, $durations)) {
        $selectedDuration = 1;
    }

    $discount = $durations[$selectedDuration];
    $monthly = $selectedDuration === 1 ? $price : round($price * (100 - $discount) / 100, 2);
    $paidAmount = round($monthly * $selectedDuration, 2);

    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+{$selectedDuration} months"));

    try {
        $pdo->beginTransaction();

        // Aynı anda gelen ikinci satın alma isteğini engelle
        $lock = $pdo->prepare(
            "SELECT id FROM user_memberships WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE() FOR UPDATE"
        );
        $lock->execute([$userId]);

        if ($lock->fetch()) {
            $pdo->rollBack();
            setFlash('warning', 'Zaten aktif bir aboneliğiniz bulunmaktadır.');
            redirect(BASE_URL . '/panel');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO user_memberships (user_id, membership_id, paid_amount, start_date, end_date, status)
             VALUES (?, ?, ?, ?, ?, 'active')"
        );
        $stmt->execute([$userId, (int) $plan['id'], $paidAmount, $startDate, $endDate]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('[GYMTY] Üyelik kaydı hatası: ' . $e->getMessage());
        setFlash('error', 'Ödeme kaydedilemedi. Lütfen tekrar deneyin.');
        redirect(BASE_URL . '/odeme?plan=' . (int) $plan['id']);
    }

    setFlash('success', $plan['name'] . " paketi ({$selectedDuration} Ay) başarıyla aktifleştirildi!");
    redirect(BASE_URL . '/panel');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>GÜVENLİ <span class="accent">ÖDEME</span></h1>
        <p>Seçilen Paket: <strong>
                <?php echo sanitize($plan['name']); ?>
            </strong> (₺
            <?php echo number_format($price, 0, ',', '.'); ?>)
        </p>
    </div>
</div>

<style>
    .duration-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        margin-bottom: 32px;
    }

    .duration-card {
        border: var(--border-width) solid var(--border);
        padding: 16px;
        background: #fff;
        cursor: pointer;
        transition: all var(--transition);
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 4px 4px 0px 0px #000;
    }

    .duration-card:hover {
        transform: translate(-2px, -2px);
        box-shadow: 6px 6px 0px 0px #000;
    }

    .duration-card.active {
        background: var(--text-primary);
        color: #fff;
    }

    .duration-card.active .discount-badge {
        background: var(--accent);
        color: #000;
    }

    .dur-info {
        display: flex;
        flex-direction: column;
    }

    .dur-title {
        font-family: var(--font-heading);
        font-size: 1.5rem;
        margin-bottom: 4px;
    }

    .dur-monthly {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .dur-price-wrap {
        text-align: right;
    }

    .dur-price {
        font-family: var(--font-heading);
        font-size: 2rem;
        color: var(--accent);
    }

    .duration-card:not(.active) .dur-price {
        color: var(--text-primary);
    }

    .discount-badge {
        display: inline-block;
        background: var(--text-primary);
        color: var(--accent);
        font-weight: bold;
        font-size: 0.8rem;
        padding: 2px 8px;
        margin-left: 8px;
        border: 1px solid currentColor;
        font-family: var(--font-body);
    }
</style>

<section class="section">
    <div class="container" style="max-width: 1000px; margin: 0 auto;">

        <form method="POST">
            <?php echo csrfField(); ?>
            <div class="checkout-grid">

                <div class="duration-section">
                    <h3
                        style="margin-bottom: 16px; font-family:var(--font-heading); font-size:1.8rem; border-left: 6px solid var(--accent); padding-left:12px;">
                        AYLIK PLAN SEÇİMİ</h3>
                    <div class="duration-grid">
                        <?php foreach ($durations as $months => $discount): ?>
                            <?php
                            $monthly = $months === 1 ? $price : round($price * (100 - $discount) / 100, 2);
                            $total = round($monthly * $months, 2);
                            ?>
                            <label class="duration-card <?php echo $months === 1 ? 'active' : ''; ?>"
                                onclick="selectDuration(this, <?php echo (float) $total; ?>)">
                                <input type="radio" name="duration" value="<?php echo (int) $months; ?>"
                                    <?php echo $months === 1 ? 'checked' : ''; ?> style="display:none;">
                                <div class="dur-info">
                                    <div class="dur-title">
                                        <?php echo (int) $months; ?> Ay
                                        <?php if ($discount > 0): ?>
                                            <span class="discount-badge">%<?php echo (int) $discount; ?> İndirim</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($months > 1): ?>
                                        <div class="dur-monthly">₺<?php echo number_format($monthly, 0, ',', '.'); ?> / ay
                                            (Aylık Karşılık)</div>
                                    <?php else: ?>
                                        <div class="dur-monthly">Standart Fiyat</div>
                                    <?php endif; ?>
                                </div>
                                <div class="dur-price-wrap">
                                    <div class="dur-price">₺<?php echo number_format($total, 0, ',', '.'); ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <div class="auth-card" style="margin-top:0; width:100%; max-width:100%;">
                        <h3>Kredi Kartı Bilgileri</h3>
                        <p style="margin-bottom: 24px; color: red; font-size: 0.9rem;">
                            * Bu sistem demo amaçlıdır. Lütfen gerçek bir kart bilgisi girmeyiniz. Girdiğiniz veriler
                            sunucuya gönderilmez ve veritabanına kayıt edilmez.
                        </p>
                        <div class="form-group">
                            <label for="card_name">Kart Üzerindeki İsim</label>
                            <input type="text" id="card_name" name="card_name" class="form-control"
                                placeholder="Ad Soyad" maxlength="60" autocomplete="off" required>
                        </div>
                        <div class="form-group">
                            <label for="card_number">Kart Numarası</label>
                            <input type="text" id="card_number" name="card_number" class="form-control"
                                placeholder="0000 0000 0000 0000" maxlength="19" inputmode="numeric"
                                autocomplete="off" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="card_expiry">Son Kullanma Tarihi</label>
                                <input type="text" id="card_expiry" name="card_expiry" class="form-control"
                                    placeholder="AA/YY" maxlength="5" inputmode="numeric" autocomplete="off" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="card_cvv">CVV/CVC</label>
                                <input type="text" id="card_cvv" name="card_cvv" class="form-control"
                                    placeholder="123" maxlength="3" inputmode="numeric" autocomplete="off" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 24px; width:100%;">
                            <i class="fas fa-lock"></i> ₺<span
                                id="pay-btn-amount"><?php echo number_format($price, 0, ',', '.'); ?></span> Öde
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</section>

<script>
    function selectDuration(element, total) {
        document.querySelectorAll('.duration-card').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
        document.getElementById('pay-btn-amount').innerText = new Intl.NumberFormat('tr-TR').format(total);
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
