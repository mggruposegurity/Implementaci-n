<?php
include("../conexion.php");
include("../funciones.php");
session_start();

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $id_usuario = $_SESSION['usuario'];
    $tabla = "planilla"; // 👈 tabla del módulo
    $campo_id = "id";    // 👈 campo llave primaria

    try {
        // 🔹 Intentar eliminar físicamente
        $sql = "DELETE FROM $tabla WHERE $campo_id=$id";
        if ($conexion->query($sql)) {
            // Registrar eliminación en bitácora
            log_event($id_usuario, "Eliminación de registro", "Se eliminó el registro de planilla con ID $id (borrado físico)");
            echo "✅ Registro eliminado correctamente.";
        } else {
            throw new Exception("Error en la eliminación física.");
        }

    } catch (Exception $e) {
        // 🔹 Eliminación lógica (por integridad referencial)
        $conexion->query("UPDATE $tabla SET estado='INACTIVO' WHERE $campo_id=$id");
        // Registrar eliminación lógica en bitácora
        log_event($id_usuario, "Eliminación de registro", "Se cambió el estado del registro de planilla con ID $id a INACTIVO (eliminación lógica)");
        echo "⚠️ No se pudo eliminar físicamente. Se cambió el estado a INACTIVO (eliminación lógica).";
    }
} else {
    echo "⚠️ No se recibió el ID del registro.";
}
?>
