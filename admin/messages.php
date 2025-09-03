<?php
$pageTitle = 'Gestión de Mensajes';
require_once 'auth_check.php';
requireAuth();

$message = '';
$messageType = '';

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Token de seguridad inválido';
        $messageType = 'error';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Mensaje eliminado exitosamente';
                $messageType = 'success';
            } catch(PDOException $e) {
                error_log("Error deleting message: " . $e->getMessage());
                $message = 'Error al eliminar el mensaje';
                $messageType = 'error';
            }
        }
    }
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

try {
    // Get total count
    $totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    $totalPages = ceil($totalMessages / $perPage);
    
    // Get messages for current page
    $stmt = $pdo->prepare("SELECT * FROM messages ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$perPage, $offset]);
    $messages = $stmt->fetchAll();
} catch(PDOException $e) {
    $messages = [];
    $totalMessages = 0;
    $totalPages = 0;
    $message = 'Error al cargar los mensajes';
    $messageType = 'error';
}

include 'header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <h2>Mensajes de Contacto</h2>
    <p style="color: #6b7280; margin-bottom: 2rem;">
        Total de mensajes: <?php echo $totalMessages; ?>
    </p>
    
    <?php if (empty($messages)): ?>
        <p style="text-align: center; color: #6b7280; padding: 2rem;">
            No hay mensajes aún.
        </p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Mensaje</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($msg['name']); ?></td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" 
                               style="color: #2563eb;">
                                <?php echo htmlspecialchars($msg['email']); ?>
                            </a>
                        </td>
                        <td>
                            <div style="max-width: 300px;">
                                <p style="margin: 0; line-height: 1.4;">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </p>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 0.875rem;">
                                <?php echo date('d/m/Y', strtotime($msg['created_at'])); ?><br>
                                <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                            </div>
                        </td>
                        <td>
                            <button onclick="replyToMessage('<?php echo htmlspecialchars($msg['email']); ?>', '<?php echo htmlspecialchars($msg['name']); ?>')" 
                                    class="btn btn-small">
                                Responder
                            </button>
                            
                            <form method="POST" style="display: inline; margin-top: 0.5rem;" 
                                  onsubmit="return confirmDelete('¿Eliminar este mensaje?')">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                <button type="submit" class="btn btn-small btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="btn btn-small">« Anterior</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="btn btn-small <?php echo $i === $page ? 'btn-success' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="btn btn-small">Siguiente »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function replyToMessage(email, name) {
    const subject = encodeURIComponent(`Re: Mensaje desde el portfolio`);
    const body = encodeURIComponent(`Hola ${name},\n\nGracias por contactarme a través de mi portfolio.\n\n`);
    window.open(`mailto:${email}?subject=${subject}&body=${body}`);
}
</script>

<?php include 'footer.php'; ?>
