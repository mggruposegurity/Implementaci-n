<?php
include("../conexion.php");
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

// Obtener el ID del usuario que está logueado
$usuario_actual = $_SESSION['usuario'];
$userQuery = $conexion->query("SELECT id FROM tbl_ms_usuarios WHERE usuario='$usuario_actual' LIMIT 1");
$userData = $userQuery->fetch_assoc();
$id_usuario = $userData ? $userData['id'] : NULL;

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = strtoupper(trim($_POST['titulo']));
    $descripcion = trim($_POST['descripcion']);
    $instructor = trim($_POST['instructor']);
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $fecha_fin = trim($_POST['fecha_fin']);
    $tipo = trim($_POST['tipo']);
    $participantes = (int)$_POST['participantes'];
    $estado = $_POST['estado'];

    // Escapar las cadenas para evitar errores de SQL
    $titulo = $conexion->real_escape_string($titulo);
    $descripcion = $conexion->real_escape_string($descripcion);
    $instructor = $conexion->real_escape_string($instructor);
    $tipo = $conexion->real_escape_string($tipo);
    $estado = $conexion->real_escape_string($estado);

    $sql = "INSERT INTO capacitaciones (titulo, descripcion, instructor, fecha_inicio, fecha_fin, tipo, participantes, estado)
            VALUES ('$titulo', '$descripcion', '$instructor', '$fecha_inicio', '$fecha_fin', '$tipo', $participantes, '$estado')";

    if ($conexion->query($sql)) {
        // Registrar en bitácora
        $accion_b = $conexion->real_escape_string("Creación de Capacitación");
        $descripcion_b = $conexion->real_escape_string("Se registró una nueva capacitación: '$titulo'.");
        if (!empty($id_usuario)) {
        $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                          VALUES ('$id_usuario', '$accion_b', '$descripcion_b', NOW())");
    } else {
        error_log("No se pudo registrar en bitácora: id_usuario vacío");
    }

        // Mostrar mensaje de éxito en la misma página
        $success = "Capacitación registrada correctamente.";
    } else {
        $error = "Error al registrar capacitación: " . $conexion->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nueva Capacitación</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #fff;
    padding: 20px;
  }

  h2 {
    color: #6f42c1;
    margin-bottom: 20px;
  }

  form {
    max-width: 600px;
    margin: 0 auto;
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }

  .form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
  }

  .form-group.full {
    flex: 1;
  }

  .form-group.half {
    flex: 1;
  }

  .form-group {
    margin-bottom: 20px;
  }

  label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
  }

  input, select, textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
  }

  input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: #6f42c1;
    box-shadow: 0 0 0 3px rgba(111,66,193,0.1);
  }

  textarea {
    resize: vertical;
    min-height: 80px;
  }

  button {
    background-color: #6f42c1;
    border: none;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  button:hover {
    background-color: #5a32a3;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(111,66,193,0.3);
  }

  .btn-cancelar {
    background-color: #6c757d;
    color: white;
  }

  .btn-cancelar:hover {
    background-color: #545b62;
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

  <h2>🎓 Nueva Capacitación</h2>

  <?php if (isset($success)): ?>
    <p style="color: green; text-align: center; margin-bottom: 15px;"><?php echo $success; ?></p>
    <script>
      // Redirigir al módulo de capacitación después de guardar
      window.location.href = 'capacitacion.php';
    </script>
  <?php endif; ?>

  <?php if (isset($error)): ?>
    <p class="error"><?php echo $error; ?></p>
  <?php endif; ?>

  <form method="POST" action="capacitacion_form.php">
    <div class="form-row">
      <div class="form-group full">
        <label for="titulo">Título *</label>
        <input type="text" name="titulo" placeholder="Título de la capacitación" required maxlength="120">
      </div>
    </div>
    <div class="form-group">
      <label for="descripcion">Descripción</label>
      <textarea name="descripcion" placeholder="Descripción detallada de la capacitación" maxlength="255" rows="3"></textarea>
    </div>
    <div class="form-row">
      <div class="form-group half">
        <label for="instructor">Instructor</label>
        <input type="text" name="instructor" placeholder="Nombre del instructor" maxlength="100">
      </div>
      <div class="form-group half">
        <label for="tipo">Tipo *</label>
        <select name="tipo" required>
          <option value="Interna">Interna</option>
          <option value="Externa">Externa</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group half">
        <label for="fecha_inicio">Fecha de Inicio *</label>
        <input type="date" name="fecha_inicio" required>
      </div>
      <div class="form-group half">
        <label for="fecha_fin">Fecha de Fin *</label>
        <input type="date" name="fecha_fin" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group half">
        <label for="participantes">Participantes</label>
        <input type="number" name="participantes" placeholder="0" min="0">
      </div>
      <div class="form-group half">
        <label for="estado">Estado *</label>
        <select name="estado" required>
          <option value="PROGRAMADA">Programada</option>
          <option value="EN CURSO">En Curso</option>
          <option value="FINALIZADA">Finalizada</option>
          <option value="INACTIVA">Inactiva</option>
        </select>
      </div>
    </div>

    <div class="actions">
      <button type="button" onclick="window.location.href='capacitacion.php'" class="btn-cancelar">Cancelar</button>
      <button type="submit">Guardar</button>
    </div>
  </form>

</body>
</html>
