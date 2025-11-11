<?php
// config.php - CONFIGURACIÓN ACTUALIZADA

// Configuración de sesión
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voluntariados');
define('SITE_NAME', 'Tu Granito de Arena');

// Función de conexión a la base de datos
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("Error de conexión: " . $conn->connect_error);
        die("Error en el sistema. Por favor, intente más tarde.");
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Función para verificar administrador - VERSIÓN SEGURA
function verificarAdministrador($email, $password) {
    $conn = getDBConnection();
    
    $sql = "SELECT * FROM administradores WHERE email = ? AND activo = TRUE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $admin = null;
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        
        if (password_verify($password, $admin['password_hash'])) {
            $stmt->close();
            $conn->close();
            return $admin;
        }
    }
    
    // Solo cerrar la conexión si no se ha cerrado ya
    $stmt->close();
    $conn->close();
    return false;
}

// Función para verificar organización (ACTUALIZADA - sin aprobación)
// Función para verificar organización - VERSIÓN CORREGIDA
function verificarOrganizacion($email, $password) {
    $conn = getDBConnection();
    
    $sql = "SELECT * FROM organizaciones WHERE email = ? AND password_hash IS NOT NULL AND password_hash != ''";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $organizacion = null;
    
    if ($result->num_rows === 1) {
        $organizacion = $result->fetch_assoc();
        
        if (!empty($organizacion['password_hash']) && password_verify($password, $organizacion['password_hash'])) {
            $stmt->close();
            $conn->close();
            return $organizacion;
        }
    }
    
    // Solo cerrar la conexión si no se ha cerrado ya
    $stmt->close();
    $conn->close();
    return false;
}

// Función de sanitización
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Función de validación de email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función de logging
function log_action($action, $user_id = null) {
    $log_file = 'logs/system.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $user_id ?? 'guest';
    
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }
    
    $log_entry = "[$timestamp] [$ip] [User:$user_id] $action\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Generar token CSRF
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verificar token CSRF
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Función para hash de contraseñas
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Función para obtener voluntariados activos y aprobados
function obtenerVoluntariadosActivos() {
    $conn = getDBConnection();
    $sql = "SELECT * FROM voluntariados WHERE activo = TRUE AND aprobado = TRUE ORDER BY fecha_creacion DESC";
    $result = $conn->query($sql);
    $voluntariados = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $voluntariados[] = $row;
        }
    }
    
    $conn->close();
    return $voluntariados;
}

// Función para obtener voluntariados pendientes de aprobación (para admin)
function obtenerVoluntariadosPendientes() {
    $conn = getDBConnection();
    $sql = "SELECT v.*, o.nombre as nombre_organizacion 
            FROM voluntariados v 
            LEFT JOIN organizaciones o ON v.organizacion_id = o.id 
            WHERE v.aprobado = FALSE 
            ORDER BY v.fecha_creacion DESC";
    $result = $conn->query($sql);
    $voluntariados = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $voluntariados[] = $row;
        }
    }
    
    $conn->close();
    return $voluntariados;
}

// Función para obtener voluntariados de una organización
function obtenerVoluntariadosOrganizacion($organizacion_id) {
    $conn = getDBConnection();
    $sql = "SELECT * FROM voluntariados WHERE organizacion_id = ? ORDER BY fecha_creacion DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $organizacion_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $voluntariados = [];
    
    while ($row = $result->fetch_assoc()) {
        // Obtener horarios para cada voluntariado
        $row['horarios'] = obtenerHorariosVoluntariado($row['id']);
        $voluntariados[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $voluntariados;
}

// Función para aprobar/rechazar voluntariado
function aprobarVoluntariado($voluntariado_id, $aprobado = true) {
    $conn = getDBConnection();
    
    $sql = "UPDATE voluntariados SET aprobado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $aprobado, $voluntariado_id);
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $success;
}

// Función para eliminar organización y sus voluntariados
function eliminarOrganizacion($organizacion_id) {
    $conn = getDBConnection();
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Eliminar voluntariados de la organización
        $stmt1 = $conn->prepare("DELETE FROM voluntariados WHERE organizacion_id = ?");
        $stmt1->bind_param("i", $organizacion_id);
        $stmt1->execute();
        $stmt1->close();
        
        // Eliminar la organización
        $stmt2 = $conn->prepare("DELETE FROM organizaciones WHERE id = ?");
        $stmt2->bind_param("i", $organizacion_id);
        $stmt2->execute();
        $stmt2->close();
        
        // Confirmar transacción
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Revertir en caso de error
        $conn->rollback();
        error_log("Error al eliminar organización: " . $e->getMessage());
        return false;
    }
}

// Función para obtener todas las organizaciones
function obtenerOrganizaciones() {
    $conn = getDBConnection();
    $sql = "SELECT * FROM organizaciones ORDER BY fecha_creacion DESC";
    $result = $conn->query($sql);
    $organizaciones = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $organizaciones[] = $row;
        }
    }
    
    $conn->close();
    return $organizaciones;
}

