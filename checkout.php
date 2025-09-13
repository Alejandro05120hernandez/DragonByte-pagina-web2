<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

// Verificar que el usuario esté logueado
requireLogin();

$usuario_id = $_SESSION['user'];

// Obtener items del carrito desde la base de datos
$stmt = $conn->prepare("SELECT c.id as carrito_id, c.cantidad, c.fecha_agregado,
                               l.id as laptop_id, l.nombre, l.precio, l.imagen, l.marca
                        FROM carrito c 
                        JOIN laptops l ON c.laptop_id = l.id 
                        WHERE c.usuario_id = ? AND l.activo = 1
                        ORDER BY c.fecha_agregado DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$carrito_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($carrito_items)) {
    header("Location: carrito.php");
    exit;
}

// Calcular totales
$subtotal = 0;
$items_carrito = [];

foreach ($carrito_items as $item) {
    $item_total = $item['precio'] * $item['cantidad'];
    $subtotal += $item_total;
    $items_carrito[] = [
        'laptop' => $item,
        'cantidad' => $item['cantidad'],
        'total' => $item_total
    ];
}

$impuestos = $subtotal * 0.16; // IVA 16%
$envio = $subtotal > 2000 ? 0 : 199; // Envío gratis arriba de $2000
$total = $subtotal + $impuestos + $envio;

