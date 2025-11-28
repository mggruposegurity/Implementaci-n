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
$id_usuario = $userData ? $userData['id'] : 0;

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_empleado = (int)$_POST['id_empleado'];
    $tipo_incidente = strtoupper(trim($_POST['tipo_incidente']));
    $descripcion = trim($_POST['descripcion']);
    $fecha = trim($_POST['fecha']);
    $gravedad = trim($_POST['gravedad']);
    $acciones_tomadas = trim($_POST['acciones_tomadas']);
    $estado = $_POST['estado'];

    $sql = "INSERT INTO incidentes (id_empleado, tipo_incidente, descripcion, fecha, gravedad, acciones_tomadas, estado)
            VALUES ($id_empleado, '$tipo_incidente', '$descripcion', '$fecha', '$gravedad', '$acciones_tomadas', '$estado')";

    if ($conexion->query($sql)) {
        // Registrar en bitácora solo si el usuario existe
        if ($id_usuario > 0) {
            $accion_b = "Creación de Incidente";
            $descripcion_b = "Se registró un nuevo incidente para el empleado ID $id_empleado.";
            $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                              VALUES ($id_usuario, '$accion_b', '$descripcion_b', NOW())");
        }

        // Redirigir de vuelta a la gestión de incidentes con mensaje de éxito
        header("Location: incidentes.php?msg=Incidente registrado correctamente.");
        exit();
    } else {
        $error = "Error al registrar incidente: " . $conexion->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nuevo Incidente</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #fff;
    padding: 20px;
  }

  h2 {
    color: #dc3545;
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
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220,53,69,0.1);
  }

  textarea {
    resize: vertical;
    min-height: 80px;
  }

  button {
    background-color: #dc3545;
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
    background-color: #c82333;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220,53,69,0.3);
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

  <h2>🚨 Nuevo Incidente</h2>

  <?php if (isset($error)): ?>
    <p class="error"><?php echo $error; ?></p>
  <?php endif; ?>

  <form method="POST" action="incidentes_form.php">
    <div class="form-row">
      <div class="form-group half">
        <label for="id_empleado">ID Empleado *</label>
        <input type="number" name="id_empleado" placeholder="ID del empleado" required min="1">
      </div>
      <div class="form-group half">
        <label for="tipo_incidente">Tipo de Incidente *</label>
        <input type="text" name="tipo_incidente" placeholder="Tipo de incidente" required maxlength="100">
      </div>
    </div>
    <div class="form-group">
      <label for="descripcion">Descripción *</label>
      <textarea name="descripcion" placeholder="Descripción detallada del incidente" required maxlength="255" rows="3"></textarea>
    </div>
    <div class="form-row">
      <div class="form-group half">
        <label for="fecha">Fecha del Incidente *</label>
        <input type="date" name="fecha" required>
      </div>
      <div class="form-group half">
        <label for="gravedad">Gravedad *</label>
        <select name="gravedad" required>
          <option value="LEVE">Leve</option>
          <option value="MODERADO">Moderado</option>
          <option value="GRAVE">Grave</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label for="acciones_tomadas">Acciones Tomadas</label>
      <textarea name="acciones_tomadas" placeholder="Acciones tomadas para resolver el incidente" maxlength="255" rows="3"></textarea>
    </div>
    <div class="form-group">
      <label for="estado">Estado *</label>
      <select name="estado" required>
        <option value="PENDIENTE">Pendiente</option>
        <option value="EN PROCESO">En Proceso</option>
        <option value="RESUELTO">Resuelto</option>
        <option value="INACTIVO">Inactivo</option>
      </select>
    </div>

    <div class="actions">
      <button type="button" onclick="window.location.href='incidentes.php'" class="btn-cancelar">Cancelar</button>
      <button type="submit">Guardar</button>
    </div>
  </form>

</body>
</html>
