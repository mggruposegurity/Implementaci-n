<?php
session_start();
include("../conexion.php");

// =========================
// VALIDAR SESIÓN Y ROL
// =========================
if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

$id_usuario_sesion = $_SESSION['usuario'];
$userQuery = $conexion->query("SELECT id, rol, usuario FROM tbl_ms_usuarios WHERE id='$id_usuario_sesion' LIMIT 1");
$userData   = $userQuery->fetch_assoc();
$rol_usuario = $userData ? $userData['rol'] : '';

if ($rol_usuario !== 'admin') {
    echo "<script>alert('⚠️ Acceso denegado. Solo los administradores pueden ver vouchers de pago.'); window.location='../menu.php';</script>";
    exit();
}

// =========================
// OBTENER DATOS PARA EL VOUCHER
// Soporta: ?item=ID_ITEM  (preferido)
//          ?id=ID_PLANILLA&empleado=ID_EMPLEADO  (compatibilidad)
// =========================

$id_item = isset($_GET['item']) ? (int)$_GET['item'] : 0;
$id_planilla = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_empleado = isset($_GET['empleado']) ? (int)$_GET['empleado'] : 0;

$planilla = null;

if ($id_item > 0) {
    $stmt = $conexion->prepare(
        "SELECT pi.*, e.nombre, e.dni AS identidad, e.puesto, e.salario AS salario_empleado, p.mes, p.anio, p.fecha_generacion AS fecha_pago
         FROM tbl_planilla_items pi
         JOIN tbl_planilla p ON pi.id_planilla = p.id_planilla
         JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
         WHERE pi.id_item = ? LIMIT 1"
    );
    $stmt->bind_param('i', $id_item);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $planilla = $res->fetch_assoc();
        $id_planilla = (int)$planilla['id_planilla'];
    }
    $stmt->close();
} elseif ($id_planilla > 0 && $id_empleado > 0) {
    $stmt = $conexion->prepare(
        "SELECT pi.*, e.nombre, e.dni AS identidad, e.puesto, e.salario AS salario_empleado, p.mes, p.anio, p.fecha_generacion AS fecha_pago
         FROM tbl_planilla_items pi
         JOIN tbl_planilla p ON pi.id_planilla = p.id_planilla
         JOIN tbl_ms_empleados e ON pi.id_empleado = e.id_empleado
         WHERE pi.id_planilla = ? AND pi.id_empleado = ? LIMIT 1"
    );
    $stmt->bind_param('ii', $id_planilla, $id_empleado);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $planilla = $res->fetch_assoc();
    }
    $stmt->close();
} else {
    echo "<script>alert('Parámetros inválidos para mostrar el voucher.'); window.location='planilla.php';</script>";
    exit();
}

if (!$planilla) {
    echo "<script>alert('Registro de planilla no encontrado.'); window.location='planilla.php';</script>";
    exit();
}

// Nombre del empleado
$nombreEmpleado = $planilla['nombre'] ?? ($planilla['empleado_nombre'] ?? 'Empleado');

// =========================
// Construir detalle de deducciones desde campos del item (si existen)
// =========================
$detalle_deducciones = [];
$total_deducciones_detalle = 0.0;

$possible_dedu = [
    'ihss' => 'IHSS',
    'ret_fuente' => 'Retención en la Fuente',
    'rap' => 'RAP',
    'cuentas' => 'Cuentas por Cobrar',
    'rap_ajuste' => 'RAP Ajuste',
    'otras_deducciones' => 'Otras Deducciones'
];

foreach ($possible_dedu as $field => $label) {
    if (isset($planilla[$field]) && (float)$planilla[$field] > 0) {
        $detalle_deducciones[] = ['tipo' => $label, 'monto' => (float)$planilla[$field]];
        $total_deducciones_detalle += (float)$planilla[$field];
    }
}

// Si aún no hay detalle, intentar leer campos genéricos
if ($total_deducciones_detalle <= 0) {
    if (isset($planilla['total_deducciones'])) $total_deducciones_detalle = (float)$planilla['total_deducciones'];
    elseif (isset($planilla['deducciones'])) $total_deducciones_detalle = (float)$planilla['deducciones'];
}

