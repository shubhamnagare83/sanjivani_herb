<?php
/**
 * API: User Login
 * POST /api/auth/login.php
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Determine if it's a JSON API call or form submission
$isApi = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

$data = getJsonBody();
if (empty($data)) {
    $data = $_POST;
}

// Validate CSRF token for form submissions (not API calls)
if (!$isApi && !empty($data['csrf_token'])) {
    if (!validateCSRFToken($data['csrf_token'])) {
        if ($isApi) {
            jsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
        header('Location: ' . APP_URL . '/pages/login.php?error=' . urlencode('Session expired. Please try again.'));
        exit;
    }
}

$error = validateRequired($data, ['email', 'password']);
if ($error) {
    if ($isApi) jsonResponse(['error' => $error], 400);
    header('Location: ' . APP_URL . '/pages/login.php?error=' . urlencode($error));
    exit;
}

// Sanitize email
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if ($isApi) jsonResponse(['error' => 'Invalid email format'], 400);
    header('Location: ' . APP_URL . '/pages/login.php?error=' . urlencode('Invalid email format'));
    exit;
}

$result = loginUser($email, $data['password']);

if ($result['success']) {
    if ($isApi) {
        jsonResponse($result);
    }
    header('Location: ' . APP_URL . '/pages/dashboard.php');
    exit;
} else {
    if ($isApi) {
        jsonResponse($result, 401);
    }
    header('Location: ' . APP_URL . '/pages/login.php?error=' . urlencode($result['error']));
    exit;
}
