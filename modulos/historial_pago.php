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

if (!in_array($rol_usuario, ['admin', 'supervisor'])) {
    echo "<script>alert('⚠️ Acceso denegado.'); window.location='../menu.php';</script>";
    exit();
}

// Obtener filtros
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : 0;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
$empleado_id = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : 0;

// Construir consulta
$sql = "SELECT h.*, e.nombre FROM tbl_historial_pago h JOIN tbl_ms_empleados e ON h.id_empleado = e.id_empleado WHERE 1=1";
if ($mes) $sql .= " AND h.mes = $mes";
if ($anio) $sql .= " AND h.anio = $anio";
if ($empleado_id) $sql .= " AND h.id_empleado = $empleado_id";
$sql .= " ORDER BY h.fecha_pago DESC";

$result = $conexion->query($sql);

// Obtener empleados para filtro
$empleados = $conexion->query("SELECT id_empleado, nombre FROM tbl_ms_empleados WHERE estado='Activo'");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Pago</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; }
        .container { width: 90%; margin: auto; background: white; padding: 20px; border: 2px solid #ccc; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .filters { margin-bottom: 20px; }
        .filters select, .filters button { padding: 5px; margin-right: 10px; }
        .actions { text-align: center; margin-top: 20px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Historial de Pago por Empleado</h2>

        <!-- Filtros -->
        <div class="filters">
            <form method="GET" action="">
                <label>Mes:</label>
                <select name="mes">
                    <option value="">Todos</option>
                    <?php for ($i=1; $i<=12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php if ($mes == $i) echo 'selected'; ?>><?php echo date('F', mktime(0,0,0,$i,1)); ?></option>
                    <?php endfor; ?>
                </select>

                <label>Año:</label>
                <select name="anio">
                    <option value="">Todos</option>
                    <?php for ($i=2023; $i<=2030; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php if ($anio == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>

                <label>Empleado:</label>
                <select name="empleado_id">
                    <option value="">Todos</option>
                    <?php while ($emp = $empleados->fetch_assoc()): ?>
                        <option value="<?php echo $emp['id_empleado']; ?>" <?php if ($empleado_id == $emp['id_empleado']) echo 'selected'; ?>><?php echo $emp['nombre']; ?></option>
                    <?php endwhile; ?>
                </select>

                <button type="submit">Filtrar</button>
            </form>
        </div>

        <!-- Tabla de historial -->
        <table>
            <thead>
                <tr>
                    <th>ID Historial</th>
                    <th>Empleado</th>
                    <th>Fecha Pago</th>
                    <th>Mes/Año</th>
                    <th>Salario Base</th>
                    <th>Días Trabajados</th>
                    <th>Horas Extra</th>
                    <th>Pago Extra</th>
                    <th>Deducciones</th>
                    <th>Salario Neto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_historial']; ?></td>
                            <td><?php echo $row['nombre']; ?></td>
                            <td><?php echo $row['fecha_pago']; ?></td>
                            <td><?php echo $row['mes'] . '/' . $row['anio']; ?></td>
                            <td><?php echo number_format($row['salario_base'], 2); ?></td>
                            <td><?php echo $row['dias_trabajados']; ?></td>
                            <td><?php echo $row['horas_extra']; ?></td>
                            <td><?php echo number_format($row['pago_horas_extra'], 2); ?></td>
                            <td><?php echo number_format($row['deducciones'], 2); ?></td>
                            <td><?php echo number_format($row['salario_neto'], 2); ?></td>
                            <td>
                                <a href="historial_pago_detalle.php?id=<?php echo $row['id_historial']; ?>" class="btn">Ver Detalle</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11">No hay registros para mostrar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="actions">
            <a href="export_excel.php?tipo=historial&mes=<?php echo $mes; ?>&anio=<?php echo $anio; ?>&empleado_id=<?php echo $empleado_id; ?>" class="btn">Exportar Excel</a>
            <a href="export_pdf.php?tipo=historial&mes=<?php echo $mes; ?>&anio=<?php echo $anio; ?>&empleado_id=<?php echo $empleado_id; ?>" class="btn">Exportar PDF</a>
            <a href="export_word.php?tipo=historial&mes=<?php echo $mes; ?>&anio=<?php echo $anio; ?>&empleado_id=<?php echo $empleado_id; ?>" class="btn">Exportar Word</a>
        </div>
    </div>
</body>
</html>