<?php
include('conexion.php');

$sql = "CREATE TABLE IF NOT EXISTS incidentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    tipo_incidente VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fecha DATE NOT NULL,
    gravedad VARCHAR(20) NOT NULL,
    acciones_tomadas VARCHAR(255),
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE'
)";

if ($conexion->query($sql) === TRUE) {
    echo "Table incidentes created successfully.";
} else {
    echo "Error creating table: " . $conexion->error;
}
?>
