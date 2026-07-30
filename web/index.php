<?php
/**
 * Landing Page
 * Campus Plant Diversity Mapper — Sanjivani University
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$user = getCurrentUser();
$db = getDB();

// Quick stats for landing page
$stmt = $db->query("SELECT COUNT(*) FROM plant_records WHERE status = 'verified'");
$totalPlants = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM species");
$totalSpecies = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM zones");
$totalZones = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🌿 Campus Plant Diversity Mapper | Sanjivani University</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar">
    <div class="nav-brand">
      <span class="icon">🌿</span>
      <span>Sanjivani Herb</span>
    </div>
    <ul class="nav-menu">
      <li><a href="index.php" class="nav-link active"><i class="fa-solid fa-house"></i> Home</a></li>
      <li><a href="pages/dashboard.php" class="nav-link"><i class="fa-solid fa-map-location-dot"></i> Live Map</a></li>
      <li><a href="pages/inventory.php" class="nav-link"><i class="fa-solid fa-book-bookmark"></i> Species List</a></li>
      <?php if ($user): ?>
        <li><a href="pages/capture.php" class="nav-link"><i class="fa-solid fa-camera"></i> Capture Plant</a></li>
        <?php if (in_array($user['role'], ['verifier', 'admin'])): ?>
          <li><a href="pages/verify.php" class="nav-link"><i class="fa-solid fa-circle-check"></i> Verify Queue</a></li>
          <li><a href="pages/analytics.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
        <?php endif; ?>
        <li><a href="api/auth/logout.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout (<?= htmlspecialchars($user['full_name']) ?>)</a></li>
      <?php else: ?>
        <li><a href="pages/login.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-to-bracket"></i> Login</a></li>
        <li><a href="pages/register.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Join Network</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <!-- Hero Section -->
  <header style="padding: 5rem 1.5rem; text-align: center; max-width: 900px; margin: 0 auto;">
    <div class="live-pulse" style="justify-content: center; margin-bottom: 1rem;">
      <span class="pulse-dot"></span>
      <span>LIVE CAMPUS BIODIVERSITY NETWORK</span>
    </div>
    <h1 style="font-size: 3.2rem; line-height: 1.15; margin-bottom: 1.25rem;">
      Map, Identify & Preserve Campus Plant Diversity in Real-Time
    </h1>
    <p style="font-size: 1.2rem; color: var(--text-secondary); margin-bottom: 2.5rem; max-width: 750px; margin-left: auto; margin-right: auto;">
      AI-powered species identification, geotagged observation mapping, and interactive QR signage for Sanjivani University.
    </p>

    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
      <a href="pages/dashboard.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-map-location-dot"></i> Open Live Map</a>
      <?php if ($user): ?>
        <a href="pages/capture.php" class="btn btn-secondary btn-lg"><i class="fa-solid fa-camera"></i> Capture New Observation</a>
      <?php else: ?>
        <a href="pages/login.php" class="btn btn-secondary btn-lg"><i class="fa-solid fa-right-to-bracket"></i> Contributor Sign In</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- Stats Grid -->
  <section class="container">
    <div class="stats-grid">
      <div class="glass-card kpi-card">
        <div>
          <div class="kpi-title">Verified Plants</div>
          <div class="kpi-value"><?= number_format($totalPlants) ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-seedling"></i></div>
      </div>

      <div class="glass-card kpi-card">
        <div>
          <div class="kpi-title">Unique Species</div>
          <div class="kpi-value"><?= number_format($totalSpecies) ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-dna"></i></div>
      </div>

      <div class="glass-card kpi-card">
        <div>
          <div class="kpi-title">Campus Zones</div>
          <div class="kpi-value"><?= number_format($totalZones) ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-layer-group"></i></div>
      </div>

      <div class="glass-card kpi-card">
        <div>
          <div class="kpi-title">AI Identification</div>
          <div class="kpi-value" style="color: var(--accent-primary);">Pl@ntNet</div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-robot"></i></div>
      </div>
    </div>
  </section>

</body>
</html>
