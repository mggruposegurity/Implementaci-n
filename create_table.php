<?php
include('conexion.php');
$sql = "CREATE TABLE IF NOT EXISTS TBL_MS_FACTURAS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(50) NOT NULL,
    cliente VARCHAR(255) NOT NULL,
    rtn VARCHAR(50),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    detalle TEXT,
    total_pagar DECIMAL(10,2) NOT NULL,
    estado VARCHAR(20) DEFAULT 'Activo'
)";
$conexion->query($sql);
echo 'Table TBL_MS_FACTURAS created or already exists.';
?>
