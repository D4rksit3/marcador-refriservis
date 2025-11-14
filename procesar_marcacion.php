<?php
// procesar_marcacion.php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

$dni = isset($_POST['dni']) ? sanitize($_POST['dni']) : '';
$tipo = isset($_POST['tipo']) ? sanitize($_POST['tipo']) : '';
$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;

// Validaciones del DNI (necesarias para ambas rutas: validar y marcar)
if (empty($dni)) {
    jsonResponse(false, 'Por favor, ingresa tu DNI');
}


# ----------------------------------------
# RUTA 1: VALIDAR SOLO DNI (sin registrar marcación)
# ----------------------------------------
if (isset($_POST['validar'])) {

    // Nota: El parámetro $dni ya fue sanitizado arriba.
    
    // Asumiendo que $pdo está disponible (de config.php o global)
    global $pdo; 
    
    // Usaremos un TRY-CATCH para asegurarnos de que el PDO sea válido
    try {
        $q = $pdo->prepare("SELECT dni, nombres, apellidos FROM empleados WHERE dni = ?");
        $q->execute([$dni]);
    
        if ($q->rowCount() > 0) {
            $empleado = $q->fetch(PDO::FETCH_ASSOC);
            $nombre_completo = $empleado['nombres'] . ' ' . $empleado['apellidos'];

            // Devolvemos el nombre para el modal
            echo json_encode(["success" => true, "data" => ["nombre" => $nombre_completo]]);
        } else {
            echo json_encode(["success" => false, "message" => "DNI no encontrado."]);
        }
    } catch(PDOException $e) {
        // En caso de error de conexión o consulta durante la validación
        echo json_encode(["success" => false, "message" => "Error de base de datos al validar DNI."]);
    }
    
    exit;
}


# ----------------------------------------
# RUTA 2: REGISTRAR MARCACIÓN
# ----------------------------------------

// A partir de aquí, la marcación es obligatoria, validamos los parámetros adicionales

// Validación del TIPO y Ubicación movida aquí:
if (empty($tipo)) {
    // Esto se lanza si no se envió 'validar' y el tipo está vacío (lo cual es incorrecto para una marcación)
    jsonResponse(false, 'Tipo de marcación no válido. Selecciona una opción del modal.');
}

$tiposValidos = ['entrada', 'salida', 'entrada_refrigerio', 'salida_refrigerio', 'entrada_campo', 'salida_campo'];
if (!in_array($tipo, $tiposValidos)) {
    jsonResponse(false, 'Tipo de marcación no válido');
}

if ($lat === null || $lng === null) {
    jsonResponse(false, 'No se pudo obtener la ubicación. Verifica el GPS.');
}


try {
    // Usamos el mismo patrón que el ejemplo anterior para obtener la conexión
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verificar si el usuario existe (usando la tabla correcta: 'usuarios')
    $stmt = $conn->prepare("SELECT id, nombres, apellidos, estado FROM usuarios WHERE dni = ? LIMIT 1");
    $stmt->execute([$dni]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        jsonResponse(false, 'DNI no válido. El usuario no existe en el sistema. Por favor, verifica tu DNI o contacta al administrador.');
    }
    
    if ($usuario['estado'] !== 'activo') {
        jsonResponse(false, 'Usuario inactivo. Contacta al administrador.');
    }
    
    // Obtener dirección de las coordenadas (Asumiendo que getAddressFromCoordinates está definido en config.php)
    $direccion = getAddressFromCoordinates($lat, $lng);
    
    // Registrar marcación
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');
    
    $stmt = $conn->prepare("
        INSERT INTO marcaciones (usuario_id, dni, tipo_marcacion, fecha, hora, latitud, longitud, direccion) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $usuario['id'],
        $dni,
        $tipo,
        $fecha,
        $hora,
        $lat,
        $lng,
        $direccion
    ]);
    
    // Función de formato (Asumiendo que formatTipoMarcacion está definido en config.php)
    jsonResponse(true, 'Marcación registrada exitosamente', [
        'nombre' => $usuario['nombres'] . ' ' . $usuario['apellidos'],
        'dni' => $dni,
        'tipo' => formatTipoMarcacion($tipo),
        'fecha' => date('d/m/Y'),
        'hora' => date('h:i A'),
        'latitud' => $lat,
        'longitud' => $lng,
        'direccion' => $direccion
    ]);
    
} catch(PDOException $e) {
    jsonResponse(false, 'Error al registrar la marcación: ' . $e->getMessage());
}
?>