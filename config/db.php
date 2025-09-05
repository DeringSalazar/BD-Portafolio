<?php
// Database configuration with enhanced security
define('DB_HOST', 'localhost');
define('DB_NAME', 'portfolio');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', 'Admin1234');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
        PDO::ATTR_STRINGIFY_FETCHES => false, // Keep data types
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'"
    ]);
} catch(PDOException $e) {
    // Log error securely without exposing sensitive information
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show generic error to user
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        die("Error de conexión a la base de datos. Contacta al administrador.");
    }
}

// Database security functions
function executeSecureQuery($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Database query error: " . $e->getMessage() . " Query: " . $query);
        throw new Exception("Error en la consulta a la base de datos");
    }
}

function sanitizeForDatabase($input) {
    if (is_array($input)) {
        return array_map('sanitizeForDatabase', $input);
    }
    
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