// =========================
// REGISTRAR EN BITÁCORA
// =========================
if ($userData) {
    $id_usuario_bit = (int)$userData['id'];
    $accion_b       = "Ver Voucher de Pago";

    // Quitamos las comillas internas y escapamos la cadena
    $descripcion_b  = "Visualización de voucher de pago de planilla ID $id_planilla para el empleado $nombreEmpleado";
    $descripcion_b  = $conexion->real_escape_string($descripcion_b);

    $sqlBitacora = "
        INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
        VALUES ($id_usuario_bit, '$accion_b', '$descripcion_b', NOW())
    ";
    $conexion->query($sqlBitacora);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Voucher de Pago - <?php echo htmlspecialchars($nombreEmpleado); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .voucher {
            width: 700px;
            max-width: 100%;
            margin: auto;
            background: #ffffff;
            border: 2px solid #000;
            padding: 20px 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .voucher-header {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .voucher-header img {
            height: 60px;
            margin-right: 15px;
        }
        .voucher-header h1 {
            margin: 0;
            font-size: 22px;
        }
        .voucher-header p {
            margin: 2px 0 0 0;
            font-size: 13px;
        }
        .section {
            margin-bottom: 18px;
        }
        .section h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }
        .section p {
            margin: 3px 0;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background: #f0f0f0;
        }
        .total-row {
            font-weight: bold;
            background: #f8f9fa;
        }
        .neto {
            font-size: 16px;
            background: #e9ecef;
        }
        .neto td {
            font-weight: bold;
        }
        .firma {
            text-align: center;
            margin-top: 25px;
            font-size: 13px;
        }
        .firma-linea {
            margin-top: 35px;
        }
        .buttons {
            text-align: center;
            margin-top: 20px;
        }
        .buttons button {
            padding: 8px 18px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-print {
            background: #28a745;
            color: #fff;
        }
        .btn-print:hover {
            background: #218838;
        }
        .btn-back {
            background: #6c757d;
            color: #fff;
        }
        .btn-back:hover {
            background: #545b62;
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }
            .voucher {
                width: 100%;
                border: none;
                box-shadow: none;
            }
            .buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="voucher">
    <div class="voucher-header">
        <img src="../imagenes/logo.jpeg" alt="Logo MG Grupo">
        <div>
            <h1>SafeControl - Voucher de Pago</h1>
            <p>MG Grupo Seguridad Privada</p>
            <p>Fecha de Pago: 
                <?php 
                    echo isset($planilla['fecha_pago']) 
                        ? htmlspecialchars(date('d/m/Y', strtotime($planilla['fecha_pago']))) 
                        : date('d/m/Y');
                ?>
            </p>
        </div>
    </div>

    <div class="section">
        <h3>Datos del Empleado</h3>
        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($nombreEmpleado); ?></p>
        <p><strong>ID Empleado:</strong> <?php echo (int)$planilla['empleado_id']; ?></p>
        <?php if (!empty($planilla['identidad'])): ?>
            <p><strong>Identidad:</strong> <?php echo htmlspecialchars($planilla['identidad']); ?></p>
        <?php endif; ?>
        <?php if (!empty($planilla['puesto'])): ?>
            <p><strong>Puesto:</strong> <?php echo htmlspecialchars($planilla['puesto']); ?></p>
        <?php endif; ?>
        <p><strong>Salario Base Mensual:</strong> L. <?php echo number_format((float)$planilla['salario_empleado'], 2); ?></p>
    </div>

    <div class="section">
        <h3>Ingresos</h3>
        <table>
            <tr>
                <th>Concepto</th>
                <th>Monto (L.)</th>
            </tr>
            <tr>
                <td>Salario base por <?php echo (int)$planilla['dias_trabajados']; ?> día(s)</td>
                <td>
                    L. <?php
                    $pago_extra = (float)$planilla['pago_extra'];
                    $ingreso_base = (float)$planilla['total_ingresos'] - $pago_extra;
                    if ($ingreso_base < 0) { $ingreso_base = 0; }
                    echo number_format($ingreso_base, 2);
                    ?>
                </td>
            </tr>
            <?php if ($pago_extra > 0): ?>
                <tr>
                    <td>Horas extra / Bono</td>
                    <td>L. <?php echo number_format($pago_extra, 2); ?></td>
                </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td>Total ingresos</td>
                <td>L. <?php echo number_format((float)$planilla['total_ingresos'], 2); ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Deducciones</h3>
        <table>
            <tr>
                <th>Concepto</th>
                <th>Monto (L.)</th>
            </tr>
            <?php if (count($detalle_deducciones) > 0): ?>
                <?php foreach ($detalle_deducciones as $ded): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ded['tipo']); ?></td>
                        <td>L. <?php echo number_format((float)$ded['monto'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" style="text-align:center;">No hay detalle registrado. Se muestra solo el total.</td>
                </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td>Total deducciones</td>
                <td>L. <?php echo number_format($total_deducciones_detalle, 2); ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Resumen</h3>
        <table>
            <tr class="neto">
                <td>Neto a pagar</td>
                <td>L. <?php echo number_format((float)$planilla['salario_total'], 2); ?></td>
            </tr>
        </table>
    </div>

    <div class="firma">
        <div class="firma-linea">______________________________</div>
        <div>Firma del Empleado</div>
    </div>

    <div class="buttons">
        <button class="btn-print" onclick="window.print();">Imprimir</button>
        <button class="btn-back" onclick="window.location.href='planilla.php';">Volver a Planilla</button>
    </div>
</div>
</body>
</html>
