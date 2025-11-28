<?php
include('conexion.php');

$sql = "CREATE TABLE IF NOT EXISTS TBL_MS_CONTRATOS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_contrato VARCHAR(50) NOT NULL UNIQUE,
    nombre_cliente VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    monto DECIMAL(10,2) DEFAULT 0.00,
    tipo VARCHAR(50) NOT NULL,
    estado VARCHAR(20) DEFAULT 'ACTIVO',
    observaciones TEXT
)";

if ($conexion->query($sql) === TRUE) {
    echo "Table TBL_MS_CONTRATOS created successfully.";
} else {
    echo "Error creating table: " . $conexion->error;
}
?>
