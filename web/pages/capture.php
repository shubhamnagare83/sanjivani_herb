<?php
/**
 * Capture & Manual Entry Screen
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireAuth();
$db = getDB();

$zones = $db->query("SELECT id, name FROM zones ORDER BY name ASC")->fetchAll();
$speciesList = $db->query("SELECT id, scientific_name, common_name, family FROM species ORDER BY common_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Capture & Add Plant | Sanjivani Herb</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .step-card {
      display: none;
    }
    .step-card.active {
      display: block;
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
        Proceed to Fill Details <i class="fa-solid fa-arrow-right"></i>
      </button>
    </div>

    <!-- STEP 2: Manual Plant Details Entry -->
    <div id="step2" class="glass-card step-card" style="padding: 2rem;">
      <h2 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-pen-to-square"></i> Step 2: Plant Details (Manual Entry)</h2>
      <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.95rem;">Select an existing species from the catalog or enter custom plant details manually.</p>

      <form id="plantForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
        <input type="hidden" id="selectedSpeciesId" name="species_id">
        <input type="hidden" id="latInput" name="latitude">
        <input type="hidden" id="lngInput" name="longitude">

        <!-- Species Selection Dropdown -->
        <div class="form-group">
          <label class="form-label">Select Species from Database</label>
          <select id="speciesSelect" class="form-control" onchange="handleSpeciesSelect(this.value)">
            <option value="">-- Select Existing Species or Choose Custom --</option>
            <?php foreach ($speciesList as $sp): ?>
              <option value="<?= htmlspecialchars($sp['id']) ?>" 
                      data-scientific="<?= htmlspecialchars($sp['scientific_name']) ?>"
                      data-common="<?= htmlspecialchars($sp['common_name'] ?? '') ?>"
                      data-family="<?= htmlspecialchars($sp['family'] ?? '') ?>">
                <?= htmlspecialchars(($sp['common_name'] ? $sp['common_name'] . ' — ' : '') . $sp['scientific_name']) ?>
              </option>
            <?php endforeach; ?>
            <option value="custom">➕ Enter Custom Species Manually</option>
          </select>
        </div>

        <!-- Manual Input Fields -->
        <div class="form-group">
          <label class="form-label">Common Name *</label>
          <input type="text" id="commonInput" name="common_name" class="form-control" placeholder="e.g. Neem Tree, Banyan Tree" required>
        </div>

        <div class="form-group">
          <label class="form-label">Scientific Name *</label>
          <input type="text" id="scientificInput" name="scientific_name" class="form-control" placeholder="e.g. Azadirachta indica" required>
        </div>

        <div class="form-group">
          <label class="form-label">Family (Optional)</label>
          <input type="text" id="familyInput" name="family" class="form-control" placeholder="e.g. Meliaceae, Moraceae">
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
          <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Healthy condition, near science department entrance"></textarea>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
          <button type="button" onclick="goToStep(1)" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button type="submit" class="btn btn-primary" style="flex:1;"><i class="fa-solid fa-paper-plane"></i> Submit to Live Map</button>
        </div>
      </form>
    </div>

  </div>

  <script>
    let photoFile = null;
    let gpsLat = 19.8762;
    let gpsLng = 74.5981;

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
    }

    document.getElementById('btnToStep2').addEventListener('click', () => {
      document.getElementById('latInput').value = gpsLat;
      document.getElementById('lngInput').value = gpsLng;
      goToStep(2);
    });

    // Populate inputs when an existing species is selected from dropdown
    function handleSpeciesSelect(val) {
      const select = document.getElementById('speciesSelect');
      const selectedOpt = select.options[select.selectedIndex];

      if (val && val !== 'custom') {
        document.getElementById('selectedSpeciesId').value = val;
        document.getElementById('commonInput').value = selectedOpt.getAttribute('data-common') || '';
        document.getElementById('scientificInput').value = selectedOpt.getAttribute('data-scientific') || '';
        document.getElementById('familyInput').value = selectedOpt.getAttribute('data-family') || '';
      } else {
        document.getElementById('selectedSpeciesId').value = '';
        if (val === 'custom') {
          document.getElementById('commonInput').value = '';
          document.getElementById('scientificInput').value = '';
          document.getElementById('familyInput').value = '';
          document.getElementById('commonInput').focus();
        }
      }
    }

    // Form submission
    document.getElementById('plantForm').addEventListener('submit', function(e) {
      e.preventDefault();

      if (!photoFile) {
        alert('Please choose or take a photo in Step 1 first.');
        goToStep(1);
        return;
      }

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
          alert('Error: ' + (data.error || 'Failed to submit observation'));
        }
      })
      .catch(err => {
        alert('Error submitting form: ' + err.message);
      });
    });
  </script>

</body>
</html>
