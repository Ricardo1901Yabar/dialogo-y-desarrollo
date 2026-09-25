<?php
include '../config/conexion.php';

$mensaje_error = '';

// 1. Procesar eliminación de video
if (isset($_GET['eliminar'])) {
    $id_borrar = $_GET['eliminar'];
    try {
        $sql_delete = "DELETE FROM videos WHERE id = :id";
        $stmt = $conexion->prepare($sql_delete);
        $stmt->execute(['id' => $id_borrar]);
        header("Location: videos.php");
        exit;
    } catch (PDOException $e) {
        $mensaje_error = "Error al eliminar el video: " . $e->getMessage();
    }
}

// 2. Cargar datos para edición si viene el parámetro ?editar=ID
$video_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $conexion->prepare("SELECT * FROM videos WHERE id = :id");
    $stmt->execute(['id' => $_GET['editar']]);
    $video_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 3. Procesar guardado (Crear o Actualizar)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $titulo = trim($_POST['titulo']);
    $url_embed = trim($_POST['url_embed']);
    $fecha_publicacion = $_POST['fecha_publicacion'];
    $usuario_id = $_POST['usuario_id'];
    
    // Mantener la ruta o enlace anterior si se está editando
    $ruta_final = isset($_POST['video_actual']) ? $_POST['video_actual'] : $url_embed;

    if (!empty($url_embed)) {
        $ruta_final = $url_embed;
    }

    // Lógica para procesar archivo de video si se subió uno
    if (isset($_FILES['archivo_video']) && $_FILES['archivo_video']['error'] == UPLOAD_ERR_OK) {
        $directorio_subida = '../uploads/videos/';
        
        if (!file_exists($directorio_subida)) {
            mkdir($directorio_subida, 0777, true);
        }

        $extension = pathinfo($_FILES['archivo_video']['name'], PATHINFO_EXTENSION);
        $extensiones_permitidas = ['mp4', 'webm'];
        
        if (in_array(strtolower($extension), $extensiones_permitidas)) {
            $nombre_archivo = time() . '_' . uniqid() . '.' . $extension;
            $ruta_destino = $directorio_subida . $nombre_archivo;
            
            if (move_uploaded_file($_FILES['archivo_video']['tmp_name'], $ruta_destino)) {
                $ruta_final = 'uploads/videos/' . $nombre_archivo;
            } else {
                $mensaje_error = "Hubo un error al guardar el video en el servidor.";
            }
        } else {
            $mensaje_error = "Solo se permiten archivos de video en formato MP4 o WebM.";
        }
    }

    if (!empty($titulo) && !empty($ruta_final) && !empty($fecha_publicacion) && !empty($usuario_id) && empty($mensaje_error)) {
        try {
            if ($id > 0) {
                // Actualizar video existente
                $sql = "UPDATE videos 
                        SET titulo = :titulo, url_embed = :url_embed, 
                            fecha_publicacion = :fecha_publicacion, usuario_id = :usuario_id 
                        WHERE id = :id";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    'titulo' => $titulo,
                    'url_embed' => $ruta_final,
                    'fecha_publicacion' => $fecha_publicacion,
                    'usuario_id' => $usuario_id,
                    'id' => $id
                ]);
            } else {
                // Insertar nuevo video
                $sql_insert = "INSERT INTO videos (titulo, url_embed, fecha_publicacion, usuario_id) 
                               VALUES (:titulo, :url_embed, :fecha_publicacion, :usuario_id)";
                $stmt = $conexion->prepare($sql_insert);
                $stmt->execute([
                    'titulo' => $titulo,
                    'url_embed' => $ruta_final,
                    'fecha_publicacion' => $fecha_publicacion,
                    'usuario_id' => $usuario_id
                ]);
            }
            header("Location: videos.php");
            exit;
        } catch (PDOException $e) {
            $mensaje_error = "Error al guardar el video: " . $e->getMessage();
        }
    } elseif (empty($ruta_final)) {
        $mensaje_error = "Debes proporcionar un enlace de YouTube/Vimeo o subir un archivo MP4.";
    }
}

// 4. Filtrar usuarios: Solo 'redactor' o 'editor' (excluye administradores)
$usuarios = $conexion->query("SELECT id, nombres, ap_paterno FROM usuarios WHERE LOWER(rol) IN ('redactor', 'editor') ORDER BY nombres ASC");

// 5. Consultar la lista de videos
$sql = "SELECT v.id, v.titulo, v.url_embed, v.fecha_publicacion, 
               CONCAT(u.nombres, ' ', u.ap_paterno) AS usuario
        FROM videos v
        INNER JOIN usuarios u ON v.usuario_id = u.id
        ORDER BY v.id DESC";
$resultado = $conexion->query($sql);

include '../includes/header.php'; 
?>

<h1 class="h3 mb-3 text-gray-800">Gestión de Videos</h1>
<p class="text-muted mb-4">Añade o edita material audiovisual. Puedes subir un archivo <strong>MP4</strong> desde tu PC o pegar un enlace de YouTube o Vimeo.</p>

