<?php
// Script to create admin user with secure password
require_once '../config/db.php';
require_once '../config/security.php';

// Only run from command line for security
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

echo "=== Portfolio Admin User Creation ===\n";

// Get username
echo "Enter admin username: ";
$username = trim(fgets(STDIN));

if (empty($username) || strlen($username) < 3) {
    die("Username must be at least 3 characters long\n");
}

// Get password
echo "Enter admin password (min 8 characters): ";
$password = trim(fgets(STDIN));

if (strlen($password) < 8) {
    die("Password must be at least 8 characters long\n");
}

// Confirm password
echo "Confirm password: ";
$confirmPassword = trim(fgets(STDIN));

if ($password !== $confirmPassword) {
    die("Passwords do not match\n");
}

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        die("User already exists\n");
    }
    
    // Create user with secure password hash
    $passwordHash = SecurityConfig::hashPassword($password);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$username, $passwordHash]);
    
    echo "Admin user created successfully!\n";
    echo "Username: $username\n";
    echo "You can now login at /admin/login.php\n";
    
} catch(PDOException $e) {
    error_log("Error creating admin user: " . $e->getMessage());
    die("Error creating user. Check logs for details.\n");
}
?>
