<?php
require_once("../conexion.php");
$conexion->set_charset("utf8mb4");

// Obtener fecha y hora actuales
date_default_timezone_set("America/Tegucigalpa");
$fecha = date("d/m/Y");
$hora  = date("g:i:s a");

// Traer todos los empleados
$sql = "SELECT id_empleado, nombre, dni, puesto, telefono, correo, estado
        FROM tbl_ms_empleados
        ORDER BY id_empleado ASC";
$res = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte General de Empleados</title>
    <link rel="stylesheet" href="../estilos_reporte.css"><!-- si ya tienes este css -->
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        h2, h3 { margin: 0; text-align: center; }
        .encabezado { text-align:center; margin-bottom: 20px; }
        .encabezado img { height:70px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background:#0097a7; color:#fff; }
        .totales { margin-top: 10px; font-size: 13px; }
        .btn-print { margin-top: 15px; text-align: right; }
        .btn-print button {
            padding: 8px 14px; border-radius: 6px; border:none;
            background:#000; color:#FFD700; cursor:pointer;
        }
        .btn-print button:hover { background:#FFD700; color:#000; }
    </style>
</head>
<body>
<div class="encabezado">
    <img src="../img/logo_mg.png" alt="MG GRUPO SECURITY">
    <h2>MG GRUPO SECURITY</h2>
    <h4>JEHOVÁ NUESTRA ROCA Y ESCUDO</h4>
    <h5>Sistema SafeControl</h5>
    <p>Honduras, a <?php echo $fecha; ?> &nbsp;&nbsp; Hora: <?php echo $hora; ?></p>
    <hr>
    <h3>REPORTE GENERAL DE EMPLEADOS</h3>
    <p>Listado consolidado de empleados registrados en el sistema.</p>
</div>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Nombre completo</th>
        <th>Identidad</th>
        <th>Puesto</th>
        <th>Teléfono</th>
        <th>Correo</th>
        <th>Estado</th>
    </tr>
    </thead>
    <tbody>
    <?php
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id_empleado']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($row['dni']) . "</td>";
            echo "<td>" . htmlspecialchars($row['puesto']) . "</td>";
            echo "<td>" . htmlspecialchars($row['telefono']) . "</td>";
            echo "<td>" . htmlspecialchars($row['correo']) . "</td>";
            echo "<td>" . htmlspecialchars($row['estado']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center;'>No hay empleados registrados.</td></tr>";
    }
    ?>
    </tbody>
</table>

<div class="totales">
    <?php
    $total = $res ? $res->num_rows : 0;
    echo "Total de empleados: <strong>{$total}</strong>";
    ?>
</div>

<div class="btn-print">
    <button onclick="window.print()">🖨️ Imprimir</button>
</div>
</body>
</html>
