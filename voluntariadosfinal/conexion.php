<?php
require_once 'config.php';

function conectarBaseDatos() {
    return getDBConnection();
}

function obtenerUsuario($conn, $id) {
    $id = (int)$id; // Sanitizar ID
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Función para hash de contraseñas
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Función para verificar contraseña
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}
?>  