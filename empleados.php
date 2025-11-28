<?php
session_start();
include("conexion.php");
include("header.php");

// Si no hay sesión, volver al login
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

// ====== Insertar nuevo empleado ======
if (isset($_POST['agregar'])) {
    $nombre = $_POST['nombre'];
    $cargo = $_POST['cargo'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $fecha_ingreso = $_POST['fecha_ingreso'];

    $sql = "INSERT INTO empleados (nombre, cargo, telefono, direccion, fecha_ingreso)
            VALUES ('$nombre', '$cargo', '$telefono', '$direccion', '$fecha_ingreso')";
    $conexion->query($sql);
}

// ====== Eliminar empleado ======
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $conexion->query("DELETE FROM empleados WHERE id=$id");
    header("Location: empleados.php");
    exit();
}

// ====== Consultar todos los empleados ======
$resultado = $conexion->query("SELECT * FROM empleados");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Empleados</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #eef2f3;
      margin: 0;
      padding: 0;
    }
    .container {
      width: 90%;
      max-width: 900px;
      margin: 30px auto;
      background-color: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #333;
    }
    form {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
      margin-bottom: 20px;
    }
    input, button {
      padding: 8px;
      font-size: 14px;
    }
    button {
      background-color: #007bff;
      border: none;
      color: white;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background-color: #0056b3;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: center;
    }
    th {
      background-color: #007bff;
      color: white;
    }
    a {
      color: red;
      text-decoration: none;
      font-weight: bold;
    }
    .volver {
      display: inline-block;
      margin-top: 15px;
      text-decoration: none;
      background-color: #28a745;
      color: white;
      padding: 10px 20px;
      border-radius: 5px;
    }
    .volver:hover {
      background-color: #218838;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Gestión de Empleados</h2>

    <!-- Formulario para agregar empleado -->
    <form method="POST">
      <input type="text" name="nombre" placeholder="Nombre completo" required>
      <input type="text" name="cargo" placeholder="Cargo" required>
      <input type="text" name="telefono" placeholder="Teléfono">
      <input type="text" name="direccion" placeholder="Dirección">
      <input type="date" name="fecha_ingreso" required>
      <button type="submit" name="agregar">Agregar</button>
    </form>

    <!-- Tabla de empleados -->
    <table>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Cargo</th>
        <th>Teléfono</th>
        <th>Dirección</th>
        <th>Fecha Ingreso</th>
        <th>Acción</th>
      </tr>

      <?php while ($fila = $resultado->fetch_assoc()): ?>
      <tr>
        <td><?php echo $fila['id']; ?></td>
        <td><?php echo $fila['nombre']; ?></td>
        <td><?php echo $fila['cargo']; ?></td>
        <td><?php echo $fila['telefono']; ?></td>
        <td><?php echo $fila['direccion']; ?></td>
        <td><?php echo $fila['fecha_ingreso']; ?></td>
        <td><a href="empleados.php?eliminar=<?php echo $fila['id']; ?>" onclick="return confirm('¿Eliminar empleado?')">🗑️</a></td>
      </tr>
      <?php endwhile; ?>
    </table>

    <a href="menu.php" class="volver">⬅️ Volver al Menú</a>
  </div>
  <?php include("footer.php"); ?>
  
</body>
</html>
