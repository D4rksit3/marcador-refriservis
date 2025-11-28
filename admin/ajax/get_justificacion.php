<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM v_justificaciones_resumen WHERE id = ?");
$stmt->execute([$id]);
$j = $stmt->fetch();

if (!$j) {
    echo json_encode(['success' => false]);
    exit;
}

$html = '
<div class="row">
    <div class="col-md-6">
        <h6 class="text-muted">Empleado</h6>
        <p><strong>' . htmlspecialchars($j['nombre_empleado']) . '</strong><br>
        <small>DNI: ' . $j['dni'] . ' | ' . htmlspecialchars($j['cargo']) . ' - ' . htmlspecialchars($j['departamento']) . '</small></p>
    </div>
    <div class="col-md-6">
        <h6 class="text-muted">Tipo</h6>
        <span class="badge fs-6" style="background-color: ' . $j['color'] . '">
            <i class="fas ' . $j['icono'] . ' me-1"></i>' . htmlspecialchars($j['tipo_justificacion']) . '
        </span>
        ' . ($j['con_goce'] ? '' : '<br><small class="text-danger">Sin goce de haber</small>') . '
    </div>
</div>
<hr>
<div class="row">
    <div class="col-md-4"><h6 class="text-muted">Desde</h6><p>' . formatearFecha($j['fecha_inicio']) . '</p></div>
    <div class="col-md-4"><h6 class="text-muted">Hasta</h6><p>' . formatearFecha($j['fecha_fin']) . '</p></div>
    <div class="col-md-4"><h6 class="text-muted">Total</h6><p><span class="badge bg-primary fs-6">' . $j['dias_calendario'] . ' días</span></p></div>
</div>
<hr>
<h6 class="text-muted">Motivo</h6>
<p class="bg-light p-3 rounded">' . nl2br(htmlspecialchars($j['motivo'])) . '</p>';

if ($j['diagnostico']) {
    $html .= '
    <div class="alert alert-info">
        <h6><i class="fas fa-medkit me-2"></i>Información Médica</h6>
        <p class="mb-1"><strong>Diagnóstico:</strong> ' . htmlspecialchars($j['diagnostico']) . '</p>
        ' . ($j['medico_nombre'] ? '<p class="mb-1"><strong>Médico:</strong> ' . htmlspecialchars($j['medico_nombre']) . '</p>' : '') . '
        ' . ($j['centro_medico'] ? '<p class="mb-0"><strong>Centro:</strong> ' . htmlspecialchars($j['centro_medico']) . '</p>' : '') . '
    </div>';
}

if ($j['observaciones']) {
    $html .= '<h6 class="text-muted">Observaciones</h6><p>' . nl2br(htmlspecialchars($j['observaciones'])) . '</p>';
}

$html .= '<hr><small class="text-muted">Registrado por: ' . htmlspecialchars($j['registrado_por']) . ' | ' . formatearFecha($j['created_at'], 'd/m/Y H:i') . '</small>';

echo json_encode(['success' => true, 'html' => $html]);
