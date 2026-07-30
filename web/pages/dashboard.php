<?php
/**
 * Main Dashboard & Live Interactive Map
 * Ultra-Realistic Hybrid Satellite & Topo Map System
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
  <title>Live Satellite Map & Dashboard | Sanjivani Herb Mapper</title>
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
      align-items: center;
    }
    .filter-item {
      flex: 1;
      min-width: 170px;
    }
    .map-wrapper {
      position: relative;
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: var(--shadow-lg);
      border: 1px solid var(--border-color);
      background: #ffffff;
    }
    .map-overlay-card {
      position: absolute;
      top: 1rem;
      right: 1rem;
      z-index: 1000;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 0.85rem 1.1rem;
      box-shadow: var(--shadow-md);
      max-width: 260px;
    }
    .map-overlay-title {
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-muted);
      letter-spacing: 0.05em;
      margin-bottom: 0.5rem;
    }
    .layer-btn-group {
      display: flex;
      gap: 0.4rem;
      margin-bottom: 0.75rem;
    }
    .layer-btn {
      flex: 1;
      padding: 0.45rem 0.5rem;
      font-size: 0.78rem;
      font-weight: 700;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-color);
      background: #f8fafc;
      color: var(--text-secondary);
      cursor: pointer;
      text-align: center;
      transition: all 0.2s ease;
    }
    .layer-btn.active {
      background: var(--accent-light);
      color: #065f46;
      border-color: var(--accent-border);
      font-weight: 800;
    }
    .map-popup-card {
      max-width: 260px;
    }
    .map-popup-card img {
      width: 100%;
      height: 140px;
      object-fit: cover;
      border-radius: var(--radius-sm);
      margin-bottom: 0.6rem;
      transition: transform 0.3s ease;
    }
    .map-popup-card img:hover {
      transform: scale(1.03);
    }
    .leaflet-popup-content-wrapper {
      border-radius: var(--radius-md) !important;
      padding: 0.5rem !important;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
      <div>
        <h2 style="font-size: 1.9rem; margin-bottom: 0.2rem; font-weight: 800;">Campus Geo-Map</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">Geotagged plant observations with satellite imagery across Sanjivani University</p>
      </div>
      <div style="display: flex; gap: 0.75rem; align-items: center;">
        <button onclick="resetMapView()" class="btn btn-secondary btn-sm"><i class="fa-solid fa-crosshairs"></i> Center Campus</button>
        <div class="live-pulse">
          <span class="pulse-dot"></span>
          <span>LIVE SSE UPDATES</span>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="glass-card" style="padding: 1.1rem 1.4rem; margin-bottom: 1.5rem;">
      <div class="filter-bar">
        <div class="filter-item" style="flex: 2;">
          <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search by species name or notes...">
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
        <button id="applyFiltersBtn" class="btn btn-primary btn-sm"><i class="fa-solid fa-sliders"></i> Filter</button>
      </div>
    </div>

    <!-- Live Map Container with Satellite Switcher Overlay -->
    <div class="map-wrapper">
      <div id="map" style="height: 640px; width: 100%;"></div>

      <!-- Floating Glass Controls Overlay -->
      <div class="map-overlay-card">
        <div class="map-overlay-title"><i class="fa-solid fa-layer-group"></i> Map Mode</div>
        <div class="layer-btn-group">
          <button class="layer-btn active" id="btnModeStreet" onclick="setMapMode('street')">🗺️ Map</button>
          <button class="layer-btn" id="btnModeSatellite" onclick="setMapMode('satellite')">🛰️ Satellite</button>
          <button class="layer-btn" id="btnModeTopo" onclick="setMapMode('topo')">🏔️ Topo</button>
        </div>

        <div style="border-top: 1px solid var(--border-color); padding-top: 0.6rem; font-size: 0.82rem; color: var(--text-secondary);">
          <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
            <span>Visible Markers:</span>
            <strong id="visibleCount" style="color: var(--accent-primary);">0</strong>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span>Campus Center:</span>
            <strong>19.8762, 74.5981</strong>
          </div>
        </div>
      </div>
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

    // Initialize Leaflet Map
    const map = L.map('map', {
      center: [CENTER_LAT, CENTER_LNG],
      zoom: DEFAULT_ZOOM,
      zoomControl: true
    });

    // Base Tile Layers
    const streetLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; OpenStreetMap &copy; CARTO',
      subdomains: 'abcd',
      maxZoom: 20
    });

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
      attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
      maxZoom: 19
    });

    const satelliteLabelsLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_only_labels/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; CARTO',
      subdomains: 'abcd',
      maxZoom: 20
    });

    const satelliteGroup = L.layerGroup([satelliteLayer, satelliteLabelsLayer]);

    const topoLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      attribution: 'Map data &copy; OpenStreetMap contributors, SRTM | Map style &copy; OpenTopoMap',
      maxZoom: 17
    });

    // Default: Street Voyager Layer
    streetLayer.addTo(map);

    function setMapMode(mode) {
      document.querySelectorAll('.layer-btn').forEach(btn => btn.classList.remove('active'));

      if (mode === 'street') {
        map.removeLayer(satelliteGroup);
        map.removeLayer(topoLayer);
        map.addLayer(streetLayer);
        document.getElementById('btnModeStreet').classList.add('active');
      } else if (mode === 'satellite') {
        map.removeLayer(streetLayer);
        map.removeLayer(topoLayer);
        map.addLayer(satelliteGroup);
        document.getElementById('btnModeSatellite').classList.add('active');
      } else if (mode === 'topo') {
        map.removeLayer(streetLayer);
        map.removeLayer(satelliteGroup);
        map.addLayer(topoLayer);
        document.getElementById('btnModeTopo').classList.add('active');
      }
    }

    function resetMapView() {
      map.setView([CENTER_LAT, CENTER_LNG], DEFAULT_ZOOM);
    }

    // Cluster group for pins
    const markerCluster = L.markerClusterGroup({
      showCoverageOnHover: false,
      maxClusterRadius: 40
    });
    map.addLayer(markerCluster);

    let currentMarkers = {};

    // Get Custom Realistic SVG Pin Icon
    function getMarkerIcon(status, nativeStatus) {
      let color = '#059669'; // Verified Green
      if (status === 'pending_verification') color = '#d97706'; // Pending Orange
      if (status === 'rejected') color = '#dc2626'; // Rejected Red
      if (nativeStatus === 'invasive') color = '#db2777'; // Invasive Pink

      const svgIcon = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="38" height="38">
          <circle cx="16" cy="16" r="14" fill="${color}" fill-opacity="0.25"/>
          <circle cx="16" cy="16" r="10" fill="${color}" stroke="#ffffff" stroke-width="2.5"/>
          <path d="M16 10 C14 14, 12 17, 16 22 C20 17, 18 14, 16 10 Z" fill="#ffffff"/>
        </svg>`;

      return L.divIcon({
        html: svgIcon,
        className: 'custom-map-pin',
        iconSize: [38, 38],
        iconAnchor: [19, 19],
        popupAnchor: [0, -18]
      });
    }

    function escapeHtml(text) {
      if (!text) return '';
      return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
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

            document.getElementById('visibleCount').innerText = data.data.length;

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

      const cName = escapeHtml(plant.common_name || 'Unidentified Plant');
      const sName = escapeHtml(plant.scientific_name || '');
      const family = escapeHtml(plant.family || 'Botanical Species');
      const zName = escapeHtml(plant.zone_name || 'Campus Zone');
      const subName = escapeHtml(plant.submitted_by_name || 'Contributor');
      const qSlug = escapeHtml(plant.qr_slug || '');

      let photoHtml = plant.photo_url ? `<img src="${escapeHtml(plant.photo_url)}" alt="${cName}">` : '';
      let statusBadge = `<span class="badge badge-${escapeHtml(plant.status)}">${escapeHtml(plant.status)}</span>`;
      let nativeBadge = plant.native_status === 'invasive' ? `<span class="badge badge-invasive">Invasive ⚠️</span>` : (plant.native_status ? `<span class="badge badge-native">${escapeHtml(plant.native_status)}</span>` : '');

      let popupContent = `
        <div class="map-popup-card">
          ${photoHtml}
          <h4 style="font-size: 1.1rem; margin-bottom:0.15rem; color: #0f172a;">${cName}</h4>
          <p style="font-style: italic; color: #64748b; font-size: 0.88rem; margin-bottom: 0.4rem;">${sName} (${family})</p>
          <div style="margin-bottom:0.6rem;">${statusBadge} ${nativeBadge}</div>
          <p style="font-size: 0.83rem; margin-bottom: 0.35rem; color: #475569;"><strong>Zone:</strong> ${zName}</p>
          <p style="font-size: 0.83rem; margin-bottom: 0.75rem; color: #475569;"><strong>Contributor:</strong> ${subName}</p>
          ${qSlug ? `<a href="plant-detail.php?slug=${qSlug}" target="_blank" class="btn btn-primary btn-sm" style="width:100%; text-align:center;"><i class="fa-solid fa-qrcode"></i> Public Tag Details</a>` : ''}
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
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
      if (e.key === 'Enter') loadPlants();
    });

    // Realtime SSE Stream for Instant Map Push
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
