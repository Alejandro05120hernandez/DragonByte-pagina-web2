<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

// Verificar que el usuario esté logueado
requireLogin();

// Obtener número de pedido
$numero_pedido = $_GET['numero'] ?? '';
if (empty($numero_pedido)) {
    header("Location: home.php");
    exit;
}

// Obtener detalles del pedido
$stmt = $conn->prepare("SELECT p.*, u.username FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id WHERE p.numero_pedido = ? AND p.usuario_id = ?");
$stmt->bind_param("si", $numero_pedido, $_SESSION['user_id']);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    header("Location: home.php");
    exit;
}

// Obtener items del pedido
$stmt = $conn->prepare("SELECT pi.*, l.nombre, l.imagen, l.marca FROM pedido_items pi JOIN laptops l ON pi.laptop_id = l.id WHERE pi.pedido_id = ?");
$stmt->bind_param("i", $pedido['id']);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Determinar tiempo estimado de entrega
$metodo_info = [
    'tarjeta_credito' => ['nombre' => 'Tarjeta de Crédito/Débito', 'icono' => 'fas fa-credit-card', 'tiempo' => '2-3 días hábiles'],
    'paypal' => ['nombre' => 'PayPal', 'icono' => 'fab fa-paypal', 'tiempo' => '2-3 días hábiles'],
    'transferencia' => ['nombre' => 'Transferencia Bancaria', 'icono' => 'fas fa-university', 'tiempo' => '3-5 días hábiles'],
    'oxxo' => ['nombre' => 'OXXO Pay', 'icono' => 'fas fa-store', 'tiempo' => '24-48 horas para confirmación']
];

