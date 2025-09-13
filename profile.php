<?php
session_start();
include "db.php";

// Verificar si el usuario está logueado
if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user"];
$message = "";
$messageType = "";

// Procesar actualización de perfil
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $nombre_completo = trim($_POST["nombre_completo"]);
    $email = trim($_POST["email"]);
    $direccion = trim($_POST["direccion"]);
    $telefono = trim($_POST["telefono"]);
    
    if (!empty($email)) {
        // Verificar que el email no esté en uso por otro usuario
        $check_email = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $user_id);
        $check_email->execute();
        $email_result = $check_email->get_result();
        
        if ($email_result->num_rows > 0) {
            $message = "Este email ya está en uso por otro usuario";
            $messageType = "error";
        } else {
            // Actualizar datos del usuario
            $update_stmt = $conn->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, direccion = ?, telefono = ? WHERE id = ?");
            $update_stmt->bind_param("ssssi", $nombre_completo, $email, $direccion, $telefono, $user_id);
            
            if ($update_stmt->execute()) {
                $message = "Perfil actualizado correctamente";
                $messageType = "success";
            } else {
                $message = "Error al actualizar el perfil";
                $messageType = "error";
            }
        }
    } else {
        $message = "El email es obligatorio";
        $messageType = "error";
    }
}

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT username, email, nombre_completo, direccion, telefono, rol, fecha_registro FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Obtener historial de pedidos
$pedidos_stmt = $conn->prepare("
    SELECT p.id, p.fecha_pedido, p.total, p.estado, p.metodo_pago,
           COUNT(pi.id) as total_items
    FROM pedidos p 
    LEFT JOIN pedido_items pi ON p.id = pi.pedido_id 
    WHERE p.usuario_id = ? 
    GROUP BY p.id 
    ORDER BY p.fecha_pedido DESC 
    LIMIT 10
");
$pedidos_stmt->bind_param("i", $user_id);
$pedidos_stmt->execute();
$pedidos_result = $pedidos_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - DragonTech</title>
    <link rel="stylesheet" href="styles/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="home.php" class="nav-logo">
                <i class="fas fa-dragon"></i>
                DragonTech
            </a>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="home.php" class="nav-link">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a href="carrito.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i> Carrito
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link active">
                        <i class="fas fa-user"></i> Mi Perfil
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-info">
                <h1>¡Hola, <?php echo htmlspecialchars($user_data['nombre_completo'] ?: $user_data['username']); ?>!</h1>
                <p class="profile-role">
                    <i class="fas fa-badge-check"></i>
                    <?php 
                    echo ucfirst($user_data['rol']);
                    if ($user_data['rol'] == 'admin') echo ' 👑';
                    ?>
                </p>
                <p class="profile-member-since">
                    <i class="fas fa-calendar-alt"></i>
                    Miembro desde <?php echo date('d/m/Y', strtotime($user_data['fecha_registro'])); ?>
                </p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="profile-content">
            <div class="profile-section">
                <h2>
                    <i class="fas fa-user-edit"></i>
                    Información Personal
                </h2>
                
                <form method="POST" class="profile-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">
                                <i class="fas fa-user"></i>
                                Nombre de Usuario
                            </label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" readonly>
                            <small>El nombre de usuario no se puede cambiar</small>
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                Email *
                            </label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="nombre_completo">
                                <i class="fas fa-id-card"></i>
                                Nombre Completo
                            </label>
                            <input type="text" id="nombre_completo" name="nombre_completo" value="<?php echo htmlspecialchars($user_data['nombre_completo'] ?: ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="telefono">
                                <i class="fas fa-phone"></i>
                                Teléfono
                            </label>
                            <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($user_data['telefono'] ?: ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="direccion">
                            <i class="fas fa-map-marker-alt"></i>
                            Dirección
                        </label>
                        <textarea id="direccion" name="direccion" rows="3"><?php echo htmlspecialchars($user_data['direccion'] ?: ''); ?></textarea>
                    </div>

                    <button type="submit" name="update_profile" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Actualizar Perfil
                    </button>
                </form>
            </div>

            <div class="profile-section">
                <h2>
                    <i class="fas fa-shopping-bag"></i>
                    Historial de Pedidos
                </h2>

                <?php if ($pedidos_result->num_rows > 0): ?>
                    <div class="orders-list">
                        <?php while ($pedido = $pedidos_result->fetch_assoc()): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-id">
                                        <i class="fas fa-receipt"></i>
                                        Pedido #<?php echo sprintf('%05d', $pedido['id']); ?>
                                    </div>
                                    <div class="order-status status-<?php echo strtolower($pedido['estado']); ?>">
                                        <?php 
                                        $estados = [
                                            'pendiente' => 'Pendiente',
                                            'procesando' => 'Procesando',
                                            'enviado' => 'Enviado',
                                            'entregado' => 'Entregado',
                                            'cancelado' => 'Cancelado'
                                        ];
                                        echo $estados[$pedido['estado']] ?? ucfirst($pedido['estado']);
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="order-details">
                                    <div class="order-info">
                                        <p>
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?>
                                        </p>
                                        <p>
                                            <i class="fas fa-box"></i>
                                            <?php echo $pedido['total_items']; ?> artículo(s)
                                        </p>
                                        <p>
                                            <i class="fas fa-credit-card"></i>
                                            <?php echo ucfirst($pedido['metodo_pago']); ?>
                                        </p>
                                    </div>
                                    <div class="order-total">
                                        $<?php echo number_format($pedido['total'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No tienes pedidos aún</h3>
                        <p>¡Explora nuestros productos y realiza tu primera compra!</p>
                        <a href="home.php" class="btn-primary">
                            <i class="fas fa-shopping-cart"></i>
                            Ir a Comprar
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3><i class="fas fa-dragon"></i> DragonTech</h3>
                <p>Tu tienda de confianza para laptops gaming de alta calidad.</p>
            </div>
            <div class="footer-section">
                <h4>Enlaces</h4>
                <ul>
                    <li><a href="home.php">Inicio</a></li>
                    <li><a href="carrito.php">Carrito</a></li>
                    <li><a href="profile.php">Mi Perfil</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contacto</h4>
                <p><i class="fas fa-envelope"></i> info@dragontech.com</p>
                <p><i class="fas fa-phone"></i> +52 123 456 7890</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 DragonTech. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        // Animación al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const profileContainer = document.querySelector('.profile-container');
            profileContainer.style.opacity = '0';
            profileContainer.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                profileContainer.style.transition = 'all 0.5s ease';
                profileContainer.style.opacity = '1';
                profileContainer.style.transform = 'translateY(0)';
            }, 100);
        });

        // Validación del formulario
        document.querySelector('.profile-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            
            if (!email.trim()) {
                e.preventDefault();
                alert('El email es obligatorio');
                return;
            }
            
            // Confirmación antes de actualizar
            if (!confirm('¿Estás seguro de que quieres actualizar tu perfil?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>