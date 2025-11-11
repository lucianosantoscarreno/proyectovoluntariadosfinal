<?php
require_once 'config.php';

// Verificar si ya está logueado como admin
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header("Location: admin.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Verificar administrador en la base de datos
    $admin = verificarAdministrador($email, $password);
    
    if ($admin) {
        // Establecer sesión de administrador
        $_SESSION['admin'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        $_SESSION['login_time'] = time();
        
        log_action("Login administrador exitoso: {$admin['email']}");
        
        // Redirección
        header("Location: admin.php");
        exit();
    } else {
        $error = "Credenciales de administrador incorrectas";
        log_action("Intento de login administrativo fallido: $email");
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Voluntariados Uruguay</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" type="image/png" href="imagenes/logosinfondo.png">
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="form-container">
        <div class="auth-form">
            <h1>🔐 Acceso Administrativo</h1>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">📧 Correo Electrónico</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="admin@tugranitodearena.com">
                </div>
                
                <div class="form-group">
                    <label for="password">🔑 Contraseña</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Ingresa tu contraseña">
                </div>
                
                <button type="submit" class="btn-primary" style="background: #ff5722; border-color: #ff5722;">
                    🛠️ Ingresar al Panel Admin
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <p style="margin: 0; color: #666; font-size: 0.9rem;">
                    <strong>Credenciales por defecto:</strong><br>
                    Email: <code>admin@tugranitodearena.com</code><br>
                    Contraseña: <code>password</code>
                </p>
            </div>
            
            <p style="text-align: center; margin-top: 20px;">
                <a href="login.php" style="color: #666; font-size: 0.9rem;">← Volver al login general</a>
            </p>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>