<?php
/**
 * Capture & AI Identification Screen
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireAuth();
$db = getDB();

$zones = $db->query("SELECT id, name FROM zones ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Capture & Identify Plant | Sanjivani Herb</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .step-card {
      display: none;
    }
    .step-card.active {
      display: block;
    }
    .candidate-card {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      border-radius: var(--radius-md);
      border: 1px solid var(--border-color);
      cursor: pointer;
      margin-bottom: 0.75rem;
      transition: all 0.2s ease;
    }
    .candidate-card:hover, .candidate-card.selected {
      border-color: var(--accent-primary);
      background: rgba(16, 185, 129, 0.1);
    }
    .candidate-card.selected {
      box-shadow: 0 0 0 2px var(--accent-primary);
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
      <li><a href="inventory.php" class="nav-link"><i class="fa-solid fa-book-bookmark"></i> Species List</a></li>
      <li><a href="capture.php" class="nav-link active"><i class="fa-solid fa-camera"></i> Capture Plant</a></li>
      <?php if (in_array($user['role'], ['verifier', 'admin'])): ?>
        <li><a href="verify.php" class="nav-link"><i class="fa-solid fa-circle-check"></i> Verify Queue</a></li>
        <li><a href="analytics.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
      <?php endif; ?>
      <li><a href="../api/auth/logout.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout (<?= htmlspecialchars($user['full_name']) ?>)</a></li>
    </ul>
  </nav>

  <div class="container" style="max-width: 650px; margin-top: 2rem;">

    <!-- STEP 1: Photo Capture & GPS -->
    <div id="step1" class="glass-card step-card active" style="padding: 2rem;">
      <h2 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-camera"></i> Step 1: Photo & Location</h2>
      <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.95rem;">Take a photo or pick from gallery. GPS coordinates will be attached automatically.</p>

      <div style="border: 2px dashed var(--border-color); border-radius: var(--radius-md); padding: 2.5rem; text-align: center; margin-bottom: 1.5rem; background: rgba(0,0,0,0.2);">
        <input type="file" id="photoInput" accept="image/*" capture="environment" style="display: none;">
        <div id="previewContainer" style="display: none; margin-bottom: 1rem;">
          <img id="photoPreview" src="" alt="Preview" style="max-width: 100%; max-height: 250px; border-radius: var(--radius-sm);">
        </div>
        <button type="button" onclick="document.getElementById('photoInput').click()" class="btn btn-primary btn-lg">
          <i class="fa-solid fa-camera"></i> Choose / Take Photo
        </button>
      </div>

      <!-- GPS Status -->
      <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
        <div>
          <i class="fa-solid fa-location-dot" style="color: var(--accent-primary);"></i>
          <span id="gpsStatus">Acquiring GPS location...</span>
        </div>
        <button id="retryGpsBtn" type="button" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-right"></i> Retry GPS</button>
      </div>

      <button id="btnToStep2" type="button" class="btn btn-primary" style="width: 100%;" disabled>
        Proceed to AI Identification <i class="fa-solid fa-arrow-right"></i>
      </button>
    </div>

    <!-- STEP 2: AI Identification Loading & Candidate Selection -->
    <div id="step2" class="glass-card step-card" style="padding: 2rem;">
      <h2 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-robot"></i> Step 2: AI Species Identification</h2>
      <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.95rem;">Pl@ntNet neural network model analyzed your image.</p>

      <div id="aiLoading" style="text-align: center; padding: 3rem 0;">
        <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 3rem; color: var(--accent-primary); margin-bottom: 1rem;"></i>
        <h4>Analyzing Plant Image...</h4>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Comparing against 10,000+ botanical species</p>
      </div>

      <div id="aiResults" style="display: none;">
        <h4 style="margin-bottom: 1rem;">Select Candidate Species:</h4>
        <div id="candidatesList"></div>

        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
          <button type="button" onclick="goToStep(1)" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button id="btnToStep3" type="button" class="btn btn-primary" style="flex:1;">Confirm & Details <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>

    <!-- STEP 3: Final Details & Submit -->
    <div id="step3" class="glass-card step-card" style="padding: 2rem;">
      <h2 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-check-double"></i> Step 3: Confirm Observation</h2>
      <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.95rem;">Review species, campus zone, and add optional notes.</p>

      <form id="plantForm">
        <input type="hidden" id="selectedSpeciesId" name="species_id">
        <input type="hidden" id="selectedScientific" name="scientific_name">
        <input type="hidden" id="selectedCommon" name="common_name">
        <input type="hidden" id="selectedFamily" name="family">
        <input type="hidden" id="aiConfidenceInput" name="ai_confidence">
        <input type="hidden" id="latInput" name="latitude">
        <input type="hidden" id="lngInput" name="longitude">

        <div class="form-group">
          <label class="form-label">Selected Species</label>
          <input type="text" id="speciesDisplay" class="form-control" readonly style="font-weight: 700; color: var(--accent-primary);">
        </div>

        <div class="form-group">
          <label class="form-label">Campus Zone</label>
          <select id="zoneInput" name="zone_id" class="form-control">
            <option value="">Auto-detect by GPS Proximity</option>
            <?php foreach ($zones as $z): ?>
              <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Observation Notes (Optional)</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Near botanical garden fountain, healthy condition"></textarea>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
          <button type="button" onclick="goToStep(2)" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button type="submit" class="btn btn-primary" style="flex:1;"><i class="fa-solid fa-paper-plane"></i> Submit to Live Map</button>
        </div>
      </form>
    </div>

  </div>

  <script>
    let photoFile = null;
    let gpsLat = 19.8762;
    let gpsLng = 74.5981;
    let selectedCandidate = null;

    // Get Geolocation
    function getGPS() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          pos => {
            gpsLat = pos.coords.latitude;
            gpsLng = pos.coords.longitude;
            document.getElementById('gpsStatus').innerText = `GPS Acquired: ${gpsLat.toFixed(4)}, ${gpsLng.toFixed(4)}`;
          },
          err => {
            document.getElementById('gpsStatus').innerText = `Using Default Sanjivani Campus Location (19.8762, 74.5981)`;
          }
        );
      }
    }
    getGPS();
    document.getElementById('retryGpsBtn').addEventListener('click', getGPS);

    // Photo selection preview
    document.getElementById('photoInput').addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        photoFile = e.target.files[0];
        const reader = new FileReader();
        reader.onload = function(evt) {
          document.getElementById('photoPreview').src = evt.target.result;
          document.getElementById('previewContainer').style.display = 'block';
          document.getElementById('btnToStep2').disabled = false;
        };
        reader.readAsDataURL(photoFile);
      }
    });

    function goToStep(stepNum) {
      document.querySelectorAll('.step-card').forEach(card => card.classList.remove('active'));
      document.getElementById('step' + stepNum).classList.add('active');

      if (stepNum === 2) {
        triggerAI();
      }
    }

    document.getElementById('btnToStep2').addEventListener('click', () => goToStep(2));

    // Call AI Endpoint
    function triggerAI() {
      document.getElementById('aiLoading').style.display = 'block';
      document.getElementById('aiResults').style.display = 'none';

      const formData = new FormData();
      formData.append('photo', photoFile);
      formData.append('latitude', gpsLat);
      formData.append('longitude', gpsLng);

      fetch('../api/plants/identify.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        document.getElementById('aiLoading').style.display = 'none';
        document.getElementById('aiResults').style.display = 'block';

        if (data.success && data.candidates) {
          renderCandidates(data.candidates, data.suggested_zone_id);
        } else {
          document.getElementById('candidatesList').innerHTML = `<p style="color:#ef4444;">AI identification unavailable. Please enter species manually.</p>`;
        }
      });
    }

    function renderCandidates(candidates, suggestedZoneId) {
      const container = document.getElementById('candidatesList');
      container.innerHTML = '';

      if (suggestedZoneId) {
        document.getElementById('zoneInput').value = suggestedZoneId;
      }

      candidates.forEach((cand, idx) => {
        const div = document.createElement('div');
        div.className = `candidate-card ${idx === 0 ? 'selected' : ''}`;
        div.onclick = () => selectCandidate(cand, div);

        div.innerHTML = `
          <div style="font-size: 1.5rem; color: var(--accent-primary);">🌿</div>
          <div style="flex:1;">
            <div style="font-weight: 700;">${cand.common_name || cand.scientific_name}</div>
            <div style="font-style: italic; font-size: 0.85rem; color: var(--text-secondary);">${cand.scientific_name}</div>
          </div>
          <div style="text-align: right;">
            <span class="badge badge-verified">${cand.confidence}% match</span>
          </div>
        `;

        container.appendChild(div);
        if (idx === 0) selectCandidate(cand, div);
      });
    }

    function selectCandidate(cand, elem) {
      document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('selected'));
      elem.classList.add('selected');
      selectedCandidate = cand;

      document.getElementById('selectedSpeciesId').value = cand.species_id || '';
      document.getElementById('selectedScientific').value = cand.scientific_name;
      document.getElementById('selectedCommon').value = cand.common_name;
      document.getElementById('selectedFamily').value = cand.family;
      document.getElementById('aiConfidenceInput').value = cand.confidence;
      document.getElementById('speciesDisplay').value = `${cand.common_name || cand.scientific_name} (${cand.scientific_name})`;
    }

    document.getElementById('btnToStep3').addEventListener('click', () => {
      document.getElementById('latInput').value = gpsLat;
      document.getElementById('lngInput').value = gpsLng;
      goToStep(3);
    });

    // Form submission
    document.getElementById('plantForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      formData.append('photo', photoFile);

      fetch('../api/plants/create.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert('🌱 Observation successfully added to Live Map!');
          window.location.href = 'dashboard.php';
        } else {
          alert('Error: ' + data.error);
        }
      });
    });
  </script>

</body>
</html>
