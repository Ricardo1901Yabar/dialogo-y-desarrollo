<?php
$host = 'localhost';
$dbname = 'revista_digital';
$username = 'root';
$password = '';

try {
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 1. Obtener el ID del reportaje
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conexion->prepare("SELECT r.*, CONCAT(a.nombres, ' ', IFNULL(a.ap_paterno, '')) AS autor 
                            FROM reportajes r 
                            INNER JOIN autores a ON r.autor_id = a.id 
                            WHERE r.id = :id");
$stmt->execute(['id' => $id]);
$reportaje = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no existe, redirigir al catálogo
if (!$reportaje) {
    header("Location: reportajes-1.php");
    exit;
}

// 2. Consultar las últimas 3 noticias/reportajes para la barra lateral
$ultimos = $conexion->query("SELECT id, titulo, fecha_publicacion FROM reportajes ORDER BY fecha_publicacion DESC LIMIT 3");

// Procesar ruta de foto
$foto = 'index_files/video.jpg';
if (!empty($reportaje['foto_principal'])) {
    $foto = filter_var($reportaje['foto_principal'], FILTER_VALIDATE_URL) 
            ? $reportaje['foto_principal'] 
            : 'revista_admin/' . htmlspecialchars($reportaje['foto_principal']);
}

// Procesar ruta de PDF
$enlace_pdf = !empty($reportaje['pdf_adjunto']) ? 'revista_admin/' . htmlspecialchars($reportaje['pdf_adjunto']) : '#';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($reportaje['titulo']); ?> - DDP Noticias</title>
    <link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600&amp;subset=latin-ext,vietnamese" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="index_files/style-starter.css">
  </head>
  <body>

<header id="site-header" class="fixed-top">
  <div class="container">
      <nav class="navbar navbar-expand-lg stroke">
          <a class="navbar-brand" href="index.php">
              <img src="index_files/logo.png" alt="Logo" style="height:75px;" />
          </a> 
          <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse"
              data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false"
              aria-label="Toggle navigation">
              <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
              <span class="navbar-toggler-icon fa icon-close fa-times"></span>
          </button>

          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
              <ul class="navbar-nav ml-auto">
                  <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                  <li class="nav-item"><a class="nav-link" href="index.php#actualidad">Actualidad</a></li>
                  <li class="nav-item active"><a class="nav-link" href="reportajes-1.php">Reportajes</a></li>
                  <li class="nav-item"><a class="nav-link" href="podcasts.php">Podcast</a></li>
                  <li class="nav-item"><a class="nav-link" href="boletines.php">Boletín NTEP</a></li>
                  <li class="nav-item"><a class="nav-link" href="alianzas.php">Alianzas</a></li>
                  <li class="nav-item"><a class="nav-link" href="sobre-dyd.php">Sobre D&D</a></li>
                  <li class="ml-2"><a href="#footer" class="btn btn-style btn-outline-secondary">Contacto</a></li>
              </ul>
          </div>
      </nav>
  </div>
</header>

<section class="breadcrumb-area py-sm-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb-contents">
                    <h2 class="title-big">Reportajes</h2>
                    <div class="breadcrumb">
                        <ul>
                            <li><a href="index.php">Inicio</a></li>
                            <li class="active"><a href="reportajes-1.php">Reportajes</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="w3l-blog mt-lg-5">
    <div class="text-element-9 py-5 mt-lg-5">
        <div class="container py-lg-3">
            <div class="row grid-text-9">
                <div class="col-lg-8">
                    <div class="blog-single-post">
                        <div class="post-content">
                            <h2 class="title-single mb-3"><?php echo htmlspecialchars($reportaje['titulo']); ?></h2>
                            <small class="text-muted d-block mb-3">
                                <i class="fa fa-calendar"></i> <?php echo date("d M, Y", strtotime($reportaje['fecha_publicacion'])); ?> 
                                &nbsp;|&nbsp; <i class="fa fa-user"></i> <?php echo htmlspecialchars($reportaje['autor']); ?>
                            </small>
                        </div>

                        <div class="single-post-image mb-4 text-center">
                            <?php if (!empty($reportaje['pdf_adjunto'])): ?>
                                <a target="_blank" href="<?php echo $enlace_pdf; ?>">
                                    <img src="<?php echo $foto; ?>" class="img-fluid w-100 radius-image" alt="<?php echo htmlspecialchars($reportaje['titulo']); ?>" />
                                    <br /><span class="text-danger fw-bold"><i class="fa fa-file-pdf-o"></i> Clic en la imagen para ver el documento completo (PDF)</span>
                                </a>
                            <?php else: ?>
                                <img src="<?php echo $foto; ?>" class="img-fluid w-100 radius-image" alt="<?php echo htmlspecialchars($reportaje['titulo']); ?>" />
                            <?php endif; ?>
                        </div>

                        <div class="single-post-content">
                            <?php if (!empty($reportaje['resumen_corto'])): ?>
                                <blockquote class="blockquote my-4">
                                    <q class="mb-3 d-block"><?php echo htmlspecialchars($reportaje['resumen_corto']); ?></q>
                                </blockquote>
                            <?php endif; ?>

                            <div align="justify" class="mb-4" style="line-height: 28px; font-size: 16px;">
                                <?php 
                                // Si tu tabla tiene 'contenido' lo muestra; de lo contrario muestra el resumen_completo
                                $cuerpo = !empty($reportaje['contenido']) ? $reportaje['contenido'] : ($reportaje['resumen_corto'] ?? '');
                                echo nl2br(htmlspecialchars($cuerpo)); 
                                ?>
                            </div>
                        </div>

                        <nav class="post-navigation row mb-5 py-4">
                            <div class="post-prev col-md-6 pr-sm-5">
                                <span class="nav-title">
                                    <span class="fa fa-arrow-left mr-2"></span> 
                                    <a href="reportajes-1.php">Volver a Reportajes</a>
                                </span>
                            </div>
                        </nav>
                    </div>
                </div>

                <!-- Barra Lateral: Últimas Noticias -->
                <div class="col-lg-4 left-text-9 mt-lg-0 mt-5 pl-lg-4">
                    <div class="left-top-9 mt-5 pt-sm-3">
                        <h6 class="heading-small-text-9 mb-3">Últimas noticias</h6>
                        <?php while($u = $ultimos->fetch(PDO::FETCH_ASSOC)): ?>
                            <a href="reportaje-detalle.php?id=<?php echo $u['id']; ?>" class="p-post d-block py-2">
                                <h6 class="text-left-inner-9"><?php echo htmlspecialchars($u['titulo']); ?></h6>
                                <span class="sub-inner-text-9"><?php echo date("M d, Y", strtotime($u['fecha_publicacion'])); ?></span>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="w3l-footer-29-main py-5" id="footer">
  <div class="footer-29 py-md-3">
    <div class="container">
      <div class="bottom-copies text-center">
        <p class="copy-footer-29">© 2026 Diálogo y Desarrollo Perú. All rights reserved.</p>
      </div>
    </div>
  </div>
</footer>

<script src="index_files/jquery-3.3.1.min.js.descarga"></script>
<script src="index_files/bootstrap.min.js.descarga"></script>

</body>
</html>