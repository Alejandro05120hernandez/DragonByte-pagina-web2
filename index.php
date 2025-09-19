<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, username, password, rol FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user["password"])) {
                $_SESSION["user"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["rol"] = $user["rol"];
                header("Location: home.php");
                exit();
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "Usuario no encontrado.";
        }
        $stmt->close();
    } else {
        $error = "Por favor, complete todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DragonTech</title>
    <link rel="stylesheet" href="styles/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .logo-img {
            max-width: 150px; /* mismo tamaño que en registro */
            height: auto;
            display: block;
            margin: 0 auto 1rem;
        }
        .dragon-logo {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            text-align: center;
            display: none;
        }
        .brand-text {
            color: var(--primary); 
            text-shadow: 0 0 5px var(--primary); 
            font-family: 'Orbitron', sans-serif; 
            font-size: 1.4rem;
            margin: 0 0 1.5rem 0;
        }
        .login-container {
            max-width: 400px;
            padding: 1.5rem;
            margin: 2rem auto;
        }
        input, button {
            padding: 0.6rem;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div style="text-align: center; margin-bottom: 1rem;">
            <img src="uploads/logo.png.jpg" alt="DragonTech Logo" class="logo-img" onerror="this.style.display='none'; document.querySelector('.dragon-logo').style.display='block';">
            <div class="dragon-logo">
                <i class="fas fa-dragon"></i>
            </div>
            <h2 class="brand-text">DRAGONTECH</h2>
        </div>
        
        <h3 style="text-align: center; margin-bottom: 1.5rem; color: var(--text-secondary); font-size: 1.1rem;">INICIAR SESIÓN</h3>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">ACCEDER</button>
        </form>
        
        <p style="text-align: center; margin-top: 1.2rem; color: var(--text-muted); font-size: 0.9rem;">
            ¿No tienes cuenta? <a href="register.php" style="color: var(--secondary); text-decoration: none; text-shadow: 0 0 3px var(--secondary);">REGÍSTRATE</a>
        </p>
        
        <?php if (!empty($error)) echo "<div class='mensaje error'>$error</div>"; ?>
    </div>
</body>
</html>
