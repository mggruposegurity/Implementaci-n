<?php
include("../conexion.php");
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

$id_usuario = $_SESSION['usuario'];
$userQuery = $conexion->query("SELECT id, rol FROM tbl_ms_usuarios WHERE id='$id_usuario' LIMIT 1");
$userData = $userQuery->fetch_assoc();
$rol_usuario = $userData ? $userData['rol'] : '';

if ($rol_usuario !== 'admin') {
    echo "<script>alert('⚠️ Acceso denegado. Solo los administradores pueden generar planillas mensuales.'); window.location='../menu.php';</script>";
    exit();
}

// Función para calcular ISR basado en rangos SAR (aproximados mensuales)
function calcularISR($salario_anual) {
    $isr = 0;
    if ($salario_anual > 500000) {
        $isr = ($salario_anual - 500000) * 0.25 + 31250;
    } elseif ($salario_anual > 200000) {
        $isr = ($salario_anual - 200000) * 0.20 + 12500;
    } elseif ($salario_anual > 100000) {
        $isr = ($salario_anual - 100000) * 0.15 + 2500;
    } elseif ($salario_anual > 50000) {
        $isr = ($salario_anual - 50000) * 0.10;
    }
    return $isr / 12; // Mensual
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar'])) {
    $mes = (int)$_POST['mes'];
    $anio = (int)$_POST['anio'];

    // Validar mes y año
    if ($mes < 1 || $mes > 12 || $anio < 2020 || $anio > 2030) {
        echo "<script>alert('Mes o año inválido.');</script>";
        exit();
    }

    // Obtener empleados
    $empleados = $conexion->query("SELECT id_empleado, nombre, salario FROM tbl_ms_empleados WHERE estado='Activo'");

    $registros_insertados = 0;
    while ($emp = $empleados->fetch_assoc()) {
        $id_empleado = $emp['id_empleado'];
        $nombre = $emp['nombre'];
        $salario_mensual = (float)$emp['salario'];

        // Cálculos
        $salario_diario = $salario_mensual / 30;
        $dias_trabajados = 30; // Asumir 30 días
        $horas_extra = 0; // Por ahora 0
        $pago_extra = 0; // Por ahora 0

        $total_ingresos = ($dias_trabajados * $salario_diario) + $pago_extra;

        $ihss = 260; // Fijo
        $rap = $salario_mensual * 0.015;
        $isr = calcularISR($salario_mensual * 12); // Anual para cálculo

        $total_deducciones = $ihss + $rap + $isr;
        $salario_neto = $total_ingresos - $total_deducciones;

        // Fecha de pago: último día del mes
        $fecha_pago = date('Y-m-t', strtotime("$anio-$mes-01"));

        // Insertar en tbl_planilla
        $sql = "INSERT INTO tbl_planilla (
            empleado_id, nombre, salario_empleado, dias_trabajados, salario_diario,
            horas_extra, pago_extra, total_ingresos, deducciones, total_egresos,
            salario_total, fecha_pago, fecha_registro
        ) VALUES (
            $id_empleado, '$nombre', $salario_mensual, $dias_trabajados, $salario_diario,
            $horas_extra, $pago_extra, $total_ingresos, $total_deducciones, $total_deducciones,
            $salario_neto, '$fecha_pago', NOW()
        )";

        if ($conexion->query($sql)) {
            $registros_insertados++;
        }
    }

    // Registrar en bitácora
    $descripcion = "Generación de planilla mensual $mes/$anio. Registros insertados: $registros_insertados.";
    $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                      VALUES ($id_usuario, 'Generar Planilla Mensual', '$descripcion', NOW())");

    echo "<script>alert('Planilla mensual generada exitosamente. Registros: $registros_insertados'); window.location='planilla.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generar Planilla Mensual</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; }
        .container { width: 50%; margin: auto; background: white; padding: 20px; border: 2px solid #ccc; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        select, button { padding: 10px; width: 100%; }
        button { background: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background: #218838; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Generar Planilla Mensual</h2>
        <form method="POST">
            <div class="form-group">
                <label for="mes">Mes:</label>
                <select name="mes" id="mes" required>
                    <option value="">Seleccione mes</option>
                    <?php for ($i=1; $i<=12; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo date('F', mktime(0,0,0,$i,1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="anio">Año:</label>
                <select name="anio" id="anio" required>
                    <option value="">Seleccione año</option>
                    <?php for ($i=2023; $i<=2030; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" name="generar">Generar Planilla</button>
        </form>
        <br>
        <a href="planilla.php">⬅️ Volver a Planilla</a>
    </div>
</body>
</html>
