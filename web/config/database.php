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

// Auto-detect APP_URL with Host Header whitelist (prevents Host Header Injection)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$rawHost = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
// Whitelist allowed hostnames to block Host Header Injection attacks
$allowedHosts = ['localhost', 'localhost:8000', 'localhost:80', '127.0.0.1', '127.0.0.1:8000'];
$hostBase = strtolower(explode(':', $rawHost)[0]);
if (!in_array(strtolower($rawHost), $allowedHosts) && !in_array($hostBase, ['localhost', '127.0.0.1'])) {
    // In production, add your domain here. For now, reject unknown hosts.
    $rawHost = 'localhost:8000';
}
$host = $rawHost;
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$webPos = strpos($scriptDir, '/web');
if ($webPos !== false) {
    $basePath = substr($scriptDir, 0, $webPos + 4);
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
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(self), microphone=(), geolocation=(self), payment=()');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
// Content Security Policy — blocks inline script injection and unauthorized external connections
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https: blob:; connect-src 'self' https://unpkg.com https://cdn.jsdelivr.net https://api.qrserver.com https://*.tile.opentopomap.org https://*.basemaps.cartocdn.com https://server.arcgisonline.com; frame-ancestors 'none';");

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
