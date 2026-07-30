<?php
/**
 * API: Create Plant Record
 * POST /api/plants/create.php
 * Accepts multipart form data (photo + fields) or JSON
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ai_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$user = requireAuth(true);
$db = getDB();

// Get data from form or JSON
$data = !empty($_POST) ? $_POST : getJsonBody();

// CSRF validation for session-authenticated web requests (skip for API token auth)
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$isTokenAuth = !empty($authHeader) && stripos($authHeader, 'Bearer') === 0;
if (!$isTokenAuth && isset($_SESSION['user_id']) && !empty($_POST)) {
    if (empty($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
        jsonResponse(['error' => 'Invalid or expired CSRF token'], 403);
    }
}

$error = validateRequired($data, ['latitude', 'longitude']);
if ($error) jsonResponse(['error' => $error], 400);

$lat = (float)$data['latitude'];
$lng = (float)$data['longitude'];

// Validate coordinates
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    jsonResponse(['error' => 'Invalid coordinates'], 400);
}

// Handle photo upload
$photoFilename = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadResult = handlePhotoUpload($_FILES['photo']);
    if (!$uploadResult['success']) {
        jsonResponse(['error' => $uploadResult['error']], 400);
    }
    $photoFilename = $uploadResult['filename'];
}

// Species handling
$speciesId = $data['species_id'] ?? null;
if (!$speciesId && !empty($data['scientific_name'])) {
    $speciesId = PlantIdentifier::findOrCreateSpecies(
        $data['scientific_name'],
        $data['common_name'] ?? '',
        $data['family'] ?? '',
        'manual'
    );
}

// Auto-detect zone
$zoneId = $data['zone_id'] ?? null;
if (!$zoneId) {
    $zoneId = PlantIdentifier::detectZone($lat, $lng, $user['institution_id']);
}

// Determine initial status (admin/verifier auto-verified)
$status = in_array($user['role'], ['admin', 'verifier']) ? 'verified' : 'pending_verification';

$recordId = generateUUID();

// Sanitize notes
$notes = isset($data['notes']) ? sanitize($data['notes']) : null;

$db->beginTransaction();

try {
    // Insert plant record
    $stmt = $db->prepare("
        INSERT INTO plant_records (id, institution_id, species_id, zone_id, latitude, longitude, 
            location_accuracy_m, status, ai_confidence, ai_candidates, notes, submitted_by, verified_by, verified_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $verifiedBy = $status === 'verified' ? $user['id'] : null;
    $verifiedAt = $status === 'verified' ? date('Y-m-d H:i:s') : null;

    $stmt->execute([
        $recordId,
        $user['institution_id'],
        $speciesId,
        $zoneId,
        $lat,
        $lng,
        $data['location_accuracy_m'] ?? null,
        $status,
        $data['ai_confidence'] ?? null,
        $data['ai_candidates'] ?? null,
        $notes,
        $user['id'],
        $verifiedBy,
        $verifiedAt
    ]);

    // Insert photo record
    if ($photoFilename) {
        $photoId = generateUUID();
        $stmt = $db->prepare("INSERT INTO plant_photos (id, plant_record_id, file_path, original_name, is_primary) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$photoId, $recordId, $photoFilename, $_FILES['photo']['name'] ?? 'photo.jpg']);
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['error' => 'Failed to save plant record'], 500);
}

// Log activity
$speciesName = $data['common_name'] ?? $data['scientific_name'] ?? 'Unknown plant';
logActivity($user['institution_id'], $user['id'], 'create', 'plant_record', $recordId, "Submitted " . sanitize($speciesName) . " observation");

jsonResponse([
    'success' => true,
    'plant_record_id' => $recordId,
    'status' => $status,
    'zone_id' => $zoneId,
    'photo_url' => $photoFilename ? UPLOAD_URL . $photoFilename : null
], 201);