$metodo_actual = $metodo_info[$pedido['metodo_pago']] ?? ['nombre' => $pedido['metodo_pago'], 'icono' => 'fas fa-credit-card', 'tiempo' => '2-3 días hábiles'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - DragonTech</title>
    <link rel="stylesheet" href="styles/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <i class="fas fa-dragon"></i>
            <span>DragonTech</span>
        </div>
        <nav class="nav">
            <a href="home.php"><i class="fas fa-home"></i> Inicio</a>
            <a href="laptops.php"><i class="fas fa-laptop"></i> Catálogo</a>
            <a href="carrito.php"><i class="fas fa-shopping-cart"></i> Carrito</a>
        </nav>
    </header>

    <div class="container">
        <div class="confirmation-container">
            <!-- Header de Confirmación -->
            <div class="confirmation-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>¡PEDIDO CONFIRMADO!</h1>
                <p>Tu pedido ha sido procesado exitosamente</p>
                <div class="order-number">
                    <strong>Número de Pedido: <?php echo htmlspecialchars($pedido['numero_pedido']); ?></strong>
                </div>
            </div>

            <div class="confirmation-content">
                <!-- Detalles del Pedido -->
                <div class="order-details">
                    <h3><i class="fas fa-receipt"></i> Detalles del Pedido</h3>
                    
                    <div class="order-info">
                        <div class="info-row">
                            <span class="label">Fecha del Pedido:</span>
                            <span class="value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Estado:</span>
                            <span class="value status-<?php echo $pedido['estado_pedido']; ?>">
                                <?php echo ucfirst($pedido['estado_pedido']); ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="label">Método de Pago:</span>
                            <span class="value">
                                <i class="<?php echo $metodo_actual['icono']; ?>"></i>
                                <?php echo $metodo_actual['nombre']; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="label">Total Pagado:</span>
                            <span class="value total-amount">$<?php echo number_format($pedido['total'], 2); ?></span>
                        </div>
                    </div>

                    <!-- Items del Pedido -->
                    <div class="order-items">
                        <h4><i class="fas fa-box"></i> Productos Ordenados</h4>
                        <?php foreach ($items as $item): ?>
                            <div class="order-item">
                                <img src="<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                <div class="item-info">
                                    <h5><?php echo htmlspecialchars($item['nombre']); ?></h5>
                                    <p class="item-brand"><?php echo htmlspecialchars($item['marca']); ?></p>
                                    <p class="item-price">
                                        Cantidad: <?php echo $item['cantidad']; ?> × $<?php echo number_format($item['precio_unitario'], 2); ?>
                                        = <strong>$<?php echo number_format($item['subtotal'], 2); ?></strong>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Resumen de Costos -->
                    <div class="cost-summary">
                        <div class="cost-line">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($pedido['subtotal'], 2); ?></span>
                        </div>
                        <div class="cost-line">
                            <span>IVA (16%):</span>
                            <span>$<?php echo number_format($pedido['impuestos'], 2); ?></span>
                        </div>
                        <div class="cost-line">
                            <span>Envío:</span>
                            <span><?php echo $pedido['envio'] > 0 ? '$' . number_format($pedido['envio'], 2) : 'GRATIS'; ?></span>
                        </div>
                        <div class="cost-line total-line">
                            <span><strong>Total:</strong></span>
                            <span><strong>$<?php echo number_format($pedido['total'], 2); ?></strong></span>
                        </div>
                    </div>
                </div>

                <!-- Información de Envío -->
                <div class="shipping-info">
                    <h3><i class="fas fa-truck"></i> Información de Envío</h3>
                    
                    <div class="shipping-address">
                        <h4>Dirección de Entrega:</h4>
                        <p><strong><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></strong></p>
                        <p><?php echo htmlspecialchars($pedido['direccion']); ?></p>
                        <p><?php echo htmlspecialchars($pedido['ciudad']); ?>, <?php echo htmlspecialchars($pedido['estado']); ?> <?php echo htmlspecialchars($pedido['codigo_postal']); ?></p>
                        <p><?php echo htmlspecialchars($pedido['pais']); ?></p>
                        <?php if ($pedido['telefono_cliente']): ?>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($pedido['telefono_cliente']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="delivery-estimate">
                        <div class="estimate-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>Tiempo Estimado de Entrega</h4>
                                <p><?php echo $metodo_actual['tiempo']; ?></p>
                            </div>
                        </div>
                        
                        <div class="estimate-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h4>Seguimiento por Email</h4>
                                <p>Recibirás actualizaciones en: <?php echo htmlspecialchars($pedido['email_cliente']); ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if ($pedido['notas_pedido']): ?>
                        <div class="order-notes">
                            <h4>Notas del Pedido:</h4>
                            <p><?php echo htmlspecialchars($pedido['notas_pedido']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Próximos Pasos -->
            <div class="next-steps">
                <h3><i class="fas fa-list-ol"></i> Próximos Pasos</h3>
                
                <div class="steps-timeline">
                    <div class="step completed">
                        <i class="fas fa-check"></i>
                        <div>
                            <h4>Pedido Confirmado</h4>
                            <p>Tu pedido ha sido recibido y confirmado</p>
                        </div>
                    </div>
                    
                    <div class="step pending">
                        <i class="fas fa-cog"></i>
                        <div>
                            <h4>Procesamiento</h4>
                            <p>Preparando tu pedido para envío</p>
                        </div>
                    </div>
                    
                    <div class="step pending">
                        <i class="fas fa-truck"></i>
                        <div>
                            <h4>Envío</h4>
                            <p>Tu pedido será enviado a la dirección indicada</p>
                        </div>
                    </div>
                    
                    <div class="step pending">
                        <i class="fas fa-home"></i>
                        <div>
                            <h4>Entrega</h4>
                            <p>Recibirás tu pedido en la fecha estimada</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="confirmation-actions">
                <a href="home.php" class="btn-primary">
                    <i class="fas fa-home"></i>
                    Continuar Comprando
                </a>
                
                <a href="mis_pedidos.php" class="btn-secondary">
                    <i class="fas fa-list"></i>
                    Ver Mis Pedidos
                </a>
                
                <button onclick="window.print()" class="btn-outline">
                    <i class="fas fa-print"></i>
                    Imprimir Confirmación
                </button>
            </div>

            <!-- Contacto de Soporte -->
            <div class="support-info">
                <div class="support-item">
                    <i class="fas fa-headset"></i>
                    <div>
                        <h4>¿Necesitas Ayuda?</h4>
                        <p>Contacta nuestro soporte: <strong>soporte@dragontech.mx</strong></p>
                        <p>Tel: <strong>+52 (55) 1234-5678</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.querySelector('.confirmation-header');
            header.style.opacity = '0';
            header.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                header.style.transition = 'all 0.5s ease';
                header.style.opacity = '1';
                header.style.transform = 'translateY(0)';
            }, 100);
        });

        // Copiar número de pedido
        document.addEventListener('click', function(e) {
            if (e.target.closest('.order-number')) {
                const orderNumber = '<?php echo $pedido['numero_pedido']; ?>';
                navigator.clipboard.writeText(orderNumber).then(() => {
                    const notification = document.createElement('div');
                    notification.textContent = 'Número de pedido copiado';
                    notification.style.cssText = 'position:fixed;top:20px;right:20px;background:var(--success);color:white;padding:10px 20px;border-radius:5px;z-index:1000;';
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 2000);
                });
            }
        });
    </script>
</body>
</html>