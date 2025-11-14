<?php
// admin/mapa.php
define('ADMIN_AREA', true);
require_once '../config.php';
requireAuth();

$page_title = 'Mapa de Ubicaciones';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Obtener filtros
    $fecha = isset($_GET['fecha']) ? sanitize($_GET['fecha']) : date('Y-m-d');
    $tipo_marcacion = isset($_GET['tipo']) ? sanitize($_GET['tipo']) : '';
    
    // Consultar marcaciones con ubicación
    $sql = "
        SELECT m.*, CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo, u.cargo, u.departamento
        FROM marcaciones m
        INNER JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.latitud IS NOT NULL AND m.longitud IS NOT NULL
        AND m.fecha = ?
    ";
    $params = [$fecha];
    
    if (!empty($tipo_marcacion)) {
        $sql .= " AND m.tipo_marcacion = ?";
        $params[] = $tipo_marcacion;
    }
    
    $sql .= " ORDER BY m.fecha_hora_registro DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $marcaciones = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>🗺️ Mapa de Ubicaciones</h1>
    <p>Visualiza las ubicaciones de las marcaciones en tiempo real</p>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Filtros de Búsqueda</h2>
    </div>
    
    <form method="GET">
        <div class="form-row">
            <div class="form-group">
                <label for="fecha">Fecha:</label>
                <input type="date" name="fecha" id="fecha" class="form-control" value="<?php echo $fecha; ?>">
            </div>
            <div class="form-group">
                <label for="tipo">Tipo de Marcación:</label>
                <select name="tipo" id="tipo" class="form-control">
                    <option value="">Todas las marcaciones</option>
                    <option value="entrada" <?php echo $tipo_marcacion === 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                    <option value="salida" <?php echo $tipo_marcacion === 'salida' ? 'selected' : ''; ?>>Salida</option>
                    <option value="entrada_refrigerio" <?php echo $tipo_marcacion === 'entrada_refrigerio' ? 'selected' : ''; ?>>Entrada Refrigerio</option>
                    <option value="salida_refrigerio" <?php echo $tipo_marcacion === 'salida_refrigerio' ? 'selected' : ''; ?>>Salida Refrigerio</option>
                    <option value="entrada_campo" <?php echo $tipo_marcacion === 'entrada_campo' ? 'selected' : ''; ?>>Entrada Campo</option>
                    <option value="salida_campo" <?php echo $tipo_marcacion === 'salida_campo' ? 'selected' : ''; ?>>Salida Campo</option>
                </select>
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary" style="display: block; width: 100%;">🔍 Buscar</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Mapa de Marcaciones (<?php echo count($marcaciones); ?> registros)</h2>
    </div>
    
    <div id="map" style="width: 100%; height: 500px; border-radius: 8px;"></div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Lista de Marcaciones</h2>
    </div>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Tipo</th>
                    <th>Hora</th>
                    <th>Ubicación</th>
                    <th>Coordenadas</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($marcaciones) > 0): ?>
                    <?php foreach($marcaciones as $marca): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($marca['nombre_completo']); ?></strong><br>
                                <small style="color: #666;"><?php echo htmlspecialchars($marca['cargo']); ?></small>
                            </td>
                            <td>
                                <?php
                                $badges = [
                                    'entrada' => 'success',
                                    'salida' => 'danger',
                                    'entrada_refrigerio' => 'info',
                                    'salida_refrigerio' => 'warning',
                                    'entrada_campo' => 'info',
                                    'salida_campo' => 'warning'
                                ];
                                $badge_class = $badges[$marca['tipo_marcacion']] ?? 'info';
                                ?>
                                <span class="badge badge-<?php echo $badge_class; ?>">
                                    <?php echo formatTipoMarcacion($marca['tipo_marcacion']); ?>
                                </span>
                            </td>
                            <td><?php echo date('h:i A', strtotime($marca['hora'])); ?></td>
                            <td style="max-width: 250px; font-size: 12px;">
                                <?php echo htmlspecialchars($marca['direccion'] ?? 'No disponible'); ?>
                            </td>
                            <td>
                                <code style="font-size: 11px;"><?php echo $marca['latitud']; ?>, <?php echo $marca['longitud']; ?></code>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="centrarMapa(<?php echo $marca['latitud']; ?>, <?php echo $marca['longitud']; ?>)">
                                    📍 Ver
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #999; padding: 30px;">
                            No hay marcaciones con ubicación para esta fecha
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Leaflet.js para el mapa (OpenStreetMap) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<script>
// Datos de marcaciones
const marcaciones = <?php echo json_encode($marcaciones); ?>;

