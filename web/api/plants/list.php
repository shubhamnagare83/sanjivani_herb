<?php
/**
 * API: List Plant Records
 * GET /api/plants/list.php
 * Query params: status, zone_id, species_id, search, page, limit
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$user = getCurrentUser();
$db = getDB();
$pagination = getPagination();

// Build query
$where = ["1=1"];
$params = [];

// Filter by institution (if user is logged in)
if ($user) {
    $where[] = "pr.institution_id = ?";
    $params[] = $user['institution_id'];
}

// Status filter
if (!empty($_GET['status'])) {
    $where[] = "pr.status = ?";
    $params[] = $_GET['status'];
}

// Zone filter
if (!empty($_GET['zone_id'])) {
    $where[] = "pr.zone_id = ?";
    $params[] = $_GET['zone_id'];
}

// Species filter
if (!empty($_GET['species_id'])) {
    $where[] = "pr.species_id = ?";
    $params[] = $_GET['species_id'];
}

// Native status filter
if (!empty($_GET['native_status'])) {
    $where[] = "s.native_status = ?";
    $params[] = $_GET['native_status'];
}

// Search
if (!empty($_GET['search'])) {
    $where[] = "(s.common_name LIKE ? OR s.scientific_name LIKE ? OR pr.notes LIKE ?)";
    $search = '%' . $_GET['search'] . '%';
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

// For public/map view — only show verified unless user is verifier/admin
if (isset($_GET['public']) && $_GET['public'] === '1') {
    $where[] = "pr.status = 'verified'";
}

$whereClause = implode(' AND ', $where);

// Get total count
$countStmt = $db->prepare("SELECT COUNT(*) FROM plant_records pr LEFT JOIN species s ON pr.species_id = s.id WHERE $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Get records with joins
$sql = "
    SELECT 
        pr.id, pr.latitude, pr.longitude, pr.status, pr.ai_confidence,
        pr.notes, pr.created_at, pr.updated_at,
        s.id AS species_id, s.scientific_name, s.common_name, s.family, 
        s.native_status, s.description AS species_description, s.medicinal_uses,
        z.name AS zone_name, z.color_hex AS zone_color,
        u.full_name AS submitted_by_name,
        pp.file_path AS primary_photo,
        qr.public_slug AS qr_slug
    FROM plant_records pr
    LEFT JOIN species s ON pr.species_id = s.id
    LEFT JOIN zones z ON pr.zone_id = z.id
    LEFT JOIN users u ON pr.submitted_by = u.id
    LEFT JOIN plant_photos pp ON pp.plant_record_id = pr.id AND pp.is_primary = 1
    LEFT JOIN qr_codes qr ON qr.plant_record_id = pr.id
    WHERE $whereClause
    ORDER BY pr.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $pagination['limit'];
$params[] = $pagination['offset'];

$stmt = $db->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Build photo URLs
foreach ($records as &$record) {
    if ($record['primary_photo']) {
        $record['photo_url'] = UPLOAD_URL . $record['primary_photo'];
    } else {
        $record['photo_url'] = null;
    }
}

jsonResponse([
    'success' => true,
    'data' => $records,
    'pagination' => [
        'total' => $total,
        'page' => $pagination['page'],
        'limit' => $pagination['limit'],
        'total_pages' => ceil($total / $pagination['limit'])
    ]
]);
