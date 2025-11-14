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


# ----------------------------------------
# VALIDAR SOLO DNI (sin registrar marcación)
# ----------------------------------------
if (isset($_POST['validar'])) {

    $dni = $_POST['dni'];

    $q = $pdo->prepare("SELECT id FROM empleados WHERE dni = ?");
    $q->execute([$dni]);

    if ($q->rowCount() > 0) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
    exit;
}


try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verificar si el usuario existe
    $stmt = $conn->prepare("SELECT id, nombres, apellidos, estado FROM usuarios WHERE dni = ? LIMIT 1");
    $stmt->execute([$dni]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        jsonResponse(false, 'DNI no válido. El usuario no existe en el sistema. Por favor, verifica tu DNI o contacta al administrador.');
    }
    
    if ($usuario['estado'] !== 'activo') {
        jsonResponse(false, 'Usuario inactivo. Contacta al administrador.');
    }
    
    // Obtener dirección de las coordenadas
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