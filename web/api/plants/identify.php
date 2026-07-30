<?php
/**
 * API: AI Plant Identification
 * POST /api/plants/identify.php
 * Accepts: multipart with 'photo' file, optional 'organ_hint'
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ai_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$user = requireAuth(true);

// Handle photo upload
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'Photo is required for identification'], 400);
}

$uploadResult = handlePhotoUpload($_FILES['photo']);
if (!$uploadResult['success']) {
    jsonResponse(['error' => $uploadResult['error']], 400);
}

$organHint = $_POST['organ_hint'] ?? 'auto';
$lat = (float)($_POST['latitude'] ?? 0);
$lng = (float)($_POST['longitude'] ?? 0);

// Call AI identification
$result = PlantIdentifier::identify($uploadResult['filepath'], $organHint);

if (!$result['success']) {
    jsonResponse(['error' => 'Identification failed. Please try manual entry.'], 502);
}

// Auto-detect zone if coordinates provided
$suggestedZoneId = null;
if ($lat && $lng) {
    $suggestedZoneId = PlantIdentifier::detectZone($lat, $lng, $user['institution_id']);
}

jsonResponse([
    'success' => true,
    'source' => $result['source'],
    'providers_queried' => $result['providers_queried'] ?? [],
    'consensus_matches' => $result['consensus_matches'] ?? 0,
    'candidates' => $result['candidates'],
    'photo_filename' => $uploadResult['filename'],
    'photo_url' => $uploadResult['url'],
    'suggested_zone_id' => $suggestedZoneId
]);
