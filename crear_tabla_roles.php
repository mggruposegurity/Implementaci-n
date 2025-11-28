<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "Moncada1234.", "sistema_empleados", 3306);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "<h2>Creando tabla tbl_ms_roles</h2>";

// Verificar si la tabla ya existe
$tabla_check = $conexion->query("SHOW TABLES LIKE 'tbl_ms_roles'");
if ($tabla_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ La tabla 'tbl_ms_roles' ya existe</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Creando tabla 'tbl_ms_roles'...</p>";
    
    // Crear la tabla
    $create_table = "
    CREATE TABLE `tbl_ms_roles` (
      `id_rol` int(11) NOT NULL AUTO_INCREMENT,
      `descripcion` varchar(50) NOT NULL,
      `usuario_creado` varchar(50) DEFAULT NULL,
      `fecha_creado` datetime DEFAULT NULL,
      `usuario_modificado` varchar(50) DEFAULT NULL,
      `fecha_modificado` datetime DEFAULT NULL,
      PRIMARY KEY (`id_rol`),
      UNIQUE KEY `descripcion` (`descripcion`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    if ($conexion->query($create_table)) {
        echo "<p style='color: green;'>✅ Tabla 'tbl_ms_roles' creada exitosamente</p>";
        
        // Insertar roles básicos
        $insert_roles = "
        INSERT INTO `tbl_ms_roles` (`descripcion`, `usuario_creado`, `fecha_creado`) VALUES
        ('ADMINISTRADOR', 'sistema', NOW()),
        ('GERENTE', 'sistema', NOW()),
        ('SUPERVISOR', 'sistema', NOW()),
        ('EMPLEADO', 'sistema', NOW());
        ";
        
        if ($conexion->query($insert_roles)) {
            echo "<p style='color: green;'>✅ Roles básicos insertados</p>";
        } else {
            echo "<p style='color: red;'>❌ Error al insertar roles: " . $conexion->error . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Error al crear tabla: " . $conexion->error . "</p>";
    }
}

// Mostrar roles actuales
echo "<h3>Roles disponibles:</h3>";
$roles = $conexion->query("SELECT * FROM tbl_ms_roles ORDER BY descripcion");

if ($roles && $roles->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Rol</th><th>Creado por</th><th>Fecha</th></tr>";
    while ($fila = $roles->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $fila['id_rol'] . "</td>";
        echo "<td>" . $fila['descripcion'] . "</td>";
        echo "<td>" . $fila['usuario_creado'] . "</td>";
        echo "<td>" . $fila['fecha_creado'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No hay roles en la tabla</p>";
}

// Verificar otras tablas que podrían faltar
echo "<h3>Verificación de otras tablas:</h3>";

$tablas_necesarias = [
    'tbl_ms_usuarios',
    'tbl_empleado', 
    'tbl_ms_bitacora',
    'tbl_ms_parametros',
    'tbl_cargo',
    'tbl_estado_empleado'
];

foreach ($tablas_necesarias as $tabla) {
    $check = $conexion->query("SHOW TABLES LIKE '$tabla'");
    if ($check->num_rows > 0) {
        echo "<p style='color: green;'>✅ $tabla - Existe</p>";
    } else {
        echo "<p style='color: red;'>❌ $tabla - No existe</p>";
    }
}

$conexion->close();

echo "<p><a href='registro.php'>← Ir a registro de usuario</a></p>";
echo "<p><a href='index.php'>← Ir a login</a></p>";
?>
