<?php
/**
 * API: Verification Actions
 * POST /api/verify/action.php
 * Body: { plant_record_id, action, reason?, new_species_id? }
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$user = requireRole('verifier', true);
$db = getDB();

$data = getJsonBody();
if (empty($data)) $data = $_POST;

// Validate CSRF token for web session actions (skip for API token auth)
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$isTokenAuth = !empty($authHeader) && stripos($authHeader, 'Bearer') === 0;
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$tokenToTest = $data['csrf_token'] ?? $csrfHeader;
if (!$isTokenAuth && isset($_SESSION['user_id']) && !validateCSRFToken($tokenToTest)) {
    jsonResponse(['error' => 'Invalid or missing CSRF token'], 403);
}

$error = validateRequired($data, ['plant_record_id', 'action']);
if ($error) jsonResponse(['error' => $error], 400);

$validActions = ['approved', 'rejected', 'edited', 'merged'];
if (!in_array($data['action'], $validActions)) {
    jsonResponse(['error' => 'Invalid action. Must be: ' . implode(', ', $validActions)], 400);
}

$plantId = $data['plant_record_id'];

// Check plant record exists
$stmt = $db->prepare("SELECT id, species_id, status FROM plant_records WHERE id = ? AND institution_id = ?");
$stmt->execute([$plantId, $user['institution_id']]);
$plant = $stmt->fetch();

if (!$plant) {
    jsonResponse(['error' => 'Plant record not found'], 404);
}

$db->beginTransaction();

try {
    $oldSpeciesId = $plant['species_id'];
    $newSpeciesId = $data['new_species_id'] ?? $oldSpeciesId;
    
    // Update plant record based on action
    switch ($data['action']) {
        case 'approved':
            $stmt = $db->prepare("UPDATE plant_records SET status = 'verified', verified_by = ?, verified_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id'], $plantId]);
            break;
            
        case 'rejected':
            $stmt = $db->prepare("UPDATE plant_records SET status = 'rejected', verified_by = ?, verified_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id'], $plantId]);
            break;
            
        case 'edited':
            $updates = ['status = \'verified\'', 'verified_by = ?', 'verified_at = NOW()', 'updated_at = NOW()'];
            $updateParams = [$user['id']];
            
            if ($newSpeciesId !== $oldSpeciesId) {
                $updates[] = 'species_id = ?';
                $updateParams[] = $newSpeciesId;
            }
            if (isset($data['notes'])) {
                $updates[] = 'notes = ?';
                $updateParams[] = sanitize($data['notes']);
            }
            
            $updateParams[] = $plantId;
            $stmt = $db->prepare("UPDATE plant_records SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($updateParams);
            break;
            
        case 'merged':
            // Mark as rejected and note the merge target
            $stmt = $db->prepare("UPDATE plant_records SET status = 'rejected', verified_by = ?, verified_at = NOW(), notes = CONCAT(IFNULL(notes, ''), ' [Merged]'), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id'], $plantId]);
            break;
    }
    
    // Create verification audit record
    $verifId = generateUUID();
    $stmt = $db->prepare("INSERT INTO verifications (id, plant_record_id, action, reason, old_species_id, new_species_id, performed_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $verifId, $plantId, $data['action'],
        $data['reason'] ?? null, $oldSpeciesId, $newSpeciesId, $user['id']
    ]);
    
    // Log activity
    logActivity($user['institution_id'], $user['id'], 'verify', 'plant_record', $plantId, 
        ucfirst($data['action']) . ' plant record' . ($data['reason'] ? ": {$data['reason']}" : ''));
    
    $db->commit();
    
    jsonResponse([
        'success' => true,
        'message' => 'Verification action completed',
        'action' => $data['action'],
        'plant_record_id' => $plantId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log('Verification error: ' . $e->getMessage());
    jsonResponse(['error' => 'Verification failed. Please try again.'], 500);
}
