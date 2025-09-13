<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

include "db.php";
include "auth.php";

$currentUser = getCurrentUser();

// Parámetros de búsqueda y filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$marca = isset($_GET['marca']) ? $_GET['marca'] : '';
$precio_min = isset($_GET['precio_min']) ? (float)$_GET['precio_min'] : 0;
$precio_max = isset($_GET['precio_max']) ? (float)$_GET['precio_max'] : 9999999;
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'reciente';

// Construir consulta base
$where_conditions = ["l.activo = 1"];
$params = [];
$types = "";

// Filtro de búsqueda
if (!empty($search)) {
    $where_conditions[] = "(l.nombre LIKE ? OR l.descripcion LIKE ? OR l.marca LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Filtro de marca
if (!empty($marca)) {
    $where_conditions[] = "l.marca = ?";
    $params[] = $marca;
    $types .= "s";
}

// Filtro de precio
$where_conditions[] = "l.precio BETWEEN ? AND ?";
$params[] = $precio_min;
$params[] = $precio_max;
$types .= "dd";

// Orden
$order_clause = "";
switch ($orden) {
    case 'precio_asc':
        $order_clause = "ORDER BY l.precio ASC";
        break;
    case 'precio_desc':
        $order_clause = "ORDER BY l.precio DESC";
        break;
    case 'nombre':
        $order_clause = "ORDER BY l.nombre ASC";
        break;
    default:
        $order_clause = "ORDER BY l.fecha_creacion DESC";
}

// Consulta final
$where_clause = implode(" AND ", $where_conditions);
$sql = "SELECT l.*, u.username as vendedor_nombre 
        FROM laptops l 
        LEFT JOIN usuarios u ON l.vendedor = u.id 
        WHERE $where_clause 
        $order_clause";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$laptops = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener marcas para el filtro
$marcas_stmt = $conn->prepare("SELECT DISTINCT marca FROM laptops WHERE marca IS NOT NULL ORDER BY marca");
$marcas_stmt->execute();
$marcas = $marcas_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Laptops - DragonTech</title>
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
            <a href="laptops.php" class="active">LAPTOPS</a>
            <a href="carrito.php">
                <i class="fas fa-shopping-cart"></i> CARRITO
                <span id="cart-count" style="background: var(--error); border-radius: 50%; padding: 2px 6px; font-size: 0.8rem; margin-left: 5px;">0</span>
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

    <div class="container" style="margin-top: 2rem;">
        <h1 style="color: var(--primary); text-align: center; margin-bottom: 2rem;">
            <i class="fas fa-laptop"></i> CATÁLOGO DE LAPTOPS GAMING
        </h1>

        <!-- Filtros y Búsqueda -->
        <div style="background: var(--card-gradient); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 2rem;">
            <form method="GET" action="laptops.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                
                <!-- Búsqueda -->
                <div>
                    <label style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Buscar:</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Nombre, descripción o marca..." 
                           style="width: 100%; padding: 0.7rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 4px; color: var(--text-primary);">
                </div>

                <!-- Marca -->
                <div>
                    <label style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Marca:</label>
                    <select name="marca" style="width: 100%; padding: 0.7rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 4px; color: var(--text-primary);">
                        <option value="">Todas las marcas</option>
                        <?php foreach ($marcas as $m): ?>
                            <option value="<?php echo htmlspecialchars($m['marca']); ?>" 
                                    <?php echo ($marca == $m['marca']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['marca']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Precio -->
                <div>
                    <label style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Precio:</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="number" name="precio_min" value="<?php echo $precio_min > 0 ? $precio_min : ''; ?>" 
                               placeholder="Mín" min="0" step="0.01"
                               style="width: 100%; padding: 0.7rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 4px; color: var(--text-primary);">
                        <input type="number" name="precio_max" value="<?php echo $precio_max < 9999999 ? $precio_max : ''; ?>" 
                               placeholder="Máx" min="0" step="0.01"
                               style="width: 100%; padding: 0.7rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 4px; color: var(--text-primary);">
                    </div>
                </div>

                <!-- Orden -->
                <div>
                    <label style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Ordenar por:</label>
                    <select name="orden" style="width: 100%; padding: 0.7rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 4px; color: var(--text-primary);">
                        <option value="reciente" <?php echo ($orden == 'reciente') ? 'selected' : ''; ?>>Más recientes</option>
                        <option value="precio_asc" <?php echo ($orden == 'precio_asc') ? 'selected' : ''; ?>>Precio: menor a mayor</option>
                        <option value="precio_desc" <?php echo ($orden == 'precio_desc') ? 'selected' : ''; ?>>Precio: mayor a menor</option>
                        <option value="nombre" <?php echo ($orden == 'nombre') ? 'selected' : ''; ?>>Nombre A-Z</option>
                    </select>
                </div>

                <!-- Botones -->
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" style="background: linear-gradient(90deg, var(--primary), var(--accent)); color: white; border: none; padding: 0.7rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <a href="laptops.php" style="background: var(--bg-tertiary); color: var(--text-secondary); border: 1px solid var(--border); padding: 0.7rem 1rem; border-radius: 4px; text-decoration: none; display: inline-block;">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Resultados -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="color: var(--text-primary); margin: 0;">
                <?php if (!empty($search) || !empty($marca) || $precio_min > 0 || $precio_max < 9999999): ?>
                    Resultados de búsqueda (<?php echo count($laptops); ?>)
                <?php else: ?>
                    Todos los productos (<?php echo count($laptops); ?>)
                <?php endif; ?>
            </h2>
        </div>

        <!-- Grid de Laptops -->
        <div class="laptops-grid">
            <?php if (count($laptops) > 0): ?>
                <?php foreach ($laptops as $laptop): ?>
                    <div class="laptop-card">
                        <img src="<?php echo htmlspecialchars($laptop['imagen']); ?>" 
                             alt="<?php echo htmlspecialchars($laptop['nombre']); ?>" 
                             class="laptop-image">
                        <div class="laptop-info">
                            <h3 class="laptop-name"><?php echo htmlspecialchars($laptop['nombre']); ?></h3>
                            <?php if (!empty($laptop['marca'])): ?>
                                <p style="color: var(--secondary); font-size: 0.9rem; margin: 0.2rem 0;">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($laptop['marca']); ?>
                                </p>
                            <?php endif; ?>
                            <div class="laptop-price">$<?php echo number_format($laptop['precio'], 2); ?></div>
                            <p class="laptop-description"><?php echo htmlspecialchars(substr($laptop['descripcion'], 0, 80)); ?>...</p>
                            
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
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <h3>NO SE ENCONTRARON LAPTOPS</h3>
                    <p>No hay productos que coincidan con tus criterios de búsqueda.</p>
                    <a href="laptops.php" class="add-btn">VER TODOS LOS PRODUCTOS</a>
                </div>
            <?php endif; ?>
        </div>
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