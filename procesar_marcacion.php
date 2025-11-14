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

// Validaciones del DNI (necesarias para ambas rutas)
if (empty($dni)) {
    jsonResponse(false, 'Por favor, ingresa tu DNI');
}


# ----------------------------------------
# RUTA 1: VALIDAR SOLO DNI (sin registrar marcación)
# ----------------------------------------
if (isset($_POST['validar'])) {

    try {
        // Usamos la clase Database para obtener la conexión
        $db = new Database();
        $conn = $db->getConnection(); 
        
        // La conexión fallará si las credenciales en config.php son incorrectas.
        if (!$conn) {
             jsonResponse(false, 'Error interno: No se pudo establecer la conexión a la base de datos.');
        }

        // Buscamos en la tabla 'usuarios' para obtener el nombre completo
        $q = $conn->prepare("SELECT dni, nombres, apellidos FROM usuarios WHERE dni = ? LIMIT 1");
        $q->execute([$dni]);
    
        if ($q->rowCount() > 0) {
            $empleado = $q->fetch(PDO::FETCH_ASSOC);
            $nombre_completo = $empleado['nombres'] . ' ' . $empleado['apellidos'];

            // Devolvemos el nombre para el modal
            jsonResponse(true, 'DNI validado', ["nombre" => $nombre_completo]);
        } else {
            jsonResponse(false, "DNI no encontrado. Por favor, verifica tu número.");
        }
    } catch(PDOException $e) {
        // Error de conexión o consulta durante la validación
        jsonResponse(false, "Error de base de datos al validar DNI: " . $e->getMessage());
    }
    // No necesitamos exit() aquí porque jsonResponse ya lo incluye.
}


# ----------------------------------------
# RUTA 2: REGISTRAR MARCACIÓN
# ----------------------------------------

// A partir de aquí, es obligatorio el tipo y la ubicación.

// Validación del TIPO
$tiposValidos = ['entrada', 'salida', 'entrada_refrigerio', 'salida_refrigerio', 'entrada_campo', 'salida_campo'];
if (empty($tipo) || !in_array($tipo, $tiposValidos)) {
    jsonResponse(false, 'Tipo de marcación no válido. Selecciona una opción del modal.');
}

// Validación de la Ubicación
if ($lat === null || $lng === null) {
    jsonResponse(false, 'No se pudo obtener la ubicación. Verifica el GPS.');
}


try {
    // Obtenemos la conexión nuevamente para la marcación
    $db = new Database();
    $conn = $db->getConnection(); 
    
    if (!$conn) {
        jsonResponse(false, 'Error interno: No se pudo establecer la conexión a la base de datos.');
    }

    // 1. Verificar si el usuario existe y está activo
    $stmt = $conn->prepare("SELECT id, nombres, apellidos, estado FROM usuarios WHERE dni = ? LIMIT 1");
    $stmt->execute([$dni]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        jsonResponse(false, 'DNI no válido. El usuario no existe en el sistema.');
    }
    
    if ($usuario['estado'] !== 'activo') {
        jsonResponse(false, 'Usuario inactivo. Contacta al administrador.');
    }
    
    // 2. Obtener dirección de las coordenadas (función de config.php)
    $direccion = getAddressFromCoordinates($lat, $lng);
    
    // 3. Registrar marcación
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
    
    // 4. Respuesta de éxito
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