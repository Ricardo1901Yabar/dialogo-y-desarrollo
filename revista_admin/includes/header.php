<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no está logueado, redirigir al login en revista_admin
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /dialogoydesarrollo/revista_admin/login.php");
    exit();
}

$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
$rol_usuario = strtolower(trim($_SESSION['usuario_rol'] ?? 'redactor'));
$es_admin = in_array($rol_usuario, ['admin', 'administrador']);
$es_editor = ($rol_usuario === 'editor');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Revista Admin - Panel de Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fc;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        #sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
            background: #d52a2a;
            color: #ffffff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 22px 20px;
            background: rgba(0, 0, 0, 0.08);
        }

        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin: 12px 15px;
        }

        #sidebar ul.components {
            padding: 10px 0;
            list-style: none;
            margin: 0;
        }

        #sidebar ul li a {
            padding: 12px 25px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            color: #ffffff;
            text-decoration: none;
            transition: 0.2s ease-in-out;
        }

        #sidebar ul li a:hover,
        #sidebar ul li.active > a {
            background: rgba(0, 0, 0, 0.2);
            border-left: 5px solid #ffffff;
        }

        #sidebar ul li a i {
            width: 30px;
            font-size: 1.15rem;
        }

        #content {
            width: calc(100% - 250px);
            margin-left: 250px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid #e3e6f0;
            padding: 12px 25px;
        }

        .btn-hamburger {
            background: transparent;
            border: none;
            color: #5a5c69;
            font-size: 1.25rem;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .btn-hamburger:hover {
            background: #eaecf4;
            color: #d52a2a;
        }

        #sidebar.collapsed {
            left: -250px;
        }
        #content.expanded {
            width: 100%;
            margin-left: 0;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }
        .sidebar-overlay.active {
            display: block;
        }

        @media (max-width: 768px) {
            #sidebar {
                left: -250px;
            }
            #sidebar.mobile-open {
                left: 0;
            }
            #content {
                width: 100%;
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-header d-flex justify-content-between align-items-center">
        <h5 class="m-0 fw-bold"><i class="fas fa-newspaper me-2"></i> Revista Admin</h5>
        <button type="button" class="btn text-white d-md-none" id="sidebarClose">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>
    <hr class="sidebar-divider">

    <ul class="components">
        <li><a href="/dialogoydesarrollo/revista_admin/index.php"><i class="fas fa-home"></i> Inicio</a></li>

        <!-- Módulos solo para Administrador -->
        <?php if ($es_admin): ?>
            <li><a href="/dialogoydesarrollo/revista_admin/modulos/usuarios.php"><i class="fas fa-user-shield"></i> Usuarios</a></li>
        <?php endif; ?>

        <!-- Módulos para Administrador y Editor -->
        <?php if ($es_admin || $es_editor): ?>
            <li><a href="/dialogoydesarrollo/revista_admin/modulos/autores.php"><i class="fas fa-users"></i> Autores</a></li>
        <?php endif; ?>

        <!-- Módulos generales en /modulos/ -->
        <li><a href="/dialogoydesarrollo/revista_admin/modulos/reportajes.php"><i class="fas fa-book"></i> Reportajes</a></li>
        <li><a href="/dialogoydesarrollo/revista_admin/modulos/noticias.php"><i class="fas fa-bolt"></i> Noticias</a></li>
        <li><a href="/dialogoydesarrollo/revista_admin/modulos/boletines.php"><i class="fas fa-file-pdf"></i> Boletines</a></li>
        <li><a href="/dialogoydesarrollo/revista_admin/modulos/podcasts.php"><i class="fas fa-podcast"></i> Podcasts</a></li>
        <li><a href="/dialogoydesarrollo/revista_admin/modulos/videos.php"><i class="fas fa-video"></i> Videos</a></li>

        <hr class="sidebar-divider">
        <!-- Ver Sitio Web (raíz pública) -->
        <li><a href="/dialogoydesarrollo/index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Ver Sitio Web</a></li>
        <!-- Cerrar Sesión (revista_admin/logout.php) -->
        <li><a href="/dialogoydesarrollo/revista_admin/logout.php" class="text-warning"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
    </ul>
</nav>

<!-- Contenedor Principal -->
<div id="content">
    <header class="top-navbar d-flex justify-content-between align-items-center mb-4 shadow-sm">
        <button type="button" id="sidebarCollapse" class="btn btn-hamburger" aria-label="Toggle Menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="user-info d-flex align-items-center gap-3">
            <span class="text-muted small d-none d-sm-inline">
                Bienvenido, <strong class="text-dark"><?php echo htmlspecialchars($nombre_usuario); ?></strong> 
                <span class="badge bg-secondary ms-1 text-uppercase"><?php echo htmlspecialchars($rol_usuario); ?></span>
            </span>
            <a href="/dialogoydesarrollo/revista_admin/logout.php" class="btn btn-sm btn-outline-danger" title="Cerrar Sesión">
                <i class="fas fa-sign-out-alt me-1"></i> Salir
            </a>
        </div>
    </header>

    <main class="container-fluid px-4 flex-grow-1">