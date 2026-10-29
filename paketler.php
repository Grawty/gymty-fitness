<?php
$pageTitle = 'Üyelik Paketleri';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';



$hasActiveMembership = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_memberships WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE()");
    $stmt->execute([$_SESSION['user_id']]);
    $hasActiveMembership = $stmt->fetchColumn() > 0;
}

$plans = $pdo->query("SELECT * FROM memberships ORDER BY price ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>SAVAŞ <span class="accent">PLANLARI</span></h1>
    <p>Senin hedefin, senin kuralların. Harekete geçmek için savaş planını seç.</p>
</div>


<section class="section">
    <div class="container">
        <div class="plans-grid">
            <?php foreach ($plans as $plan): ?>
                <div class="plan-card <?php echo !empty($plan['is_popular']) ? 'popular' : ''; ?>">
                    <?php if (!empty($plan['is_popular'])): ?>
                        <div class="plan-popular-tag">Popüler</div>
                    <?php endif; ?>
                    <div class="plan-name">
                        <?php echo sanitize($plan['name']); ?>
                    </div>
                    <div class="plan-price">
                        <span class="currency">₺</span>
                        <span class="amount">
                            <?php echo number_format((float) $plan['price'], 0, ',', '.'); ?>
                        </span>
                        <span class="period">/
                            <?php echo (int) $plan['duration_months']; ?> ay
                        </span>
                    </div>
                    <ul class="plan-features">
                        <?php foreach (explode('|', (string) $plan['features']) as $feature): ?>
                            <?php if (trim($feature) === '') {
                                continue;
                            } ?>
                            <li><i class="fas fa-check-circle"></i>
                                <?php echo sanitize($feature); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div style="margin-top:auto;">
                        <?php if ($hasActiveMembership): ?>
                            <button class="btn btn-secondary btn-block" disabled
                                style="width:100%; text-align:center; justify-content:center; cursor:not-allowed; opacity: 0.7;">
                                <i class="fas fa-check"></i> Aktif Planınız Var
                            </button>
                        <?php else: ?>
                            <a href="<?php echo sanitize(BASE_URL); ?>/odeme?plan=<?php echo (int) $plan['id']; ?>"
                                class="btn <?php echo !empty($plan['is_popular']) ? 'btn-primary' : 'btn-outline'; ?> btn-block"
                                style="width:100%; text-align:center; justify-content:center;">
                                <i class="fas fa-shopping-cart"></i> Satın Al
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>