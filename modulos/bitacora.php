<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../conexion.php");
include("../funciones.php");
include("../header.php");
session_start();

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Registrar entrada al módulo bitácora
$id_usuario = $_SESSION['usuario'];
log_event($id_usuario, "Entrada a módulo", "El usuario accedió al módulo de bitácora");

// Obtener datos del usuario logueado
$id_usuario = $_SESSION['usuario'];
$query = $conexion->query("SELECT * FROM tbl_ms_usuarios WHERE id='$id_usuario' LIMIT 1");

if ($query && $query->num_rows > 0) {
    $usuario = $query->fetch_assoc();
} else {
    echo "<p style='color:red; text-align:center;'>⚠️ Error: usuario no encontrado.</p>";
    exit();
}

// Verificar rol admin
if ($usuario['rol'] !== 'admin') {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso restringido. Solo administradores pueden ver la bitácora.</p>";
    exit();
}

// Procesar eliminación individual (deshabilitado)
if (isset($_GET['eliminar'])) {
    echo "<script>alert('⚠️ La eliminación individual está deshabilitada. Use la depuración completa.');</script>";
}

// Procesar depuración completa
if (isset($_GET['depurar']) && $_GET['depurar'] == 1) {
    // Crear tabla de respaldo si no existe
    $conexion->query("CREATE TABLE IF NOT EXISTS tbl_ms_bitacora_backup LIKE tbl_ms_bitacora");

    // Vaciar la tabla de respaldo para evitar duplicados
    $conexion->query("TRUNCATE TABLE tbl_ms_bitacora_backup");

    // Copiar registros a la tabla de respaldo
    $conexion->query("INSERT INTO tbl_ms_bitacora_backup SELECT * FROM tbl_ms_bitacora");

    // Vaciar la bitácora original
    if ($conexion->query("TRUNCATE TABLE tbl_ms_bitacora")) {
        $mensaje = "Bitácora depurada completamente. Registros movidos a tbl_ms_bitacora_backup.";
    } else {
        $mensaje = "Error al depurar la bitácora: " . $conexion->error;
    }
}

// Filtro de búsqueda
$filtro = "";
if (isset($_GET['buscar']) && !isset($_GET['mostrar_todo'])) {
    $texto = trim($_GET['buscar']);
    $texto = $conexion->real_escape_string($texto);
    $filtro = "WHERE u.usuario LIKE '%$texto%' OR b.accion LIKE '%$texto%' OR b.descripcion LIKE '%$texto%'";
}

// Consulta de bitácora
$sql = "SELECT b.id_bitacora, u.usuario, u.nombre, b.accion, b.descripcion, b.fecha_hora
        FROM tbl_ms_bitacora b
        INNER JOIN tbl_ms_usuarios u ON b.id_usuario = u.id
        $filtro
        ORDER BY b.fecha_hora DESC";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Bitácora del Sistema</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; background-color: #fff; color: #333; }
h2 { text-align: center; color: #FFD700; margin-bottom: 20px; }
form { text-align: center; margin-bottom: 20px; }
input[type="text"] { padding: 8px; width: 250px; border: 1px solid #ccc; border-radius: 5px; }
button { padding: 6px 10px; border: none; background-color: #000000; color: #FFD700; border-radius: 5px; cursor: pointer; }
button:hover { background-color: #FFD700; color: #000000; }
a.button-link { padding: 5px 10px; border-radius:5px; text-decoration:none; color:white; margin:2px; }
a.button-link:hover { opacity:0.8; }
a.eliminar { background-color: #dc3545; }
a.depurar { background-color: #28a745; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
th { background-color: #000000; color: #FFD700; }
tr:nth-child(even) { background-color: #f9f9f9; }
tr:hover { background-color: #f1f1f1; }
</style>
<script>
function confirmarEliminar() {
    return confirm("⚠️ ¿Está seguro que desea eliminar este registro?");
}

function confirmarDepurar() {
    return confirm("⚠️ ¿Está seguro que desea depurar toda la bitácora?");
}
</script>
</head>
<body>

<h2>📜 Bitácora del Sistema</h2>

<?php
if (isset($_GET['mensaje'])) {
    if ($_GET['mensaje'] == 'eliminado') {
        echo "<p style='color:green; text-align:center; font-weight:bold;'>Registro eliminado correctamente.</p>";
    } elseif ($_GET['mensaje'] == 'error_eliminar') {
        echo "<p style='color:red; text-align:center; font-weight:bold;'>Error al eliminar el registro.</p>";
    }
}
if (!empty($mensaje)) echo "<p style='color:green; text-align:center; font-weight:bold;'>$mensaje</p>";
?>

<form method="GET" action="/modulos/bitacora.php">
    <input type="text" name="buscar" placeholder="Buscar por usuario o acción..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
    <button type="submit">Buscar</button>
    <a href="/modulos/bitacora.php" style="margin-left:10px; text-decoration:none; color:#FFD700;">🔄 Mostrar Todo</a>
    <a href="/modulos/bitacora.php?depurar=1" class="button-link depurar" onclick="return confirmarDepurar();">🗑️ Depurar Bitácora</a>
    
</form>

<table>
<thead>
<tr>
<th>ID</th>
<th>Usuario</th>
<th>Nombre</th>
<th>Acción</th>
<th>Descripción</th>
<th>Fecha y Hora</th>
<th>Opciones</th>
</tr>
</thead>
<tbody>
<?php
if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $id_bitacora = $fila['id_bitacora'];
        echo "<tr>
                <td>{$id_bitacora}</td>
                <td>" . htmlspecialchars($fila['usuario'] ?? '') . "</td>
                <td>" . htmlspecialchars($fila['nombre'] ?? '') . "</td>
                <td>" . htmlspecialchars($fila['accion'] ?? '') . "</td>
                <td>" . htmlspecialchars($fila['descripcion'] ?? '') . "</td>
                <td>{$fila['fecha_hora']}</td>
                <td>
                    <!-- Eliminación individual deshabilitada -->
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7'>No hay registros en la bitácora.</td></tr>";
}
?>
</tbody>
</table>
<p style="text-align:center; margin-top:20px;">
    <a href="../menu.php">⬅️ Volver al menú principal</a>
  </p>
<?php include("../footer.php"); ?>
</body>
</html>
