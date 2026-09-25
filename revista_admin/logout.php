<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vaciar y destruir sesión
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

session_destroy();

// Redirección fija al login de revista_admin
header("Location: /dialogoydesarrollo/revista_admin/login.php");
exit();
?>