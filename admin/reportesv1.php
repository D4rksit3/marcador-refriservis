<?php
// admin/reportes.php
define('ADMIN_AREA', true);
require_once '../config.php';
requireAuth();

$page_title = 'Reportes de Asistencia';

// Exportar datos
if (isset($_GET['exportar'])) {
    $tipo = $_GET['exportar'];
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Aplicar filtros
    $buscar_dni = isset($_GET['dni']) ? sanitize($_GET['dni']) : '';
    $buscar_nombre = isset($_GET['nombre']) ? sanitize($_GET['nombre']) : '';
    $fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize($_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? sanitize($_GET['fecha_fin']) : '';
    
    $sql = "SELECT * FROM v_marcaciones_diarias WHERE 1=1";
    $params = [];
    
    if (!empty($buscar_dni)) {
        $sql .= " AND dni LIKE ?";
        $params[] = "%{$buscar_dni}%";
    }
    if (!empty($buscar_nombre)) {
        $sql .= " AND nombre_completo LIKE ?";
        $params[] = "%{$buscar_nombre}%";
    }
    if (!empty($fecha_inicio)) {
        $sql .= " AND fecha >= ?";
        $params[] = $fecha_inicio;
    }
    if (!empty($fecha_fin)) {
        $sql .= " AND fecha <= ?";
        $params[] = $fecha_fin;
    }
    
    $sql .= " ORDER BY fecha DESC, nombre_completo";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll();
    
    if ($tipo === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="reporte_asistencia_' . date('Y-m-d_His') . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        echo '<x:Name>Reporte Asistencia</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions>';
        echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml></head><body>';
        echo '<table border="1">';
        echo '<thead><tr style="background-color: #667eea; color: white; font-weight: bold;">';
        echo '<th>DNI</th><th>Empleado</th><th>Cargo</th><th>Departamento</th><th>Fecha</th>';
        echo '<th>Horario</th><th>Entrada</th><th>Ubicación Entrada</th>';
        echo '<th>Tardanza</th><th>Salida Refrigerio</th><th>Entrada Refrigerio</th>';
        echo '<th>Entrada Campo</th><th>Salida Campo</th>';
        echo '<th>Salida</th><th>Horas Extras</th><th>Ubicación Salida</th>';
        echo '</tr></thead><tbody>';
        
        foreach($datos as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['dni']) . '</td>';
            echo '<td>' . htmlspecialchars($row['nombre_completo']) . '</td>';
            echo '<td>' . htmlspecialchars($row['cargo']) . '</td>';
            echo '<td>' . htmlspecialchars($row['departamento']) . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($row['fecha'])) . '</td>';
            echo '<td>' . date('H:i', strtotime($row['horario_entrada'])) . ' - ' . date('H:i', strtotime($row['horario_salida'])) . '</td>';
            echo '<td>' . ($row['entrada'] ? date('h:i A', strtotime($row['entrada'])) : '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['direccion_entrada'] ?? '-') . '</td>';
            
            // Tardanza
            $tardanza = intval($row['minutos_tardanza']);
            if ($tardanza > 0) {
                $horas = floor($tardanza / 60);
                $mins = $tardanza % 60;
                $tardanza_texto = ($horas > 0 ? $horas . 'h ' : '') . $mins . 'm';
                echo '<td style="color: #ef4444; font-weight: bold;">' . $tardanza_texto . '</td>';
            } else {
                echo '<td style="color: #10b981;">A tiempo</td>';
            }
            
            echo '<td>' . ($row['salida_refrigerio'] ? date('h:i A', strtotime($row['salida_refrigerio'])) : '-') . '</td>';
            echo '<td>' . ($row['entrada_refrigerio'] ? date('h:i A', strtotime($row['entrada_refrigerio'])) : '-') . '</td>';
            echo '<td>' . ($row['entrada_campo'] ? date('h:i A', strtotime($row['entrada_campo'])) : '-') . '</td>';
            echo '<td>' . ($row['salida_campo'] ? date('h:i A', strtotime($row['salida_campo'])) : '-') . '</td>';
            echo '<td>' . ($row['salida'] ? date('h:i A', strtotime($row['salida'])) : '-') . '</td>';
            
            // Horas extras
            $extras = intval($row['minutos_extras']);
            if ($extras > 0) {
                $horas = floor($extras / 60);
                $mins = $extras % 60;
                $extras_texto = ($horas > 0 ? $horas . 'h ' : '') . $mins . 'm';
                echo '<td style="color: #8b5cf6; font-weight: bold;">' . $extras_texto . '</td>';
            } else {
                echo '<td>-</td>';
            }
            
            echo '<td>' . htmlspecialchars($row['direccion_salida'] ?? '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table></body></html>';
        exit();
    }
    elseif ($tipo === 'pdf') {
        // Generar PDF simple sin librerías externas
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="reporte_asistencia_' . date('Y-m-d_His') . '.pdf"');
        
        // PDF básico (nota: para producción se recomienda usar TCPDF o similar)
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>body{font-family:Arial;font-size:12px;}table{width:100%;border-collapse:collapse;}';
        $html .= 'th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background:#667eea;color:white;}</style>';
        $html .= '</head><body><h2>Reporte de Asistencia - ' . date('d/m/Y') . '</h2><table><thead><tr>';
        $html .= '<th>DNI</th><th>Empleado</th><th>Fecha</th><th>Entrada</th><th>Salida</th></tr></thead><tbody>';
        
        foreach($datos as $row) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['dni']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['nombre_completo']) . '</td>';
            $html .= '<td>' . date('d/m/Y', strtotime($row['fecha'])) . '</td>';
            $html .= '<td>' . ($row['entrada'] ? date('h:i A', strtotime($row['entrada'])) : '-') . '</td>';
            $html .= '<td>' . ($row['salida'] ? date('h:i A', strtotime($row['salida'])) : '-') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table><p style="margin-top:20px;font-size:10px;color:#666;">Generado el ' . date('d/m/Y H:i:s') . '</p></body></html>';
        
        // Convertir a PDF usando wkhtmltopdf si está disponible, sino HTML plano
        echo $html;
        exit();
    }
}

// Obtener reportes
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $buscar_dni = isset($_GET['dni']) ? sanitize($_GET['dni']) : '';
    $buscar_nombre = isset($_GET['nombre']) ? sanitize($_GET['nombre']) : '';
    $fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize($_GET['fecha_inicio']) : date('Y-m-01');
    $fecha_fin = isset($_GET['fecha_fin']) ? sanitize($_GET['fecha_fin']) : date('Y-m-d');
    
    $sql = "SELECT * FROM v_marcaciones_diarias WHERE 1=1";
    $params = [];
    
    if (!empty($buscar_dni)) {
        $sql .= " AND dni LIKE ?";
        $params[] = "%{$buscar_dni}%";
    }
    if (!empty($buscar_nombre)) {
        $sql .= " AND nombre_completo LIKE ?";
        $params[] = "%{$buscar_nombre}%";
    }
    if (!empty($fecha_inicio)) {
        $sql .= " AND fecha >= ?";
        $params[] = $fecha_inicio;
    }
    if (!empty($fecha_fin)) {
        $sql .= " AND fecha <= ?";
        $params[] = $fecha_fin;
    }
    
    $sql .= " ORDER BY fecha DESC, nombre_completo";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $reportes = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>📋 Reportes de Asistencia</h1>
    <p>Consulta detallada de todas las marcaciones</p>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Filtros de Búsqueda</h2>
        <div style="display: flex; gap: 10px;">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['exportar' => 'excel'])); ?>" class="btn btn-success btn-sm">
                📊 Exportar Excel
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['exportar' => 'pdf'])); ?>" class="btn btn-danger btn-sm">
                📄 Exportar PDF
            </a>
        </div>
    </div>
    
    <form method="GET">
        <div class="form-row">
            <div class="form-group">
                <label for="dni">DNI:</label>
                <input type="text" name="dni" id="dni" class="form-control" placeholder="Buscar por DNI" value="<?php echo htmlspecialchars($buscar_dni); ?>">
            </div>
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Buscar por nombre" value="<?php echo htmlspecialchars($buscar_nombre); ?>">
            </div>
            <div class="form-group">
                <label for="fecha_inicio">Fecha Inicio:</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
            </div>
            <div class="form-group">
                <label for="fecha_fin">Fecha Fin:</label>
                <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
            </div>
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary">🔍 Buscar</button>
            <a href="reportes.php" class="btn btn-warning">🔄 Limpiar Filtros</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Resultados (<?php echo count($reportes); ?> registros)</h2>
    </div>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>DNI</th>
                    <th>Empleado</th>
                    <th>Cargo</th>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th>Entrada</th>
                    <th>Tardanza</th>
                    <th>Sal. Refri.</th>
                    <th>Ent. Refri.</th>
                    <th>Ent. Campo</th>
                    <th>Sal. Campo</th>
                    <th>Salida</th>
                    <th>H. Extras</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reportes) > 0): ?>
                    <?php foreach($reportes as $reporte): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($reporte['dni']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($reporte['nombre_completo']); ?><br>
                                <small style="color: #666;"><?php echo htmlspecialchars($reporte['departamento']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($reporte['cargo']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($reporte['fecha'])); ?></td>
                            <td>
                                <small style="color: #666;">
                                    <?php echo date('H:i', strtotime($reporte['horario_entrada'])); ?> - 
                                    <?php echo date('H:i', strtotime($reporte['horario_salida'])); ?>
                                </small>
                            </td>
                            <td><?php echo $reporte['entrada'] ? '<span style="color: #10b981;">' . date('h:i A', strtotime($reporte['entrada'])) . '</span>' : '-'; ?></td>
                            <td>
                                <?php 
                                $tardanza = intval($reporte['minutos_tardanza']);
                                if ($tardanza > 0) {
                                    $horas = floor($tardanza / 60);
                                    $mins = $tardanza % 60;
                                    $tardanza_texto = ($horas > 0 ? $horas . 'h ' : '') . $mins . 'm';
                                    echo '<span style="color: #ef4444; font-weight: 600;">⏱️ ' . $tardanza_texto . '</span>';
                                } else {
                                    echo '<span style="color: #10b981;">✓ A tiempo</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo $reporte['salida_refrigerio'] ? date('h:i A', strtotime($reporte['salida_refrigerio'])) : '-'; ?></td>
                            <td><?php echo $reporte['entrada_refrigerio'] ? date('h:i A', strtotime($reporte['entrada_refrigerio'])) : '-'; ?></td>
                            <td><?php echo $reporte['entrada_campo'] ? date('h:i A', strtotime($reporte['entrada_campo'])) : '-'; ?></td>
                            <td><?php echo $reporte['salida_campo'] ? date('h:i A', strtotime($reporte['salida_campo'])) : '-'; ?></td>
                            <td><?php echo $reporte['salida'] ? '<span style="color: #ef4444;">' . date('h:i A', strtotime($reporte['salida'])) . '</span>' : '-'; ?></td>
                            <td>
                                <?php 
                                $extras = intval($reporte['minutos_extras']);
                                if ($extras > 0) {
                                    $horas = floor($extras / 60);
                                    $mins = $extras % 60;
                                    $extras_texto = ($horas > 0 ? $horas . 'h ' : '') . $mins . 'm';
                                    echo '<span style="color: #8b5cf6; font-weight: 600;">⭐ ' . $extras_texto . '</span>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick='verDetalle(<?php echo json_encode($reporte, JSON_UNESCAPED_UNICODE); ?>)'>
                                    👁️ Ver
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" style="text-align: center; color: #999; padding: 30px;">
                            No se encontraron registros con los filtros aplicados
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detalle -->
<div id="modalDetalle" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2>📋 Detalle de Marcaciones</h2>
            <span class="close-modal" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body" id="detalleContenido">
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="cerrarModal()">Cerrar</button>
        </div>
    </div>
</div>

<script>
function verDetalle(reporte) {
    let html = '<div style="display: grid; gap: 15px;">';
    
    html += '<div style="background: #f3f4f6; padding: 15px; border-radius: 8px;">';
    html += '<h3 style="margin-bottom: 10px; color: #333;">Información del Empleado</h3>';
    html += '<p><strong>DNI:</strong> ' + reporte.dni + '</p>';
    html += '<p><strong>Nombre:</strong> ' + reporte.nombre_completo + '</p>';
    html += '<p><strong>Cargo:</strong> ' + reporte.cargo + '</p>';
    html += '<p><strong>Departamento:</strong> ' + reporte.departamento + '</p>';
    html += '<p><strong>Fecha:</strong> ' + new Date(reporte.fecha).toLocaleDateString('es-PE') + '</p>';
    html += '<p><strong>Horario:</strong> ' + reporte.horario_entrada.substring(0,5) + ' - ' + reporte.horario_salida.substring(0,5) + '</p>';
    html += '<p><strong>Tolerancia:</strong> ' + reporte.tolerancia_minutos + ' minutos</p>';
    html += '</div>';
    
    html += '<div style="background: #d1fae5; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981;">';
    html += '<h3 style="margin-bottom: 10px; color: #059669;">🟢 Entrada</h3>';
    html += '<p><strong>Hora:</strong> ' + (reporte.entrada || 'No registrada') + '</p>';
    
    // Mostrar tardanza
    let tardanza = parseInt(reporte.minutos_tardanza);
    if (tardanza > 0) {
        let horas = Math.floor(tardanza / 60);
        let mins = tardanza % 60;
        let tardanza_texto = (horas > 0 ? horas + 'h ' : '') + mins + 'm';
        html += '<p style="color: #ef4444; font-weight: bold;"><strong>⏱️ Tardanza:</strong> ' + tardanza_texto + '</p>';
    } else {
        html += '<p style="color: #10b981; font-weight: bold;"><strong>✓ Estado:</strong> A tiempo</p>';
    }
    
    if (reporte.direccion_entrada) {
        html += '<p><strong>Ubicación:</strong> ' + reporte.direccion_entrada + '</p>';
    }
    if (reporte.ubicacion_entrada) {
        html += '<p><strong>Coordenadas:</strong> ' + reporte.ubicacion_entrada + '</p>';
        html += '<p><a href="https://www.google.com/maps?q=' + reporte.ubicacion_entrada + '" target="_blank" class="btn btn-info btn-sm">Ver en Mapa</a></p>';
    }
    html += '</div>';
    
    html += '<div style="background: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b;">';
    html += '<h3 style="margin-bottom: 10px; color: #d97706;">☕ Refrigerio</h3>';
    html += '<p><strong>Salida:</strong> ' + (reporte.salida_refrigerio || '-') + '</p>';
    if (reporte.direccion_salida_refrigerio) {
        html += '<p><strong>Ubicación Salida:</strong> ' + reporte.direccion_salida_refrigerio + '</p>';
    }
    if (reporte.ubicacion_salida_refrigerio) {
        html += '<p><strong>Coordenadas:</strong> ' + reporte.ubicacion_salida_refrigerio + '</p>';
        html += '<p><a href="https://www.google.com/maps?q=' + reporte.ubicacion_salida_refrigerio + '" target="_blank" class="btn btn-info btn-sm">Ver en Mapa</a></p>';
    }
    html += '<hr style="margin: 10px 0; border: none; border-top: 1px solid #fbbf24;">';
    html += '<p><strong>Entrada:</strong> ' + (reporte.entrada_refrigerio || '-') + '</p>';
    if (reporte.direccion_entrada_refrigerio) {
        html += '<p><strong>Ubicación Entrada:</strong> ' + reporte.direccion_entrada_refrigerio + '</p>';
    }
    if (reporte.ubicacion_entrada_refrigerio) {
        html += '<p><strong>Coordenadas:</strong> ' + reporte.ubicacion_entrada_refrigerio + '</p>';
        html += '<p><a href="https://www.google.com/maps?q=' + reporte.ubicacion_entrada_refrigerio + '" target="_blank" class="btn btn-info btn-sm">Ver en Mapa</a></p>';
    }
    html += '</div>';
    
    if (reporte.entrada_campo || reporte.salida_campo) {
        html += '<div style="background: #dbeafe; padding: 15px; border-radius: 8px; border-left: 4px solid #06b6d4;">';
        html += '<h3 style="margin-bottom: 10px; color: #0891b2;">🚗 Campo</h3>';
        html += '<p><strong>Entrada:</strong> ' + (reporte.entrada_campo || '-') + '</p>';
        if (reporte.direccion_entrada_campo) {
            html += '<p><strong>Ubicación:</strong> ' + reporte.direccion_entrada_campo + '</p>';
        }
        if (reporte.ubicacion_entrada_campo) {
            html += '<p><strong>Coordenadas:</strong> ' + reporte.ubicacion_entrada_campo + '</p>';
            html += '<p><a href="https://www.google.com/maps?q=' + reporte.ubicacion_entrada_campo + '" target="_blank" class="btn btn-info btn-sm">Ver en Mapa</a></p>';
        }
        html += '<hr style="margin: 10px 0; border: none; border-top: 1px solid #0ea5e9;">';
        html += '<p><strong>Salida:</strong> ' + (reporte.salida_campo || '-') + '</p>';
        if (reporte.direccion_salida_campo) {
            html += '<p><strong>Ubicación:</strong> ' + reporte.direccion_salida_campo + '</p>';
        }
        if (reporte.ubicacion_salida_campo) {
            html += '<p><strong>Coordenadas:</strong> ' + reporte.ubicacion_salida_campo + '</p>';
            html += '<p><a href="https://www.google.com/maps?q=' + reporte.ubicacion_salida_campo + '" target="_blank" class="btn btn-info btn-sm">Ver en Mapa</a></p>';
        }
        html += '</div>';
    }
    
    html += '<div style="background: #fee2e2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444;">';
    html += '<h3 style="margin-bottom: 10px; color: #dc2626;">🔴 Salida</h3>';
    html += '<p><strong>Hora:</strong> ' + (reporte.salida || 'No registrada') + '</p>';
    
    // Mostrar horas extras
    let extras = parseInt(reporte.minutos_extras);
    if (extras > 0) {
        let horas = Math.floor(extras / 60);
        let mins = extras % 60;
        let extras_texto = (horas > 0 ? horas + 'h ' : '') + mins + 'm';
        html += '<p style="color: #8b5cf6; font-weight: bold;"><strong>⭐ Horas Extras:</strong> ' + extras_texto + '</p>';
    }
    
    if (reporte.direccion_salida) {
        html += '<p><strong>Ubicación:</strong> ' + reporte.direccion_salida + '</p>';
    }
    if (reporte.ubicacion_salida) {
        html += '<p><strong>Coordenadas:</strong> ' + reporte.ubicacion_salida + '</p>';
        html += '<p><a href="https://www.google.com/maps?q=' + reporte.ubicacion_salida + '" target="_blank" class="btn btn-info btn-sm">Ver en Mapa</a></p>';
    }
    html += '</div>';
    
    html += '</div>';
    
    document.getElementById('detalleContenido').innerHTML = html;
    document.getElementById('modalDetalle').style.display = 'block';
}

function cerrarModal() {
    document.getElementById('modalDetalle').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('modalDetalle');
    if (event.target == modal) {
        cerrarModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
