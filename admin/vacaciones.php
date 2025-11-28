<?php
/**
 * =====================================================
 * GESTIÓN DE VACACIONES - RRHH
 * Control de saldos, períodos y programación
 * =====================================================
 */

require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ajustar saldo de vacaciones
    if (isset($_POST['action']) && $_POST['action'] === 'ajustar_saldo') {
        try {
            $usuario_id = (int) $_POST['usuario_id'];
            $periodo = $_POST['periodo'];
            $dias_correspondientes = (int) $_POST['dias_correspondientes'];
            $dias_adicionales = (int) $_POST['dias_adicionales'];
            $observaciones = sanitize($_POST['observaciones'] ?? '');
            
            $stmt = $pdo->prepare("
                INSERT INTO vacaciones_saldo 
                (usuario_id, periodo, fecha_inicio_periodo, fecha_fin_periodo, dias_correspondientes, dias_adicionales, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    dias_correspondientes = VALUES(dias_correspondientes),
                    dias_adicionales = VALUES(dias_adicionales),
                    observaciones = VALUES(observaciones)
            ");
            $stmt->execute([
                $usuario_id,
                $periodo,
                $periodo . '-01-01',
                $periodo . '-12-31',
                $dias_correspondientes,
                $dias_adicionales,
                $observaciones
            ]);
            
            $mensaje = 'Saldo de vacaciones actualizado correctamente';
            $tipo_mensaje = 'success';
            
        } catch (Exception $e) {
            $mensaje = 'Error: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
    
    // Programar vacaciones rápidas
    if (isset($_POST['action']) && $_POST['action'] === 'programar_vacaciones') {
        try {
            $pdo->beginTransaction();
            
            $usuario_id = (int) $_POST['usuario_id'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = $_POST['fecha_fin'];
            $motivo = sanitize($_POST['motivo']);
            $admin_id = $_SESSION['admin_id'];
            
            // Calcular días
            $dias = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / 86400 + 1;
            
            // Verificar saldo
            $saldo = getSaldoVacaciones($usuario_id, date('Y', strtotime($fecha_inicio)));
            if ($dias > $saldo['dias_pendientes']) {
                throw new Exception("Saldo insuficiente. Disponible: {$saldo['dias_pendientes']} días");
            }
            
            // Obtener tipo de justificación de vacaciones
            $stmt = $pdo->prepare("SELECT id, codigo FROM tipos_justificacion WHERE codigo = 'VAC'");
            $stmt->execute();
            $tipo_vac = $stmt->fetch();
            
            // Generar código
            $codigo = generarCodigoJustificacion($tipo_vac['codigo']);
            
            // Obtener día de descanso del usuario
            $stmt = $pdo->prepare("SELECT dia_descanso FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $usuario = $stmt->fetch();
            $dias_habiles = calcularDiasHabiles($fecha_inicio, $fecha_fin, $usuario['dia_descanso'] ?? 0);
            
            // Insertar justificación
            $stmt = $pdo->prepare("
                INSERT INTO justificaciones 
                (codigo, usuario_id, tipo_justificacion_id, fecha_inicio, fecha_fin, dias_habiles,
                 motivo, con_goce, estado, registrado_por, fecha_aprobacion, aprobado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'aprobada', ?, NOW(), ?)
            ");
            $stmt->execute([
                $codigo, $usuario_id, $tipo_vac['id'], $fecha_inicio, $fecha_fin, 
                $dias_habiles, $motivo, $admin_id, $admin_id
            ]);
            
            // Actualizar saldo
            $stmt = $pdo->prepare("
                UPDATE vacaciones_saldo 
                SET dias_usados = dias_usados + ? 
                WHERE usuario_id = ? AND periodo = ?
            ");
            $stmt->execute([$dias, $usuario_id, date('Y', strtotime($fecha_inicio))]);
            
            $pdo->commit();
            
            $mensaje = "Vacaciones programadas exitosamente: $codigo ($dias días)";
            $tipo_mensaje = 'success';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = 'Error: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
}

// Período actual
$periodo_actual = $_GET['periodo'] ?? date('Y');

// Obtener usuarios con saldos
$usuarios_saldos = $pdo->prepare("
    SELECT 
        u.id,
        u.dni,
        u.nombres,
        u.apellidos,
        CONCAT(u.apellidos, ', ', u.nombres) as nombre_completo,
        u.departamento,
        u.cargo,
        u.fecha_ingreso,
        u.dias_vacaciones_anuales,
        COALESCE(vs.dias_correspondientes, u.dias_vacaciones_anuales, 30) as dias_correspondientes,
        COALESCE(vs.dias_adicionales, 0) as dias_adicionales,
        COALESCE(vs.dias_usados, 0) as dias_usados,
        COALESCE(vs.dias_pendientes, u.dias_vacaciones_anuales, 30) as dias_pendientes,
        COALESCE(vs.observaciones, '') as observaciones
    FROM usuarios u
    LEFT JOIN vacaciones_saldo vs ON u.id = vs.usuario_id AND vs.periodo = ?
    WHERE u.estado = 'activo'
    ORDER BY u.apellidos, u.nombres
");
$usuarios_saldos->execute([$periodo_actual]);
$usuarios = $usuarios_saldos->fetchAll();

// Vacaciones programadas del período
$vacaciones_programadas = $pdo->prepare("
    SELECT 
        j.*,
        u.dni,
        CONCAT(u.apellidos, ', ', u.nombres) as nombre_empleado,
        u.departamento
    FROM justificaciones j
    INNER JOIN usuarios u ON j.usuario_id = u.id
    INNER JOIN tipos_justificacion tj ON j.tipo_justificacion_id = tj.id
    WHERE tj.codigo = 'VAC'
    AND YEAR(j.fecha_inicio) = ?
    AND j.estado = 'aprobada'
    ORDER BY j.fecha_inicio
");
$vacaciones_programadas->execute([$periodo_actual]);
$programadas = $vacaciones_programadas->fetchAll();

// Estadísticas
$total_dias_disponibles = array_sum(array_column($usuarios, 'dias_pendientes'));
$total_dias_usados = array_sum(array_column($usuarios, 'dias_usados'));
$empleados_sin_vacaciones = count(array_filter($usuarios, fn($u) => $u['dias_usados'] == 0));

include 'includes/header.php';
?>

<style>
    .vacation-card { border-radius: 15px; overflow: hidden; }
    .vacation-header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; }
    .saldo-circle { width: 80px; height: 80px; border-radius: 50%; background: white; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0 auto; }
    .saldo-number { font-size: 1.8rem; font-weight: bold; line-height: 1; }
    .progress-vacation { height: 10px; border-radius: 10px; }
    .calendar-mini { font-size: 0.8rem; }
    .calendar-mini th, .calendar-mini td { padding: 2px; text-align: center; }
    .dia-vacaciones { background-color: #28a74520; color: #28a745; font-weight: bold; }
</style>

<div class="container-fluid py-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="mb-1"><i class="fas fa-umbrella-beach me-2"></i>Gestión de Vacaciones</h2>
                    <p class="text-muted mb-0">Control de saldos y programación - Período <?= $periodo_actual ?></p>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select" style="width: auto;" onchange="location.href='?periodo='+this.value">
                        <?php for($a = date('Y'); $a >= date('Y')-3; $a--): ?>
                        <option value="<?= $a ?>" <?= $periodo_actual == $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalProgramar">
                        <i class="fas fa-calendar-plus me-2"></i>Programar Vacaciones
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
        <?= $mensaje ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Resumen General -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card vacation-card shadow-sm">
                <div class="vacation-header">
                    <div class="saldo-circle">
                        <span class="saldo-number text-success"><?= $total_dias_disponibles ?></span>
                        <small class="text-muted">días</small>
                    </div>
                </div>
                <div class="card-body text-center">
                    <h6 class="mb-0">Total Días Disponibles</h6>
                    <small class="text-muted">Todos los empleados</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-3x text-primary mb-3"></i>
                    <h3><?= $total_dias_usados ?></h3>
                    <p class="text-muted mb-0">Días Utilizados</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x text-warning mb-3"></i>
                    <h3><?= $empleados_sin_vacaciones ?></h3>
                    <p class="text-muted mb-0">Sin Vacaciones Tomadas</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-plane fa-3x text-info mb-3"></i>
                    <h3><?= count($programadas) ?></h3>
                    <p class="text-muted mb-0">Vacaciones Programadas</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Tabla de Saldos -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Saldo de Vacaciones por Empleado</h5>
                    <input type="text" class="form-control form-control-sm" style="width: 200px;" 
                           placeholder="Buscar..." id="buscarEmpleado">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="tablaSaldos">
                            <thead class="table-light">
                                <tr>
                                    <th>Empleado</th>
                                    <th>Departamento</th>
                                    <th class="text-center">Corresponden</th>
                                    <th class="text-center">Usados</th>
                                    <th class="text-center">Pendientes</th>
                                    <th style="width: 150px;">Progreso</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): 
                                    $total = $u['dias_correspondientes'] + $u['dias_adicionales'];
                                    $porcentaje = $total > 0 ? round(($u['dias_usados'] / $total) * 100) : 0;
                                    $color = $porcentaje > 80 ? 'danger' : ($porcentaje > 50 ? 'warning' : 'success');
                                ?>
                                <tr class="fila-empleado" data-nombre="<?= strtolower($u['nombre_completo'] . ' ' . $u['dni']) ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($u['nombre_completo']) ?></strong>
                                        <br><small class="text-muted"><?= $u['dni'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($u['departamento'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $u['dias_correspondientes'] ?></span>
                                        <?php if ($u['dias_adicionales'] > 0): ?>
                                        <span class="badge bg-info">+<?= $u['dias_adicionales'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><span class="badge bg-secondary"><?= $u['dias_usados'] ?></span></td>
                                    <td class="text-center"><span class="badge bg-<?= $color ?> fs-6"><?= $u['dias_pendientes'] ?></span></td>
                                    <td>
                                        <div class="progress progress-vacation">
                                            <div class="progress-bar bg-<?= $color ?>" style="width: <?= $porcentaje ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $porcentaje ?>% usado</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-success" onclick="programarPara(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nombre_completo']) ?>', <?= $u['dias_pendientes'] ?>)" title="Programar">
                                                <i class="fas fa-calendar-plus"></i>
                                            </button>
                                            <button class="btn btn-outline-primary" onclick="editarSaldo(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nombre_completo']) ?>', <?= $u['dias_correspondientes'] ?>, <?= $u['dias_adicionales'] ?>, '<?= htmlspecialchars($u['observaciones']) ?>')" title="Editar saldo">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vacaciones Programadas -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-plane-departure me-2"></i>Próximas Vacaciones</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                        <?php if (empty($programadas)): ?>
                        <li class="list-group-item text-center text-muted py-4">
                            <i class="fas fa-calendar-times fa-3x mb-2 d-block"></i>
                            No hay vacaciones programadas
                        </li>
                        <?php else: ?>
                        <?php foreach ($programadas as $p): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong class="small"><?= htmlspecialchars($p['nombre_empleado']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= $p['departamento'] ?></small>
                                </div>
                                <span class="badge bg-success"><?= $p['dias_calendario'] ?> días</span>
                            </div>
                            <div class="mt-2 small">
                                <i class="fas fa-calendar text-primary me-1"></i>
                                <?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?>
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

<!-- Modal Programar Vacaciones -->
<div class="modal fade" id="modalProgramar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Programar Vacaciones</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="programar_vacaciones">
                    
                    <div class="mb-3">
                        <label class="form-label">Empleado</label>
                        <select name="usuario_id" class="form-select" required id="selectEmpleadoProg">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>" data-saldo="<?= $u['dias_pendientes'] ?>">
                                <?= htmlspecialchars($u['dni'] . ' - ' . $u['nombre_completo']) ?> 
                                (<?= $u['dias_pendientes'] ?> días disponibles)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="alertaSaldoProg" class="alert alert-info d-none">
                        Días disponibles: <strong id="diasDisponiblesProg">0</strong>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" required id="fechaInicioProg">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" required id="fechaFinProg">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Total días: <strong id="totalDiasProg">0</strong></label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Motivo/Observaciones</label>
                        <textarea name="motivo" class="form-control" rows="2" placeholder="Vacaciones programadas"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Programar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Saldo -->
<div class="modal fade" id="modalSaldo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Ajustar Saldo de Vacaciones</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="ajustar_saldo">
                    <input type="hidden" name="usuario_id" id="saldoUsuarioId">
                    <input type="hidden" name="periodo" value="<?= $periodo_actual ?>">
                    
                    <div class="alert alert-info">
                        <strong id="saldoNombreEmpleado"></strong>
                        <br>Período: <?= $periodo_actual ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Días Correspondientes</label>
                            <input type="number" name="dias_correspondientes" class="form-control" id="saldoDiasCorrespondientes" min="0" max="60" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Días Adicionales</label>
                            <input type="number" name="dias_adicionales" class="form-control" id="saldoDiasAdicionales" min="0" max="30" value="0">
                            <small class="text-muted">Bonus, compensaciones, etc.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2" id="saldoObservaciones" 
                                  placeholder="Razón del ajuste (opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Búsqueda de empleados
document.getElementById('buscarEmpleado').addEventListener('input', function() {
    const buscar = this.value.toLowerCase();
    document.querySelectorAll('.fila-empleado').forEach(fila => {
        const nombre = fila.dataset.nombre;
        fila.style.display = nombre.includes(buscar) ? '' : 'none';
    });
});

// Calcular días al programar
function calcularDiasProg() {
    const inicio = document.getElementById('fechaInicioProg').value;
    const fin = document.getElementById('fechaFinProg').value;
    if (inicio && fin) {
        const dias = Math.floor((new Date(fin) - new Date(inicio)) / 86400000) + 1;
        document.getElementById('totalDiasProg').textContent = dias > 0 ? dias : 0;
    }
}

document.getElementById('fechaInicioProg').addEventListener('change', calcularDiasProg);
document.getElementById('fechaFinProg').addEventListener('change', calcularDiasProg);

document.getElementById('selectEmpleadoProg').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const saldo = option.dataset.saldo || 0;
    document.getElementById('diasDisponiblesProg').textContent = saldo;
    document.getElementById('alertaSaldoProg').classList.remove('d-none');
});

// Programar para empleado específico
function programarPara(id, nombre, saldo) {
    document.getElementById('selectEmpleadoProg').value = id;
    document.getElementById('diasDisponiblesProg').textContent = saldo;
    document.getElementById('alertaSaldoProg').classList.remove('d-none');
    new bootstrap.Modal(document.getElementById('modalProgramar')).show();
}

// Editar saldo
function editarSaldo(id, nombre, dias, adicionales, obs) {
    document.getElementById('saldoUsuarioId').value = id;
    document.getElementById('saldoNombreEmpleado').textContent = nombre;
    document.getElementById('saldoDiasCorrespondientes').value = dias;
    document.getElementById('saldoDiasAdicionales').value = adicionales;
    document.getElementById('saldoObservaciones').value = obs;
    new bootstrap.Modal(document.getElementById('modalSaldo')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
