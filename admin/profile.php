<?php
$pageTitle = 'Gestión de Perfil';
require_once 'auth_check.php';
requireAuth();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Token de seguridad inválido';
        $messageType = 'error';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if (empty($name) || empty($description)) {
            $message = 'Todos los campos son obligatorios';
            $messageType = 'error';
        } else {
            try {
                // Get current profile
                $stmt = $pdo->query("SELECT * FROM profile LIMIT 1");
                $currentProfile = $stmt->fetch();
                
                $photoPath = $currentProfile['photo'] ?? 'assets/images/default-profile.jpg';
                
                // Handle photo upload
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = handleImageUpload($_FILES['photo'], 'profile');
                    if ($uploadResult['success']) {
                        // Delete old photo if it's not the default
                        if ($photoPath !== 'assets/images/default-profile.jpg' && file_exists('../' . $photoPath)) {
                            unlink('../' . $photoPath);
                        }
                        $photoPath = $uploadResult['path'];
                    } else {
                        $message = $uploadResult['message'];
                        $messageType = 'error';
                    }
                }
                
                if ($messageType !== 'error') {
                    if ($currentProfile) {
                        // Update existing profile
                        $stmt = $pdo->prepare("UPDATE profile SET name = ?, description = ?, photo = ? WHERE id = ?");
                        $stmt->execute([$name, $description, $photoPath, $currentProfile['id']]);
                    } else {
                        // Create new profile
                        $stmt = $pdo->prepare("INSERT INTO profile (name, description, photo) VALUES (?, ?, ?)");
                        $stmt->execute([$name, $description, $photoPath]);
                    }
                    
                    $message = 'Perfil actualizado exitosamente';
                    $messageType = 'success';
                }
            } catch(PDOException $e) {
                error_log("Error updating profile: " . $e->getMessage());
                $message = 'Error al actualizar el perfil';
                $messageType = 'error';
            }
        }
    }
}

// Get current profile
try {
    $stmt = $pdo->query("SELECT * FROM profile LIMIT 1");
    $profile = $stmt->fetch();
} catch(PDOException $e) {
    $profile = [
        'name' => '',
        'description' => '',
        'photo' => 'assets/images/default-profile.jpg'
    ];
}

function handleImageUpload($file, $type) {
    $uploadDir = '../assets/images/';
    $allowedTypes = ['image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido. Solo JPG, PNG, GIF y WebP'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $type . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => 'assets/images/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Error al subir el archivo'];
    }
}

include 'header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <h2>Editar Perfil Personal</h2>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-group">
            <label for="name">Nombre Completo *</label>
            <input type="text" 
                   id="name" 
                   name="name" 
                   required 
                   maxlength="100"
                   value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="description">Descripción Profesional *</label>
            <textarea id="description" 
                      name="description" 
                      required 
                      rows="4"
                      placeholder="Ej: Desarrollador Full Stack apasionado por crear soluciones innovadoras..."><?php echo htmlspecialchars($profile['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="photo">Foto de Perfil</label>
            <input type="file" 
                   id="photo" 
                   name="photo" 
                   accept="image/*"
                   onchange="previewImage(this, 'photoPreview')">
            <small style="color: #6b7280; display: block; margin-top: 0.25rem;">
                Formatos permitidos: JPG, PNG, GIF, WebP. Máximo 5MB. Recomendado: imagen cuadrada.
            </small>
            
            <div style="margin-top: 1rem;">
                <p>Foto actual:</p>
                <img src="../<?php echo htmlspecialchars($profile['photo'] ?? 'assets/images/default-profile.png'); ?>" 
                     alt="Foto actual" 
                     id="currentPhoto"
                     style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #e5e7eb;">
            </div>
            
            <img id="photoPreview" 
                 style="display: none; width: 150px; height: 150px; object-fit: cover; border-radius: 50%; margin-top: 1rem; border: 3px solid #2563eb;">
        </div>
        
        <button type="submit" class="btn btn-success">Actualizar Perfil</button>
    </form>
</div>

<div class="admin-card">
    <h3>Vista Previa del Portfolio</h3>
    <p style="color: #6b7280; margin-bottom: 1rem;">
        Así se verá tu perfil en la página principal:
    </p>
    
    <div style="background: linear-gradient(135deg, #2563eb, #1e40af); color: white; padding: 2rem; border-radius: 1rem; text-align: center;">
        <img src="../<?php echo htmlspecialchars($profile['photo'] ?? 'assets/images/default-profile.png'); ?>" 
             alt="<?php echo htmlspecialchars($profile['name'] ?? 'Tu Nombre'); ?>" 
             style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid white; margin-bottom: 1rem;">
        <h2 style="margin: 0 0 0.5rem 0; font-size: 2rem;"><?php echo htmlspecialchars($profile['name'] ?? 'Tu Nombre'); ?></h2>
        <p style="margin: 0; font-size: 1.1rem; opacity: 0.9;"><?php echo htmlspecialchars($profile['description'] ?? 'Tu descripción profesional'); ?></p>
    </div>
    
    <div style="text-align: center; margin-top: 1rem;">
        <a href="../index.php" target="_blank" class="btn">Ver Portfolio Completo</a>
    </div>
</div>

<?php include 'footer.php'; ?>
