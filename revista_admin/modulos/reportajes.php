<?php
include '../config/conexion.php';

// Control de sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /dialogoydesarrollo/revista_admin/login.php");
    exit();
}

$rol_actual = strtolower(trim($_SESSION['usuario_rol'] ?? ''));
$es_admin = in_array($rol_actual, ['admin', 'administrador']);
$es_editor = ($rol_actual === 'editor');

$mensaje_error = '';

// 1. Eliminar reportaje (Solo Admin y Editor)
if (isset($_GET['eliminar'])) {
    if (!$es_admin && !$es_editor) {
        $mensaje_error = "No tienes permisos suficientes para eliminar publicaciones.";
    } else {
        try {
            $stmt = $conexion->prepare("DELETE FROM reportajes WHERE id = :id");
            $stmt->bindValue(':id', intval($_GET['eliminar']), PDO::PARAM_INT);
            $stmt->execute();
            header("Location: reportajes.php"); 
            exit;
        } catch (PDOException $e) { 
            $mensaje_error = "Error al eliminar: " . $e->getMessage(); 
        }
    }
}

// 2. Cargar reporte a editar
$reportaje_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $conexion->prepare("SELECT * FROM reportajes WHERE id = :id");
    $stmt->bindValue(':id', intval($_GET['editar']), PDO::PARAM_INT);
    $stmt->execute();
    $reportaje_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 3. Procesar formulario (Guardar / Actualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $titulo = trim($_POST['titulo'] ?? '');
    $resumen = trim($_POST['resumen_corto'] ?? '');
    $desarrollo = trim($_POST['desarrollo'] ?? '');
    $fecha_publicacion = $_POST['fecha_publicacion'] ?? date('Y-m-d');
    $es_destacado = isset($_POST['es_destacado']) ? 1 : 0;
    $autor_id = $_POST['autor_id'] ?? null;
    $usuario_id = $_POST['usuario_id'] ?? $_SESSION['usuario_id'];

    // Subida de foto
    $foto_final = isset($_POST['foto_actual']) ? $_POST['foto_actual'] : null;
    if (isset($_FILES['foto_upload']) && $_FILES['foto_upload']['error'] === UPLOAD_ERR_OK) {
        $dir = '../uploads/imagenes/';
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $nombre = time() . '_rep_' . uniqid() . '.' . pathinfo($_FILES['foto_upload']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['foto_upload']['tmp_name'], $dir . $nombre)) {
            $foto_final = 'uploads/imagenes/' . $nombre;
        }
    }

    // Subida de PDF
    $pdf_final = isset($_POST['pdf_actual']) ? $_POST['pdf_actual'] : null;
    if (isset($_FILES['pdf_upload']) && $_FILES['pdf_upload']['error'] === UPLOAD_ERR_OK) {
        $dir = '../uploads/pdfs/';
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $nombre = time() . '_reppdf_' . uniqid() . '.pdf';
        if (move_uploaded_file($_FILES['pdf_upload']['tmp_name'], $dir . $nombre)) {
            $pdf_final = 'uploads/pdfs/' . $nombre;
        }
    }

    if (!empty($titulo) && !empty($desarrollo) && !empty($autor_id) && !empty($usuario_id)) {
        if ($id > 0) {
            $sql = "UPDATE reportajes 
                    SET titulo = :tit, resumen_corto = :res, desarrollo = :des, foto_principal = :fot, 
                        pdf_adjunto = :pdf, fecha_publicacion = :fec, es_destacado = :dest, 
                        autor_id = :aut, usuario_id = :usu 
                    WHERE id = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                'tit' => $titulo, 
                'res' => $resumen, 
                'des' => $desarrollo, 
                'fot' => $foto_final, 
                'pdf' => $pdf_final, 
                'fec' => $fecha_publicacion, 
                'dest' => $es_destacado, 
                'aut' => $autor_id, 
                'usu' => $usuario_id, 
                'id'  => $id
            ]);
        } else {
            $sql = "INSERT INTO reportajes (titulo, resumen_corto, desarrollo, foto_principal, pdf_adjunto, fecha_publicacion, es_destacado, autor_id, usuario_id) 
                    VALUES (:tit, :res, :des, :fot, :pdf, :fec, :dest, :aut, :usu)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                'tit' => $titulo, 
                'res' => $resumen, 
                'des' => $desarrollo, 
                'fot' => $foto_final, 
                'pdf' => $pdf_final, 
                'fec' => $fecha_publicacion, 
                'dest' => $es_destacado, 
                'aut' => $autor_id, 
                'usu' => $usuario_id
            ]);
        }
        header("Location: reportajes.php"); 
        exit;
    } else {
        $mensaje_error = "Por favor, completa los campos obligatorios (*).";
    }
}

