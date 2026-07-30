<?php
/**
 * Biodiversity Analytics Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireRole('verifier');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Biodiversity Analytics | Sanjivani Herb</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar">
    <div class="nav-brand">
      <span class="icon">🌿</span>
      <span>Sanjivani Herb</span>
    </div>
    <ul class="nav-menu">
      <li><a href="../index.php" class="nav-link"><i class="fa-solid fa-house"></i> Home</a></li>
      <li><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-map-location-dot"></i> Live Map</a></li>
      <li><a href="inventory.php" class="nav-link"><i class="fa-solid fa-book-bookmark"></i> Species List</a></li>
      <li><a href="capture.php" class="nav-link"><i class="fa-solid fa-camera"></i> Capture Plant</a></li>
      <li><a href="verify.php" class="nav-link"><i class="fa-solid fa-circle-check"></i> Verify Queue</a></li>
      <li><a href="analytics.php" class="nav-link active"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
      <li><a href="../api/auth/logout.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout (<?= htmlspecialchars($user['full_name']) ?>)</a></li>
    </ul>
  </nav>

  <div class="container">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h2>Biodiversity Analytics & Reports</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">NAAC / NBA Green Campus Audit & Diversity Metrics</p>
      </div>
      <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fa-solid fa-file-pdf"></i> Export Audit Report</button>
    </div>

    <!-- Stats Overview Cards -->
    <div class="stats-grid">
      <div class="glass-card kpi-card">
        <div>
          <div class="kpi-title">Total Logged Plants</div>
          <div id="totalPlants" class="kpi-value">-</div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-tree"></i></div>
      </div>
      <div class="glass-card kpi-card">
        <div>
          <div class="kpi-title">Species Count</div>
          <div id="totalSpecies" class="kpi-value">-</div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-dna"></i></div>
      </div>
      <div class="glass-card kpi-card">
        <div>
          <div class="kpi-title">Verified Rate</div>
          <div id="verifiedPct" class="kpi-value" style="color: var(--accent-primary);">-</div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-certificate"></i></div>
      </div>
      <div class="glass-card kpi-card">
        <div>
          <div class="kpi-title">Contributors</div>
          <div id="totalContributors" class="kpi-value">-</div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
      </div>
    </div>

    <!-- Charts Section -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
      <div class="glass-card" style="padding: 1.5rem;">
        <h4 style="margin-bottom: 1rem;">Native vs Introduced vs Invasive</h4>
        <canvas id="nativeChart" height="220"></canvas>
      </div>
      <div class="glass-card" style="padding: 1.5rem;">
        <h4 style="margin-bottom: 1rem;">Plant Count by Campus Zone</h4>
        <canvas id="zoneChart" height="220"></canvas>
      </div>
    </div>

  </div>

  <script>
    fetch('../api/analytics/summary.php')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('totalPlants').innerText = data.total_plants;
          document.getElementById('totalSpecies').innerText = data.total_species;
          document.getElementById('verifiedPct').innerText = data.verified_pct + '%';
          document.getElementById('totalContributors').innerText = data.total_contributors;

          renderNativeChart(data.native_breakdown);
          renderZoneChart(data.zone_breakdown);
        }
      });

    function renderNativeChart(nb) {
      new Chart(document.getElementById('nativeChart'), {
        type: 'doughnut',
        data: {
          labels: ['Native', 'Introduced', 'Invasive ⚠️', 'Unknown'],
          datasets: [{
            data: [nb.native || 0, nb.introduced || 0, nb.invasive || 0, nb.unknown || 0],
            backgroundColor: ['#10b981', '#3b82f6', '#ec4899', '#6b7280']
          }]
        },
        options: { responsive: true, plugins: { legend: { labels: { color: '#f9fafb' } } } }
      });
    }

    function renderZoneChart(zb) {
      const labels = zb.map(z => z.name);
      const counts = zb.map(z => z.cnt);
      const colors = zb.map(z => z.color_hex || '#10b981');

      new Chart(document.getElementById('zoneChart'), {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Logged Plants',
            data: counts,
            backgroundColor: colors
          }]
        },
        options: {
          responsive: true,
          scales: {
            x: { ticks: { color: '#9ca3af' } },
            y: { ticks: { color: '#9ca3af' } }
          },
          plugins: { legend: { display: false } }
        }
      });
    }
  </script>
</body>
</html>
