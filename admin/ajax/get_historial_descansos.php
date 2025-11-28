<?php
/**
 * Obtener historial de cambios de días de descanso
 */

require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$usuario_id = (int) ($_GET['usuario_id'] ?? 0);

if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$dias_semana = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];

$stmt = $pdo->prepare("
    SELECT h.*, a.nombre_completo as admin_nombre
    FROM historial_descansos h
    LEFT JOIN administradores a ON h.registrado_por = a.id
    WHERE h.usuario_id = ?
    ORDER BY h.created_at DESC
    LIMIT 20
");
$stmt->execute([$usuario_id]);
$historial = $stmt->fetchAll();

if (empty($historial)) {
    echo json_encode(['success' => false, 'message' => 'Sin historial']);
    exit;
}

$html = '<table class="table table-sm"><thead><tr><th>Fecha</th><th>Anterior</th><th>Nuevo</th><th>Motivo</th></tr></thead><tbody>';

foreach ($historial as $h) {
    $html .= '<tr>';
    $html .= '<td><small>' . date('d/m/Y H:i', strtotime($h['created_at'])) . '</small></td>';
    $html .= '<td><span class="badge bg-secondary">' . $dias_semana[$h['dia_anterior']] . '</span></td>';
    $html .= '<td><span class="badge bg-primary">' . $dias_semana[$h['dia_nuevo']] . '</span></td>';
    $html .= '<td><small>' . htmlspecialchars($h['motivo'] ?? '-') . '</small></td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';

echo json_encode(['success' => true, 'html' => $html]);
