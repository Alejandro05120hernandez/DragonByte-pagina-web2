<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}
include "db.php";

if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    $id = intval($_GET["id"]);
    $user_id = $_SESSION["user"];
    
    // Verificar que el usuario es el propietario antes de eliminar
    $stmt = $conn->prepare("SELECT imagen FROM laptops WHERE id = ? AND vendedor = ?");
    $stmt->bind_param("is", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $laptop = $result->fetch_assoc();
        
        // Eliminar la imagen del servidor
        if (file_exists($laptop["imagen"])) {
            unlink($laptop["imagen"]);
        }
        
        // Eliminar de la base de datos
        $stmt2 = $conn->prepare("DELETE FROM laptops WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();
    }
    $stmt->close();
}

header("Location: home.php");
exit();
?>