<?php
session_start();
include("../conexion.php");

// ✅ Validar sesión
if (!isset($_SESSION['usuario'])) {
    echo "⚠️ Sesión no válida";
    exit();
}

// ✅ Verificar ID recibido
if (!isset($_POST['id'])) {
    echo "❌ No se recibió el ID";
    exit();
}

$id = intval($_POST['id']);

// ✅ Eliminar registro de asistencia
$query = "DELETE FROM tbl_ms_asistencia WHERE id_asistencia = $id";

if ($conexion->query($query)) {
    echo "✅ Registro eliminado correctamente";
} else {
    echo "❌ Error al eliminar el registro";
}
?>