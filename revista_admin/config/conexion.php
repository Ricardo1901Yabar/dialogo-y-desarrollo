<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Comprobar si está corriendo en Localhost (XAMPP) o en el Hosting (InfinityFree)
$es_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);

if ($es_local) {
    // 1. Configuración para tu máquina local
    $host     = 'localhost';
    $dbname   = 'revista_digital';
    $username = 'root';
    $password = '';
} else {
    // 2. Configuración para InfinityFree (midemo.42web.io)
    // REVISA estos 4 datos en tu panel de InfinityFree (MySQL Databases):
    $host     = 'sql302.infinityfree.com';      // Reemplaza por tu 'MySQL Hostname' exacto (sqlXXX...)
    $dbname   = 'if0_42993409_revista_digital'; // Tu BD completa con el prefijo if0_42993409_
    $username = 'if0_42993409';                 // Tu usuario de cuenta de hosting
    $password = 'TU_PASSWORD_DE_HOSTING';       // La contraseña de tu cuenta if0_42993409
}

try {
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>