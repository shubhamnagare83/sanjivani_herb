<?php
/**
 * Main Dashboard & Live Interactive Map
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = getCurrentUser();
$db = getDB();

// Get campus center default
$stmt = $db->query("SELECT campus_center_lat, campus_center_lng, default_zoom FROM institutions LIMIT 1");
$inst = $stmt->fetch();
$centerLat = $inst['campus_center_lat'] ?? 19.8762;
$centerLng = $inst['campus_center_lng'] ?? 74.5981;
$zoom = $inst['default_zoom'] ?? 17;

// Fetch zones for filter dropdown
$zones = $db->query("SELECT id, name FROM zones ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Map & Dashboard | Sanjivani Herb Mapper</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
  <style>
    .filter-bar {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      margin-bottom: 1.25rem;
      align-items: center;
    }
    .filter-item {
      flex: 1;
      min-width: 160px;
    }
    .map-popup-card {
      max-width: 240px;
    }
    .map-popup-card img {
      width: 100%;
      height: 130px;
      object-fit: cover;
      border-radius: var(--radius-sm);
      margin-bottom: 0.5rem;
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
      <li><a href="dashboard.php" class="nav-link active"><i class="fa-solid fa-map-location-dot"></i> Live Map</a></li>
      <li><a href="inventory.php" class="nav-link"><i class="fa-solid fa-book-bookmark"></i> Species List</a></li>
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
    <!-- Header Controls -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
      <div>
        <h2 style="font-size: 1.8rem; margin-bottom: 0.2rem;">Interactive Campus Map</h2>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Geotagged plant biodiversity records across Sanjivani University</p>
      </div>
      <div class="live-pulse">
        <span class="pulse-dot"></span>
        <span>REALTIME SSE ACTIVE</span>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="glass-card" style="padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
      <div class="filter-bar">
        <div class="filter-item">
          <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search species or notes...">
        </div>
        <div class="filter-item">
          <select id="statusFilter" class="form-control">
            <option value="">All Statuses</option>
            <option value="verified" selected>Verified Only</option>
            <option value="pending_verification">Pending Verification</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
        <div class="filter-item">
          <select id="zoneFilter" class="form-control">
            <option value="">All Campus Zones</option>
            <?php foreach ($zones as $z): ?>
              <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-item">
          <select id="nativeFilter" class="form-control">
            <option value="">All Native Statuses</option>
            <option value="native">Native Species</option>
            <option value="introduced">Introduced</option>
            <option value="invasive">Invasive Species ⚠️</option>
          </select>
        </div>
        <button id="applyFiltersBtn" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
      </div>
    </div>

    <!-- Live Map Container -->
    <div class="map-container">
      <div id="map"></div>
    </div>
  </div>

  <!-- Floating Action Button for Plant Capture -->
  <a href="capture.php" class="fab" title="Capture & Identify Plant">
    <i class="fa-solid fa-camera"></i>
  </a>

  <!-- Leaflet JS & Scripts -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
  <script>
    const CENTER_LAT = <?= $centerLat ?>;
    const CENTER_LNG = <?= $centerLng ?>;
    const DEFAULT_ZOOM = <?= $zoom ?>;

    // Initialize Map
    const map = L.map('map').setView([CENTER_LAT, CENTER_LNG], DEFAULT_ZOOM);

    // Dark Tile Layer (CartoDB Dark Matter)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; OpenStreetMap &copy; CARTO',
      subdomains: 'abcd',
      maxZoom: 20
    }).addTo(map);

    // Cluster group for pins
    const markerCluster = L.markerClusterGroup();
    map.addLayer(markerCluster);

    let currentMarkers = {};

    // Get Marker Icon based on status and native status
    function getMarkerIcon(status, nativeStatus) {
      let color = '#10b981'; // Verified Green
      if (status === 'pending_verification') color = '#f59e0b'; // Pending Orange
      if (status === 'rejected') color = '#ef4444'; // Rejected Red
      if (nativeStatus === 'invasive') color = '#ec4899'; // Invasive Pink

      const svgIcon = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="36" height="36">
          <path fill="${color}" stroke="#ffffff" stroke-width="1.5" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
        </svg>`;

      return L.divIcon({
        html: svgIcon,
        className: 'custom-map-pin',
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [0, -32]
      });
    }

    // Load plant records from API
    function loadPlants() {
      const status = document.getElementById('statusFilter').value;
      const zoneId = document.getElementById('zoneFilter').value;
      const nativeStatus = document.getElementById('nativeFilter').value;
      const search = document.getElementById('searchInput').value;

      const url = `../api/plants/list.php?status=${encodeURIComponent(status)}&zone_id=${encodeURIComponent(zoneId)}&native_status=${encodeURIComponent(nativeStatus)}&search=${encodeURIComponent(search)}&limit=500`;

      fetch(url)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            markerCluster.clearLayers();
            currentMarkers = {};

            data.data.forEach(plant => {
              addPlantMarker(plant);
            });
          }
        });
    }

    // Add marker to map
    function addPlantMarker(plant) {
      if (currentMarkers[plant.id]) {
        markerCluster.removeLayer(currentMarkers[plant.id]);
      }

      const icon = getMarkerIcon(plant.status, plant.native_status);
      const marker = L.marker([plant.latitude, plant.longitude], { icon });

      let photoHtml = plant.photo_url ? `<img src="${plant.photo_url}" alt="${plant.common_name}">` : '';
      let statusBadge = `<span class="badge badge-${plant.status}">${plant.status}</span>`;
      let nativeBadge = plant.native_status === 'invasive' ? `<span class="badge badge-invasive">Invasive ⚠️</span>` : (plant.native_status ? `<span class="badge badge-native">${plant.native_status}</span>` : '');

      let popupContent = `
        <div class="map-popup-card">
          ${photoHtml}
          <h4 style="margin-bottom:0.2rem;">${plant.common_name || 'Unidentified Plant'}</h4>
          <p style="font-style: italic; color: #6b7280; font-size: 0.85rem; margin-bottom: 0.5rem;">${plant.scientific_name || ''}</p>
          <div style="margin-bottom:0.5rem;">${statusBadge} ${nativeBadge}</div>
          <p style="font-size: 0.82rem; margin-bottom: 0.5rem;"><strong>Zone:</strong> ${plant.zone_name || 'N/A'}</p>
          <p style="font-size: 0.82rem; margin-bottom: 0.75rem;"><strong>By:</strong> ${plant.submitted_by_name || 'Anonymous'}</p>
          ${plant.qr_slug ? `<a href="plant-detail.php?slug=${plant.qr_slug}" target="_blank" class="btn btn-primary btn-sm" style="width:100%; display:block; text-align:center;"><i class="fa-solid fa-qrcode"></i> View QR Details</a>` : ''}
        </div>
      `;

      marker.bindPopup(popupContent);
      markerCluster.addLayer(marker);
      currentMarkers[plant.id] = marker;
    }

    // Initial load
    loadPlants();

    // Event listeners
    document.getElementById('applyFiltersBtn').addEventListener('click', loadPlants);

    // Setup Realtime SSE Listener for Live Data Push
    const sse = new EventSource('../api/events/stream.php');
    sse.addEventListener('plant_update', function(e) {
      const data = JSON.parse(e.data);
      if (data.records && data.records.length > 0) {
        data.records.forEach(plant => {
          addPlantMarker(plant);
        });
      }
    });
  </script>

</body>
</html>
