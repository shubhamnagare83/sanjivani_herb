<?php
/**
 * API: Analytics Summary
 * GET /api/analytics/summary.php
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'Method not allowed'], 405);

$user = requireAuth(true);
$db = getDB();
$instId = $user['institution_id'];

// Basic counts
$stmt = $db->prepare("SELECT COUNT(DISTINCT id) AS total_plants, COUNT(DISTINCT species_id) AS total_species, COUNT(DISTINCT submitted_by) AS total_contributors FROM plant_records WHERE institution_id = ?");
$stmt->execute([$instId]);
$counts = $stmt->fetch();

// Status breakdown
$stmt = $db->prepare("SELECT status, COUNT(*) AS cnt FROM plant_records WHERE institution_id = ? GROUP BY status");
$stmt->execute([$instId]);
$statusBreakdown = [];
foreach ($stmt->fetchAll() as $row) $statusBreakdown[$row['status']] = (int)$row['cnt'];

// Native status breakdown
$stmt = $db->prepare("SELECT IFNULL(s.native_status, 'unknown') AS ns, COUNT(*) AS cnt FROM plant_records pr LEFT JOIN species s ON pr.species_id = s.id WHERE pr.institution_id = ? GROUP BY ns");
$stmt->execute([$instId]);
$nativeBreakdown = [];
foreach ($stmt->fetchAll() as $row) $nativeBreakdown[$row['ns']] = (int)$row['cnt'];

// Zone breakdown
$stmt = $db->prepare("SELECT z.name, z.color_hex, COUNT(pr.id) AS cnt FROM zones z LEFT JOIN plant_records pr ON pr.zone_id = z.id WHERE z.institution_id = ? GROUP BY z.id ORDER BY cnt DESC");
$stmt->execute([$instId]);
$zoneBreakdown = $stmt->fetchAll();

// Recent activity (last 30 days per day)
$stmt = $db->prepare("SELECT DATE(created_at) AS date, COUNT(*) AS cnt FROM plant_records WHERE institution_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY date ASC");
$stmt->execute([$instId]);
$dailySubmissions = $stmt->fetchAll();

// Top species
$stmt = $db->prepare("SELECT s.common_name, s.scientific_name, COUNT(pr.id) AS cnt FROM plant_records pr JOIN species s ON pr.species_id = s.id WHERE pr.institution_id = ? GROUP BY s.id ORDER BY cnt DESC LIMIT 10");
$stmt->execute([$instId]);
$topSpecies = $stmt->fetchAll();

// Top contributors
$stmt = $db->prepare("SELECT u.full_name, COUNT(pr.id) AS cnt FROM plant_records pr JOIN users u ON pr.submitted_by = u.id WHERE pr.institution_id = ? GROUP BY u.id ORDER BY cnt DESC LIMIT 5");
$stmt->execute([$instId]);
$topContributors = $stmt->fetchAll();

$verified = $statusBreakdown['verified'] ?? 0;
$total = (int)$counts['total_plants'];
$verifiedPct = $total > 0 ? round(($verified / $total) * 100, 1) : 0;

jsonResponse([
    'success' => true,
    'total_plants' => $total,
    'total_species' => (int)$counts['total_species'],
    'total_contributors' => (int)$counts['total_contributors'],
    'verified_pct' => $verifiedPct,
    'status_breakdown' => $statusBreakdown,
    'native_breakdown' => $nativeBreakdown,
    'zone_breakdown' => $zoneBreakdown,
    'daily_submissions' => $dailySubmissions,
    'top_species' => $topSpecies,
    'top_contributors' => $topContributors
]);
