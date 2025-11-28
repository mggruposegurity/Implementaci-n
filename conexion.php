<?php
$conexion = new mysqli("localhost", "root", "Moncada1234.", "sistema_empleados", 3306);
// include("header.php");

if ($conexion->connect_error) {
  die("Error de conexión: " . $conexion->connect_error);
}

// ================================================
// 🔧 Cargar configuración de parámetros del sistema
// ================================================
// Crear arreglo global para parámetros con valores por defecto
$PARAMS = [
    'MAIL_HOST' => 'smtp.gmail.com',
    'MAIL_PORT' => '587',
    'MAIL_USERNAME' => 'empleadossistema@gmail.com',
    'MAIL_PASSWORD' => 'sktxqxmgddbhxchu', // Contraseña de aplicación actualizada
    'MAIL_FROM_NAME' => 'SafeControl',
    'MAIL_SECURE' => 'tls'
];

// Cargar configuraciones desde la tabla tbl_ms_parametros
$result = $conexion->query("SELECT parametro, valor FROM tbl_ms_parametros");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $PARAMS[$row['parametro']] = $row['valor'];
    }
}
?>
