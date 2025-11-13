<?php
// admin/administradores.php
define('ADMIN_AREA', true);
require_once '../config.php';
requireAuth();

$page_title = 'Gestión de Administradores';
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        if ($accion === 'agregar') {
            $usuario = sanitize($_POST['usuario']);
            $password = $_POST['password'];
            $nombre_completo = sanitize($_POST['nombre_completo']);
            $correo = sanitize($_POST['correo']);
            
            // Validar que el usuario no exista
            $stmt = $conn->prepare("SELECT id FROM administradores WHERE usuario = ? OR correo = ?");
            $stmt->execute([$usuario, $correo]);
            if ($stmt->fetch()) {
                $mensaje = 'El usuario o correo ya existe';
                $tipo_mensaje = 'error';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("
                    INSERT INTO administradores (usuario, password, nombre_completo, correo) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$usuario, $password_hash, $nombre_completo, $correo]);
                
                $mensaje = 'Administrador agregado exitosamente';
                $tipo_mensaje = 'success';
            }
        }
        elseif ($accion === 'editar') {
            $id = intval($_POST['id']);
            $usuario = sanitize($_POST['usuario']);
            $nombre_completo = sanitize($_POST['nombre_completo']);
            $correo = sanitize($_POST['correo']);
            $password = $_POST['password'] ?? '';
            
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    UPDATE administradores 
                    SET usuario = ?, password = ?, nombre_completo = ?, correo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$usuario, $password_hash, $nombre_completo, $correo, $id]);
            } else {
                $stmt = $conn->prepare("
                    UPDATE administradores 
                    SET usuario = ?, nombre_completo = ?, correo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$usuario, $nombre_completo, $correo, $id]);
            }
            
            $mensaje = 'Administrador actualizado exitosamente';
            $tipo_mensaje = 'success';
        }
        elseif ($accion === 'eliminar') {
            $id = intval($_POST['id']);
            
            // No permitir eliminar al último administrador
            $stmt = $conn->query("SELECT COUNT(*) as total FROM administradores");
            if ($stmt->fetch()['total'] <= 1) {
                $mensaje = 'No puedes eliminar al único administrador del sistema';
                $tipo_mensaje = 'error';
            } elseif ($id == $_SESSION['admin_id']) {
                $mensaje = 'No puedes eliminar tu propia cuenta';
                $tipo_mensaje = 'error';
            } else {
                $stmt = $conn->prepare("DELETE FROM administradores WHERE id = ?");
                $stmt->execute([$id]);
                
                $mensaje = 'Administrador eliminado exitosamente';
                $tipo_mensaje = 'success';
            }
        }
    } catch(PDOException $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener administradores
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SELECT * FROM administradores ORDER BY id DESC");
    $administradores = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>🔐 Gestión de Administradores</h1>
    <p>Administra los usuarios con acceso al panel</p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Listado de Administradores</h2>
        <button class="btn btn-primary" onclick="abrirModalAgregar()">
            ➕ Agregar Administrador
        </button>
    </div>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Nombre Completo</th>
                    <th>Correo</th>
                    <th>Último Acceso</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($administradores as $admin): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($admin['usuario']); ?></strong></td>
                        <td><?php echo htmlspecialchars($admin['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($admin['correo']); ?></td>
                        <td>
                            <?php 
                            if ($admin['ultimo_acceso']) {
                                echo date('d/m/Y h:i A', strtotime($admin['ultimo_acceso']));
                            } else {
                                echo '<span style="color: #999;">Nunca</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($admin['fecha_registro'])); ?></td>
                        <td>
                            <button class="btn btn-info btn-sm" onclick='editarAdmin(<?php echo json_encode($admin); ?>)'>✏️ Editar</button>
                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                <button class="btn btn-danger btn-sm" onclick="eliminarAdmin(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['usuario']); ?>')">🗑️ Eliminar</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Agregar/Editar Administrador -->
<div id="modalAdmin" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo">Agregar Administrador</h2>
            <span class="close-modal" onclick="cerrarModal()">&times;</span>
        </div>
        <form method="POST" id="formAdmin">
            <div class="modal-body">
                <input type="hidden" name="accion" id="accion" value="agregar">
                <input type="hidden" name="id" id="id">
                
                <div class="form-group">
                    <label for="usuario">Usuario: *</label>
                    <input type="text" name="usuario" id="usuario" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo: *</label>
                    <input type="text" name="nombre_completo" id="nombre_completo" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="correo">Correo Electrónico: *</label>
                    <input type="email" name="correo" id="correo" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password" id="labelPassword">Contraseña: *</label>
                    <input type="password" name="password" id="password" class="form-control">
                    <small style="color: #666; font-size: 12px;" id="passwordHelp"></small>
                </div>
                
                <div class="alert alert-info">
                    <strong>Nota:</strong> Asegúrate de guardar las credenciales en un lugar seguro.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn btn-success">💾 Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Formulario oculto para eliminar -->
<form method="POST" id="formEliminar" style="display: none;">
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="id" id="eliminar_id">
</form>

<script>
function abrirModalAgregar() {
    document.getElementById('modalTitulo').textContent = 'Agregar Administrador';
    document.getElementById('accion').value = 'agregar';
    document.getElementById('formAdmin').reset();
    document.getElementById('labelPassword').textContent = 'Contraseña: *';
    document.getElementById('password').required = true;
    document.getElementById('passwordHelp').textContent = 'Mínimo 8 caracteres';
    document.getElementById('modalAdmin').style.display = 'block';
}

function editarAdmin(admin) {
    document.getElementById('modalTitulo').textContent = 'Editar Administrador';
    document.getElementById('accion').value = 'editar';
    document.getElementById('id').value = admin.id;
    document.getElementById('usuario').value = admin.usuario;
    document.getElementById('nombre_completo').value = admin.nombre_completo;
    document.getElementById('correo').value = admin.correo;
    document.getElementById('password').value = '';
    document.getElementById('labelPassword').textContent = 'Nueva Contraseña (dejar vacío para no cambiar):';
    document.getElementById('password').required = false;
    document.getElementById('passwordHelp').textContent = 'Solo completa este campo si deseas cambiar la contraseña';
    document.getElementById('modalAdmin').style.display = 'block';
}

function eliminarAdmin(id, usuario) {
    if (confirm('¿Estás seguro de eliminar al administrador "' + usuario + '"?')) {
        document.getElementById('eliminar_id').value = id;
        document.getElementById('formEliminar').submit();
    }
}

function cerrarModal() {
    document.getElementById('modalAdmin').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('modalAdmin');
    if (event.target == modal) {
        cerrarModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>