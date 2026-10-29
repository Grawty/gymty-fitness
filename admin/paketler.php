<?php
$pageTitle = 'Paket Yönetimi';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    requireCsrf();

    $updateId = (int) $_POST['update_id'];
    $name = normalizeText(limitText((string) ($_POST['name'] ?? ''), 100));
    $price = (float) ($_POST['price'] ?? 0);
    $duration = (int) ($_POST['duration_months'] ?? 1);
    $features = limitText(trim((string) ($_POST['features'] ?? '')), 2000);
    $isPopular = isset($_POST['is_popular']) ? 1 : 0;

    if ($name === '' || $price <= 0 || $duration < 1 || $duration > 36) {
        setFlash('error', 'Lütfen paket bilgilerini geçerli değerlerle doldurun.');
        redirect(BASE_URL . '/admin/paketler');
    }

    $stmt = $pdo->prepare("UPDATE memberships SET name = ?, price = ?, duration_months = ?, features = ?, is_popular = ? WHERE id = ?");
    $stmt->execute([$name, $price, $duration, $features, $isPopular, $updateId]);

    setFlash('success', 'Paket başarıyla güncellendi.');
    redirect(BASE_URL . '/admin/paketler');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new'])) {
    requireCsrf();

    $name = normalizeText(limitText((string) ($_POST['new_name'] ?? ''), 100));
    $price = (float) ($_POST['new_price'] ?? 0);
    $duration = (int) ($_POST['new_duration'] ?? 1);
    $features = limitText(trim((string) ($_POST['new_features'] ?? '')), 2000);

    if ($name === '' || $price <= 0 || $duration < 1 || $duration > 36) {
        setFlash('error', 'Lütfen paket bilgilerini geçerli değerlerle doldurun.');
        redirect(BASE_URL . '/admin/paketler');
    }

    $stmt = $pdo->prepare("INSERT INTO memberships (name, price, duration_months, features, is_popular) VALUES (?, ?, ?, ?, 0)");
    $stmt->execute([$name, $price, $duration, $features]);

    setFlash('success', 'Yeni paket başarıyla eklendi.');
    redirect(BASE_URL . '/admin/paketler');
}

$plans = $pdo->query("SELECT * FROM memberships ORDER BY price ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        <h1 class="admin-title">PAKET <span class="accent">YÖNETİMİ</span></h1>
        <p class="admin-subtitle">Üyelik paketlerini düzenleyin veya yeni paket ekleyin.</p>

        <div class="dashboard-header" style="margin-bottom:0px; padding-bottom: 0px; border-bottom: none;">
            <div class="admin-nav" style="margin-bottom: 24px;">
                <a href="<?php echo BASE_URL; ?>/admin/">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/admin/uyeler">Üyeler</a>
                <a href="<?php echo BASE_URL; ?>/admin/paketler" class="active">Paketler</a>
            </div>
            <div class="dashboard-actions" style="margin-bottom: 24px;">
                <a href="<?php echo BASE_URL; ?>/cikis" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i>
                    Çıkış Yap</a>
            </div>
        </div>

        <h2 style="font-family:var(--font-heading);font-size:1.6rem;letter-spacing:1px;margin-bottom:20px;">MEVCUT
            PAKETLER</h2>

        <div class="plans-grid" style="margin-bottom:48px;">
            <?php foreach ($plans as $plan): ?>
                <div class="plan-card">
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="update_id" value="<?php echo (int) $plan['id']; ?>">

                        <div class="form-group">
                            <label>Paket Adı</label>
                            <input type="text" name="name" class="form-control"
                                value="<?php echo sanitize($plan['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Fiyat (₺)</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0.01"
                                value="<?php echo (float) $plan['price']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Süre (Ay)</label>
                            <input type="number" name="duration_months" class="form-control"
                                value="<?php echo (int) $plan['duration_months']; ?>" min="1" max="36" required>
                        </div>
                        <div class="form-group">
                            <label>Özellikler <small style="color:var(--text-muted);">(| ile ayırın)</small></label>
                            <textarea name="features" class="form-control"
                                rows="3"><?php echo sanitize($plan['features']); ?></textarea>
                        </div>
                        <div class="form-group" style="display:flex;align-items:center;gap:10px;">
                            <input type="checkbox" name="is_popular" id="pop_<?php echo (int) $plan['id']; ?>" <?php echo !empty($plan['is_popular']) ? 'checked' : ''; ?>>
                            <label for="pop_<?php echo (int) $plan['id']; ?>"
                                style="margin:0;text-transform:none;letter-spacing:0;">Popüler olarak işaretle</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-sm">
                            <i class="fas fa-save"></i> Güncelle
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 style="font-family:var(--font-heading);font-size:1.6rem;letter-spacing:1px;margin-bottom:20px;">YENİ PAKET
            EKLE</h2>

        <div style="max-width:500px;">
            <div class="plan-card">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="add_new" value="1">
                    <div class="form-group">
                        <label>Paket Adı</label>
                        <input type="text" name="new_name" class="form-control" placeholder="Örn: Platinum" required>
                    </div>
                    <div class="form-group">
                        <label>Fiyat (₺)</label>
                        <input type="number" name="new_price" class="form-control" step="0.01" placeholder="999.00"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Süre (Ay)</label>
                        <input type="number" name="new_duration" class="form-control" value="1" min="1" max="36" required>
                    </div>
                    <div class="form-group">
                        <label>Özellikler <small style="color:var(--text-muted);">(| ile ayırın)</small></label>
                        <textarea name="new_features" class="form-control" rows="3"
                            placeholder="Özellik 1|Özellik 2|Özellik 3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-plus"></i> Paket Ekle
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>