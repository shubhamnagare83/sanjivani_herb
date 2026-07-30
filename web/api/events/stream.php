<?php
/**
 * Realtime Live Data Stream (Server-Sent Events)
 * GET /api/events/stream.php
 * Streams new plant records to connected clients without WebSockets
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Set SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable Nginx buffering

// Turn off output buffering
if (ob_get_level()) ob_end_clean();

$db = getDB();
$lastTime = $_GET['since'] ?? date('Y-m-d H:i:s', time() - 30);

// SSE event loop
$maxExecution = 25; // Send heartbeats for ~25 seconds then let browser reconnect automatically
$startTime = time();

while ((time() - $startTime) < $maxExecution) {
    // Check for new or updated plant records
    $stmt = $db->prepare("
        SELECT pr.id, pr.latitude, pr.longitude, pr.status, pr.created_at, pr.updated_at,
               s.scientific_name, s.common_name, s.family, s.native_status,
               z.name AS zone_name, z.color_hex AS zone_color,
               u.full_name AS submitted_by_name,
               pp.file_path AS primary_photo
        FROM plant_records pr
        LEFT JOIN species s ON pr.species_id = s.id
        LEFT JOIN zones z ON pr.zone_id = z.id
        LEFT JOIN users u ON pr.submitted_by = u.id
        LEFT JOIN plant_photos pp ON pp.plant_record_id = pr.id AND pp.is_primary = 1
        WHERE pr.updated_at > ?
        ORDER BY pr.updated_at ASC
    ");
    $stmt->execute([$lastTime]);
    $updates = $stmt->fetchAll();

    if (!empty($updates)) {
        foreach ($updates as &$up) {
            $up['photo_url'] = $up['primary_photo'] ? UPLOAD_URL . $up['primary_photo'] : null;
        }
        $lastTime = end($updates)['updated_at'];
        
        echo "event: plant_update\n";
        echo "data: " . json_encode(['timestamp' => $lastTime, 'records' => $updates]) . "\n\n";
        flush();
    }

    // Send heartbeat
    echo ":ping\n\n";
    flush();

    // Check if client disconnected
    if (connection_aborted()) break;

    sleep(2);
}
