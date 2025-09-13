<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

include "db.php";
include "auth.php";

// Obtener información del usuario actual
$currentUser = getCurrentUser();

// Obtener laptops de la base de datos con información del vendedor
$stmt = $conn->prepare("SELECT l.id, l.nombre, l.precio, l.descripcion, l.imagen, l.vendedor, u.username as vendedor_nombre FROM laptops l LEFT JOIN usuarios u ON l.vendedor = u.id ORDER BY l.id DESC");
$stmt->execute();
$result = $stmt->get_result();
$laptops = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - DragonTech</title>
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
            <a href="home.php" class="active">INICIO</a>
            <a href="laptops.php">LAPTOPS</a>
            <a href="carrito.php">
                <i class="fas fa-shopping-cart"></i> CARRITO
                <span id="cart-count" style="background: var(--error); border-radius: 50%; padding: 2px 6px; font-size: 0.8rem; margin-left: 5px;">0</span>
            </a>
            <a href="profile.php">
                <i class="fas fa-user"></i> PERFIL
            </a>
            <?php if (isAdmin()): ?>
                <a href="admin.php">PANEL ADMIN</a>
            <?php endif; ?>
            <a href="logout.php">CERRAR SESIÓN</a>
        </nav>
        <?php if (isAdmin()): ?>
            <a href="add-laptop.php" class="add-btn">+ AGREGAR LAPTOP</a>
        <?php endif; ?>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>POTENCIA GAMER EXTREMA</h1>
            <p>Descubre la mejor colección de laptops gaming con el rendimiento que necesitas para dominar cualquier juego.</p>
            <div class="hero-buttons">
                <a href="laptops.php" class="btn-primary">EXPLORAR COLECCIÓN</a>
                <?php if (isAdmin()): ?>
                    <a href="add-laptop.php" class="btn-secondary">GESTIONAR TIENDA</a>
                <?php else: ?>
                    <a href="#" class="btn-secondary" onclick="alert('Solo los administradores pueden gestionar productos');">CONTACTAR TIENDA</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="container">
        <?php if (isset($_GET['error']) && $_GET['error'] == 'no_admin'): ?>
            <div style="background: rgba(255, 56, 100, 0.1); border: 1px solid var(--error); color: var(--error); padding: 1rem; border-radius: 6px; margin-bottom: 2rem; text-align: center;">
                <i class="fas fa-exclamation-triangle"></i> <strong>Acceso Denegado:</strong> Solo los administradores pueden gestionar productos de la tienda.
            </div>
        <?php endif; ?>
        
        <h2 class="section-title">LAPTOPS DESTACADAS</h2>
        
        <div class="laptops-grid">
            <?php if (count($laptops) > 0): ?>
                <?php foreach ($laptops as $laptop): ?>
                    <div class="laptop-card">
                        <img src="<?php echo htmlspecialchars($laptop['imagen']); ?>" alt="<?php echo htmlspecialchars($laptop['nombre']); ?>" class="laptop-image">
                        <div class="laptop-info">
                            <h3 class="laptop-name"><?php echo htmlspecialchars($laptop['nombre']); ?></h3>
                            <div class="laptop-price">$<?php echo number_format($laptop['precio'], 2); ?></div>
                            <p class="laptop-description"><?php echo htmlspecialchars($laptop['descripcion']); ?></p>
                            <?php if (isAdmin()): ?>
                                <p class="laptop-seller" style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">
                                    <i class="fas fa-user"></i> ID: <?php echo $laptop['id']; ?> | Agregado por: <?php echo htmlspecialchars($laptop['vendedor_nombre']); ?>
                                </p>
                            <?php endif; ?>
                            <div class="laptop-actions">
                                <a href="view_laptop.php?id=<?php echo $laptop['id']; ?>" class="btn btn-view">VER DETALLES</a>
                                <button onclick="addToCart(<?php echo $laptop['id']; ?>)" class="btn" style="background: linear-gradient(90deg, var(--success), var(--secondary)); color: white; border: none; cursor: pointer;">
                                    <i class="fas fa-cart-plus"></i> AGREGAR
                                </button>
                                <?php if (isAdmin()): ?>
                                    <a href="delete_laptop.php?id=<?php echo $laptop['id']; ?>" class="btn btn-delete" onclick="return confirm('¿Estás seguro de eliminar esta laptop?');">ELIMINAR</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>NO HAY LAPTOPS EN EL CATÁLOGO</h3>
                    <p>La tienda aún no tiene productos disponibles.</p>
                    <?php if (isAdmin()): ?>
                        <a href="add-laptop.php" class="add-btn">+ AGREGAR PRIMERA LAPTOP</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <i class="fas fa-dragon"></i>
                <span>DRAGONTECH</span>
            </div>
            <p>El mejor lugar para encontrar y vender laptops gaming de alta calidad.</p>
            <div class="footer-links">
                <a href="home.php">Inicio</a>
                <a href="laptops.php">Laptops</a>
                <a href="carrito.php">Carrito</a>
                <a href="profile.php">Mi Perfil</a>
                <a href="logout.php">Cerrar Sesión</a>
            </div>
            <p>© 2023 DRAGONTECH - Todos los derechos reservados</p>
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
                alert('✅ Producto agregado al carrito');
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