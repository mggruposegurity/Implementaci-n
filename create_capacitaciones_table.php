<?php
include('conexion.php');

$sql = "CREATE TABLE IF NOT EXISTS capacitaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255),
    instructor VARCHAR(100),
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    participantes INT DEFAULT 0,
    estado VARCHAR(20) NOT NULL DEFAULT 'PROGRAMADA'
)";

if ($conexion->query($sql) === TRUE) {
    echo "Table capacitaciones created successfully.";
} else {
    echo "Error creating table: " . $conexion->error;
}
?>
