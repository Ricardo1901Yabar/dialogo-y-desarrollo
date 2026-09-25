<?php
include '../config/conexion.php';
$mensaje_error = '';

// Eliminar boletín
if (isset($_GET['eliminar'])) {
    try {
        $stmt = $conexion->prepare("DELETE FROM boletines WHERE id = :id");
        $stmt->execute(['id' => $_GET['eliminar']]);
        header("Location: boletines.php"); exit;
    } catch (PDOException $e) { $mensaje_error = "Error: " . $e->getMessage(); }
}

// Cargar datos para edición si viene el parámetro ?editar=ID
$boletin_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $conexion->prepare("SELECT * FROM boletines WHERE id = :id");
    $stmt->execute(['id' => $_GET['editar']]);
    $boletin_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Procesar formulario (Crear o Actualizar)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $numero_boletin = trim($_POST['numero_boletin']);
    $resumen = trim($_POST['resumen']) ?: null;
    $fecha_publicacion = $_POST['fecha_publicacion'];
    $usuario_id = $_POST['usuario_id'];
    
    // 1. Imagen de Portada (conservar anterior si se edita)
    $foto_final = isset($_POST['foto_actual']) ? $_POST['foto_actual'] : (trim($_POST['foto_url'] ?? '') ?: null);
    if (isset($_FILES['foto_upload']) && $_FILES['foto_upload']['error'] == UPLOAD_ERR_OK) {
        $dir = '../uploads/imagenes/';
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $nombre = time() . '_bol_' . uniqid() . '.' . pathinfo($_FILES['foto_upload']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['foto_upload']['tmp_name'], $dir . $nombre)) $foto_final = 'uploads/imagenes/' . $nombre;
    }

    // 2. Archivo PDF (conservar anterior si se edita)
    $pdf_final = isset($_POST['pdf_actual']) ? $_POST['pdf_actual'] : (trim($_POST['pdf_url'] ?? '') ?: null);
    if (isset($_FILES['pdf_upload']) && $_FILES['pdf_upload']['error'] == UPLOAD_ERR_OK) {
        $dir = '../uploads/pdfs/';
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $nombre = time() . '_pdf_' . uniqid() . '.pdf';
        if (move_uploaded_file($_FILES['pdf_upload']['tmp_name'], $dir . $nombre)) $pdf_final = 'uploads/pdfs/' . $nombre;
    }

    if (!empty($numero_boletin) && !empty($pdf_final) && !empty($usuario_id)) {
        try {
            if ($id > 0) {
                // Actualizar boletín existente
                $sql = "UPDATE boletines 
                        SET numero_boletin = :num, resumen = :res, foto_portada = :fot, 
                            archivo_pdf = :pdf, fecha_publicacion = :fec, usuario_id = :usu 
                        WHERE id = :id";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    'num' => $numero_boletin, 'res' => $resumen, 'fot' => $foto_final, 
                    'pdf' => $pdf_final, 'fec' => $fecha_publicacion, 'usu' => $usuario_id, 'id' => $id
                ]);
            } else {
                // Insertar nuevo boletín
                $sql = "INSERT INTO boletines (numero_boletin, resumen, foto_portada, archivo_pdf, fecha_publicacion, usuario_id) 
                        VALUES (:num, :res, :fot, :pdf, :fec, :usu)";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    'num' => $numero_boletin, 'res' => $resumen, 'fot' => $foto_final, 
                    'pdf' => $pdf_final, 'fec' => $fecha_publicacion, 'usu' => $usuario_id
                ]);
            }
            header("Location: boletines.php"); exit;
        } catch (PDOException $e) {
            $mensaje_error = ($e->getCode() == '23000') ? "El boletín ya existe." : "Error: " . $e->getMessage();
        }
    } else { 
        $mensaje_error = "Falta el archivo PDF o datos obligatorios."; 
    }
}

// Filtro: Solo usuarios con rol 'redactor' o 'editor' (excluye administradores)
$usuarios = $conexion->query("SELECT id, nombres, ap_paterno FROM usuarios WHERE LOWER(rol) IN ('redactor', 'editor') ORDER BY nombres ASC");
$resultado = $conexion->query("SELECT b.*, CONCAT(u.nombres, ' ', u.ap_paterno) AS usuario FROM boletines b INNER JOIN usuarios u ON b.usuario_id = u.id ORDER BY b.id DESC");

include '../includes/header.php'; 
?>

