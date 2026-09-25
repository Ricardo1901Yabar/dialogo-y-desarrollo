<?php
include '../config/conexion.php';

$mensaje_error = '';

// Eliminar noticia
if (isset($_GET['eliminar'])) {
    try {
        $stmt = $conexion->prepare("DELETE FROM noticias WHERE id = :id");
        $stmt->execute(['id' => $_GET['eliminar']]);
        header("Location: noticias.php");
        exit;
    } catch (PDOException $e) {
        $mensaje_error = "Error al eliminar: " . $e->getMessage();
    }
}

// Cargar datos para edición si viene el parámetro ?editar=ID
$noticia_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $conexion->prepare("SELECT * FROM noticias WHERE id = :id");
    $stmt->execute(['id' => $_GET['editar']]);
    $noticia_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Procesar formulario (Crear o Actualizar)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $titulo = trim($_POST['titulo']);
    $link_externo = trim($_POST['link_externo']) ?: null;
    $fecha_publicacion = $_POST['fecha_publicacion'];
    $usuario_id = $_POST['usuario_id'];
    
    // Mantener la foto anterior si se está editando
    $foto_final = isset($_POST['foto_actual']) ? $_POST['foto_actual'] : null;
    
    // Si escribió una URL nueva
    if (!empty(trim($_POST['foto_url']))) {
        $foto_final = trim($_POST['foto_url']);
    }

    // Si subió un archivo desde su equipo
    if (isset($_FILES['foto_upload']) && $_FILES['foto_upload']['error'] == UPLOAD_ERR_OK) {
        $dir = '../uploads/imagenes/';
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $ext = pathinfo($_FILES['foto_upload']['name'], PATHINFO_EXTENSION);
        $nombre = time() . '_noticia_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['foto_upload']['tmp_name'], $dir . $nombre)) {
            $foto_final = 'uploads/imagenes/' . $nombre;
        }
    }

    if (!empty($titulo) && !empty($fecha_publicacion) && !empty($usuario_id)) {
        if ($id > 0) {
            // Actualizar noticia existente
            $sql = "UPDATE noticias 
                    SET titulo = :titulo, foto = :foto, link_externo = :link_externo, 
                        fecha_publicacion = :fecha_publicacion, usuario_id = :usuario_id 
                    WHERE id = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                'titulo' => $titulo, 'foto' => $foto_final, 
                'link_externo' => $link_externo, 'fecha_publicacion' => $fecha_publicacion, 
                'usuario_id' => $usuario_id, 'id' => $id
            ]);
        } else {
            // Insertar nueva noticia
            $sql = "INSERT INTO noticias (titulo, foto, link_externo, fecha_publicacion, usuario_id) 
                    VALUES (:titulo, :foto, :link_externo, :fecha_publicacion, :usuario_id)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                'titulo' => $titulo, 'foto' => $foto_final, 
                'link_externo' => $link_externo, 'fecha_publicacion' => $fecha_publicacion, 
                'usuario_id' => $usuario_id
            ]);
        }
        header("Location: noticias.php");
        exit;
    }
}

// Filtro: Solo usuarios con rol 'redactor' o 'editor'
$usuarios = $conexion->query("SELECT id, nombres, ap_paterno FROM usuarios WHERE LOWER(rol) IN ('redactor', 'editor') ORDER BY nombres ASC");
$resultado = $conexion->query("SELECT n.*, CONCAT(u.nombres, ' ', u.ap_paterno) AS usuario FROM noticias n INNER JOIN usuarios u ON n.usuario_id = u.id ORDER BY n.id DESC");

include '../includes/header.php'; 
?>

<h1 class="h3 mb-3 text-gray-800">Gestión de Noticias</h1>
<?php if ($mensaje_error): ?><div class="alert alert-danger"><?php echo $mensaje_error; ?></div><?php endif; ?>

<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 text-primary">
            <i class="fas fa-<?php echo $noticia_editar ? 'edit' : 'bolt'; ?>"></i> 
            <?php echo $noticia_editar ? 'Editar Noticia' : 'Registrar Noticia'; ?>
        </h6>
        <?php if ($noticia_editar): ?>
            <a href="noticias.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Cancelar Edición</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form action="noticias.php" method="POST" enctype="multipart/form-data" class="row g-3">
            <?php if ($noticia_editar): ?>
                <input type="hidden" name="id" value="<?php echo $noticia_editar['id']; ?>">
                <input type="hidden" name="foto_actual" value="<?php echo htmlspecialchars($noticia_editar['foto'] ?? ''); ?>">
            <?php endif; ?>

            <div class="col-md-8">
                <label class="form-label fw-bold">Título *</label>
                <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($noticia_editar['titulo'] ?? ''); ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Fecha *</label>
                <input type="date" name="fecha_publicacion" class="form-control" value="<?php echo htmlspecialchars($noticia_editar['fecha_publicacion'] ?? ''); ?>" required>
            </div>
            
            <div class="col-md-6 border-end">
                <label class="form-label fw-bold text-primary"><i class="fas fa-upload"></i> Subir Foto (PC)</label>
                <input type="file" name="foto_upload" class="form-control" accept="image/*">
                <?php if ($noticia_editar && !empty($noticia_editar['foto'])): ?>
                    <small class="text-muted d-block mt-1">Foto actual: <strong><?php echo htmlspecialchars($noticia_editar['foto']); ?></strong></small>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold text-muted"><i class="fas fa-link"></i> O usar URL de Foto</label>
                <input type="text" name="foto_url" class="form-control" placeholder="Ej: https://..." value="<?php echo ($noticia_editar && filter_var($noticia_editar['foto'], FILTER_VALIDATE_URL)) ? htmlspecialchars($noticia_editar['foto']) : ''; ?>">
            </div>

            <div class="col-md-6 mt-3">
                <label class="form-label fw-bold">Enlace de la Noticia</label>
                <input type="url" name="link_externo" class="form-control" value="<?php echo htmlspecialchars($noticia_editar['link_externo'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mt-3">
                <label class="form-label fw-bold">Publicado por (Usuario) *</label>
                <select name="usuario_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php 
                    $selected_user = $noticia_editar['usuario_id'] ?? '';
                    while($u = $usuarios->fetch(PDO::FETCH_ASSOC)) {
                        $sel = ($u['id'] == $selected_user) ? 'selected' : '';
                        echo "<option value='{$u['id']}' {$sel}>{$u['nombres']} {$u['ap_paterno']}</option>";
                    } 
                    ?>
                </select>
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $noticia_editar ? 'Actualizar Noticia' : 'Guardar'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th>Foto</th>
                    <th>Título</th>
                    <th>Enlace</th>
                    <th>Publicado por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($f = $resultado->fetch(PDO::FETCH_ASSOC)) { ?>
                    <tr>
                        <td>
                            <?php 
                            if ($f['foto']) {
                                $src = filter_var($f['foto'], FILTER_VALIDATE_URL) ? $f['foto'] : "../{$f['foto']}";
                                echo "<img src='{$src}' width='50' height='50' style='object-fit:cover; border-radius:5px;'>";
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($f['titulo']); ?></td>
                        <td><?php if($f['link_externo']) echo "<a href='{$f['link_externo']}' target='_blank'>Ver</a>"; ?></td>
                        <td><?php echo htmlspecialchars($f['usuario']); ?></td>
                        <td>
                            <a href="?editar=<?php echo $f['id']; ?>" class="btn btn-warning btn-sm text-white" title="Editar"><i class="fas fa-edit"></i></a>
                            <a href="?eliminar=<?php echo $f['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta noticia?');" title="Eliminar"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>