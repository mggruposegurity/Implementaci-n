<?php
include('conexion.php');

// Crear tabla configuracion si no existe
$sql = "CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descripcion TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conexion->query($sql) === TRUE) {
    echo "Table configuracion created successfully.<br>";
} else {
    echo "Error creating table: " . $conexion->error . "<br>";
}

// Insertar valores por defecto desde $PARAMS si no existen
$default_configs = [
    ['MAIL_HOST', 'smtp.gmail.com', 'Servidor SMTP para envío de correos'],
    ['MAIL_PORT', '587', 'Puerto del servidor SMTP'],
    ['MAIL_USERNAME', 'empleadossistema@gmail.com', 'Usuario del correo electrónico'],
    ['MAIL_PASSWORD', 'bmysuwfrwllxzyfq', 'Contraseña de aplicación del correo'],
    ['MAIL_FROM_NAME', 'Sistema de Control de Empleados', 'Nombre del remitente'],
    ['ADMIN_INTENTOS_INVALIDOS', '3', 'Número máximo de intentos de login fallidos'],
    ['DIAS_VENCIMIENTO_CLAVE', '90', 'Días para vencimiento de contraseña']
];

foreach ($default_configs as $config) {
    $clave = $conexion->real_escape_string($config[0]);
    $valor = $conexion->real_escape_string($config[1]);
    $descripcion = $conexion->real_escape_string($config[2]);

    // Verificar si ya existe
    $check = $conexion->query("SELECT id FROM configuracion WHERE clave='$clave'");
    if ($check->num_rows == 0) {
        $conexion->query("INSERT INTO configuracion (clave, valor, descripcion) VALUES ('$clave', '$valor', '$descripcion')");
        echo "Inserted default config: $clave<br>";
    }
}

echo "Configuración inicial completada.";
?>
