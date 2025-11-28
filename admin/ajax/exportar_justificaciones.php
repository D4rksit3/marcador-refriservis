<?php
require_once '../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: ../login.php'); exit; }

$formato = $_GET['formato'] ?? 'excel';
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_mes = $_GET['mes'] ?? date('Y-m');

$sql = "SELECT * FROM v_justificaciones_resumen WHERE estado = 'aprobada'";
$params = [];
if ($filtro_usuario) { $sql .= " AND usuario_id = ?"; $params[] = $filtro_usuario; }
if ($filtro_tipo) { $sql .= " AND tipo_codigo = ?"; $params[] = $filtro_tipo; }
if ($filtro_mes) { $sql .= " AND (DATE_FORMAT(fecha_inicio, '%Y-%m') = ? OR DATE_FORMAT(fecha_fin, '%Y-%m') = ?)"; $params[] = $filtro_mes; $params[] = $filtro_mes; }
$sql .= " ORDER BY fecha_inicio DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $datos = $stmt->fetchAll();

if ($formato === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="justificaciones_' . date('Y-m-d') . '.xls"');
    echo "\xEF\xBB\xBF";
    echo "REPORTE DE JUSTIFICACIONES - " . SITE_NAME . "\n";
    echo "Generado: " . date('d/m/Y H:i:s') . "\n\n";
    echo "DNI\tEmpleado\tDepartamento\tTipo\tDesde\tHasta\tDías\tMotivo\tCon Goce\n";
    foreach ($datos as $r) {
        echo implode("\t", [$r['dni'], $r['nombre_empleado'], $r['departamento'] ?? '-', $r['tipo_justificacion'],
            date('d/m/Y', strtotime($r['fecha_inicio'])), date('d/m/Y', strtotime($r['fecha_fin'])),
            $r['dias_calendario'], str_replace(["\n", "\r", "\t"], ' ', $r['motivo']), $r['con_goce'] ? 'Sí' : 'No']) . "\n";
    }
} else { ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reporte</title>
<style>body{font-family:Arial;font-size:11px;margin:20px}h1{text-align:center;font-size:16px}table{width:100%;border-collapse:collapse;margin-top:15px}th,td{border:1px solid #ddd;padding:5px;text-align:left}th{background:#667eea;color:white}.no-print{margin-bottom:20px;text-align:center}@media print{.no-print{display:none}}</style>
</head><body>
<div class="no-print"><button onclick="window.print()">🖨️ Imprimir / PDF</button></div>
<h1>REPORTE DE JUSTIFICACIONES</h1><p style="text-align:center">Generado: <?= date('d/m/Y H:i') ?></p>
<table><thead><tr><th>DNI</th><th>Empleado</th><th>Tipo</th><th>Desde</th><th>Hasta</th><th>Días</th><th>Motivo</th></tr></thead><tbody>
<?php foreach ($datos as $r): ?><tr><td><?= $r['dni'] ?></td><td><?= htmlspecialchars($r['nombre_empleado']) ?></td><td><?= htmlspecialchars($r['tipo_justificacion']) ?></td><td><?= date('d/m/Y', strtotime($r['fecha_inicio'])) ?></td><td><?= date('d/m/Y', strtotime($r['fecha_fin'])) ?></td><td style="text-align:center"><?= $r['dias_calendario'] ?></td><td><?= htmlspecialchars(substr($r['motivo'], 0, 50)) ?></td></tr><?php endforeach; ?>
</tbody></table><p style="text-align:center;margin-top:20px">Total: <?= count($datos) ?> registros</p>
</body></html>
<?php }