// Función para validar teléfono uruguayo
function validarTelefonoUruguayo($telefono) {
    return preg_match('/^\+598[0-9]{8}$/', $telefono);
}

// Función para obtener departamentos de Uruguay
function obtenerDepartamentosUruguay() {
    return [
        'Montevideo', 'Canelones', 'Maldonado', 'Colonia', 'San José', 
        'Rocha', 'Florida', 'Durazno', 'Treinta y Tres', 'Cerro Largo', 
        'Rivera', 'Artigas', 'Salto', 'Paysandú', 'Río Negro', 
        'Soriano', 'Flores', 'Lavalleja', 'Tacuarembó'
    ];
}

// Función para obtener tipos de voluntariado
function obtenerTiposVoluntariado() {
    return [
        'Ambiental', 'Educación', 'Salud', 'Social', 'Comunitario',
        'Emergencias', 'Cultural', 'Deportivo', 'Adultos Mayores',
        'Niños y Adolescentes', 'Desarrollo Comunitario', 
        'Emergencias y Ayuda Humanitaria', 'Arte y Cultura',
        'Deporte y Recreación', 'Derechos Humanos', 'Investigación'
    ];
}

// Función para obtener usuario por ID
function obtenerUsuario($conn, $id) {
    $id = (int)$id;
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    return $usuario;
}

// Inicializar token CSRF
if (empty($_SESSION['csrf_token'])) {
    generate_csrf_token();
}
// Función mejorada para validación internacional
function validarTelefonoInternacional($telefono, $codigoPais) {
    $telefonoLimpio = preg_replace('/\D/', '', $telefono);
    
    $validaciones = [
        '+598' => '/^[2-9][0-9]{7,8}$/', // Uruguay: 8-9 dígitos
        '+54' => '/^[1-9][0-9]{9,11}$/', // Argentina: 10-12 dígitos
        '+55' => '/^[1-9][0-9]{10,11}$/', // Brasil: 11-12 dígitos
        '+56' => '/^[2-9][0-9]{8}$/',   // Chile: 9 dígitos
        '+595' => '/^[1-9][0-9]{8,9}$/', // Paraguay: 9-10 dígitos
        '+51' => '/^[1-9][0-9]{8}$/',   // Perú: 9 dígitos
        '+57' => '/^[1-9][0-9]{9}$/',   // Colombia: 10 dígitos
    ];
    
    if (isset($validaciones[$codigoPais])) {
        return preg_match($validaciones[$codigoPais], $telefonoLimpio);
    }
    
    return strlen($telefonoLimpio) >= 8 && strlen($telefonoLimpio) <= 15;
}

function formatearTelefonoInternacional($telefono, $codigoPais) {
    $telefonoLimpio = preg_replace('/\D/', '', $telefono);
    return $codigoPais . $telefonoLimpio;
}

// ==============================
// FUNCIONES PARA EXPERIENCIAS
// ==============================

