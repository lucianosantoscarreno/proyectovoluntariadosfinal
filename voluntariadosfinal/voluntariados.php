<?php
require_once 'config.php';

// Obtener parámetros de filtro
$filtro_departamento = $_GET['departamento'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_organizacion = $_GET['organizacion'] ?? '';

// Obtener voluntariados activos y aprobados (filtrados si aplica)
$voluntariados = obtenerVoluntariadosFiltrados($filtro_departamento, $filtro_tipo, $filtro_organizacion);

// Procesar inscripción
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscribirse'])) {
    if (!isset($_SESSION['voluntario_id'])) {
        $error = "Debes iniciar sesión para inscribirte";
    } else {
        $voluntario_id = $_SESSION['voluntario_id'];
        $voluntariado_id = (int)$_POST['voluntariado_id'];
        $departamento = sanitize_input($_POST['departamento']);
        $horario_seleccionado = sanitize_input($_POST['horario_seleccionado']);
        $experiencia = sanitize_input($_POST['experiencia']);
        $motivacion = sanitize_input($_POST['motivacion']);

        $conn = getDBConnection();
        
        // VERIFICAR PRIMERO SI EL VOLUNTARIADO EXISTE Y ESTÁ APROBADO
        $sql_check = "SELECT id, titulo FROM voluntariados WHERE id = ? AND activo = TRUE AND aprobado = TRUE";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $voluntariado_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $voluntariado = $result_check->fetch_assoc();
            
            // INSERTAR CON EL HORARIO SELECCIONADO (sin disponibilidad)
            $sql = "INSERT INTO inscripciones (usuario_id, voluntariado_id, voluntariado, departamento, horario_seleccionado, experiencia, motivacion) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisssss", $voluntario_id, $voluntariado_id, $voluntariado['titulo'], $departamento, $horario_seleccionado, $experiencia, $motivacion);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "✅ Te has inscrito correctamente en el voluntariado";
                header("Location: voluntariados.php");
                exit();
            } else {
                $error = "Error al inscribirse: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "El voluntariado no existe o no está disponible para inscripción";
        }
        
        $stmt_check->close();
        $conn->close();
    }
}

// Procesar agregar experiencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_experiencia'])) {
    if (!isset($_SESSION['voluntario_id'])) {
        $_SESSION['error_message'] = "Debes iniciar sesión para compartir tu experiencia";
        header("Location: voluntariados.php");
        exit();
    } else {
        $voluntariado_id = (int)$_POST['voluntariado_id'];
        $comentario = sanitize_input($_POST['comentario']);
        $calificacion = (int)$_POST['calificacion'];
        
        if (agregarExperiencia($voluntariado_id, $_SESSION['voluntario_id'], $comentario, $calificacion)) {
            // Éxito - el JS maneja la actualización del modal
        } else {
            $_SESSION['error_message'] = "Error al compartir tu experiencia. Asegúrate de estar inscrito en este voluntariado.";
            header("Location: voluntariados.php");
            exit();
        }
    }
}

