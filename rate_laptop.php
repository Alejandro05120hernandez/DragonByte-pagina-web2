<?php
session_start();
if (!isset($_SESSION["user"])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

include "db.php";
include "auth.php";

// Obtener información del usuario actual
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Obtener datos JSON de la solicitud
$data = json_decode(file_get_contents('php://input'), true);
$laptopId = $data['laptop_id'];
$tipo = $data['tipo'];

// Verificar si el usuario ya ha valorado esta laptop
$stmt = $conn->prepare("SELECT id, tipo FROM valoraciones WHERE laptop_id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $laptopId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$existingRating = $result->fetch_assoc();
$stmt->close();

if ($existingRating) {
    if ($existingRating['tipo'] === $tipo) {
        // Eliminar la valoración existente si es del mismo tipo
        $stmt = $conn->prepare("DELETE FROM valoraciones WHERE id = ?");
        $stmt->bind_param("i", $existingRating['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Actualizar la valoración existente
        $stmt = $conn->prepare("UPDATE valoraciones SET tipo = ? WHERE id = ?");
        $stmt->bind_param("si", $tipo, $existingRating['id']);
        $stmt->execute();
        $stmt->close();
    }
} else {
    // Insertar nueva valoración
    $stmt = $conn->prepare("INSERT INTO valoraciones (laptop_id, usuario_id, tipo) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $laptopId, $userId, $tipo);
    $stmt->execute();
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Valoración registrada']);
?>