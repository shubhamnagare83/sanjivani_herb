<?php
/**
 * Faculty & Admin Verification Queue
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = requireRole('verifier');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verification Queue | Sanjivani Herb</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .queue-card {
      display: grid;
      grid-template-columns: 200px 1fr 180px;
      gap: 1.5rem;
      padding: 1.25rem;
      margin-bottom: 1rem;
      align-items: center;
    }
    @media (max-width: 768px) {
      .queue-card {
        grid-template-columns: 1fr;
      }
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
      <li><a href="capture.php" class="nav-link"><i class="fa-solid fa-camera"></i> Capture Plant</a></li>
      <li><a href="verify.php" class="nav-link active"><i class="fa-solid fa-circle-check"></i> Verify Queue</a></li>
      <li><a href="analytics.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
      <li><a href="../api/auth/logout.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout (<?= htmlspecialchars($user['full_name']) ?>)</a></li>
    </ul>
  </nav>

  <div class="container">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h2>Faculty Verification Queue</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">Review and confirm student observations before publishing to public map</p>
      </div>
      <span id="pendingBadge" class="badge badge-pending" style="font-size: 1rem; padding: 0.5rem 1rem;">Loading...</span>
    </div>

    <div id="queueContainer"></div>
  </div>

  <script>
    function loadQueue() {
      fetch('../api/verify/queue.php')
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            document.getElementById('pendingBadge').innerText = `${data.total_pending} Pending Review`;
            renderQueue(data.data);
          }
        });
    }

    function renderQueue(records) {
      const container = document.getElementById('queueContainer');
      container.innerHTML = '';

      if (records.length === 0) {
        container.innerHTML = `
          <div class="glass-card" style="padding: 3rem; text-align: center;">
            <i class="fa-solid fa-circle-check" style="font-size: 3rem; color: var(--accent-primary); margin-bottom: 1rem;"></i>
            <h3>Queue Clean!</h3>
            <p style="color: var(--text-secondary);">All submitted observations have been reviewed and verified.</p>
          </div>
        `;
        return;
      }

      records.forEach(r => {
        const card = document.createElement('div');
        card.className = 'glass-card queue-card';

        let imgHtml = r.photo_url ? `<img src="${r.photo_url}" style="width:100%; height:140px; object-fit:cover; border-radius: var(--radius-sm);">` : `<div style="height:140px; background:rgba(0,0,0,0.3); border-radius: var(--radius-sm); display:flex; align-items:center; justify-content:center;">No Image</div>`;

        card.innerHTML = `
          <div>${imgHtml}</div>
          <div>
            <h3 style="font-size: 1.2rem; margin-bottom: 0.25rem;">${r.common_name || r.scientific_name || 'Unidentified'}</h3>
            <p style="font-style: italic; color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">${r.scientific_name || 'Species Pending'}</p>
            <p style="font-size: 0.85rem; margin-bottom: 0.25rem;"><strong>Submitted By:</strong> ${r.submitted_by_name || 'Contributor'}</p>
            <p style="font-size: 0.85rem; margin-bottom: 0.25rem;"><strong>Zone:</strong> ${r.zone_name || 'General Campus'}</p>
            <p style="font-size: 0.85rem; margin-bottom: 0.5rem;"><strong>Notes:</strong> ${r.notes || 'None'}</p>
            <span class="badge badge-pending">AI Score: ${r.ai_confidence ? r.ai_confidence + '%' : 'N/A'}</span>
          </div>
          <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <button onclick="handleAction('${r.id}', 'approved')" class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i> Approve</button>
            <button onclick="handleAction('${r.id}', 'rejected')" class="btn btn-danger btn-sm"><i class="fa-solid fa-xmark"></i> Reject</button>
          </div>
        `;

        container.appendChild(card);
      });
    }

    function handleAction(plantId, action) {
      fetch('../api/verify/action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ plant_record_id: plantId, action: action })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          loadQueue();
        } else {
          alert('Error: ' + data.error);
        }
      });
    }

    loadQueue();
  </script>

</body>
</html>
