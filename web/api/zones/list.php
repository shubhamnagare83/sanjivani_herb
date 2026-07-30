<?php
/**
 * API: List Campus Zones
 * GET /api/zones/list.php
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'Method not allowed'], 405);

$db = getDB();

$stmt = $db->query("
    SELECT z.*, COUNT(pr.id) AS total_plants
    FROM zones z
    LEFT JOIN plant_records pr ON pr.zone_id = z.id
    GROUP BY z.id
    ORDER BY z.name ASC
");
$zones = $stmt->fetchAll();

foreach ($zones as &$zone) {
    if ($zone['boundary_json']) {
        $zone['boundary_json'] = json_decode($zone['boundary_json'], true);
    }
}

jsonResponse(['success' => true, 'zones' => $zones]);
