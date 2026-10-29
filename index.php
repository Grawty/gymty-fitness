<?php
$pageTitle = 'Anasayfa';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';


$memberCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='member'")->fetchColumn();

$plans = $pdo->query("SELECT * FROM memberships ORDER BY price ASC")->fetchAll();

$hasActiveMembership = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_memberships WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE()");
    $stmt->execute([$_SESSION['user_id']]);
    $hasActiveMembership = $stmt->fetchColumn() > 0;
}
?>


<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>
                GÜCÜNÜ<br>
                <span class="line-accent">KEŞFET.</span>
            </h1>
            <p>Sınırlarını zorlamaya ve en iyi versiyonunu yaratmaya hazır mısın? Ter, emek ve demirle şekillenen bir
                hayata ilk adımı bugün at.</p>
            <div class="hero-buttons">
                <a href="<?php echo BASE_URL; ?>/kayit" class="btn btn-primary"><i class="fas fa-rocket"></i>
                    Hemen Üye Ol</a>
                <a href="<?php echo BASE_URL; ?>/paketler" class="btn btn-outline"><i class="fas fa-eye"></i> Paketleri
                    İncele</a>
            </div>
        </div>
    </div>
</section>

<div class="marquee-wrap">
    <div class="marquee-content">
        <span>GYMTY İLE GÜCÜNÜ KEŞFET</span>
        <span>YENİ NESİL FİTNESS DENEYİMİ</span>
        <span>HAYALLERİNİ ERTELEME</span>
        <span>SINIRLARINI ZORLA</span>
        <span>GYMTY İLE GÜCÜNÜ KEŞFET</span>
        <span>YENİ NESİL FİTNESS DENEYİMİ</span>
        <span>HAYALLERİNİ ERTELEME</span>
        <span>SINIRLARINI ZORLA</span>
    </div>
</div>

<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number" data-count="<?php echo $memberCount > 50 ? $memberCount : 350; ?>"
                    data-suffix="+">0</div>
                <div class="stat-label">Aktif Sporcu</div>
            </div>

            <div class="stat-item">
                <div class="stat-number" data-count="8">0</div>
                <div class="stat-label">Yıllık Deneyim</div>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-header">
            <span class="overline">Hedefine Giden Yol</span>
            <h2>ŞAMPİYONLARIN TERCİHİ</h2>
            <p>Mazeret yok, sadece sonuç var. Sana en uygun planı seç ve dönüşümü başlat.</p>
        </div>
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
                            <a href="<?php echo BASE_URL; ?>/paketler" class="btn btn-primary btn-block"
                                style="width:100%;">Seçimi Yap</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<section class="section">
    <div class="container">
        <div style="margin-top:40px;">
            <h2
                style="font-family:var(--font-heading); font-size:4rem; text-align:center; width:100%; margin-bottom:40px; text-transform:uppercase;">
                NEDEN GYMTY?</h2>
            <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-medal"></i></div>
                        <h3>ŞAMPİYON EĞİTMENLER</h3>
                        <p>Sınırlarını bilen değil, sınırları aşmanı sağlayan gerçek uzmanlar ve hardcore ekipmanlar.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-clock"></i></div>
                        <h3>MESAİ BİTMEZ</h3>
                        <p>Sen ne zaman hazırsan, ağırlıklar seni bekliyor. Haftanın 7 günü bahanelere kapalıyız.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-users"></i></div>
                        <h3>GÜÇLÜ TOPLULUK</h3>
                        <p>Etrafında senin gibi pes etmeyen sporcularla hedeflerine daha hızlı ve emin adımlarla ulaş.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-utensils"></i></div>
                        <h3>DOĞRU YAKIT</h3>
                        <p>Antrenman salonda biter, gelişim mutfakta başlar. Kişiselleştirilmiş beslenme ve performans
                            taktikleri.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-shower"></i></div>
                        <h3>DİNLENME & TOPARLANMA</h3>
                        <p>Sert bir idmandan sonra kaslarını dinlendir: Sauna, modern duşlar ve kişisel alanlar.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                        <h3>ZAFER GÜNLÜĞÜ</h3>
                        <p>Nereden başladığını unutma. Dijital panelinle kırılan rekorlarını ve gelişimini anlık izle.</p>
                    </div>
                </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>