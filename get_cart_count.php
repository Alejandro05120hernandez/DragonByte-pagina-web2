<?php
session_start();
include "db.php";

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user"])) {
    echo json_encode(['count' => 0]);
    exit();
}

$usuario_id = $_SESSION['user'];

try {
    // Obtener la cantidad total de items en el carrito
    $stmt = $conn->prepare("SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $count = $result['total'] ?? 0;
    
    echo json_encode(['count' => (int)$count]);

} catch (Exception $e) {
    echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
}

$conn->close();
?>