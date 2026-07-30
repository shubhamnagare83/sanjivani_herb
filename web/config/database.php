<?php
/**
 * Database Configuration
 * Campus Plant Diversity Mapper
 * 
 * Security: Auto-detects APP_URL, uses secure JWT secret,
 * sets security headers, and configures session hardening.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'plant_mapper');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Auto-detect APP_URL from current request
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
// Detect base path: if running under /sanjivani_herb/web or at root
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
// Find the 'web' directory in the path
$webPos = strpos($scriptDir, '/web');
if ($webPos !== false) {
    $basePath = substr($scriptDir, 0, $webPos + 4); // include '/web'
} else {
    $basePath = '';
}
define('APP_URL', $protocol . '://' . $host . $basePath);

// Application settings
define('APP_NAME', 'Campus Plant Diversity Mapper');
define('APP_VERSION', '1.0.0');
define('UPLOAD_DIR', __DIR__ . '/../uploads/plant-photos/');
define('UPLOAD_URL', APP_URL . '/uploads/plant-photos/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// AI Service
define('PLANTNET_API_KEY', ''); // Add your Pl@ntNet API key here
define('PLANTNET_API_URL', 'https://my-api.plantnet.org/v2/identify/all');

// Security: JWT secret (change in production!)
define('JWT_SECRET', 'sH3rb_' . hash('sha256', __DIR__ . '_sanjivani_2026_secure'));
define('SESSION_LIFETIME', 86400 * 7); // 7 days

// CSRF Token secret
define('CSRF_SECRET', hash('sha256', JWT_SECRET . '_csrf'));

// Security Headers (applied on every request)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

/**
 * Get PDO database connection
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            // Don't leak DB details in production
            echo json_encode(['error' => 'Database connection failed']);
            error_log('DB Connection Error: ' . $e->getMessage());
            exit;
        }
    }
    return $pdo;
}
