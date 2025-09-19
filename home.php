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
$userId = $currentUser['id'];

// Implementar caché para consultas (mejora de rendimiento)
$cache_time = 300; // 5 minutos
$cache_key = 'laptops_data_' . md5(serialize($_GET));

// Intentar obtener datos del caché
$cached_data = false; // En producción, usaríamos memcached o Redis
/*
// Ejemplo de implementación con memcached (descomentar en producción)
if (class_exists('Memcached')) {
    $memcached = new Memcached();
    $memcached->addServer('localhost', 11211);
    $cached_data = $memcached->get($cache_key);
}
*/

if ($cached_data) {
    $laptops = $cached_data;
} else {
    // Obtener laptops de la base de datos con información del vendedor y conteo de likes
    $stmt = $conn->prepare("
        SELECT l.id, l.nombre, l.precio, l.descripcion, l.imagen, l.vendedor, 
               u.username as vendedor_nombre,
               COALESCE(SUM(CASE WHEN r.tipo = 'like' THEN 1 ELSE 0 END), 0) as likes,
               COALESCE(SUM(CASE WHEN r.tipo = 'dislike' THEN 1 ELSE 0 END), 0) as dislikes,
               EXISTS(SELECT 1 FROM valoraciones WHERE laptop_id = l.id AND usuario_id = ? AND tipo = 'like') as user_liked,
               EXISTS(SELECT 1 FROM valoraciones WHERE laptop_id = l.id AND usuario_id = ? AND tipo = 'dislike') as user_disliked
        FROM laptops l 
        LEFT JOIN usuarios u ON l.vendedor = u.id 
        LEFT JOIN valoraciones r ON l.id = r.laptop_id
        GROUP BY l.id
        ORDER BY l.id DESC
    ");
    
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $laptops = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Guardar en caché (en producción)
    /*
    if (class_exists('Memcached')) {
        $memcached->set($cache_key, $laptops, $cache_time);
    }
    */
}
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
    <style>
        /* Estilos optimizados y comprimidos */
        :root{--primary:#6a11cb;--secondary:#2575fc;--accent:#ff2a6d;--success:#00c896;--warning:#ffbd44;--error:#ff2a6d;--dark:#0c0e2e;--darker:#070917;--light:#f8f9ff;--text-light:#fff;--text-muted:#a1a8d3;--card-bg:#13142e;--card-hover:#1c1e40;--gradient:linear-gradient(135deg,var(--primary)0%,var(--secondary)100%);--gradient-accent:linear-gradient(135deg,var(--accent)0%,#ff5e7d 100%);--purple:#8a2be2}*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Rajdhani',sans-serif;background:var(--darker) url('https://images.unsplash.com/photo-1593640408182-31c70c8268f5?ixlib=rb-4.0.3') center/cover fixed;color:var(--text-light);line-height:1.6;position:relative}body::before{content:'';position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(rgba(7,9,23,.85),rgba(7,9,23,.95));z-index:-1}.header{display:flex;justify-content:space-between;align-items:center;padding:1rem 5%;background:rgba(7,9,23,.9);backdrop-filter:blur(10px);border-bottom:1px solid rgba(255,255,255,.1);position:sticky;top:0;z-index:1000}.logo{display:flex;align-items:center;font-family:'Orbitron',sans-serif;font-weight:700;font-size:1.5rem;color:var(--text-light);text-shadow:0 0 10px rgba(106,17,203,.5)}.header-logo{max-width:50px;height:auto;margin-right:10px;vertical-align:middle;border-radius:50%;box-shadow:0 0 15px rgba(106,17,203,.7)}.dragon-logo{font-size:1.8rem;color:var(--accent);margin-right:10px;text-shadow:0 0 10px rgba(255,42,109,.7)}.nav{display:flex;gap:1.5rem}.nav a{color:var(--text-light);text-decoration:none;font-weight:600;font-size:1rem;padding:.5rem 1rem;border-radius:4px;transition:all .3s ease;position:relative}.nav a:hover{color:var(--accent)}.nav a.active{color:var(--accent);background:rgba(255,42,109,.1)}.nav a::after{content:'';position:absolute;bottom:0;left:50%;width:0;height:2px;background:var(--gradient-accent);transition:all .3s ease;transform:translateX(-50%)}.nav a:hover::after{width:80%}.add-btn{background:var(--gradient);color:#fff;padding:.7rem 1.5rem;border-radius:30px;text-decoration:none;font-weight:600;transition:all .3s ease;box-shadow:0 4px 15px rgba(106,17,203,.4)}.add-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(106,17,203,.6)}.hero{padding:6rem 5%;text-align:center;position:relative;overflow:hidden}.hero-content{max-width:800px;margin:0 auto;position:relative;z-index:2}.welcome-text{font-size:1.4rem;margin-bottom:.5rem;color:var(--text-light);font-weight:600;text-shadow:0 0 10px rgba(255,255,255,.5)}.welcome-user{font-size:2rem;margin-bottom:1rem;color:var(--purple);font-weight:700;text-shadow:0 0 15px rgba(138,43,226,.7)}.hero-content p{font-size:1.2rem;margin-bottom:2rem;color:var(--text-muted);max-width:600px;margin-left:auto;margin-right:auto}.hero-content h1{font-family:'Orbitron',sans-serif;font-size:3.5rem;margin-bottom:2rem;background:linear-gradient(90deg,#ff2a6d,#00c896,#2575fc);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-shadow:0 0 20px rgba(255,42,109,.5)}.hero-buttons{display:flex;justify-content:center;gap:1rem;margin-top:2rem}.btn-primary,.btn-secondary{padding:1rem 2rem;border-radius:30px;text-decoration:none;font-weight:600;transition:all .3s ease;font-size:1rem}.btn-primary{background:var(--gradient);color:#fff;box-shadow:0 4px 15px rgba(106,17,203,.4)}.btn-primary:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(106,17,203,.6)}.btn-secondary{background:rgba(255,255,255,.1);color:var(--text-light);border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(10px)}.btn-secondary:hover{background:rgba(255,255,255,.2);transform:translateY(-3px)}.container{padding:3rem 5%;position:relative;z-index:2}.section-title{font-family:'Orbitron',sans-serif;font-size:2.5rem;text-align:center;margin-bottom:3rem;color:var(--text-light);position:relative;padding-bottom:1rem}.section-title::after{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:100px;height:4px;background:var(--gradient);border-radius:2px}.laptops-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:2rem}.laptop-card{background:var(--card-bg);border-radius:12px;overflow:hidden;transition:all .3s ease;box-shadow:0 5px 15px rgba(0,0,0,.2);border:1px solid rgba(255,255,255,.05)}.laptop-card:hover{transform:translateY(-5px);box-shadow:0 10px 25px rgba(106,17,203,.3);border-color:rgba(106,17,203,.3)}.laptop-image{width:100%;height:200px;object-fit:cover;border-bottom:1px solid rgba(255,255,255,.05)}.laptop-info{padding:1.5rem}.laptop-name{font-family:'Orbitron',sans-serif;font-size:1.3rem;margin-bottom:.5rem;color:var(--text-light)}.laptop-price{font-size:1.5rem;font-weight:700;color:var(--accent);margin-bottom:1rem}.laptop-description{color:var(--text-muted);margin-bottom:1.5rem;font-size:.95rem;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}.laptop-actions{display:flex;gap:.5rem;flex-wrap:wrap}.btn{padding:.6rem 1rem;border-radius:4px;text-decoration:none;font-weight:600;transition:all .3s ease;font-size:.9rem;display:inline-flex;align-items:center;gap:.3rem;cursor:pointer}.btn-view{background:rgba(37,117,252,.1);color:var(--secondary);border:1px solid rgba(37,117,252,.3)}.btn-view:hover{background:rgba(37,117,252,.2)}.btn-delete{background:rgba(255,42,109,.1);color:var(--error);border:1px solid rgba(255,42,109,.3)}.btn-delete:hover{background:rgba(255,42,109,.2)}.empty-state{grid-column:1/-1;text-align:center;padding:3rem;background:var(--card-bg);border-radius:12px;border:1px solid rgba(255,255,255,.05)}.empty-state h3{font-family:'Orbitron',sans-serif;color:var(--text-light);margin-bottom:1rem}.empty-state p{color:var(--text-muted);margin-bottom:2rem}.footer{background:var(--dark);padding:3rem 5%;text-align:center;margin-top:4rem;border-top:1px solid rgba(255,255,255,.05);position:relative;z-index:2}.footer-content{max-width:800px;margin:0 auto}.footer-logo{display:flex;justify-content:center;align-items:center;font-family:'Orbitron',sans-serif;font-weight:700;font-size:1.5rem;color:var(--text-light);margin-bottom:1.5rem;gap:.5rem}.footer-logo i{color:var(--accent)}.footer p{color:var(--text-muted);margin-bottom:2rem}.footer-links{display:flex;justify-content:center;gap:2rem;margin-bottom:2rem;flex-wrap:wrap}.footer-links a{color:var(--text-light);text-decoration:none;transition:color .3s ease}.footer-links a:hover{color:var(--accent)}.notification{position:fixed;top:20px;right:20px;padding:15px 25px;border-radius:8px;color:#fff;font-weight:600;z-index:10000;opacity:0;transform:translateX(100%);transition:all .3s ease;box-shadow:0 5px 15px rgba(0,0,0,.2)}.notification.success{background:var(--success);opacity:1;transform:translateX(0)}.notification.error{background:var(--error);opacity:1;transform:translateX(0)}
        
        /* Nuevos estilos para el sistema de valoraciones */
        .rating-container {display: flex; align-items: center; margin: 10px 0; gap: 10px;}
        .rating-btn {background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 20px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 5px;}
        .rating-btn:hover {background: rgba(255,255,255,0.1);}
        .rating-btn.active-like {background: rgba(0,200,150,0.2); border-color: var(--success); color: var(--success);}
        .rating-btn.active-dislike {background: rgba(255,42,109,0.2); border-color: var(--error); color: var(--error);}
        .rating-count {font-size: 0.9rem; color: var(--text-muted); margin-left: 5px;}
        
        /* Optimizaciones de rendimiento */
        .laptop-image {transition: opacity 0.3s ease;}
        .laptop-image.lazy {opacity: 0;}
        .laptop-image.loaded {opacity: 1;}
        
        @media (max-width:768px){.header{flex-direction:column;padding:1rem}.nav{margin-top:1rem;flex-wrap:wrap;justify-content:center}.hero-content h1{font-size:2.5rem}.hero-buttons{flex-direction:column;align-items:center}.laptops-grid{grid-template-columns:1fr}.footer-links{flex-direction:column;gap:1rem}.rating-container{flex-wrap:wrap}}
    </style>
</head>
<body>
    <div id="notification" class="notification" style="display: none;"></div>
    
    <header class="header">
        <div class="logo">
            <img src="uploads//logo.png.jpg" alt="DragonTech Logo" class="header-logo" loading="lazy">
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
                <i class="fas fa-user"></i> <?php echo strtoupper(htmlspecialchars($currentUser['username'])); ?>
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
            <div class="welcome-text">BIENVENIDO</div>
            <div class="welcome-user"><?php echo strtoupper(htmlspecialchars($currentUser['username'])); ?></div>
            <p>Descubre la mejor colección de laptops gaming con el rendimiento que necesitas para dominar cualquier juego.</p>
            <h1>POTENCIA GAMER EXTREMA</h1>
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
                        <img src="<?php echo htmlspecialchars($laptop['imagen']); ?>" alt="<?php echo htmlspecialchars($laptop['nombre']); ?>" class="laptop-image lazy" loading="lazy" onerror="this.src='https://via.placeholder.com/300x200/13142e/ffffff?text=Imagen+No+Disponible'" onload="this.classList.add('loaded')">
                        <div class="laptop-info">
                            <h3 class="laptop-name"><?php echo htmlspecialchars($laptop['nombre']); ?></h3>
                            <div class="laptop-price">$<?php echo number_format($laptop['precio'], 2); ?></div>
                            <p class="laptop-description"><?php echo htmlspecialchars($laptop['descripcion']); ?></p>
                            
                            <!-- Sistema de valoraciones (me gusta/no me gusta) -->
                            <div class="rating-container">
                                <button class="rating-btn <?php echo $laptop['user_liked'] ? 'active-like' : ''; ?>" onclick="rateLaptop(<?php echo $laptop['id']; ?>, 'like')">
                                    <i class="fas fa-thumbs-up"></i> Me gusta
                                    <span class="rating-count"><?php echo $laptop['likes']; ?></span>
                                </button>
                                <button class="rating-btn <?php echo $laptop['user_disliked'] ? 'active-dislike' : ''; ?>" onclick="rateLaptop(<?php echo $laptop['id']; ?>, 'dislike')">
                                    <i class="fas fa-thumbs-down"></i> No me gusta
                                    <span class="rating-count"><?php echo $laptop['dislikes']; ?></span>
                                </button>
                            </div>
                            
                            <?php if (isAdmin()): ?>
                                <p class="laptop-seller" style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">
                                    <i class="fas fa-user"></i> ID: <?php echo $laptop['id']; ?> | Agregado por: <?php echo htmlspecialchars($laptop['vendedor_nombre']); ?>
                                </p>
                            <?php endif; ?>
                            <div class="laptop-actions">
                                <a href="view_laptop.php?id=<?php echo $laptop['id']; ?>" class="btn btn-view">VER DETALLES</a>
                                <button onclick="addToCart(<?php echo $laptop['id']; ?>)" class="btn" style="background: linear-gradient(90deg, var(--success), var(--secondary)); color: white; border: none;">
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
    // Función para mostrar notificaciones
    function showNotification(message, type) {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.className = 'notification ' + type;
        notification.style.display = 'block';
        
        setTimeout(() => {
            notification.style.opacity = 0;
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 300);
        }, 3000);
    }

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
                showNotification('✅ Producto agregado al carrito', 'success');
                updateCartCount();
            } else {
                showNotification('❌ Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('❌ Error al agregar al carrito', 'error');
        });
    }

    // Función para valorar una laptop (like/dislike)
    function rateLaptop(laptopId, type) {
        fetch('rate_laptop.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                laptop_id: laptopId,
                tipo: type
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('✅ Valoración registrada', 'success');
                // Recargar la página para actualizar los contadores
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification('❌ Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('❌ Error al registrar valoración', 'error');
        });
    }

    // Actualizar contador del carrito
    function updateCartCount() {
        fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('cart-count').textContent = data.count || 0;
        })
        .catch(error => {
            console.error('Error al obtener el conteo del carrito:', error);
        });
    }

    // Implementar lazy loading para imágenes
    function initLazyLoading() {
        const lazyImages = document.querySelectorAll('.laptop-image.lazy');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('lazy');
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });

            lazyImages.forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback para navegadores sin IntersectionObserver
            lazyImages.forEach(img => {
                img.src = img.dataset.src || img.src;
                img.classList.remove('lazy');
                img.classList.add('loaded');
            });
        }
    }

    // Cargar contador al iniciar
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
        initLazyLoading();
        
        // Verificar si hay parámetros de éxito/error en la URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success')) {
            showNotification(urlParams.get('success'), 'success');
        }
        if (urlParams.has('error')) {
            showNotification(urlParams.get('error'), 'error');
        }
    });
    </script>
</body>
</html>