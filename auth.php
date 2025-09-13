<?php
// Funciones de autenticación y autorización

// Verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user']);
}

// Verificar si el usuario es administrador
function isAdmin() {
    if (!isset($_SESSION['user'])) {
        return false;
    }
    
    global $conn;
    $stmt = $conn->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user['rol'] === 'admin';
    }
    
    return false;
}

// Redirigir si no es admin
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: home.php?error=no_admin");
        exit();
    }
}

// Redirigir si no está logueado
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

// Obtener información del usuario actual
function getCurrentUser() {
    if (!isset($_SESSION['user'])) {
        return null;
    }
    
    global $conn;
    $stmt = $conn->prepare("SELECT id, username, email, rol FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}
?>