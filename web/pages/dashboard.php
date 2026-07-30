<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
$user = getCurrentUser();
$db = getDB();
$stmt = $db->query("SELECT campus_center_lat, campus_center_lng, default_zoom FROM institutions LIMIT 1");
$inst = $stmt->fetch();
$centerLat = $inst['campus_center_lat'] ?? 19.8762;
$centerLng = $inst['campus_center_lng'] ?? 74.5981;
$zoom = $inst['default_zoom'] ?? 17;
$zones = $db->query("SELECT id, name FROM zones ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Campus Map | Sanjivani Herb Mapper</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css"/>
  <style>
    .filter-bar{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center}
    .filter-item{flex:1;min-width:160px}
    .map-wrap{position:relative;border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--border-color);box-shadow:var(--shadow-lg);background:#fff}
    /* Floating overlay panel */
    .map-panel{position:absolute;z-index:800;background:rgba(255,255,255,.94);backdrop-filter:blur(14px);border:1px solid var(--border-color);border-radius:var(--radius-md);padding:.85rem 1rem;box-shadow:var(--shadow-md);font-size:.82rem}
    .map-panel-title{font-size:.72rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.06em;margin-bottom:.5rem}
    .layer-btns{display:flex;gap:.35rem;flex-wrap:wrap}
    .layer-btn{padding:.4rem .6rem;font-size:.76rem;font-weight:700;border-radius:8px;border:1px solid var(--border-color);background:#f8fafc;color:var(--text-secondary);cursor:pointer;transition:all .2s}
    .layer-btn.active{background:#ecfdf5;color:#065f46;border-color:#a7f3d0}
    .layer-btn:hover{background:#f1f5f9}
    /* Legend */
    .legend-row{display:flex;align-items:center;gap:.4rem;margin-bottom:.3rem}
    .legend-dot{width:10px;height:10px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,.15)}
    /* Popup */
    .map-popup{max-width:260px;font-family:var(--font-body)}
    .map-popup img{width:100%;height:140px;object-fit:cover;border-radius:8px;margin-bottom:.5rem}
    .leaflet-popup-content-wrapper{border-radius:14px!important;box-shadow:0 8px 28px rgba(0,0,0,.12)!important}
    .leaflet-popup-content{margin:10px 12px!important}
    /* Fullscreen button */
    .map-fs-btn{position:absolute;top:.75rem;left:.75rem;z-index:800;width:36px;height:36px;background:rgba(255,255,255,.92);border:1px solid var(--border-color);border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1rem;color:var(--text-secondary);box-shadow:var(--shadow-sm);transition:all .2s}
    .map-fs-btn:hover{background:#f1f5f9}
    .map-locate-btn{top:3.25rem}
    /* Minimap */
    .minimap-wrap{position:absolute;bottom:.75rem;right:.75rem;z-index:800;width:140px;height:100px;border-radius:10px;overflow:hidden;border:2px solid rgba(255,255,255,.9);box-shadow:var(--shadow-md)}
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="nav-brand"><span class="icon">🌿</span><span>Sanjivani Herb</span></div>
    <ul class="nav-menu">
      <li><a href="../index.php" class="nav-link"><i class="fa-solid fa-house"></i> Home</a></li>
      <li><a href="dashboard.php" class="nav-link active"><i class="fa-solid fa-map-location-dot"></i> Live Map</a></li>
      <li><a href="inventory.php" class="nav-link"><i class="fa-solid fa-book-bookmark"></i> Species List</a></li>
      <?php if ($user): ?>
        <li><a href="capture.php" class="nav-link"><i class="fa-solid fa-camera"></i> Capture</a></li>
        <?php if (in_array($user['role'], ['verifier', 'admin'])): ?>
          <li><a href="verify.php" class="nav-link"><i class="fa-solid fa-circle-check"></i> Verify</a></li>
          <li><a href="analytics.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
        <?php endif; ?>
        <li><a href="../api/auth/logout.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> <?= htmlspecialchars($user['full_name']) ?></a></li>
      <?php else: ?>
        <li><a href="login.php" class="btn btn-secondary btn-sm">Login</a></li>
        <li><a href="register.php" class="btn btn-primary btn-sm">Join</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem">
      <div>
        <h2 style="font-size:1.85rem;margin-bottom:.15rem;font-weight:800">Campus Geo-Map</h2>
        <p style="color:var(--text-secondary);font-size:.92rem">Real-time geotagged plant observations with satellite imagery</p>
      </div>
      <div class="live-pulse"><span class="pulse-dot"></span><span>LIVE UPDATES</span></div>
    </div>

    <!-- Filters -->
    <div class="glass-card" style="padding:1rem 1.25rem;margin-bottom:1.25rem">
      <div class="filter-bar">
        <div class="filter-item" style="flex:2"><input type="text" id="searchInput" class="form-control" placeholder="🔍 Search species or notes..."></div>
        <div class="filter-item">
          <select id="statusFilter" class="form-control">
            <option value="">All Statuses</option>
            <option value="verified" selected>Verified</option>
            <option value="pending_verification">Pending</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
        <div class="filter-item">
          <select id="zoneFilter" class="form-control">
            <option value="">All Zones</option>
            <?php foreach ($zones as $z): ?>
              <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-item">
          <select id="nativeFilter" class="form-control">
            <option value="">All Types</option>
            <option value="native">Native</option>
            <option value="introduced">Introduced</option>
            <option value="invasive">Invasive ⚠️</option>
          </select>
        </div>
        <button id="applyFiltersBtn" class="btn btn-primary btn-sm"><i class="fa-solid fa-sliders"></i> Filter</button>
      </div>
    </div>

    <!-- Map -->
    <div class="map-wrap">
      <div id="map" style="height:660px;width:100%"></div>

      <!-- Fullscreen & Locate -->
      <button class="map-fs-btn" id="fsBtn" title="Fullscreen"><i class="fa-solid fa-expand"></i></button>
      <button class="map-fs-btn map-locate-btn" id="locateBtn" title="My Location"><i class="fa-solid fa-location-crosshairs"></i></button>

      <!-- Layer Switcher -->
      <div class="map-panel" style="top:.75rem;right:.75rem;width:240px">
        <div class="map-panel-title"><i class="fa-solid fa-layer-group"></i> Map Layers</div>
        <div class="layer-btns" style="margin-bottom:.65rem">
          <button class="layer-btn active" onclick="setLayer('street',this)">🗺️ Map</button>
          <button class="layer-btn" onclick="setLayer('satellite',this)">🛰️ Satellite</button>
          <button class="layer-btn" onclick="setLayer('hybrid',this)">🌐 Hybrid</button>
          <button class="layer-btn" onclick="setLayer('terrain',this)">🏔️ Terrain</button>
        </div>
        <div style="border-top:1px solid var(--border-color);padding-top:.5rem">
          <div class="map-panel-title">Legend</div>
          <div class="legend-row"><span class="legend-dot" style="background:#059669"></span> Verified</div>
          <div class="legend-row"><span class="legend-dot" style="background:#d97706"></span> Pending</div>
          <div class="legend-row"><span class="legend-dot" style="background:#dc2626"></span> Rejected</div>
          <div class="legend-row"><span class="legend-dot" style="background:#db2777"></span> Invasive</div>
        </div>
        <div style="border-top:1px solid var(--border-color);padding-top:.5rem;margin-top:.5rem">
          <div style="display:flex;justify-content:space-between"><span>Markers:</span><strong id="markerCount" style="color:var(--accent-primary)">0</strong></div>
        </div>
      </div>

      <!-- Minimap -->
      <div class="minimap-wrap"><div id="minimap" style="width:100%;height:100%"></div></div>
    </div>
  </div>

  <a href="capture.php" class="fab" title="Capture Plant"><i class="fa-solid fa-camera"></i></a>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
  <script>
    const CL=<?=$centerLat?>,CN=<?=$centerLng?>,DZ=<?=$zoom?>;
    const map=L.map('map',{center:[CL,CN],zoom:DZ,zoomControl:false});
    L.control.zoom({position:'bottomleft'}).addTo(map);
    L.control.scale({imperial:false,position:'bottomleft'}).addTo(map);

    // Google-style tile layers
    const layers={
      street:L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',{subdomains:'abcd',maxZoom:20,attribution:'&copy; OSM &copy; CARTO'}),
      satellite:L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{maxZoom:19,attribution:'Tiles &copy; Esri'}),
      hybrid:L.layerGroup([
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{maxZoom:19}),
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_only_labels/{z}/{x}/{y}{r}.png',{subdomains:'abcd',maxZoom:20})
      ]),
      terrain:L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',{maxZoom:17,attribution:'&copy; OpenTopoMap'})
    };
    let activeLayer='street';
    layers.street.addTo(map);

    function setLayer(name,btn){
      if(activeLayer===name)return;
      map.removeLayer(layers[activeLayer]);
      layers[name].addTo?layers[name].addTo(map):map.addLayer(layers[name]);
      activeLayer=name;
      document.querySelectorAll('.layer-btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      // Update minimap tile
      miniTile.setUrl(name==='street'||name==='terrain'?'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png':'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}');
    }

    // Fullscreen
    document.getElementById('fsBtn').addEventListener('click',()=>{
      const el=document.querySelector('.map-wrap');
      if(!document.fullscreenElement){el.requestFullscreen();document.getElementById('map').style.height='100vh'}
      else{document.exitFullscreen();document.getElementById('map').style.height='660px'}
      setTimeout(()=>map.invalidateSize(),300);
    });

    // Locate me
    document.getElementById('locateBtn').addEventListener('click',()=>{
      map.locate({setView:true,maxZoom:18});
    });
    map.on('locationfound',e=>{L.circle(e.latlng,{radius:e.accuracy/2,color:'#3b82f6',fillOpacity:.15}).addTo(map);L.marker(e.latlng).addTo(map).bindPopup('You are here').openPopup()});

    // Minimap
    const miniTile=L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',{subdomains:'abcd',maxZoom:20});
    const minimap=L.map('minimap',{center:[CL,CN],zoom:DZ-5,zoomControl:false,dragging:false,scrollWheelZoom:false,attributionControl:false,layers:[miniTile]});
    const miniRect=L.rectangle(map.getBounds(),{color:'#059669',weight:2,fillOpacity:.15}).addTo(minimap);
    map.on('moveend',()=>{miniRect.setBounds(map.getBounds());minimap.fitBounds(map.getBounds().pad(2))});

    // Cluster & Markers
    const cluster=L.markerClusterGroup({showCoverageOnHover:false,maxClusterRadius:45});
    map.addLayer(cluster);
    let markers={};

    function pinIcon(status,native){
      let c='#059669';
      if(status==='pending_verification')c='#d97706';
      if(status==='rejected')c='#dc2626';
      if(native==='invasive')c='#db2777';
      return L.divIcon({
        html:`<svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 34 34"><circle cx="17" cy="17" r="15" fill="${c}" fill-opacity=".2"/><circle cx="17" cy="17" r="9" fill="${c}" stroke="#fff" stroke-width="2.5"/><circle cx="17" cy="17" r="4" fill="#fff"/></svg>`,
        className:'',iconSize:[34,34],iconAnchor:[17,17],popupAnchor:[0,-16]
      });
    }
    function esc(t){return t?String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'):''}

    function loadPlants(){
      const s=document.getElementById('statusFilter').value,z=document.getElementById('zoneFilter').value,
            n=document.getElementById('nativeFilter').value,q=document.getElementById('searchInput').value;
      fetch(`../api/plants/list.php?status=${encodeURIComponent(s)}&zone_id=${encodeURIComponent(z)}&native_status=${encodeURIComponent(n)}&search=${encodeURIComponent(q)}&limit=500`)
        .then(r=>r.json()).then(d=>{
          if(!d.success)return;
          cluster.clearLayers();markers={};
          document.getElementById('markerCount').innerText=d.data.length;
          d.data.forEach(p=>addPin(p));
        });
    }

    function addPin(p){
      if(markers[p.id])cluster.removeLayer(markers[p.id]);
      const m=L.marker([p.latitude,p.longitude],{icon:pinIcon(p.status,p.native_status)});
      const cn=esc(p.common_name||'Unidentified'),sn=esc(p.scientific_name||''),zn=esc(p.zone_name||'Campus'),
            by=esc(p.submitted_by_name||'Contributor'),sl=esc(p.qr_slug||''),fam=esc(p.family||'');
      const img=p.photo_url?`<img src="${esc(p.photo_url)}" alt="${cn}">`:'';
      const st=`<span class="badge badge-${esc(p.status)}">${esc(p.status)}</span>`;
      const nt=p.native_status==='invasive'?'<span class="badge badge-invasive">Invasive</span>':(p.native_status?`<span class="badge badge-native">${esc(p.native_status)}</span>`:'');
      m.bindPopup(`<div class="map-popup">${img}<h4 style="font-size:1.05rem;margin-bottom:.1rem;color:#0f172a">${cn}</h4><p style="font-style:italic;color:#64748b;font-size:.85rem;margin-bottom:.4rem">${sn}${fam?' · '+fam:''}</p><div style="margin-bottom:.5rem">${st} ${nt}</div><p style="font-size:.82rem;color:#475569;margin-bottom:.25rem"><b>Zone:</b> ${zn}</p><p style="font-size:.82rem;color:#475569;margin-bottom:.6rem"><b>By:</b> ${by}</p>${sl?`<a href="plant-detail.php?slug=${sl}" target="_blank" class="btn btn-primary btn-sm" style="width:100%;text-align:center"><i class="fa-solid fa-qrcode"></i> QR Details</a>`:''}</div>`);
      cluster.addLayer(m);markers[p.id]=m;
    }

    loadPlants();
    document.getElementById('applyFiltersBtn').addEventListener('click',loadPlants);
    document.getElementById('searchInput').addEventListener('keyup',e=>{if(e.key==='Enter')loadPlants()});

    // SSE live updates
    const sse=new EventSource('../api/events/stream.php');
    sse.addEventListener('plant_update',e=>{const d=JSON.parse(e.data);if(d.records)d.records.forEach(p=>addPin(p))});
  </script>
</body>
</html>
