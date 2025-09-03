<?php
$pageTitle = 'Dashboard';
require_once 'auth_check.php';
requireAuth();

// Get statistics
try {
    $projectsCount = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $messagesCount = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    $recentMessages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch(PDOException $e) {
    $projectsCount = 0;
    $messagesCount = 0;
    $recentMessages = [];
}

include 'header.php';
?>

<div class="admin-card">
    <h2>Resumen del Portfolio</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0;">
        <div style="background: #f3f4f6; padding: 1.5rem; border-radius: 0.5rem; text-align: center;">
            <h3 style="font-size: 2rem; color: #2563eb; margin-bottom: 0.5rem;"><?php echo $projectsCount; ?></h3>
            <p style="color: #6b7280;">Proyectos Publicados</p>
        </div>
        
        <div style="background: #f3f4f6; padding: 1.5rem; border-radius: 0.5rem; text-align: center;">
            <h3 style="font-size: 2rem; color: #16a34a; margin-bottom: 0.5rem;"><?php echo $messagesCount; ?></h3>
            <p style="color: #6b7280;">Mensajes Recibidos</p>
        </div>
    </div>
</div>

<?php if (!empty($recentMessages)): ?>
<div class="admin-card">
    <h3>Mensajes Recientes</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Mensaje</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentMessages as $message): ?>
            <tr>
                <td><?php echo htmlspecialchars($message['name']); ?></td>
                <td><?php echo htmlspecialchars($message['email']); ?></td>
                <td><?php echo htmlspecialchars(substr($message['message'], 0, 50)) . '...'; ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="text-align: center; margin-top: 1rem;">
        <a href="messages.php" class="btn">Ver Todos los Mensajes</a>
    </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