// Función para obtener experiencias de un voluntariado
function obtenerExperiencias($voluntariado_id, $solo_aprobadas = true) {
    $conn = getDBConnection();
    
    if ($solo_aprobadas) {
        $sql = "SELECT e.*, u.nombre as nombre_voluntario 
                FROM experiencias e 
                JOIN usuarios u ON e.usuario_id = u.id 
                WHERE e.voluntariado_id = ? AND e.aprobado = TRUE 
                ORDER BY e.fecha_creacion DESC";
    } else {
        $sql = "SELECT e.*, u.nombre as nombre_voluntario 
                FROM experiencias e 
                JOIN usuarios u ON e.usuario_id = u.id 
                WHERE e.voluntariado_id = ? 
                ORDER BY e.fecha_creacion DESC";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $voluntariado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $experiencias = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $experiencias[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();
    return $experiencias;
}

// Función para agregar experiencia
function agregarExperiencia($voluntariado_id, $usuario_id, $comentario, $calificacion = 5) {
    $conn = getDBConnection();
    
    // Verificar si el usuario está inscrito en el voluntariado
    $sql_check = "SELECT id FROM inscripciones WHERE usuario_id = ? AND voluntariado_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $usuario_id, $voluntariado_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        $stmt_check->close();
        $conn->close();
        return false; // Usuario no inscrito
    }
    $stmt_check->close();
    
    $sql = "INSERT INTO experiencias (voluntariado_id, usuario_id, comentario, calificacion) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $voluntariado_id, $usuario_id, $comentario, $calificacion);
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    return $success;
}

// Función para eliminar experiencia (solo admin)
function eliminarExperiencia($experiencia_id) {
    $conn = getDBConnection();
    $sql = "DELETE FROM experiencias WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $experiencia_id);
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    return $success;
}

// Función para obtener promedio de calificaciones
function obtenerPromedioCalificaciones($voluntariado_id) {
    $conn = getDBConnection();
    $sql = "SELECT AVG(calificacion) as promedio, COUNT(*) as total 
            FROM experiencias 
            WHERE voluntariado_id = ? AND aprobado = TRUE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $voluntariado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    return $data;
}

// Función para verificar si usuario puede comentar
function puedeComentar($voluntariado_id, $usuario_id) {
    $conn = getDBConnection();
    
    // Verificar inscripción
    $sql_inscripcion = "SELECT id FROM inscripciones WHERE usuario_id = ? AND voluntariado_id = ?";
    $stmt_inscripcion = $conn->prepare($sql_inscripcion);
    $stmt_inscripcion->bind_param("ii", $usuario_id, $voluntariado_id);
    $stmt_inscripcion->execute();
    $result_inscripcion = $stmt_inscripcion->get_result();
    $esta_inscrito = $result_inscripcion->num_rows > 0;
    $stmt_inscripcion->close();
    
    // Verificar si ya comentó
    $sql_comentario = "SELECT id FROM experiencias WHERE usuario_id = ? AND voluntariado_id = ?";
    $stmt_comentario = $conn->prepare($sql_comentario);
    $stmt_comentario->bind_param("ii", $usuario_id, $voluntariado_id);
    $stmt_comentario->execute();
    $result_comentario = $stmt_comentario->get_result();
    $ya_comento = $result_comentario->num_rows > 0;
    $stmt_comentario->close();
    
    $conn->close();
    return $esta_inscrito && !$ya_comento;
}
function obtenerTodasLasExperiencias() {
    $conn = getDBConnection();
    $sql = "SELECT e.*, u.nombre as nombre_voluntario, v.titulo as titulo_voluntariado 
            FROM experiencias e 
            JOIN usuarios u ON e.usuario_id = u.id 
            JOIN voluntariados v ON e.voluntariado_id = v.id 
            ORDER BY e.fecha_creacion DESC";
    $result = $conn->query($sql);
    $experiencias = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $experiencias[] = $row;
        }
    }
    
    $conn->close();
    return $experiencias;
}

