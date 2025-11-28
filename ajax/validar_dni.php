<?php
require_once '../config.php';
header('Content-Type: application/json');

$dni = sanitize($_GET['dni'] ?? '');

if (!preg_match('/^[0-9]{8}$/', $dni)) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, nombres, apellidos, cargo, departamento FROM usuarios WHERE dni = ? AND estado = 'activo'");
$stmt->execute([$dni]);
$usuario = $stmt->fetch();

if ($usuario) {
    echo json_encode([
        'success' => true,
        'id' => $usuario['id'],
        'nombre' => $usuario['nombres'] . ' ' . $usuario['apellidos'],
        'cargo' => $usuario['cargo'],
        'departamento' => $usuario['departamento']
    ]);
} else {
    echo json_encode(['success' => false]);
}
