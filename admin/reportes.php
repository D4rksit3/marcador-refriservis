<?php
require_once '../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_mes = $_GET['mes'] ?? date('Y-m');

$usuarios = $pdo->query("SELECT id, dni, nombres, apellidos, cargo, departamento, dia_descanso FROM usuarios WHERE estado = 'activo' ORDER BY apellidos")->fetchAll();

include 'includes/header.php';
?>
<div class="container-fluid py-4">
    <h2><i class="fas fa-clipboard-list me-2"></i>Reportes de Asistencia</h2>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <select name="usuario" class="form-select">
                        <option value="">Todos los empleados</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filtro_usuario == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['apellidos'] . ', ' . $u['nombres']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><input type="month" name="mes" class="form-control" value="<?= $filtro_mes ?>"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button></div>
            </form>
        </div>
    </div>
    
    <?php if ($filtro_usuario): 
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$filtro_usuario]); $empleado = $stmt->fetch();
        
        // Generar calendario del mes
        $inicio = $filtro_mes . '-01';
        $fin = date('Y-m-t', strtotime($inicio));
        $calendario = [];
        $fecha = new DateTime($inicio);
        $finDt = new DateTime($fin);
        
        while ($fecha <= $finDt) {
            $f = $fecha->format('Y-m-d');
            $estado = getEstadoDia($filtro_usuario, $f);
            
            $stmt = $pdo->prepare("SELECT MIN(hora) as entrada, MAX(hora) as salida FROM marcaciones WHERE usuario_id = ? AND fecha = ?");
            $stmt->execute([$filtro_usuario, $f]);
            $marc = $stmt->fetch();
            
            $calendario[] = [
                'fecha' => $f,
                'dia_nombre' => getNombreDia((int)$fecha->format('w')),
                'estado' => $estado,
                'entrada' => $marc['entrada'],
                'salida' => $marc['salida']
            ];
            $fecha->modify('+1 day');
        }
        
        $totales = ['ASISTIÓ' => 0, 'FALTA' => 0, 'DESCANSO' => 0, 'JUSTIFICADO' => 0];
        foreach ($calendario as $c) {
            if (isset($totales[$c['estado']['estado']])) $totales[$c['estado']['estado']]++;
            elseif (strpos($c['estado']['estado'], 'VACACIONES') !== false || strpos($c['estado']['estado'], 'DESCANSO') !== false) $totales['JUSTIFICADO']++;
        }
    ?>
    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <h5><?= htmlspecialchars($empleado['nombres'] . ' ' . $empleado['apellidos']) ?></h5>
                    <p class="text-muted"><?= $empleado['dni'] ?> | <?= $empleado['cargo'] ?></p>
                    <span class="badge bg-info">Descanso: <?= getNombreDia($empleado['dia_descanso']) ?></span>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body"><canvas id="chartEstados" height="200"></canvas></div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span class="text-success">Asistió</span><strong><?= $totales['ASISTIÓ'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-danger">Faltas</span><strong><?= $totales['FALTA'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-info">Descansos</span><strong><?= $totales['DESCANSO'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-warning">Justificados</span><strong><?= $totales['JUSTIFICADO'] ?></strong></li>
                </ul>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><h6 class="mb-0">Calendario de Asistencia - <?= getNombreMes((int)date('m', strtotime($filtro_mes . '-01'))) ?></h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Fecha</th><th>Día</th><th>Estado</th><th>Entrada</th><th>Salida</th></tr></thead>
                        <tbody>
                            <?php foreach ($calendario as $c): ?>
                            <tr>
                                <td><?= formatearFecha($c['fecha']) ?></td>
                                <td><?= $c['dia_nombre'] ?></td>
                                <td><span class="badge" style="background-color: <?= $c['estado']['color'] ?>"><i class="fas <?= $c['estado']['icono'] ?> me-1"></i><?= $c['estado']['estado'] ?></span></td>
                                <td><?= $c['entrada'] ? date('H:i', strtotime($c['entrada'])) : '-' ?></td>
                                <td><?= $c['salida'] ? date('H:i', strtotime($c['salida'])) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>new Chart(document.getElementById('chartEstados'),{type:'doughnut',data:{labels:['Asistió','Faltas','Descansos','Justificados'],datasets:[{data:[<?=$totales['ASISTIÓ']?>,<?=$totales['FALTA']?>,<?=$totales['DESCANSO']?>,<?=$totales['JUSTIFICADO']?>],backgroundColor:['#28a745','#dc3545','#17a2b8','#ffc107']}]},options:{plugins:{legend:{position:'bottom'}}}});</script>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white"><h6 class="mb-0">Resumen General - <?= getNombreMes((int)date('m', strtotime($filtro_mes . '-01'))) ?></h6></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Empleado</th><th>DNI</th><th class="text-center">Asistencias</th><th class="text-center">Faltas</th><th class="text-center">Justificados</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($usuarios as $u): 
                        $inicio = $filtro_mes . '-01'; $fin = date('Y-m-t', strtotime($inicio));
                        $asist = $pdo->prepare("SELECT COUNT(DISTINCT fecha) FROM marcaciones WHERE usuario_id = ? AND fecha BETWEEN ? AND ?");
                        $asist->execute([$u['id'], $inicio, $fin]); $dias_asist = $asist->fetchColumn();
                        $just = $pdo->prepare("SELECT COALESCE(SUM(DATEDIFF(LEAST(fecha_fin, ?), GREATEST(fecha_inicio, ?)) + 1), 0) FROM justificaciones WHERE usuario_id = ? AND estado = 'aprobada' AND fecha_inicio <= ? AND fecha_fin >= ?");
                        $just->execute([$fin, $inicio, $u['id'], $fin, $inicio]); $dias_just = $just->fetchColumn();
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['apellidos'] . ', ' . $u['nombres']) ?></strong></td>
                        <td><?= $u['dni'] ?></td>
                        <td class="text-center"><span class="badge bg-success"><?= $dias_asist ?></span></td>
                        <td class="text-center"><span class="badge bg-danger"><?= max(0, date('d') - $dias_asist - $dias_just - 4) ?></span></td>
                        <td class="text-center"><span class="badge bg-warning"><?= $dias_just ?></span></td>
                        <td><a href="?usuario=<?= $u['id'] ?>&mes=<?= $filtro_mes ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