<?php if (!empty($mensaje_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $mensaje_error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Formulario con enctype para archivos -->
<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 text-primary fw-bold">
            <i class="fas fa-<?php echo $video_editar ? 'edit' : 'video'; ?>"></i> 
            <?php echo $video_editar ? 'Editar Video' : 'Publicar Nuevo Video'; ?>
        </h6>
        <?php if ($video_editar): ?>
            <a href="videos.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Cancelar Edición</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form action="videos.php" method="POST" enctype="multipart/form-data" class="row g-3">
            <?php if ($video_editar): ?>
                <input type="hidden" name="id" value="<?php echo $video_editar['id']; ?>">
                <input type="hidden" name="video_actual" value="<?php echo htmlspecialchars($video_editar['url_embed']); ?>">
            <?php endif; ?>

            <div class="col-md-8">
                <label class="form-label fw-bold">Título del Video *</label>
                <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($video_editar['titulo'] ?? ''); ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Fecha de Publicación *</label>
                <input type="date" name="fecha_publicacion" class="form-control" value="<?php echo htmlspecialchars($video_editar['fecha_publicacion'] ?? ''); ?>" required>
            </div>
            
            <div class="col-md-12 mt-4 mb-2">
                <span class="badge bg-secondary mb-2">Opciones de Video (Elige una)</span>
            </div>

            <div class="col-md-6 border-end">
                <label class="form-label fw-bold text-primary"><i class="fas fa-upload"></i> Opción 1: Subir Archivo MP4</label>
                <input type="file" name="archivo_video" class="form-control" accept="video/mp4,video/webm">
                <?php 
                if ($video_editar) {
                    $url_act = strtolower($video_editar['url_embed']);
                    if (strpos($url_act, '.mp4') !== false || strpos($url_act, '.webm') !== false) {
                        echo "<small class='text-muted d-block mt-1'>Archivo actual: <strong>" . htmlspecialchars($video_editar['url_embed']) . "</strong></small>";
                    } else {
                        echo "<small class='text-muted'>El archivo se guardará en tu servidor web (Max 40MB recomendado).</small>";
                    }
                } else {
                    echo "<small class='text-muted'>El archivo se guardará en tu servidor web (Max 40MB recomendado).</small>";
                }
                ?>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold text-danger"><i class="fab fa-youtube"></i> Opción 2: Enlace Externo</label>
                <?php 
                $val_enlace = '';
                if ($video_editar) {
                    $url_act = strtolower($video_editar['url_embed']);
                    if (strpos($url_act, '.mp4') === false && strpos($url_act, '.webm') === false) {
                        $val_enlace = $video_editar['url_embed'];
                    }
                }
                ?>
                <input type="text" name="url_embed" class="form-control" placeholder="Ej: https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($val_enlace); ?>">
                <small class="text-muted">Si subiste un archivo MP4, deja este campo vacío.</small>
            </div>

            <div class="col-md-6 mt-4">
                <label class="form-label fw-bold">Usuario (Quien lo publica) *</label>
                <select name="usuario_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php 
                    $selected_user = $video_editar['usuario_id'] ?? '';
                    while($usu = $usuarios->fetch(PDO::FETCH_ASSOC)) { 
                        $sel = ($usu['id'] == $selected_user) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $usu['id']; ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($usu['nombres'] . ' ' . $usu['ap_paterno']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save"></i> <?php echo $video_editar ? 'Actualizar Video' : 'Guardar Video'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 text-primary"><i class="fas fa-list"></i> Videos Registrados</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Video / Enlace</th>
                        <th>Publicado por</th>
                        <th>Fecha</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($fila = $resultado->fetch(PDO::FETCH_ASSOC)) { ?>
                        <tr>
                            <td class="fw-bold text-muted"><?php echo $fila['id']; ?></td>
                            <td><?php echo htmlspecialchars($fila['titulo']); ?></td>
                            <td>
                                <?php 
                                $url_min = strtolower($fila['url_embed']);
                                if (strpos($url_min, '.mp4') !== false || strpos($url_min, '.webm') !== false) { ?>
                                    <video controls style="height: 60px; border-radius: 5px; background: #000;">
                                        <source src="../<?php echo htmlspecialchars($fila['url_embed']); ?>" type="video/mp4">
                                        Tu navegador no soporta el video.
                                    </video>
                                <?php } else { ?>
                                    <a href="<?php echo htmlspecialchars($fila['url_embed']); ?>" target="_blank" class="text-decoration-none text-danger fw-bold small">
                                        <i class="fab fa-youtube"></i> Ver Enlace
                                    </a>
                                <?php } ?>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($fila['usuario']); ?></span></td>
                            <td><?php echo date("d/m/Y", strtotime($fila['fecha_publicacion'])); ?></td>
                            <td class="text-center">
                                <a href="videos.php?editar=<?php echo $fila['id']; ?>" class="btn btn-warning btn-sm text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="videos.php?eliminar=<?php echo $fila['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este video?');" title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>