<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}
include("../conexion.php");
date_default_timezone_set('America/Tegucigalpa');

// Obtener todos los clientes
$sql = "SELECT id, identidad, nombre, correo, telefono, direccion, estado
        FROM tbl_ms_clientes
        ORDER BY nombre ASC";
$result = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte General de Clientes</title>
<style>
  body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: #f8f9fa;
  }
  .encabezado-reporte {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 15px;
  }
  .encabezado-reporte img {
    width: 70px;
    height: 70px;
    object-fit: contain;
    border-radius: 8px;
  }
  .titulo-reporte {
    text-align: center;
    flex: 1;
  }
  .titulo-reporte h2 {
    margin: 0;
    font-size: 22px;
  }
  .titulo-reporte p {
    margin: 3px 0;
    font-size: 13px;
  }
  .info-fecha {
    text-align: right;
    font-size: 13px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    margin-top: 10px;
  }
  th, td {
    border: 1px solid #ccc;
    padding: 6px 8px;
    font-size: 12px;
  }
  th {
    background: #000;
    color: #FFD700;
  }
  tr:nth-child(even) {
    background: #f2f2f2;
  }
  .acciones-reporte {
    margin-top: 15px;
    text-align: center;
  }
  .acciones-reporte button {
    padding: 8px 16px;
    margin: 0 5px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: bold;
  }
  .btn-imprimir {
    background: #28a745;
    color: #fff;
  }
  .btn-imprimir:hover {
    background: #218838;
  }
  .btn-reload {
    background: #007bff;
    color: #fff;
  }
  .btn-reload:hover {
    background: #0056b3;
  }
  .btn-salir {
    background: #dc3545;
    color: #fff;
  }
  .btn-salir:hover {
    background: #b52a37;
  }
  .pie-pagina {
    margin-top: 15px;
    font-size: 12px;
    text-align: right;
    color: #555;
  }
</style>
</head>
<body>

<div class="encabezado-reporte">
  <img src="../imagenes/logo.jpeg" alt="Logo">
  <div class="titulo-reporte">
    <h2>REPORTE GENERAL DE CLIENTES</h2>
    <p>Sistema SafeControl - MG Grupo Security</p>
  </div>
  <div class="info-fecha">
    <p><strong>Fecha:</strong> <?php echo date("d/m/Y"); ?></p>
    <p><strong>Hora:</strong> <?php echo date("H:i:s"); ?></p>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>DNI</th>
      <th>Nombre</th>
      <th>Correo</th>
      <th>Teléfono</th>
      <th>Dirección</th>
      <th>Estado</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?php echo $row['id']; ?></td>
          <td><?php echo htmlspecialchars($row['identidad']); ?></td>
          <td><?php echo htmlspecialchars($row['nombre']); ?></td>
          <td><?php echo htmlspecialchars($row['correo']); ?></td>
          <td><?php echo htmlspecialchars($row['telefono']); ?></td>
          <td><?php echo htmlspecialchars($row['direccion']); ?></td>
          <td><?php echo htmlspecialchars($row['estado']); ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="7" style="text-align:center;">No hay clientes registrados.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<div class="acciones-reporte">
  <button class="btn-reload" onclick="location.reload();">↻ Volver a generar</button>
  <button class="btn-imprimir" onclick="window.print();">🖨️ Imprimir</button>
  <button class="btn-salir" onclick="window.close();">✖ Salir</button>
</div>

<div class="pie-pagina">
  Página 1 / 1
</div>

</body>
</html>