// Consultas de datos
$autores = $conexion->query("SELECT id, nombres, ap_paterno FROM autores ORDER BY nombres ASC");
$usuarios = $conexion->query("SELECT id, nombres, ap_paterno, rol FROM usuarios ORDER BY nombres ASC");
$resultado = $conexion->query("SELECT r.*, 
                                     CONCAT(a.nombres, ' ', IFNULL(a.ap_paterno, '')) AS autor, 
                                     CONCAT(u.nombres, ' ', IFNULL(u.ap_paterno, '')) AS usuario 
                              FROM reportajes r 
                              INNER JOIN autores a ON r.autor_id = a.id 
                              INNER JOIN usuarios u ON r.usuario_id = u.id 
                              ORDER BY r.id DESC");

include '../includes/header.php'; 
?>

<!-- Summernote Lite CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<style>
    .note-editor.note-frame {
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        background-color: #fff;
    }
    .note-toolbar {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
</style>

<h1 class="h3 mb-3 text-gray-800">Reportajes</h1>
<?php if (!empty($mensaje_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($mensaje_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
        <h6 class="m-0 text-primary fw-bold">
            <i class="fas fa-<?php echo $reportaje_editar ? 'edit' : 'newspaper'; ?> me-1"></i> 
            <?php echo $reportaje_editar ? 'Editar Reportaje' : 'Registrar Reportaje'; ?>
        </h6>
        <?php if ($reportaje_editar): ?>
            <a href="reportajes.php" class="btn btn-secondary btn-sm"><i class="fas fa-times me-1"></i> Cancelar Edición</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form action="reportajes.php" method="POST" enctype="multipart/form-data" class="row g-3">
            <?php if ($reportaje_editar): ?>
                <input type="hidden" name="id" value="<?php echo $reportaje_editar['id']; ?>">
                <input type="hidden" name="foto_actual" value="<?php echo htmlspecialchars($reportaje_editar['foto_principal'] ?? ''); ?>">
                <input type="hidden" name="pdf_actual" value="<?php echo htmlspecialchars($reportaje_editar['pdf_adjunto'] ?? ''); ?>">
            <?php endif; ?>

            <div class="col-md-8">
                <label class="form-label fw-bold">Título *</label>
                <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($reportaje_editar['titulo'] ?? ''); ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Fecha *</label>
                <input type="date" name="fecha_publicacion" class="form-control" value="<?php echo htmlspecialchars($reportaje_editar['fecha_publicacion'] ?? date('Y-m-d')); ?>" required>
            </div>
            
            <div class="col-md-6 border-end">
                <label class="form-label fw-bold text-primary"><i class="fas fa-image me-1"></i> Foto Principal (Subir)</label>
                <input type="file" name="foto_upload" class="form-control" accept="image/*">
                <?php if ($reportaje_editar && !empty($reportaje_editar['foto_principal'])): ?>
                    <small class="text-muted d-block mt-1">Archivo actual: <strong><?php echo htmlspecialchars($reportaje_editar['foto_principal']); ?></strong></small>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold text-danger"><i class="fas fa-file-pdf me-1"></i> PDF Adjunto (Opcional)</label>
                <input type="file" name="pdf_upload" class="form-control" accept=".pdf">
                <?php if ($reportaje_editar && !empty($reportaje_editar['pdf_adjunto'])): ?>
                    <small class="text-muted d-block mt-1">PDF actual: <strong><?php echo htmlspecialchars($reportaje_editar['pdf_adjunto']); ?></strong></small>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Autor *</label>
                <select name="autor_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php 
                    $selected_autor = $reportaje_editar['autor_id'] ?? '';
                    while($a = $autores->fetch(PDO::FETCH_ASSOC)) {
                        $sel = ($a['id'] == $selected_autor) ? 'selected' : '';
                        echo "<option value='{$a['id']}' {$sel}>" . htmlspecialchars($a['nombres'] . ' ' . $a['ap_paterno']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Usuario Responsable *</label>
                <select name="usuario_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php 
                    $selected_usu = $reportaje_editar['usuario_id'] ?? $_SESSION['usuario_id'];
                    while($u = $usuarios->fetch(PDO::FETCH_ASSOC)) {
                        $sel = ($u['id'] == $selected_usu) ? 'selected' : '';
                        $etiqueta_rol = strtoupper($u['rol']);
                        echo "<option value='{$u['id']}' {$sel}>" . htmlspecialchars($u['nombres'] . ' ' . $u['ap_paterno']) . " ({$etiqueta_rol})</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Editores Summernote -->
            <div class="col-12">
                <label class="form-label fw-bold">Resumen</label>
                <textarea name="resumen_corto" id="editor_resumen" class="form-control"><?php echo htmlspecialchars($reportaje_editar['resumen_corto'] ?? ''); ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold">Desarrollo *</label>
                <textarea name="desarrollo" id="editor_desarrollo" class="form-control"><?php echo htmlspecialchars($reportaje_editar['desarrollo'] ?? ''); ?></textarea>
            </div>
            
            <div class="col-md-2 mt-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="es_destacado" id="chk" <?php echo (!empty($reportaje_editar['es_destacado'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-bold" for="chk">¿Destacar?</label>
                </div>
            </div>
            <div class="col-md-10 text-end mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-1"></i> <?php echo $reportaje_editar ? 'Actualizar Reportaje' : 'Guardar'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Reportajes -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>Foto</th>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Destacado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($f = $resultado->fetch(PDO::FETCH_ASSOC)) { ?>
                        <tr>
                            <td>
                                <?php if(!empty($f['foto_principal'])): ?>
                                    <img src="../<?php echo htmlspecialchars($f['foto_principal']); ?>" width="60" height="40" style="object-fit:cover; border-radius:4px;">
                                <?php else: ?>
                                    <span class="text-muted small">Sin foto</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($f['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($f['autor']); ?></td>
                            <td><?php echo $f['es_destacado'] ? '<span class="badge bg-success">Sí</span>' : '<span class="text-muted">-</span>'; ?></td>
                            <td>
                                <a href="?editar=<?php echo $f['id']; ?>" class="btn btn-warning btn-sm text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($es_admin || $es_editor): ?>
                                    <a href="?eliminar=<?php echo $f['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar este reportaje?');" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scripts de Summernote -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<script>
$(document).ready(function() {
    $('#editor_resumen').summernote({
        placeholder: 'Escribe un resumen con formato...',
        tabsize: 2,
        height: 120,
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['view', ['codeview']]
        ]
    });

    $('#editor_desarrollo').summernote({
        placeholder: 'Redacta todo el contenido aquí...',
        tabsize: 2,
        height: 320,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video', 'hr']],
            ['view', ['fullscreen', 'codeview']]
        ]
    });
});
</script>

<?php include '../includes/footer.php'; ?>