// Procesar eliminar experiencia (solo admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_experiencia'])) {
    if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
        $experiencia_id = (int)$_POST['experiencia_id'];
        if (eliminarExperiencia($experiencia_id)) {
            $_SESSION['success_message'] = "✅ Experiencia eliminada exitosamente";
            header("Location: voluntariados.php");
            exit();
        } else {
            $error = "Error al eliminar la experiencia";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voluntariados - Voluntariados Uruguay</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" type="image/png" href="imagenes/logosinfondo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="volunteerings-section">
        <h1 class="section-title">Voluntariados Disponibles</h1>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Sistema de Filtros Compacto -->
        <section class="filtros-section">
            <div class="filtros-header">
                <h2>Encuentra tu voluntariado ideal</h2>
            </div>
            
            <form method="GET" action="voluntariados.php">
                <div class="filtros-grid">
                    <div class="filtro-group">
                        <label for="filtro_departamento"><i class="fas fa-map-marker-alt"></i> Departamento</label>
                        <select id="filtro_departamento" name="departamento" class="filtro-select">
                            <option value="">Todos los departamentos</option>
                            <?php 
                            $departamentos = obtenerDepartamentosUruguay();
                            $departamento_seleccionado = $_GET['departamento'] ?? '';
                            foreach ($departamentos as $depto): 
                            ?>
                            <option value="<?php echo $depto; ?>" <?php echo $departamento_seleccionado == $depto ? 'selected' : ''; ?>>
                                <?php echo $depto; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filtro-group">
                        <label for="filtro_tipo"><i class="fas fa-bullseye"></i> Tipo de Voluntariado</label>
                        <select id="filtro_tipo" name="tipo" class="filtro-select">
                            <option value="">Todos los tipos</option>
                            <?php 
                            $tipos = obtenerTiposVoluntariado();
                            $tipo_seleccionado = $_GET['tipo'] ?? '';
                            foreach ($tipos as $tipo): 
                            ?>
                            <option value="<?php echo $tipo; ?>" <?php echo $tipo_seleccionado == $tipo ? 'selected' : ''; ?>>
                                <?php echo $tipo; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filtro-group">
                        <label for="filtro_organizacion"><i class="fas fa-building"></i> Organización</label>
                        <input type="text" id="filtro_organizacion" name="organizacion" 
                               value="<?php echo htmlspecialchars($_GET['organizacion'] ?? ''); ?>"
                               placeholder="Buscar organización..." class="filtro-select">
                    </div>
                </div>
                
                <div class="filtros-actions">
                    <button type="submit" class="btn-filtrar">
                        <i class="fas fa-search"></i> Aplicar Filtros
                    </button>
                    <a href="voluntariados.php" class="btn-limpiar">
                        <i class="fas fa-times"></i> Limpiar Filtros
                    </a>
                </div>
            </form>
        </section>

        <?php
        // Mostrar información de resultados
        $total_resultados = count($voluntariados);
        if ($filtro_departamento || $filtro_tipo || $filtro_organizacion):
        ?>
        <div class="resultados-info">
            <strong>📊 Resultados de búsqueda:</strong> 
            Encontrados <?php echo $total_resultados; ?> voluntariado<?php echo $total_resultados != 1 ? 's' : ''; ?>
            
            <?php if ($filtro_departamento): ?>
                <span style="margin-left: 15px;">📍 <?php echo htmlspecialchars($filtro_departamento); ?></span>
            <?php endif; ?>
            
            <?php if ($filtro_tipo): ?>
                <span style="margin-left: 15px;">🎯 <?php echo htmlspecialchars($filtro_tipo); ?></span>
            <?php endif; ?>
            
            <?php if ($filtro_organizacion): ?>
                <span style="margin-left: 15px;">🏢 <?php echo htmlspecialchars($filtro_organizacion); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="lista-vol">
            <?php if (count($voluntariados) > 0): ?>
                <?php foreach($voluntariados as $voluntariado): ?>
                    <div class="card">
                        <center>
                            <img class="volopc" src="<?php echo $voluntariado['imagen']; ?>" alt="<?php echo $voluntariado['titulo']; ?>" 
                                 onerror="this.src='imagenes/placeholder.jpg'">
                        </center>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($voluntariado['titulo']); ?></h3>
                            <p class="location">📍 <?php echo htmlspecialchars($voluntariado['ubicacion']); ?></p>
                            <p class="organization">🏢 <?php echo htmlspecialchars($voluntariado['organizacion']); ?></p>
                            <p class="type">🎯 <?php echo htmlspecialchars($voluntariado['tipo_voluntariado']); ?></p>
                            
                            <!-- Mostrar Horarios -->
                            <?php if (!empty($voluntariado['horarios'])): ?>
                            <div class="horarios-disponibles">
                                <p><strong>🕒 Horarios Disponibles:</strong></p>
                                <div class="lista-horarios">
                                    <?php 
                                    // Agrupar horarios por día
                                    $horarios_por_dia = [];
                                    foreach ($voluntariado['horarios'] as $horario) {
                                        $horarios_por_dia[$horario['dia_semana']][] = $horario;
                                    }
                                    
                                    foreach ($horarios_por_dia as $dia => $horarios_dia): 
                                    ?>
                                    <div class="horario-dia">
                                        <strong><?php echo $dia; ?>:</strong>
                                        <?php 
                                        $horas = [];
                                        foreach ($horarios_dia as $horario) {
                                            $horas[] = substr($horario['hora_inicio'], 0, 5) . ' - ' . substr($horario['hora_fin'], 0, 5);
                                        }
                                        echo implode(', ', $horas);
                                        ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <p class="sin-horarios">⏰ Horarios por confirmar</p>
                            <?php endif; ?>
                            
                            <p><?php echo htmlspecialchars(substr($voluntariado['descripcion'], 0, 100)); ?><?php echo strlen($voluntariado['descripcion']) > 100 ? '...' : ''; ?></p>
                            
                            <div class="card-actions">
                                <?php if (isset($_SESSION['voluntario_id'])): ?>
                                    <button onclick="abrirModalInscripcion(<?php echo $voluntariado['id']; ?>, '<?php echo addslashes($voluntariado['titulo']); ?>')" class="ins">
                                        Inscribirse
                                    </button>
                                <?php elseif (!isset($_SESSION['admin']) && !isset($_SESSION['organizacion_id'])): ?>
                                    <div style="text-align: center; padding: 10px; background: #f5f5f5; border-radius: 5px;">
                                        <small>💡 <a href="login.php" style="color: #2e7d32;">Inicia sesión</a> como voluntario para inscribirte</small>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 10px; background: #f5f5f5; border-radius: 5px;">
                                        <small>Los administradores y organizaciones no pueden inscribirse</small>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Botón para ver experiencias -->
                                <button onclick="abrirModalExperiencias(<?php echo $voluntariado['id']; ?>, '<?php echo addslashes($voluntariado['titulo']); ?>')" 
                                  class="btn-ver-experiencias">
                                  <i class="fas fa-comments"></i> Ver Experiencias
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
                    <div style="font-size: 4rem; margin-bottom: 20px;">🔍</div>
                    <h3 style="color: #666; margin-bottom: 15px;">No se encontraron voluntariados</h3>
                    <p style="color: #888; max-width: 400px; margin: 0 auto;">
                        No hay voluntariados que coincidan con tus criterios de búsqueda.
                        Intenta con otros filtros o <a href="voluntariados.php" style="color: #2e7d32; font-weight: 600;">muestra todos los voluntariados</a>.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modal para inscripción (solo para voluntarios) - VERSIÓN CON HORARIOS -->
    <?php if (isset($_SESSION['voluntario_id'])): ?>
    <div id="modalInscripcion" class="modal-inscripcion-compact">
        <div class="modal-content-compact">
            <h2>Inscribirse en Voluntariado</h2>
            
            <form method="POST" id="formInscripcion" class="form-compact">
                <input type="hidden" name="voluntariado_id" id="voluntariadoId">
                <input type="hidden" name="voluntariado" id="voluntariadoTitulo">
                
                <div class="form-group">
                    <label for="departamento">Departamento donde resides</label>
                    <select id="departamento" name="departamento" required>
                        <option value="">-- Seleccioná --</option>
                        <?php 
                        $departamentos = obtenerDepartamentosUruguay();
                        foreach ($departamentos as $depto): 
                        ?>
                        <option value="<?php echo $depto; ?>"><?php echo $depto; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="horariosContainer" style="display: none;">
                    <label for="horario_seleccionado">Selecciona un horario disponible</label>
                    <select id="horario_seleccionado" name="horario_seleccionado" required>
                        <option value="">-- Cargando horarios...</option>
                    </select>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        💡 Selecciona uno de los horarios que ofrece este voluntariado
                    </small>
                </div>

                <div class="form-group">
                    <label for="experiencia">¿Tienes experiencia previa en voluntariados? <span style="color: #666; font-weight: normal;">(opcional)</span></label>
                    <textarea id="experiencia" name="experiencia" rows="2" placeholder="Contanos sobre tu experiencia previa..."></textarea>
                </div>

                <div class="form-group">
                    <label for="motivacion">Motivación para participar</label>
                    <textarea id="motivacion" name="motivacion" rows="3" placeholder="¿Por qué quieres participar en este voluntariado?" required></textarea>
                </div>

                <div class="btn-group-compact">
                    <button type="submit" name="inscribirse" class="btn-compact btn-primary-compact" id="btnInscribirse" disabled>
                        ⏳ Cargando...
                    </button>
                    <button type="button" onclick="cerrarModalInscripcion()" class="btn-compact btn-cancel-compact">
                        ❌ Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal de Experiencias -->
    <div id="modalExperiencias" class="modal-overlay">
        <div class="modal-experiencias">
            <div class="modal-header">
                <h2 id="modalExperienciasTitulo">Experiencias del Voluntariado</h2>
                <button class="btn-cerrar" onclick="cerrarModalExperiencias()">×</button>
            </div>
            
            <div class="modal-body">
                <div class="modal-stats">
                    <div id="modalStatsContent">
                        <!-- Las estadísticas se cargarán aquí -->
                    </div>
                </div>
                
                <div class="experiencias-container" id="experienciasContainer">
                    <!-- Las experiencias se cargarán aquí -->
                </div>
                
                <div id="experienciaFormContainer" style="padding: 0 25px 25px 25px;">
                    <!-- El formulario se cargará aquí -->
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="voluntariados.js"></script>
</body>
</html>