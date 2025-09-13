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
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="dragon-logo" style="font-size: 3rem; margin-bottom: 1rem;">
                <i class="fas fa-dragon"></i>
            </div>
            <h2 style="color: var(--primary); text-shadow: 0 0 10px var(--primary); font-family: 'Orbitron', sans-serif;">DRAGONTECH</h2>
        </div>
        
        <h3 style="text-align: center; margin-bottom: 2rem; color: var(--text-secondary);">INICIAR SESIÓN</h3>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">ACCEDER</button>
        </form>
        
        <p style="text-align: center; margin-top: 1.5rem; color: var(--text-muted);">
            ¿No tienes cuenta? <a href="register.php" style="color: var(--secondary); text-decoration: none; text-shadow: 0 0 5px var(--secondary);">REGÍSTRATE</a>
        </p>
        
        <?php if (!empty($error)) echo "<div class='mensaje error'>$error</div>"; ?>
    </div>
</body>
</html>