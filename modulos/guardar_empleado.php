<?php
include("../conexion.php");

$id = $_POST['id_empleado'] ?? null;
$nombre = $_POST['nombre'];
$dni = $_POST['dni'];
$puesto = $_POST['puesto'];
$telefono = $_POST['telefono'];
$correo = $_POST['correo'];
$estado = $_POST['estado'];
$id_turno = $_POST['id_turno'] ?? null;

if ($id) {
    // Actualizar empleado existente
    $sql = "UPDATE TBL_MS_EMPLEADOS
            SET nombre='$nombre', dni='$dni', puesto='$puesto', telefono='$telefono', correo='$correo', estado='$estado'
            WHERE id_empleado=$id";
    $mensaje = "Empleado actualizado correctamente.";

    if ($conexion->query($sql)) {
        // Actualizar asignación de turno
        $conexion->query("DELETE FROM empleado_turno WHERE id_empleado=$id");
        if ($id_turno) {
            $conexion->query("INSERT INTO empleado_turno (id_empleado, id_turno) VALUES ($id, $id_turno)");
        }
    }
} else {
    // Insertar nuevo empleado
    $sql = "INSERT INTO TBL_MS_EMPLEADOS (nombre, dni, puesto, telefono, correo, estado)
            VALUES ('$nombre', '$dni', '$puesto', '$telefono', '$correo', '$estado')";
    $mensaje = "Empleado registrado correctamente.";

    if ($conexion->query($sql)) {
        $id_empleado = $conexion->insert_id;

        // Asignar turno si se seleccionó
        if ($id_turno) {
            $conexion->query("INSERT INTO empleado_turno (id_empleado, id_turno) VALUES ($id_empleado, $id_turno)");
        }
    }
}

if ($conexion->query($sql)) {
    echo "<script>alert('$mensaje'); window.location.href='empleados.php';</script>";
} else {
    echo "Error: " . $conexion->error;
}
?>
