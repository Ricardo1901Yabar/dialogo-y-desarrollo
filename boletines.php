<?php
// Conexión a la base de datos
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

// --- LÓGICA DE PAGINACIÓN DINÁMICA ---
$por_pagina = 6; // 6 boletines por página (igual a la plantilla original)
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

// Contar el total de boletines registrados
$total_boletines = $conexion->query("SELECT COUNT(*) FROM boletines")->fetchColumn();
$total_paginas = ceil($total_boletines / $por_pagina);
if ($total_paginas < 1) $total_paginas = 1;

if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;

$offset = ($pagina_actual - 1) * $por_pagina;

// Consultar los boletines de la página actual vinculados con usuarios
$sql = "SELECT b.*, CONCAT(u.nombres, ' ', u.ap_paterno) AS usuario 
        FROM boletines b 
        INNER JOIN usuarios u ON b.usuario_id = u.id 
        ORDER BY b.fecha_publicacion DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $conexion->prepare($sql);
$stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
?>
<!--Author: W3layouts
Author URL: http://w3layouts.com
--><!doctype html>
<html lang="es">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Boletines NTEP - DDP Noticias</title>
    <!-- Google fonts -->
    <link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600&amp;subset=latin-ext,vietnamese" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Template CSS -->
    <link rel="stylesheet" href="index_files/style-starter.css">
  </head>
  <body>
<!-- header -->
<header id="site-header" class="fixed-top">
  <div class="container">
      <nav class="navbar navbar-expand-lg stroke">
          <a class="navbar-brand" href="index.php">
              <img src="index_files/logo.png" alt="Logo DDP" title="Logo DDP" style="height:75px;" />
          </a> 
          <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse"
              data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false"
              aria-label="Toggle navigation">
              <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
              <span class="navbar-toggler-icon fa icon-close fa-times"></span>
          </button>

          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
              <ul class="navbar-nav ml-auto">
                  <li class="nav-item">
                      <a class="nav-link" href="index.php">Inicio</a>
                  </li>
                  <li class="nav-item @@about__active">
                      <a class="nav-link" href="index.php#actualidad">Actualidad</a>
                  </li>
                  <li class="nav-item @@about__active">
                      <a class="nav-link" href="reportajes.php">Reportajes</a>
                  </li>
                  <li class="nav-item @@about__active">
                      <a class="nav-link" href="podcasts.php">Podcast</a>
                  </li>
                  <li class="nav-item active">
                      <a class="nav-link" href="boletines.php">Boletín NTEP <span class="sr-only">(current)</span></a>
                  </li>
                  <li class="nav-item @@about__active">
                      <a class="nav-link" href="alianzas.php">Alianzas</a>
                  </li>
                  <li class="nav-item @@contact__active">
                      <a class="nav-link" href="sobre-dyd.php">Sobre D&amp;D</a>
                  </li>               
                  <li class="ml-2">
                      <a href="#footer" class="btn btn-style btn-outline-secondary">Contacto</a>
                  </li>
              </ul>
          </div>
      </nav>
  </div>
</header>
<!-- //header -->

<section class="breadcrumb-area py-sm-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb-contents">
                    <h2 class="title-big">Boletines NTEP</h2>
                    <div class="breadcrumb">
                        <ul>
                            <li>
                                <a href="index.php">Inicio</a>
                            </li>
                            <li class="active">
                                 Boletines
                            </li>
                        </ul>
                    </div>
                </div>
            </div><!-- end .col-md-12 -->
        </div><!-- end .row -->
    </div><!-- end .container -->
</section>