<h1 class="h3 mb-3 text-gray-800">Boletines</h1>
<?php if ($mensaje_error): ?><div class="alert alert-danger"><?php echo $mensaje_error; ?></div><?php endif; ?>

<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 text-primary fw-bold">
            <i class="fas fa-<?php echo $boletin_editar ? 'edit' : 'book-open'; ?>"></i> 
            <?php echo $boletin_editar ? 'Editar Boletín' : 'Registrar Boletín'; ?>
        </h6>
        <?php if ($boletin_editar): ?>
            <a href="boletines.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Cancelar Edición</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form action="boletines.php" method="POST" enctype="multipart/form-data" class="row g-3">
            <?php if ($boletin_editar): ?>
                <input type="hidden" name="id" value="<?php echo $boletin_editar['id']; ?>">
                <input type="hidden" name="foto_actual" value="<?php echo htmlspecialchars($boletin_editar['foto_portada'] ?? ''); ?>">
                <input type="hidden" name="pdf_actual" value="<?php echo htmlspecialchars($boletin_editar['archivo_pdf'] ?? ''); ?>">
            <?php endif; ?>

            <div class="col-md-3">
                <label class="fw-bold">N° Edición *</label>
                <input type="text" name="numero_boletin" class="form-control" value="<?php echo htmlspecialchars($boletin_editar['numero_boletin'] ?? ''); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="fw-bold">Fecha *</label>
                <input type="date" name="fecha_publicacion" class="form-control" value="<?php echo htmlspecialchars($boletin_editar['fecha_publicacion'] ?? ''); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="fw-bold">Publicado por (Usuario) *</label>
                <select name="usuario_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php 
                    $selected_usu = $boletin_editar['usuario_id'] ?? '';
                    while($u = $usuarios->fetch(PDO::FETCH_ASSOC)) {
                        $sel = ($u['id'] == $selected_usu) ? 'selected' : '';
                        echo "<option value='{$u['id']}' {$sel}>{$u['nombres']} {$u['ap_paterno']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-6 border-end">
                <label class="fw-bold text-primary"><i class="fas fa-image"></i> Portada (Subir imagen)</label>
                <input type="file" name="foto_upload" class="form-control" accept="image/*">
                <?php if ($boletin_editar && !empty($boletin_editar['foto_portada'])): ?>
                    <small class="text-muted d-block mt-1">Portada actual: <strong><?php echo htmlspecialchars($boletin_editar['foto_portada']); ?></strong></small>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="fw-bold text-danger"><i class="fas fa-file-pdf"></i> Archivo PDF (Subir documento) <?php echo $boletin_editar ? '' : '*'; ?></label>
                <input type="file" name="pdf_upload" class="form-control" accept=".pdf" <?php echo $boletin_editar ? '' : 'required'; ?>>
                <?php if ($boletin_editar && !empty($boletin_editar['archivo_pdf'])): ?>
                    <small class="text-muted d-block mt-1">PDF actual: <strong><?php echo htmlspecialchars($boletin_editar['archivo_pdf']); ?></strong></small>
                <?php endif; ?>
            </div>

            <div class="col-12">
                <label class="fw-bold">Resumen</label>
                <textarea name="resumen" class="form-control" rows="2"><?php echo htmlspecialchars($boletin_editar['resumen'] ?? ''); ?></textarea>
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $boletin_editar ? 'Actualizar Boletín' : 'Guardar'; ?>
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
                    <th>Edición</th>
                    <th>Portada</th>
                    <th>PDF</th>
                    <th>Publicado por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($f = $resultado->fetch(PDO::FETCH_ASSOC)) { ?>
                    <tr>
                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($f['numero_boletin']); ?></span></td>
                        <td><?php if($f['foto_portada']) echo "<img src='../{$f['foto_portada']}' width='40' height='50' style='object-fit:cover;'>"; ?></td>
                        <td><a href="../<?php echo $f['archivo_pdf']; ?>" target="_blank" class="text-danger"><i class="fas fa-file-pdf fa-2x"></i></a></td>
                        <td><?php echo htmlspecialchars($f['usuario']); ?></td>
                        <td>
                            <a href="?editar=<?php echo $f['id']; ?>" class="btn btn-warning btn-sm text-white" title="Editar"><i class="fas fa-edit"></i></a>
                            <a href="?eliminar=<?php echo $f['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este boletín?');" title="Eliminar"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>