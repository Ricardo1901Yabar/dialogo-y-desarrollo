<?php
include '../config/conexion.php';

$mensaje_error = '';

// 1. Procesar eliminación de podcast
if (isset($_GET['eliminar'])) {
    $id_borrar = $_GET['eliminar'];
    try {
        $sql_delete = "DELETE FROM podcasts WHERE id = :id";
        $stmt = $conexion->prepare($sql_delete);
        $stmt->execute(['id' => $id_borrar]);
        header("Location: podcasts.php");
        exit;
    } catch (PDOException $e) {
        $mensaje_error = "Error al eliminar el podcast: " . $e->getMessage();
    }
}

// 2. Cargar datos para edición si viene el parámetro ?editar=ID
$podcast_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $conexion->prepare("SELECT * FROM podcasts WHERE id = :id");
    $stmt->execute(['id' => $_GET['editar']]);
    $podcast_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 3. Procesar guardado (Crear o Actualizar)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $titulo = trim($_POST['titulo']);
    $url_embed = trim($_POST['url_embed']);
    $fecha_publicacion = $_POST['fecha_publicacion'];
    $usuario_id = $_POST['usuario_id'];
    
    // Mantener la ruta/enlace actual si se edita y no se ingresa nuevo
    $ruta_final = isset($_POST['audio_actual']) ? $_POST['audio_actual'] : $url_embed;

    if (!empty($url_embed)) {
        $ruta_final = $url_embed;
    }

    // Procesar archivo MP3 si se subió uno
    if (isset($_FILES['archivo_mp3']) && $_FILES['archivo_mp3']['error'] == UPLOAD_ERR_OK) {
        $directorio_subida = '../uploads/audios/';
        
        if (!file_exists($directorio_subida)) {
            mkdir($directorio_subida, 0777, true);
        }

        $extension = pathinfo($_FILES['archivo_mp3']['name'], PATHINFO_EXTENSION);
        if (strtolower($extension) == 'mp3') {
            $nombre_archivo = time() . '_' . uniqid() . '.mp3';
            $ruta_destino = $directorio_subida . $nombre_archivo;
            
            if (move_uploaded_file($_FILES['archivo_mp3']['tmp_name'], $ruta_destino)) {
                $ruta_final = 'uploads/audios/' . $nombre_archivo;
            } else {
                $mensaje_error = "Hubo un error al guardar el archivo MP3 en el servidor.";
            }
        } else {
            $mensaje_error = "Solo se permiten archivos en formato MP3.";
        }
    }

    if (!empty($titulo) && !empty($ruta_final) && !empty($fecha_publicacion) && !empty($usuario_id) && empty($mensaje_error)) {
        try {
            if ($id > 0) {
                // Actualizar podcast existente
                $sql = "UPDATE podcasts 
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
                // Insertar nuevo podcast
                $sql_insert = "INSERT INTO podcasts (titulo, url_embed, fecha_publicacion, usuario_id) 
                               VALUES (:titulo, :url_embed, :fecha_publicacion, :usuario_id)";
                $stmt = $conexion->prepare($sql_insert);
                $stmt->execute([
                    'titulo' => $titulo,
                    'url_embed' => $ruta_final,
                    'fecha_publicacion' => $fecha_publicacion,
                    'usuario_id' => $usuario_id
                ]);
            }
            header("Location: podcasts.php");
            exit;
        } catch (PDOException $e) {
            $mensaje_error = "Error al guardar el podcast: " . $e->getMessage();
        }
    } elseif (empty($ruta_final)) {
        $mensaje_error = "Debes proporcionar un enlace o subir un archivo MP3.";
    }
}

// 4. Filtrar usuarios: Solo 'redactor' o 'editor' (excluye administradores)
$usuarios = $conexion->query("SELECT id, nombres, ap_paterno FROM usuarios WHERE LOWER(rol) IN ('redactor', 'editor') ORDER BY nombres ASC");

// 5. Consultar la lista de podcasts
$sql = "SELECT p.id, p.titulo, p.url_embed, p.fecha_publicacion, 
               CONCAT(u.nombres, ' ', u.ap_paterno) AS usuario
        FROM podcasts p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        ORDER BY p.id DESC";
$resultado = $conexion->query($sql);

include '../includes/header.php'; 
?>

