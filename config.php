
<?php
/**
 * =====================================================
 * CONFIGURACIÓN DEL SISTEMA DE MARCACIONES REFRISERVIS
 * =====================================================
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_marcaciones');
define('DB_USER', 'refriservis');
define('DB_PASS', '123456');


// Configuración del sistema
define('SITE_NAME', 'Sistema de Marcaciones - Refriservis');
define('TIMEZONE', 'America/Lima');

// Configuración de uploads
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('EVIDENCIAS_PATH', UPLOAD_PATH . 'evidencias/');
define('FOTOS_CAMPO_PATH', UPLOAD_PATH . 'fotos_campo/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Configurar zona horaria
date_default_timezone_set(TIMEZONE);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function getNombreDia($numero) {
    $dias = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
    return $dias[$numero] ?? 'Desconocido';
}

function getNombreMes($numero) {
    $meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 
              7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    return $meses[$numero] ?? '';
}

function formatearFecha($fecha, $formato = 'd/m/Y') {
    return date($formato, strtotime($fecha));
}

function calcularDiasLaborables($fecha_inicio, $fecha_fin, $dia_descanso = 0) {
    global $pdo;
    
    $dias = 0;
    $fecha = new DateTime($fecha_inicio);
    $fin = new DateTime($fecha_fin);
    
    // Obtener feriados
    $stmt = $pdo->prepare("SELECT fecha FROM feriados WHERE fecha BETWEEN ? AND ?");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $feriados = array_column($stmt->fetchAll(), 'fecha');
    
    while ($fecha <= $fin) {
        $dia_semana = (int) $fecha->format('w');
        $fecha_str = $fecha->format('Y-m-d');
        
        // Si no es día de descanso ni feriado
        if ($dia_semana != $dia_descanso && !in_array($fecha_str, $feriados)) {
            $dias++;
        }
        $fecha->modify('+1 day');
    }
    
    return $dias;
}

// =====================================================
// FUNCIONES DE ESTADO DE DÍA
// =====================================================

function getEstadoDia($usuario_id, $fecha) {
    global $pdo;
    
    // Obtener día de descanso del usuario
    $stmt = $pdo->prepare("SELECT dia_descanso FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();
    $dia_descanso = $usuario['dia_descanso'] ?? 0;
    
    $dia_semana = (int) date('w', strtotime($fecha));
    
    // Es día de descanso
    if ($dia_semana == $dia_descanso) {
        return ['estado' => 'DESCANSO', 'color' => '#17a2b8', 'icono' => 'fa-bed'];
    }
    
    // Es feriado
    $stmt = $pdo->prepare("SELECT nombre FROM feriados WHERE fecha = ?");
    $stmt->execute([$fecha]);
    $feriado = $stmt->fetch();
    if ($feriado) {
        return ['estado' => 'FERIADO', 'color' => '#6f42c1', 'icono' => 'fa-flag', 'detalle' => $feriado['nombre']];
    }
    
    // Tiene justificación
    $stmt = $pdo->prepare("
        SELECT j.*, tj.nombre as tipo_nombre, tj.color, tj.icono
        FROM justificaciones j
        INNER JOIN tipos_justificacion tj ON j.tipo_justificacion_id = tj.id
        WHERE j.usuario_id = ? AND ? BETWEEN j.fecha_inicio AND j.fecha_fin AND j.estado = 'aprobada'
        LIMIT 1
    ");
    $stmt->execute([$usuario_id, $fecha]);
    $justificacion = $stmt->fetch();
    
    if ($justificacion) {
        return [
            'estado' => strtoupper($justificacion['tipo_nombre']),
            'color' => $justificacion['color'],
            'icono' => $justificacion['icono'],
            'justificacion' => $justificacion
        ];
    }
    
    // Tiene marcación
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM marcaciones WHERE usuario_id = ? AND fecha = ?");
    $stmt->execute([$usuario_id, $fecha]);
    if ($stmt->fetch()['total'] > 0) {
        return ['estado' => 'ASISTIÓ', 'color' => '#28a745', 'icono' => 'fa-check-circle'];
    }
    
    // Fecha futura
    if (strtotime($fecha) > strtotime(date('Y-m-d'))) {
        return ['estado' => 'PENDIENTE', 'color' => '#6c757d', 'icono' => 'fa-clock'];
    }
    
    // Falta
    return ['estado' => 'FALTA', 'color' => '#dc3545', 'icono' => 'fa-times-circle'];
}

// =====================================================
// FUNCIONES DE ARCHIVOS
// =====================================================

function crearDirectorios() {
    $dirs = [UPLOAD_PATH, EVIDENCIAS_PATH, FOTOS_CAMPO_PATH];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

function subirArchivo($file, $directorio, $prefijo = 'doc') {
    crearDirectorios();
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir el archivo'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'El archivo excede el tamaño máximo (10MB)'];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime, ALLOWED_DOC_TYPES)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombre = $prefijo . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $ruta = $directorio . $nombre;
    
    if (move_uploaded_file($file['tmp_name'], $ruta)) {
        return [
            'success' => true,
            'nombre' => $nombre,
            'original' => $file['name'],
            'tipo' => $mime,
            'tamano' => $file['size'],
            'ruta' => str_replace(__DIR__ . '/', '', $ruta)
        ];
    }
    
    return ['success' => false, 'error' => 'No se pudo guardar el archivo'];
}

function subirFotoCampo($base64_image) {
    crearDirectorios();
    
    if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $matches)) {
        $ext = $matches[1];
        $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
    } else {
        return ['success' => false, 'error' => 'Formato de imagen inválido'];
    }
    
    $data = base64_decode($base64_image);
    if ($data === false || strlen($data) > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'Imagen inválida o muy grande'];
    }
    
    $nombre = 'campo_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $ruta = FOTOS_CAMPO_PATH . $nombre;
    
    if (file_put_contents($ruta, $data)) {
        return ['success' => true, 'nombre' => $nombre, 'ruta' => 'uploads/fotos_campo/' . $nombre];
    }
    
    return ['success' => false, 'error' => 'No se pudo guardar la imagen'];
}

// =====================================================
// FUNCIONES DE VACACIONES
// =====================================================

function getSaldoVacaciones($usuario_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT vp.*, u.fecha_ingreso,
               DATEDIFF(vp.fecha_vencimiento, CURDATE()) as dias_para_vencer
        FROM vacaciones_periodos vp
        INNER JOIN usuarios u ON vp.usuario_id = u.id
        WHERE vp.usuario_id = ? AND vp.estado = 'vigente'
        ORDER BY vp.periodo_inicio DESC
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetch();
}

function getHistorialVacaciones($usuario_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT j.*, tj.nombre as tipo
        FROM justificaciones j
        INNER JOIN tipos_justificacion tj ON j.tipo_justificacion_id = tj.id
        WHERE j.usuario_id = ? AND tj.codigo = 'VAC' AND j.estado = 'aprobada'
        ORDER BY j.fecha_inicio DESC
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll();
}

// Crear directorios al cargar
crearDirectorios();