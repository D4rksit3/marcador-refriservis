<?php
require_once '../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$mensaje = ''; $tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'cambiar_dia') {
        $usuario_id = (int) $_POST['usuario_id'];
        $dia_nuevo = (int) $_POST['dia_nuevo'];
        $motivo = sanitize($_POST['motivo'] ?? '');
        $stmt = $pdo->prepare("SELECT dia_descanso FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]); $usuario = $stmt->fetch();
        if ($usuario) {
            $pdo->prepare("INSERT INTO historial_dia_descanso (usuario_id, dia_anterior, dia_nuevo, motivo, admin_id) VALUES (?, ?, ?, ?, ?)")
                ->execute([$usuario_id, $usuario['dia_descanso'], $dia_nuevo, $motivo, $_SESSION['admin_id']]);
            $pdo->prepare("UPDATE usuarios SET dia_descanso = ? WHERE id = ?")->execute([$dia_nuevo, $usuario_id]);
            $mensaje = 'Día de descanso actualizado'; $tipo_mensaje = 'success';
        }
    }
}

$usuarios = $pdo->query("SELECT * FROM usuarios WHERE estado = 'activo' ORDER BY apellidos, nombres")->fetchAll();
$distribucion = $pdo->query("SELECT dia_descanso, COUNT(*) as total FROM usuarios WHERE estado = 'activo' GROUP BY dia_descanso")->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/header.php';
?>
<div class="container-fluid py-4">
    <h2><i class="fas fa-calendar-week me-2"></i>Días de Descanso</h2>
    <?php if ($mensaje): ?><div class="alert alert-<?= $tipo_mensaje ?>"><?= $mensaje ?></div><?php endif; ?>
    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white"><h6 class="mb-0">Distribución</h6></div>
                <div class="card-body"><canvas id="chartDias" height="200"></canvas></div>
                <ul class="list-group list-group-flush">
                    <?php for ($i = 0; $i <= 6; $i++): ?>
                    <li class="list-group-item d-flex justify-content-between"><?= getNombreDia($i) ?><span class="badge bg-primary"><?= $distribucion[$i] ?? 0 ?></span></li>
                    <?php endfor; ?>
                </ul>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><h6 class="mb-0">Empleados</h6></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>DNI</th><th>Empleado</th><th>Día Descanso</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= $u['dni'] ?></td>
                                <td><strong><?= htmlspecialchars($u['apellidos'] . ', ' . $u['nombres']) ?></strong></td>
                                <td><span class="badge bg-info"><?= getNombreDia($u['dia_descanso']) ?></span></td>
                                <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCambiar" onclick="document.getElementById('cambiar_usuario_id').value=<?= $u['id'] ?>;document.getElementById('cambiar_dia').value=<?= $u['dia_descanso'] ?>"><i class="fas fa-edit"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalCambiar" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Cambiar Día</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST"><div class="modal-body">
        <input type="hidden" name="action" value="cambiar_dia"><input type="hidden" name="usuario_id" id="cambiar_usuario_id">
        <div class="mb-3"><label class="form-label">Nuevo Día</label><select name="dia_nuevo" id="cambiar_dia" class="form-select"><?php for ($i = 0; $i <= 6; $i++): ?><option value="<?= $i ?>"><?= getNombreDia($i) ?></option><?php endfor; ?></select></div>
        <div class="mb-3"><label class="form-label">Motivo</label><input type="text" name="motivo" class="form-control"></div>
    </div><div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div></form>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>new Chart(document.getElementById('chartDias'),{type:'doughnut',data:{labels:['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'],datasets:[{data:[<?=implode(',',array_map(fn($i)=>$distribucion[$i]??0,range(0,6)))?>],backgroundColor:['#dc3545','#ffc107','#28a745','#17a2b8','#6f42c1','#fd7e14','#6c757d']}]},options:{plugins:{legend:{position:'bottom'}}}});</script>
<?php include 'includes/footer.php'; ?>
