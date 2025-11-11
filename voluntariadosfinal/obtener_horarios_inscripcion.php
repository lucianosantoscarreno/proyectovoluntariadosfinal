<?php
require_once 'config.php';

if (!isset($_GET['voluntariado_id'])) {
    echo json_encode(['error' => 'ID de voluntariado no proporcionado']);
    exit();
}

$voluntariado_id = (int)$_GET['voluntariado_id'];
$horarios = obtenerHorariosVoluntariado($voluntariado_id);

$opciones = [];
foreach ($horarios as $horario) {
    $hora_inicio = substr($horario['hora_inicio'], 0, 5);
    $hora_fin = substr($horario['hora_fin'], 0, 5);
    $opciones[] = [
        'valor' => $horario['dia_semana'] . ' - ' . $hora_inicio . ' a ' . $hora_fin,
        'texto' => $horario['dia_semana'] . ' de ' . $hora_inicio . ' a ' . $hora_fin
    ];
}

header('Content-Type: application/json');
echo json_encode(['horarios' => $opciones]);
?>