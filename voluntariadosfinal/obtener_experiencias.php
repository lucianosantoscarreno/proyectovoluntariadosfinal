<?php
require_once 'config.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $voluntariado_id = (int)$_GET['id'];
    
    $experiencias = obtenerExperiencias($voluntariado_id);
    $stats = obtenerPromedioCalificaciones($voluntariado_id);
    
    $puede_comentar = false;
    if (isset($_SESSION['voluntario_id'])) {
        $puede_comentar = puedeComentar($voluntariado_id, $_SESSION['voluntario_id']);
    }
    
    echo json_encode([
        'experiencias' => $experiencias,
        'stats' => $stats,
        'puede_comentar' => $puede_comentar,
        'voluntariado_id' => $voluntariado_id
    ]);
} else {
    echo json_encode(['error' => 'ID no proporcionado']);
}
?>