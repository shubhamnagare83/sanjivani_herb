<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
$user = getCurrentUser();
$db = getDB();

// Live stats
$totalPlants = (int)$db->query("SELECT COUNT(*) FROM plant_records WHERE status='verified'")->fetchColumn();
$totalSpecies = (int)$db->query("SELECT COUNT(*) FROM species")->fetchColumn();
$totalZones = (int)$db->query("SELECT COUNT(*) FROM zones")->fetchColumn();
$totalContrib = (int)$db->query("SELECT COUNT(DISTINCT submitted_by) FROM plant_records")->fetchColumn();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM plant_records WHERE status='pending_verification'")->fetchColumn();
$nativeCount = (int)$db->query("SELECT COUNT(*) FROM species WHERE native_status='native'")->fetchColumn();
$invasiveCount = (int)$db->query("SELECT COUNT(*) FROM species WHERE native_status='invasive'")->fetchColumn();

// Recent 4 verified observations
$recent = $db->query("SELECT pr.*, s.common_name, s.scientific_name, s.family, s.native_status, z.name AS zone_name, u.full_name AS contributor FROM plant_records pr LEFT JOIN species s ON pr.species_id=s.id LEFT JOIN zones z ON pr.zone_id=z.id LEFT JOIN users u ON pr.submitted_by=u.id WHERE pr.status='verified' ORDER BY pr.created_at DESC LIMIT 4")->fetchAll();

