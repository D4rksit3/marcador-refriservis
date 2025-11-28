<?php
require_once '../config.php';
$error = '';
if (isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitize($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($usuario && $password) {
        $stmt = $pdo->prepare("SELECT * FROM administradores WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nombre'] = $admin['nombre_completo'];
            $pdo->prepare("UPDATE administradores SET ultimo_acceso = NOW() WHERE id = ?")->execute([$admin['id']]);
            header('Location: index.php');
            exit;
        }
    }
    $error = 'Usuario o contraseña incorrectos';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 400px; width: 100%; overflow: hidden; }
        .login-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .login-header i { font-size: 4rem; margin-bottom: 15px; }
        .login-body { padding: 30px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header"><i class="fas fa-user-shield"></i><h4><?= SITE_NAME ?></h4></div>
        <div class="login-body">
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <div class="mb-3"><input type="text" name="usuario" class="form-control" placeholder="Usuario" required></div>
                <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Contraseña" required></div>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt me-2"></i>Ingresar</button>
            </form>
        </div>
    </div>
</body>
</html>
