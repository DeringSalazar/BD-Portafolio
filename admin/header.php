<?php
// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Check session timeout (24 hours)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 86400) {
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?> - Portfolio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Panel de Administración</h1>
            <div>
                <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" style="color: white; margin-left: 1rem; text-decoration: none;">Cerrar Sesión</a>
            </div>
        </div>
        
        <nav class="admin-nav">
            <a href="dashboard.php" <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'class="active"' : ''; ?>>
                Dashboard
            </a>
            <a href="projects.php" <?php echo (basename($_SERVER['PHP_SELF']) === 'projects.php') ? 'class="active"' : ''; ?>>
                Proyectos
            </a>
            <a href="messages.php" <?php echo (basename($_SERVER['PHP_SELF']) === 'messages.php') ? 'class="active"' : ''; ?>>
                Mensajes
            </a>
            <a href="profile.php" <?php echo (basename($_SERVER['PHP_SELF']) === 'profile.php') ? 'class="active"' : ''; ?>>
                Perfil
            </a>
            <a href="../index.php" target="_blank" style="margin-left: auto;">
                Ver Portfolio
            </a>
        </nav>
