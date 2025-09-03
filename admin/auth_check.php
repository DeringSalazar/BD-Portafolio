<?php
// Enhanced authentication and security checks
require_once '../config/security.php';

// Session configuration for security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();
require_once '../config/db.php';

// Function to check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Function to require authentication
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
    
    // Check session timeout (24 hours)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 86400) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    
    // Check for session hijacking
    if (!validateSession()) {
        session_destroy();
        header('Location: login.php?security=1');
        exit;
    }
}

// Enhanced session validation
function validateSession() {
    // Check user agent consistency
    if (isset($_SESSION['user_agent'])) {
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            return false;
        }
    } else {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
    
    // Check IP consistency (optional - can cause issues with dynamic IPs)
    /*
    if (isset($_SESSION['ip_address'])) {
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            return false;
        }
    } else {
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    */
    
    return true;
}

// Function to get current admin user
function getCurrentAdmin() {
    global $pdo;
    
    if (!isAuthenticated()) {
        return null;
    }
    
    try {
        $stmt = executeSecureQuery($pdo, "SELECT id, username FROM users WHERE id = ?", [$_SESSION['admin_id']]);
        return $stmt->fetch();
    } catch(Exception $e) {
        error_log("Error fetching admin user: " . $e->getMessage());
        return null;
    }
}

// Enhanced CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > 3600) { // Regenerate every hour
        $_SESSION['csrf_token'] = SecurityConfig::generateSecureToken();
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check token age (max 1 hour)
    if ((time() - $_SESSION['csrf_token_time']) > 3600) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Enhanced sanitization helpers
function sanitizeInput($input, $type = 'string') {
    if (is_array($input)) {
        return array_map(function($item) use ($type) {
            return sanitizeInput($item, $type);
        }, $input);
    }
    
    $input = trim($input);
    
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'string':
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function validateInteger($value, $min = null, $max = null) {
    $int = filter_var($value, FILTER_VALIDATE_INT);
    
    if ($int === false) {
        return false;
    }
    
    if ($min !== null && $int < $min) {
        return false;
    }
    
    if ($max !== null && $int > $max) {
        return false;
    }
    
    return $int;
}

// Secure file upload handling
function handleSecureFileUpload($file, $uploadDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']) {
    // Validate file
    $validation = SecurityConfig::validateFileUpload($file, $allowedTypes);
    
    if (!$validation['valid']) {
        return ['success' => false, 'message' => implode(', ', $validation['errors'])];
    }
    
    // Generate secure filename
    $extension = $validation['extension'];
    $filename = uniqid('upload_', true) . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'message' => 'No se pudo crear el directorio de subida'];
        }
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Verify it's actually an image
        if (!SecurityConfig::isValidImageFile($filepath)) {
            unlink($filepath);
            return ['success' => false, 'message' => 'El archivo no es una imagen válida'];
        }
        
        // Set proper permissions
        chmod($filepath, 0644);
        
        return ['success' => true, 'path' => str_replace('../', '', $filepath)];
    } else {
        return ['success' => false, 'message' => 'Error al subir el archivo'];
    }
}

// Logging functions
function logSecurityEvent($event, $details = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => $_SESSION['admin_id'] ?? null,
        'details' => $details
    ];
    
    error_log("SECURITY: " . json_encode($logEntry));
}

function logAdminAction($action, $details = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'user_id' => $_SESSION['admin_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'details' => $details
    ];
    
    error_log("ADMIN: " . json_encode($logEntry));
}

// Rate limiting for admin actions
function checkAdminRateLimit($action, $maxAttempts = 10, $timeWindow = 3600) {
    $key = 'admin_rate_limit_' . $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $rateLimit = $_SESSION[$key];
    
    // Reset if time window has passed
    if ((time() - $rateLimit['first_attempt']) > $timeWindow) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }
    
    // Check if limit exceeded
    if ($rateLimit['count'] >= $maxAttempts) {
        return false;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    return true;
}
?>
