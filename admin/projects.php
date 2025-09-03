<?php
$pageTitle = 'Gestión de Proyectos';
require_once 'auth_check.php';
requireAuth();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Token de seguridad inválido';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $result = createProject();
                break;
            case 'update':
                $result = updateProject();
                break;
            case 'delete':
                $result = deleteProject();
                break;
        }
        
        if (isset($result)) {
            $message = $result['message'];
            $messageType = $result['type'];
        }
    }
}

// Get all projects
try {
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
    $projects = $stmt->fetchAll();
} catch(PDOException $e) {
    $projects = [];
    $message = 'Error al cargar los proyectos';
    $messageType = 'error';
}

// Get project for editing
$editProject = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editProject = $stmt->fetch();
    } catch(PDOException $e) {
        $message = 'Error al cargar el proyecto';
        $messageType = 'error';
    }
}

function createProject() {
    global $pdo;
    
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $link = sanitizeInput($_POST['link'] ?? '');
    
    // Validation
    if (empty($title) || empty($description) || empty($link)) {
        return ['message' => 'Todos los campos son obligatorios', 'type' => 'error'];
    }
    
    if (!validateURL($link)) {
        return ['message' => 'La URL del proyecto no es válida', 'type' => 'error'];
    }
    
    // Handle image upload
    $imagePath = 'assets/images/default-project.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleImageUpload($_FILES['image'], 'project');
        if ($uploadResult['success']) {
            $imagePath = $uploadResult['path'];
        } else {
            return ['message' => $uploadResult['message'], 'type' => 'error'];
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO projects (title, description, link, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $link, $imagePath]);
        return ['message' => 'Proyecto creado exitosamente', 'type' => 'success'];
    } catch(PDOException $e) {
        error_log("Error creating project: " . $e->getMessage());
        return ['message' => 'Error al crear el proyecto', 'type' => 'error'];
    }
}

function updateProject() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $link = sanitizeInput($_POST['link'] ?? '');
    
    if (!$id || empty($title) || empty($description) || empty($link)) {
        return ['message' => 'Todos los campos son obligatorios', 'type' => 'error'];
    }
    
    if (!validateURL($link)) {
        return ['message' => 'La URL del proyecto no es válida', 'type' => 'error'];
    }
    
    // Get current project
    try {
        $stmt = $pdo->prepare("SELECT image FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $currentProject = $stmt->fetch();
        
        if (!$currentProject) {
            return ['message' => 'Proyecto no encontrado', 'type' => 'error'];
        }
        
        $imagePath = $currentProject['image'];
        
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleImageUpload($_FILES['image'], 'project');
            if ($uploadResult['success']) {
                // Delete old image if it's not the default
                if ($imagePath !== 'assets/images/default-project.jpg' && file_exists('../' . $imagePath)) {
                    unlink('../' . $imagePath);
                }
                $imagePath = $uploadResult['path'];
            } else {
                return ['message' => $uploadResult['message'], 'type' => 'error'];
            }
        }
        
        $stmt = $pdo->prepare("UPDATE projects SET title = ?, description = ?, link = ?, image = ? WHERE id = ?");
        $stmt->execute([$title, $description, $link, $imagePath, $id]);
        
        return ['message' => 'Proyecto actualizado exitosamente', 'type' => 'success'];
    } catch(PDOException $e) {
        error_log("Error updating project: " . $e->getMessage());
        return ['message' => 'Error al actualizar el proyecto', 'type' => 'error'];
    }
}

function deleteProject() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        return ['message' => 'ID de proyecto inválido', 'type' => 'error'];
    }
    
    try {
        // Get project image to delete
        $stmt = $pdo->prepare("SELECT image FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        
        if ($project) {
            // Delete project from database
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            
            // Delete image file if it's not the default
            if ($project['image'] !== 'assets/images/default-project.jpg' && file_exists('../' . $project['image'])) {
                unlink('../' . $project['image']);
            }
            
            return ['message' => 'Proyecto eliminado exitosamente', 'type' => 'success'];
        } else {
            return ['message' => 'Proyecto no encontrado', 'type' => 'error'];
        }
    } catch(PDOException $e) {
        error_log("Error deleting project: " . $e->getMessage());
        return ['message' => 'Error al eliminar el proyecto', 'type' => 'error'];
    }
}

function handleImageUpload($file, $type) {
    $uploadDir = '../assets/images/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Validate file type
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido. Solo JPG, PNG, GIF y WebP'];
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $type . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
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
    <h2><?php echo $editProject ? 'Editar Proyecto' : 'Nuevo Proyecto'; ?></h2>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="<?php echo $editProject ? 'update' : 'create'; ?>">
        <?php if ($editProject): ?>
            <input type="hidden" name="id" value="<?php echo $editProject['id']; ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="title">Título del Proyecto *</label>
            <input type="text" 
                   id="title" 
                   name="title" 
                   required 
                   maxlength="100"
                   value="<?php echo htmlspecialchars($editProject['title'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="description">Descripción *</label>
            <textarea id="description" 
                      name="description" 
                      required 
                      rows="4"><?php echo htmlspecialchars($editProject['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="link">URL del Proyecto *</label>
            <input type="url" 
                   id="link" 
                   name="link" 
                   required 
                   placeholder="https://ejemplo.com"
                   value="<?php echo htmlspecialchars($editProject['link'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="image">Imagen del Proyecto</label>
            <input type="file" 
                   id="image" 
                   name="image" 
                   accept="image/*"
                   onchange="previewImage(this, 'imagePreview')">
            <small style="color: #6b7280; display: block; margin-top: 0.25rem;">
                Formatos permitidos: JPG, PNG, GIF, WebP. Máximo 5MB.
            </small>
            
            <?php if ($editProject && $editProject['image']): ?>
                <div style="margin-top: 1rem;">
                    <p>Imagen actual:</p>
                    <img src="../<?php echo htmlspecialchars($editProject['image']); ?>" 
                         alt="Imagen actual" 
                         style="max-width: 200px; height: auto; border-radius: 0.5rem;">
                </div>
            <?php endif; ?>
            
            <img id="imagePreview" 
                 style="display: none; max-width: 200px; height: auto; margin-top: 1rem; border-radius: 0.5rem;">
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-success">
                <?php echo $editProject ? 'Actualizar Proyecto' : 'Crear Proyecto'; ?>
            </button>
            
            <?php if ($editProject): ?>
                <a href="projects.php" class="btn">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-card">
    <h2>Proyectos Existentes</h2>
    
    <?php if (empty($projects)): ?>
        <p style="text-align: center; color: #6b7280; padding: 2rem;">
            No hay proyectos creados aún.
        </p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>URL</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                <tr>
                    <td>
                        <img src="../<?php echo htmlspecialchars($project['image']); ?>" 
                             alt="<?php echo htmlspecialchars($project['title']); ?>"
                             style="width: 60px; height: 40px; object-fit: cover; border-radius: 0.25rem;">
                    </td>
                    <td><?php echo htmlspecialchars($project['title']); ?></td>
                    <td><?php echo htmlspecialchars(substr($project['description'], 0, 50)) . '...'; ?></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($project['link']); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           style="color: #2563eb;">Ver</a>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($project['created_at'])); ?></td>
                    <td>
                        <a href="?edit=<?php echo $project['id']; ?>" class="btn btn-small">Editar</a>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete('¿Eliminar este proyecto?')">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
                            <button type="submit" class="btn btn-small btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
