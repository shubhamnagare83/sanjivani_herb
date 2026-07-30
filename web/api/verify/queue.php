<?php
/**
 * API: Verification Queue
 * GET /api/verify/queue.php
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'Method not allowed'], 405);

$user = requireRole('verifier', true);
$db = getDB();
$pagination = getPagination();

$stmt = $db->prepare("
    SELECT pr.id, pr.latitude, pr.longitude, pr.status, pr.ai_confidence, pr.ai_candidates, pr.notes, pr.created_at,
        s.scientific_name, s.common_name, s.family, s.native_status, z.name AS zone_name,
        u.full_name AS submitted_by_name, pp.file_path AS primary_photo
    FROM plant_records pr
    LEFT JOIN species s ON pr.species_id = s.id
    LEFT JOIN zones z ON pr.zone_id = z.id
    LEFT JOIN users u ON pr.submitted_by = u.id
    LEFT JOIN plant_photos pp ON pp.plant_record_id = pr.id AND pp.is_primary = 1
    WHERE pr.institution_id = ? AND pr.status = 'pending_verification'
    ORDER BY pr.created_at ASC LIMIT ? OFFSET ?
");
$stmt->execute([$user['institution_id'], $pagination['limit'], $pagination['offset']]);
$records = $stmt->fetchAll();

foreach ($records as &$r) {
    $r['photo_url'] = $r['primary_photo'] ? UPLOAD_URL . $r['primary_photo'] : null;
    if ($r['ai_candidates']) $r['ai_candidates'] = json_decode($r['ai_candidates'], true);
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM plant_records WHERE institution_id = ? AND status = 'pending_verification'");
$countStmt->execute([$user['institution_id']]);

jsonResponse(['success' => true, 'data' => $records, 'total_pending' => (int)$countStmt->fetchColumn()]);
