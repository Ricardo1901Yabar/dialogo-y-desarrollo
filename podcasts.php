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

// Paginación dinámica
$por_pagina = 6;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

// Contar podcasts
$total_podcasts = $conexion->query("SELECT COUNT(*) FROM podcasts")->fetchColumn();
$total_paginas = ceil($total_podcasts / $por_pagina);
if ($total_paginas < 1) $total_paginas = 1;
if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;

$offset = ($pagina_actual - 1) * $por_pagina;

// Consultar directamente la tabla podcasts
$sql = "SELECT * FROM podcasts ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $conexion->prepare($sql);
$stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

// Función para generar reproductor embebido de audio/video
function renderizarContenidoPodcast($url) {
    $url = trim($url);
    $url_lower = strtolower($url);

    if (empty($url)) {
        return '<div class="text-center p-3 bg-light rounded text-muted"><i class="fa fa-podcast fa-2x"></i><br>Audio no disponible</div>';
    }

    // 1. Enlace o código embed directo (si pegaron un <iframe> completo)
    if (strpos($url, '<iframe') !== false) {
        return $url;
    }

    // 2. Spotify
    if (strpos($url, 'spotify.com') !== false) {
        $embed_spotify = str_replace('open.spotify.com/', 'open.spotify.com/embed/', $url);
        return '<iframe style="border-radius:12px" src="' . htmlspecialchars($embed_spotify) . '" width="100%" height="152" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>';
    }

    // 3. YouTube (watch o corto youtu.be)
    if (strpos($url, 'youtu.be/') !== false) {
        $partes = explode('youtu.be/', $url);
        $id = explode('?', $partes[1])[0];
        return '<iframe style="width: 100%; height: 210px; border-radius: 8px;" src="https://www.youtube.com/embed/' . htmlspecialchars($id) . '" frameborder="0" allowfullscreen></iframe>';
    }
    if (strpos($url, 'watch?v=') !== false) {
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $id = $params['v'] ?? '';
        return '<iframe style="width: 100%; height: 210px; border-radius: 8px;" src="https://www.youtube.com/embed/' . htmlspecialchars($id) . '" frameborder="0" allowfullscreen></iframe>';
    }

    // 4. Archivo de audio local o directo MP3
    if (strpos($url_lower, '.mp3') !== false || strpos($url_lower, '.wav') !== false || strpos($url_lower, '.m4a') !== false) {
        $ruta = filter_var($url, FILTER_VALIDATE_URL) ? $url : 'revista_admin/' . htmlspecialchars($url);
        return '<audio controls style="width: 100%; margin-top: 20px;">
                    <source src="' . $ruta . '" type="audio/mpeg">
                    Tu navegador no soporta el reproductor de audio.
                </audio>';
    }

    // 5. Archivo local MP4
    if (strpos($url_lower, '.mp4') !== false || strpos($url_lower, '.webm') !== false) {
        $ruta = filter_var($url, FILTER_VALIDATE_URL) ? $url : 'revista_admin/' . htmlspecialchars($url);
        return '<video controls style="width: 100%; height: 210px; background: #000; border-radius: 8px;">
                    <source src="' . $ruta . '" type="video/mp4">
                </video>';
    }

    // Enlace genérico fallback
    return '<iframe style="width: 100%; height: 200px; border-radius: 8px;" src="' . htmlspecialchars($url) . '" frameborder="0" allowfullscreen></iframe>';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Podcasts - DDP Noticias</title>
    <link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600&subset=latin-ext,vietnamese" rel="stylesheet">
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
          <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo02">
              <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
              <span class="navbar-toggler-icon fa icon-close fa-times"></span>
          </button>

          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
              <ul class="navbar-nav ml-auto">
                  <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                  <li class="nav-item"><a class="nav-link" href="index.php#actualidad">Actualidad</a></li>
                  <li class="nav-item"><a class="nav-link" href="reportajes.php">Reportajes</a></li>
                  <li class="nav-item active"><a class="nav-link" href="podcasts.php">Podcast</a></li>
                  <li class="nav-item"><a class="nav-link" href="boletines.php">Boletín NTEP</a></li>
                  <li class="nav-item"><a class="nav-link" href="alianzas.php">Alianzas</a></li>
                  <li class="nav-item"><a class="nav-link" href="sobre-dyd.php">Sobre D&amp;D</a></li>
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
                    <h2 class="title-big">Podcasts</h2>
                    <div class="breadcrumb">
                        <ul>
                            <li><a href="index.php">Inicio</a></li>
                            <li class="active">Podcast</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="grids-block-5 py-5">
    <section class="py-lg-4 py-md-3">
        <div class="container">
            <div class="row">
                <?php 
                if ($stmt->rowCount() > 0) {
                    while ($pod = $stmt->fetch(PDO::FETCH_ASSOC)) { 
                        // Detección flexible de los nombres de columnas habituales en tu BD
                        $enlace = $pod['url_audio'] ?? $pod['url_spotify'] ?? $pod['url_embed'] ?? $pod['url'] ?? '';
                        $titulo = $pod['titulo'] ?? $pod['nombre'] ?? 'Episodio de Podcast';
                        $fecha  = !empty($pod['fecha_publicacion']) ? date("M d, Y", strtotime($pod['fecha_publicacion'])) : '';
                        $descripcion = $pod['descripcion'] ?? $pod['resumen'] ?? '';
                ?>
                    <div class="col-lg-4 col-md-6 grids5-info mt-5">
                        <div class="video-container mb-3">
                            <?php echo renderizarContenidoPodcast($enlace); ?>
                        </div>
                        <div class="blog-info">
                            <h4>
                                <span class="d-block font-weight-bold" style="color: #333; font-size: 18px;">
                                    <?php echo htmlspecialchars($titulo); ?>
                                </span>
                            </h4>
                            <?php if (!empty($fecha)): ?>
                                <h5 class="text-muted mt-2"><i class="fa fa-calendar-o"></i> <?php echo $fecha; ?></h5>
                            <?php endif; ?>
                            <?php if (!empty($descripcion)): ?>
                                <p class="mt-2 text-muted" style="font-size: 14px; line-height: 1.5;">
                                    <?php echo htmlspecialchars(substr($descripcion, 0, 120)) . '...'; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    }
                } else { 
                ?>
                    <p class="text-center w-100 py-5">No hay episodios de podcast disponibles por el momento.</p>
                <?php 
                } 
                ?>
            </div>

            <!-- Paginación Dinámica -->
            <?php if ($total_paginas > 1): ?>
            <div class="pagination mt-5">
                <ul>
                    <?php if ($pagina_actual > 1): ?>
                        <li class="prev"><a href="?pagina=<?php echo $pagina_actual - 1; ?>"> Ant</a></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li>
                            <a href="?pagina=<?php echo $i; ?>" class="<?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="next"><a href="?pagina=<?php echo $pagina_actual + 1; ?>"> Sig </a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<section class="w3l-footer-29-main py-5" id="footer">
  <div class="footer-29 py-md-3">
    <div class="container">
      <div class="bottom-copies text-center">
        <p class="copy-footer-29">© 2026 Diálogo y Desarrollo Perú. All rights reserved.</p>
      </div>
    </div>
  </div>
</section>

<script src="index_files/jquery-3.3.1.min.js.descarga"></script>
<script src="index_files/bootstrap.min.js.descarga"></script>

</body>
</html>