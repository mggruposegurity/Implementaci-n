<?php
include("conexion.php");

echo "<h2>Verificación de Turnos</h2>";

// 1. Verificar turnos existentes
echo "<h3>1. Turnos en tbl_ms_turnos:</h3>";
$result = $conexion->query("SELECT * FROM tbl_ms_turnos ORDER BY id_turno");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Hora Inicio</th><th>Hora Fin</th><th>Estado</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id_turno']}</td>";
        echo "<td>{$row['nombre_turno']}</td>";
        echo "<td>{$row['hora_inicio']}</td>";
        echo "<td>{$row['hora_fin']}</td>";
        echo "<td>{$row['estado']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay turnos registrados.</p>";
}

// 2. Verificar asignaciones de turnos
echo "<h3>2. Asignaciones en empleado_turno:</h3>";
$result2 = $conexion->query("SELECT et.*, e.nombre as nombre_empleado, t.nombre_turno 
                             FROM empleado_turno et 
                             LEFT JOIN tbl_ms_empleados e ON et.id_empleado = e.id_empleado
                             LEFT JOIN tbl_ms_turnos t ON et.id_turno = t.id_turno
                             ORDER BY et.id");
if ($result2 && $result2->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>ID Empleado</th><th>Nombre Empleado</th><th>ID Turno</th><th>Nombre Turno</th><th>Fecha Asignación</th></tr>";
    while ($row = $result2->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['id_empleado']}</td>";
        echo "<td>{$row['nombre_empleado']}</td>";
        echo "<td>{$row['id_turno']}</td>";
        echo "<td>{$row['nombre_turno']}</td>";
        echo "<td>{$row['fecha_asignacion']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay asignaciones de turnos.</p>";
}

// 3. Crear turnos 24 y 12 horas si no existen
echo "<h3>3. Crear turnos 24 HORAS y 12 HORAS:</h3>";
$turnos_crear = [
    ['24 HORAS', '00:00:00', '23:59:59'],
    ['12 HORAS', '08:00:00', '20:00:00']
];

foreach ($turnos_crear as $turno) {
    $nombre = $turno[0];
    $inicio = $turno[1];
    $fin = $turno[2];
    
    // Verificar si existe
    $check = $conexion->query("SELECT id_turno FROM tbl_ms_turnos WHERE UPPER(nombre_turno) = '$nombre'");
    if ($check && $check->num_rows > 0) {
        echo "<p>✓ Turno '$nombre' ya existe.</p>";
    } else {
        $sql = "INSERT INTO tbl_ms_turnos (nombre_turno, hora_inicio, hora_fin, ubicacion, estado) 
                VALUES ('$nombre', '$inicio', '$fin', 'GENERAL', 'ACTIVO')";
        if ($conexion->query($sql)) {
            echo "<p>✓ Turno '$nombre' creado exitosamente (ID: {$conexion->insert_id}).</p>";
        } else {
            echo "<p>✗ Error al crear turno '$nombre': {$conexion->error}</p>";
        }
    }
}

echo "<br><a href='/modulos/empleados.php'>Volver a Empleados</a>";
?>
