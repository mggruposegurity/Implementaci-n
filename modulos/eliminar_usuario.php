<?php
session_start();
include("../conexion.php");
include("../funciones.php");

if (!isset($_POST['id'])) {
    exit("⚠️ No se recibió el ID del usuario.");
}

$id = intval($_POST['id']);
$id_admin = $_SESSION['usuario'];

// Evitar eliminar el usuario principal ADMIN
$consulta = $conexion->query("SELECT usuario, rol FROM tbl_ms_usuarios WHERE id=$id");
$datos = $consulta->fetch_assoc();

if (!$datos) {
    exit("❌ Usuario no encontrado.");
}

if (strtoupper($datos['usuario']) === 'ADMIN' || strtolower($datos['rol']) === 'admin' || $id == 1) {
    exit("🚫 No se puede eliminar el usuario administrador principal del sistema.");
}

// Eliminación física: borrar el registro de la base de datos
$sql = "DELETE FROM tbl_ms_usuarios WHERE id=$id";

if ($conexion->query($sql)) {
    log_event($id_admin, "Eliminación física", "El usuario {$datos['usuario']} fue eliminado permanentemente");
    echo "🗑️ Usuario eliminado correctamente (eliminado permanentemente).";
} else {
    echo "❌ Error al eliminar usuario: " . $conexion->error;
}
?>
