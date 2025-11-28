<?php
/**
 * =====================================================
 * REPORTE COMPLETO DE JUSTIFICACIONES - RRHH
 * Panel unificado con todas las métricas y análisis
 * =====================================================
 */

require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Filtros
$anio = $_GET['anio'] ?? date('Y');
$mes = $_GET['mes'] ?? '';
$departamento = $_GET['departamento'] ?? '';
$tipo = $_GET['tipo'] ?? '';

// Obtener datos para filtros
$departamentos = $pdo->query("SELECT DISTINCT departamento FROM usuarios WHERE departamento IS NOT NULL ORDER BY departamento")->fetchAll();
$tipos_justificacion = $pdo->query("SELECT * FROM tipos_justificacion WHERE estado = 1 ORDER BY orden")->fetchAll();
$anios_disponibles = $pdo->query("SELECT DISTINCT YEAR(fecha_inicio) as anio FROM justificaciones ORDER BY anio DESC")->fetchAll();

// Construir filtros SQL
$where_sql = "WHERE YEAR(j.fecha_inicio) = ?";
$params = [$anio];

if ($mes) {
    $where_sql .= " AND MONTH(j.fecha_inicio) = ?";
    $params[] = $mes;
}
if ($departamento) {
    $where_sql .= " AND u.departamento = ?";
    $params[] = $departamento;
}
if ($tipo) {
    $where_sql .= " AND tj.id = ?";
    $params[] = $tipo;
}

// =====================================================
// ESTADÍSTICAS GENERALES
// =====================================================

