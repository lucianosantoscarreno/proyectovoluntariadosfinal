<?php
require_once 'config.php';

// Verificar si es administrador o organización
if (!isset($_SESSION['admin']) && !isset($_SESSION['organizacion_id'])) {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

if (!isset($_GET['voluntariado_id'])) {
    echo json_encode(['error' => 'ID de voluntariado no proporcionado']);
    exit();
}

$voluntariado_id = (int)$_GET['voluntariado_id'];

// Si es organización, verificar que el voluntariado le pertenece
if (isset($_SESSION['organizacion_id'])) {
    $conn = getDBConnection();
    $sql_check = "SELECT id FROM voluntariados WHERE id = ? AND organizacion_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $voluntariado_id, $_SESSION['organizacion_id']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        header("HTTP/1.1 403 Forbidden");
        echo json_encode(['error' => 'No tienes permisos para acceder a este voluntariado']);
        exit();
    }
    $stmt_check->close();
    $conn->close();
}

$horarios = obtenerHorariosVoluntariado($voluntariado_id);

header('Content-Type: application/json');
echo json_encode(['horarios' => $horarios]);
?>