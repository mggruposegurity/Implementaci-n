<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

include("../conexion.php");
$conexion->set_charset("utf8mb4");

date_default_timezone_set('America/Tegucigalpa');

$query = "SELECT id_empleado, nombre, dni, puesto, telefono, correo, estado
          FROM tbl_ms_empleados
          ORDER BY id_empleado ASC";
$result = $conexion->query($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte General de Empleados</title>
  <style>
    body{
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
      background: #f5f5f5;
    }
    .reporte-container{
      background: #ffffff;
      border-radius: 10px;
      padding: 20px 25px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    .reporte-header{
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }
    .reporte-header-left h2{
      margin: 0;
      font-size: 16px;
      font-weight: 700;
    }
    .reporte-header-left p{
      margin: 0;
      font-size: 12px;
    }
    .reporte-title{
      text-align: center;
      margin: 15px 0 5px 0;
      font-size: 20px;
      font-weight: 700;
    }
    .reporte-subtitle{
      text-align: center;
      font-size: 13px;
      margin-bottom: 15px;
    }
    table{
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
      margin-bottom: 15px;
    }
    th, td{
      border: 1px solid #ddd;
      padding: 6px 8px;
      text-align: left;
    }
    th{
      background: #000;
      color: #FFD700;
      text-transform: uppercase;
      font-size: 12px;
    }
    tr:nth-child(even){
      background: #f9f9f9;
    }
    .reporte-footer{
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 10px;
    }
    .btn{
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 13px;
    }
    .btn-print{
      background: #000;
      color: #FFD700;
    }
    .btn-close{
      background: #6c757d;
      color: #fff;
    }
  </style>
</head>
<body>
  <div class="reporte-container">
    <div class="reporte-header">
      <div class="reporte-header-left">
        <h2>MG GRUPO SECURITY - SafeControl</h2>
        <p>Fecha: <?php echo date("d/m/Y"); ?></p>
        <p>Hora: <?php echo date("g:i:s a"); ?></p>
      </div>
    </div>

    <div class="reporte-title">REPORTE GENERAL DE EMPLEADOS</div>
    <div class="reporte-subtitle">
      Listado completo de empleados registrados en el sistema.
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Identidad</th>
          <th>Puesto</th>
          <th>Teléfono</th>
          <th>Correo</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['id_empleado']); ?></td>
              <td><?php echo htmlspecialchars($row['nombre']); ?></td>
              <td><?php echo htmlspecialchars($row['dni']); ?></td>
              <td><?php echo htmlspecialchars($row['puesto']); ?></td>
              <td><?php echo htmlspecialchars($row['telefono']); ?></td>
              <td><?php echo htmlspecialchars($row['correo']); ?></td>
              <td><?php echo htmlspecialchars($row['estado']); ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" style="text-align:center;">No hay empleados registrados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="reporte-footer">
      <button class="btn btn-close" type="button" onclick="window.parent && window.parent.cerrarReporteEmpleados ? window.parent.cerrarReporteEmpleados() : window.close();">
        Cerrar
      </button>
      <button class="btn btn-print" type="button" onclick="window.print();">
        Imprimir
      </button>
    </div>
  </div>
</body>
</html>
