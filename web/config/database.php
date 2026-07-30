<?php
/**
 * Database Configuration
 * Campus Plant Diversity Mapper
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'plant_mapper');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Campus Plant Diversity Mapper');
define('APP_URL', 'http://localhost/sanjivani_herb/web');
define('APP_VERSION', '1.0.0');
define('UPLOAD_DIR', __DIR__ . '/../uploads/plant-photos/');
define('UPLOAD_URL', APP_URL . '/uploads/plant-photos/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// AI Service
define('PLANTNET_API_KEY', ''); // Add your Pl@ntNet API key here
define('PLANTNET_API_URL', 'https://my-api.plantnet.org/v2/identify/all');

// Session
define('SESSION_LIFETIME', 86400 * 7); // 7 days
define('JWT_SECRET', 'sanjivani_herb_jwt_secret_key_2026');

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
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}
