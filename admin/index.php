<?php
$pageTitle = 'Admin Panel';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$totalMembers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='member'")->fetchColumn();
$activeSubscriptions = (int) $pdo->query("SELECT COUNT(*) FROM user_memberships WHERE status='active' AND end_date >= CURDATE()")->fetchColumn();
$totalRevenue = (float) $pdo->query("SELECT COALESCE(SUM(COALESCE(um.paid_amount, m.price)),0) FROM user_memberships um JOIN memberships m ON um.membership_id = m.id")->fetchColumn();
$unreadMessages = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();

$recentMembers = $pdo->query("SELECT * FROM users WHERE role='member' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$recentMessages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        <h1 class="admin-title">ADMİN <span class="accent">PANELİ</span></h1>
        <p class="admin-subtitle">Sistemin genel durumunu buradan takip edebilirsiniz.</p>

        <div class="dashboard-header" style="margin-bottom:0px; padding-bottom: 0px; border-bottom: none;">
            <div class="admin-nav" style="margin-bottom: 24px;">
                <a href="<?php echo BASE_URL; ?>/admin/" class="active">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/admin/uyeler">Üyeler</a>
                <a href="<?php echo BASE_URL; ?>/admin/paketler">Paketler</a>
            </div>
            <div class="dashboard-actions" style="margin-bottom: 24px;">
                <a href="<?php echo BASE_URL; ?>/cikis" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i>
                    Çıkış Yap</a>
            </div>
        </div>

        <div class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-icon"><i class="fas fa-users"></i></div>
                <div class="admin-stat-info">
                    <h3>Toplam Üye</h3>
                    <div class="number">
                        <?php echo $totalMembers; ?>                    </div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon"><i class="fas fa-id-badge"></i></div>
                <div class="admin-stat-info">
                    <h3>Aktif Üyelik</h3>
                    <div class="number">
                        <?php echo $activeSubscriptions; ?>
                    </div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon"><i class="fas fa-lira-sign"></i></div>
                <div class="admin-stat-info">
                    <h3>Toplam Gelir</h3>
                    <div class="number">₺
                        <?php echo number_format($totalRevenue, 0, ',', '.'); ?>
                    </div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon"><i class="fas fa-envelope"></i></div>
                <div class="admin-stat-info">
                    <h3>Okunmamış Mesaj</h3>
                    <div class="number">
                        <span id="unread-counter"><?php echo $unreadMessages; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <h2 style="font-family:var(--font-heading);font-size:1.6rem;letter-spacing:1px;margin-bottom:16px;">SON KAYIT
            OLAN ÜYELER</h2>
        <div class="data-table-wrap" style="margin-bottom:48px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Soyad</th>
                        <th>T.C. Kimlik Numarası</th>
                        <th>Telefon</th>
                        <th>Kayıt Tarihi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentMembers as $member): ?>
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
                                <?php echo date('d.m.Y H:i', strtotime($member['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentMembers)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px;">Henüz üye yok.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 style="font-family:var(--font-heading);font-size:1.6rem;letter-spacing:1px;margin-bottom:16px;">SON MESAJLAR
        </h2>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Gönderen</th>
                        <th>E-Posta</th>
                        <th>Konu</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentMessages as $msg): ?>
                        <tr id="msg-row-<?php echo $msg['id']; ?>">
                            <td style="font-weight:600;">
                                <?php echo sanitize($msg['sender_name']); ?>
                            </td>
                            <td>
                                <?php echo sanitize($msg['sender_email']); ?>
                            </td>
                            <td>
                                <?php echo sanitize($msg['subject']); ?>
                            </td>
                            <td>
                                <?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?>
                            </td>
                            <td>
                                <?php if ($msg['is_read']): ?>
                                    <span class="badge badge-active" id="badge-<?php echo $msg['id']; ?>">Okundu</span>
                                <?php else: ?>
                                    <span class="badge badge-cancelled" id="badge-<?php echo $msg['id']; ?>">Yeni</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr id="msg-details-<?php echo $msg['id']; ?>" style="background-color: var(--bg-secondary);">
                            <td colspan="5"
                                style="padding:0; border:none; border-bottom: var(--border-width) solid var(--border);">
                                <details class="admin-msg-details" style="margin: 16px;" data-id="<?php echo $msg['id']; ?>" data-read="<?php echo $msg['is_read'] ? '1' : '0'; ?>">
                                    <summary><i class="fas fa-eye"></i> Mesajı Gör / Oku</summary>
                                    <p
                                        style="margin-top:12px; font-weight:500; font-size:1.1rem; line-height:1.6; white-space: pre-wrap;">
                                        <?php echo sanitize($msg['message']); ?>
                                    </p>
                                    <div style="margin-top: 16px; text-align: right;">
                                        <button class="btn btn-danger" style="padding: 8px 16px; font-size: 1rem;" onclick="deleteMessage(<?php echo $msg['id']; ?>)">
                                            <i class="fas fa-trash"></i> Mesajı Sil
                                        </button>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentMessages)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px;">Henüz mesaj yok.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.querySelectorAll('.admin-msg-details').forEach(details => {
        details.addEventListener('toggle', function() {
            if (this.open && this.getAttribute('data-read') === '0') {
                const id = this.getAttribute('data-id');
                const badge = document.getElementById('badge-' + id);
                const unreadCounter = document.getElementById('unread-counter');
                
                fetch('ajax_mesaj.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': csrfToken
                    },
                    body: 'action=read&id=' + encodeURIComponent(id)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.setAttribute('data-read', '1');
                        if (badge) {
                            badge.className = 'badge badge-active';
                            badge.textContent = 'Okundu';
                        }
                        if (unreadCounter && data.unread_count !== undefined) {
                            unreadCounter.textContent = data.unread_count;
                        }
                    }
                })
                .catch(err => console.error('Hata:', err));
            }
        });
    });

    function deleteMessage(id) {
        if (confirm('Bu mesajı silmek istediğinize emin misiniz?')) {
            fetch('ajax_mesaj.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': csrfToken
                },
                body: 'action=delete&id=' + encodeURIComponent(id)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('msg-row-' + id);
                    const detailsRow = document.getElementById('msg-details-' + id);
                    if (row) row.remove();
                    if (detailsRow) detailsRow.remove();

                    alert('Mesaj başarıyla silindi.');
                    window.location.reload();
                } else {
                    alert('Hata: ' + (data.error || 'Bilinmeyen bir hata oluştu.'));
                }
            })
            .catch(err => {
                console.error('Hata:', err);
                alert('Silme işlemi başarısız oldu.');
            });
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>