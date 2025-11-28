<?php
include("../conexion.php");
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

$tipo   = $_GET['tipo']   ?? 'general';
$filtro = trim($_GET['filtro'] ?? '');

$sql = "SELECT id, nombre, identidad, correo, telefono, direccion, estado 
        FROM tbl_ms_clientes";
$params = [];
$types  = "";

if ($tipo === 'filtro' && $filtro !== '') {
    $sql .= " WHERE nombre LIKE ? OR identidad LIKE ? OR correo LIKE ?";
    $like = "%".$filtro."%";
    $params = [$like, $like, $like];
    $types  = "sss";
}
$sql .= " ORDER BY nombre ASC";

$stmt = $conexion->prepare($sql);
if ($types !== "" && $params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$fecha = date("d/m/Y");
$hora  = date("H:i:s");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Clientes</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f8f9fa;
      color: #333;
      margin: 0;
      padding: 20px;
    }
    .reporte-container {
      max-width: 900px;
      margin: 0 auto;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      padding: 20px 30px 30px 30px;
    }
    .reporte-encabezado {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #000;
      padding-bottom: 10px;
      margin-bottom: 15px;
    }
    .reporte-encabezado img {
      width: 70px;
      height: 70px;
      object-fit: contain;
      border-radius: 8px;
    }
    .reporte-titulo {
      text-align: center;
      flex: 1;
    }
    .reporte-titulo h2 {
      margin: 0;
      font-size: 20px;
    }
    .reporte-titulo p {
      margin: 2px 0;
      font-size: 13px;
      color: #555;
    }
    .reporte-fecha {
      font-size: 12px;
      text-align: right;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 13px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 6px 8px;
      text-align: left;
    }
    thead {
      background: #000;
      color: #FFD700;
    }
    tr:nth-child(even) {
      background: #f5f5f5;
    }
    .sin-datos {
      text-align: center;
      color: #777;
      padding: 15px 0;
    }
    .reporte-footer {
      margin-top: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 12px;
    }
    .reporte-footer button,
    .reporte-footer a {
      padding: 6px 12px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      font-size: 12px;
      text-decoration: none;
      display: inline-block;
    }
    .btn-volver {
      background: #6c757d;
      color: #fff;
    }
    .btn-volver:hover {
      background: #5a6268;
    }
    .btn-imprimir {
      background: #28a745;
      color: #fff;
    }
    .btn-imprimir:hover {
      background: #218838;
    }
  </style>
</head>
<body>

<div class="reporte-container">
  <div class="reporte-encabezado">
    <img src="../imagenes/logo.jpeg" alt="Logo">
    <div class="reporte-titulo">
      <h2>REPORTE DE CLIENTES</h2>
      <?php if ($tipo === 'filtro' && $filtro !== ''): ?>
        <p>Resultado de búsqueda: <strong><?= htmlspecialchars($filtro) ?></strong></p>
      <?php else: ?>
        <p>Listado general de clientes en cartera</p>
      <?php endif; ?>
    </div>
    <div class="reporte-fecha">
      <p>Fecha: <?= $fecha ?></p>
      <p>Hora: <?= $hora ?></p>
    </div>
  </div>

  <?php if ($result && $result->num_rows > 0): ?>
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
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['identidad']) ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= htmlspecialchars($row['correo']) ?></td>
            <td><?= htmlspecialchars($row['telefono']) ?></td>
            <td><?= htmlspecialchars($row['direccion']) ?></td>
            <td><?= htmlspecialchars($row['estado']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="sin-datos">
      No se encontraron clientes para este criterio.
    </div>
  <?php endif; ?>

  <div class="reporte-footer">
    <a class="btn-volver" href="../menu.php?modulo=Clientes">⬅️ Volver al sistema</a>
    <button class="btn-imprimir" onclick="window.print()">🖨️ Imprimir</button>
  </div>
</div>

</body>
</html>


