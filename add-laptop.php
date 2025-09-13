<?php
session_start();
include "db.php";
include "auth.php";

// Verificar que esté logueado y sea administrador
requireLogin();
requireAdmin();

// Procesar formulario al enviar
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $precio = $_POST["precio"];
    $descripcion = trim($_POST["descripcion"]);
    $vendedor = $_SESSION["user"];
    $url_imagen = trim($_POST["url_imagen"] ?? "");
    
    // Validaciones básicas
    if (empty($nombre) || empty($precio) || empty($descripcion)) {
        $mensaje = "⚠️ Todos los campos son obligatorios.";
        $tipo = "error";
    } elseif (!is_numeric($precio) || $precio <= 0) {
        $mensaje = "⚠️ El precio debe ser un número válido mayor a 0.";
        $tipo = "error";
    } else {
        $imagen = "";
        $uploadOk = 1;
        
        // Verificar si se proporcionó URL de imagen
        if (!empty($url_imagen)) {
            // Validar que sea una URL válida
            if (filter_var($url_imagen, FILTER_VALIDATE_URL)) {
                // Verificar que la URL termine en una extensión de imagen
                $extension = strtolower(pathinfo(parse_url($url_imagen, PHP_URL_PATH), PATHINFO_EXTENSION));
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $imagen = $url_imagen;
                    $uploadOk = 1;
                } else {
                    $mensaje = "⚠️ La URL debe terminar en .jpg, .jpeg, .png o .gif";
                    $tipo = "error";
                    $uploadOk = 0;
                }
            } else {
                $mensaje = "⚠️ URL de imagen no válida.";
                $tipo = "error";
                $uploadOk = 0;
            }
        } 
        // Si no hay URL, procesar archivo subido
        elseif (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] == 0) {
            // Carpeta para imágenes
            $carpeta = "uploads/";
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0777, true);
            }

            // Subir imagen
            $nombreImagen = basename($_FILES["imagen"]["name"]);
            $imagen = $carpeta . uniqid() . "_" . $nombreImagen;
            $tipoArchivo = strtolower(pathinfo($imagen, PATHINFO_EXTENSION));

            // Validar que sea imagen
            $check = getimagesize($_FILES["imagen"]["tmp_name"]);
            if($check === false) {
                $mensaje = "⚠️ El archivo no es una imagen.";
                $tipo = "error";
                $uploadOk = 0;
            }

            // Limitar extensiones
            if(!in_array($tipoArchivo, ["jpg","jpeg","png","gif"])) {
                $mensaje = "⚠️ Solo se permiten imágenes JPG, JPEG, PNG o GIF.";
                $tipo = "error";
                $uploadOk = 0;
            }

            // Limitar tamaño (máximo 2MB)
            if ($_FILES["imagen"]["size"] > 2000000) {
                $mensaje = "⚠️ La imagen es demasiado grande. Máximo 2MB.";
                $tipo = "error";
                $uploadOk = 0;
            }
        } else {
            $mensaje = "⚠️ Debes proporcionar una imagen (archivo o URL).";
            $tipo = "error";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            // Si es archivo local, moverlo
            if (!empty($_FILES["imagen"]["tmp_name"])) {
                if (!move_uploaded_file($_FILES["imagen"]["tmp_name"], $imagen)) {
                    $mensaje = "⚠️ Error al subir la imagen.";
                    $tipo = "error";
                    $uploadOk = 0;
                }
            }
            
            if ($uploadOk == 1) {
                // Guardar en BD usando consultas preparadas
                $stmt = $conn->prepare("INSERT INTO laptops (nombre, precio, descripcion, imagen, vendedor) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sdsss", $nombre, $precio, $descripcion, $imagen, $vendedor);
                
                if ($stmt->execute()) {
                    $mensaje = "✅ Laptop agregada correctamente.";
                    $tipo = "exito";
                    // Limpiar campos después de éxito
                    $nombre = $precio = $descripcion = $url_imagen = "";
                } else {
                    $mensaje = "⚠️ Error al guardar en la base de datos: " . $conn->error;
                    $tipo = "error";
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Laptop - DragonTech</title>
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
            <a href="admin.php">PANEL ADMIN</a>
            <a href="add-laptop.php" class="active">AGREGAR LAPTOP</a>
            <a href="logout.php">CERRAR SESIÓN</a>
        </nav>
        <a href="admin.php" class="add-btn">← VOLVER AL PANEL</a>
    </header>

    <div class="container">
        <div class="form-container">
            <h2 class="section-title">AGREGAR LAPTOP</h2>
            
            <form method="post" enctype="multipart/form-data">
                <label>NOMBRE DE LA LAPTOP:</label>
                <input type="text" name="nombre" value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" required>

                <label>PRECIO:</label>
                <input type="number" step="0.01" name="precio" value="<?php echo isset($precio) ? htmlspecialchars($precio) : ''; ?>" required>

                <label>DESCRIPCIÓN:</label>
                <textarea name="descripcion" required><?php echo isset($descripcion) ? htmlspecialchars($descripcion) : ''; ?></textarea>

                <label>IMAGEN:</label>
                <div class="image-options">
                    <div class="option">
                        <input type="radio" id="option-file" name="image_option" value="file" checked onchange="toggleImageOption()">
                        <label for="option-file">📁 Subir archivo</label>
                    </div>
                    <div class="option">
                        <input type="radio" id="option-url" name="image_option" value="url" onchange="toggleImageOption()">
                        <label for="option-url">🌐 URL de imagen</label>
                    </div>
                </div>
                <input type="file" id="imagen-file" name="imagen" accept="image/*">
                <input type="url" id="imagen-url" name="url_imagen" placeholder="https://ejemplo.com/imagen.jpg" value="<?php echo htmlspecialchars($url_imagen ?? ''); ?>" style="display: none;">
                <small>📌 Formatos permitidos: JPG, PNG, GIF. Para archivos: máximo 2MB</small>

                <button type="submit">GUARDAR LAPTOP</button>
            </form>

            <?php if(isset($mensaje)): ?>
                <div class="mensaje <?php echo $tipo; ?>"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <a href="home.php" class="volver">← VOLVER AL INICIO</a>
        </div>
    </div>

    <script>
    function toggleImageOption() {
        const fileOption = document.getElementById('option-file');
        const urlOption = document.getElementById('option-url');
        const fileInput = document.getElementById('imagen-file');
        const urlInput = document.getElementById('imagen-url');
        
        if (fileOption.checked) {
            fileInput.style.display = 'block';
            urlInput.style.display = 'none';
            fileInput.required = true;
            urlInput.required = false;
        } else {
            fileInput.style.display = 'none';
            urlInput.style.display = 'block';
            fileInput.required = false;
            urlInput.required = true;
        }
    }
    
    // Validar URL en tiempo real
    document.getElementById('imagen-url').addEventListener('input', function() {
        const url = this.value;
        const validExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (url) {
            try {
                const parsedUrl = new URL(url);
                const path = parsedUrl.pathname;
                const extension = path.split('.').pop().toLowerCase();
                
                if (validExtensions.includes(extension)) {
                    this.style.border = '2px solid #00ff00';
                } else {
                    this.style.border = '2px solid #ff6b6b';
                }
            } catch {
                this.style.border = '2px solid #ff6b6b';
            }
        } else {
            this.style.border = '';
        }
    });
    </script>
</body>
</html>