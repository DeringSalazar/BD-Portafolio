<?php
require_once 'config/db.php';

// Fetch profile information
try {
    $profileStmt = $pdo->query("SELECT * FROM profile LIMIT 1");
    $profile = $profileStmt->fetch();
} catch(PDOException $e) {
    $profile = [
        'name' => 'Dering Esteban Salazar',
        'description' => 'Desarrollador Web',
        'photo' => 'assets/images/profile.jpg'
    ];
}

// Fetch projects
try {
    $projectsStmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
    $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log para depuración
    error_log("Número de proyectos recuperados: " . count($projects));
} catch(PDOException $e) {
    error_log("Error al cargar proyectos: " . $e->getMessage());
    $projects = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile['name']); ?> - Portfolio</title>
    <meta name="description" content="Portfolio personal de <?php echo htmlspecialchars($profile['name']); ?> - Desarrollador Full Stack">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header Section -->
    <header class="header" id="inicio">
        <div class="container">
            <img src="<?php echo htmlspecialchars($profile['photo']); ?>" 
                 alt="<?php echo htmlspecialchars($profile['name']); ?>" 
                 class="profile-img"
                 onerror="this.src='assets/images/default-profile.jpg'">
            <h1><?php echo htmlspecialchars($profile['name']); ?></h1>
            <p><?php echo htmlspecialchars($profile['description']); ?></p>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="nav">
        <div class="container">
            <div class="nav-container">
                <a href="#inicio">Inicio</a>
                <a href="#proyectos">Proyectos</a>
                <a href="#contacto">Contacto</a>
            </div>
        </div>
    </nav>

    <!-- Projects Section -->
    <section class="section" id="proyectos">
        <div class="container">
            <h2 class="section-title">Mis Proyectos</h2>
            
            <?php if (empty($projects)): ?>
                <div style="text-align: center; padding: 2rem; color: #6b7280;">
                    <p>No hay proyectos disponibles en este momento.</p>
                </div>
            <?php else: ?>
                <!-- Contador de proyectos para depuración -->
                <div class="debug-info" style="display: none;">
                    Proyectos cargados: <?php echo count($projects); ?>
                </div>
                
                <div class="projects-grid">
                    <?php foreach ($projects as $project): ?>
                        <div class="project-card">
                            <?php if (!empty($project['image'])): ?>
                                <img src="<?php echo htmlspecialchars($project['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($project['title']); ?>" 
                                     class="project-img"
                                     onerror="this.onerror=null; this.src='assets/images/default-project.jpg';">
                            <?php else: ?>
                                <img src="assets/images/default-project.jpg" 
                                     alt="<?php echo htmlspecialchars($project['title']); ?>" 
                                     class="project-img">
                            <?php endif; ?>
                            
                            <div class="project-content">
                                <h3 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h3>
                                <p class="project-description"><?php echo htmlspecialchars($project['description']); ?></p>
                                <?php if (!empty($project['link'])): ?>
                                    <a href="<?php echo htmlspecialchars($project['link']); ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer" 
                                       class="project-link">Ver Proyecto</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section" id="contacto">
        <div class="container">
            <h2 class="section-title">Contacto</h2>
            
            <form class="contact-form" id="contactForm" method="POST" action="contact.php">
                <!-- Added honeypot field for spam protection -->
                <input type="text" name="website" style="display: none;" tabindex="-1" autocomplete="off">
                
                <div class="form-group">
                    <label for="name">Nombre *</label>
                    <input type="text" id="name" name="name" required minlength="2" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="message">Mensaje *</label>
                    <textarea id="message" name="message" required minlength="10" maxlength="1000" placeholder="Escribe tu mensaje aquí..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-full">Enviar Mensaje</button>
                
                <p style="font-size: 0.875rem; color: #6b7280; margin-top: 1rem; text-align: center;">
                    * Campos obligatorios. Tu información será tratada de forma confidencial.
                </p>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($profile['name']); ?>. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
</body>
</html>
