<?php
// LOGOUT COMPLETO
session_start();

// Log
error_log("=== LOGOUT EJECUTADO ===");

// Destruir completamente
$_SESSION = array();

// Destruir cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirigir
header("Location: index.php");
exit();
?>