<h1 class="h3 mb-3 text-gray-800">Gestión de Podcasts</h1>
<p class="text-muted mb-4">Añade o edita episodios de audio. Puedes subir un archivo <strong>MP3</strong> directamente desde tu PC o pegar el código/enlace de plataformas externas.</p>

<?php if (!empty($mensaje_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $mensaje_error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Formulario -->
<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 text-primary fw-bold">
            <i class="fas fa-<?php echo $podcast_editar ? 'edit' : 'microphone'; ?>"></i> 
            <?php echo $podcast_editar ? 'Editar Episodio' : 'Publicar Nuevo Episodio'; ?>
        </h6>
        <?php if ($podcast_editar): ?>
            <a href="podcasts.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Cancelar Edición</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form action="podcasts.php" method="POST" enctype="multipart/form-data" class="row g-3">
            <?php if ($podcast_editar): ?>
                <input type="hidden" name="id" value="<?php echo $podcast_editar['id']; ?>">
                <input type="hidden" name="audio_actual" value="<?php echo htmlspecialchars($podcast_editar['url_embed']); ?>">
            <?php endif; ?>

            <div class="col-md-8">
                <label class="form-label fw-bold">Título del Episodio *</label>
                <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($podcast_editar['titulo'] ?? ''); ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Fecha de Publicación *</label>
                <input type="date" name="fecha_publicacion" class="form-control" value="<?php echo htmlspecialchars($podcast_editar['fecha_publicacion'] ?? ''); ?>" required>
            </div>
            
            <div class="col-md-12 mt-4 mb-2">
                <span class="badge bg-secondary mb-2">Opciones de Audio</span>
            </div>

            <div class="col-md-6 border-end">
                <label class="form-label fw-bold text-primary"><i class="fas fa-upload"></i> Opción 1: Subir Archivo MP3</label>
                <input type="file" name="archivo_mp3" class="form-control" accept=".mp3">
                <?php if ($podcast_editar && strpos(strtolower($podcast_editar['url_embed']), '.mp3') !== false): ?>
                    <small class="text-muted d-block mt-1">Archivo actual: <strong><?php echo htmlspecialchars($podcast_editar['url_embed']); ?></strong></small>
                <?php else: ?>
                    <small class="text-muted">El archivo se guardará en tu servidor web.</small>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold text-success"><i class="fab fa-spotify"></i> Opción 2: Enlace Externo</label>
                <input type="text" name="url_embed" class="form-control" placeholder="Ej: https://open.spotify.com/embed/..." value="<?php echo ($podcast_editar && strpos(strtolower($podcast_editar['url_embed']), '.mp3') === false) ? htmlspecialchars($podcast_editar['url_embed']) : ''; ?>">
                <small class="text-muted">Si subiste un archivo MP3, deja este campo vacío.</small>
            </div>

            <div class="col-md-6 mt-4">
                <label class="form-label fw-bold">Publicado por (Usuario) *</label>
                <select name="usuario_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php 
                    $selected_user = $podcast_editar['usuario_id'] ?? '';
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
                    <i class="fas fa-save"></i> <?php echo $podcast_editar ? 'Actualizar Podcast' : 'Guardar Podcast'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 text-primary"><i class="fas fa-list"></i> Episodios Registrados</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Audio / Enlace</th>
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
                                <?php if (strpos(strtolower($fila['url_embed']), '.mp3') !== false) { ?>
                                    <audio controls style="height: 30px; width: 250px;">
                                        <source src="../<?php echo htmlspecialchars($fila['url_embed']); ?>" type="audio/mpeg">
                                        Tu navegador no soporta el audio.
                                    </audio>
                                <?php } else { ?>
                                    <small class="text-truncate d-inline-block text-muted" style="max-width: 250px;">
                                        <i class="fas fa-link"></i> <?php echo htmlspecialchars($fila['url_embed']); ?>
                                    </small>
                                <?php } ?>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($fila['usuario']); ?></span></td>
                            <td><?php echo date("d/m/Y", strtotime($fila['fecha_publicacion'])); ?></td>
                            <td class="text-center">
                                <a href="podcasts.php?editar=<?php echo $fila['id']; ?>" class="btn btn-warning btn-sm text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="podcasts.php?eliminar=<?php echo $fila['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este episodio de podcast?');" title="Eliminar">
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