// Función para obtener voluntariados filtrados
function obtenerVoluntariadosFiltrados($departamento = '', $tipo = '', $organizacion = '') {
    $conn = getDBConnection();
    
    $sql = "SELECT v.* FROM voluntariados v WHERE v.activo = TRUE AND v.aprobado = TRUE";
    $params = [];
    $types = "";
    
    if (!empty($departamento)) {
        $sql .= " AND v.departamento = ?";
        $params[] = $departamento;
        $types .= "s";
    }
    
    if (!empty($tipo)) {
        $sql .= " AND v.tipo_voluntariado = ?";
        $params[] = $tipo;
        $types .= "s";
    }
    
    if (!empty($organizacion)) {
        $sql .= " AND v.organizacion LIKE ?";
        $params[] = "%$organizacion%";
        $types .= "s";
    }
    
    $sql .= " ORDER BY v.fecha_creacion DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $voluntariados = [];
    
    while ($row = $result->fetch_assoc()) {
        // Obtener horarios para cada voluntariado
        $row['horarios'] = obtenerHorariosVoluntariado($row['id']);
        $voluntariados[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $voluntariados;
}
// ==============================
// FUNCIÓN PARA VERIFICAR EMAIL ÚNICO
// ==============================
function verificarEmailUnico($email) {
    $conn = getDBConnection();
    
    // Verificar en todas las tablas
    $sql = "SELECT 
        (SELECT COUNT(*) FROM usuarios WHERE email = ?) as en_usuarios,
        (SELECT COUNT(*) FROM organizaciones WHERE email = ?) as en_organizaciones,
        (SELECT COUNT(*) FROM administradores WHERE email = ?) as en_administradores";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    // Si existe en alguna tabla, retornar false
    return ($data['en_usuarios'] == 0 && 
            $data['en_organizaciones'] == 0 && 
            $data['en_administradores'] == 0);
}

// Función para obtener en qué tablas existe el email (para mensajes de error)
function obtenerTablasConEmail($email) {
    $conn = getDBConnection();
    
    $sql = "SELECT 
        (SELECT COUNT(*) FROM usuarios WHERE email = ?) as en_usuarios,
        (SELECT COUNT(*) FROM organizaciones WHERE email = ?) as en_organizaciones,
        (SELECT COUNT(*) FROM administradores WHERE email = ?) as en_administradores";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    $tablas = [];
    if ($data['en_usuarios'] > 0) $tablas[] = 'voluntarios';
    if ($data['en_organizaciones'] > 0) $tablas[] = 'organizaciones';
    if ($data['en_administradores'] > 0) $tablas[] = 'administradores';
    
    return $tablas;
}
// Función para obtener horarios de un voluntariado
function obtenerHorariosVoluntariado($voluntariado_id) {
    $conn = getDBConnection();
    $sql = "SELECT * FROM horarios_voluntariado WHERE voluntariado_id = ? ORDER BY 
            FIELD(dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'), 
            hora_inicio";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $voluntariado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $horarios = [];
    while ($row = $result->fetch_assoc()) {
        $horarios[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $horarios;
}

// Función para guardar horarios
function guardarHorariosVoluntariado($voluntariado_id, $horarios) {
    $conn = getDBConnection();
    
    // Eliminar horarios existentes
    $sql_delete = "DELETE FROM horarios_voluntariado WHERE voluntariado_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $voluntariado_id);
    $stmt_delete->execute();
    $stmt_delete->close();
    
    // Insertar nuevos horarios
    if (!empty($horarios)) {
        $sql_insert = "INSERT INTO horarios_voluntariado (voluntariado_id, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        
        foreach ($horarios as $horario) {
            if (!empty($horario['dia']) && !empty($horario['hora_inicio']) && !empty($horario['hora_fin'])) {
                $stmt_insert->bind_param("isss", $voluntariado_id, $horario['dia'], $horario['hora_inicio'], $horario['hora_fin']);
                $stmt_insert->execute();
            }
        }
        $stmt_insert->close();
    }
    
    $conn->close();
    return true;
}
// Función para obtener horarios disponibles formateados para select
function obtenerHorariosParaSelect($voluntariado_id) {
    $horarios = obtenerHorariosVoluntariado($voluntariado_id);
    $opciones = [];
    
    foreach ($horarios as $horario) {
        $hora_inicio = substr($horario['hora_inicio'], 0, 5);
        $hora_fin = substr($horario['hora_fin'], 0, 5);
        $opciones[] = [
            'valor' => $horario['dia_semana'] . ' - ' . $hora_inicio . ' a ' . $hora_fin,
            'texto' => $horario['dia_semana'] . ' de ' . $hora_inicio . ' a ' . $hora_fin
        ];
    }
    
    return $opciones;
}

?>