// Inicializar mapa
let map;
let markers = [];

function initMap() {
    // Lima, Perú como centro por defecto
    const defaultCenter = [-12.0464, -77.0428];
    const defaultZoom = 12;
    
    // Crear el mapa
    map = L.map('map').setView(defaultCenter, defaultZoom);
    
    // Agregar capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Agregar marcadores
    if (marcaciones.length > 0) {
        const bounds = [];
        
        marcaciones.forEach((marca, index) => {
            const lat = parseFloat(marca.latitud);
            const lng = parseFloat(marca.longitud);
            
            if (!isNaN(lat) && !isNaN(lng)) {
                bounds.push([lat, lng]);
                
                // Colores según tipo de marcación
                const colores = {
                    'entrada': 'green',
                    'salida': 'red',
                    'entrada_refrigerio': 'blue',
                    'salida_refrigerio': 'orange',
                    'entrada_campo': 'purple',
                    'salida_campo': 'pink'
                };
                
                const color = colores[marca.tipo_marcacion] || 'gray';
                
                // Crear ícono personalizado
                const icon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div style="background: ${color}; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">${getEmoji(marca.tipo_marcacion)}</div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });
                
                // Crear marcador
                const marker = L.marker([lat, lng], { icon: icon }).addTo(map);
                
                // Contenido del popup
                const popupContent = `
                    <div style="font-family: 'Segoe UI', sans-serif;">
                        <h3 style="margin: 0 0 10px 0; color: #333; font-size: 16px;">${marca.nombre_completo}</h3>
                        <p style="margin: 5px 0; font-size: 13px;"><strong>Cargo:</strong> ${marca.cargo}</p>
                        <p style="margin: 5px 0; font-size: 13px;"><strong>Departamento:</strong> ${marca.departamento}</p>
                        <p style="margin: 5px 0; font-size: 13px;"><strong>Tipo:</strong> <span style="color: ${color}; font-weight: 600;">${formatTipoMarcacion(marca.tipo_marcacion)}</span></p>
                        <p style="margin: 5px 0; font-size: 13px;"><strong>Hora:</strong> ${formatHora(marca.hora)}</p>
                        <p style="margin: 5px 0; font-size: 12px; color: #666;"><strong>Ubicación:</strong> ${marca.direccion || 'No disponible'}</p>
                        <p style="margin: 5px 0; font-size: 11px; color: #999;"><strong>Coordenadas:</strong> ${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
                        <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" style="display: inline-block; margin-top: 10px; padding: 5px 10px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-size: 12px;">Ver en Google Maps</a>
                    </div>
                `;
                
                marker.bindPopup(popupContent);
                markers.push(marker);
            }
        });
        
        // Ajustar el mapa para mostrar todos los marcadores
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    }
}

function getEmoji(tipo) {
    const emojis = {
        'entrada': '🟢',
        'salida': '🔴',
        'entrada_refrigerio': '🍽️',
        'salida_refrigerio': '☕',
        'entrada_campo': '🚗',
        'salida_campo': '🏢'
    };
    return emojis[tipo] || '📍';
}

function formatTipoMarcacion(tipo) {
    const tipos = {
        'entrada': 'Entrada',
        'salida': 'Salida',
        'entrada_refrigerio': 'Entrada Refrigerio',
        'salida_refrigerio': 'Salida Refrigerio',
        'entrada_campo': 'Entrada Campo',
        'salida_campo': 'Salida Campo'
    };
    return tipos[tipo] || tipo;
}

function formatHora(hora) {
    const [h, m] = hora.split(':');
    const horas = parseInt(h);
    const ampm = horas >= 12 ? 'PM' : 'AM';
    const horas12 = horas % 12 || 12;
    return `${horas12}:${m} ${ampm}`;
}

function centrarMapa(lat, lng) {
    map.setView([lat, lng], 17);
    
    // Abrir el popup del marcador correspondiente
    markers.forEach(marker => {
        const markerLatLng = marker.getLatLng();
        if (Math.abs(markerLatLng.lat - lat) < 0.0001 && Math.abs(markerLatLng.lng - lng) < 0.0001) {
            marker.openPopup();
        }
    });
}

// Inicializar el mapa cuando la página cargue
window.onload = initMap;
</script>

<?php include 'includes/footer.php'; ?>