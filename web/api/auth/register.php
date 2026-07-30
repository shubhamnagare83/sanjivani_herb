<?php
/**
 * API: User Registration
 * POST /api/auth/register.php
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = getJsonBody();
if (empty($data)) $data = $_POST;

$error = validateRequired($data, ['email', 'password', 'full_name']);
if ($error) jsonResponse(['error' => $error], 400);

// Validate email format
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email format'], 400);
}

// Validate password length
if (strlen($data['password']) < 6) {
    jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
}

// Get default institution (first one)
$db = getDB();
$stmt = $db->query("SELECT id FROM institutions LIMIT 1");
$inst = $stmt->fetch();

if (!$inst) {
    jsonResponse(['error' => 'No institution configured. Please run the seed script first.'], 500);
}

$institutionId = $data['institution_id'] ?? $inst['id'];
$role = $data['role'] ?? 'contributor';

// Only admins can create verifier/admin accounts
if ($role !== 'contributor') {
    $currentUser = getCurrentUser();
    if (!$currentUser || $currentUser['role'] !== 'admin') {
        $role = 'contributor';
    }
}

$result = registerUser($data['email'], $data['password'], sanitize($data['full_name']), $institutionId, $role);

if ($result['success']) {
    // Auto-login after registration
    $loginResult = loginUser($data['email'], $data['password']);
    
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        jsonResponse($loginResult, 201);
    }
    header('Location: ' . APP_URL . '/pages/dashboard.php');
    exit;
} else {
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        jsonResponse($result, 409);
    }
    header('Location: ' . APP_URL . '/pages/register.php?error=' . urlencode($result['error']));
    exit;
}
