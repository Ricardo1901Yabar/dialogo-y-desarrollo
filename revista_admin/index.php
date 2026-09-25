<?php 
include 'config/conexion.php'; 

// 1. Consultas para obtener las métricas principales (contadores)
$total_reportajes = $conexion->query("SELECT COUNT(*) FROM reportajes")->fetchColumn();
$total_noticias = $conexion->query("SELECT COUNT(*) FROM noticias")->fetchColumn();
$total_boletines = $conexion->query("SELECT COUNT(*) FROM boletines")->fetchColumn();
$total_usuarios = $conexion->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

// 2. Consulta para obtener los 5 reportajes más recientes para la tabla inferior
$sql_recientes = "SELECT r.titulo, r.fecha_publicacion, CONCAT(a.nombres, ' ', IFNULL(a.ap_paterno, '')) AS autor 
                  FROM reportajes r 
                  INNER JOIN autores a ON r.autor_id = a.id 
                  ORDER BY r.id DESC LIMIT 5";
$reportajes_recientes = $conexion->query($sql_recientes);

include 'includes/header.php'; 
?>

<!-- Encabezado del Dashboard -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard General</h1>
    <p class="text-muted mb-0">Bienvenido/a, <span class="fw-bold text-dark"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>.</p>
</div>

<!-- Fila de Tarjetas de Resumen -->
<div class="row">
    
    <!-- Tarjeta Reportajes -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left: 4px solid #d52a2a;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-uppercase mb-1" style="color: #d52a2a;">Total Reportajes</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $total_reportajes; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book-open fa-2x text-muted" style="opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta Noticias -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left: 4px solid #d52a2a;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-uppercase mb-1" style="color: #d52a2a;">Noticias Publicadas</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $total_noticias; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-bolt fa-2x text-muted" style="opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta Boletines -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left: 4px solid #d52a2a;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-uppercase mb-1" style="color: #d52a2a;">Boletines (NTEP)</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $total_boletines; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-file-pdf fa-2x text-muted" style="opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta Usuarios -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left: 4px solid #d52a2a;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-uppercase mb-1" style="color: #d52a2a;">Usuarios Activos</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $total_usuarios; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-muted" style="opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fila de Contenido Reciente -->
<div class="row mt-2">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold" style="color: #d52a2a;"><i class="fas fa-clock"></i> Últimos Reportajes Publicados</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Título del Reportaje</th>
                                <th>Autor</th>
                                <th>Fecha de Publicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($reportajes_recientes->rowCount() > 0) { ?>
                                <?php while($fila = $reportajes_recientes->fetch(PDO::FETCH_ASSOC)) { ?>
                                    <tr>
                                        <td class="fw-bold text-start ps-4"><?php echo htmlspecialchars($fila['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['autor']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo date("d/m/Y", strtotime($fila['fecha_publicacion'])); ?></span></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-5">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                        No hay reportajes publicados aún.
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>