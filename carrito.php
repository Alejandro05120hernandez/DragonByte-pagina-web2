<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

include "db.php";
include "auth.php";

$currentUser = getCurrentUser();
$usuario_id = $_SESSION['user'];

// Procesar acciones del carrito
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                $carrito_id = (int)$_POST['carrito_id'];
                $nueva_cantidad = (int)$_POST['cantidad'];
                
                if ($nueva_cantidad > 0) {
                    $stmt = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE id = ? AND usuario_id = ?");
                    $stmt->bind_param("iii", $nueva_cantidad, $carrito_id, $usuario_id);
                    $stmt->execute();
                    $stmt->close();
                }
                break;
                
            case 'remove_item':
                $carrito_id = (int)$_POST['carrito_id'];
                $stmt = $conn->prepare("DELETE FROM carrito WHERE id = ? AND usuario_id = ?");
                $stmt->bind_param("ii", $carrito_id, $usuario_id);
                $stmt->execute();
                $stmt->close();
                break;
                
            case 'clear_cart':
                $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ?");
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $stmt->close();
                break;
        }
        
        header("Location: carrito.php");
        exit();
    }
}

// Obtener items del carrito
$stmt = $conn->prepare("SELECT c.id as carrito_id, c.cantidad, c.fecha_agregado,
                               l.id as laptop_id, l.nombre, l.precio, l.imagen, l.marca
                        FROM carrito c 
                        JOIN laptops l ON c.laptop_id = l.id 
                        WHERE c.usuario_id = ? AND l.activo = 1
                        ORDER BY c.fecha_agregado DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$carrito_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcular totales
$subtotal = 0;
foreach ($carrito_items as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

$impuestos = $subtotal * 0.16; // 16% IVA
$envio = $subtotal > 1000 ? 0 : 50; // Envío gratis para compras mayores a $1000
$total = $subtotal + $impuestos + $envio;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - DragonTech</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <i class="dragon-logo fas fa-dragon"></i>
            <span>DRAGONTECH</span>
        </div>
        <nav class="nav">
            <a href="home.php">INICIO</a>
            <a href="laptops.php">LAPTOPS</a>
            <a href="carrito.php" class="active">
                <i class="fas fa-shopping-cart"></i> CARRITO
            </a>
            <?php if (isAdmin()): ?>
                <a href="admin.php">PANEL ADMIN</a>
            <?php endif; ?>
            <a href="logout.php">CERRAR SESIÓN</a>
        </nav>
        <a href="laptops.php" class="add-btn">← SEGUIR COMPRANDO</a>
    </header>

    <div class="container" style="margin-top: 2rem;">
        <h1 style="color: var(--primary); text-align: center; margin-bottom: 2rem;">
            <i class="fas fa-shopping-cart"></i> CARRITO DE COMPRAS
        </h1>

        <?php if (empty($carrito_items)): ?>
            <!-- Carrito vacío -->
            <div class="empty-state" style="text-align: center; padding: 3rem; background: var(--card-gradient); border-radius: 8px; border: 1px solid var(--border);">
                <div style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2 style="color: var(--text-primary); margin-bottom: 1rem;">Tu carrito está vacío</h2>
                <p style="color: var(--text-secondary); margin-bottom: 2rem;">¡Descubre nuestras increíbles laptops gaming y agrega algo a tu carrito!</p>
                <a href="laptops.php" class="btn-primary" style="display: inline-block; padding: 1rem 2rem; text-decoration: none;">
                    <i class="fas fa-laptop"></i> EXPLORAR PRODUCTOS
                </a>
            </div>
        <?php else: ?>
            <!-- Carrito con productos -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                
                <!-- Lista de productos -->
                <div>
                    <div style="background: var(--card-gradient); border-radius: 8px; border: 1px solid var(--border); overflow: hidden;">
                        <div style="background: var(--bg-tertiary); padding: 1rem; border-bottom: 1px solid var(--border);">
                            <h2 style="color: var(--text-primary); margin: 0; display: flex; justify-content: space-between; align-items: center;">
                                <span>Productos en tu carrito (<?php echo count($carrito_items); ?>)</span>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="clear_cart">
                                    <button type="submit" onclick="return confirm('¿Vaciar carrito completo?')" 
                                            style="background: none; border: none; color: var(--error); cursor: pointer; font-size: 0.9rem;">
                                        <i class="fas fa-trash"></i> Vaciar carrito
                                    </button>
                                </form>
                            </h2>
                        </div>

                        <?php foreach ($carrito_items as $item): ?>
                            <div style="padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: grid; grid-template-columns: 100px 1fr auto auto; gap: 1rem; align-items: center;">
                                
                                <!-- Imagen -->
                                <img src="<?php echo htmlspecialchars($item['imagen']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['nombre']); ?>"
                                     style="width: 100px; height: 80px; object-fit: cover; border-radius: 6px;">

                                <!-- Información del producto -->
                                <div>
                                    <h3 style="color: var(--text-primary); margin-bottom: 0.5rem; font-size: 1.1rem;">
                                        <a href="view_laptop.php?id=<?php echo $item['laptop_id']; ?>" 
                                           style="color: var(--text-primary); text-decoration: none;">
                                            <?php echo htmlspecialchars($item['nombre']); ?>
                                        </a>
                                    </h3>
                                    <?php if (!empty($item['marca'])): ?>
                                        <p style="color: var(--secondary); font-size: 0.9rem; margin: 0;">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['marca']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p style="color: var(--success); font-weight: bold; font-size: 1.1rem; margin: 0.5rem 0;">
                                        $<?php echo number_format($item['precio'], 2); ?> c/u
                                    </p>
                                </div>

                                <!-- Cantidad -->
                                <div style="text-align: center;">
                                    <form method="POST" style="display: flex; align-items: center; gap: 0.5rem;">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="carrito_id" value="<?php echo $item['carrito_id']; ?>">
                                        <button type="button" onclick="changeQuantity(this, -1)" 
                                                style="background: var(--bg-secondary); border: 1px solid var(--border); color: var(--text-primary); width: 30px; height: 30px; border-radius: 4px; cursor: pointer;">
                                            -
                                        </button>
                                        <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" 
                                               min="1" max="10" onchange="this.form.submit()"
                                               style="width: 60px; text-align: center; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 4px; color: var(--text-primary); padding: 0.3rem;">
                                        <button type="button" onclick="changeQuantity(this, 1)" 
                                                style="background: var(--bg-secondary); border: 1px solid var(--border); color: var(--text-primary); width: 30px; height: 30px; border-radius: 4px; cursor: pointer;">
                                            +
                                        </button>
                                    </form>
                                    <div style="color: var(--text-primary); font-weight: bold; margin-top: 0.5rem;">
                                        $<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?>
                                    </div>
                                </div>

                                <!-- Eliminar -->
                                <div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="carrito_id" value="<?php echo $item['carrito_id']; ?>">
                                        <button type="submit" onclick="return confirm('¿Eliminar este producto?')"
                                                style="background: none; border: none; color: var(--error); cursor: pointer; font-size: 1.2rem; padding: 0.5rem;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Resumen del pedido -->
                <div>
                    <div style="background: var(--card-gradient); border-radius: 8px; border: 1px solid var(--border); padding: 2rem; position: sticky; top: 2rem;">
                        <h2 style="color: var(--text-primary); margin-bottom: 1.5rem; text-align: center;">
                            <i class="fas fa-receipt"></i> Resumen del Pedido
                        </h2>

                        <div style="margin-bottom: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: var(--text-secondary);">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: var(--text-secondary);">
                                <span>Impuestos (16%):</span>
                                <span>$<?php echo number_format($impuestos, 2); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: var(--text-secondary);">
                                <span>Envío:</span>
                                <span><?php echo $envio > 0 ? '$' . number_format($envio, 2) : 'GRATIS'; ?></span>
                            </div>
                            <hr style="border: none; border-top: 1px solid var(--border); margin: 1rem 0;">
                            <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; color: var(--success);">
                                <span>Total:</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>

                        <?php if ($subtotal < 1000): ?>
                            <div style="background: var(--secondary); color: var(--bg-primary); padding: 0.8rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem;">
                                <i class="fas fa-truck"></i> 
                                ¡Agrega $<?php echo number_format(1000 - $subtotal, 2); ?> más para envío gratis!
                            </div>
                        <?php else: ?>
                            <div style="background: var(--success); color: var(--bg-primary); padding: 0.8rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem;">
                                <i class="fas fa-check"></i> ¡Felicidades! Tienes envío gratis
                            </div>
                        <?php endif; ?>

                        <button onclick="proceedToCheckout()" 
                                style="width: 100%; background: linear-gradient(90deg, var(--primary), var(--accent)); color: white; border: none; padding: 1rem; border-radius: 6px; font-size: 1.1rem; font-weight: 600; cursor: pointer; margin-bottom: 1rem;">
                            <i class="fas fa-credit-card"></i> PROCEDER AL PAGO
                        </button>

                        <a href="laptops.php" 
                           style="display: block; text-align: center; color: var(--secondary); text-decoration: none; font-size: 0.9rem;">
                            <i class="fas fa-arrow-left"></i> Continuar comprando
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer" style="margin-top: 3rem;">
        <div class="footer-content">
            <div class="footer-logo">
                <i class="fas fa-dragon"></i>
                <span>DRAGONTECH</span>
            </div>
            <p>Tu tienda de laptops gaming de confianza.</p>
        </div>
    </footer>

    <script>
    function changeQuantity(button, change) {
        const input = button.parentElement.querySelector('input[name="cantidad"]');
        let newValue = parseInt(input.value) + change;
        if (newValue >= 1 && newValue <= 10) {
            input.value = newValue;
            input.form.submit();
        }
    }

    function proceedToCheckout() {
        alert('🚧 Funcionalidad de pago en desarrollo.\n\n' +
              'En una implementación real, aquí se integraría con:\n' +
              '• Stripe, PayPal, MercadoPago\n' +
              '• Sistema de direcciones de envío\n' +
              '• Confirmación de pedido\n' +
              '• Gestión de inventario\n\n' +
              'Por ahora, gracias por probar el carrito! 😊');
    }
    </script>
</body>
</html>