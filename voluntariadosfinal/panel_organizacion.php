<?php
require_once 'config.php';

// Verificar si el usuario es una organización
if (!isset($_SESSION['organizacion_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();
$organizacion_id = $_SESSION['organizacion_id'];

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_voluntariado'])) {
        $titulo = sanitize_input($_POST['titulo']);
        $descripcion = sanitize_input($_POST['descripcion']);
        $ubicacion = sanitize_input($_POST['ubicacion']);
        $departamento = sanitize_input($_POST['departamento']);
        $imagen = sanitize_input($_POST['imagen']);
        $tipo_voluntariado = sanitize_input($_POST['tipo_voluntariado']);
        
        $sql = "INSERT INTO voluntariados (titulo, descripcion, ubicacion, departamento, imagen, organizacion, organizacion_id, tipo_voluntariado, aprobado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, FALSE)";
        $stmt = $conn->prepare($sql);
        $organizacion_nombre = $_SESSION['organizacion_nombre'];
        $stmt->bind_param("ssssssis", $titulo, $descripcion, $ubicacion, $departamento, $imagen, $organizacion_nombre, $organizacion_id, $tipo_voluntariado);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Voluntariado agregado exitosamente. Estará visible una vez aprobado por el administrador.";
        } else {
            $_SESSION['error_message'] = "Error al agregar voluntariado";
        }
        $stmt->close();
        
    } elseif (isset($_POST['editar_voluntariado'])) {
        $id = (int)$_POST['id'];
        $titulo = sanitize_input($_POST['titulo']);
        $descripcion = sanitize_input($_POST['descripcion']);
        $ubicacion = sanitize_input($_POST['ubicacion']);
        $departamento = sanitize_input($_POST['departamento']);
        $imagen = sanitize_input($_POST['imagen']);
        $tipo_voluntariado = sanitize_input($_POST['tipo_voluntariado']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Verificar que el voluntariado pertenezca a esta organización
        $sql_check = "SELECT id FROM voluntariados WHERE id = ? AND organizacion_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $id, $organizacion_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $sql = "UPDATE voluntariados SET titulo=?, descripcion=?, ubicacion=?, departamento=?, imagen=?, tipo_voluntariado=?, activo=?, aprobado=FALSE WHERE id=? AND organizacion_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssiii", $titulo, $descripcion, $ubicacion, $departamento, $imagen, $tipo_voluntariado, $activo, $id, $organizacion_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Voluntariado actualizado exitosamente. Debe ser re-aprobado por el administrador.";
            } else {
                $_SESSION['error_message'] = "Error al actualizar voluntariado";
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "No tienes permisos para editar este voluntariado";
        }
        $stmt_check->close();
        
    } elseif (isset($_POST['eliminar_voluntariado'])) {
        $id = (int)$_POST['id'];
        
        // Verificar que el voluntariado pertenezca a esta organización
        $sql_check = "SELECT id FROM voluntariados WHERE id = ? AND organizacion_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $id, $organizacion_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $sql = "DELETE FROM voluntariados WHERE id = ? AND organizacion_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $organizacion_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Voluntariado eliminado exitosamente";
            } else {
                $_SESSION['error_message'] = "Error al eliminar voluntariado";
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "No tienes permisos para eliminar este voluntariado";
        }
        $stmt_check->close();
    }
    
    // Procesar horarios del voluntariado
    if (isset($_POST['guardar_horarios'])) {
        $voluntariado_id = (int)$_POST['voluntariado_id'];
        $horarios = [];
        
        // Verificar que el voluntariado pertenece a esta organización
        $sql_check = "SELECT id FROM voluntariados WHERE id = ? AND organizacion_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $voluntariado_id, $organizacion_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            if (isset($_POST['horarios']) && is_array($_POST['horarios'])) {
                foreach ($_POST['horarios'] as $horario_data) {
                    $horarios[] = [
                        'dia' => sanitize_input($horario_data['dia']),
                        'hora_inicio' => sanitize_input($horario_data['hora_inicio']),
                        'hora_fin' => sanitize_input($horario_data['hora_fin'])
                    ];
                }
            }
            
            if (guardarHorariosVoluntariado($voluntariado_id, $horarios)) {
                $_SESSION['success_message'] = "Horarios guardados exitosamente";
            } else {
                $_SESSION['error_message'] = "Error al guardar los horarios";
            }
        } else {
            $_SESSION['error_message'] = "No tienes permisos para gestionar horarios de este voluntariado";
        }
        $stmt_check->close();
        
        header("Location: panel_organizacion.php");
        exit();
    }
    
    header("Location: panel_organizacion.php");
    exit();
}

