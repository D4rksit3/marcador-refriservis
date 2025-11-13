<?php
// admin/graficas.php
define('ADMIN_AREA', true);
require_once '../config.php';
requireAuth();

$page_title = 'Gráficas y Estadísticas';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Marcaciones por día (últimos 7 días)
    $stmt = $conn->query("
        SELECT DATE_FORMAT(fecha, '%d/%m') as dia, COUNT(*) as total
        FROM marcaciones
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY fecha
        ORDER BY fecha
    ");
    $marcaciones_dias = $stmt->fetchAll();
    
    // Marcaciones por tipo (últimos 30 días)
    $stmt = $conn->query("
        SELECT tipo_marcacion, COUNT(*) as total
        FROM marcaciones
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY tipo_marcacion
    ");
    $marcaciones_tipo = $stmt->fetchAll();
    
    // Marcaciones por usuario (top 10 del mes)
    $stmt = $conn->query("
        SELECT CONCAT(u.nombres, ' ', u.apellidos) as nombre, COUNT(*) as total
        FROM marcaciones m
        INNER JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY m.usuario_id, nombre
        ORDER BY total DESC
        LIMIT 10
    ");
    $usuarios_top = $stmt->fetchAll();
    
    // Promedio de marcaciones por día de la semana
    $stmt = $conn->query("
        SELECT 
            CASE DAYOFWEEK(fecha)
                WHEN 1 THEN 'Domingo'
                WHEN 2 THEN 'Lunes'
                WHEN 3 THEN 'Martes'
                WHEN 4 THEN 'Miércoles'
                WHEN 5 THEN 'Jueves'
                WHEN 6 THEN 'Viernes'
                WHEN 7 THEN 'Sábado'
            END as dia_semana,
            COUNT(*) as total
        FROM marcaciones
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DAYOFWEEK(fecha), dia_semana
        ORDER BY DAYOFWEEK(fecha)
    ");
    $marcaciones_semana = $stmt->fetchAll();
    
    // Estadísticas por departamento
    $stmt = $conn->query("
        SELECT u.departamento, COUNT(DISTINCT m.usuario_id) as usuarios, COUNT(*) as marcaciones
        FROM marcaciones m
        INNER JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY u.departamento
        ORDER BY marcaciones DESC
    ");
    $stats_departamento = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>📈 Gráficas y Estadísticas</h1>
    <p>Análisis visual de las marcaciones del sistema</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 25px;">
    
    <!-- Gráfica de Marcaciones por Día -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📊 Marcaciones Últimos 7 Días</h2>
        </div>
        <canvas id="chartDias" style="max-height: 300px;"></canvas>
    </div>
    
    <!-- Gráfica de Marcaciones por Tipo -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🎯 Marcaciones por Tipo (Último Mes)</h2>
        </div>
        <canvas id="chartTipos" style="max-height: 300px;"></canvas>
    </div>
    
    <!-- Gráfica de Usuarios Top -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🏆 Usuarios Más Activos (Último Mes)</h2>
        </div>
        <canvas id="chartUsuarios" style="max-height: 300px;"></canvas>
    </div>
    
    <!-- Gráfica por Día de la Semana -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📅 Marcaciones por Día de la Semana</h2>
        </div>
        <canvas id="chartSemana" style="max-height: 300px;"></canvas>
    </div>
    
</div>

<!-- Tabla de Estadísticas por Departamento -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🏢 Estadísticas por Departamento (Último Mes)</h2>
    </div>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Departamento</th>
                    <th>Usuarios Activos</th>
                    <th>Total Marcaciones</th>
                    <th>Promedio por Usuario</th>
                    <th>Representación</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_marcaciones = array_sum(array_column($stats_departamento, 'marcaciones'));
                foreach($stats_departamento as $dept): 
                    $promedio = $dept['usuarios'] > 0 ? round($dept['marcaciones'] / $dept['usuarios'], 1) : 0;
                    $porcentaje = $total_marcaciones > 0 ? round(($dept['marcaciones'] / $total_marcaciones) * 100, 1) : 0;
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($dept['departamento']); ?></strong></td>
                        <td><?php echo $dept['usuarios']; ?></td>
                        <td><?php echo $dept['marcaciones']; ?></td>
                        <td><?php echo $promedio; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; background: #e5e7eb; border-radius: 10px; height: 10px; overflow: hidden;">
                                    <div style="background: #667eea; height: 100%; width: <?php echo $porcentaje; ?>%;"></div>
                                </div>
                                <span style="font-weight: 600; color: #667eea;"><?php echo $porcentaje; ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js desde CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
// Configuración común para todas las gráficas
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.font.size = 12;

// Gráfica de Marcaciones por Día
const chartDias = new Chart(document.getElementById('chartDias'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($marcaciones_dias, 'dia')); ?>,
        datasets: [{
            label: 'Marcaciones',
            data: <?php echo json_encode(array_column($marcaciones_dias, 'total')); ?>,
            backgroundColor: 'rgba(102, 126, 234, 0.2)',
            borderColor: 'rgba(102, 126, 234, 1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: 'rgba(102, 126, 234, 1)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Gráfica de Marcaciones por Tipo
const chartTipos = new Chart(document.getElementById('chartTipos'), {
    type: 'doughnut',
    data: {
        labels: <?php 
            $tipos_labels = array_map(function($item) {
                return formatTipoMarcacion($item['tipo_marcacion']);
            }, $marcaciones_tipo);
            echo json_encode($tipos_labels); 
        ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($marcaciones_tipo, 'total')); ?>,
            backgroundColor: [
                'rgba(16, 185, 129, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(6, 182, 212, 0.8)',
                'rgba(236, 72, 153, 0.8)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfica de Usuarios Top
const chartUsuarios = new Chart(document.getElementById('chartUsuarios'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($usuarios_top, 'nombre')); ?>,
        datasets: [{
            label: 'Marcaciones',
            data: <?php echo json_encode(array_column($usuarios_top, 'total')); ?>,
            backgroundColor: 'rgba(102, 126, 234, 0.8)',
            borderColor: 'rgba(102, 126, 234, 1)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                beginAtZero: true
            }
        }
    }
});

// Gráfica por Día de la Semana
const chartSemana = new Chart(document.getElementById('chartSemana'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($marcaciones_semana, 'dia_semana')); ?>,
        datasets: [{
            label: 'Marcaciones',
            data: <?php echo json_encode(array_column($marcaciones_semana, 'total')); ?>,
            backgroundColor: [
                'rgba(239, 68, 68, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(6, 182, 212, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(249, 115, 22, 0.8)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>