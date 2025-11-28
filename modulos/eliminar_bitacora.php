<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../conexion.php");
include("../funciones.php");
session_start();

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Obtener datos del usuario logueado
$id_usuario = $_SESSION['usuario'];
$query = $conexion->query("SELECT rol FROM tbl_ms_usuarios WHERE id='$id_usuario' LIMIT 1");
$usuario = $query->fetch_assoc();

// Verificar rol administrador
if ($usuario['rol'] !== 'admin') {
    echo "<p style='color:red; text-align:center;'>Acceso restringido. Solo administradores.</p>";
    exit();
}

// Depurar toda la bitácora
if (isset($_GET['depurar']) && $_GET['depurar'] == 1) {
    $conexion->query("TRUNCATE TABLE tbl_ms_bitacora");
    header("Location: bitacora.php");
    exit();
}

// Eliminar registro individual
if (isset($_POST['id_bitacora'])) {
    $id = (int)$_POST['id_bitacora'];
    if ($conexion->query("DELETE FROM tbl_ms_bitacora WHERE id_bitacora=$id")) {
        header("Location: bitacora.php?mensaje=eliminado");
        exit();
    } else {
        header("Location: bitacora.php?mensaje=error_eliminar");
        exit();
    }
}

?>
