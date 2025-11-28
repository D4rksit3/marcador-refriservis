<?php
require_once 'config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'empleado' => ''];

try {
    $dni = sanitize($_POST['dni'] ?? '');
    $tipo = sanitize($_POST['tipo'] ?? '');
    $latitud = floatval($_POST['latitud'] ?? 0);
    $longitud = floatval($_POST['longitud'] ?? 0);
    $direccion = sanitize($_POST['direccion'] ?? '');
    $foto_base64 = $_POST['foto_validacion'] ?? '';
    
    // Validaciones básicas
    if (!preg_match('/^[0-9]{8}$/', $dni)) {
        throw new Exception('DNI inválido');
    }
    
    $tipos_validos = ['entrada', 'salida', 'entrada_refrigerio', 'salida_refrigerio', 'entrada_campo', 'salida_campo'];
    if (!in_array($tipo, $tipos_validos)) {
        throw new Exception('Tipo de marcación inválido');
    }
    
    if ($latitud == 0 || $longitud == 0) {
        throw new Exception('Ubicación GPS no disponible');
    }
    
    // Verificar usuario
    $stmt = $pdo->prepare("SELECT id, nombres, apellidos, cargo, dia_descanso FROM usuarios WHERE dni = ? AND estado = 'activo'");
    $stmt->execute([$dni]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception('Usuario no encontrado o inactivo');
    }
    
    $hoy = date('Y-m-d');
    $hora = date('H:i:s');
    $dia_semana = (int) date('w'); // 0=Domingo
    
    // Verificar día de descanso
    if ($dia_semana == $usuario['dia_descanso']) {
        throw new Exception('Hoy es su día de descanso (' . getNombreDia($usuario['dia_descanso']) . ')');
    }
    
    // Verificar feriado
    $stmt = $pdo->prepare("SELECT nombre FROM feriados WHERE fecha = ?");
    $stmt->execute([$hoy]);
    $feriado = $stmt->fetch();
    if ($feriado) {
        throw new Exception('Hoy es feriado: ' . $feriado['nombre']);
    }
    
    // Verificar justificación
    $stmt = $pdo->prepare("
        SELECT j.*, tj.nombre as tipo_nombre 
        FROM justificaciones j
        INNER JOIN tipos_justificacion tj ON j.tipo_justificacion_id = tj.id
        WHERE j.usuario_id = ? AND ? BETWEEN j.fecha_inicio AND j.fecha_fin AND j.estado = 'aprobada'
    ");
    $stmt->execute([$usuario['id'], $hoy]);
    $justificacion = $stmt->fetch();
    if ($justificacion) {
        throw new Exception('Tiene una justificación activa: ' . $justificacion['tipo_nombre']);
    }
    
    // Verificar duplicado (misma marcación en últimos 5 minutos)
    $stmt = $pdo->prepare("
        SELECT id FROM marcaciones 
        WHERE usuario_id = ? AND fecha = ? AND tipo_marcacion = ? 
        AND TIMESTAMPDIFF(MINUTE, CONCAT(fecha, ' ', hora), NOW()) < 5
    ");
    $stmt->execute([$usuario['id'], $hoy, $tipo]);
    if ($stmt->fetch()) {
        throw new Exception('Ya realizó esta marcación hace menos de 5 minutos');
    }
    
    // Procesar foto si es marcación de campo
    $foto_ruta = null;
    if (in_array($tipo, ['entrada_campo', 'salida_campo'])) {
        if (empty($foto_base64)) {
            throw new Exception('La foto es obligatoria para marcaciones de campo');
        }
        
        $resultado = subirFotoCampo($foto_base64);
        if (!$resultado['success']) {
            throw new Exception('Error al guardar la foto: ' . $resultado['error']);
        }
        $foto_ruta = $resultado['ruta'];
    }
    
    // Insertar marcación
    $stmt = $pdo->prepare("
        INSERT INTO marcaciones (usuario_id, dni, tipo_marcacion, fecha, hora, latitud, longitud, direccion, foto_validacion, ip_address, user_agent, device_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $device_id = 'DEV_' . substr(md5($_SERVER['HTTP_USER_AGENT'] ?? '' . $_SERVER['REMOTE_ADDR'] ?? ''), 0, 10);
    
    $stmt->execute([
        $usuario['id'],
        $dni,
        $tipo,
        $hoy,
        $hora,
        $latitud,
        $longitud,
        $direccion,
        $foto_ruta,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $device_id
    ]);
    
    $tipo_texto = str_replace('_', ' ', ucfirst($tipo));
    $response['success'] = true;
    $response['message'] = $tipo_texto . ' registrada a las ' . date('H:i');
    $response['empleado'] = $usuario['nombres'] . ' ' . $usuario['apellidos'];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
