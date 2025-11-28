<?php
require_once '../../config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { echo json_encode(['success' => false]); exit; }

$justificacion_id = (int) ($_GET['justificacion_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM justificacion_documentos WHERE justificacion_id = ?");
$stmt->execute([$justificacion_id]); $docs = $stmt->fetchAll();

if (empty($docs)) { echo json_encode(['success' => false, 'html' => '<p class="text-muted">No hay documentos</p>']); exit; }

$html = '<div class="row">';
foreach ($docs as $d) {
    $es_imagen = strpos($d['tipo_archivo'], 'image') !== false;
    $tamano = round($d['tamano'] / 1024, 2) . ' KB';
    $html .= '<div class="col-md-6 mb-3"><div class="card">';
    if ($es_imagen) { $html .= '<a href="../' . htmlspecialchars($d['ruta']) . '" target="_blank"><img src="../' . htmlspecialchars($d['ruta']) . '" class="card-img-top" style="max-height:200px;object-fit:cover;"></a>'; }
    else { $html .= '<div class="card-body text-center py-4"><i class="fas fa-file-pdf fa-4x text-danger"></i></div>'; }
    $html .= '<div class="card-footer"><small class="text-muted d-block">' . htmlspecialchars($d['nombre_original']) . '</small><small class="text-muted">' . $tamano . '</small><a href="../' . htmlspecialchars($d['ruta']) . '" target="_blank" class="btn btn-sm btn-primary float-end"><i class="fas fa-download"></i></a></div></div></div>';
}
$html .= '</div>';
echo json_encode(['success' => true, 'html' => $html]);
