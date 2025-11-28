<?php

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

// Obtener usuario actual
$usuario_sesion = $_SESSION['usuario'];
$urow = $conexion->query("SELECT id, usuario FROM tbl_ms_usuarios WHERE usuario='$usuario_sesion' LIMIT 1")->fetch_assoc();
$id_usuario = $urow['id'] ?? 0;
$nombre_usuario = $urow['usuario'] ?? 'Desconocido';

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $conexion->real_escape_string(trim($_POST['nombre_turno']));
    $inicio = $conexion->real_escape_string($_POST['hora_inicio']);
    $fin = $conexion->real_escape_string($_POST['hora_fin']);
    $ubicacion = $conexion->real_escape_string(trim($_POST['ubicacion']));

    $sql = "INSERT INTO tbl_ms_turnos (nombre_turno, hora_inicio, hora_fin, ubicacion)
            VALUES ('$nombre', '$inicio', '$fin', '$ubicacion')";

    if ($conexion->query($sql)) {
        // Log en bitácora
        log_event($id_usuario, 'Creación', "Se creó el turno $nombre");

        // Redirigir de vuelta a la gestión de turnos con mensaje de éxito
        header("Location: turnos.php?msg=" . urlencode("Turno agregado correctamente."));
        exit();
    } else {
        $error = "Error al agregar turno: " . $conexion->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nuevo Turno</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #fff;
    padding: 20px;
  }

  h2 {
    color: #FFD700;
    margin-bottom: 20px;
  }

  form {
    max-width: 500px;
    margin: 0 auto;
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }

  input {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
  }

  button {
    background-color: #000000;
    border: none;
    color: #FFD700;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
  }

  button:hover {
    background-color: #FFD700;
    color: #000000;
  }

  .btn-cancelar {
    background-color: #6c757d;
    color: white;
  }

  .btn-cancelar:hover {
    background-color: #5a6268;
  }

  .actions {
    text-align: center;
  }

  .error {
    color: red;
    text-align: center;
    margin-bottom: 15px;
  }
</style>
</head>
<body>

  <h2>➕ Nuevo Turno</h2>

  <?php if (isset($error)): ?>
    <p class="error"><?php echo $error; ?></p>
  <?php endif; ?>

  <form method="POST" action="turnos_form.php">
    <input type="text" name="nombre_turno" placeholder="Nombre del turno" required maxlength="100">
    <input type="time" name="hora_inicio" required>
    <input type="time" name="hora_fin" required>
    <input type="text" name="ubicacion" placeholder="Ubicación asignada" maxlength="150">

    <div class="actions">
      <button type="button" onclick="window.location.href='turnos.php'">Cancelar</button>
      <button type="submit">Guardar</button>
    </div>
  </form>

</body>
</html>
