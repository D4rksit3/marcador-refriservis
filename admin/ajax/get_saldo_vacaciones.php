<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$usuario_id = (int) ($_GET['usuario_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT vp.*, 
           DATE_FORMAT(vp.periodo_inicio, '%d/%m/%Y') as periodo_inicio_fmt,
           DATE_FORMAT(vp.periodo_fin, '%d/%m/%Y') as periodo_fin_fmt,
           DATE_FORMAT(vp.fecha_vencimiento, '%d/%m/%Y') as fecha_vencimiento_fmt
    FROM vacaciones_periodos vp 
    WHERE vp.usuario_id = ? AND vp.estado = 'vigente' 
    ORDER BY vp.periodo_inicio DESC LIMIT 1
");
$stmt->execute([$usuario_id]);
$saldo = $stmt->fetch();

if ($saldo) {
    echo json_encode([
        'success' => true,
        'saldo' => [
            'periodo_id' => $saldo['id'],
            'dias_correspondientes' => $saldo['dias_correspondientes'],
            'dias_tomados' => $saldo['dias_tomados'],
            'dias_pendientes' => $saldo['dias_pendientes'],
            'periodo_inicio' => $saldo['periodo_inicio_fmt'],
            'periodo_fin' => $saldo['periodo_fin_fmt'],
            'fecha_vencimiento' => $saldo['fecha_vencimiento_fmt']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'saldo' => null]);
}
