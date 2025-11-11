<?php
require_once 'config.php';

// Verificar si el usuario es administrador o organización
if (!isset($_SESSION['admin']) && !isset($_SESSION['organizacion_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn = getDBConnection();
    
    // Si es organización, verificar que el voluntariado le pertenece
    if (isset($_SESSION['organizacion_id'])) {
        $organizacion_id = $_SESSION['organizacion_id'];
        $sql = "SELECT * FROM voluntariados WHERE id = ? AND organizacion_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $organizacion_id);
    } else {
        // Si es admin, puede ver cualquier voluntariado
        $sql = "SELECT * FROM voluntariados WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $voluntariado = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($voluntariado);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Voluntariado no encontrado']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'ID no proporcionado']);
}
?>