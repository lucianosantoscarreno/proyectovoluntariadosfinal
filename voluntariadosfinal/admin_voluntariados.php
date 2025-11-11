<?php
require_once 'config.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Conexión a la base de datos
$conexion = getDBConnection();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_voluntariado'])) {
        // Agregar nuevo voluntariado (automáticamente aprobado si lo crea admin)
        $titulo = sanitize_input($_POST['titulo']);
        $descripcion = sanitize_input($_POST['descripcion']);
        $ubicacion = sanitize_input($_POST['ubicacion']);
        $departamento = sanitize_input($_POST['departamento']);
        $imagen = sanitize_input($_POST['imagen']);
        $organizacion = sanitize_input($_POST['organizacion']);
        $tipo_voluntariado = sanitize_input($_POST['tipo_voluntariado']);
        $aprobado = 1; // Los voluntariados creados por admin se aprueban automáticamente
        
        $sql = "INSERT INTO voluntariados (titulo, descripcion, ubicacion, departamento, imagen, organizacion, tipo_voluntariado, aprobado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssssi", $titulo, $descripcion, $ubicacion, $departamento, $imagen, $organizacion, $tipo_voluntariado, $aprobado);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Voluntariado agregado y aprobado exitosamente";
        } else {
            $_SESSION['error_message'] = "Error al agregar voluntariado";
        }
        $stmt->close();
        
    } elseif (isset($_POST['editar_voluntariado'])) {
        // Editar voluntariado existente
        $id = (int)$_POST['id'];
        $titulo = sanitize_input($_POST['titulo']);
        $descripcion = sanitize_input($_POST['descripcion']);
        $ubicacion = sanitize_input($_POST['ubicacion']);
        $departamento = sanitize_input($_POST['departamento']);
        $imagen = sanitize_input($_POST['imagen']);
        $organizacion = sanitize_input($_POST['organizacion']);
        $tipo_voluntariado = sanitize_input($_POST['tipo_voluntariado']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $aprobado = isset($_POST['aprobado']) ? 1 : 0;
        
        $sql = "UPDATE voluntariados SET titulo=?, descripcion=?, ubicacion=?, departamento=?, imagen=?, organizacion=?, tipo_voluntariado=?, activo=?, aprobado=? WHERE id=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssssiii", $titulo, $descripcion, $ubicacion, $departamento, $imagen, $organizacion, $tipo_voluntariado, $activo, $aprobado, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Voluntariado actualizado exitosamente";
        } else {
            $_SESSION['error_message'] = "Error al actualizar voluntariado";
        }
        $stmt->close();
        
    } elseif (isset($_POST['eliminar_voluntariado'])) {
        // Eliminar voluntariado
        $id = (int)$_POST['id'];
        
        $sql = "DELETE FROM voluntariados WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Voluntariado eliminado exitosamente";
        } else {
            $_SESSION['error_message'] = "Error al eliminar voluntariado";
        }
        $stmt->close();
        
    } elseif (isset($_POST['aprobar_voluntariado'])) {
        // Aprobar voluntariado
        $id = (int)$_POST['id'];
        
        if (aprobarVoluntariado($id, true)) {
            $_SESSION['success_message'] = "Voluntariado aprobado exitosamente";
        } else {
            $_SESSION['error_message'] = "Error al aprobar voluntariado";
        }
        
    } elseif (isset($_POST['rechazar_voluntariado'])) {
        // Rechazar voluntariado (eliminar)
        $id = (int)$_POST['id'];
        
        $sql = "DELETE FROM voluntariados WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Voluntariado rechazado y eliminado";
        } else {
            $_SESSION['error_message'] = "Error al rechazar voluntariado";
        }
        $stmt->close();
    }
    // Procesar horarios del voluntariado
if (isset($_POST['guardar_horarios'])) {
    $voluntariado_id = (int)$_POST['voluntariado_id'];
    $horarios = [];
    
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
    
    header("Location: admin_voluntariados.php");
    exit();
}
    
    header("Location: admin_voluntariados.php");
    exit();
}

// Obtener todos los voluntariados
$voluntariados = $conexion->query("SELECT * FROM voluntariados ORDER BY fecha_creacion DESC");

// Obtener voluntariados pendientes de aprobación
$voluntariados_pendientes = obtenerVoluntariadosPendientes();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Voluntariados - Voluntariados Uruguay</title>
  <link rel="stylesheet" href="estilos.css">
  <link rel="icon" type="image/png" href="imagenes/logosinfondo.png">
