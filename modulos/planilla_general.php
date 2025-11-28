<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario'])) {
    echo "<div style='padding:20px; color:red; font-size:18px;'>";
    echo "⚠️ No has iniciado sesión correctamente.<br>";
    echo "Por favor, <a href='../index.php' style='color:#FFD700;'>haz clic aquí para iniciar sesión</a>.";
    echo "</div>";
    exit();
}

$id_usuario = $_SESSION['usuario'];
$rol = $_SESSION['rol'] ?? 'empleado';

// Obtener info del usuario de sesión (sin JOIN erróneo)
$query_usuario = "SELECT u.usuario, COALESCE(u.rol, '') AS rol_nombre
                  FROM tbl_ms_usuarios u
                  WHERE u.id = " . (int)$id_usuario . " LIMIT 1";
$result_usuario = $conexion->query($query_usuario);

if (!$result_usuario || $result_usuario->num_rows == 0) {
    echo "<div style='padding:20px; color:red;'>⚠️ Error al obtener información del usuario.</div>";
    exit();
}

$usuario_actual = $result_usuario->fetch_assoc();

// Consulta para obtener todos los empleados (incluye todos los roles)
$query = "
    SELECT
        e.id_empleado AS id,
        COALESCE(e.nombre, u.nombre, u.usuario) AS nombre,
        COALESCE(u.usuario, '') AS usuario,
        COALESCE(u.rol, '') AS rol,
        COALESCE(e.dni, '') AS dni,
        COALESCE(e.correo, u.email, '') AS email,
        COALESCE(e.telefono, u.telefono, '') AS telefono,
        COALESCE(e.estado, '') AS estado,
        COALESCE(e.fecha_ingreso, u.fecha_vencimiento, '') AS fecha_ingreso,
        COALESCE(e.salario, 0) AS salario_base
    FROM tbl_ms_empleados e
    LEFT JOIN tbl_ms_usuarios u ON u.id_empleado = e.id_empleado
    ORDER BY nombre ASC
";

$resultado = $conexion->query($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla General de Empleados</title>
    <style>
        body { font-family: Arial, sans-serif; background: #fff; color: #222; }
        .planilla-container { padding: 20px; }
        h1 { color: #FFD700; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #FFD700; }
        th { background: #FFD700; color: #000; }
        tr:nth-child(even) { background: #f9f9f9; }
        .total-row { background: #FFD700; font-weight: bold; }
    </style>
</head>
<body>
    <div class="planilla-container">
        <h1>📊 Planilla General de Empleados</h1>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre Completo</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Identidad</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Fecha Contratación</th>
                    <th>Salario Base</th>
                </tr>
            </thead>
            <tbody>
                <?php
               $contador = 1;
$total_sueldos = 0;
if ($resultado && $resultado->num_rows > 0) {
    while ($empleado = $resultado->fetch_assoc()) {
        $salario = floatval($empleado['salario_base']);
        $total_sueldos += $salario;
        echo "<tr>";
        echo "<td>{$contador}</td>";
        echo "<td>" . htmlspecialchars($empleado['nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($empleado['usuario']) . "</td>";
        echo "<td>" . htmlspecialchars($empleado['rol']) . "</td>";
        echo "<td>" . htmlspecialchars($empleado['dni']) . "</td>";
        echo "<td>" . htmlspecialchars($empleado['email']) . "</td>";
        echo "<td>" . htmlspecialchars($empleado['telefono']) . "</td>";
        echo "<td>" . htmlspecialchars($empleado['estado']) . "</td>";
        echo "<td>" . (!empty($empleado['fecha_ingreso']) ? date('d/m/Y', strtotime($empleado['fecha_ingreso'])) : '') . "</td>";
        echo "<td>L " . number_format($salario, 2) . "</td>";
        echo "</tr>";
        $contador++;
    }
} else {
    echo "<tr><td colspan='10' style='text-align:center;'>No hay empleados registrados</td></tr>";
}
                ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="9" style="text-align:right;">TOTAL SUELDOS:</td>
                    <td>L <?php echo number_format($total_sueldos, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>