<?php
$pageTitle = 'Panelim';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

if (isAdmin()) {
    redirect(BASE_URL . '/admin/');
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT um.*, m.name AS plan_name, m.duration_months, m.price
    FROM user_memberships um
    JOIN memberships m ON um.membership_id = m.id
    WHERE um.user_id = ? AND um.status = 'active' AND um.end_date >= CURDATE()
    ORDER BY um.end_date DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$activeMembership = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT um.*, m.name AS plan_name, m.price
    FROM user_memberships um
    JOIN memberships m ON um.membership_id = m.id
    WHERE um.user_id = ?
    ORDER BY um.created_at DESC
");
$stmt->execute([$userId]);
$membershipHistory = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="dashboard-section">
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>Hoş Geldin, <span class="accent">
                        <?php echo sanitize($user['full_name'] ?? $_SESSION['user_name'] ?? 'Misafir'); ?>
                    </span></h1>
                <p style="color:var(--text-secondary);">Üyelik durumunuzu ve bilgilerinizi buradan takip edebilirsiniz.
                </p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo BASE_URL; ?>/paketler" class="btn btn-primary"><i class="fas fa-plus"></i> Üyelik
                    Al</a>
                <a href="<?php echo BASE_URL; ?>/cikis" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i>
                    Çıkış Yap</a>
            </div>
        </div>

        <div class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-icon"><i class="fas fa-id-card"></i></div>
                <div class="admin-stat-info">
                    <h3>Üyelik Durumu</h3>
                    <div class="number">
                        <?php if ($activeMembership): ?>
                            <span class="badge badge-active">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-expired">Pasif</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon"><i class="fas fa-crown"></i></div>
                <div class="admin-stat-info">
                    <h3>Mevcut Paket</h3>
                    <div class="number" style="font-size: 1.2rem;">
                        <?php echo $activeMembership ? sanitize($activeMembership['plan_name']) : '—'; ?>
                    </div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="admin-stat-info">
                    <h3>Bitiş Tarihi</h3>
                    <div class="number" style="font-size:1.2rem;">
                        <?php echo $activeMembership ? date('d.m.Y', strtotime($activeMembership['end_date'])) : '—'; ?>
                    </div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon"><i class="fas fa-id-badge"></i></div>
                <div class="admin-stat-info">
                    <h3>T.C. Kimlik</h3>
                    <div class="number" style="font-size:1rem;word-break:break-all;">
                        <?php echo sanitize($user['tc_no'] ?? $_SESSION['user_tc'] ?? '-'); ?>
                    </div>
                </div>
            </div>
        </div>

        <h2 style="font-family:var(--font-heading);font-size:1.8rem;letter-spacing:1px;margin-bottom:20px;">
            ÜYELİK GEÇMİŞİ
        </h2>

        <?php if (empty($membershipHistory)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>Savaş meydanına adım atılmadı</h3>
                <p>Gücünü kanıtlamak ve dönüşümü başlatmak için bir savaş planı seç!</p>
                <a href="<?php echo BASE_URL; ?>/paketler" class="btn btn-primary" style="margin-top:16px;">Savaş
                    Planlarını İncele</a>
            </div>
        <?php else: ?>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Paket</th>
                            <th>Başlangıç</th>
                            <th>Bitiş</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membershipHistory as $m): ?>
                            <tr>
                                <td style="font-weight:600;">
                                    <?php echo sanitize($m['plan_name']); ?>
                                </td>
                                <td>
                                    <?php echo date('d.m.Y', strtotime($m['start_date'])); ?>
                                </td>
                                <td>
                                    <?php echo date('d.m.Y', strtotime($m['end_date'])); ?>
                                </td>
                                <td style="color:var(--accent);font-weight:600;">₺
                                    <?php
                                    $displayPrice = $m['paid_amount'] !== null ? $m['paid_amount'] : $m['price'];
                                    echo number_format((float) $displayPrice, 0, ',', '.');
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $displayStatus = $m['status'];
                                    if ($displayStatus === 'active' && strtotime($m['end_date']) < strtotime('today')) {
                                        $displayStatus = 'expired';
                                    }

                                    $statusClass = match ($displayStatus) {
                                        'active' => 'badge-active',
                                        'expired' => 'badge-expired',
                                        'cancelled' => 'badge-cancelled',
                                        default => ''
                                    };
                                    $statusText = match ($displayStatus) {
                                        'active' => 'Aktif',
                                        'expired' => 'Süresi Doldu',
                                        'cancelled' => 'İptal',
                                        default => $displayStatus
                                    };
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>