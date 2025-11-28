<?php
require_once '../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$hoy = date('Y-m-d');

// Estadísticas
$total_empleados = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'activo'")->fetchColumn();
$marcaciones_hoy = $pdo->prepare("SELECT COUNT(DISTINCT usuario_id) FROM marcaciones WHERE fecha = ?");
$marcaciones_hoy->execute([$hoy]);
$marcaciones_hoy = $marcaciones_hoy->fetchColumn();

$justificaciones_hoy = $pdo->prepare("SELECT COUNT(*) FROM justificaciones WHERE estado = 'aprobada' AND ? BETWEEN fecha_inicio AND fecha_fin");
$justificaciones_hoy->execute([$hoy]);
$justificaciones_hoy = $justificaciones_hoy->fetchColumn();

// Últimas marcaciones
$ultimas = $pdo->prepare("
    SELECT m.*, CONCAT(u.nombres, ' ', u.apellidos) as nombre, u.dni
    FROM marcaciones m
    INNER JOIN usuarios u ON m.usuario_id = u.id
    WHERE m.fecha = ? ORDER BY m.hora DESC LIMIT 10
");
$ultimas->execute([$hoy]);
$ultimas_marcaciones = $ultimas->fetchAll();

// Justificaciones activas
$just_activas = $pdo->prepare("
    SELECT j.*, CONCAT(u.nombres, ' ', u.apellidos) as nombre, tj.nombre as tipo, tj.color
    FROM justificaciones j
    INNER JOIN usuarios u ON j.usuario_id = u.id
    INNER JOIN tipos_justificacion tj ON j.tipo_justificacion_id = tj.id
    WHERE j.estado = 'aprobada' AND ? BETWEEN j.fecha_inicio AND j.fecha_fin
    ORDER BY j.fecha_fin
");
$just_activas->execute([$hoy]);
$justificaciones_activas = $just_activas->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
    
    <!-- Tarjetas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div><h6 class="opacity-75">Empleados</h6><h2><?= $total_empleados ?></h2></div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div><h6 class="opacity-75">Asistencia Hoy</h6><h2><?= $marcaciones_hoy ?></h2></div>
                        <i class="fas fa-user-check fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div><h6 class="opacity-75">Justificaciones</h6><h2><?= $justificaciones_hoy ?></h2></div>
                        <i class="fas fa-file-medical fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div><h6 class="opacity-75">% Asistencia</h6><h2><?= $total_empleados > 0 ? round(($marcaciones_hoy / $total_empleados) * 100) : 0 ?>%</h2></div>
                        <i class="fas fa-chart-pie fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Últimas Marcaciones -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><h5 class="mb-0"><i class="fas fa-clock me-2"></i>Marcaciones de Hoy</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Empleado</th><th>Tipo</th><th>Hora</th></tr></thead>
                        <tbody>
                            <?php if (empty($ultimas_marcaciones)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">Sin marcaciones hoy</td></tr>
                            <?php else: ?>
                            <?php foreach ($ultimas_marcaciones as $m): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($m['nombre']) ?></strong><br><small class="text-muted"><?= $m['dni'] ?></small></td>
                                <td><span class="badge bg-<?= str_contains($m['tipo_marcacion'], 'entrada') ? 'success' : 'danger' ?>"><?= ucfirst(str_replace('_', ' ', $m['tipo_marcacion'])) ?></span></td>
                                <td><?= date('H:i:s', strtotime($m['hora'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Justificaciones Activas -->
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-warning"><h5 class="mb-0"><i class="fas fa-file-medical me-2"></i>Ausencias Justificadas Hoy</h5></div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($justificaciones_activas)): ?>
                        <li class="list-group-item text-center py-4 text-muted">No hay ausencias justificadas</li>
                        <?php else: ?>
                        <?php foreach ($justificaciones_activas as $j): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?= htmlspecialchars($j['nombre']) ?></strong>
                                    <br><span class="badge" style="background-color: <?= $j['color'] ?>"><?= $j['tipo'] ?></span>
                                </div>
                                <small class="text-muted">Hasta: <?= formatearFecha($j['fecha_fin']) ?></small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
