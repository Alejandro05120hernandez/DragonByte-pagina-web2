<?php
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Validaciones
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Todos los campos son obligatorios.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Verificar si el usuario ya existe
        $check_stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "El usuario o correo electrónico ya existe.";
        } else {
            // Hash de la contraseña
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $stmt = $conn->prepare("INSERT INTO usuarios (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                header("Location: index.php?registered=1");
                exit();
            } else {
                $error = "Error: " . $conn->error;
            }
            
            $stmt->close();
        }
        
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - DragonTech</title>
    <link rel="stylesheet" href="styles/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div style="text-align: center; margin-bottom: 2rem;">
            <!-- Logo personalizado -->
            <img src="uploads/logo.png.jpg" alt="Logo DragonTech" style="max-width: 150px; margin-bottom: 1rem;">
            
            <h2 style="color: var(--primary); text-shadow: 0 0 10px var(--primary); font-family: 'Orbitron', sans-serif;">
                DRAGONTECH
            </h2>
        </div>
        
        <h3 style="text-align: center; margin-bottom: 2rem; color: var(--text-secondary);">CREAR CUENTA</h3>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Usuario" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <input type="password" name="confirm_password" placeholder="Confirmar contraseña" required>
            <button type="submit">REGISTRAR</button>
        </form>
        
        <p style="text-align: center; margin-top: 1.5rem; color: var(--text-muted);">
            ¿Ya tienes cuenta? <a href="index.php" style="color: var(--secondary); text-decoration: none; text-shadow: 0 0 5px var(--secondary);">INICIAR SESIÓN</a>
        </p>
        
        <?php if (!empty($error)) echo "<div class='mensaje error'>$error</div>"; ?>
    </div>
</body>
</html>
