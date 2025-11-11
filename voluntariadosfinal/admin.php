<?php
require_once 'config.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Conexión a la base de datos
$conexion = getDBConnection();

// Generar token CSRF
$csrf_token = generate_csrf_token();

// Procesar acciones del administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Token de seguridad inválido';
        header("Location: admin.php");
        exit();
    }
    
    $action = sanitize_input($_POST['action']);
    $id = (int)$_POST['id'];
    $type = sanitize_input($_POST['type']);
    
    if ($action === 'delete') {
        $success = false;
        
        if ($type === 'usuario') {
            // Eliminar inscripciones primero
            $stmt_inscripciones = $conexion->prepare("DELETE FROM inscripciones WHERE usuario_id = ?");
            $stmt_inscripciones->bind_param("i", $id);
            $stmt_inscripciones->execute();
            $stmt_inscripciones->close();
            
            // Luego eliminar el usuario
            $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id);
            $success = $stmt->execute();
            $stmt->close();
            
            log_action("Eliminado usuario ID: $id", $_SESSION['admin_email']);
            
        } elseif ($type === 'organizacion') {
            // Eliminar organización y sus voluntariados
            $success = eliminarOrganizacion($id);
            log_action("Eliminada organización ID: $id", $_SESSION['admin_email']);
            
        } elseif ($type === 'inscripcion') {
            $stmt = $conexion->prepare("DELETE FROM inscripciones WHERE id = ?");
            $stmt->bind_param("i", $id);
            $success = $stmt->execute();
            $stmt->close();
            
            log_action("Eliminada inscripción ID: $id", $_SESSION['admin_email']);
            
        } elseif ($type === 'voluntariado') {
            $stmt = $conexion->prepare("DELETE FROM voluntariados WHERE id = ?");
            $stmt->bind_param("i", $id);
            $success = $stmt->execute();
            $stmt->close();
            
            log_action("Eliminado voluntariado ID: $id", $_SESSION['admin_email']);
            
        } elseif ($type === 'experiencia') {
            $success = eliminarExperiencia($id);
            log_action("Eliminada experiencia ID: $id", $_SESSION['admin_email']);
        }
        
        if ($success) {
            $_SESSION['success_message'] = "Registro eliminado exitosamente";
        } else {
            $_SESSION['error_message'] = "Error al eliminar registro";
        }
        
    } elseif ($action === 'approve_voluntariado') {
        // Aprobar voluntariado
        $success = aprobarVoluntariado($id, true);
        if ($success) {
            $_SESSION['success_message'] = "Voluntariado aprobado exitosamente";
        } else {
            $_SESSION['error_message'] = "Error al aprobar voluntariado";
        }
        
    } elseif ($action === 'reject_voluntariado') {
        // Rechazar voluntariado (eliminarlo)
        $stmt = $conexion->prepare("DELETE FROM voluntariados WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            $_SESSION['success_message'] = "Voluntariado rechazado y eliminado";
        } else {
            $_SESSION['error_message'] = "Error al rechazar voluntariado";
        }
    }
    
    header("Location: admin.php");
    exit();
}

// Obtener datos de la base de datos
$usuarios = $conexion->query("SELECT * FROM usuarios ORDER BY fecha_registro DESC");
$organizaciones = $conexion->query("SELECT * FROM organizaciones ORDER BY fecha_creacion DESC");
$inscripciones = $conexion->query("SELECT i.*, u.nombre as nombre_voluntario, u.email as email_voluntario, u.telefono as telefono_voluntario FROM inscripciones i LEFT JOIN usuarios u ON i.usuario_id = u.id ORDER BY i.fecha_inscripcion DESC");
$voluntariados_pendientes = obtenerVoluntariadosPendientes();
$experiencias = obtenerTodasLasExperiencias();

