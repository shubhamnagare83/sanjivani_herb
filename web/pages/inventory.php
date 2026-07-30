<?php
/**
 * Master Species Inventory List
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = getCurrentUser();
$db = getDB();

// Get unique families for filter
$families = $db->query("SELECT DISTINCT family FROM species WHERE family IS NOT NULL AND family != '' ORDER BY family ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Species Inventory | Sanjivani Herb</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .species-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.25rem;
    }
    .species-card {
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
  </style>
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
      <li><a href="inventory.php" class="nav-link active"><i class="fa-solid fa-book-bookmark"></i> Species List</a></li>
      <?php if ($user): ?>
        <li><a href="capture.php" class="nav-link"><i class="fa-solid fa-camera"></i> Capture Plant</a></li>
        <?php if (in_array($user['role'], ['verifier', 'admin'])): ?>
          <li><a href="verify.php" class="nav-link"><i class="fa-solid fa-circle-check"></i> Verify Queue</a></li>
          <li><a href="analytics.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
        <?php endif; ?>
        <li><a href="../api/auth/logout.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout (<?= htmlspecialchars($user['full_name']) ?>)</a></li>
      <?php else: ?>
        <li><a href="login.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-to-bracket"></i> Login</a></li>
        <li><a href="register.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Join Network</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <div class="container">
    <div style="margin-bottom: 1.5rem;">
      <h2>Campus Species Inventory</h2>
      <p style="color: var(--text-secondary); font-size: 0.95rem;">Catalog of all botanically cataloged plant species on campus</p>
    </div>

    <!-- Search & Filters -->
    <div class="glass-card" style="padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
      <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <input type="text" id="searchInput" class="form-control" style="flex: 2; min-width: 200px;" placeholder="Search by common name, scientific name or family...">
        <select id="familyFilter" class="form-control" style="flex: 1; min-width: 160px;">
          <option value="">All Botanical Families</option>
          <?php foreach ($families as $f): ?>
            <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="nativeFilter" class="form-control" style="flex: 1; min-width: 160px;">
          <option value="">All Native Statuses</option>
          <option value="native">Native</option>
          <option value="introduced">Introduced</option>
          <option value="invasive">Invasive ⚠️</option>
        </select>
      </div>
    </div>

    <!-- Species Grid -->
    <div id="speciesGrid" class="species-grid"></div>
  </div>

  <script>
    function loadSpecies() {
      const search = document.getElementById('searchInput').value;
      const family = document.getElementById('familyFilter').value;
      const nativeStatus = document.getElementById('nativeFilter').value;

      fetch(`../api/species/list.php?search=${encodeURIComponent(search)}&family=${encodeURIComponent(family)}&native_status=${encodeURIComponent(nativeStatus)}&limit=100`)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            renderGrid(data.species);
          }
        });
    }

    function renderGrid(speciesList) {
      const grid = document.getElementById('speciesGrid');
      grid.innerHTML = '';

      if (speciesList.length === 0) {
        grid.innerHTML = `<p style="grid-column: 1/-1; color: var(--text-muted); text-align: center; padding: 3rem;">No species match your filter criteria.</p>`;
        return;
      }

      speciesList.forEach(s => {
        let nativeBadge = `<span class="badge badge-${s.native_status || 'unknown'}">${s.native_status || 'unknown'}</span>`;
        let imgHtml = s.reference_image_url ? `<img src="${s.reference_image_url}" style="width:100%; height:160px; object-fit:cover; border-radius: var(--radius-sm); margin-bottom: 0.75rem;">` : '';

        const card = document.createElement('div');
        card.className = 'glass-card species-card';
        card.innerHTML = `
          <div>
            ${imgHtml}
            <h3 style="font-size: 1.15rem; margin-bottom: 0.2rem;">${s.common_name || s.scientific_name}</h3>
            <p style="font-style: italic; color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 0.5rem;">${s.scientific_name}</p>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.5rem;"><strong>Family:</strong> ${s.family || 'N/A'}</p>
            <div style="margin-bottom: 0.75rem;">${nativeBadge}</div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">${s.description || 'No description available.'}</p>
          </div>
          <div style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.85rem; font-weight: 700; color: var(--accent-primary);"><i class="fa-solid fa-tree"></i> ${s.plant_count} logged</span>
            <a href="dashboard.php?species_id=${s.id}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-map"></i> View Map</a>
          </div>
        `;
        grid.appendChild(card);
      });
    }

    loadSpecies();
    document.getElementById('searchInput').addEventListener('input', loadSpecies);
    document.getElementById('familyFilter').addEventListener('change', loadSpecies);
    document.getElementById('nativeFilter').addEventListener('change', loadSpecies);
  </script>
</body>
</html>
