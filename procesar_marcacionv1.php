<?php
// procesar_marcacion.php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

$dni = isset($_POST['dni']) ? sanitize($_POST['dni']) : '';
$tipo = isset($_POST['tipo_marcacion']) ? sanitize($_POST['tipo_marcacion']) : (isset($_POST['tipo']) ? sanitize($_POST['tipo']) : '');
$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$device_id = isset($_POST['device_id']) ? sanitize($_POST['device_id']) : '';
$user_agent = isset($_POST['user_agent']) ? sanitize($_POST['user_agent']) : '';
$ip_address = $_SERVER['REMOTE_ADDR'];

// Validaciones
if (empty($dni)) {
    jsonResponse(false, 'Por favor, ingresa tu DNI');
}

if (empty($tipo)) {
    jsonResponse(false, 'Tipo de marcación no válido');
}

$tiposValidos = ['entrada', 'salida', 'entrada_refrigerio', 'salida_refrigerio', 'entrada_campo', 'salida_campo'];
if (!in_array($tipo, $tiposValidos)) {
    jsonResponse(false, 'Tipo de marcación no válido');
}

if ($lat === null || $lng === null) {
    jsonResponse(false, 'No se pudo obtener la ubicación');
}

if (empty($device_id)) {
    jsonResponse(false, 'No se pudo identificar el dispositivo');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verificar si el usuario existe
    $stmt = $conn->prepare("SELECT id, nombres, apellidos, estado, horario_entrada, horario_salida, tolerancia_minutos FROM usuarios WHERE dni = ? LIMIT 1");
    $stmt->execute([$dni]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        jsonResponse(false, 'DNI no válido. El usuario no existe en el sistema. Por favor, verifica tu DNI o contacta al administrador.');
    }
    
    if ($usuario['estado'] !== 'activo') {
        jsonResponse(false, 'Usuario inactivo. Contacta al administrador.');
    }
    
    // VALIDACIÓN DE DISPOSITIVO - Un dispositivo solo puede tener un DNI activo por día
    $fecha_actual = date('Y-m-d');
    
    // Verificar si este dispositivo tiene alguna marcación de ENTRADA activa (sin su respectiva SALIDA)
    $stmt = $conn->prepare("
        SELECT 
            dni,
            tipo_marcacion,
            DATE_FORMAT(hora, '%H:%i') as hora_marcacion
        FROM marcaciones 
        WHERE device_id = ? 
        AND fecha = ?
        AND tipo_marcacion IN ('entrada', 'entrada_campo')
        AND NOT EXISTS (
            SELECT 1 FROM marcaciones m2 
            WHERE m2.device_id = marcaciones.device_id 
            AND m2.dni = marcaciones.dni
            AND m2.fecha = marcaciones.fecha
            AND m2.fecha_hora_registro > marcaciones.fecha_hora_registro
            AND (
                (marcaciones.tipo_marcacion = 'entrada' AND m2.tipo_marcacion = 'salida')
                OR (marcaciones.tipo_marcacion = 'entrada_campo' AND m2.tipo_marcacion = 'salida_campo')
            )
        )
        ORDER BY fecha_hora_registro DESC
        LIMIT 1
    ");
    $stmt->execute([$device_id, $fecha_actual]);
    $marcacion_activa = $stmt->fetch();
    
    // Si hay una marcación de entrada activa (sin salida correspondiente)
    if ($marcacion_activa) {
        $dni_activo = $marcacion_activa['dni'];
        $tipo_activo = $marcacion_activa['tipo_marcacion'];
        $hora_activa = $marcacion_activa['hora_marcacion'];
        
        // Si está intentando marcar con un DNI DIFERENTE
        if ($dni !== $dni_activo) {
            $tipo_entrada_texto = $tipo_activo === 'entrada' ? 'ENTRADA' : 'ENTRADA CAMPO';
            $tipo_salida_esperada = $tipo_activo === 'entrada' ? 'SALIDA' : 'SALIDA CAMPO';
            
            jsonResponse(false, '🚫 DISPOSITIVO OCUPADO<br><br>' .
                'Este dispositivo tiene una marcación activa:<br><br>' .
                '<strong>DNI:</strong> ' . $dni_activo . '<br>' .
                '<strong>Tipo:</strong> ' . $tipo_entrada_texto . '<br>' .
                '<strong>Hora:</strong> ' . $hora_activa . '<br><br>' .
                '⚠️ <strong>Debe marcar ' . $tipo_salida_esperada . ' primero</strong> con el DNI <strong>' . $dni_activo . '</strong><br>' .
                'antes de poder usar este dispositivo con otro DNI.<br><br>' .
                '💡 <strong>Opciones:</strong><br>' .
                '• Marca ' . $tipo_salida_esperada . ' con DNI ' . $dni_activo . '<br>' .
                '• O usa otro dispositivo para marcar con DNI ' . $dni
            );
        }
        
        // Si es el MISMO DNI, validar que el tipo de marcación sea correcto
        if ($dni === $dni_activo) {
            // Determinar qué tipo de salida debe marcar
            $salida_esperada = $tipo_activo === 'entrada' ? 'salida' : 'salida_campo';
            
            // Si está intentando marcar otra entrada sin haber marcado la salida correspondiente
            if ($tipo === 'entrada' || $tipo === 'entrada_campo') {
                $tipo_entrada_texto = $tipo_activo === 'entrada' ? 'ENTRADA' : 'ENTRADA CAMPO';
                $tipo_salida_esperada_texto = $tipo_activo === 'entrada' ? 'SALIDA' : 'SALIDA CAMPO';
                
                jsonResponse(false, '⚠️ MARCACIÓN PENDIENTE<br><br>' .
                    'Ya tienes una <strong>' . $tipo_entrada_texto . '</strong> registrada a las <strong>' . $hora_activa . '</strong>.<br><br>' .
                    '🔴 <strong>Debes marcar ' . $tipo_salida_esperada_texto . ' primero</strong><br>' .
                    'antes de poder realizar otra marcación de entrada.<br><br>' .
                    '💡 Completa el ciclo: ' . $tipo_entrada_texto . ' → ' . $tipo_salida_esperada_texto
                );
            }
            
            // Si está marcando salida_refrigerio o entrada_refrigerio, permitir (no afecta el ciclo principal)
            if ($tipo === 'salida_refrigerio' || $tipo === 'entrada_refrigerio') {
                // Permitir - no hacer nada, continuar con el registro
            }
            
            // Si está marcando la salida INCORRECTA
            else if ($tipo !== $salida_esperada && in_array($tipo, ['salida', 'salida_campo'])) {
                $tipo_entrada_texto = $tipo_activo === 'entrada' ? 'ENTRADA' : 'ENTRADA CAMPO';
                $tipo_salida_correcta = $tipo_activo === 'entrada' ? 'SALIDA' : 'SALIDA CAMPO';
                $tipo_salida_incorrecta = $tipo === 'salida' ? 'SALIDA' : 'SALIDA CAMPO';
                
                jsonResponse(false, '⚠️ TIPO DE SALIDA INCORRECTO<br><br>' .
                    'Tu última marcación fue <strong>' . $tipo_entrada_texto . '</strong> a las <strong>' . $hora_activa . '</strong>.<br><br>' .
                    '✅ Debes marcar: <strong>' . $tipo_salida_correcta . '</strong><br>' .
                    '❌ Estás intentando: <strong>' . $tipo_salida_incorrecta . '</strong><br><br>' .
                    'Por favor, marca el tipo de salida correspondiente.'
                );
            }
            // Si está marcando la salida CORRECTA, permitir - continuar con el registro
        }
    }
    
    // Obtener dirección de las coordenadas
    $direccion = getAddressFromCoordinates($lat, $lng);
    
    // Registrar marcación
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');
    
    $stmt = $conn->prepare("
        INSERT INTO marcaciones (usuario_id, dni, tipo_marcacion, fecha, hora, latitud, longitud, direccion, device_id, user_agent, ip_address) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $usuario['id'],
        $dni,
        $tipo,
        $fecha,
        $hora,
        $lat,
        $lng,
        $direccion,
        $device_id,
        $user_agent,
        $ip_address
    ]);
    
    // Calcular estado según horario (solo para entrada y salida)
    $estado_horario = '';
    $color_estado = '';
    
    if ($tipo === 'entrada') {
        $hora_entrada = new DateTime($hora);
        $horario_entrada = new DateTime($usuario['horario_entrada']);
        $tolerancia = new DateTime($usuario['horario_entrada']);
        $tolerancia->modify('+' . $usuario['tolerancia_minutos'] . ' minutes');
        
        if ($hora_entrada <= $horario_entrada) {
            $estado_horario = '✅ A tiempo';
            $color_estado = '#10b981';
        } elseif ($hora_entrada <= $tolerancia) {
            $minutos = round(($hora_entrada->getTimestamp() - $horario_entrada->getTimestamp()) / 60);
            $estado_horario = '⚠️ Dentro de tolerancia (' . $minutos . ' min)';
            $color_estado = '#f59e0b';
        } else {
            $minutos = round(($hora_entrada->getTimestamp() - $tolerancia->getTimestamp()) / 60);
            $estado_horario = '❌ Tardanza (' . $minutos . ' min)';
            $color_estado = '#ef4444';
        }
    } elseif ($tipo === 'salida') {
        $hora_salida = new DateTime($hora);
        $horario_salida = new DateTime($usuario['horario_salida']);
        
        if ($hora_salida <= $horario_salida) {
            $estado_horario = '✅ Salida regular';
            $color_estado = '#10b981';
        } else {
            $minutos = round(($hora_salida->getTimestamp() - $horario_salida->getTimestamp()) / 60);
            $estado_horario = '⭐ Horas extras (' . $minutos . ' min)';
            $color_estado = '#8b5cf6';
        }
    }
    
    jsonResponse(true, 'Marcación registrada exitosamente', [
        'nombre' => $usuario['nombres'] . ' ' . $usuario['apellidos'],
        'dni' => $dni,
        'tipo' => formatTipoMarcacion($tipo),
        'fecha' => date('d/m/Y'),
        'hora' => date('h:i A'),
        'latitud' => $lat,
        'longitud' => $lng,
        'direccion' => $direccion,
        'estado_horario' => $estado_horario,
        'color_estado' => $color_estado
    ]);
    
} catch(PDOException $e) {
    jsonResponse(false, 'Error al registrar la marcación: ' . $e->getMessage());
}
?>
