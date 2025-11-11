<?php
require_once 'config.php';

// Si ya está logueado, redirigir
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header("Location: admin.php");
    exit();
} elseif (isset($_SESSION['voluntario_id'])) {
    header("Location: voluntariados.php");
    exit();
} elseif (isset($_SESSION['organizacion_id'])) {
    header("Location: panel_organizacion.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // PRIMERO intentar como administrador
    $admin = verificarAdministrador($email, $password);
    if ($admin) {
        $_SESSION['admin'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        header("Location: admin.php");
        exit();
    }
    
    // LUEGO intentar como organización
    $organizacion = verificarOrganizacion($email, $password);
    if ($organizacion) {
        $_SESSION['organizacion_id'] = $organizacion['id'];
        $_SESSION['organizacion_nombre'] = $organizacion['nombre'];
        $_SESSION['organizacion_email'] = $organizacion['email'];
        $_SESSION['organizacion_contacto'] = $organizacion['contacto'];
        header("Location: panel_organizacion.php");
        exit();
    }
    
    // FINALMENTE intentar como voluntario
    $conn = getDBConnection();
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        if (password_verify($password, $usuario['password'])) {
            $_SESSION['voluntario_id'] = $usuario['id'];
            $_SESSION['voluntario_nombre'] = $usuario['nombre'];
            $_SESSION['voluntario_email'] = $usuario['email'];
            header("Location: voluntariados.php");
            exit();
        } else {
            $error = "Email o contraseña incorrectos";
        }
    } else {
        $error = "Email o contraseña incorrectos";
    }
    
    if (isset($stmt) && is_object($stmt)) {
        $stmt->close();
    }
    if (isset($conn) && is_object($conn)) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión - Voluntariados Uruguay</title>
  <link rel="stylesheet" href="estilos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="imagenes/logosinfondo.png">
</head>
<body>
  <?php include 'header.php'; ?>

  <section class="form-container">
    <div class="auth-form">
      <h1>Iniciar Sesión</h1>
      
      <?php if (!empty($error)): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
      <?php endif; ?>
      
      <form method="POST">
        <div class="form-group with-icon">
          <i class="fas fa-envelope icon-left"></i>
          <input type="email" id="email" name="email" placeholder="Correo Electrónico" required 
                 value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <div class="password-group">
          <i class="fas fa-lock icon-left"></i>
          <div class="password-input-wrapper">
            <input type="password" id="password" name="password" placeholder="Contraseña" required>
            <button type="button" class="toggle-password" data-target="password" aria-label="Mostrar contraseña">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        
        <button type="submit" class="btn-primary">
          Ingresar
        </button>
      </form>
      
      <p style="text-align: center; margin-top: 20px;">
        ¿No tienes cuenta? <a href="registro.php" style="color: #2e7d32; font-weight: 600;">
        Regístrate como voluntario</a>
        o <a href="sumate.php" style="color: #2196F3; font-weight: 600;">
        registra tu organización</a>
      </p>
    </div>
  </section>

  <?php include 'footer.php'; ?>
  <script src="password-toggle.js"></script>
</body>
</html>