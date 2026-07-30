<?php
/**
 * Helper Functions
 * Campus Plant Diversity Mapper
 */

/**
 * Send JSON response
 */
function jsonResponse(mixed $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    // Restrict CORS to same-origin only — never use wildcard '*'
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = defined('APP_URL') ? parse_url(APP_URL, PHP_URL_HOST) : 'localhost';
    if ($origin && parse_url($origin, PHP_URL_HOST) === $allowed) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get JSON request body
 */
function getJsonBody(): array {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    return $data ?? [];
}

/**
 * Validate required fields
 */
function validateRequired(array $data, array $fields): ?string {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            return "Field '$field' is required";
        }
    }
    return null;
}

/**
 * Handle file upload
 */
function handlePhotoUpload(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
        ];
        return ['success' => false, 'error' => $errors[$file['error']] ?? 'Upload error'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File exceeds 10MB limit'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    
    // Strict MIME type verification using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes)) {
        return ['success' => false, 'error' => 'Invalid file content. Must be a valid image file.'];
    }
    
    // Create upload directory if it doesn't exist
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Generate unique filename
    $filename = generateUUID() . '.' . $ext;
    $filepath = UPLOAD_DIR . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Failed to save file'];
    }
    
    // Resize image if too large (max 1600px)
    resizeImage($filepath, 1600);
    
    return [
        'success' => true,
        'filename' => $filename,
        'filepath' => $filepath,
        'original_name' => $file['name'],
        'size' => $file['size'],
        'url' => UPLOAD_URL . $filename
    ];
}

/**
 * Resize image to max dimension while keeping aspect ratio
 */
function resizeImage(string $filepath, int $maxDim): void {
    $info = getimagesize($filepath);
    if (!$info) return;
    
    [$width, $height, $type] = $info;
    
    if ($width <= $maxDim && $height <= $maxDim) return;
    
    $ratio = min($maxDim / $width, $maxDim / $height);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);
    
    $src = match($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($filepath),
        IMAGETYPE_PNG => imagecreatefrompng($filepath),
        IMAGETYPE_WEBP => imagecreatefromwebp($filepath),
        default => null
    };
    
    if (!$src) return;
    
    $dst = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    match($type) {
        IMAGETYPE_JPEG => imagejpeg($dst, $filepath, 85),
        IMAGETYPE_PNG => imagepng($dst, $filepath, 8),
        IMAGETYPE_WEBP => imagewebp($dst, $filepath, 85),
        default => null
    };
    
    imagedestroy($src);
    imagedestroy($dst);
}

/**
 * Generate a short random slug for QR codes
 */
function generateSlug(int $length = 6): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $slug = '';
    for ($i = 0; $i < $length; $i++) {
        $slug .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $slug;
}

/**
 * Calculate distance between two points using Haversine formula (in meters)
 */
function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R = 6371000; // Earth's radius in meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

/**
 * Log an activity
 */
function logActivity(string $institutionId, ?string $userId, string $actionType, string $entityType, ?string $entityId, string $description): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO activity_log (id, institution_id, user_id, action_type, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([generateUUID(), $institutionId, $userId, $actionType, $entityType, $entityId, $description]);
    } catch (Exception $e) {
        // Silently fail — activity logging should not break main operations
        error_log("Activity log error: " . $e->getMessage());
    }
}

/**
 * Get pagination parameters
 */
function getPagination(): array {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    return ['page' => $page, 'limit' => $limit, 'offset' => $offset];
}

/**
 * Sanitize string input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Handle CORS preflight — supports React dev server and production origins
 */
function handleCORS(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Allowed origins: React dev server + production
    $allowedOrigins = ['http://localhost:5173', 'http://localhost:3000'];
    if (defined('APP_URL')) {
        $allowedOrigins[] = APP_URL;
    }
    if (defined('CORS_ALLOWED_ORIGINS') && is_array(CORS_ALLOWED_ORIGINS)) {
        $allowedOrigins = array_merge($allowedOrigins, CORS_ALLOWED_ORIGINS);
    }
    
    if ($origin && in_array($origin, $allowedOrigins)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, Accept');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Always handle CORS
handleCORS();

