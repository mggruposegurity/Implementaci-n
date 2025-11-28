<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "Moncada1234.", "sistema_empleados", 3306);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "<h2>Verificación de Usuarios en Base de Datos</h2>";

// Verificar si la tabla existe
$tabla_check = $conexion->query("SHOW TABLES LIKE 'tbl_ms_usuarios'");
if ($tabla_check->num_rows == 0) {
    echo "<p style='color: red;'>❌ La tabla 'tbl_ms_usuarios' no existe</p>";
    
    // Mostrar tablas disponibles
    $tablas = $conexion->query("SHOW TABLES");
    echo "<h3>Tablas disponibles:</h3>";
    while ($tabla = $tablas->fetch_row()) {
        echo "- " . $tabla[0] . "<br>";
    }
} else {
    echo "<p style='color: green;'>✅ Tabla 'tbl_ms_usuarios' encontrada</p>";
    
    // Verificar estructura de la tabla
    echo "<h3>Estructura de la tabla:</h3>";
    $estructura = $conexion->query("DESCRIBE tbl_ms_usuarios");
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
    while ($fila = $estructura->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $fila['Field'] . "</td>";
        echo "<td>" . $fila['Type'] . "</td>";
        echo "<td>" . $fila['Null'] . "</td>";
        echo "<td>" . $fila['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar todos los usuarios
    echo "<h3>Usuarios registrados:</h3>";
    $usuarios = $conexion->query("SELECT id, usuario, correo, estado, rol FROM tbl_ms_usuarios");
    
    if ($usuarios->num_rows == 0) {
        echo "<p style='color: orange;'>⚠️ No hay usuarios registrados</p>";
        
        // Crear usuario administrador por defecto
        echo "<h3>Creando usuario administrador por defecto...</h3>";
        
        // Primero verificar si existe empleado
        $empleado_check = $conexion->query("SELECT id_empleado FROM tbl_empleado LIMIT 1");
        if ($empleado_check->num_rows > 0) {
            $empleado = $empleado_check->fetch_assoc();
            $id_empleado = $empleado['id_empleado'];
        } else {
            // Crear empleado primero
            $conexion->query("INSERT INTO tbl_empleado (nombre, dni, id_cargo, id_estado_empleado) 
                           VALUES ('Administrador', 'ADMIN001', 1, 1)");
            $id_empleado = $conexion->insert_id;
        }
        
        // Crear usuario admin
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_usuario = $conexion->query("INSERT INTO tbl_ms_usuarios 
            (id_empleado, usuario, password, correo, rol, estado) 
            VALUES ($id_empleado, 'admin', '$password_hash', 'admin@sistema.com', 'ADMIN', 'ACTIVO')");
        
        if ($insert_usuario) {
            echo "<p style='color: green;'>✅ Usuario admin creado con contraseña: admin123</p>";
        } else {
            echo "<p style='color: red;'>❌ Error al crear usuario: " . $conexion->error . "</p>";
        }
        
        // Volver a consultar usuarios
        $usuarios = $conexion->query("SELECT id, usuario, correo, estado, rol FROM tbl_ms_usuarios");
    }
    
    if ($usuarios->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Usuario</th><th>Correo</th><th>Estado</th><th>Rol</th></tr>";
        while ($fila = $usuarios->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $fila['id'] . "</td>";
            echo "<td>" . $fila['usuario'] . "</td>";
            echo "<td>" . $fila['correo'] . "</td>";
            echo "<td>" . $fila['estado'] . "</td>";
            echo "<td>" . $fila['rol'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Buscar usuario específico ALEJANDRO1
    echo "<h3>Búsqueda específica de 'ALEJANDRO1':</h3>";
    $busqueda = $conexion->query("SELECT * FROM tbl_ms_usuarios WHERE usuario = 'ALEJANDRO1' OR correo = 'ALEJANDRO1'");
    
    if ($busqueda->num_rows > 0) {
        echo "<p style='color: green;'>✅ Usuario ALEJANDRO1 encontrado</p>";
        $usuario_encontrado = $busqueda->fetch_assoc();
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        foreach ($usuario_encontrado as $campo => $valor) {
            echo "<tr><td>$campo</td><td>$valor</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Usuario ALEJANDRO1 no encontrado</p>";
        
        // Buscar usuarios similares
        $similares = $conexion->query("SELECT usuario, correo FROM tbl_ms_usuarios WHERE usuario LIKE '%ALEJANDRO%' OR correo LIKE '%ALEJANDRO%'");
        if ($similares->num_rows > 0) {
            echo "<p>Usuarios similares encontrados:</p>";
            while ($fila = $similares->fetch_assoc()) {
                echo "- Usuario: " . $fila['usuario'] . ", Correo: " . $fila['correo'] . "<br>";
            }
        }
    }
}

$conexion->close();
?>

<p><a href="index.php">← Volver al login</a></p>
