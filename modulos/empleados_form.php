<?php
session_start();
include("../conexion.php");

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red; text-align:center;'>⚠️ Acceso no autorizado.</p>";
    exit();
}

// Obtener el ID del usuario que está logueado
$usuario_actual = $_SESSION['usuario'];
$userQuery = $conexion->query("SELECT id FROM tbl_ms_usuarios WHERE usuario='$usuario_actual' LIMIT 1");
$userData = $userQuery->fetch_assoc();
$id_usuario = $userData ? $userData['id'] : NULL;

$empleado = null;

// Verificar si se pasó un ID por URL
if (isset($_GET['id'])) {
  $id_empleado = intval($_GET['id']);
  $resultado = $conexion->query("SELECT e.* FROM tbl_ms_empleados e WHERE e.id_empleado = $id_empleado");
  if ($resultado && $resultado->num_rows > 0) {
    $empleado = $resultado->fetch_assoc();
  }
}

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_empleado = $_POST['id_empleado'] ?? null;
    $nombre = strtoupper(trim($_POST['nombre']));
    $dni = trim($_POST['dni']);
    $puesto = trim($_POST['puesto']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $estado = $_POST['estado'];
    $turno = isset($_POST['turno']) ? strtoupper(trim($_POST['turno'])) : '24 HORAS';
    if (!in_array($turno, ['24 HORAS','12 HORAS'])) {
        $turno = '24 HORAS';
    }

    if ($id_empleado) {
        $nombre_esc = $conexion->real_escape_string($nombre);
        $dni_esc = $conexion->real_escape_string($dni);
        $puesto_esc = $conexion->real_escape_string($puesto);
        $telefono_esc = $conexion->real_escape_string($telefono);
        $correo_esc = $conexion->real_escape_string($correo);
        $estado_esc = $conexion->real_escape_string($estado);
        $turno_esc = $conexion->real_escape_string($turno);

        $sql = "UPDATE tbl_ms_empleados SET
            nombre='$nombre_esc', dni='$dni_esc', puesto='$puesto_esc',
            telefono='$telefono_esc', correo='$correo_esc', estado='$estado_esc', turno='$turno_esc'
            WHERE id_empleado=$id_empleado";

        if ($conexion->query($sql)) {
      // No se usa tabla intermedia de turnos; se guarda en campo ENUM 'turno'.

            // Registrar en bitácora solo si el usuario logueado existe
            if ($id_usuario !== NULL) {
                $accion_b = "Actualización de Empleado";
                $descripcion = $conexion->real_escape_string("Se modificó la información del empleado '$nombre' (ID: $id_empleado).");
                $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                  VALUES ($id_usuario, '$accion_b', '$descripcion', NOW())");
            }

            // Redirigir de vuelta a la gestión de empleados con mensaje de éxito
            header("Location: empleados.php?msg=Empleado actualizado correctamente.");
            exit();
        } else {
            $error = "Error al actualizar empleado: " . $conexion->error;
        }
    } else {
        // Agregar nuevo empleado
        $nombre_esc = $conexion->real_escape_string($nombre);
        $dni_esc = $conexion->real_escape_string($dni);
        $puesto_esc = $conexion->real_escape_string($puesto);
        $telefono_esc = $conexion->real_escape_string($telefono);
        $correo_esc = $conexion->real_escape_string($correo);
        $estado_esc = $conexion->real_escape_string($estado);
        $turno_esc = $conexion->real_escape_string($turno);

        $dni = trim($_POST['dni']);

$check = $conexion->prepare("SELECT id_empleado FROM tbl_ms_empleados WHERE dni = ? LIMIT 1");
$check->bind_param("s", $dni);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $error = "⚠️ Ya existe un empleado con ese número de identidad.";
} else {
    // aquí haces el INSERT tranquilo
}


        $sql = "INSERT INTO tbl_ms_empleados (nombre, dni, puesto, telefono, correo, estado, turno)
            VALUES ('$nombre_esc', '$dni_esc', '$puesto_esc', '$telefono_esc', '$correo_esc', '$estado_esc', '$turno_esc')";

        if ($conexion->query($sql)) {
            $id_empleado = $conexion->insert_id;

      // No se usa tabla intermedia de turnos

            // Registrar en bitácora solo si el usuario logueado existe
            if ($id_usuario !== NULL) {
                $accion_b = "Creación de Empleado";
                $descripcion = $conexion->real_escape_string("Se agregó al empleado '$nombre' con DNI $dni.");
                $conexion->query("INSERT INTO tbl_ms_bitacora (id_usuario, accion, descripcion, fecha)
                                  VALUES ($id_usuario, '$accion_b', '$descripcion', NOW())");
            }

            // Redirigir de vuelta a la gestión de empleados con mensaje de éxito
            header("Location: empleados.php?msg=Empleado agregado correctamente.");
            exit();
        } else {
            $error = "Error al agregar empleado: " . $conexion->error;
        }
    }
}

// Cargar turnos para el select
 // Select estático: solo 24 HORAS y 12 HORAS
 $turnos = ['24 HORAS','12 HORAS'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nuevo Empleado</title>
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

  input, select {
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

  <h2><?php echo $empleado ? '✏️ Editar Empleado' : '➕ Nuevo Empleado'; ?></h2>

  <?php if (isset($error)): ?>
    <p class="error"><?php echo $error; ?></p>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="id_empleado" value="<?php echo $empleado['id_empleado'] ?? ''; ?>">

    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?php echo $empleado['nombre'] ?? ''; ?>" required>

    <label>DNI:</label>
    <input type="text" name="dni" value="<?php echo $empleado['dni'] ?? ''; ?>">

    <label>Puesto:</label>
    <input type="text" name="puesto" value="<?php echo $empleado['puesto'] ?? ''; ?>">

    <label>Teléfono:</label>
    <input type="text" name="telefono" value="<?php echo $empleado['telefono'] ?? ''; ?>">

    <label>Correo:</label>
    <input type="email" name="correo" value="<?php echo $empleado['correo'] ?? ''; ?>">

    <label>Turno:</label>
    <select name="turno">
      <?php foreach ($turnos as $t): ?>
        <option value="<?php echo $t; ?>" <?php echo (isset($empleado) && isset($empleado['turno']) && strtoupper($empleado['turno'])===strtoupper($t)) ? 'selected' : ''; ?>><?php echo $t; ?></option>
      <?php endforeach; ?>
    </select>

    <label>Estado:</label>
    <select name="estado" required>
      <option value="Activo" <?php echo (isset($empleado) && $empleado['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
      <option value="Inactivo" <?php echo (isset($empleado) && $empleado['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
    </select>

    <div class="actions">
      <button type="button" onclick="window.location.href='empleados.php'" class="btn-cancelar">Cancelar</button>
      <button type="submit">💾 Guardar Cambios</button>
    </div>
  </form>

</body>
</html>