// Obtener voluntariados de esta organización
$voluntariados = obtenerVoluntariadosOrganizacion($organizacion_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Organización - Voluntariados Uruguay</title>
  <link rel="stylesheet" href="estilos.css">
  <link rel="icon" type="image/png" href="imagenes/logosinfondo.png">
</head>
<body>
  <?php include 'header.php'; ?>

  <section class="admin-container">
    <h1 class="section-title">🏢 Panel de Organización</h1>
    
    <div style="text-align: center; color: #666; margin-bottom: 30px; background: #e3f2fd; padding: 15px; border-radius: 8px;">
      <strong>Bienvenida, <?php echo $_SESSION['organizacion_nombre']; ?></strong>
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

    <!-- Formulario para agregar voluntariado -->
    <div class="form-voluntariado">
        <h3>➕ Agregar Nuevo Voluntariado</h3>
        <form method="POST">
            <div class="form-group">
                <label for="titulo">Título del Voluntariado</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3" required></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="ubicacion">Ubicación</label>
                    <input type="text" id="ubicacion" name="ubicacion" required>
                </div>
                
                <div class="form-group">
                    <label for="departamento">Departamento</label>
                    <select id="departamento" name="departamento" required>
                        <option value="">-- Seleccionar --</option>
                        <?php 
                        $departamentos = obtenerDepartamentosUruguay();
                        foreach ($departamentos as $depto): 
                        ?>
                        <option value="<?php echo $depto; ?>"><?php echo $depto; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="tipo_voluntariado">Tipo de Voluntariado</label>
                    <select id="tipo_voluntariado" name="tipo_voluntariado" required>
                        <option value="">-- Seleccionar --</option>
                        <?php 
                        $tipos = obtenerTiposVoluntariado();
                        foreach ($tipos as $tipo): 
                        ?>
                        <option value="<?php echo $tipo; ?>"><?php echo $tipo; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="imagen">URL de la Imagen</label>
                    <input type="text" id="imagen" name="imagen" required 
                           placeholder="imagenes/nombre-imagen.jpg">
                </div>
            </div>
            
            <button type="submit" name="agregar_voluntariado" class="btn-primary">
                ➕ Agregar Voluntariado
            </button>
        </form>
    </div>

    <!-- Formulario de Horarios -->
    <div class="form-voluntariado" style="margin-top: 30px;">
        <h3>🕒 Gestión de Horarios del Voluntariado</h3>
        
        <div class="horarios-selector">
            <div class="horarios-header">
                <h4>Selecciona un voluntariado para gestionar horarios:</h4>
                <select id="selectorVoluntariado" onchange="cargarHorarios(this.value)">
                    <option value="">-- Seleccionar Voluntariado --</option>
                    <?php 
                    $voluntariados_lista = obtenerVoluntariadosOrganizacion($organizacion_id);
                    foreach ($voluntariados_lista as $vol): 
                    ?>
                    <option value="<?php echo $vol['id']; ?>">
                        <?php echo htmlspecialchars($vol['titulo']); ?> 
                        (<?php echo $vol['aprobado'] ? 'Aprobado' : 'Pendiente'; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <form method="POST" id="formHorarios" style="display: none;">
                <input type="hidden" name="voluntariado_id" id="voluntariado_id_horarios">
                
                <div id="contenedorHorarios">
                    <!-- Los horarios se cargarán aquí dinámicamente -->
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="agregarHorario()" class="btn-secondary">
                        ➕ Agregar Horario
                    </button>
                    <button type="submit" name="guardar_horarios" class="btn-primary">
                        💾 Guardar Horarios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de voluntariados de la organización -->
    <h3>📋 Mis Voluntariados</h3>
    <div class="voluntariados-grid">
        <?php if (count($voluntariados) > 0): ?>
            <?php foreach($voluntariados as $vol): ?>
                <div class="voluntariado-card">
                    <img src="<?php echo $vol['imagen']; ?>" alt="<?php echo $vol['titulo']; ?>" 
                         onerror="this.src='imagenes/placeholder.jpg'" style="width: 100%; height: 120px; object-fit: cover; border-radius: 5px; margin-bottom: 10px;">
                    
                    <h4><?php echo htmlspecialchars($vol['titulo']); ?></h4>
                    <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($vol['ubicacion']); ?></p>
                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($vol['tipo_voluntariado']); ?></p>
                    
                    <!-- Mostrar Horarios -->
                    <?php if (!empty($vol['horarios'])): ?>
                    <div class="horarios-disponibles" style="background: #e8f5e8; padding: 8px; border-radius: 5px; margin: 8px 0;">
                        <p style="margin: 0 0 5px 0; font-size: 0.9em;"><strong>🕒 Horarios:</strong></p>
                        <div style="font-size: 0.8em;">
                            <?php 
                            // Mostrar solo los primeros 2 horarios para no ocupar mucho espacio
                            $horarios_mostrar = array_slice($vol['horarios'], 0, 2);
                            foreach ($horarios_mostrar as $horario): 
                            ?>
                            <div><?php echo $horario['dia_semana']; ?>: <?php echo substr($horario['hora_inicio'], 0, 5); ?>-<?php echo substr($horario['hora_fin'], 0, 5); ?></div>
                            <?php endforeach; ?>
                            <?php if (count($vol['horarios']) > 2): ?>
                            <div style="color: #666; font-style: italic;">+<?php echo count($vol['horarios']) - 2; ?> más...</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <p style="color: #666; font-style: italic; font-size: 0.9em; margin: 8px 0;">⏰ Sin horarios definidos</p>
                    <?php endif; ?>
                    
                    <p><strong>Estado:</strong> 
                        <span style="color: <?php echo $vol['aprobado'] ? '#4caf50' : '#ff9800'; ?>; font-weight: bold;">
                            <?php echo $vol['aprobado'] ? 'Aprobado' : 'Pendiente de aprobación'; ?>
                        </span>
                    </p>
                    
                    <div style="display: flex; gap: 5px; margin-top: 10px;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?php echo $vol['id']; ?>">
                            <button type="submit" name="eliminar_voluntariado" 
                                    class="btn-delete" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="return confirm('¿Estás seguro de eliminar este voluntariado?')">
                                🗑️ Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <p style="color: #666;">No hay voluntariados registrados.</p>
            </div>
        <?php endif; ?>
    </div>
  </section>

  <?php include 'footer.php'; ?>

  <script>
    function cargarHorarios(voluntariado_id) {
        if (!voluntariado_id) {
            document.getElementById('formHorarios').style.display = 'none';
            return;
        }
        
        fetch('obtener_horarios.php?voluntariado_id=' + voluntariado_id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('voluntariado_id_horarios').value = voluntariado_id;
                document.getElementById('formHorarios').style.display = 'block';
                
                const contenedor = document.getElementById('contenedorHorarios');
                contenedor.innerHTML = '';
                
                if (data.horarios && data.horarios.length > 0) {
                    data.horarios.forEach(horario => {
                        agregarFilaHorario(horario.dia_semana, horario.hora_inicio, horario.hora_fin);
                    });
                } else {
                    agregarFilaHorario();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los horarios');
            });
    }

    function agregarFilaHorario(dia = '', hora_inicio = '', hora_fin = '') {
        const contenedor = document.getElementById('contenedorHorarios');
        const index = contenedor.children.length;
        
        const fila = document.createElement('div');
        fila.className = 'fila-horario';
        fila.style.display = 'grid';
        fila.style.gridTemplateColumns = '1fr 1fr 1fr auto';
        fila.style.gap = '10px';
        fila.style.marginBottom = '10px';
        fila.style.alignItems = 'end';
        fila.style.padding = '15px';
        fila.style.background = '#f8f9fa';
        fila.style.borderRadius = '5px';
        
        fila.innerHTML = `
            <div class="form-group">
                <label>Día</label>
                <select name="horarios[${index}][dia]" required>
                    <option value="">-- Seleccionar --</option>
                    <option value="Lunes" ${dia === 'Lunes' ? 'selected' : ''}>Lunes</option>
                    <option value="Martes" ${dia === 'Martes' ? 'selected' : ''}>Martes</option>
                    <option value="Miércoles" ${dia === 'Miércoles' ? 'selected' : ''}>Miércoles</option>
                    <option value="Jueves" ${dia === 'Jueves' ? 'selected' : ''}>Jueves</option>
                    <option value="Viernes" ${dia === 'Viernes' ? 'selected' : ''}>Viernes</option>
                    <option value="Sábado" ${dia === 'Sábado' ? 'selected' : ''}>Sábado</option>
                    <option value="Domingo" ${dia === 'Domingo' ? 'selected' : ''}>Domingo</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Hora Inicio</label>
                <input type="time" name="horarios[${index}][hora_inicio]" value="${hora_inicio}" required>
            </div>
            
            <div class="form-group">
                <label>Hora Fin</label>
                <input type="time" name="horarios[${index}][hora_fin]" value="${hora_fin}" required>
            </div>
            
            <button type="button" onclick="this.parentElement.remove()" class="btn-delete" style="height: 40px; padding: 8px;">
                🗑️
            </button>
        `;
        
        contenedor.appendChild(fila);
    }

    function agregarHorario() {
        agregarFilaHorario();
    }
  </script>
</body>
</html>

<?php $conn->close(); ?>