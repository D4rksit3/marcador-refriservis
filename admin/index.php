<?php
// admin/index.php - Dashboard
define('ADMIN_AREA', true);
require_once '../config.php';
requireAuth();

$page_title = 'Dashboard';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Total de usuarios
    $stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'");
    $total_usuarios = $stmt->fetch()['total'];
    
    // Marcaciones de hoy
    $stmt = $conn->query("SELECT COUNT(*) as total FROM marcaciones WHERE fecha = CURDATE()");
    $marcaciones_hoy = $stmt->fetch()['total'];
    
    // Usuarios que marcaron hoy
    $stmt = $conn->query("SELECT COUNT(DISTINCT usuario_id) as total FROM marcaciones WHERE fecha = CURDATE()");
    $usuarios_hoy = $stmt->fetch()['total'];
    
    // Marcaciones de esta semana
    $stmt = $conn->query("SELECT COUNT(*) as total FROM marcaciones WHERE YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)");
    $marcaciones_semana = $stmt->fetch()['total'];
    
    // Últimas marcaciones
    $stmt = $conn->query("
        SELECT m.*, CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo, u.cargo
        FROM marcaciones m
        INNER JOIN usuarios u ON m.usuario_id = u.id
        ORDER BY m.fecha_hora_registro DESC
        LIMIT 10
    ");
    $ultimas_marcaciones = $stmt->fetchAll();
    
    // Marcaciones por tipo hoy
    $stmt = $conn->query("
        SELECT tipo_marcacion, COUNT(*) as total
        FROM marcaciones
        WHERE fecha = CURDATE()
        GROUP BY tipo_marcacion
    ");
    $marcaciones_tipo = $stmt->fetchAll();
    
    // Usuarios más activos (esta semana)
    $stmt = $conn->query("
        SELECT CONCAT(u.nombres, ' ', u.apellidos) as nombre, COUNT(*) as total
        FROM marcaciones m
        INNER JOIN usuarios u ON m.usuario_id = u.id
        WHERE YEARWEEK(m.fecha, 1) = YEARWEEK(CURDATE(), 1)
        GROUP BY m.usuario_id, nombre
        ORDER BY total DESC
        LIMIT 5
    ");
    $usuarios_activos = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>📊 Dashboard</h1>
    <p>Resumen general del sistema de marcaciones</p>
</div>

<!-- Tarjetas de estadísticas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Usuarios</div>
                <div style="font-size: 36px; font-weight: 700;"><?php echo $total_usuarios; ?></div>
            </div>
            <div style="font-size: 48px; opacity: 0.3;">👥</div>
        </div>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Marcaciones Hoy</div>
                <div style="font-size: 36px; font-weight: 700;"><?php echo $marcaciones_hoy; ?></div>
            </div>
            <div style="font-size: 48px; opacity: 0.3;">✅</div>
        </div>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Asistencias Hoy</div>
                <div style="font-size: 36px; font-weight: 700;"><?php echo $usuarios_hoy; ?></div>
            </div>
            <div style="font-size: 48px; opacity: 0.3;">🎯</div>
        </div>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Esta Semana</div>
                <div style="font-size: 36px; font-weight: 700;"><?php echo $marcaciones_semana; ?></div>
            </div>
            <div style="font-size: 48px; opacity: 0.3;">📅</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 25px;">
    <!-- Últimas Marcaciones -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🕐 Últimas Marcaciones</h2>
            <a href="reportes.php" class="btn btn-primary btn-sm">Ver Todas</a>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Tipo</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($ultimas_marcaciones) > 0): ?>
                        <?php foreach($ultimas_marcaciones as $marca): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($marca['nombre_completo']); ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($marca['cargo']); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $badges = [
                                        'entrada' => 'success',
                                        'salida' => 'danger',
                                        'entrada_refrigerio' => 'info',
                                        'salida_refrigerio' => 'warning',
                                        'entrada_campo' => 'info',
                                        'salida_campo' => 'warning'
                                    ];
                                    $badge_class = $badges[$marca['tipo_marcacion']] ?? 'info';
                                    ?>
                                    <span class="badge badge-<?php echo $badge_class; ?>">
                                        <?php echo formatTipoMarcacion($marca['tipo_marcacion']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('h:i A', strtotime($marca['hora'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #999;">No hay marcaciones aún</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Marcaciones por Tipo -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📊 Marcaciones Hoy por Tipo</h2>
        </div>
        <?php if (count($marcaciones_tipo) > 0): ?>
            <div style="padding: 10px 0;">
                <?php foreach($marcaciones_tipo as $tipo): ?>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-weight: 600; font-size: 14px;">
                                <?php echo formatTipoMarcacion($tipo['tipo_marcacion']); ?>
                            </span>
                            <span style="font-weight: 700; color: #667eea;"><?php echo $tipo['total']; ?></span>
                        </div>
                        <div style="background: #e5e7eb; border-radius: 10px; height: 8px; overflow: hidden;">
                            <?php 
                            $porcentaje = ($tipo['total'] / $marcaciones_hoy) * 100;
                            $colores = [
                                'entrada' => '#10b981',
                                'salida' => '#ef4444',
                                'entrada_refrigerio' => '#06b6d4',
                                'salida_refrigerio' => '#f59e0b',
                                'entrada_campo' => '#8b5cf6',
                                'salida_campo' => '#ec4899'
                            ];
                            $color = $colores[$tipo['tipo_marcacion']] ?? '#667eea';
                            ?>
                            <div style="background: <?php echo $color; ?>; height: 100%; width: <?php echo $porcentaje; ?>%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #999; padding: 20px;">No hay marcaciones hoy</p>
        <?php endif; ?>
    </div>
    
    <!-- Usuarios Más Activos -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🏆 Usuarios Más Activos (Esta Semana)</h2>
        </div>
        <?php if (count($usuarios_activos) > 0): ?>
            <div style="padding: 10px 0;">
                <?php foreach($usuarios_activos as $index => $usuario): ?>
                    <div style="display: flex; align-items: center; gap: 15px; padding: 12px; background: <?php echo $index % 2 == 0 ? '#f9fafb' : 'white'; ?>; border-radius: 8px; margin-bottom: 8px;">
                        <div style="font-size: 24px; font-weight: 700; color: <?php echo $index == 0 ? '#f59e0b' : ($index == 1 ? '#94a3b8' : ($index == 2 ? '#fb923c' : '#cbd5e1')); ?>;">
                            #<?php echo $index + 1; ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                        </div>
                        <div style="background: #667eea; color: white; padding: 4px 12px; border-radius: 12px; font-weight: 600; font-size: 14px;">
                            <?php echo $usuario['total']; ?> marcaciones
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #999; padding: 20px;">No hay datos esta semana</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>