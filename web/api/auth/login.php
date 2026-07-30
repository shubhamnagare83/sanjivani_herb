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

$data = getJsonBody();

// Also handle form data
if (empty($data)) {
    $data = $_POST;
}

$error = validateRequired($data, ['email', 'password']);
if ($error) jsonResponse(['error' => $error], 400);

$result = loginUser($data['email'], $data['password']);

if ($result['success']) {
    // If it's an AJAX/API request, return JSON
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        jsonResponse($result);
    }
    // Otherwise redirect to dashboard
    header('Location: ' . APP_URL . '/pages/dashboard.php');
    exit;
} else {
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        jsonResponse($result, 401);
    }
    header('Location: ' . APP_URL . '/pages/login.php?error=' . urlencode($result['error']));
    exit;
}