</head>
<body>
  <?php include 'header.php'; ?>

  <section class="admin-container">
    <h1 class="section-title">Administrar Voluntariados</h1>
    
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

    <!-- Sección de voluntariados pendientes de aprobación -->
    <?php if (count($voluntariados_pendientes) > 0): ?>
    <div class="form-voluntariado" style="background: #fff3cd; border-left: 4px solid #ffc107;">
        <h3>⏳ Voluntariados Pendientes de Aprobación (<?php echo count($voluntariados_pendientes); ?>)</h3>
        <div class="voluntariados-grid">
            <?php foreach($voluntariados_pendientes as $vol): ?>
                <div class="voluntariado-card" style="border: 2px solid #ffc107;">
                    <img src="<?php echo $vol['imagen']; ?>" alt="<?php echo $vol['titulo']; ?>" 
                         onerror="this.src='imagenes/placeholder.jpg'" style="width: 100%; height: 120px; object-fit: cover; border-radius: 5px; margin-bottom: 10px;">
                    
                    <h4><?php echo htmlspecialchars($vol['titulo']); ?></h4>
                    <p><strong>Organización:</strong> <?php echo htmlspecialchars($vol['nombre_organizacion'] ?? $vol['organizacion']); ?></p>
                    <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($vol['ubicacion']); ?></p>
                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($vol['tipo_voluntariado']); ?></p>
                    <p><strong>Estado:</strong> 
                        <span style="color: #ff9800; font-weight: bold;">Pendiente de Aprobación</span>
                    </p>
                    
                    <div style="display: flex; gap: 5px; margin-top: 10px; flex-wrap: wrap;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?php echo $vol['id']; ?>">
                            <button type="submit" name="aprobar_voluntariado" 
                                    class="btn-primary" style="padding: 5px 10px; font-size: 12px; background: #4caf50;">
                                ✅ Aprobar
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?php echo $vol['id']; ?>">
                            <button type="submit" name="rechazar_voluntariado" 
                                    class="btn-delete" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="return confirm('¿Estás seguro de rechazar este voluntariado? Se eliminará permanentemente.')">
                                ❌ Rechazar
                            </button>
                        </form>
                        <button type="button" onclick="editarVoluntariado(<?php echo $vol['id']; ?>)" 
                                class="btn-primary" style="padding: 5px 10px; font-size: 12px; background: #2196F3;">
                            ✏️ Editar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formulario para agregar/editar voluntariado -->
    <div class="form-voluntariado">
        <h3 id="form-title">➕ Agregar Nuevo Voluntariado</h3>
        <form method="POST" id="formVoluntariado">
            <input type="hidden" name="id" id="voluntariado_id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="titulo">Título del Voluntariado</label>
                    <input type="text" id="titulo" name="titulo" required>
                </div>
                
                <div class="form-group">
                    <label for="organizacion">Organización</label>
                    <input type="text" id="organizacion" name="organizacion" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3" required></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
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
            </div>
            
            <div class="form-group">
                <label for="imagen">URL de la Imagen</label>
                <input type="text" id="imagen" name="imagen" required 
                       placeholder="imagenes/nombre-imagen.jpg">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; display: none;" id="campos-extra">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="activo" name="activo" value="1" checked>
                        Voluntariado Activo
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="aprobado" name="aprobado" value="1" checked>
                        Voluntariado Aprobado
                    </label>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" name="agregar_voluntariado" id="btn-agregar" class="btn-primary">
                    ➕ Agregar Voluntariado
                </button>
                <button type="submit" name="editar_voluntariado" id="btn-editar" class="btn-primary" style="display: none;">
                    ✏️ Actualizar Voluntariado
                </button>
                <button type="button" onclick="cancelarEdicion()" id="btn-cancelar" class="btn-secondary" style="display: none;">
                    ❌ Cancelar
                </button>
            </div>
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
                $conn = getDBConnection();
                $voluntariados_lista = $conn->query("SELECT id, titulo FROM voluntariados ORDER BY titulo");
                while ($vol = $voluntariados_lista->fetch_assoc()): 
                ?>
                <option value="<?php echo $vol['id']; ?>"><?php echo htmlspecialchars($vol['titulo']); ?></option>
                <?php endwhile; 
                $conn->close();
                ?>
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

    <!-- Lista de voluntariados existentes -->
    <h3>📋 Todos los Voluntariados</h3>
    <div class="voluntariados-grid">
        <?php if ($voluntariados->num_rows > 0): ?>
            <?php while($vol = $voluntariados->fetch_assoc()): ?>
                <div class="voluntariado-card">
                    <img src="<?php echo $vol['imagen']; ?>" alt="<?php echo $vol['titulo']; ?>" 
                         onerror="this.src='imagenes/placeholder.jpg'" style="width: 100%; height: 120px; object-fit: cover; border-radius: 5px; margin-bottom: 10px;">
                    
                    <h4><?php echo htmlspecialchars($vol['titulo']); ?></h4>
                    <p><strong>Organización:</strong> <?php echo htmlspecialchars($vol['organizacion']); ?></p>
                    <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($vol['ubicacion']); ?></p>
                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($vol['tipo_voluntariado']); ?></p>
                    <p><strong>Estado:</strong> 
                        <span style="color: <?php echo $vol['activo'] ? '#4caf50' : '#f44336'; ?>; font-weight: bold;">
                            <?php echo $vol['activo'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </p>
                    <p><strong>Aprobación:</strong> 
                        <span style="color: <?php echo $vol['aprobado'] ? '#4caf50' : '#ff9800'; ?>; font-weight: bold;">
                            <?php echo $vol['aprobado'] ? '✅ Aprobado' : '⏳ Pendiente'; ?>
                        </span>
                    </p>
                    
                    <div style="display: flex; gap: 5px; margin-top: 10px; flex-wrap: wrap;">
                        <button type="button" onclick="editarVoluntariado(<?php echo $vol['id']; ?>)" 
                                class="btn-primary" style="padding: 5px 10px; font-size: 12px;">
                            ✏️ Editar
                        </button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?php echo $vol['id']; ?>">
                            <button type="submit" name="eliminar_voluntariado" 
                                    class="btn-delete" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="return confirm('¿Estás seguro de eliminar este voluntariado?')">
                                🗑️ Eliminar
                            </button>
                        </form>
                        <?php if (!$vol['aprobado']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $vol['id']; ?>">
                                <button type="submit" name="aprobar_voluntariado" 
                                        class="btn-primary" style="padding: 5px 10px; font-size: 12px; background: #4caf50;">
                                    ✅ Aprobar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <p style="color: #666;">No hay voluntariados registrados.</p>
            </div>
        <?php endif; ?>
    </div>
  </section>

  <?php include 'footer.php'; ?>

  <script>
    function editarVoluntariado(id) {
        fetch('obtener_voluntariado.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('voluntariado_id').value = data.id;
                document.getElementById('titulo').value = data.titulo;
                document.getElementById('descripcion').value = data.descripcion;
                document.getElementById('ubicacion').value = data.ubicacion;
                document.getElementById('departamento').value = data.departamento;
                document.getElementById('imagen').value = data.imagen;
                document.getElementById('organizacion').value = data.organizacion;
                document.getElementById('tipo_voluntariado').value = data.tipo_voluntariado;
                document.getElementById('activo').checked = data.activo == 1;
                document.getElementById('aprobado').checked = data.aprobado == 1;
                
                document.getElementById('form-title').textContent = '✏️ Editar Voluntariado';
                document.getElementById('btn-agregar').style.display = 'none';
                document.getElementById('btn-editar').style.display = 'block';
                document.getElementById('btn-cancelar').style.display = 'block';
                document.getElementById('campos-extra').style.display = 'grid';
                
                document.querySelector('.form-voluntariado').scrollIntoView({ behavior: 'smooth' });
            })
            .catch(error => {
                alert('Error al cargar los datos del voluntariado');
                console.error('Error:', error);
            });
    }
    
    function cancelarEdicion() {
        document.getElementById('formVoluntariado').reset();
        document.getElementById('voluntariado_id').value = '';
        document.getElementById('form-title').textContent = '➕ Agregar Nuevo Voluntariado';
        document.getElementById('btn-agregar').style.display = 'block';
        document.getElementById('btn-editar').style.display = 'none';
        document.getElementById('btn-cancelar').style.display = 'none';
        document.getElementById('campos-extra').style.display = 'none';
    }
  </script>
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

<?php $conexion->close(); ?>