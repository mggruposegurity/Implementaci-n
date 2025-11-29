<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

// Permitir acceso sólo a admin para seguridad
$id_usuario = $_SESSION['usuario'];
$userQ = $conexion->query("SELECT rol FROM tbl_ms_usuarios WHERE id='$id_usuario' LIMIT 1");
$user = $userQ ? $userQ->fetch_assoc() : null;
if (!$user || strtolower($user['rol']) !== 'admin') {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso denegado. Sólo administradores.</p>";
    exit();
}

// Parámetros opcionales
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$limit = $limit > 0 && $limit <= 1000 ? $limit : 100;
$empleado = isset($_GET['empleado']) ? (int)$_GET['empleado'] : 0;

$where = '';
if ($empleado > 0) $where = "WHERE pi.id_empleado = $empleado";

$sql = "SELECT pi.*, e.nombre AS empleado_nombre, e.dni AS empleado_dni
        FROM tbl_planilla_items pi
        LEFT JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
        $where
        ORDER BY pi.id_item DESC
        LIMIT $limit";

$res = $conexion->query($sql);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Diagnóstico tbl_planilla_items</title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;padding:20px;background:#f7f7f7}
    table{border-collapse:collapse;width:100%;background:white}
    th,td{border:1px solid #ddd;padding:8px;font-size:13px}
    th{background:#222;color:#ffd700}
    .meta{margin-bottom:15px}
    .error{color:red}
  </style>
</head>
<body>
  <h2>Diagnóstico `tbl_planilla_items`</h2>
  <div class="meta">
    <form method="get">
      <label>Empleado ID (opcional): <input type="number" name="empleado" value="<?php echo htmlspecialchars($empleado); ?>"></label>
      &nbsp; <label>Líneas: <input type="number" name="limit" value="<?php echo htmlspecialchars($limit); ?>" min="1" max="1000"></label>
      &nbsp; <button type="submit">Filtrar</button>
    </form>
  </div>

<?php
if (!$res) {
    echo '<p class="error">Error en la consulta: ' . htmlspecialchars($conexion->error) . '</p>';
    echo '<pre>' . htmlspecialchars($sql) . '</pre>';
    exit();
}

if ($res->num_rows === 0) {
    echo '<p>No se encontraron registros.</p>';
    exit();
}

echo '<table><thead><tr>';
echo '<th>id_item</th><th>id_planilla</th><th>id_empleado</th><th>empleado</th><th>dni</th><th>días</th><th>salario_diario</th><th>percepciones</th><th>deducciones</th><th>neto</th><th>otras</th>';
echo '</tr></thead><tbody>';

while ($row = $res->fetch_assoc()) {
    $otras = [];
    foreach (['ihss','ret_fuente','rap','cuentas','rap_ajuste','otras_deducciones','horas_extra','pago_extra'] as $f) {
        if (isset($row[$f]) && $row[$f] != 0) $otras[] = $f . ':' . $row[$f];
    }
    echo '<tr>';
    echo '<td>' . (int)$row['id_item'] . '</td>';
    echo '<td>' . (int)$row['id_planilla'] . '</td>';
    echo '<td>' . (int)$row['id_empleado'] . '</td>';
    echo '<td>' . htmlspecialchars($row['empleado_nombre'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['empleado_dni'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['dias_trabajados'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['salario_diario'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['total_percepciones'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['total_deducciones'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['total_neto'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars(implode(', ', $otras)) . '</td>';
    echo '</tr>';
}

echo '</tbody></table>';
?>

</body>
</html>