<div class="grids-block-5 py-5">
    <!-- grids block 5 -->
    <section class="py-lg-4 py-md-3">
        <div class="container">
            <div class="row">
                <?php 
                if ($stmt->rowCount() > 0) {
                    while ($bol = $stmt->fetch(PDO::FETCH_ASSOC)) { 
                        // Portada
                        $portada = 'index_files/boletin-ntep-45.png';
                        if (!empty($bol['foto_portada'])) {
                            if (filter_var($bol['foto_portada'], FILTER_VALIDATE_URL)) {
                                $portada = $bol['foto_portada'];
                            } else {
                                $portada = 'revista_admin/' . htmlspecialchars($bol['foto_portada']);
                            }
                        }
                        
                        // Archivo PDF
                        $archivo_pdf = !empty($bol['archivo_pdf']) ? 'revista_admin/' . htmlspecialchars($bol['archivo_pdf']) : '#';
                ?>
                    <div class="col-lg-4 col-md-6 grids5-info mt-5">
                        <a target="_blank" href="<?php echo $archivo_pdf; ?>" class="d-block">
                            <img src="<?php echo $portada; ?>" alt="<?php echo htmlspecialchars($bol['numero_boletin']); ?>" class="img-fluid" style="width: 100%; height: 380px; object-fit: contain; background: #fff; border-radius: 8px;" />
                        </a>
                        <div class="blog-info">
                            <h5><?php echo date("M d, Y", strtotime($bol['fecha_publicacion'])); ?></h5>
                            <a target="_blank" href="<?php echo $archivo_pdf; ?>" class="btn mt-4 p-0">Ver boletín <span class="fa fa-arrow-right"></span> </a>
                        </div>
                    </div>
                <?php 
                    }
                } else { 
                ?>
                    <p class="text-center w-100 py-5">No hay boletines publicados por el momento.</p>
                <?php 
                } 
                ?>
            </div>

            <!-- PAGINACIÓN DINÁMICA -->
            <?php if ($total_paginas > 1): ?>
            <div class="pagination">
                <ul>
                    <!-- Botón Anterior -->
                    <?php if ($pagina_actual > 1): ?>
                        <li class="prev"><a href="?pagina=<?php echo $pagina_actual - 1; ?>"> Ant</a></li>
                    <?php endif; ?>

                    <!-- Números de Página -->
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li>
                            <a href="?pagina=<?php echo $i; ?>" class="<?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- Botón Siguiente -->
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="next"><a href="?pagina=<?php echo $pagina_actual + 1; ?>"> Sig </a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

        </div>
    </section>
</div>
<!-- // grids block 5 -->

<!-- footer block -->
<section class="w3l-footer-29-main py-5" id="footer">
  <div class="footer-29 py-md-3">
    <div class="container">
      <div class="row footer-top-29">
        <div class="col-lg-6 col-md-6 footer-list-29 footer-1">
          <h6 class="footer-title-29">Quiénes Somos</h6>
          <p>Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.</p>
          <div class="main-social-footer-29">
            <a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru" class="facebook"><span class="fa fa-facebook-square"></span></a>
            <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="index_files/tiktokp.png"></a>
            <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fa fa-instagram"></span></a>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 footer-list-29 footer-2 mt-md-0 mt-5">
          <ul>
            <h6 class="footer-title-29">Contenido</h6>
            <li><a href="index.php#actualidad">Noticias</a></li>
            <li><a href="reportajes.php">Reportajes</a></li>
            <li><a href="podcasts.php">Podcast</a></li>
          </ul>
        </div>
        <div class="col-lg-3 col-md-6 mt-lg-0 mt-5 footer-list-29 footer-3">
          <div class="properties">
            <h6 class="footer-title-29">Contacto</h6>
            <ul>
                <li><a href="mailto:info@dialogoydesarrollo.com.pe">info@dialogoydesarrollo.com.pe</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="bottom-copies text-center">
            <p class="copy-footer-29">© 2026 Diálogo y Desarrollo Perú. All rights reserved | Designed by <a target="_blank" href="https://www.wsperu.info">WebSolutions</a></p>
      </div>
    </div>
  </div>
  <!-- move top -->
  <button onclick="topFunction()" id="movetop" title="Go to top">
    <span class="fa fa-angle-up"></span>
  </button>
  <script>
    window.onscroll = function () {
      scrollFunction()
    };
    function scrollFunction() {
      if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
        document.getElementById("movetop").style.display = "block";
      } else {
        document.getElementById("movetop").style.display = "none";
      }
    }
    function topFunction() {
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
    }
  </script>
  <!-- /move top -->
</section>
<!-- //footer block -->

<!-- Template JavaScript -->
<script src="index_files/jquery-3.3.1.min.js.descarga"></script>
<script src="index_files/theme-change.js.descarga"></script>
<script src="index_files/easyResponsiveTabs.js.descarga"></script>
<script src="index_files/owl.carousel.js.descarga"></script>
<script src="index_files/jquery.magnific-popup.min.js.descarga"></script>

<script>
  $(function () {
    $('.navbar-toggler').click(function () {
      $('body').toggleClass('noscroll');
    })
  });
</script>

<script>
  $(window).on("scroll", function () {
    var scroll = $(window).scrollTop();
    if (scroll >= 80) {
      $("#site-header").addClass("nav-fixed");
    } else {
      $("#site-header").removeClass("nav-fixed");
    }
  });

  $(".navbar-toggler").on("click", function () {
    $("header").toggleClass("active");
  });
  $(document).on("ready", function () {
    if ($(window).width() > 991) {
      $("header").removeClass("active");
    }
    $(window).on("resize", function () {
      if ($(window).width() > 991) {
        $("header").removeClass("active");
      }
    });
  });
</script>

<script src="index_files/bootstrap.min.js.descarga"></script>

</body>
</html>