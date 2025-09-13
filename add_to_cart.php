<?php
session_start();
include "db.php";

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user"])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['laptop_id']) || !isset($input['cantidad'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$laptop_id = (int)$input['laptop_id'];
$cantidad = (int)$input['cantidad'];
$usuario_id = $_SESSION['user'];

// Validar que la cantidad sea positiva
if ($cantidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'La cantidad debe ser mayor a 0']);
    exit();
}

// Verificar que la laptop existe y está activa
$stmt = $conn->prepare("SELECT id, nombre, precio FROM laptops WHERE id = ? AND activo = 1");
$stmt->bind_param("i", $laptop_id);
$stmt->execute();
$laptop = $stmt->get_result()->fetch_assoc();

if (!$laptop) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    exit();
}

try {
    // Verificar si el producto ya está en el carrito
    $check_stmt = $conn->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND laptop_id = ?");
    $check_stmt->bind_param("ii", $usuario_id, $laptop_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();

    if ($existing) {
        // Actualizar cantidad existente
        $nueva_cantidad = $existing['cantidad'] + $cantidad;
        $update_stmt = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $nueva_cantidad, $existing['id']);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insertar nuevo item al carrito
        $insert_stmt = $conn->prepare("INSERT INTO carrito (usuario_id, laptop_id, cantidad) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iii", $usuario_id, $laptop_id, $cantidad);
        $insert_stmt->execute();
        $insert_stmt->close();
    }

    $check_stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Producto agregado al carrito']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al agregar al carrito: ' . $e->getMessage()]);
}

$conn->close();
?>