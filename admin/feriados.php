<?php
require_once '../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$mensaje = ''; $tipo_mensaje = '';
$anio = $_GET['anio'] ?? date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'agregar') {
        $fecha = $_POST['fecha'];
        $nombre = sanitize($_POST['nombre']);
        $tipo = $_POST['tipo'];
        try {
            $pdo->prepare("INSERT INTO feriados (fecha, nombre, tipo) VALUES (?, ?, ?)")->execute([$fecha, $nombre, $tipo]);
            $mensaje = 'Feriado agregado'; $tipo_mensaje = 'success';
        } catch (PDOException $e) {
            $mensaje = 'Ya existe un feriado en esa fecha'; $tipo_mensaje = 'danger';
        }
    }
    if ($action === 'eliminar') {
        $pdo->prepare("DELETE FROM feriados WHERE id = ?")->execute([(int)$_POST['id']]);
        $mensaje = 'Feriado eliminado'; $tipo_mensaje = 'success';
    }
}

$feriados = $pdo->prepare("SELECT * FROM feriados WHERE YEAR(fecha) = ? ORDER BY fecha");
$feriados->execute([$anio]); $feriados = $feriados->fetchAll();

include 'includes/header.php';
?>
<div class="container-fluid py-4">
    <h2><i class="fas fa-flag me-2"></i>Feriados <?= $anio ?></h2>
    <?php if ($mensaje): ?><div class="alert alert-<?= $tipo_mensaje ?>"><?= $mensaje ?></div><?php endif; ?>
    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white"><h6 class="mb-0"><i class="fas fa-plus me-2"></i>Agregar Feriado</h6></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="agregar">
                        <div class="mb-3"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" required placeholder="Ej: Día del Trabajo"></div>
                        <div class="mb-3"><label class="form-label">Tipo</label>
                            <select name="tipo" class="form-select">
                                <option value="nacional">Nacional</option>
                                <option value="regional">Regional</option>
                                <option value="empresa">Empresa</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-plus me-2"></i>Agregar</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <h6 class="mb-0">Listado de Feriados</h6>
                    <div>
                        <a href="?anio=<?= $anio - 1 ?>" class="btn btn-sm btn-light">&laquo; <?= $anio - 1 ?></a>
                        <a href="?anio=<?= $anio + 1 ?>" class="btn btn-sm btn-light"><?= $anio + 1 ?> &raquo;</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Fecha</th><th>Nombre</th><th>Tipo</th><th></th></tr></thead>
                        <tbody>
                            <?php if (empty($feriados)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No hay feriados registrados</td></tr>
                            <?php else: foreach ($feriados as $f): ?>
                            <tr>
                                <td><?= formatearFecha($f['fecha']) ?> <small class="text-muted">(<?= getNombreDia((int)date('w', strtotime($f['fecha']))) ?>)</small></td>
                                <td><strong><?= htmlspecialchars($f['nombre']) ?></strong></td>
                                <td><span class="badge bg-<?= $f['tipo'] == 'nacional' ? 'danger' : ($f['tipo'] == 'regional' ? 'warning' : 'info') ?>"><?= ucfirst($f['tipo']) ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar feriado?')">
                                        <input type="hidden" name="action" value="eliminar"><input type="hidden" name="id" value="<?= $f['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer"><small class="text-muted">Total: <?= count($feriados) ?> feriados en <?= $anio ?></small></div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
