<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "dragontech_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error en conexión: " . $conn->connect_error);
}

// Configurar charset para prevenir problemas con caracteres especiales
$conn->set_charset("utf8mb4");
?>