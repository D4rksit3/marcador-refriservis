<?php
// config.php - Archivo de configuración

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_marcaciones');
define('DB_USER', 'refriservis');
define('DB_PASS', '123456');

// Configuración general
define('SITE_NAME', 'Sistema de Marcaciones');
define('TIMEZONE', 'America/Lima');

// Establecer zona horaria
date_default_timezone_set(TIMEZONE);

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Clase de conexión a la base de datos
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn = null;

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Error de conexión: " . $e->getMessage();
        }
        return $this->conn;
    }
}

// Función para verificar si el usuario está autenticado
function isAuthenticated() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_usuario']);
}

// Función para redirigir si no está autenticado
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit();
    }
}

// Función para sanitizar datos
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Función para generar respuesta JSON
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Función para obtener dirección desde coordenadas usando Nominatim (OpenStreetMap)
function getAddressFromCoordinates($lat, $lng) {
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Sistema de Marcaciones');
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['display_name'])) {
            return $data['display_name'];
        }
    }
    
    return "Dirección no disponible";
}

// Función para formatear tipo de marcación
function formatTipoMarcacion($tipo) {
    $tipos = [
        'entrada' => 'Entrada',
        'salida' => 'Salida',
        'entrada_refrigerio' => 'Entrada Refrigerio',
        'salida_refrigerio' => 'Salida Refrigerio',
        'entrada_campo' => 'Entrada Campo',
        'salida_campo' => 'Salida Campo'
    ];
    return $tipos[$tipo] ?? $tipo;
}
?>