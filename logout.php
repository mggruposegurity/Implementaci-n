<?php
session_start();
include("conexion.php");
include("funciones.php");

// Registrar logout en bitácora si hay sesión activa
if (isset($_SESSION['usuario'])) {
    $id_usuario = $_SESSION['usuario'];
    log_event($id_usuario, "Cierre de sesión", "El usuario cerró sesión exitosamente");
}

session_destroy();
header("Location: index.php");
exit();
?>
