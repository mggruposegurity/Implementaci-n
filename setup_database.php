<?php
// ================================================
// Script para crear la base de datos sistema_empleados
// ================================================

// Configuración de conexión sin especificar base de datos
$conexion = new mysqli("localhost", "root", "", "", 3306);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "Conectado a MySQL<br>";

// Leer y ejecutar el archivo SQL
$sql_file = __DIR__ . '/crear_base_datos.sql';

if (!file_exists($sql_file)) {
    die("Error: No se encuentra el archivo SQL: $sql_file");
}

// Leer el contenido del archivo SQL
$sql_content = file_get_contents($sql_file);

if ($sql_content === false) {
    die("Error: No se puede leer el archivo SQL");
}

// Dividir el contenido en consultas individuales
$queries = explode(';', $sql_content);

$success_count = 0;
$error_count = 0;

echo "<h2>Ejecutando script de base de datos...</h2>";

foreach ($queries as $query) {
    $query = trim($query);
    
    // Ignorar comentarios y consultas vacías
    if (empty($query) || 
        strpos($query, '--') === 0 || 
        strpos($query, '/*') === 0 ||
        strpos($query, 'USE ') === 0) {
        continue;
    }
    
    try {
        if ($conexion->query($query)) {
            $success_count++;
            echo "<span style='color: green;'>✓ Consulta ejecutada: " . substr($query, 0, 50) . "...</span><br>";
        } else {
            $error_count++;
            echo "<span style='color: red;'>✗ Error: " . $conexion->error . "</span><br>";
            echo "<span style='color: gray;'>Consulta: " . substr($query, 0, 100) . "...</span><br>";
        }
    } catch (Exception $e) {
        $error_count++;
        echo "<span style='color: red;'>✗ Excepción: " . $e->getMessage() . "</span><br>";
    }
}

echo "<h3>Resumen:</h3>";
echo "<span style='color: green;'>Consultas exitosas: $success_count</span><br>";
echo "<span style='color: red;'>Consultas con error: $error_count</span><br>";

// Verificar que la base de datos fue creada
$result = $conexion->query("SHOW DATABASES LIKE 'sistema_empleados'");
if ($result && $result->num_rows > 0) {
    echo "<span style='color: blue; font-weight: bold;'>✓ Base de datos 'sistema_empleados' creada exitosamente</span><br>";
    
    // Verificar las tablas
    $conexion->select_db("sistema_empleados");
    $tables = $conexion->query("SHOW TABLES");
    
    if ($tables) {
        echo "<h4>Tablas creadas:</h4>";
        while ($row = $tables->fetch_row()) {
            echo "- " . $row[0] . "<br>";
        }
    }
} else {
    echo "<span style='color: red; font-weight: bold;'>✗ No se pudo crear la base de datos</span><br>";
}

$conexion->close();

echo "<br><h2>Configuración completada</h2>";
echo "<p>La base de datos 'sistema_empleados' ha sido configurada con collation UTF8MB4 general_ci.</p>";
echo "<p>Usuario administrador por defecto:</p>";
echo "<ul>";
echo "<li>Username: admin</li>";
echo "<li>Password: admin123</li>";
echo "</ul>";
echo "<p><strong>Importante:</strong> Cambie la contraseña del administrador después del primer inicio de sesión.</p>";
?>
