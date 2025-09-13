<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

include "db.php";
include "auth.php";

$currentUser = getCurrentUser();

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: laptops.php");
    exit();
}

$laptop_id = (int)$_GET['id'];

// Obtener información de la laptop
$stmt = $conn->prepare("SELECT l.*, u.username as vendedor_nombre, u.email as vendedor_email 
                        FROM laptops l 
                        LEFT JOIN usuarios u ON l.vendedor = u.id 
                        WHERE l.id = ? AND l.activo = 1");
$stmt->bind_param("i", $laptop_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: laptops.php?error=not_found");
    exit();
}

$laptop = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($laptop['nombre']); ?> - DragonTech</title>
    <link rel="stylesheet" href="styles/main.css">
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
            <a href="carrito.php">
                <i class="fas fa-shopping-cart"></i> CARRITO
                <span id="cart-count" style="background: var(--error); border-radius: 50%; padding: 2px 6px; font-size: 0.8rem; margin-left: 5px;">0</span>
            </a>
            <?php if (isAdmin()): ?>
                <a href="admin.php">PANEL ADMIN</a>
            <?php endif; ?>
            <a href="logout.php">CERRAR SESIÓN</a>
        </nav>
        <a href="laptops.php" class="add-btn">← VOLVER AL CATÁLOGO</a>
    </header>

    <div class="container" style="margin-top: 2rem;">
        <!-- Breadcrumb -->
        <nav style="margin-bottom: 2rem; color: var(--text-muted); font-size: 0.9rem;">
            <a href="home.php" style="color: var(--secondary); text-decoration: none;">Inicio</a>
            <span style="margin: 0 0.5rem;">></span>
            <a href="laptops.php" style="color: var(--secondary); text-decoration: none;">Laptops</a>
            <span style="margin: 0 0.5rem;">></span>
            <span><?php echo htmlspecialchars($laptop['nombre']); ?></span>
        </nav>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 3rem;">
            <!-- Imagen del producto -->
            <div style="background: var(--card-gradient); padding: 2rem; border-radius: 8px; border: 1px solid var(--border);">
                <img src="<?php echo htmlspecialchars($laptop['imagen']); ?>" 
                     alt="<?php echo htmlspecialchars($laptop['nombre']); ?>"
                     style="width: 100%; height: auto; border-radius: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                
                <?php if (isAdmin()): ?>
                    <div style="margin-top: 1rem; text-align: center;">
                        <a href="delete_laptop.php?id=<?php echo $laptop['id']; ?>" 
                           onclick="return confirm('¿Eliminar esta laptop?')"
                           style="background: var(--error); color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; font-size: 0.9rem;">
                            <i class="fas fa-trash"></i> Eliminar Producto
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Información del producto -->
            <div>
                <div style="background: var(--card-gradient); padding: 2rem; border-radius: 8px; border: 1px solid var(--border); height: fit-content;">
                    <h1 style="color: var(--primary); margin-bottom: 1rem; font-size: 2rem;">
                        <?php echo htmlspecialchars($laptop['nombre']); ?>
                    </h1>

                    <?php if (!empty($laptop['marca'])): ?>
                        <div style="color: var(--secondary); font-size: 1.1rem; margin-bottom: 1rem;">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($laptop['marca']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="font-size: 2.5rem; color: var(--success); font-weight: bold; margin-bottom: 1.5rem;">
                        $<?php echo number_format($laptop['precio'], 2); ?>
                    </div>

                    <div style="margin-bottom: 2rem;">
                        <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Descripción:</h3>
                        <p style="color: var(--text-secondary); line-height: 1.6; font-size: 1.1rem;">
                            <?php echo nl2br(htmlspecialchars($laptop['descripcion'])); ?>
                        </p>
                    </div>

                    <!-- Especificaciones técnicas -->
                    <?php if (!empty($laptop['procesador']) || !empty($laptop['ram']) || !empty($laptop['almacenamiento']) || !empty($laptop['tarjeta_grafica'])): ?>
                        <div style="margin-bottom: 2rem;">
                            <h3 style="color: var(--text-primary); margin-bottom: 1rem;">
                                <i class="fas fa-microchip"></i> Especificaciones Técnicas:
                            </h3>
                            <div style="display: grid; gap: 0.8rem;">
                                <?php if (!empty($laptop['procesador'])): ?>
                                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                        <span style="color: var(--text-secondary);">Procesador:</span>
                                        <span style="color: var(--text-primary); font-weight: 600;"><?php echo htmlspecialchars($laptop['procesador']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($laptop['ram'])): ?>
                                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                        <span style="color: var(--text-secondary);">Memoria RAM:</span>
                                        <span style="color: var(--text-primary); font-weight: 600;"><?php echo htmlspecialchars($laptop['ram']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($laptop['almacenamiento'])): ?>
                                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                        <span style="color: var(--text-secondary);">Almacenamiento:</span>
                                        <span style="color: var(--text-primary); font-weight: 600;"><?php echo htmlspecialchars($laptop['almacenamiento']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($laptop['tarjeta_grafica'])): ?>
                                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                        <span style="color: var(--text-secondary);">Tarjeta Gráfica:</span>
                                        <span style="color: var(--text-primary); font-weight: 600;"><?php echo htmlspecialchars($laptop['tarjeta_grafica']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($laptop['pantalla'])): ?>
                                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                        <span style="color: var(--text-secondary);">Pantalla:</span>
                                        <span style="color: var(--text-primary); font-weight: 600;"><?php echo htmlspecialchars($laptop['pantalla']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($laptop['sistema_operativo'])): ?>
                                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                                        <span style="color: var(--text-secondary);">Sistema Operativo:</span>
                                        <span style="color: var(--text-primary); font-weight: 600;"><?php echo htmlspecialchars($laptop['sistema_operativo']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Botones de acción -->
                    <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                        <button onclick="addToCart(<?php echo $laptop['id']; ?>)" 
                                style="flex: 1; background: linear-gradient(90deg, var(--success), var(--secondary)); color: white; border: none; padding: 1rem 2rem; border-radius: 6px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                            <i class="fas fa-cart-plus"></i> AGREGAR AL CARRITO
                        </button>
                        
                        <button onclick="buyNow(<?php echo $laptop['id']; ?>)" 
                                style="flex: 1; background: linear-gradient(90deg, var(--primary), var(--accent)); color: white; border: none; padding: 1rem 2rem; border-radius: 6px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                            <i class="fas fa-bolt"></i> COMPRAR AHORA
                        </button>
                    </div>

                    <!-- Información del vendedor -->
                    <div style="border-top: 1px solid var(--border); padding-top: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-user"></i>
                            <span>Agregado por: <strong style="color: var(--text-secondary);"><?php echo htmlspecialchars($laptop['vendedor_nombre']); ?></strong></span>
                        </div>
                        <div style="margin-top: 0.5rem;">
                            <i class="fas fa-calendar"></i>
                            <span>Fecha: <?php echo date('d/m/Y', strtotime($laptop['fecha_creacion'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos relacionados -->
        <?php
        // Obtener productos relacionados (misma marca o precio similar)
        $related_stmt = $conn->prepare("SELECT id, nombre, precio, imagen FROM laptops 
                                       WHERE (marca = ? OR (precio BETWEEN ? AND ?)) 
                                       AND id != ? AND activo = 1 
                                       LIMIT 4");
        $precio_min = $laptop['precio'] * 0.7;
        $precio_max = $laptop['precio'] * 1.3;
        $related_stmt->bind_param("sddi", $laptop['marca'], $precio_min, $precio_max, $laptop['id']);
        $related_stmt->execute();
        $related_laptops = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        ?>

        <?php if (count($related_laptops) > 0): ?>
            <div style="margin-top: 3rem;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem; text-align: center;">
                    <i class="fas fa-heart"></i> PRODUCTOS RELACIONADOS
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($related_laptops as $related): ?>
                        <div style="background: var(--card-gradient); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; transition: transform 0.3s ease;">
                            <img src="<?php echo htmlspecialchars($related['imagen']); ?>" 
                                 alt="<?php echo htmlspecialchars($related['nombre']); ?>"
                                 style="width: 100%; height: 200px; object-fit: cover;">
                            <div style="padding: 1rem;">
                                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem; font-size: 1rem;">
                                    <?php echo htmlspecialchars($related['nombre']); ?>
                                </h3>
                                <div style="color: var(--success); font-weight: bold; margin-bottom: 1rem;">
                                    $<?php echo number_format($related['precio'], 2); ?>
                                </div>
                                <a href="view_laptop.php?id=<?php echo $related['id']; ?>" 
                                   style="background: var(--primary); color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; font-size: 0.9rem; display: inline-block; width: 100%; text-align: center;">
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
    // Función para agregar al carrito
    function addToCart(laptopId) {
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                laptop_id: laptopId,
                cantidad: 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Producto agregado al carrito exitosamente');
                updateCartCount();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error al agregar al carrito');
        });
    }

    // Función para comprar ahora
    function buyNow(laptopId) {
        addToCart(laptopId);
        setTimeout(() => {
            window.location.href = 'carrito.php';
        }, 1000);
    }

    // Actualizar contador del carrito
    function updateCartCount() {
        fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('cart-count').textContent = data.count || 0;
        });
    }

    // Cargar contador al iniciar
    updateCartCount();
    </script>
</body>
</html>