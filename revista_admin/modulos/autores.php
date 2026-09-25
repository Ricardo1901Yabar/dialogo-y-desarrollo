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
$es_admin = in_array($rol_actual, ['admin', 'administrador']);
$es_editor = ($rol_actual === 'editor');

if (!$es_admin && !$es_editor) {
    http_response_code(403);
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            <h2 style='color:#d52a2a;'>Acceso Denegado</h2>
            <p>Tu rol actual (<strong>" . htmlspecialchars($rol_actual) . "</strong>) no tiene permisos para gestionar autores.</p>
            <a href='index.php'>Volver al Inicio</a>
         </div>");
}

$mensaje_error = '';

// 1. Eliminar autor (Solo Admin)
if (isset($_GET['eliminar'])) {
    if (!$es_admin) {
        $mensaje_error = "Solo un usuario con rol Administrador tiene autorización para eliminar autores.";
    } else {
        $id_borrar = intval($_GET['eliminar']);
        try {
            $sql_delete = "DELETE FROM autores WHERE id = :id";
            $stmt = $conexion->prepare($sql_delete);
            $stmt->bindValue(':id', $id_borrar, PDO::PARAM_INT);
            $stmt->execute();
            header("Location: autores.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $mensaje_error = "No se puede eliminar este autor porque ya tiene reportajes asignados en el sistema.";
            } else {
                $mensaje_error = "Error al eliminar: " . $e->getMessage();
            }
        }
    }
}

// 2. Registrar autor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = trim($_POST['nombres'] ?? '');
    $ap_paterno = trim($_POST['ap_paterno'] ?? '') ?: null;
    $ap_materno = trim($_POST['ap_materno'] ?? '') ?: null;
    $nickname = trim($_POST['nickname'] ?? '') ?: null;
    $es_nickname = isset($_POST['es_nickname']) ? 1 : 0;

    if (!empty($nombres)) {
        $sql_insert = "INSERT INTO autores (nombres, ap_paterno, ap_materno, nickname, es_nickname) 
                       VALUES (:nombres, :ap_paterno, :ap_materno, :nickname, :es_nickname)";
        $stmt = $conexion->prepare($sql_insert);
        $stmt->bindValue(':nombres', $nombres, PDO::PARAM_STR);
        $stmt->bindValue(':ap_paterno', $ap_paterno, $ap_paterno ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':ap_materno', $ap_materno, $ap_materno ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':nickname', $nickname, $nickname ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':es_nickname', $es_nickname, PDO::PARAM_INT);
        $stmt->execute();
        
        header("Location: autores.php");
        exit;
    } else {
        $mensaje_error = "El campo 'Nombres' es obligatorio.";
    }
}

// 3. Consultar autores
$sql = "SELECT id, nombres, ap_paterno, ap_materno, nickname, es_nickname FROM autores ORDER BY id DESC";
$resultado = $conexion->query($sql);

if (file_exists(__DIR__ . '/includes/header.php')) {
    require_once __DIR__ . '/includes/header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
}
?>

<h1 class="h3 mb-3 text-gray-800">Gestión de Autores y Colaboradores</h1>
<p class="text-muted mb-4">Registra a las personas que redactan los reportajes. Pueden usar su nombre real o un seudónimo (nickname).</p>

<?php if (!empty($mensaje_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($mensaje_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-header py-3 bg-white">
        <h6 class="m-0 text-primary fw-bold"><i class="fas fa-user-edit me-1"></i> Registrar Nuevo Autor</h6>
    </div>
    <div class="card-body">
        <form action="autores.php" method="POST" class="row g-3 align-items-center">
            <div class="col-md-3">
                <label class="form-label fw-bold">Nombres *</label>
                <input type="text" name="nombres" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Apellido Paterno</label>
                <input type="text" name="ap_paterno" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Apellido Materno</label>
                <input type="text" name="ap_materno" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Nickname / Seudónimo</label>
                <input type="text" name="nickname" class="form-control" placeholder="Ej: El Cronista">
            </div>
            <div class="col-md-6 mt-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="es_nickname" id="esNickname">
                    <label class="form-check-label fw-bold text-muted" for="esNickname">
                        ¿Utiliza el nickname públicamente en lugar de su nombre real?
                    </label>
                </div>
            </div>
            <div class="col-md-6 text-end mt-4">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Guardar Autor</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-header py-3 bg-white">
        <h6 class="m-0 text-primary fw-bold"><i class="fas fa-list me-1"></i> Autores Registrados</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Nickname</th>
                        <th>Firma Pública</th>
                        <?php if ($es_admin): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while($fila = $resultado->fetch(PDO::FETCH_ASSOC)) { ?>
                        <tr>
                            <td class="fw-bold text-muted"><?php echo $fila['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars(trim($fila['nombres'] . ' ' . ($fila['ap_paterno'] ?? '') . ' ' . ($fila['ap_materno'] ?? ''))); ?>
                            </td>
                            <td>
                                <?php echo !empty($fila['nickname']) ? htmlspecialchars($fila['nickname']) : '<span class="text-muted small">N/A</span>'; ?>
                            </td>
                            <td>
                                <?php if ($fila['es_nickname']) { ?>
                                    <span class="badge bg-info text-dark"><i class="fas fa-user-tag me-1"></i> Usa Nickname</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary"><i class="fas fa-id-card me-1"></i> Nombre Real</span>
                                <?php } ?>
                            </td>
                            <?php if ($es_admin): ?>
                                <td>
                                    <a href="autores.php?eliminar=<?php echo $fila['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Confirmas que deseas eliminar a este autor?');" title="Eliminar Autor">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            <?php endif; ?>
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