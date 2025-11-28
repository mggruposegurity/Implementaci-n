<?php
include("../conexion.php");
include("../funciones.php");
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Solo admin puede depurar
$id_usuario = $_SESSION['usuario'];
$query = $conexion->query("SELECT rol FROM tbl_ms_usuarios WHERE id='$id_usuario' LIMIT 1");
$usuario = $query->fetch_assoc();
if ($usuario['rol'] !== 'admin') {
    die("Acceso denegado.");
}

// Crear tabla de respaldo si no existe
$conexion->query("CREATE TABLE IF NOT EXISTS tbl_ms_bitacora_backup LIKE tbl_ms_bitacora");

// Copiar registros a la tabla de respaldo
$conexion->query("INSERT INTO tbl_ms_bitacora_backup SELECT * FROM tbl_ms_bitacora");

// Vaciar bitácora usando DELETE para preservar autoincrement
$conexion->query("DELETE FROM tbl_ms_bitacora");

// Registrar la depuración en la bitácora
log_event($id_usuario, "Depuración de Bitácora", "Se depuró la bitácora completa. Registros movidos a respaldo.");

header("Location: bitacora.php");
exit();
