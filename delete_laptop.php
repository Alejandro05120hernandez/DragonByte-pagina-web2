<?php
session_start();
include "db.php";
include "auth.php";

// Verificar que esté logueado y sea administrador
requireLogin();
requireAdmin();

if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    $id = intval($_GET["id"]);
    
    // Como es admin, puede eliminar cualquier laptop
    $stmt = $conn->prepare("SELECT imagen FROM laptops WHERE id = ?");
    $stmt->bind_param("i", $id);
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