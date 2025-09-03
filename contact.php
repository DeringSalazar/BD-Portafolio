<?php
require 'vendor/autoload.php';
require_once 'config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Set JSON response header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Rate limiting - max 3 messages per IP per hour
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitResult = checkRateLimit($clientIP);
if (!$rateLimitResult['allowed']) {
    http_response_code(429);
    echo json_encode([
        'success' => false, 
        'message' => 'Demasiados mensajes enviados. Intenta de nuevo en ' . $rateLimitResult['wait_time'] . ' minutos.'
    ]);
    exit;
}

// Honeypot spam protection
if (!empty($_POST['website'])) {
    // This field should be empty (hidden from users, filled by bots)
    echo json_encode(['success' => false, 'message' => 'Mensaje detectado como spam']);
    exit;
}

// Validate and sanitize input
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

// Enhanced validation
$errors = validateContactForm($name, $email, $message);

if (!empty($errors)) {
    echo json_encode([
        'success' => false, 
        'message' => implode(', ', $errors)
    ]);
    exit;
}

// Save message to database
try {
    $stmt = $pdo->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $message]);
    
    // Update rate limiting
    updateRateLimit($clientIP);
    
    // Send email notification if configured
    $emailSent = sendEmailNotification($name, $email, $message);
    
    // Log successful contact
    error_log("Contact form submission: {$name} ({$email})");
    
    $response = [
        'success' => true,
        'message' => 'Mensaje enviado correctamente'
    ];
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    error_log("Contact form database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor. Inténtalo más tarde.'
    ]);
}

function validateContactForm($name, $email, $message) {
    $errors = [];
    
    // Name validation
    if (empty($name)) {
        $errors[] = 'El nombre es obligatorio';
    } elseif (strlen($name) < 2) {
        $errors[] = 'El nombre debe tener al menos 2 caracteres';
    } elseif (strlen($name) > 100) {
        $errors[] = 'El nombre no puede exceder 100 caracteres';
    } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $name)) {
        $errors[] = 'El nombre solo puede contener letras y espacios';
    }
    
    // Email validation
    if (empty($email)) {
        $errors[] = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido';
    } elseif (strlen($email) > 100) {
        $errors[] = 'El email no puede exceder 100 caracteres';
    }
    
    // Message validation
    if (empty($message)) {
        $errors[] = 'El mensaje es obligatorio';
    } elseif (strlen($message) < 10) {
        $errors[] = 'El mensaje debe tener al menos 10 caracteres';
    } elseif (strlen($message) > 1000) {
        $errors[] = 'El mensaje no puede exceder 1000 caracteres';
    }
    
    // Check for spam patterns
    $spamPatterns = [
        '/\b(viagra|casino|lottery|winner|congratulations)\b/i',
        '/\b(click here|visit now|act now|limited time)\b/i',
        '/(http:\/\/|https:\/\/|www\.)/i' // URLs in message
    ];
    
    foreach ($spamPatterns as $pattern) {
        if (preg_match($pattern, $message)) {
            $errors[] = 'El mensaje contiene contenido no permitido';
            break;
        }
    }
    
    return $errors;
}

function checkRateLimit($ip) {
    global $pdo;
    
    try {
        // Clean old entries (older than 1 hour)
        $pdo->prepare("DELETE FROM rate_limit WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)")->execute();
        
        // Check current IP submissions in the last hour
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rate_limit WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$ip]);
        $count = $stmt->fetchColumn();
        
        if ($count >= 3) {
            // Get time until next allowed submission
            $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(MIN(created_at), INTERVAL 1 HOUR)) as wait_time FROM rate_limit WHERE ip_address = ?");
            $stmt->execute([$ip]);
            $waitTime = $stmt->fetchColumn() ?: 60;
            
            return ['allowed' => false, 'wait_time' => max(1, $waitTime)];
        }
        
        return ['allowed' => true];
        
    } catch(PDOException $e) {
        error_log("Rate limit check error: " . $e->getMessage());
        return ['allowed' => true]; // Allow on error to not block legitimate users
    }
}

function updateRateLimit($ip) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO rate_limit (ip_address) VALUES (?)");
        $stmt->execute([$ip]);
    } catch(PDOException $e) {
        error_log("Rate limit update error: " . $e->getMessage());
    }
}

function sendEmailNotification($name, $email, $message) {
    $config = require_once 'config/email.php';
    
    if (!$config['enabled']) {
        return false;
    }

    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host = $config['smtp']['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp']['username'];
        $mail->Password = $config['smtp']['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp']['port'];
        
        // Recipients
        $mail->setFrom($config['from_email'], 'Formulario de Contacto');
        $mail->addAddress($config['admin_email']);
        $mail->addReplyTo($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Nuevo mensaje de contacto de ' . $name;
        $mail->Body = "Has recibido un nuevo mensaje:<br><br>" .
                     "<strong>Nombre:</strong> " . $name . "<br>" .
                     "<strong>Email:</strong> " . $email . "<br>" .
                     "<strong>Mensaje:</strong><br>" . nl2br($message);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar email: " . $mail->ErrorInfo);
        return false;
    }
}
?>
