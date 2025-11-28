<?php
include("../conexion.php");
session_start();

// =======================
// VALIDAR SESIÓN
// =======================
if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

$id_usuario = $_SESSION['usuario'];
$userQuery  = $conexion->query("SELECT id, rol FROM tbl_ms_usuarios WHERE id='$id_usuario' LIMIT 1");
$userData   = $userQuery->fetch_assoc();
$rol_usuario = $userData ? $userData['rol'] : '';

if ($rol_usuario !== 'admin') {
    echo "<script>alert('⚠️ Acceso denegado. Solo los administradores pueden ver detalles de deducciones.'); window.location='../menu.php';</script>";
    exit();
}

// =======================
// VALIDAR ID DE PLANILLA
// =======================
$id_planilla = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_planilla <= 0) {
    echo "<script>alert('ID de planilla inválido.'); window.location='planilla.php';</script>";
    exit();
}

// =======================
// OBTENER PLANILLA
// =======================
// Si quieres más datos del empleado, se puede hacer LEFT JOIN
$query = "
    SELECT p.*, e.nombre AS nombre_empleado
    FROM tbl_planilla p
    LEFT JOIN tbl_ms_empleados e ON p.empleado_id = e.id_empleado
    WHERE p.id_planilla = $id_planilla
    LIMIT 1
";
$result = $conexion->query($query);
if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Planilla no encontrada.'); window.location='planilla.php';</script>";
    exit();
}
$planilla = $result->fetch_assoc();

$nombreEmpleado = $planilla['nombre_empleado'] ?: $planilla['nombre'];
$fechaPago      = isset($planilla['fecha_pago']) ? $planilla['fecha_pago'] : '';
$total_egresos  = isset($planilla['total_egresos']) ? (float)$planilla['total_egresos'] : (float)$planilla['deducciones'];

// =======================
// OBTENER DETALLE GUARDADO
// =======================
$stmtDet = $conexion->prepare("
    SELECT tipo, monto 
    FROM tbl_planilla_deducciones 
    WHERE id_planilla = ?
    ORDER BY id ASC
");
$stmtDet->bind_param("i", $id_planilla);
$stmtDet->execute();
$resDet = $stmtDet->get_result();

$detalle = [];
$suma_detalle = 0;

while ($row = $resDet->fetch_assoc()) {
    $detalle[] = $row;
    $suma_detalle += (float)$row['monto'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Deducciones - <?php echo htmlspecialchars($nombreEmpleado); ?></title>
    <style>
        body { font-family: Arial; background: #f5f5f5; }
        .container { width: 60%; margin: auto; background: white; padding: 20px; border: 2px solid #ccc; }
        h2 { text-align: center; }
        .detalle { margin-bottom: 20px; }
        .detalle table { width: 100%; border-collapse: collapse; }
        .detalle th, .detalle td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        .detalle th { background: #f0f0f0; }
        .total { font-weight: bold; background: #e9ecef; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; margin-right: 5px; }
        button:hover { background: #0056b3; }
        .msg-empty { padding: 15px; background:#fff3cd; border:1px solid #ffeeba; border-radius:4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Detalle de Deducciones</h2>
        <p><strong>Empleado:</strong> <?php echo htmlspecialchars($nombreEmpleado); ?></p>
        <p><strong>Fecha de Pago:</strong> <?php echo htmlspecialchars($fechaPago); ?></p>

        <div class="detalle">
            <?php if (count($detalle) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Deducción</th>
                            <th>Monto (L.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalle as $d): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d['tipo']); ?></td>
                                <td><?php echo number_format($d['monto'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total">
                            <td>Total Deducciones (detalle)</td>
                            <td><?php echo number_format($suma_detalle, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Total Deducciones registradas en planilla</td>
                            <td><?php echo number_format($total_egresos, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="msg-empty">
                    <strong>⚠️ No hay detalle de deducciones registrado para esta planilla.</strong><br>
                    Solo se tiene el total de egresos: <b>L. <?php echo number_format($total_egresos, 2); ?></b>.
                </div>
            <?php endif; ?>
        </div>

        <button onclick="window.print()">Imprimir Detalle</button>
        <button onclick="window.location='planilla.php'">Volver a Planilla</button>
    </div>

    <style media="print">
        button { display: none; }
        .container { width: 100%; margin: 0; padding: 10px; border: none; }
    </style>
</body>
</html>
