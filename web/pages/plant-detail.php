<?php
/**
 * Public Plant Detail Page (QR Landing Page)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$slug = $_GET['slug'] ?? null;
$plant = null;
$qrImage = null;

if ($slug) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT pr.*, s.scientific_name, s.common_name, s.family, s.native_status, s.description AS species_description, s.medicinal_uses, s.reference_image_url,
               i.name AS institution_name, z.name AS zone_name, pp.file_path AS primary_photo, q.scan_count
        FROM qr_codes q
        JOIN plant_records pr ON q.plant_record_id = pr.id
        JOIN species s ON pr.species_id = s.id
        JOIN institutions i ON pr.institution_id = i.id
        LEFT JOIN zones z ON pr.zone_id = z.id
        LEFT JOIN plant_photos pp ON pp.plant_record_id = pr.id AND pp.is_primary = 1
        WHERE q.public_slug = ? AND pr.status = 'verified'
    ");
    $stmt->execute([$slug]);
    $plant = $stmt->fetch();

    if ($plant) {
        $db->prepare("UPDATE qr_codes SET scan_count = scan_count + 1 WHERE public_slug = ?")->execute([$slug]);
        $currentUrl = APP_URL . '/pages/plant-detail.php?slug=' . $slug;
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($currentUrl);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $plant ? htmlspecialchars($plant['common_name']) : 'Plant Detail' ?> | Sanjivani Herb</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <!-- Minimal Public Header -->
  <header style="padding: 1rem 1.5rem; background: rgba(9, 13, 22, 0.9); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
    <div class="nav-brand">
      <span class="icon">🌿</span>
      <span><?= htmlspecialchars($plant['institution_name'] ?? 'Sanjivani University') ?> Biodiversity Tag</span>
    </div>
    <a href="dashboard.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-map"></i> View Campus Map</a>
  </header>

  <div class="container" style="max-width: 700px; margin-top: 2rem;">
    <?php if (!$plant): ?>
      <div class="glass-card" style="padding: 3rem; text-align: center;">
        <i class="fa-solid fa-qrcode" style="font-size: 3rem; color: var(--status-pending); margin-bottom: 1rem;"></i>
        <h2>Plant Record Not Found</h2>
        <p style="color: var(--text-secondary);">This QR tag code is either invalid or pending verification.</p>
        <a href="dashboard.php" class="btn btn-primary" style="margin-top: 1.5rem;">Explore Campus Live Map</a>
      </div>
    <?php else: ?>
      <div class="glass-card" style="padding: 2rem; overflow: hidden;">

        <?php if ($plant['primary_photo']): ?>
          <img src="<?= UPLOAD_URL . htmlspecialchars($plant['primary_photo']) ?>" alt="<?= htmlspecialchars($plant['common_name']) ?>" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
          <div>
            <h1 style="font-size: 2.2rem; margin-bottom: 0.2rem;"><?= htmlspecialchars($plant['common_name']) ?></h1>
            <p style="font-style: italic; color: var(--text-secondary); font-size: 1.1rem;"><?= htmlspecialchars($plant['scientific_name']) ?></p>
          </div>
          <span class="badge badge-<?= $plant['native_status'] ?>"><?= htmlspecialchars($plant['native_status']) ?></span>
        </div>

        <div style="display: flex; gap: 1.5rem; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 1rem 0; margin-bottom: 1.5rem; font-size: 0.9rem;">
          <div><strong>Family:</strong> <?= htmlspecialchars($plant['family'] ?? 'N/A') ?></div>
          <div><strong>Zone:</strong> <?= htmlspecialchars($plant['zone_name'] ?? 'Campus') ?></div>
          <div><strong>Scans:</strong> <?= $plant['scan_count'] + 1 ?></div>
        </div>

        <div style="margin-bottom: 1.5rem;">
          <h4 style="margin-bottom: 0.5rem; color: var(--accent-primary);"><i class="fa-solid fa-book"></i> Species Overview</h4>
          <p style="color: var(--text-secondary); line-height: 1.6;"><?= nl2br(htmlspecialchars($plant['species_description'] ?? 'No detailed description.')) ?></p>
        </div>

        <?php if (!empty($plant['medicinal_uses'])): ?>
          <div style="margin-bottom: 1.5rem; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); padding: 1.25rem; border-radius: var(--radius-sm);">
            <h4 style="margin-bottom: 0.5rem; color: var(--accent-primary);"><i class="fa-solid fa-hand-holding-medical"></i> Traditional & Medicinal Uses</h4>
            <p style="color: var(--text-primary); font-size: 0.95rem; line-height: 1.5;"><?= htmlspecialchars($plant['medicinal_uses']) ?></p>
          </div>
        <?php endif; ?>

        <!-- Printable QR Code Section -->
        <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.3); padding: 1.25rem; border-radius: var(--radius-sm);">
          <div>
            <h4 style="margin-bottom: 0.3rem;">Physical Signage Tag</h4>
            <p style="font-size: 0.85rem; color: var(--text-muted);">Public Tag Code: <strong><?= htmlspecialchars($slug) ?></strong></p>
            <button onclick="window.print()" class="btn btn-secondary btn-sm" style="margin-top: 0.5rem;"><i class="fa-solid fa-print"></i> Print Signage Label</button>
          </div>
          <?php if ($qrImage): ?>
            <img src="<?= $qrImage ?>" alt="QR Code" style="width: 110px; height: 110px; border-radius: var(--radius-sm); border: 4px solid #fff;">
          <?php endif; ?>
        </div>

      </div>
    <?php endif; ?>
  </div>

</body>
</html>