// Procesar el pedido si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['procesar_pedido'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $ciudad = trim($_POST['ciudad']);
    $estado = trim($_POST['estado']);
    $codigo_postal = trim($_POST['codigo_postal']);
    $metodo_pago = $_POST['metodo_pago'];
    $notas = trim($_POST['notas']);
    
    // Generar número de pedido único
    $numero_pedido = 'DT' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    try {
        $conn->begin_transaction();
        
        // Insertar pedido
        $stmt = $conn->prepare("INSERT INTO pedidos (numero_pedido, usuario_id, nombre_cliente, email_cliente, telefono_cliente, direccion, ciudad, estado, codigo_postal, subtotal, impuestos, envio, total, metodo_pago, notas_pedido) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssssssddddss", $numero_pedido, $_SESSION['user_id'], $nombre, $email, $telefono, $direccion, $ciudad, $estado, $codigo_postal, $subtotal, $impuestos, $envio, $total, $metodo_pago, $notas);
        $stmt->execute();
        $pedido_id = $conn->insert_id;
        
        // Insertar items del pedido
        foreach ($items_carrito as $item) {
            $stmt = $conn->prepare("INSERT INTO pedido_items (pedido_id, laptop_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $pedido_id, $item['laptop']['laptop_id'], $item['cantidad'], $item['laptop']['precio'], $item['total']);
            $stmt->execute();
        }
        
        // Actualizar stock de laptops
        foreach ($items_carrito as $item) {
            $stmt = $conn->prepare("UPDATE laptops SET stock = stock - ? WHERE id = ?");
            $stmt->bind_param("ii", $item['cantidad'], $item['laptop']['laptop_id']);
            $stmt->execute();
        }
        
        $conn->commit();
        
        // Limpiar carrito de la base de datos
        $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        
        // Redirigir a página de confirmación
        header("Location: pedido_confirmado.php?numero=" . $numero_pedido);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error al procesar el pedido: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - DragonTech</title>
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
        <h1 class="page-title">
            <i class="fas fa-credit-card"></i>
            FINALIZAR COMPRA
        </h1>

        <?php if (isset($error)): ?>
            <div class="mensaje error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="checkout-container">
            <div class="checkout-form">
                <form method="POST" id="checkout-form">
                    <!-- Datos del Cliente -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Datos del Cliente</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre">Nombre Completo *</label>
                                <input type="text" id="nombre" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono">
                        </div>
                    </div>

                    <!-- Dirección de Envío -->
                    <div class="form-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Dirección de Envío</h3>
                        <div class="form-group">
                            <label for="direccion">Dirección Completa *</label>
                            <input type="text" id="direccion" name="direccion" placeholder="Calle, número, colonia" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="ciudad">Ciudad *</label>
                                <input type="text" id="ciudad" name="ciudad" required>
                            </div>
                            <div class="form-group">
                                <label for="estado">Estado *</label>
                                <input type="text" id="estado" name="estado" required>
                            </div>
                            <div class="form-group">
                                <label for="codigo_postal">Código Postal *</label>
                                <input type="text" id="codigo_postal" name="codigo_postal" required>
                            </div>
                        </div>
                    </div>

                    <!-- Métodos de Pago -->
                    <div class="form-section">
                        <h3><i class="fas fa-credit-card"></i> Método de Pago</h3>
                        <div class="payment-methods">
                            <div class="payment-option">
                                <input type="radio" id="tarjeta" name="metodo_pago" value="tarjeta_credito" checked>
                                <label for="tarjeta" class="payment-label">
                                    <i class="fab fa-cc-visa"></i>
                                    <i class="fab fa-cc-mastercard"></i>
                                    <i class="fab fa-cc-amex"></i>
                                    Tarjeta de Crédito/Débito
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="paypal" name="metodo_pago" value="paypal">
                                <label for="paypal" class="payment-label">
                                    <i class="fab fa-paypal"></i>
                                    PayPal
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="transferencia" name="metodo_pago" value="transferencia">
                                <label for="transferencia" class="payment-label">
                                    <i class="fas fa-university"></i>
                                    Transferencia Bancaria
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="oxxo" name="metodo_pago" value="oxxo">
                                <label for="oxxo" class="payment-label">
                                    <i class="fas fa-store"></i>
                                    OXXO Pay
                                </label>
                            </div>
                        </div>

                        <!-- Formulario de Tarjeta -->
                        <div id="tarjeta-form" class="payment-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="numero_tarjeta">Número de Tarjeta</label>
                                    <input type="text" id="numero_tarjeta" placeholder="1234 5678 9012 3456" maxlength="19">
                                </div>
                                <div class="form-group">
                                    <label for="nombre_tarjeta">Nombre en la Tarjeta</label>
                                    <input type="text" id="nombre_tarjeta" placeholder="NOMBRE APELLIDO">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="expiracion">Fecha de Expiración</label>
                                    <input type="text" id="expiracion" placeholder="MM/AA" maxlength="5">
                                </div>
                                <div class="form-group">
                                    <label for="cvv">CVV</label>
                                    <input type="text" id="cvv" placeholder="123" maxlength="4">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notas Adicionales -->
                    <div class="form-section">
                        <h3><i class="fas fa-sticky-note"></i> Notas del Pedido</h3>
                        <div class="form-group">
                            <textarea name="notas" placeholder="Instrucciones especiales de entrega..."></textarea>
                        </div>
                    </div>

                    <button type="submit" name="procesar_pedido" class="btn-checkout">
                        <i class="fas fa-lock"></i>
                        FINALIZAR COMPRA - $<?php echo number_format($total, 2); ?>
                    </button>
                </form>
            </div>

            <!-- Resumen del Pedido -->
            <div class="order-summary">
                <h3><i class="fas fa-receipt"></i> Resumen del Pedido</h3>
                
                <div class="cart-items">
                    <?php foreach ($items_carrito as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo htmlspecialchars($item['laptop']['imagen']); ?>" alt="<?php echo htmlspecialchars($item['laptop']['nombre']); ?>">
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['laptop']['nombre']); ?></h4>
                                <p>Cantidad: <?php echo $item['cantidad']; ?></p>
                                <p class="price">$<?php echo number_format($item['total'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-totals">
                    <div class="total-line">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="total-line">
                        <span>IVA (16%):</span>
                        <span>$<?php echo number_format($impuestos, 2); ?></span>
                    </div>
                    <div class="total-line">
                        <span>Envío:</span>
                        <span><?php echo $envio > 0 ? '$' . number_format($envio, 2) : 'GRATIS'; ?></span>
                    </div>
                    <div class="total-line total-final">
                        <span><strong>Total:</strong></span>
                        <span><strong>$<?php echo number_format($total, 2); ?></strong></span>
                    </div>
                </div>

                <div class="security-badges">
                    <div class="badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>Compra 100% Segura</span>
                    </div>
                    <div class="badge">
                        <i class="fas fa-truck"></i>
                        <span>Envío Asegurado</span>
                    </div>
                    <div class="badge">
                        <i class="fas fa-undo"></i>
                        <span>30 días garantía</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Formatear número de tarjeta
        document.getElementById('numero_tarjeta').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            e.target.value = value;
        });

        // Formatear fecha de expiración
        document.getElementById('expiracion').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Solo números en CVV
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        // Mostrar/ocultar formularios de pago
        document.querySelectorAll('input[name="metodo_pago"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.payment-form').forEach(form => {
                    form.style.display = 'none';
                });
                
                if (this.value === 'tarjeta_credito') {
                    document.getElementById('tarjeta-form').style.display = 'block';
                }
            });
        });

        // Validación del formulario
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const metodo = document.querySelector('input[name="metodo_pago"]:checked').value;
            
            if (metodo === 'tarjeta_credito') {
                const numero = document.getElementById('numero_tarjeta').value.replace(/\s/g, '');
                const expiracion = document.getElementById('expiracion').value;
                const cvv = document.getElementById('cvv').value;
                
                if (numero.length < 13 || !expiracion || cvv.length < 3) {
                    e.preventDefault();
                    alert('Por favor completa todos los datos de la tarjeta');
                    return;
                }
            }
            
            // Simular procesamiento
            const btn = document.querySelector('.btn-checkout');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESANDO...';
            btn.disabled = true;
        });
    </script>
</body>
</html>