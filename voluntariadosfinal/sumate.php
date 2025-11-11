<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize_input($_POST['nombre_org']);
    $email = sanitize_input($_POST['email_org']);
    $password = $_POST['password_org'];
    $contacto = sanitize_input($_POST['contacto_org']);
    $telefono = sanitize_input($_POST['telefono']);
    $codigo_pais = sanitize_input($_POST['countryCode']);
    $departamento = sanitize_input($_POST['departamento_org']);
    $tipo_voluntariado = sanitize_input($_POST['tipo_org']);
    $descripcion = sanitize_input($_POST['descripcion_org']);
    $necesidades = sanitize_input($_POST['necesidades_org']);
    $horarios = sanitize_input($_POST['horarios_org']);
    
    // Validaciones
    if (!validate_email($email)) {
        $error = "Email inválido";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
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
        
        // Insertar nueva organización
        $sql_insert = "INSERT INTO organizaciones (nombre, email, password_hash, contacto, telefono, departamento, tipo_voluntariado, descripcion, necesidades, horarios) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ssssssssss", $nombre, $email, $password_hash, $contacto, $telefono_completo, $departamento, $tipo_voluntariado, $descripcion, $necesidades, $horarios);
        
        if ($stmt_insert->execute()) {
            $organizacion_id = $conn->insert_id;
            log_action("Nueva organización registrada: $nombre", $organizacion_id);
            
            $stmt_insert->close();
            $conn->close();
            
            // Iniciar sesión automáticamente
            $_SESSION['organizacion_id'] = $organizacion_id;
            $_SESSION['organizacion_nombre'] = $nombre;
            $_SESSION['organizacion_email'] = $email;
            $_SESSION['organizacion_contacto'] = $contacto;
            
            header("Location: panel_organizacion.php");
            exit();
        } else {
            $error = "Error al registrar la organización: " . $conn->error;
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
  <title>Sumate - Voluntariados Uruguay</title>
  <link rel="stylesheet" href="estilos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="imagenes/logosinfondo.png">
</head>
<body>
  <?php include 'header.php'; ?>

  <section class="form-container">
    <div class="auth-form">
      <h1>¿Sos una ONG o proyecto social?</h1>
      <p>
        Registrá tu organización para publicar oportunidades de voluntariado y conectar con voluntarios en todo Uruguay.
      </p>

      <?php if (!empty($error)): ?>
        <div class="error-message">
          ❌ <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group with-icon">
          <i class="fas fa-building icon-left"></i>
          <input type="text" id="nombre_org" name="nombre_org" required 
                 placeholder="Nombre de la ONG / Proyecto"
                 maxlength="50"
                 value="<?php echo isset($_POST['nombre_org']) ? htmlspecialchars($_POST['nombre_org']) : ''; ?>" />
        </div>

        <div class="form-group with-icon">
          <i class="fas fa-envelope icon-left"></i>
          <input type="email" id="email_org" name="email_org" required 
                 placeholder="Correo electrónico de la organización"
                 maxlength="60"
                 value="<?php echo isset($_POST['email_org']) ? htmlspecialchars($_POST['email_org']) : ''; ?>" />
        </div>

        <div class="form-group with-icon password-group">
          <i class="fas fa-lock icon-left"></i>
          <div class="password-input-wrapper">
            <input type="password" id="password_org" name="password_org" required 
                   minlength="6" placeholder="Mínimo 6 caracteres"
                   maxlength="25"
                   style="padding-left: 45px; padding-right: 45px;">
            <button type="button" class="toggle-password" data-target="password_org" aria-label="Mostrar contraseña">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="form-group with-icon">
          <i class="fas fa-user icon-left"></i>
          <input type="text" id="contacto_org" name="contacto_org" required 
                 placeholder="Persona de contacto"
                 value="<?php echo isset($_POST['contacto_org']) ? htmlspecialchars($_POST['contacto_org']) : ''; ?>" />
        </div>

        <div class="form-group">
          <label for="telefono">Teléfono de contacto</label>
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
          <label for="departamento_org">Departamento donde actúan</label>
          <select id="departamento_org" name="departamento_org" required>
            <option value="">-- Seleccioná --</option>
            <?php 
            $departamentos = obtenerDepartamentosUruguay();
            $selected_depto = $_POST['departamento_org'] ?? '';
            foreach ($departamentos as $depto): 
            ?>
            <option value="<?php echo $depto; ?>" <?php echo $selected_depto == $depto ? 'selected' : ''; ?>>
              <?php echo $depto; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="tipo_org">Área principal de trabajo</label>
          <select id="tipo_org" name="tipo_org" required>
            <option value="">-- Seleccioná --</option>
            <?php 
            $tipos = obtenerTiposVoluntariado();
            $selected_tipo = $_POST['tipo_org'] ?? '';
            foreach ($tipos as $tipo): 
            ?>
            <option value="<?php echo $tipo; ?>" <?php echo $selected_tipo == $tipo ? 'selected' : ''; ?>>
              <?php echo $tipo; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="descripcion_org">Descripción de la organización</label>
          <textarea id="descripcion_org" name="descripcion_org" rows="4" placeholder="Contanos sobre tu organización, misión y valores" required><?php echo isset($_POST['descripcion_org']) ? htmlspecialchars($_POST['descripcion_org']) : ''; ?></textarea>
        </div>

        <div class="form-group">
          <label for="necesidades_org">Necesidades de voluntariado</label>
          <textarea id="necesidades_org" name="necesidades_org" rows="3" placeholder="¿Qué tipo de apoyo necesitan de los voluntarios?" required><?php echo isset($_POST['necesidades_org']) ? htmlspecialchars($_POST['necesidades_org']) : ''; ?></textarea>
        </div>

        <div class="form-group">
          <label for="horarios_org">Horarios y disponibilidad requerida</label>
          <textarea id="horarios_org" name="horarios_org" rows="2" placeholder="Ej: Lunes a viernes de 9 a 12 hs, fines de semana, etc." required><?php echo isset($_POST['horarios_org']) ? htmlspecialchars($_POST['horarios_org']) : ''; ?></textarea>
        </div>

        <button type="submit" class="btn-primary">Registrar organización</button>
      </form>

      <p style="text-align: center; margin-top: 20px;">
        ¿Ya tienes cuenta? <a href="login.php" style="color: #2196F3; font-weight: 600;">Inicia sesión aquí</a>
      </p>
    </div>
  </section>

  <?php include 'footer.php'; ?>
  <script src="country_selector.js"></script>
  <script src="password-toggle.js"></script>
</body>
</html>