// Estadísticas
$total_usuarios = $usuarios->num_rows;
$total_organizaciones = $organizaciones->num_rows;
$total_inscripciones = $inscripciones->num_rows;
$total_pendientes = count($voluntariados_pendientes);
$total_experiencias = count($experiencias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin - Voluntariados Uruguay</title>
  <link rel="stylesheet" href="estilos.css">
  <link rel="icon" type="image/png" href="imagenes/logosinfondo.png">
</head>
<body>
  <?php include 'header.php'; ?>

  <section class="admin-container">
    <div class="admin-header">
      <h1 class="section-title">Panel de Administración</h1>
    </div>
    
    <div class="welcome-card">
      <strong>Bienvenido, <?php echo $_SESSION['admin_nombre']; ?> (<?php echo $_SESSION['admin_email']; ?>)</strong> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="success-message">
            ✅ <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message">
            ❌ <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <div class="admin-stats">
      <div class="admin-stat">
        <h3>Total Voluntarios</h3>
        <span><?php echo $total_usuarios; ?></span>
      </div>
      <div class="admin-stat">
        <h3>Organizaciones</h3>
        <span><?php echo $total_organizaciones; ?></span>
      </div>
      <div class="admin-stat">
        <h3>Inscripciones</h3>
        <span><?php echo $total_inscripciones; ?></span>
      </div>
      <div class="admin-stat">
        <h3>Voluntariados Pendientes</h3>
        <span style="color: <?php echo $total_pendientes > 0 ? '#ff9800' : '#4caf50'; ?>">
          <?php echo $total_pendientes; ?>
        </span>
      </div>
      <div class="admin-stat">
        <h3>Experiencias</h3>
        <span><?php echo $total_experiencias; ?></span>
      </div>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="admin_voluntariados.php" class="btn-gestionar">
            🎯 Gestionar Voluntariados
        </a>
    </div>

    <div class="admin-tabs">
      <button class="tab-button active" onclick="openTab('pendientes')">
        Pendientes
        <span class="badge"><?php echo $total_pendientes; ?></span>
      </button>
      <button class="tab-button" onclick="openTab('voluntarios')">
        Voluntarios
        <span class="badge"><?php echo $total_usuarios; ?></span>
      </button>
      <button class="tab-button" onclick="openTab('organizaciones')">
        Organizaciones
        <span class="badge"><?php echo $total_organizaciones; ?></span>
      </button>
      <button class="tab-button" onclick="openTab('inscripciones')">
        Inscripciones
        <span class="badge"><?php echo $total_inscripciones; ?></span>
      </button>
      <button class="tab-button" onclick="openTab('experiencias')">
        Experiencias
        <span class="badge"><?php echo $total_experiencias; ?></span>
      </button>
    </div>

    <div class="tab-content">
      <!-- Tab de Voluntariados Pendientes -->
      <div id="pendientes" class="tab-pane active">
        <h3>Voluntariados Pendientes de Aprobación</h3>
        <?php if (count($voluntariados_pendientes) > 0): ?>
          <div class="admin-table">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Título</th>
                  <th>Organización</th>
                  <th>Ubicación</th>
                  <th>Tipo</th>
                  <th>Fecha</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($voluntariados_pendientes as $vol): ?>
                <tr>
                  <td><?php echo $vol['id']; ?></td>
                  <td><?php echo htmlspecialchars($vol['titulo']); ?></td>
                  <td><?php echo htmlspecialchars($vol['nombre_organizacion'] ?? $vol['organizacion']); ?></td>
                  <td><?php echo htmlspecialchars($vol['ubicacion']); ?></td>
                  <td><?php echo htmlspecialchars($vol['tipo_voluntariado']); ?></td>
                  <td><?php echo date('d/m/Y', strtotime($vol['fecha_creacion'])); ?></td>
                  <td>
                    <form method="POST" style="display: inline;">
                      <input type="hidden" name="action" value="approve_voluntariado">
                      <input type="hidden" name="id" value="<?php echo $vol['id']; ?>">
                      <input type="hidden" name="type" value="voluntariado">
                      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                      <button type="submit" class="btn-primary" style="padding: 8px 16px; font-size: 12px; background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); border: none; border-radius: 6px; color: white; cursor: pointer; transition: all 0.3s ease;">✅ Aprobar</button>
                    </form>
                    <form method="POST" style="display: inline;">
                      <input type="hidden" name="action" value="reject_voluntariado">
                      <input type="hidden" name="id" value="<?php echo $vol['id']; ?>">
                      <input type="hidden" name="type" value="voluntariado">
                      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                      <button type="submit" class="btn-delete" style="padding: 8px 16px; font-size: 12px;" 
                              onclick="return confirm('¿Estás seguro de rechazar este voluntariado?')">❌ Rechazar</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="no-data">No hay voluntariados pendientes de aprobación.</p>
        <?php endif; ?>
      </div>

      <!-- Tab de Voluntarios -->
      <div id="voluntarios" class="tab-pane">
        <h3>Voluntarios Registrados</h3>
        <?php if ($usuarios->num_rows > 0): ?>
          <div class="admin-table">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nombre</th>
                  <th>Email</th>
                  <th>Teléfono</th>
                  <th>Departamento</th>
                  <th>Fecha Registro</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php while($row = $usuarios->fetch_assoc()): ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                  <td><?php echo htmlspecialchars($row['email']); ?></td>
                  <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                  <td><?php echo htmlspecialchars($row['departamento']); ?></td>
                  <td><?php echo date('d/m/Y', strtotime($row['fecha_registro'])); ?></td>
                  <td>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este voluntario? Se eliminarán también sus inscripciones.')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                      <input type="hidden" name="type" value="usuario">
                      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                      <button type="submit" class="btn-delete">Eliminar</button>
                    </form>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="no-data">No hay voluntarios registrados.</p>
        <?php endif; ?>
      </div>

      <!-- Tab de Organizaciones -->
      <div id="organizaciones" class="tab-pane">
        <h3>Organizaciones Registradas</h3>
        <?php if ($organizaciones->num_rows > 0): ?>
          <div class="admin-table">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nombre</th>
                  <th>Email</th>
                  <th>Contacto</th>
                  <th>Teléfono</th>
                  <th>Departamento</th>
                  <th>Tipo</th>
                  <th>Fecha Registro</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php while($row = $organizaciones->fetch_assoc()): ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                  <td><?php echo htmlspecialchars($row['email']); ?></td>
                  <td><?php echo htmlspecialchars($row['contacto']); ?></td>
                  <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                  <td><?php echo htmlspecialchars($row['departamento']); ?></td>
                  <td><?php echo htmlspecialchars($row['tipo_voluntariado']); ?></td>
                  <td><?php echo date('d/m/Y', strtotime($row['fecha_creacion'])); ?></td>
                  <td>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta organización? Se eliminarán también sus voluntariados.')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                      <input type="hidden" name="type" value="organizacion">
                      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                      <button type="submit" class="btn-delete">Eliminar</button>
                    </form>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="no-data">No hay organizaciones registradas.</p>
        <?php endif; ?>
      </div>

      <!-- Tab de Inscripciones - ACTUALIZADO CON HORARIO_SELECCIONADO -->
      <div id="inscripciones" class="tab-pane">
        <h3>Inscripciones a Voluntariados</h3>
        <?php if ($inscripciones->num_rows > 0): ?>
          <div class="admin-table">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Voluntario</th>
                  <th>Email</th>
                  <th>Teléfono</th>
                  <th>Voluntariado</th>
                  <th>Departamento</th>
                  <th>Horario Seleccionado</th>
                  <th>Fecha Inscripción</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php while($row = $inscripciones->fetch_assoc()): ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td><?php echo htmlspecialchars($row['nombre_voluntario'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars($row['email_voluntario'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars($row['telefono_voluntario'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars($row['voluntariado']); ?></td>
                  <td><?php echo htmlspecialchars($row['departamento']); ?></td>
                  <td>
                    <?php if (!empty($row['horario_seleccionado'])): ?>
                      <?php echo htmlspecialchars($row['horario_seleccionado']); ?>
                    <?php else: ?>
                      <span style="color: #666; font-style: italic;">No especificado</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_inscripcion'])); ?></td>
                  <td>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta inscripción?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                      <input type="hidden" name="type" value="inscripcion">
                      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                      <button type="submit" class="btn-delete">Eliminar</button>
                    </form>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="no-data">No hay inscripciones registradas.</p>
        <?php endif; ?>
      </div>

      <!-- Tab de Experiencias -->
      <div id="experiencias" class="tab-pane">
        <h3>Gestión de Experiencias</h3>
        <?php if (count($experiencias) > 0): ?>
          <div class="admin-table">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Voluntario</th>
                  <th>Voluntariado</th>
                  <th>Calificación</th>
                  <th>Comentario</th>
                  <th>Fecha</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($experiencias as $exp): ?>
                <tr>
                  <td><?php echo $exp['id']; ?></td>
                  <td><?php echo htmlspecialchars($exp['nombre_voluntario']); ?></td>
                  <td><?php echo htmlspecialchars($exp['titulo_voluntariado']); ?></td>
                  <td>
                    <span style="color: #ffc107;">
                        <?php echo str_repeat('★', $exp['calificacion']) . str_repeat('☆', 5 - $exp['calificacion']); ?>
                    </span>
                  </td>
                  <td style="max-width: 300px;"><?php echo htmlspecialchars(substr($exp['comentario'], 0, 100)) . (strlen($exp['comentario']) > 100 ? '...' : ''); ?></td>
                  <td><?php echo date('d/m/Y', strtotime($exp['fecha_creacion'])); ?></td>
                  <td>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta experiencia?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo $exp['id']; ?>">
                      <input type="hidden" name="type" value="experiencia">
                      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                      <button type="submit" class="btn-delete">Eliminar</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="no-data">No hay experiencias registradas.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php include 'footer.php'; ?>

  <script>
    function openTab(tabName) {
      const tabs = document.getElementsByClassName('tab-pane');
      for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
      }
      
      const buttons = document.getElementsByClassName('tab-button');
      for (let i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove('active');
      }
      
      document.getElementById(tabName).classList.add('active');
      event.currentTarget.classList.add('active');
    }
  </script>
</body>
</html>

<?php $conexion->close(); ?>