// Total por tipo de justificación
$stmt = $pdo->prepare("
    SELECT 
        tj.nombre,
        tj.codigo,
        tj.color,
        tj.icono,
        COUNT(j.id) as cantidad,
        COALESCE(SUM(j.dias_calendario), 0) as dias_totales,
        COUNT(DISTINCT j.usuario_id) as empleados_afectados
    FROM tipos_justificacion tj
    LEFT JOIN justificaciones j ON tj.id = j.tipo_justificacion_id 
        AND j.estado = 'aprobada'
        AND YEAR(j.fecha_inicio) = ?
        " . ($mes ? "AND MONTH(j.fecha_inicio) = ?" : "") . "
    GROUP BY tj.id
    ORDER BY dias_totales DESC
");
$params_tipo = [$anio];
if ($mes) $params_tipo[] = $mes;
$stmt->execute($params_tipo);
$stats_por_tipo = $stmt->fetchAll();

// Tendencia mensual del año
$stmt = $pdo->prepare("
    SELECT 
        MONTH(j.fecha_inicio) as mes,
        COUNT(j.id) as cantidad,
        SUM(j.dias_calendario) as dias
    FROM justificaciones j
    WHERE YEAR(j.fecha_inicio) = ? AND j.estado = 'aprobada'
    GROUP BY MONTH(j.fecha_inicio)
    ORDER BY mes
");
$stmt->execute([$anio]);
$tendencia_mensual = $stmt->fetchAll();

// Top empleados con más justificaciones
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.dni,
        CONCAT(u.apellidos, ', ', u.nombres) as nombre,
        u.departamento,
        COUNT(j.id) as total_justificaciones,
        SUM(j.dias_calendario) as total_dias
    FROM usuarios u
    INNER JOIN justificaciones j ON u.id = j.usuario_id
    WHERE j.estado = 'aprobada' AND YEAR(j.fecha_inicio) = ?
    GROUP BY u.id
    ORDER BY total_dias DESC
    LIMIT 10
");
$stmt->execute([$anio]);
$top_empleados = $stmt->fetchAll();

// Resumen por departamento
$stmt = $pdo->prepare("
    SELECT 
        u.departamento,
        COUNT(j.id) as total_justificaciones,
        SUM(j.dias_calendario) as total_dias,
        COUNT(DISTINCT j.usuario_id) as empleados
    FROM justificaciones j
    INNER JOIN usuarios u ON j.usuario_id = u.id
    WHERE j.estado = 'aprobada' AND YEAR(j.fecha_inicio) = ?
    GROUP BY u.departamento
    ORDER BY total_dias DESC
");
$stmt->execute([$anio]);
$stats_departamento = $stmt->fetchAll();

// Saldos de vacaciones
$saldos_vacaciones = $pdo->query("
    SELECT 
        u.id,
        u.dni,
        CONCAT(u.apellidos, ', ', u.nombres) as nombre,
        u.departamento,
        u.fecha_ingreso,
        COALESCE(vs.dias_correspondientes, 30) as dias_correspondientes,
        COALESCE(vs.dias_usados, 0) as dias_usados,
        COALESCE(vs.dias_pendientes, 30) as dias_pendientes
    FROM usuarios u
    LEFT JOIN vacaciones_saldo vs ON u.id = vs.usuario_id AND vs.periodo = YEAR(CURDATE())
    WHERE u.estado = 'activo'
    ORDER BY dias_pendientes DESC
")->fetchAll();

// Listado detallado
$stmt = $pdo->prepare("
    SELECT 
        j.*,
        u.dni,
        CONCAT(u.apellidos, ', ', u.nombres) as nombre_empleado,
        u.departamento,
        u.cargo,
        tj.nombre as tipo_nombre,
        tj.codigo as tipo_codigo,
        tj.color,
        tj.icono
    FROM justificaciones j
    INNER JOIN usuarios u ON j.usuario_id = u.id
    INNER JOIN tipos_justificacion tj ON j.tipo_justificacion_id = tj.id
    $where_sql
    AND j.estado = 'aprobada'
    ORDER BY j.fecha_inicio DESC
");
$stmt->execute($params);
$listado = $stmt->fetchAll();

// Nombres de meses
$meses_nombres = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

include 'includes/header.php';
?>

<style>
    .stat-card { border-radius: 15px; border: none; transition: all 0.3s; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
    .tipo-badge { padding: 8px 15px; border-radius: 20px; font-weight: 500; }
    .chart-container { position: relative; height: 300px; }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.02); }
    .progress-vacaciones { height: 8px; border-radius: 10px; }
    .card-header-tabs { border-bottom: none; }
    .nav-pills .nav-link.active { background-color: #4a5568; }
    .filter-card { background: linear-gradient(135deg, #f8f9fa, #e9ecef); border: none; }
</style>

<div class="container-fluid py-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="mb-1"><i class="fas fa-chart-pie me-2"></i>Reporte de Justificaciones - RRHH</h2>
                    <p class="text-muted mb-0">Análisis completo de vacaciones, licencias y permisos</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-success" onclick="exportarExcel()">
                        <i class="fas fa-file-excel me-2"></i>Excel
                    </button>
                    <button class="btn btn-danger" onclick="exportarPDF()">
                        <i class="fas fa-file-pdf me-2"></i>PDF
                    </button>
                    <a href="justificaciones.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nueva
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card filter-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Año</label>
                    <select name="anio" class="form-select">
                        <?php for($a = date('Y'); $a >= date('Y')-5; $a--): ?>
                        <option value="<?= $a ?>" <?= $anio == $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Mes</label>
                    <select name="mes" class="form-select">
                        <option value="">Todos</option>
                        <?php for($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $mes == $m ? 'selected' : '' ?>><?= $meses_nombres[$m] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Departamento</label>
                    <select name="departamento" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($departamentos as $d): ?>
                        <option value="<?= htmlspecialchars($d['departamento']) ?>" <?= $departamento == $d['departamento'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['departamento']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($tipos_justificacion as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $tipo == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100"><i class="fas fa-filter me-2"></i>Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen por Tipo -->
    <div class="row mb-4">
        <?php foreach (array_slice($stats_por_tipo, 0, 6) as $s): ?>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card stat-card h-100" style="border-left: 4px solid <?= $s['color'] ?>">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto mb-2" style="background: <?= $s['color'] ?>20">
                        <i class="fas <?= $s['icono'] ?> fa-lg" style="color: <?= $s['color'] ?>"></i>
                    </div>
                    <h3 class="mb-0"><?= $s['dias_totales'] ?></h3>
                    <small class="text-muted d-block"><?= $s['nombre'] ?></small>
                    <span class="badge bg-secondary mt-1"><?= $s['cantidad'] ?> registros</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Gráficos y Análisis -->
    <div class="row mb-4">
        <!-- Tendencia Mensual -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Tendencia Mensual <?= $anio ?></h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartTendencia"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribución por Tipo -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribución por Tipo</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartTipos"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas de Información -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <ul class="nav nav-pills" id="tabsReporte" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabListado">
                        <i class="fas fa-list me-1"></i>Listado Detallado
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabVacaciones">
                        <i class="fas fa-umbrella-beach me-1"></i>Saldo Vacaciones
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabDepartamentos">
                        <i class="fas fa-building me-1"></i>Por Departamento
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabEmpleados">
                        <i class="fas fa-users me-1"></i>Top Empleados
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Tab Listado -->
                <div class="tab-pane fade show active" id="tabListado">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaListado">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Empleado</th>
                                    <th>Departamento</th>
                                    <th>Tipo</th>
                                    <th>Período</th>
                                    <th class="text-center">Días</th>
                                    <th>Goce</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($listado)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No hay registros para los filtros seleccionados</td></tr>
                                <?php else: ?>
                                <?php foreach ($listado as $l): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($l['codigo']) ?></code></td>
                                    <td>
                                        <strong><?= htmlspecialchars($l['nombre_empleado']) ?></strong>
                                        <br><small class="text-muted"><?= $l['dni'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($l['departamento'] ?? '-') ?></td>
                                    <td>
                                        <span class="tipo-badge" style="background: <?= $l['color'] ?>20; color: <?= $l['color'] ?>">
                                            <i class="fas <?= $l['icono'] ?> me-1"></i><?= htmlspecialchars($l['tipo_nombre']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($l['fecha_inicio'])) ?>
                                        <br><small class="text-muted">al <?= date('d/m/Y', strtotime($l['fecha_fin'])) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-dark"><?= $l['dias_calendario'] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($l['con_goce']): ?>
                                        <span class="text-success"><i class="fas fa-check"></i> Sí</span>
                                        <?php else: ?>
                                        <span class="text-danger"><i class="fas fa-times"></i> No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= htmlspecialchars(substr($l['motivo'], 0, 50)) ?>...</small></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small">Total: <?= count($listado) ?> registros</div>
                </div>

                <!-- Tab Vacaciones -->
                <div class="tab-pane fade" id="tabVacaciones">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>DNI</th>
                                    <th>Empleado</th>
                                    <th>Departamento</th>
                                    <th>Fecha Ingreso</th>
                                    <th class="text-center">Corresponden</th>
                                    <th class="text-center">Usados</th>
                                    <th class="text-center">Pendientes</th>
                                    <th style="width: 200px;">Progreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($saldos_vacaciones as $sv): 
                                    $porcentaje = $sv['dias_correspondientes'] > 0 
                                        ? round(($sv['dias_usados'] / $sv['dias_correspondientes']) * 100) 
                                        : 0;
                                    $color_barra = $porcentaje > 80 ? 'danger' : ($porcentaje > 50 ? 'warning' : 'success');
                                ?>
                                <tr>
                                    <td><?= $sv['dni'] ?></td>
                                    <td><strong><?= htmlspecialchars($sv['nombre']) ?></strong></td>
                                    <td><?= htmlspecialchars($sv['departamento'] ?? '-') ?></td>
                                    <td><?= $sv['fecha_ingreso'] ? date('d/m/Y', strtotime($sv['fecha_ingreso'])) : '-' ?></td>
                                    <td class="text-center"><span class="badge bg-primary"><?= $sv['dias_correspondientes'] ?></span></td>
                                    <td class="text-center"><span class="badge bg-secondary"><?= $sv['dias_usados'] ?></span></td>
                                    <td class="text-center"><span class="badge bg-<?= $color_barra ?>"><?= $sv['dias_pendientes'] ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress progress-vacaciones flex-grow-1 me-2">
                                                <div class="progress-bar bg-<?= $color_barra ?>" style="width: <?= $porcentaje ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?= $porcentaje ?>%</small>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Departamentos -->
                <div class="tab-pane fade" id="tabDepartamentos">
                    <div class="row">
                        <?php foreach ($stats_departamento as $sd): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card border">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($sd['departamento'] ?? 'Sin Departamento') ?></h5>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <h4 class="text-primary mb-0"><?= $sd['total_justificaciones'] ?></h4>
                                            <small class="text-muted">Registros</small>
                                        </div>
                                        <div class="col-4">
                                            <h4 class="text-success mb-0"><?= $sd['total_dias'] ?></h4>
                                            <small class="text-muted">Días</small>
                                        </div>
                                        <div class="col-4">
                                            <h4 class="text-info mb-0"><?= $sd['empleados'] ?></h4>
                                            <small class="text-muted">Empleados</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab Top Empleados -->
                <div class="tab-pane fade" id="tabEmpleados">
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>DNI</th>
                                    <th>Empleado</th>
                                    <th>Departamento</th>
                                    <th class="text-center">Justificaciones</th>
                                    <th class="text-center">Total Días</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_empleados as $i => $te): ?>
                                <tr>
                                    <td><span class="badge bg-<?= $i < 3 ? 'warning' : 'secondary' ?>"><?= $i + 1 ?></span></td>
                                    <td><?= $te['dni'] ?></td>
                                    <td><strong><?= htmlspecialchars($te['nombre']) ?></strong></td>
                                    <td><?= htmlspecialchars($te['departamento'] ?? '-') ?></td>
                                    <td class="text-center"><?= $te['total_justificaciones'] ?></td>
                                    <td class="text-center"><span class="badge bg-dark"><?= $te['total_dias'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Datos para gráficos
const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
const tendenciaData = <?= json_encode(array_column($tendencia_mensual, 'dias')) ?>;
const tendenciaMeses = <?= json_encode(array_column($tendencia_mensual, 'mes')) ?>;

// Preparar datos mensuales
const dataMensual = new Array(12).fill(0);
tendenciaMeses.forEach((mes, i) => {
    dataMensual[mes - 1] = parseInt(tendenciaData[i]) || 0;
});

// Gráfico de tendencia mensual
new Chart(document.getElementById('chartTendencia'), {
    type: 'bar',
    data: {
        labels: meses,
        datasets: [{
            label: 'Días de Justificación',
            data: dataMensual,
            backgroundColor: '#4a556833',
            borderColor: '#4a5568',
            borderWidth: 2,
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// Gráfico de distribución por tipo
const tiposData = <?= json_encode(array_filter($stats_por_tipo, fn($s) => $s['dias_totales'] > 0)) ?>;
new Chart(document.getElementById('chartTipos'), {
    type: 'doughnut',
    data: {
        labels: tiposData.map(t => t.nombre),
        datasets: [{
            data: tiposData.map(t => t.dias_totales),
            backgroundColor: tiposData.map(t => t.color),
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } }
        }
    }
});

function exportarExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = 'ajax/exportar_justificaciones.php?' + params.toString();
}

function exportarPDF() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'pdf');
    window.location.href = 'ajax/exportar_justificaciones.php?' + params.toString();
}
</script>

<?php include 'includes/footer.php'; ?>