// Featured species (highest scan QR)
$featured = $db->query("SELECT s.*, qr.scan_count, qr.public_slug FROM species s JOIN plant_records pr ON pr.species_id=s.id JOIN qr_codes qr ON qr.plant_record_id=pr.id ORDER BY qr.scan_count DESC LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sanjivani Herb Mapper | Campus Plant Diversity Platform</title>
  <meta name="description" content="AI-powered campus plant diversity mapping platform for Sanjivani University. Identify, map, and preserve medicinal herbs and native species.">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .hero{text-align:center;padding:4rem 1.5rem 3rem;max-width:960px;margin:0 auto}
    .hero h1{font-size:3.2rem;line-height:1.12;margin-bottom:1.2rem;font-weight:800;letter-spacing:-.03em}
    .hero h1 .accent{color:var(--accent-primary)}
    .hero-sub{font-size:1.15rem;color:var(--text-secondary);margin-bottom:2.5rem;max-width:720px;margin-left:auto;margin-right:auto;line-height:1.7}
    .hero-actions{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-bottom:3rem}
    .section-title{font-size:1.6rem;font-weight:800;margin-bottom:.35rem}
    .section-sub{color:var(--text-secondary);font-size:.95rem;margin-bottom:1.75rem}
    /* How it works */
    .steps-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-bottom:3rem}
    .step-card{padding:2rem 1.5rem;text-align:center;position:relative}
    .step-num{width:44px;height:44px;border-radius:50%;background:#ecfdf5;color:#059669;font-weight:800;font-size:1.1rem;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;border:2px solid #a7f3d0}
    .step-card h3{font-size:1.1rem;margin-bottom:.5rem}
    .step-card p{color:var(--text-secondary);font-size:.9rem;line-height:1.6}
    /* Facts banner */
    .facts-banner{background:linear-gradient(135deg,#ecfdf5 0%,#f0fdf4 100%);border:1px solid #a7f3d0;border-radius:var(--radius-lg);padding:2rem 2.5rem;margin-bottom:3rem}
    .facts-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem}
    .fact-item{text-align:center}
    .fact-icon{font-size:2rem;margin-bottom:.5rem}
    .fact-value{font-family:var(--font-heading);font-size:1.8rem;font-weight:800;color:#065f46}
    .fact-label{font-size:.82rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em}
    /* Recent observations */
    .obs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem;margin-bottom:3rem}
    .obs-card{padding:1.25rem}
    .obs-card h4{font-size:1.05rem;margin-bottom:.15rem}
    .obs-meta{font-size:.83rem;color:var(--text-muted);margin-bottom:.5rem}
    /* Featured species */
    .featured-card{display:grid;grid-template-columns:1fr 1fr;gap:2rem;padding:2rem;margin-bottom:3rem;align-items:center}
    .featured-info h3{font-size:1.5rem;margin-bottom:.5rem}
    .featured-info p{color:var(--text-secondary);line-height:1.7;margin-bottom:.75rem}
    .featured-detail{display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:1rem}
    .featured-detail div{font-size:.88rem}
    .featured-detail strong{color:var(--text-primary)}
    /* Feature highlights */
    .features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.25rem;margin-bottom:2rem}
    .feature-card{padding:1.5rem}
    .feature-icon{width:48px;height:48px;border-radius:var(--radius-md);background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:1rem}
    .feature-card h4{font-size:1.05rem;margin-bottom:.4rem}
    .feature-card p{color:var(--text-secondary);font-size:.88rem;line-height:1.6}
    @media(max-width:768px){
      .hero h1{font-size:2.2rem}
      .steps-grid{grid-template-columns:1fr}
      .featured-card{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar">
    <div class="nav-brand"><span class="icon">🌿</span><span>Sanjivani Herb</span></div>
    <ul class="nav-menu">
      <li><a href="index.php" class="nav-link active"><i class="fa-solid fa-house"></i> Home</a></li>
      <li><a href="pages/dashboard.php" class="nav-link"><i class="fa-solid fa-map-location-dot"></i> Live Map</a></li>
      <li><a href="pages/inventory.php" class="nav-link"><i class="fa-solid fa-book-bookmark"></i> Species List</a></li>
      <?php if ($user): ?>
        <li><a href="pages/capture.php" class="nav-link"><i class="fa-solid fa-camera"></i> Capture</a></li>
        <?php if (in_array($user['role'], ['verifier', 'admin'])): ?>
          <li><a href="pages/verify.php" class="nav-link"><i class="fa-solid fa-circle-check"></i> Verify</a></li>
          <li><a href="pages/analytics.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
        <?php endif; ?>
        <li><a href="api/auth/logout.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> <?= htmlspecialchars($user['full_name']) ?></a></li>
      <?php else: ?>
        <li><a href="pages/login.php" class="btn btn-secondary btn-sm">Login</a></li>
        <li><a href="pages/register.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Join</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <!-- HERO -->
  <header class="hero">
    <div class="live-pulse" style="justify-content:center;margin-bottom:1.25rem">
      <span class="pulse-dot"></span><span>LIVE CAMPUS BIODIVERSITY NETWORK</span>
    </div>
    <h1>Map, Identify & Preserve <span class="accent">Campus Plant Diversity</span> in Real-Time</h1>
    <p class="hero-sub">
      Our planet hosts over <strong>390,000 known plant species</strong>. India alone is home to <strong>45,000+ species</strong> with 
      <strong>7,500+ medicinal plants</strong> used in Ayurveda. Yet campus green zones remain largely undocumented. 
      This platform uses <strong>AI-powered identification</strong> and <strong>satellite geo-mapping</strong> to catalog every herb, 
      tree, and shrub across Sanjivani University.
    </p>
    <div class="hero-actions">
      <a href="pages/dashboard.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-satellite-dish"></i> Open Satellite Map</a>
      <?php if ($user): ?>
        <a href="pages/capture.php" class="btn btn-secondary btn-lg"><i class="fa-solid fa-camera"></i> Capture Observation</a>
      <?php else: ?>
        <a href="pages/login.php" class="btn btn-secondary btn-lg"><i class="fa-solid fa-right-to-bracket"></i> Contributor Sign In</a>
      <?php endif; ?>
    </div>
  </header>

  <div class="container">

    <!-- GLOBAL PLANT FACTS -->
    <div class="facts-banner">
      <div class="facts-grid">
        <div class="fact-item">
          <div class="fact-icon">🌍</div>
          <div class="fact-value">390,000+</div>
          <div class="fact-label">Known Plant Species</div>
        </div>
        <div class="fact-item">
          <div class="fact-icon">🇮🇳</div>
          <div class="fact-value">45,000+</div>
          <div class="fact-label">Species in India</div>
        </div>
        <div class="fact-item">
          <div class="fact-icon">🌿</div>
          <div class="fact-value">7,500+</div>
          <div class="fact-label">Medicinal Plants</div>
        </div>
        <div class="fact-item">
          <div class="fact-icon">🏫</div>
          <div class="fact-value"><?= $totalSpecies ?></div>
          <div class="fact-label">Cataloged on Campus</div>
        </div>
      </div>
    </div>

    <!-- LIVE CAMPUS STATS -->
    <div class="stats-grid">
      <div class="glass-card kpi-card">
        <div><div class="kpi-title">Verified Plants</div><div class="kpi-value"><?= $totalPlants ?></div></div>
        <div class="kpi-icon"><i class="fa-solid fa-seedling"></i></div>
      </div>
      <div class="glass-card kpi-card">
        <div><div class="kpi-title">Unique Species</div><div class="kpi-value"><?= $totalSpecies ?></div></div>
        <div class="kpi-icon"><i class="fa-solid fa-dna"></i></div>
      </div>
      <div class="glass-card kpi-card">
        <div><div class="kpi-title">Campus Zones</div><div class="kpi-value"><?= $totalZones ?></div></div>
        <div class="kpi-icon"><i class="fa-solid fa-layer-group"></i></div>
      </div>
      <div class="glass-card kpi-card">
        <div><div class="kpi-title">Contributors</div><div class="kpi-value"><?= $totalContrib ?></div></div>
        <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
      </div>
    </div>

    <!-- HOW IT WORKS -->
    <div style="text-align:center;margin-bottom:1.5rem">
      <h2 class="section-title">How It Works</h2>
      <p class="section-sub">Three simple steps to contribute to campus biodiversity research</p>
    </div>
    <div class="steps-grid">
      <div class="glass-card step-card">
        <div class="step-num">1</div>
        <h3><i class="fa-solid fa-camera" style="color:var(--accent-primary)"></i> Capture</h3>
        <p>Take a photo of any plant on campus. Our system captures your GPS location, timestamp, and observation notes automatically.</p>
      </div>
      <div class="glass-card step-card">
        <div class="step-num">2</div>
        <h3><i class="fa-solid fa-robot" style="color:var(--accent-primary)"></i> AI Identify</h3>
        <p>Our Pl@ntNet AI engine analyzes leaf shape, flower pattern, and bark texture to identify the species with up to 97% accuracy.</p>
      </div>
      <div class="glass-card step-card">
        <div class="step-num">3</div>
        <h3><i class="fa-solid fa-map-pin" style="color:var(--accent-primary)"></i> Map & Verify</h3>
        <p>Your observation appears on the live satellite map instantly. Faculty verifiers confirm the identification and publish it to the public atlas.</p>
      </div>
    </div>

    <!-- FEATURED SPECIES SPOTLIGHT -->
    <?php if ($featured): ?>
    <div style="text-align:center;margin-bottom:1.5rem">
      <h2 class="section-title">🌟 Species Spotlight</h2>
      <p class="section-sub">Most scanned species on campus QR signage</p>
    </div>
    <div class="glass-card featured-card">
      <div class="featured-info">
        <span class="badge badge-native" style="margin-bottom:.75rem;display:inline-flex"><?= htmlspecialchars($featured['native_status'] ?? 'native') ?></span>
        <h3><?= htmlspecialchars($featured['common_name']) ?></h3>
        <p style="font-style:italic;color:var(--text-muted);font-size:.95rem;margin-bottom:.75rem"><?= htmlspecialchars($featured['scientific_name']) ?> · <?= htmlspecialchars($featured['family']) ?></p>
        <p><?= htmlspecialchars($featured['description'] ?? '') ?></p>
        <div class="featured-detail">
          <div>🏥 <strong>Medicinal:</strong> <?= htmlspecialchars(mb_strimwidth($featured['medicinal_uses'] ?? 'N/A', 0, 120, '...')) ?></div>
        </div>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap">
          <a href="pages/plant-detail.php?slug=<?= htmlspecialchars($featured['public_slug'] ?? '') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-qrcode"></i> View QR Page</a>
          <a href="pages/dashboard.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-map"></i> Find on Map</a>
        </div>
      </div>
      <div style="text-align:center">
        <div style="width:100%;height:260px;background:#ecfdf5;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;border:1px solid #a7f3d0;font-size:5rem">
          🌿
        </div>
        <p style="margin-top:.75rem;font-size:.85rem;color:var(--text-muted)"><strong><?= $featured['scan_count'] ?></strong> QR scans recorded</p>
      </div>
    </div>
    <?php endif; ?>

    <!-- RECENT OBSERVATIONS -->
    <div style="text-align:center;margin-bottom:1.5rem">
      <h2 class="section-title">Recent Verified Observations</h2>
      <p class="section-sub">Latest botanically confirmed plant records from campus contributors</p>
    </div>
    <div class="obs-grid">
      <?php foreach ($recent as $r): ?>
      <div class="glass-card obs-card">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:.5rem">
          <h4><?= htmlspecialchars($r['common_name'] ?? $r['scientific_name'] ?? 'Unknown') ?></h4>
          <span class="badge badge-<?= htmlspecialchars($r['native_status'] ?? 'native') ?>"><?= htmlspecialchars($r['native_status'] ?? '') ?></span>
        </div>
        <p style="font-style:italic;color:var(--text-muted);font-size:.85rem;margin-bottom:.4rem"><?= htmlspecialchars($r['scientific_name'] ?? '') ?> · <?= htmlspecialchars($r['family'] ?? '') ?></p>
        <p class="obs-meta"><i class="fa-solid fa-map-pin"></i> <?= htmlspecialchars($r['zone_name'] ?? 'Campus') ?> · <i class="fa-solid fa-user"></i> <?= htmlspecialchars($r['contributor'] ?? 'Contributor') ?></p>
        <p style="font-size:.85rem;color:var(--text-secondary);line-height:1.5"><?= htmlspecialchars(mb_strimwidth($r['notes'] ?? '', 0, 100, '...')) ?></p>
        <?php if ($r['ai_confidence']): ?>
        <div style="margin-top:.5rem"><span class="badge badge-verified">AI <?= round($r['ai_confidence'], 1) ?>%</span></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- PLATFORM FEATURES -->
    <div style="text-align:center;margin-bottom:1.5rem">
      <h2 class="section-title">Platform Capabilities</h2>
      <p class="section-sub">Built for researchers, students, and campus conservation initiatives</p>
    </div>
    <div class="features-grid">
      <div class="glass-card feature-card">
        <div class="feature-icon"><i class="fa-solid fa-brain"></i></div>
        <h4>AI Species Identification</h4>
        <p>Powered by Pl@ntNet deep learning. Snap a photo of leaf, flower, or bark and get instant species matches with confidence scores.</p>
      </div>
      <div class="glass-card feature-card">
        <div class="feature-icon"><i class="fa-solid fa-satellite-dish"></i></div>
        <h4>Satellite Geo-Mapping</h4>
        <p>High-resolution satellite imagery with hybrid labels. Switch between map, satellite, hybrid, and terrain views like Google Maps.</p>
      </div>
      <div class="glass-card feature-card">
        <div class="feature-icon"><i class="fa-solid fa-qrcode"></i></div>
        <h4>QR Code Signage</h4>
        <p>Generate unique QR tags for verified trees. Visitors scan with their phone to learn botanical details, medicinal uses, and conservation status.</p>
      </div>
      <div class="glass-card feature-card">
        <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <h4>Faculty Verification</h4>
        <p>Two-tier review system. Student observations go through expert faculty verification before being published to the public campus atlas.</p>
      </div>
      <div class="glass-card feature-card">
        <div class="feature-icon"><i class="fa-solid fa-chart-column"></i></div>
        <h4>Biodiversity Analytics</h4>
        <p>Real-time dashboards tracking native vs invasive ratios, zone-wise distribution, daily activity trends, and top contributors.</p>
      </div>
      <div class="glass-card feature-card">
        <div class="feature-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
        <h4>PWA Mobile App</h4>
        <p>Install as a native app on Android or iOS. Works offline in the field and syncs observations when connectivity returns.</p>
      </div>
    </div>

    <!-- BIODIVERSITY CONTEXT -->
    <div class="facts-banner" style="margin-top:1rem">
      <div style="text-align:center;margin-bottom:1.5rem">
        <h2 class="section-title" style="color:#065f46">Campus Biodiversity at a Glance</h2>
      </div>
      <div class="facts-grid">
        <div class="fact-item">
          <div class="fact-icon">✅</div>
          <div class="fact-value"><?= $totalPlants ?></div>
          <div class="fact-label">Verified Records</div>
        </div>
        <div class="fact-item">
          <div class="fact-icon">⏳</div>
          <div class="fact-value"><?= $pendingCount ?></div>
          <div class="fact-label">Pending Review</div>
        </div>
        <div class="fact-item">
          <div class="fact-icon">🌱</div>
          <div class="fact-value"><?= $nativeCount ?></div>
          <div class="fact-label">Native Species</div>
        </div>
        <div class="fact-item">
          <div class="fact-icon">⚠️</div>
          <div class="fact-value"><?= $invasiveCount ?></div>
          <div class="fact-label">Invasive Species</div>
        </div>
      </div>
    </div>

  </div>

  <!-- Footer -->
  <footer style="text-align:center;padding:2rem;color:var(--text-muted);font-size:.85rem;border-top:1px solid var(--border-color);margin-top:2rem">
    <p>🌿 Sanjivani Herb Mapper — Campus Plant Diversity Platform</p>
    <p style="margin-top:.35rem">Built for Sanjivani University · AI-Powered by Pl@ntNet · <?= date('Y') ?></p>
  </footer>
</body>
</html>
