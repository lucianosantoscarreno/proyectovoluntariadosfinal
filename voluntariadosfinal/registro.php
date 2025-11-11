<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize_input($_POST['nombre']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $telefono = sanitize_input($_POST['telefono']);
    $codigo_pais = sanitize_input($_POST['countryCode']);
    $departamento = sanitize_input($_POST['departamento']);
    
    // Validaciones
    if (!validate_email($email)) {
        $error = "Email inválido";
    } elseif (!validarTelefonoInternacional($telefono, $codigo_pais)) {
        $error = "El número de teléfono no es válido para " . $codigo_pais;
    } elseif (!verificarEmailUnico($email)) {
        $tablas = obtenerTablasConEmail($email);
        $error = "Este correo electrónico ya está registrado como " . implode(' o ', $tablas);
    } else {
        $conn = getDBConnection();
        
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Formatear teléfono completo
        $telefono_completo = formatearTelefonoInternacional($telefono, $codigo_pais);
        
        // Insertar nuevo usuario
        $sql_insert = "INSERT INTO usuarios (nombre, email, password, telefono, departamento) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sssss", $nombre, $email, $password_hash, $telefono_completo, $departamento);
        
        if ($stmt_insert->execute()) {
            $usuario_id = $conn->insert_id;
            
            $_SESSION['voluntario_id'] = $usuario_id;
            $_SESSION['voluntario_nombre'] = $nombre;
            $_SESSION['voluntario_email'] = $email;
            
            log_action("Nuevo registro voluntario: $email", $usuario_id);
            
            $stmt_insert->close();
            $conn->close();
            
            header("Location: voluntariados.php");
            exit();
        } else {
            $error = "Error al registrar el usuario: " . $conn->error;
        }
        
        $stmt_insert->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro Voluntario</title>
  <link rel="stylesheet" href="estilos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="imagenes/logosinfondo.png">
</head>
<body>
  <?php include 'header.php'; ?>

  <section class="form-container">
    <div class="auth-form">
      <h1>Registro Voluntario</h1>
      
      <?php if (isset($error)): ?>
        <div class="error-message">
          <?php echo $error; ?>
        </div>
      <?php endif; ?>
      
      <form method="POST">
        <div class="form-group with-icon">
          <i class="fas fa-user icon-left"></i>
          <input type="text" id="nombre" name="nombre" required 
                 placeholder="Nombre Completo"
                 maxlength="50"
                 value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
        </div>
        
        <div class="form-group with-icon">
          <i class="fas fa-envelope icon-left"></i>
          <input type="email" id="email" name="email" required 
                 placeholder="Correo Electrónico"
                 maxlength="60"
                 value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group with-icon password-group">
          <i class="fas fa-lock icon-left"></i>
          <div class="password-input-wrapper">
            <input type="password" id="password" name="password" required 
                   minlength="6" placeholder="Mínimo 6 caracteres"
                   maxlength="25"
                   style="padding-left: 45px; padding-right: 45px;">
            <button type="button" class="toggle-password" data-target="password" aria-label="Mostrar contraseña">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        
        <div class="form-group">
          <label for="telefono">Teléfono</label>
          <div class="phone-input-wrapper">
            <div class="country-selector">
              <div class="selected-country">
                <span class="flag">🇺🇾</span>
                <span class="country-code">+598</span>
                <span class="dropdown-arrow">▼</span>
              </div>
              <div class="country-dropdown">
                <div class="country-option" data-code="+598" data-flag="🇺🇾">
                  <span class="flag">🇺🇾</span>
                  <span class="country-name">Uruguay</span>
                  <span class="country-code-option">+598</span>
                </div>
                <div class="country-option" data-code="+54" data-flag="🇦🇷">
                  <span class="flag">🇦🇷</span>
                  <span class="country-name">Argentina</span>
                  <span class="country-code-option">+54</span>
                </div>
                <div class="country-option" data-code="+55" data-flag="🇧🇷">
                  <span class="flag">🇧🇷</span>
                  <span class="country-name">Brasil</span>
                  <span class="country-code-option">+55</span>
                </div>
                <div class="country-option" data-code="+56" data-flag="🇨🇱">
                  <span class="flag">🇨🇱</span>
                  <span class="country-name">Chile</span>
                  <span class="country-code-option">+56</span>
                </div>
                <div class="country-option" data-code="+595" data-flag="🇵🇾">
                  <span class="flag">🇵🇾</span>
                  <span class="country-name">Paraguay</span>
                  <span class="country-code-option">+595</span>
                </div>
                <div class="country-option" data-code="+51" data-flag="🇵🇪">
                  <span class="flag">🇵🇪</span>
                  <span class="country-name">Perú</span>
                  <span class="country-code-option">+51</span>
                </div>
                <div class="country-option" data-code="+57" data-flag="🇨🇴">
                  <span class="flag">🇨🇴</span>
                  <span class="country-name">Colombia</span>
                  <span class="country-code-option">+57</span>
                </div>
              </div>
            </div>
            <input type="tel" 
                   id="telefono" 
                   name="telefono" 
                   required 
                   placeholder="9 1234 5678"
                   pattern="[0-9]{8,9}"
                   maxlength="12"
                   inputmode="numeric"
                   value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
          </div>
          <input type="hidden" id="countryCode" name="countryCode" value="+598">
          <small class="phone-hint">Selecciona tu país e ingresa tu número</small>
        </div>
        
        <div class="form-group">
          <label for="departamento">Departamento</label>
          <select id="departamento" name="departamento" required>
            <option value="">-- Seleccioná --</option>
            <?php 
            $departamentos = obtenerDepartamentosUruguay();
            $selected_depto = $_POST['departamento'] ?? '';
            foreach ($departamentos as $depto): 
            ?>
            <option value="<?php echo $depto; ?>" <?php echo $selected_depto == $depto ? 'selected' : ''; ?>>
              <?php echo $depto; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <button type="submit" class="btn-primary">Registrarse</button>
      </form>
      
      <p style="text-align: center; margin-top: 20px;">
        ¿Ya tienes cuenta? <a href="login.php" style="color: #2e7d32; font-weight: 600;">Inicia sesión aquí</a>
      </p>
      
      <p style="text-align: center; margin-top: 10px;">
        ¿Eres una organización? <a href="sumate.php" style="color: #2196F3; font-weight: 600;">Registra tu organización aquí</a>
      </p>
    </div>
  </section>

  <?php include 'footer.php'; ?>
  <script src="country_selector.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>