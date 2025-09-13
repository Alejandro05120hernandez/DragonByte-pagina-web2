<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

include "db.php";

// Obtener laptops de la base de datos
$stmt = $conn->prepare("SELECT id, nombre, precio, descripcion, imagen FROM laptops ORDER BY id DESC");
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
            <a href="home.php" class="active">INICIO</a>
            <a href="laptops.php">LAPTOPS</a>
            <a href="sales.php">VENTAS</a>
            <a href="logout.php">CERRAR SESIÓN</a>
        </nav>
        <a href="add_laptop.php" class="add-btn">+ AGREGAR LAPTOP</a>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>POTENCIA GAMER EXTREMA</h1>
            <p>Descubre la mejor colección de laptops gaming con el rendimiento que necesitas para dominar cualquier juego.</p>
            <div class="hero-buttons">
                <a href="laptops.php" class="btn-primary">EXPLORAR COLECCIÓN</a>
                <a href="add_laptop.php" class="btn-secondary">VENDER LAPTOP</a>
            </div>
        </div>
    </section>

    <div class="container">
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
                            <div class="laptop-actions">
                                <a href="view_laptop.php?id=<?php echo $laptop['id']; ?>" class="btn btn-view">VER DETALLES</a>
                                <a href="delete_laptop.php?id=<?php echo $laptop['id']; ?>" class="btn btn-delete" onclick="return confirm('¿Estás seguro de eliminar esta laptop?');">ELIMINAR</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>NO HAY LAPTOPS EN EL CATÁLOGO</h3>
                    <p>Comienza agregando la primera laptop al sistema.</p>
                    <a href="add_laptop.php" class="add-btn">+ AGREGAR PRIMERA LAPTOP</a>
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
                <a href="sales.php">Ventas</a>
                <a href="add_laptop.php">Agregar Laptop</a>
                <a href="logout.php">Cerrar Sesión</a>
            </div>
            <p>© 2023 DRAGONTECH - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>