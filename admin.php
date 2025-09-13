<?php
session_start();
include "db.php";
include "auth.php";

// Verificar que esté logueado y sea administrador
requireLogin();
requireAdmin();

$currentUser = getCurrentUser();

// Obtener estadísticas
$stmt = $conn->prepare("SELECT COUNT(*) as total_laptops FROM laptops");
$stmt->execute();
$total_laptops = $stmt->get_result()->fetch_assoc()['total_laptops'];

$stmt = $conn->prepare("SELECT COUNT(*) as total_usuarios FROM usuarios WHERE rol = 'cliente'");
$stmt->execute();
$total_usuarios = $stmt->get_result()->fetch_assoc()['total_usuarios'];

// Obtener laptops recientes
$stmt = $conn->prepare("SELECT l.id, l.nombre, l.precio, l.fecha_creacion, u.username as agregado_por FROM laptops l LEFT JOIN usuarios u ON l.vendedor = u.id ORDER BY l.fecha_creacion DESC LIMIT 5");
$stmt->execute();
$laptops_recientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - DragonTech</title>
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
            </a>
            <a href="admin.php" class="active">PANEL ADMIN</a>
            <a href="add-laptop.php">AGREGAR LAPTOP</a>
            <a href="logout.php">CERRAR SESIÓN</a>
        </nav>
        <div style="color: var(--success); font-size: 0.9rem;">
            <i class="fas fa-crown"></i> Admin: <?php echo htmlspecialchars($currentUser['username']); ?>
        </div>
    </header>

    <div class="container" style="margin-top: 2rem;">
        <h1 style="color: var(--primary); text-align: center; margin-bottom: 2rem;">
            <i class="fas fa-cogs"></i> PANEL DE ADMINISTRACIÓN
        </h1>

        <!-- Estadísticas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: var(--card-gradient); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border); text-align: center;">
                <div style="color: var(--primary); font-size: 2.5rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-laptop"></i>
                </div>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">Laptops en Tienda</h3>
                <div style="font-size: 2rem; font-weight: bold; color: var(--success);"><?php echo $total_laptops; ?></div>
            </div>

            <div style="background: var(--card-gradient); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border); text-align: center;">
                <div style="color: var(--secondary); font-size: 2.5rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-users"></i>
                </div>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">Usuarios Registrados</h3>
                <div style="font-size: 2rem; font-weight: bold; color: var(--success);"><?php echo $total_usuarios; ?></div>
            </div>

            <div style="background: var(--card-gradient); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border); text-align: center;">
                <div style="color: var(--accent); font-size: 2.5rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">Estado Sistema</h3>
                <div style="font-size: 1.2rem; font-weight: bold; color: var(--success);">ACTIVO</div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div style="background: var(--card-gradient); padding: 2rem; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 2rem;">
            <h2 style="color: var(--text-primary); margin-bottom: 1.5rem;">
                <i class="fas fa-bolt"></i> Acciones Rápidas
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="add-laptop.php" style="background: linear-gradient(90deg, var(--primary), var(--accent)); color: white; padding: 1rem; border-radius: 6px; text-decoration: none; text-align: center; font-weight: 600; transition: all 0.3s ease;">
                    <i class="fas fa-plus"></i> Agregar Laptop
                </a>
                <a href="home.php" style="background: linear-gradient(90deg, var(--secondary), var(--primary)); color: white; padding: 1rem; border-radius: 6px; text-decoration: none; text-align: center; font-weight: 600; transition: all 0.3s ease;">
                    <i class="fas fa-eye"></i> Ver Tienda
                </a>
                <a href="#" onclick="alert('Funcionalidad próximamente')" style="background: linear-gradient(90deg, var(--accent), var(--secondary)); color: white; padding: 1rem; border-radius: 6px; text-decoration: none; text-align: center; font-weight: 600; transition: all 0.3s ease;">
                    <i class="fas fa-users"></i> Gestionar Usuarios
                </a>
            </div>
        </div>

        <!-- Laptops Recientes -->
        <div style="background: var(--card-gradient); padding: 2rem; border-radius: 8px; border: 1px solid var(--border);">
            <h2 style="color: var(--text-primary); margin-bottom: 1.5rem;">
                <i class="fas fa-clock"></i> Laptops Agregadas Recientemente
            </h2>
            
            <?php if (count($laptops_recientes) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <th style="padding: 1rem; text-align: left; color: var(--text-secondary);">ID</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-secondary);">Nombre</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-secondary);">Precio</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-secondary);">Fecha</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-secondary);">Agregado por</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-secondary);">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($laptops_recientes as $laptop): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <td style="padding: 1rem; color: var(--text-primary);">#<?php echo $laptop['id']; ?></td>
                                    <td style="padding: 1rem; color: var(--text-primary);"><?php echo htmlspecialchars($laptop['nombre']); ?></td>
                                    <td style="padding: 1rem; color: var(--success);">$<?php echo number_format($laptop['precio'], 2); ?></td>
                                    <td style="padding: 1rem; color: var(--text-secondary);"><?php echo date('d/m/Y', strtotime($laptop['fecha_creacion'])); ?></td>
                                    <td style="padding: 1rem; color: var(--text-secondary);"><?php echo htmlspecialchars($laptop['agregado_por']); ?></td>
                                    <td style="padding: 1rem;">
                                        <a href="delete_laptop.php?id=<?php echo $laptop['id']; ?>" 
                                           onclick="return confirm('¿Eliminar esta laptop?')"
                                           style="color: var(--error); text-decoration: none; padding: 0.3rem; border-radius: 3px;">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No hay laptops en la tienda aún.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer" style="margin-top: 3rem;">
        <div class="footer-content">
            <div class="footer-logo">
                <i class="fas fa-dragon"></i>
                <span>DRAGONTECH ADMIN</span>
            </div>
            <p>Panel de administración para gestionar la tienda DragonTech.</p>
        </div>
    </footer>
</body>
</html>