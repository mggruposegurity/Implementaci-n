<?php
include("../conexion.php");
session_start();

if (isset($_GET['id'])) {
    $id_turno = intval($_GET['id']);

    // Primero eliminar las asignaciones del turno en la tabla intermedia
    $conexion->query("DELETE FROM tbl_ms_empleado_turno WHERE id_turno = $id_turno");

    // Luego eliminar el turno
    $delete = $conexion->query("DELETE FROM tbl_ms_turnos WHERE id_turno = $id_turno");

    if ($delete) {
        // Registrar en bitácora si hay sesión activa
        if (isset($_SESSION['usuario'])) {
            $usuario = $_SESSION['usuario'];
            $accion = "Eliminar turno";
            $descripcion = "El usuario $usuario eliminó el turno con ID $id_turno y sus asignaciones relacionadas.";
            $conexion->query("
                INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                VALUES (
                    (SELECT id FROM tbl_ms_usuarios WHERE usuario = '$usuario' LIMIT 1),
                    '$accion',
                    '$descripcion',
                    NOW()
                )
            ");
        }

        echo "<script>
                alert('✅ Turno y sus asignaciones eliminados correctamente.');
                window.location.href = 'turnos.php';
              </script>";
    } else {
        echo "<script>
                alert('❌ Error al eliminar el turno.');
                window.location.href = 'turnos.php';
              </script>";
    }
} else {
    echo "<script>
            alert('⚠️ ID de turno no válido.');
            window.location.href = 'turnos.php';
          </script>";
}
?>
