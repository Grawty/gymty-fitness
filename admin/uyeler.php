<?php
$pageTitle = 'Üye Yönetimi';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    requireCsrf();

    $deleteId = (int) $_POST['delete_id'];

    if ($deleteId !== (int) $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'member'");
        $stmt->execute([$deleteId]);
        setFlash('success', 'Üye başarıyla silindi.');
    } else {
        setFlash('error', 'Kendinizi silemezsiniz.');
    }

    redirect(BASE_URL . '/admin/uyeler');
}

$members = $pdo->query("
    SELECT u.*,
        (SELECT m.name FROM user_memberships um JOIN memberships m ON um.membership_id = m.id
         WHERE um.user_id = u.id AND um.status = 'active' AND um.end_date >= CURDATE()
         ORDER BY um.end_date DESC LIMIT 1) AS active_plan
    FROM users u
    WHERE u.role = 'member'
    ORDER BY u.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        <h1 class="admin-title">ÜYE <span class="accent">YÖNETİMİ</span></h1>
        <p class="admin-subtitle">Tüm üyeleri görüntüleyin ve yönetin.</p>

        <div class="dashboard-header" style="margin-bottom:0px; padding-bottom: 0px; border-bottom: none;">
            <div class="admin-nav" style="margin-bottom: 24px;">
                <a href="<?php echo BASE_URL; ?>/admin/">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/admin/uyeler" class="active">Üyeler</a>
                <a href="<?php echo BASE_URL; ?>/admin/paketler">Paketler</a>
            </div>
            <div class="dashboard-actions" style="margin-bottom: 24px;">
                <a href="<?php echo BASE_URL; ?>/cikis" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i>
                    Çıkış Yap</a>
            </div>
        </div>

        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Soyad</th>
                        <th>T.C. Kimlik Numarası</th>
                        <th>Telefon</th>
                        <th>Aktif Paket</th>
                        <th>Kayıt Tarihi</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td>#
                                <?php echo (int) $member['id']; ?>
                            </td>
                            <td style="font-weight:600;">
                                <?php echo sanitize($member['full_name']); ?>
                            </td>
                            <td>
                                <?php echo substr(sanitize($member['tc_no']), 0, 2) . '******' . substr(sanitize($member['tc_no']), -3); ?>
                            </td>
                            <td>
                                <?php echo sanitize($member['phone'] ?: '—'); ?>
                            </td>
                            <td>
                                <?php if ($member['active_plan']): ?>
                                    <span class="badge badge-active">
                                        <?php echo sanitize($member['active_plan']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-expired">Yok</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('d.m.Y', strtotime((string) $member['created_at'])); ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;"
                                    onsubmit="return confirm('Bu üyeyi silmek istediğinize emin misiniz?')">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="delete_id" value="<?php echo (int) $member['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;color:var(--text-muted);padding:32px;">Henüz üye yok.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>