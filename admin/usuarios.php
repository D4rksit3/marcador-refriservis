<?php
require_once '../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$mensaje = ''; $tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'crear') {
        $dni = sanitize($_POST['dni']);
        $nombres = sanitize($_POST['nombres']);
        $apellidos = sanitize($_POST['apellidos']);
        $correo = sanitize($_POST['correo']);
        $telefono = sanitize($_POST['telefono']);
        $cargo = sanitize($_POST['cargo']);
        $departamento = sanitize($_POST['departamento']);
        $fecha_ingreso = $_POST['fecha_ingreso'];
        $dia_descanso = (int) $_POST['dia_descanso'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (dni, nombres, apellidos, correo, telefono, cargo, departamento, fecha_ingreso, dia_descanso) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$dni, $nombres, $apellidos, $correo, $telefono, $cargo, $departamento, $fecha_ingreso, $dia_descanso]);
            $mensaje = 'Usuario creado correctamente'; $tipo_mensaje = 'success';
        } catch (PDOException $e) {
            $mensaje = 'Error: DNI o correo ya existe'; $tipo_mensaje = 'danger';
        }
    }
    
    if ($action === 'editar') {
        $id = (int) $_POST['id'];
        $nombres = sanitize($_POST['nombres']);
        $apellidos = sanitize($_POST['apellidos']);
        $correo = sanitize($_POST['correo']);
        $telefono = sanitize($_POST['telefono']);
        $cargo = sanitize($_POST['cargo']);
        $departamento = sanitize($_POST['departamento']);
        $estado = $_POST['estado'];
        
        $pdo->prepare("UPDATE usuarios SET nombres=?, apellidos=?, correo=?, telefono=?, cargo=?, departamento=?, estado=? WHERE id=?")
            ->execute([$nombres, $apellidos, $correo, $telefono, $cargo, $departamento, $estado, $id]);
        $mensaje = 'Usuario actualizado'; $tipo_mensaje = 'success';
    }
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY estado DESC, apellidos, nombres")->fetchAll();
$departamentos = $pdo->query("SELECT DISTINCT departamento FROM usuarios WHERE departamento IS NOT NULL AND departamento != '' ORDER BY departamento")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between mb-4">
        <h2><i class="fas fa-users me-2"></i>Gestión de Usuarios</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear"><i class="fas fa-plus me-2"></i>Nuevo Usuario</button>
    </div>
    
    <?php if ($mensaje): ?><div class="alert alert-<?= $tipo_mensaje ?>"><?= $mensaje ?></div><?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-dark"><tr><th>DNI</th><th>Nombre</th><th>Cargo</th><th>Departamento</th><th>Ingreso</th><th>Descanso</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr class="<?= $u['estado'] == 'inactivo' ? 'table-secondary' : '' ?>">
                        <td><?= $u['dni'] ?></td>
                        <td><strong><?= htmlspecialchars($u['apellidos'] . ', ' . $u['nombres']) ?></strong><br><small class="text-muted"><?= $u['correo'] ?></small></td>
                        <td><?= htmlspecialchars($u['cargo'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($u['departamento'] ?? '-') ?></td>
                        <td><?= $u['fecha_ingreso'] ? formatearFecha($u['fecha_ingreso']) : '-' ?></td>
                        <td><span class="badge bg-info"><?= getNombreDia($u['dia_descanso'] ?? 0) ?></span></td>
                        <td><span class="badge bg-<?= $u['estado'] == 'activo' ? 'success' : 'secondary' ?>"><?= ucfirst($u['estado']) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick='editarUsuario(<?= json_encode($u) ?>)'><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer"><small class="text-muted">Total: <?= count($usuarios) ?> usuarios</small></div>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="modalCrear" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header bg-primary text-white"><h5 class="modal-title">Nuevo Usuario</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST"><div class="modal-body">
        <input type="hidden" name="action" value="crear">
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">DNI</label><input type="text" name="dni" class="form-control" maxlength="8" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Nombres</label><input type="text" name="nombres" class="form-control" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Apellidos</label><input type="text" name="apellidos" class="form-control" required></div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Correo</label><input type="email" name="correo" class="form-control" required></div>
            <div class="col-md-6 mb-3"><label class="form-label">Teléfono</label><input type="text" name="telefono" class="form-control"></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Cargo</label><input type="text" name="cargo" class="form-control"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Departamento</label><input type="text" name="departamento" class="form-control" list="deptos">
                <datalist id="deptos"><?php foreach ($departamentos as $d): ?><option value="<?= htmlspecialchars($d) ?>"><?php endforeach; ?></datalist>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label">Fecha Ingreso</label><input type="date" name="fecha_ingreso" class="form-control"></div>
        </div>
        <div class="mb-3"><label class="form-label">Día de Descanso</label>
            <select name="dia_descanso" class="form-select"><?php for ($i = 0; $i <= 6; $i++): ?><option value="<?= $i ?>"><?= getNombreDia($i) ?></option><?php endfor; ?></select>
        </div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-primary">Crear Usuario</button></div>
    </form>
</div></div></div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header bg-warning"><h5 class="modal-title">Editar Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" id="formEditar"><div class="modal-body">
        <input type="hidden" name="action" value="editar"><input type="hidden" name="id" id="edit_id">
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Nombres</label><input type="text" name="nombres" id="edit_nombres" class="form-control" required></div>
            <div class="col-md-6 mb-3"><label class="form-label">Apellidos</label><input type="text" name="apellidos" id="edit_apellidos" class="form-control" required></div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Correo</label><input type="email" name="correo" id="edit_correo" class="form-control" required></div>
            <div class="col-md-6 mb-3"><label class="form-label">Teléfono</label><input type="text" name="telefono" id="edit_telefono" class="form-control"></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Cargo</label><input type="text" name="cargo" id="edit_cargo" class="form-control"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Departamento</label><input type="text" name="departamento" id="edit_departamento" class="form-control"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Estado</label><select name="estado" id="edit_estado" class="form-select"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
        </div>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-warning">Guardar Cambios</button></div>
    </form>
</div></div></div>

<script>
function editarUsuario(u) {
    document.getElementById('edit_id').value = u.id;
    document.getElementById('edit_nombres').value = u.nombres;
    document.getElementById('edit_apellidos').value = u.apellidos;
    document.getElementById('edit_correo').value = u.correo;
    document.getElementById('edit_telefono').value = u.telefono || '';
    document.getElementById('edit_cargo').value = u.cargo || '';
    document.getElementById('edit_departamento').value = u.departamento || '';
    document.getElementById('edit_estado').value = u.estado;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>
<?php include 'includes/footer.php'; ?>
