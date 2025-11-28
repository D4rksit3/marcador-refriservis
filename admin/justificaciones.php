<?php
/**
 * =====================================================
 * MÓDULO DE GESTIÓN DE JUSTIFICACIONES - RRHH
 * Sistema Unificado: Vacaciones, Descansos Médicos, etc.
 * =====================================================
 */

require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';
$tab_activo = $_GET['tab'] ?? 'nueva';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        // ========== CREAR JUSTIFICACIÓN ==========
        if ($action === 'crear_justificacion') {
            $usuario_id = (int) $_POST['usuario_id'];
            $tipo_id = (int) $_POST['tipo_justificacion_id'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = $_POST['fecha_fin'];
            $motivo = sanitize($_POST['motivo']);
            $con_goce = isset($_POST['con_goce']) ? 1 : 0;
            $observaciones = sanitize($_POST['observaciones'] ?? '');
            
            // Campos médicos
            $diagnostico = sanitize($_POST['diagnostico'] ?? '');
            $medico_nombre = sanitize($_POST['medico_nombre'] ?? '');
            $medico_cmp = sanitize($_POST['medico_cmp'] ?? '');
            $centro_medico = sanitize($_POST['centro_medico'] ?? '');
            
            // Validar fechas
            if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
                throw new Exception('La fecha fin no puede ser anterior a la fecha inicio');
            }
            
            // Verificar solapamiento
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total FROM justificaciones 
                WHERE usuario_id = ? AND estado = 'aprobada'
                AND ((fecha_inicio BETWEEN ? AND ?) OR (fecha_fin BETWEEN ? AND ?) OR (? BETWEEN fecha_inicio AND fecha_fin))
            ");
            $stmt->execute([$usuario_id, $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin, $fecha_inicio]);
            if ($stmt->fetch()['total'] > 0) {
                throw new Exception('Ya existe una justificación en ese rango de fechas');
            }
            
            // Obtener info del tipo
            $stmt = $pdo->prepare("SELECT * FROM tipos_justificacion WHERE id = ?");
            $stmt->execute([$tipo_id]);
            $tipo = $stmt->fetch();
            
            // Si es vacaciones, verificar saldo
            if ($tipo['codigo'] === 'VAC') {
                $periodo_id = (int) $_POST['periodo_id'];
                $dias = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / 86400 + 1;
                
                $stmt = $pdo->prepare("SELECT dias_pendientes FROM vacaciones_periodos WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$periodo_id, $usuario_id]);
                $periodo = $stmt->fetch();
                
                if (!$periodo || $dias > $periodo['dias_pendientes']) {
                    throw new Exception('No tiene suficientes días de vacaciones disponibles');
                }
            }
            
            // Insertar justificación
            $stmt = $pdo->prepare("
                INSERT INTO justificaciones 
                (usuario_id, tipo_justificacion_id, fecha_inicio, fecha_fin, motivo, diagnostico, 
                 medico_nombre, medico_cmp, centro_medico, observaciones, con_goce, admin_id, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aprobada')
            ");
            $stmt->execute([
                $usuario_id, $tipo_id, $fecha_inicio, $fecha_fin, $motivo, $diagnostico,
                $medico_nombre, $medico_cmp, $centro_medico, $observaciones, $con_goce, $_SESSION['admin_id']
            ]);
            
            $justificacion_id = $pdo->lastInsertId();
            
            // Si es vacaciones, actualizar saldo
            if ($tipo['codigo'] === 'VAC' && isset($periodo_id)) {
                $dias = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / 86400 + 1;
                $stmt = $pdo->prepare("UPDATE vacaciones_periodos SET dias_tomados = dias_tomados + ? WHERE id = ?");
                $stmt->execute([$dias, $periodo_id]);
            }
            
            // Subir documentos
            if (!empty($_FILES['documentos']['name'][0])) {
                foreach ($_FILES['documentos']['name'] as $key => $name) {
                    if ($_FILES['documentos']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['documentos']['name'][$key],
                            'type' => $_FILES['documentos']['type'][$key],
                            'tmp_name' => $_FILES['documentos']['tmp_name'][$key],
                            'error' => $_FILES['documentos']['error'][$key],
                            'size' => $_FILES['documentos']['size'][$key]
                        ];
                        
                        $resultado = subirArchivo($file, EVIDENCIAS_PATH, 'just_' . $justificacion_id);
                        if ($resultado['success']) {
                            $stmt = $pdo->prepare("
                                INSERT INTO justificacion_documentos 
                                (justificacion_id, nombre_archivo, nombre_original, tipo_archivo, tamano, ruta) 
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $justificacion_id, $resultado['nombre'], $resultado['original'],
                                $resultado['tipo'], $resultado['tamano'], $resultado['ruta']
                            ]);
                        }
                    }
                }
            }
            
            $mensaje = 'Justificación registrada correctamente';
            $tipo_mensaje = 'success';
            $tab_activo = 'listado';
        }
        
        // ========== ELIMINAR JUSTIFICACIÓN ==========
        if ($action === 'eliminar') {
            $id = (int) $_POST['justificacion_id'];
            
            // Obtener info para revertir vacaciones si aplica
            $stmt = $pdo->prepare("
                SELECT j.*, tj.codigo 
                FROM justificaciones j
                INNER JOIN tipos_justificacion tj ON j.tipo_justificacion_id = tj.id
                WHERE j.id = ?
            ");
            $stmt->execute([$id]);
            $just = $stmt->fetch();
            
            // Eliminar documentos físicos
            $stmt = $pdo->prepare("SELECT ruta FROM justificacion_documentos WHERE justificacion_id = ?");
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll() as $doc) {
                @unlink(__DIR__ . '/../' . $doc['ruta']);
            }
            
            // Si era vacaciones, revertir días
            if ($just && $just['codigo'] === 'VAC') {
                $dias = $just['dias_calendario'];
                $stmt = $pdo->prepare("
                    UPDATE vacaciones_periodos 
                    SET dias_tomados = GREATEST(0, dias_tomados - ?) 
                    WHERE usuario_id = ? AND estado = 'vigente'
                ");
                $stmt->execute([$dias, $just['usuario_id']]);
            }
            
            $stmt = $pdo->prepare("DELETE FROM justificaciones WHERE id = ?");
            $stmt->execute([$id]);
            
            $mensaje = 'Justificación eliminada correctamente';
            $tipo_mensaje = 'success';
        }
        
        // ========== CREAR PERÍODO DE VACACIONES ==========
        if ($action === 'crear_periodo') {
            $usuario_id = (int) $_POST['usuario_id'];
            $periodo_inicio = $_POST['periodo_inicio'];
            $periodo_fin = $_POST['periodo_fin'];
            $dias_correspondientes = (int) $_POST['dias_correspondientes'];
            $fecha_vencimiento = $_POST['fecha_vencimiento'];
            
            $stmt = $pdo->prepare("
                INSERT INTO vacaciones_periodos 
                (usuario_id, periodo_inicio, periodo_fin, dias_correspondientes, fecha_vencimiento, estado) 
                VALUES (?, ?, ?, ?, ?, 'vigente')
            ");
            $stmt->execute([$usuario_id, $periodo_inicio, $periodo_fin, $dias_correspondientes, $fecha_vencimiento]);
            
            $mensaje = 'Período de vacaciones creado correctamente';
            $tipo_mensaje = 'success';
            $tab_activo = 'vacaciones';
        }
        
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener datos para formularios
$usuarios = $pdo->query("SELECT id, dni, nombres, apellidos, cargo, departamento FROM usuarios WHERE estado = 'activo' ORDER BY apellidos, nombres")->fetchAll();
$tipos_justificacion = $pdo->query("SELECT * FROM tipos_justificacion WHERE estado = 'activo' ORDER BY nombre")->fetchAll();

// Filtros para listado
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_mes = $_GET['mes'] ?? date('Y-m');
$filtro_estado = $_GET['estado'] ?? '';

// Consulta de justificaciones
$sql = "SELECT * FROM v_justificaciones_resumen WHERE 1=1";
$params = [];

if ($filtro_usuario) {
    $sql .= " AND usuario_id = ?";
    $params[] = $filtro_usuario;
}
if ($filtro_tipo) {
    $sql .= " AND tipo_codigo = ?";
    $params[] = $filtro_tipo;
}
if ($filtro_mes) {
    $sql .= " AND (DATE_FORMAT(fecha_inicio, '%Y-%m') = ? OR DATE_FORMAT(fecha_fin, '%Y-%m') = ?)";
    $params[] = $filtro_mes;
    $params[] = $filtro_mes;
}
if ($filtro_estado) {
    $sql .= " AND estado = ?";
    $params[] = $filtro_estado;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$justificaciones = $stmt->fetchAll();

// Estadísticas del mes
$stats = $pdo->prepare("
    SELECT 
        tj.codigo,
        tj.nombre,
        tj.color,
        COUNT(j.id) as total,
        COALESCE(SUM(j.dias_calendario), 0) as dias
    FROM tipos_justificacion tj
    LEFT JOIN justificaciones j ON tj.id = j.tipo_justificacion_id 
        AND j.estado = 'aprobada'
        AND DATE_FORMAT(j.fecha_inicio, '%Y-%m') = ?
    WHERE tj.estado = 'activo'
    GROUP BY tj.id
    ORDER BY dias DESC
");
$stats->execute([$filtro_mes]);
$estadisticas = $stats->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Título y Stats Rápidas -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <h2><i class="fas fa-file-medical-alt me-2"></i>Gestión de Justificaciones</h2>
            <p class="text-muted">Vacaciones, Descansos Médicos, Permisos y más</p>
        </div>
        <div class="col-lg-6">
            <div class="row text-center">
                <?php 
                $total_just = array_sum(array_column($estadisticas, 'total'));
                $total_dias = array_sum(array_column($estadisticas, 'dias'));
                ?>
                <div class="col-4">
                    <div class="bg-primary text-white rounded p-2">
                        <h4 class="mb-0"><?= $total_just ?></h4>
                        <small>Justificaciones</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="bg-success text-white rounded p-2">
                        <h4 class="mb-0"><?= $total_dias ?></h4>
                        <small>Días Totales</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="bg-info text-white rounded p-2">
                        <h4 class="mb-0"><?= count($usuarios) ?></h4>
                        <small>Empleados</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show">
        <?= $mensaje ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Tabs de Navegación -->
    <ul class="nav nav-tabs mb-4" id="tabsJustificaciones">
        <li class="nav-item">
            <a class="nav-link <?= $tab_activo === 'nueva' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tabNueva">
                <i class="fas fa-plus-circle me-1"></i>Nueva Justificación
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab_activo === 'listado' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tabListado">
                <i class="fas fa-list me-1"></i>Listado
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab_activo === 'vacaciones' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tabVacaciones">
                <i class="fas fa-umbrella-beach me-1"></i>Vacaciones
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab_activo === 'reportes' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tabReportes">
                <i class="fas fa-chart-bar me-1"></i>Reportes
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- ========== TAB NUEVA JUSTIFICACIÓN ========== -->
        <div class="tab-pane fade <?= $tab_activo === 'nueva' ? 'show active' : '' ?>" id="tabNueva">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Registrar Nueva Justificación</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="formJustificacion">
                                <input type="hidden" name="action" value="crear_justificacion">
                                
                                <div class="row">
                                    <!-- Empleado -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Empleado <span class="text-danger">*</span></label>
                                        <select name="usuario_id" class="form-select" required id="selectUsuario">
                                            <option value="">Seleccionar empleado...</option>
                                            <?php foreach ($usuarios as $u): ?>
                                            <option value="<?= $u['id'] ?>" 
                                                    data-cargo="<?= htmlspecialchars($u['cargo']) ?>"
                                                    data-depto="<?= htmlspecialchars($u['departamento']) ?>">
                                                <?= htmlspecialchars($u['dni'] . ' - ' . $u['apellidos'] . ', ' . $u['nombres']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div id="infoEmpleado" class="form-text"></div>
                                    </div>
                                    
                                    <!-- Tipo -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tipo de Justificación <span class="text-danger">*</span></label>
                                        <select name="tipo_justificacion_id" class="form-select" required id="selectTipo">
                                            <option value="">Seleccionar tipo...</option>
                                            <?php foreach ($tipos_justificacion as $t): ?>
                                            <option value="<?= $t['id'] ?>" 
                                                    data-codigo="<?= $t['codigo'] ?>"
                                                    data-color="<?= $t['color'] ?>"
                                                    data-requiere-doc="<?= $t['requiere_evidencia'] ?>">
                                                <?= htmlspecialchars($t['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Panel de Vacaciones (oculto por defecto) -->
                                <div id="panelVacaciones" class="alert alert-success" style="display:none;">
                                    <h6><i class="fas fa-umbrella-beach me-2"></i>Saldo de Vacaciones</h6>
                                    <div id="saldoVacaciones">Seleccione un empleado...</div>
                                    <input type="hidden" name="periodo_id" id="periodoId">
                                </div>
                                
                                <!-- Panel Médico (oculto por defecto) -->
                                <div id="panelMedico" class="card bg-light mb-3" style="display:none;">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fas fa-medkit me-2"></i>Información Médica</h6>
                                        <div class="row">
                                            <div class="col-md-12 mb-2">
                                                <label class="form-label">Diagnóstico</label>
                                                <input type="text" name="diagnostico" class="form-control" placeholder="Ej: Descanso por gripe">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">Médico</label>
                                                <input type="text" name="medico_nombre" class="form-control" placeholder="Nombre del médico">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">CMP</label>
                                                <input type="text" name="medico_cmp" class="form-control" placeholder="N° de colegiatura">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">Centro Médico</label>
                                                <input type="text" name="centro_medico" class="form-control" placeholder="Hospital/Clínica">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <!-- Fechas -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Fecha Inicio <span class="text-danger">*</span></label>
                                        <input type="date" name="fecha_inicio" class="form-control" required id="fechaInicio">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Fecha Fin <span class="text-danger">*</span></label>
                                        <input type="date" name="fecha_fin" class="form-control" required id="fechaFin">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Días</label>
                                        <input type="text" class="form-control bg-light" readonly id="diasCalculados" value="0 días">
                                    </div>
                                </div>
                                
                                <!-- Motivo -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Motivo <span class="text-danger">*</span></label>
                                    <textarea name="motivo" class="form-control" rows="2" required placeholder="Describa el motivo..."></textarea>
                                </div>
                                
                                <!-- Opciones -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="con_goce" id="conGoce" checked>
                                            <label class="form-check-label" for="conGoce">
                                                Con goce de remuneraciones
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Documentos -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-paperclip me-1"></i>Documentos de Sustento
                                        <span class="text-muted small" id="docRequerido">(Opcional)</span>
                                    </label>
                                    <input type="file" name="documentos[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                    <div class="form-text">PDF, imágenes o documentos Word. Máximo 10MB por archivo.</div>
                                </div>
                                
                                <!-- Observaciones -->
                                <div class="mb-3">
                                    <label class="form-label">Observaciones Adicionales</label>
                                    <textarea name="observaciones" class="form-control" rows="2" placeholder="Observaciones opcionales..."></textarea>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>Registrar Justificación
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Panel Lateral - Tipos de Justificación -->
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Tipos de Justificación</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($tipos_justificacion as $t): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <span class="badge me-2" style="background-color: <?= $t['color'] ?>">
                                            <i class="fas <?= $t['icono'] ?>"></i>
                                        </span>
                                        <div>
                                            <strong><?= htmlspecialchars($t['nombre']) ?></strong>
                                            <?php if ($t['dias_maximos']): ?>
                                            <br><small class="text-muted">Máx: <?= $t['dias_maximos'] ?> días</small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($t['requiere_evidencia']): ?>
                                        <span class="badge bg-warning text-dark ms-auto">Doc. Requerido</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== TAB LISTADO ========== -->
        <div class="tab-pane fade <?= $tab_activo === 'listado' ? 'show active' : '' ?>" id="tabListado">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <!-- Filtros -->
                    <form method="GET" class="row g-2">
                        <input type="hidden" name="tab" value="listado">
                        <div class="col-md-3">
                            <select name="usuario" class="form-select form-select-sm">
                                <option value="">Todos los empleados</option>
                                <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $filtro_usuario == $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['apellidos'] . ', ' . $u['nombres']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="tipo" class="form-select form-select-sm">
                                <option value="">Todos los tipos</option>
                                <?php foreach ($tipos_justificacion as $t): ?>
                                <option value="<?= $t['codigo'] ?>" <?= $filtro_tipo == $t['codigo'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="month" name="mes" class="form-control form-control-sm" value="<?= $filtro_mes ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="estado" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="aprobada" <?= $filtro_estado == 'aprobada' ? 'selected' : '' ?>>Aprobadas</option>
                                <option value="pendiente" <?= $filtro_estado == 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                                <option value="rechazada" <?= $filtro_estado == 'rechazada' ? 'selected' : '' ?>>Rechazadas</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filtrar
                            </button>
                        </div>
                        <div class="col-md-1">
                            <a href="?tab=listado" class="btn btn-sm btn-secondary w-100">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Empleado</th>
                                    <th>Tipo</th>
                                    <th>Período</th>
                                    <th class="text-center">Días</th>
                                    <th>Motivo</th>
                                    <th class="text-center">Docs</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($justificaciones)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No hay justificaciones</td></tr>
                                <?php else: ?>
                                <?php foreach ($justificaciones as $j): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($j['nombre_empleado']) ?></strong>
                                        <br><small class="text-muted"><?= $j['dni'] ?> | <?= $j['departamento'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: <?= $j['color'] ?>">
                                            <i class="fas <?= $j['icono'] ?> me-1"></i><?= $j['tipo_justificacion'] ?>
                                        </span>
                                        <?php if (!$j['con_goce']): ?>
                                        <br><small class="text-danger">Sin goce</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= formatearFecha($j['fecha_inicio']) ?>
                                        <br><small class="text-muted">al <?= formatearFecha($j['fecha_fin']) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary fs-6"><?= $j['dias_calendario'] ?></span>
                                    </td>
                                    <td style="max-width: 200px;">
                                        <span class="text-truncate d-inline-block" style="max-width: 180px;" title="<?= htmlspecialchars($j['motivo']) ?>">
                                            <?= htmlspecialchars($j['motivo']) ?>
                                        </span>
                                        <?php if ($j['diagnostico']): ?>
                                        <br><small class="text-info"><i class="fas fa-medkit me-1"></i><?= htmlspecialchars($j['diagnostico']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($j['num_documentos'] > 0): ?>
                                        <button class="btn btn-sm btn-outline-info" onclick="verDocumentos(<?= $j['id'] ?>)">
                                            <i class="fas fa-paperclip"></i> <?= $j['num_documentos'] ?>
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="verDetalle(<?= $j['id'] ?>)" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="eliminarJustificacion(<?= $j['id'] ?>)" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <small class="text-muted">Total: <?= count($justificaciones) ?> registros</small>
                    <div class="float-end">
                        <button class="btn btn-sm btn-success" onclick="exportarExcel()">
                            <i class="fas fa-file-excel me-1"></i>Excel
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="exportarPDF()">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== TAB VACACIONES ========== -->
        <div class="tab-pane fade <?= $tab_activo === 'vacaciones' ? 'show active' : '' ?>" id="tabVacaciones">
            <div class="row">
                <!-- Crear Período -->
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Crear Período de Vacaciones</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="crear_periodo">
                                
                                <div class="mb-3">
                                    <label class="form-label">Empleado</label>
                                    <select name="usuario_id" class="form-select" required>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach ($usuarios as $u): ?>
                                        <option value="<?= $u['id'] ?>">
                                            <?= htmlspecialchars($u['apellidos'] . ', ' . $u['nombres']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Período Desde</label>
                                        <input type="date" name="periodo_inicio" class="form-control" required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Período Hasta</label>
                                        <input type="date" name="periodo_fin" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Días Correspondientes</label>
                                    <input type="number" name="dias_correspondientes" class="form-control" value="30" min="1" max="60" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Fecha Vencimiento</label>
                                    <input type="date" name="fecha_vencimiento" class="form-control" required>
                                    <div class="form-text">Fecha límite para usar las vacaciones</div>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-plus me-2"></i>Crear Período
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Saldo de Vacaciones -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-umbrella-beach me-2"></i>Control de Vacaciones por Empleado</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Empleado</th>
                                            <th>Período</th>
                                            <th class="text-center">Corresponde</th>
                                            <th class="text-center">Tomados</th>
                                            <th class="text-center">Pendientes</th>
                                            <th>Vencimiento</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $saldos = $pdo->query("SELECT * FROM v_vacaciones_saldo ORDER BY nombre_empleado")->fetchAll();
                                        foreach ($saldos as $s):
                                            $color_venc = $s['dias_para_vencer'] < 30 ? 'danger' : ($s['dias_para_vencer'] < 90 ? 'warning' : 'success');
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($s['nombre_empleado']) ?></strong>
                                                <br><small class="text-muted"><?= $s['dni'] ?></small>
                                            </td>
                                            <td>
                                                <?php if ($s['periodo_id']): ?>
                                                <?= formatearFecha($s['periodo_inicio']) ?> - <?= formatearFecha($s['periodo_fin']) ?>
                                                <?php else: ?>
                                                <span class="text-warning">Sin período</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?= $s['dias_correspondientes'] ?? '-' ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?= $s['dias_tomados'] ?? 0 ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= ($s['dias_pendientes'] ?? 0) > 15 ? 'success' : 'warning' ?> fs-6">
                                                    <?= $s['dias_pendientes'] ?? 0 ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($s['fecha_vencimiento']): ?>
                                                <span class="text-<?= $color_venc ?>">
                                                    <?= formatearFecha($s['fecha_vencimiento']) ?>
                                                    <br><small><?= $s['dias_para_vencer'] ?> días</small>
                                                </span>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="verHistorialVacaciones(<?= $s['usuario_id'] ?>)">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                            </td>
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

        <!-- ========== TAB REPORTES ========== -->
        <div class="tab-pane fade <?= $tab_activo === 'reportes' ? 'show active' : '' ?>" id="tabReportes">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Resumen <?= getNombreMes((int)date('m', strtotime($filtro_mes . '-01'))) ?></h6>
                        </div>
                        <div class="card-body">
                            <canvas id="chartTipos" height="200"></canvas>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($estadisticas as $e): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>
                                    <span class="badge" style="background-color: <?= $e['color'] ?>"><?= $e['total'] ?></span>
                                    <?= $e['nombre'] ?>
                                </span>
                                <strong><?= $e['dias'] ?> días</strong>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Alertas y Pendientes</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            // Vacaciones por vencer
                            $por_vencer = $pdo->query("
                                SELECT * FROM v_vacaciones_saldo 
                                WHERE dias_para_vencer <= 60 AND dias_para_vencer > 0 AND dias_pendientes > 0
                                ORDER BY dias_para_vencer
                            ")->fetchAll();
                            ?>
                            
                            <?php if (!empty($por_vencer)): ?>
                            <h6 class="text-danger"><i class="fas fa-clock me-2"></i>Vacaciones por Vencer</h6>
                            <ul class="list-group mb-3">
                                <?php foreach ($por_vencer as $v): ?>
                                <li class="list-group-item list-group-item-warning d-flex justify-content-between">
                                    <span><?= htmlspecialchars($v['nombre_empleado']) ?></span>
                                    <span>
                                        <strong><?= $v['dias_pendientes'] ?> días</strong> vencen en 
                                        <span class="badge bg-danger"><?= $v['dias_para_vencer'] ?> días</span>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <p class="text-success"><i class="fas fa-check me-2"></i>No hay vacaciones próximas a vencer</p>
                            <?php endif; ?>
                            
                            <?php
                            // Descansos médicos activos
                            $dm_activos = $pdo->query("
                                SELECT j.*, CONCAT(u.nombres, ' ', u.apellidos) as empleado
                                FROM justificaciones j
                                INNER JOIN usuarios u ON j.usuario_id = u.id
                                INNER JOIN tipos_justificacion tj ON j.tipo_justificacion_id = tj.id
                                WHERE tj.codigo = 'DM' AND j.estado = 'aprobada'
                                AND CURDATE() BETWEEN j.fecha_inicio AND j.fecha_fin
                            ")->fetchAll();
                            ?>
                            
                            <?php if (!empty($dm_activos)): ?>
                            <h6 class="text-danger mt-3"><i class="fas fa-medkit me-2"></i>Descansos Médicos Activos</h6>
                            <ul class="list-group">
                                <?php foreach ($dm_activos as $dm): ?>
                                <li class="list-group-item list-group-item-danger d-flex justify-content-between">
                                    <span><?= htmlspecialchars($dm['empleado']) ?></span>
                                    <span>Hasta: <?= formatearFecha($dm['fecha_fin']) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Justificación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoDetalle"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDocumentos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Documentos Adjuntos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoDocumentos"></div>
        </div>
    </div>
</div>

<form id="formEliminar" method="POST" style="display:none;">
    <input type="hidden" name="action" value="eliminar">
    <input type="hidden" name="justificacion_id" id="eliminarId">
</form>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de tipos
new Chart(document.getElementById('chartTipos'), {
    type: 'doughnut',
    data: {
        labels: [<?= implode(',', array_map(fn($e) => "'" . $e['nombre'] . "'", $estadisticas)) ?>],
        datasets: [{
            data: [<?= implode(',', array_column($estadisticas, 'dias')) ?>],
            backgroundColor: [<?= implode(',', array_map(fn($e) => "'" . $e['color'] . "'", $estadisticas)) ?>]
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

// Mostrar info del empleado
document.getElementById('selectUsuario').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('infoEmpleado').innerHTML = 
            '<i class="fas fa-briefcase me-1"></i>' + opt.dataset.cargo + ' | ' + opt.dataset.depto;
        cargarSaldoVacaciones(opt.value);
    }
});

// Mostrar paneles según tipo
document.getElementById('selectTipo').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const codigo = opt.dataset.codigo;
    
    document.getElementById('panelVacaciones').style.display = codigo === 'VAC' ? 'block' : 'none';
    document.getElementById('panelMedico').style.display = codigo === 'DM' ? 'block' : 'none';
    
    const reqDoc = opt.dataset.requiereDoc === '1';
    document.getElementById('docRequerido').innerHTML = reqDoc ? '<span class="text-danger">* Requerido</span>' : '(Opcional)';
});

// Calcular días
document.getElementById('fechaInicio').addEventListener('change', calcularDias);
document.getElementById('fechaFin').addEventListener('change', calcularDias);

function calcularDias() {
    const inicio = document.getElementById('fechaInicio').value;
    const fin = document.getElementById('fechaFin').value;
    if (inicio && fin) {
        const dias = Math.ceil((new Date(fin) - new Date(inicio)) / (1000 * 60 * 60 * 24)) + 1;
        document.getElementById('diasCalculados').value = dias + ' días';
    }
}

function cargarSaldoVacaciones(usuarioId) {
    fetch('ajax/get_saldo_vacaciones.php?usuario_id=' + usuarioId)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.saldo) {
                document.getElementById('saldoVacaciones').innerHTML = `
                    <strong>Días disponibles: ${data.saldo.dias_pendientes}</strong>
                    <br><small>Período: ${data.saldo.periodo_inicio} - ${data.saldo.periodo_fin}</small>
                    <br><small>Vence: ${data.saldo.fecha_vencimiento}</small>
                `;
                document.getElementById('periodoId').value = data.saldo.periodo_id;
            } else {
                document.getElementById('saldoVacaciones').innerHTML = '<span class="text-warning">Sin período de vacaciones registrado</span>';
            }
        });
}

function verDetalle(id) {
    fetch('ajax/get_justificacion.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('contenidoDetalle').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('modalDetalle')).show();
            }
        });
}

function verDocumentos(id) {
    fetch('ajax/get_documentos.php?justificacion_id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('contenidoDocumentos').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('modalDocumentos')).show();
            }
        });
}

function eliminarJustificacion(id) {
    if (confirm('¿Eliminar esta justificación?\nSi es vacaciones, se restaurarán los días al saldo.')) {
        document.getElementById('eliminarId').value = id;
        document.getElementById('formEliminar').submit();
    }
}

function exportarExcel() {
    window.location.href = 'ajax/exportar_justificaciones.php?formato=excel&' + new URLSearchParams(window.location.search);
}

function exportarPDF() {
    window.location.href = 'ajax/exportar_justificaciones.php?formato=pdf&' + new URLSearchParams(window.location.search);
}
</script>

<?php include 'includes/footer.php'; ?>
