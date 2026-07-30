<?php
/**
 * API: Public Stats for Homepage
 * GET /api/stats.php
 * No authentication required
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'Method not allowed'], 405);

$db = getDB();

$totalPlants = (int)$db->query("SELECT COUNT(*) FROM plant_records WHERE status='verified'")->fetchColumn();
$totalSpecies = (int)$db->query("SELECT COUNT(*) FROM species")->fetchColumn();
$totalZones = (int)$db->query("SELECT COUNT(*) FROM zones")->fetchColumn();
$totalContrib = (int)$db->query("SELECT COUNT(DISTINCT submitted_by) FROM plant_records")->fetchColumn();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM plant_records WHERE status='pending_verification'")->fetchColumn();
$nativeCount = (int)$db->query("SELECT COUNT(*) FROM species WHERE native_status='native'")->fetchColumn();
$invasiveCount = (int)$db->query("SELECT COUNT(*) FROM species WHERE native_status='invasive'")->fetchColumn();

// Recent 4 verified
$recent = $db->query("SELECT pr.*, s.common_name, s.scientific_name, s.family, s.native_status, z.name AS zone_name, u.full_name AS contributor, pp.file_path AS primary_photo FROM plant_records pr LEFT JOIN species s ON pr.species_id=s.id LEFT JOIN zones z ON pr.zone_id=z.id LEFT JOIN users u ON pr.submitted_by=u.id LEFT JOIN plant_photos pp ON pp.plant_record_id=pr.id AND pp.is_primary=1 WHERE pr.status='verified' ORDER BY pr.created_at DESC LIMIT 4")->fetchAll();

foreach ($recent as &$r) {
    $r['photo_url'] = $r['primary_photo'] ? UPLOAD_URL . $r['primary_photo'] : null;
}

jsonResponse([
    'success' => true,
    'total_plants' => $totalPlants,
    'total_species' => $totalSpecies,
    'total_zones' => $totalZones,
    'total_contributors' => $totalContrib,
    'pending_count' => $pendingCount,
    'native_count' => $nativeCount,
    'invasive_count' => $invasiveCount,
    'recent_observations' => $recent
]);
