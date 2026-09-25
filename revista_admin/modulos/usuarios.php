<?php
if (file_exists(__DIR__ . '/config/conexion.php')) {
    require_once __DIR__ . '/config/conexion.php';
} else {
    require_once __DIR__ . '/../config/conexion.php';
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$rol_actual = strtolower(trim($_SESSION['usuario_rol'] ?? ''));

if (!in_array($rol_actual, ['administrador', 'admin'])) {
    http_response_code(403);
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            <h2 style='color:#d52a2a;'>Acceso Denegado</h2>
            <p>No tienes permisos para gestionar las cuentas de usuario.</p>
            <a href='index.php'>Volver al Inicio</a>
         </div>");
}

$mensaje_error = '';

// 1. Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id_borrar = intval($_GET['eliminar']);

    if ($id_borrar === intval($_SESSION['usuario_id'])) {
        $mensaje_error = "No puedes eliminar tu propia cuenta de usuario mientras tengas la sesión activa.";
    } else {
        try {
            $sql_delete = "DELETE FROM usuarios WHERE id = :id";
            $stmt = $conexion->prepare($sql_delete);
            $stmt->bindValue(':id', $id_borrar, PDO::PARAM_INT);
            $stmt->execute();
            header("Location: usuarios.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $mensaje_error = "No se puede eliminar este usuario porque ya tiene reportajes o noticias publicadas.";
            } else {
                $mensaje_error = "Error al eliminar: " . $e->getMessage();
            }
        }
    }
}

// 2. Registrar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = trim($_POST['nombres'] ?? '');
    $ap_paterno = trim($_POST['ap_paterno'] ?? '');
    $ap_materno = trim($_POST['ap_materno'] ?? '') ?: null;
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = trim($_POST['rol'] ?? 'redactor');

    if (!empty($nombres) && !empty($ap_paterno) && !empty($email) && !empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $sql_insert = "INSERT INTO usuarios (nombres, ap_paterno, ap_materno, email, password_hash, rol) 
                           VALUES (:nombres, :ap_paterno, :ap_materno, :email, :password_hash, :rol)";
            $stmt = $conexion->prepare($sql_insert);
            $stmt->bindValue(':nombres', $nombres, PDO::PARAM_STR);
            $stmt->bindValue(':ap_paterno', $ap_paterno, PDO::PARAM_STR);
            $stmt->bindValue(':ap_materno', $ap_materno, $ap_materno ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
            $stmt->bindValue(':rol', $rol, PDO::PARAM_STR);
            $stmt->execute();

            header("Location: usuarios.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $mensaje_error = "El correo electrónico ya se encuentra registrado por otro usuario.";
            } else {
                $mensaje_error = "Error al registrar el usuario: " . $e->getMessage();
            }
        }
    } else {
        $mensaje_error = "Por favor, completa todos los campos requeridos (*).";
    }
}

// 3. Consultar usuarios
$sql = "SELECT id, nombres, ap_paterno, ap_materno, email, rol, created_at FROM usuarios ORDER BY id DESC";
$resultado = $conexion->query($sql);

if (file_exists(__DIR__ . '/includes/header.php')) {
    require_once __DIR__ . '/includes/header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}
?>

<h1 class="h3 mb-3 text-gray-800">Gestión de Usuarios</h1>
<p class="text-muted mb-4">Administra los accesos al panel. Recuerda que todo el contenido publicado estará asociado al usuario que lo redacta.</p>

<?php if (!empty($mensaje_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($mensaje_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-header py-3 bg-white">
        <h6 class="m-0 text-primary fw-bold"><i class="fas fa-user-plus me-1"></i> Registrar Nuevo Usuario</h6>
    </div>
    <div class="card-body">
        <form action="usuarios.php" method="POST" class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Nombres *</label>
                <input type="text" name="nombres" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Apellido Paterno *</label>
                <input type="text" name="ap_paterno" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Apellido Materno</label>
                <input type="text" name="ap_materno" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Correo Electrónico *</label>
                <input type="email" name="email" class="form-control" placeholder="ejemplo@revista.com" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Contraseña *</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Nivel de Acceso (Rol) *</label>
                <select name="rol" class="form-select" required>
                    <option value="redactor" selected>Redactor (Solo publica)</option>
                    <option value="editor">Editor (Revisa contenido)</option>
                    <option value="admin">Administrador (Acceso total)</option>
                </select>
            </div>
            <div class="col-12 text-end mt-3">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-header py-3 bg-white">
        <h6 class="m-0 text-primary fw-bold"><i class="fas fa-users me-1"></i> Personal Registrado</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Fecha de Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($fila = $resultado->fetch(PDO::FETCH_ASSOC)) { 
                        $es_propio_usuario = ($fila['id'] == $_SESSION['usuario_id']);
                        $rol_fila = strtolower($fila['rol']);
                    ?>
                        <tr>
                            <td class="fw-bold text-muted"><?php echo $fila['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars(trim($fila['nombres'] . ' ' . $fila['ap_paterno'] . ' ' . ($fila['ap_materno'] ?? ''))); ?>
                                <?php if ($es_propio_usuario): ?>
                                    <span class="badge bg-secondary ms-1">Tú</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($fila['email']); ?></td>
                            <td>
                                <?php 
                                    if (in_array($rol_fila, ['admin', 'administrador'])) {
                                        echo '<span class="badge bg-danger">Administrador</span>';
                                    } elseif ($rol_fila === 'editor') {
                                        echo '<span class="badge bg-warning text-dark">Editor</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Redactor</span>';
                                    }
                                ?>
                            </td>
                            <td class="text-muted small"><?php echo date("d/m/Y", strtotime($fila['created_at'])); ?></td>
                            <td>
                                <?php if (!$es_propio_usuario): ?>
                                    <a href="usuarios.php?eliminar=<?php echo $fila['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Confirmas que deseas eliminar este usuario del sistema?');" title="Eliminar Usuario">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small" title="No puedes eliminar tu propia cuenta"><i class="fas fa-lock"></i> Activo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
if (file_exists(__DIR__ . '/includes/footer.php')) {
    require_once __DIR__ . '/includes/footer.php';
} else {
    require_once __DIR__ . '/../includes/footer.php';
}
?>