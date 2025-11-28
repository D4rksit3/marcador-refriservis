<?php
require_once '../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? date('Y-m-d');

$sql = "SELECT m.*, CONCAT(u.nombres, ' ', u.apellidos) as nombre, u.dni
        FROM marcaciones m 
        INNER JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.foto_validacion IS NOT NULL";
$params = [];

if ($filtro_usuario) { $sql .= " AND m.usuario_id = ?"; $params[] = $filtro_usuario; }
if ($filtro_tipo) { $sql .= " AND m.tipo_marcacion = ?"; $params[] = $filtro_tipo; }
if ($filtro_fecha) { $sql .= " AND m.fecha = ?"; $params[] = $filtro_fecha; }

$sql .= " ORDER BY m.fecha DESC, m.hora DESC LIMIT 50";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $fotos = $stmt->fetchAll();

$usuarios = $pdo->query("SELECT id, nombres, apellidos FROM usuarios WHERE estado = 'activo' ORDER BY apellidos")->fetchAll();

include 'includes/header.php';
?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">

<div class="container-fluid py-4">
    <h2><i class="fas fa-camera me-2"></i>Fotos de Campo</h2>
    
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
                <div class="col-md-2">
                    <select name="tipo" class="form-select">
                        <option value="">Todos los tipos</option>
                        <option value="entrada_campo" <?= $filtro_tipo == 'entrada_campo' ? 'selected' : '' ?>>Entrada Campo</option>
                        <option value="salida_campo" <?= $filtro_tipo == 'salida_campo' ? 'selected' : '' ?>>Salida Campo</option>
                    </select>
                </div>
                <div class="col-md-2"><input type="date" name="fecha" class="form-control" value="<?= $filtro_fecha ?>"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button></div>
                <div class="col-md-1"><a href="?" class="btn btn-secondary w-100"><i class="fas fa-times"></i></a></div>
            </form>
        </div>
    </div>
    
    <?php if (empty($fotos)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No hay fotos de campo para los filtros seleccionados</div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($fotos as $f): ?>
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="card shadow-sm h-100">
                <a href="<?= '../' . htmlspecialchars($f['foto_validacion']) ?>" data-lightbox="fotos" data-title="<?= htmlspecialchars($f['nombre'] . ' - ' . $f['tipo_marcacion']) ?>">
                    <img src="<?= '../' . htmlspecialchars($f['foto_validacion']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                </a>
                <div class="card-body py-2">
                    <span class="badge bg-<?= $f['tipo_marcacion'] == 'entrada_campo' ? 'success' : 'danger' ?> mb-2"><?= ucfirst(str_replace('_', ' ', $f['tipo_marcacion'])) ?></span>
                    <h6 class="card-title mb-1"><?= htmlspecialchars($f['nombre']) ?></h6>
                    <p class="card-text small text-muted mb-1"><i class="fas fa-id-card me-1"></i><?= $f['dni'] ?></p>
                    <p class="card-text small text-muted mb-1"><i class="fas fa-calendar me-1"></i><?= formatearFecha($f['fecha']) ?> <?= date('H:i', strtotime($f['hora'])) ?></p>
                    <p class="card-text small text-muted text-truncate" title="<?= htmlspecialchars($f['direccion']) ?>"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars(substr($f['direccion'], 0, 40)) ?>...</p>
                </div>
                <div class="card-footer bg-white">
                    <a href="https://www.google.com/maps?q=<?= $f['latitud'] ?>,<?= $f['longitud'] ?>" target="_blank" class="btn btn-sm btn-outline-info w-100">
                        <i class="fas fa-map me-1"></i>Ver en Mapa
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="text-muted mt-3"><small>Mostrando <?= count($fotos) ?> fotos</small></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
<?php include 'includes/footer.php'; ?>
