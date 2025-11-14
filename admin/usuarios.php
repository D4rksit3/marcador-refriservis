<?php
// admin/usuarios.php
define('ADMIN_AREA', true);
require_once '../config.php';
requireAuth();

$page_title = 'Gestión de Usuarios';
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        if ($accion === 'agregar') {
            $dni = sanitize($_POST['dni']);
            $nombres = sanitize($_POST['nombres']);
            $apellidos = sanitize($_POST['apellidos']);
            $correo = sanitize($_POST['correo']);
            $telefono = sanitize($_POST['telefono']);
            $cargo = sanitize($_POST['cargo']);
            $departamento = sanitize($_POST['departamento']);
            $fecha_ingreso = sanitize($_POST['fecha_ingreso']);
            
            $stmt = $conn->prepare("
                INSERT INTO usuarios (dni, nombres, apellidos, correo, telefono, cargo, departamento, fecha_ingreso) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$dni, $nombres, $apellidos, $correo, $telefono, $cargo, $departamento, $fecha_ingreso]);
            
            $mensaje = 'Usuario agregado exitosamente';
            $tipo_mensaje = 'success';
        }
        elseif ($accion === 'editar') {
            $id = intval($_POST['id']);
            $dni = sanitize($_POST['dni']);
            $nombres = sanitize($_POST['nombres']);
            $apellidos = sanitize($_POST['apellidos']);
            $correo = sanitize($_POST['correo']);
            $telefono = sanitize($_POST['telefono']);
            $cargo = sanitize($_POST['cargo']);
            $departamento = sanitize($_POST['departamento']);
            $fecha_ingreso = sanitize($_POST['fecha_ingreso']);
            $estado = sanitize($_POST['estado']);
            
            $stmt = $conn->prepare("
                UPDATE usuarios 
                SET dni = ?, nombres = ?, apellidos = ?, correo = ?, telefono = ?, 
                    cargo = ?, departamento = ?, fecha_ingreso = ?, estado = ?
                WHERE id = ?
            ");
            $stmt->execute([$dni, $nombres, $apellidos, $correo, $telefono, $cargo, $departamento, $fecha_ingreso, $estado, $id]);
            
            $mensaje = 'Usuario actualizado exitosamente';
            $tipo_mensaje = 'success';
        }
        elseif ($accion === 'eliminar') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            
            $mensaje = 'Usuario eliminado exitosamente';
            $tipo_mensaje = 'success';
        }
    } catch(PDOException $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener usuarios
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $buscar = isset($_GET['buscar']) ? sanitize($_GET['buscar']) : '';
    $filtro_estado = isset($_GET['estado']) ? sanitize($_GET['estado']) : '';
    
    $sql = "SELECT * FROM usuarios WHERE 1=1";
    $params = [];
    
    if (!empty($buscar)) {
        $sql .= " AND (dni LIKE ? OR nombres LIKE ? OR apellidos LIKE ? OR correo LIKE ?)";
        $buscar_param = "%{$buscar}%";
        $params = array_merge($params, [$buscar_param, $buscar_param, $buscar_param, $buscar_param]);
    }
    
    if (!empty($filtro_estado)) {
        $sql .= " AND estado = ?";
        $params[] = $filtro_estado;
    }
    
    $sql .= " ORDER BY id DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>👥 Gestión de Usuarios</h1>
    <p>Administra los empleados del sistema</p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Listado de Usuarios</h2>
        <button class="btn btn-primary" onclick="abrirModalAgregar()">
            ➕ Agregar Usuario
        </button>
    </div>
    
    <!-- Filtros -->
    <form method="GET" style="margin-bottom: 20px;">
        <div class="form-row">
            <div class="form-group">
                <input type="text" name="buscar" class="form-control" placeholder="Buscar por DNI, nombre o correo..." value="<?php echo htmlspecialchars($buscar); ?>">
            </div>
            <div class="form-group">
                <select name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activos</option>
                    <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-info">🔍 Buscar</button>
                <a href="usuarios.php" class="btn btn-warning">🔄 Limpiar</a>
            </div>
        </div>
    </form>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>DNI</th>
                    <th>Nombre Completo</th>
                    <th>Correo</th>
                    <th>Cargo</th>
                    <th>Departamento</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($usuarios) > 0): ?>
                    <?php foreach($usuarios as $usuario): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($usuario['dni']); ?></strong></td>
                            <td><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['cargo']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['departamento']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $usuario['estado'] === 'activo' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($usuario['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick='editarUsuario(<?php echo json_encode($usuario); ?>)'>✏️ Editar</button>
                                <button class="btn btn-danger btn-sm" onclick="eliminarUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombres']); ?>')">🗑️ Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999;">No se encontraron usuarios</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Agregar/Editar Usuario -->
<div id="modalUsuario" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo">Agregar Usuario</h2>
            <span class="close-modal" onclick="cerrarModal()">&times;</span>
        </div>
        <form method="POST" id="formUsuario">
            <div class="modal-body">
                <input type="hidden" name="accion" id="accion" value="agregar">
                <input type="hidden" name="id" id="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="dni">DNI: *</label>
                        <input type="text" name="dni" id="dni" class="form-control" required maxlength="20">
                    </div>
                    <div class="form-group">
                        <label for="fecha_ingreso">Fecha de Ingreso:</label>
                        <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombres">Nombres: *</label>
                        <input type="text" name="nombres" id="nombres" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="apellidos">Apellidos: *</label>
                        <input type="text" name="apellidos" id="apellidos" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="correo">Correo Electrónico: *</label>
                        <input type="email" name="correo" id="correo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" name="telefono" id="telefono" class="form-control" maxlength="20">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cargo">Cargo:</label>
                        <input type="text" name="cargo" id="cargo" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="departamento">Departamento:</label>
                        <input type="text" name="departamento" id="departamento" class="form-control">
                    </div>
                </div>
                
                <div class="form-group" id="estadoGroup" style="display: none;">
                    <label for="estado">Estado:</label>
                    <select name="estado" id="estado" class="form-control">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
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
    document.getElementById('modalTitulo').textContent = 'Agregar Usuario';
    document.getElementById('accion').value = 'agregar';
    document.getElementById('formUsuario').reset();
    document.getElementById('estadoGroup').style.display = 'none';
    document.getElementById('modalUsuario').style.display = 'block';
}

function editarUsuario(usuario) {
    document.getElementById('modalTitulo').textContent = 'Editar Usuario';
    document.getElementById('accion').value = 'editar';
    document.getElementById('id').value = usuario.id;
    document.getElementById('dni').value = usuario.dni;
    document.getElementById('nombres').value = usuario.nombres;
    document.getElementById('apellidos').value = usuario.apellidos;
    document.getElementById('correo').value = usuario.correo;
    document.getElementById('telefono').value = usuario.telefono || '';
    document.getElementById('cargo').value = usuario.cargo || '';
    document.getElementById('departamento').value = usuario.departamento || '';
    document.getElementById('fecha_ingreso').value = usuario.fecha_ingreso || '';
    document.getElementById('estado').value = usuario.estado;
    document.getElementById('estadoGroup').style.display = 'block';
    document.getElementById('modalUsuario').style.display = 'block';
}

function eliminarUsuario(id, nombre) {
    if (confirm('¿Estás seguro de eliminar al usuario "' + nombre + '"?\n\nEsta acción también eliminará todas sus marcaciones.')) {
        document.getElementById('eliminar_id').value = id;
        document.getElementById('formEliminar').submit();
    }
}

function cerrarModal() {
    document.getElementById('modalUsuario').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('modalUsuario');
    if (event.target == modal) {
        cerrarModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>s