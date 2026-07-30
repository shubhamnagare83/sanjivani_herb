<?php
/**
 * API: User Logout
 * POST /api/auth/logout.php
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

logoutUser();

if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    jsonResponse(['success' => true, 'message' => 'Logged out']);
}

header('Location: ' . APP_URL . '/pages/login.php');
exit;
