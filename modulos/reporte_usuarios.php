<?php
session_start();
include("../conexion.php");
include("../funciones.php");

// ============================
// Validar sesión
// ============================
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$id_usuario = $_SESSION['usuario'];

// (Opcional) validar que sea admin:
$usuarioQuery = $conexion->query("SELECT rol FROM tbl_ms_usuarios WHERE id = '$id_usuario' LIMIT 1");
$usuarioData  = $usuarioQuery ? $usuarioQuery->fetch_assoc() : null;
if (!$usuarioData || strtolower($usuarioData['rol']) !== 'admin') {
    echo "<script>alert('⚠️ Solo los administradores pueden generar este reporte.');window.location='usuarios.php';</script>";
    exit();
}

// ============================
// Consultar usuarios
// ============================

$sql = "SELECT 
            id,
            nombre,
            usuario,
            email,
            rol,
            estado
        FROM tbl_ms_usuarios
        ORDER BY id ASC";

$result = $conexion->query($sql);

$usuarios = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

// (Opcional) registrar en bitácora si tu función existe
if (function_exists('log_event')) {
    log_event($id_usuario, "Reporte Usuarios", "Generó reporte general de usuarios");
}

// Fecha/hora actual
$fecha_hoy = date("d/m/Y");
$hora_hoy  = date("H:i:s");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte General de Usuarios</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 20px;
  }
  .reporte-container {
    max-width: 1000px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 10px;
    padding: 20px 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
  }
  .reporte-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 15px;
  }
  .reporte-header-left {
    font-size: 14px;
    color: #333;
  }
  .reporte-title {
    text-align: center;
    margin: 10px 0 15px 0;
  }
  .reporte-title h1 {
    margin: 0;
    font-size: 20px;
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  .reporte-subtitle {
    font-size: 13px;
    color: #555;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 5px;
    font-size: 13px;
  }
  table th, table td {
    border: 1px solid #ddd;
    padding: 6px 8px;
    text-align: left;
  }
  table th {
    background: #000;
    color: #FFD700;
    text-transform: uppercase;
    font-size: 12px;
  }
  table tr:nth-child(even) {
    background: #f9f9f9;
  }
  .acciones-footer {
    margin-top: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .acciones-footer button,
  .acciones-footer a {
    background: #000000;
    color: #FFD700;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    cursor: pointer;
  }
  .acciones-footer button:hover,
  .acciones-footer a:hover {
    background: #FFD700;
    color: #000000;
  }
  @media print {
    .acciones-footer { display: none; }
    body { background: #ffffff; padding: 0; }
    .reporte-container { box-shadow:none; border-radius:0; }
  }
</style>
</head>
<body>

<div class="reporte-container">
  <div class="reporte-header">
    <div class="reporte-header-left">
      <div><strong>MG GRUPO SECURITY - SafeControl</strong></div>
      <div>Fecha: <?php echo $fecha_hoy; ?></div>
      <div>Hora: <?php echo $hora_hoy; ?></div>
    </div>
    <div>
      <img src="../imagenes/logo.jpeg" alt="Logo MG" style="height:60px; border-radius:8px;">
    </div>
  </div>

  <div class="reporte-title">
    <h1>Reporte General de Usuarios</h1>
    <div class="reporte-subtitle">
      Listado de usuarios registrados en el sistema SafeControl
    </div>
  </div>

  <?php if (empty($usuarios)): ?>
    <p style="text-align:center; margin:20px 0;">No hay usuarios registrados.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Usuario</th>
          <th>Correo</th>
          <th>Rol</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?php echo htmlspecialchars($u['id']); ?></td>
            <td><?php echo htmlspecialchars($u['nombre']); ?></td>
            <td><?php echo htmlspecialchars($u['usuario']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo ucfirst(htmlspecialchars($u['rol'])); ?></td>
            <td><?php echo htmlspecialchars($u['estado']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="acciones-footer">
    <a href="usuarios.php">⬅️ Volver a la gestión</a>
    <button onclick="window.print()">🖨️ Imprimir</button>
  </div>
</div>

</body>
</html>
