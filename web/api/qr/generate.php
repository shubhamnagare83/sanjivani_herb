<?php
/**
 * API: Generate/Retrieve QR Code for Plant Record
 * GET or POST /api/qr/generate.php
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user = getCurrentUser();
$db = getDB();

$plantId = $_GET['plant_record_id'] ?? $_POST['plant_record_id'] ?? null;
$slug = $_GET['slug'] ?? null;

if ($slug) {
    // Resolve public plant record by slug
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

    if (!$plant) {
        jsonResponse(['error' => 'Plant record not found or not verified'], 404);
    }

    // Increment scan count
    $db->prepare("UPDATE qr_codes SET scan_count = scan_count + 1 WHERE public_slug = ?")->execute([$slug]);

    if ($plant['primary_photo']) {
        $plant['photo_url'] = UPLOAD_URL . $plant['primary_photo'];
    }

    jsonResponse(['success' => true, 'plant' => $plant]);
}

if (!$plantId) {
    jsonResponse(['error' => 'plant_record_id or slug is required'], 400);
}

// Check if QR already exists
$stmt = $db->prepare("SELECT * FROM qr_codes WHERE plant_record_id = ?");
$stmt->execute([$plantId]);
$qr = $stmt->fetch();

if (!$qr) {
    // Check if record is verified
    $stmt = $db->prepare("SELECT id, status FROM plant_records WHERE id = ?");
    $stmt->execute([$plantId]);
    $plant = $stmt->fetch();

    if (!$plant) jsonResponse(['error' => 'Plant record not found'], 404);

    $qrId = generateUUID();
    $publicSlug = strtoupper(generateSlug(6));

    $stmt = $db->prepare("INSERT INTO qr_codes (id, plant_record_id, public_slug) VALUES (?, ?, ?)");
    $stmt->execute([$qrId, $plantId, $publicSlug]);

    $qr = ['id' => $qrId, 'plant_record_id' => $plantId, 'public_slug' => $publicSlug, 'scan_count' => 0];
}

$publicUrl = APP_URL . '/pages/plant-detail.php?slug=' . $qr['public_slug'];
// Google Chart API / QR generator fallback for QR PNG URL
$qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($publicUrl);

jsonResponse([
    'success' => true,
    'qr' => $qr,
    'public_url' => $publicUrl,
    'qr_image_url' => $qrImageUrl
]);
