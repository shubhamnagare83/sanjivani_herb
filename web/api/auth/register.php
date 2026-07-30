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

$isApi = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

$data = getJsonBody();
if (empty($data)) $data = $_POST;

// CSRF validation for form submissions
if (!$isApi && !empty($data['csrf_token'])) {
    if (!validateCSRFToken($data['csrf_token'])) {
        header('Location: ' . APP_URL . '/pages/register.php?error=' . urlencode('Session expired. Please try again.'));
        exit;
    }
}

$error = validateRequired($data, ['email', 'password', 'full_name']);
if ($error) {
    if ($isApi) jsonResponse(['error' => $error], 400);
    header('Location: ' . APP_URL . '/pages/register.php?error=' . urlencode($error));
    exit;
}

// Validate email format
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if ($isApi) jsonResponse(['error' => 'Invalid email format'], 400);
    header('Location: ' . APP_URL . '/pages/register.php?error=' . urlencode('Invalid email format'));
    exit;
}

// Validate password
if (strlen($data['password']) < 6) {
    if ($isApi) jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
    header('Location: ' . APP_URL . '/pages/register.php?error=' . urlencode('Password must be at least 6 characters'));
    exit;
}

// Sanitize full name - strip tags and trim
$fullName = trim(strip_tags($data['full_name']));
if (strlen($fullName) < 2) {
    if ($isApi) jsonResponse(['error' => 'Full name is too short'], 400);
    header('Location: ' . APP_URL . '/pages/register.php?error=' . urlencode('Full name is too short'));
    exit;
}

// Get default institution
$db = getDB();
$stmt = $db->query("SELECT id FROM institutions LIMIT 1");
$inst = $stmt->fetch();

if (!$inst) {
    if ($isApi) jsonResponse(['error' => 'No institution configured'], 500);
    header('Location: ' . APP_URL . '/pages/register.php?error=' . urlencode('System not configured. Contact admin.'));
    exit;
}

$institutionId = $data['institution_id'] ?? $inst['id'];
$role = 'contributor'; // Always contributor for self-registration

$result = registerUser($email, $data['password'], $fullName, $institutionId, $role);

if ($result['success']) {
    // Auto-login after registration
    $loginResult = loginUser($email, $data['password']);

    if ($isApi) {
        jsonResponse($loginResult, 201);
    }
    header('Location: ' . APP_URL . '/pages/dashboard.php');
    exit;
} else {
    if ($isApi) {
        jsonResponse($result, 409);
    }
    header('Location: ' . APP_URL . '/pages/register.php?error=' . urlencode($result['error']));
    exit;
}
