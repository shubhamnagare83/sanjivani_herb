<?php
/**
 * Authentication Middleware
 * Handles session-based auth for web + token-based for API/PWA
 */

require_once __DIR__ . '/../config/database.php';

session_start();

/**
 * Hash a password
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verify password against hash
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Register a new user
 */
function registerUser(string $email, string $password, string $fullName, string $institutionId, string $role = 'contributor'): array {
    $db = getDB();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already registered'];
    }
    
    $id = generateUUID();
    $hash = hashPassword($password);
    
    $stmt = $db->prepare("INSERT INTO users (id, institution_id, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $institutionId, $email, $hash, $fullName, $role]);
    
    return ['success' => true, 'user_id' => $id];
}

/**
 * Authenticate user by email/password
 */
function loginUser(string $email, string $password): array {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT u.*, i.name AS institution_name, i.slug AS institution_slug FROM users u JOIN institutions i ON u.institution_id = i.id WHERE u.email = ? AND u.is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !verifyPassword($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    // Update last login
    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
    
    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['institution_id'] = $user['institution_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['institution_name'] = $user['institution_name'];
    
    // Generate API token for PWA/mobile use
    $token = generateToken($user['id']);
    
    unset($user['password_hash']);
    
    return ['success' => true, 'user' => $user, 'token' => $token];
}

/**
 * Generate a simple JWT-like token
 */
function generateToken(string $userId): string {
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64_encode(json_encode([
        'user_id' => $userId,
        'iat' => time(),
        'exp' => time() + SESSION_LIFETIME
    ]));
    $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET);
    return "$header.$payload.$signature";
}

/**
 * Verify and decode token
 */
function verifyToken(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    
    [$header, $payload, $signature] = $parts;
    
    $expectedSig = hash_hmac('sha256', "$header.$payload", JWT_SECRET);
    if (!hash_equals($expectedSig, $signature)) return null;
    
    $data = json_decode(base64_decode($payload), true);
    if (!$data || $data['exp'] < time()) return null;
    
    return $data;
}

/**
 * Get the current authenticated user (from session or token)
 */
function getCurrentUser(): ?array {
    // Check session first
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'institution_id' => $_SESSION['institution_id'],
            'role' => $_SESSION['role'],
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email']
        ];
    }
    
    // Check Authorization header (for API/PWA)
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $tokenData = verifyToken($matches[1]);
        if ($tokenData) {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, institution_id, role, full_name, email FROM users WHERE id = ? AND is_active = 1");
            $stmt->execute([$tokenData['user_id']]);
            return $stmt->fetch() ?: null;
        }
    }
    
    return null;
}

/**
 * Require authentication — redirect or return 401
 */
function requireAuth(bool $isApi = false): array {
    $user = getCurrentUser();
    if (!$user) {
        if ($isApi) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        } else {
            header('Location: ' . APP_URL . '/pages/login.php');
            exit;
        }
    }
    return $user;
}

/**
 * Require specific role
 */
function requireRole(string $role, bool $isApi = false): array {
    $user = requireAuth($isApi);
    $roleHierarchy = ['contributor' => 1, 'verifier' => 2, 'admin' => 3];
    
    if (($roleHierarchy[$user['role']] ?? 0) < ($roleHierarchy[$role] ?? 0)) {
        if ($isApi) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit;
        } else {
            header('Location: ' . APP_URL . '/pages/dashboard.php?error=forbidden');
            exit;
        }
    }
    return $user;
}

/**
 * Logout
 */
function logoutUser(): void {
    session_destroy();
    $_SESSION = [];
}

/**
 * Generate UUID v4
 */
function generateUUID(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
