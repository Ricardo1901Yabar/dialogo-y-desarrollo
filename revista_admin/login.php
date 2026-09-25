<?php
include 'config/conexion.php';

// Si ya está logueado, lo mandamos directo al panel
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Buscamos al usuario por correo
    $sql = "SELECT id, nombres, ap_paterno, password_hash, rol FROM usuarios WHERE email = :email";
    $stmt = $conexion->prepare($sql);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificamos si existe y si la contraseña escrita coincide con la encriptada
    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        // Guardamos los datos en la sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombres'] . ' ' . $usuario['ap_paterno'];
        $_SESSION['usuario_rol'] = $usuario['rol'];
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Panel - Revista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #d52a2a; font-family: 'Nunito', sans-serif; }
        .card { border-radius: 10px; border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        .btn-primary { background-color: #d52a2a; border-color: #d52a2a; }
        .btn-primary:hover { background-color: #b02020; border-color: #b02020; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="card p-4" style="width: 100%; max-width: 400px;">
        <div class="text-center mb-4">
            <i class="fas fa-newspaper fa-3x text-danger mb-3"></i>
            <h4 class="fw-bold text-dark">Revista Admin</h4>
            <p class="text-muted small">Ingresa tus credenciales para continuar</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger p-2 text-center small"><i class="fas fa-times-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Correo Electrónico</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="ejemplo@revista.com" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Ingresar al Panel</button>
        </form>
    </div>

</body>
</html>