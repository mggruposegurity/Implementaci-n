<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("conexion.php");

// Verificar si hay un usuario temporal (primer login)
if (!isset($_SESSION['usuario_temp'])) {
    header("Location: index.php");
    exit();
}

$usuario_temp = $_SESSION['usuario_temp'];
$mensaje = "";

// Cuando el usuario envía el formulario
if (isset($_POST['cambiar'])) {
    $nueva = trim($_POST['nueva']);
    $confirmar = trim($_POST['confirmar']);

    // Validar campos vacíos
    if (empty($nueva) || empty($confirmar)) {
        $mensaje = "⚠️ Todos los campos son obligatorios.";
    } elseif ($nueva !== $confirmar) {
        $mensaje = "❌ Las contraseñas no coinciden.";
    } elseif (strlen($nueva) < 8) {
        $mensaje = "⚠️ La contraseña debe tener al menos 8 caracteres.";
    } elseif (!preg_match("/[A-Z]/", $nueva) || !preg_match("/[a-z]/", $nueva) || !preg_match("/[0-9]/", $nueva) || !preg_match("/[\W]/", $nueva)) {
        $mensaje = "⚠️ La contraseña debe contener mayúsculas, minúsculas, números y símbolos.";
    } else {
        // Actualizar contraseña y marcar que ya cambió
        $query = "UPDATE tbl_ms_usuarios SET contrasena='$nueva', primer_login=0 WHERE usuario='$usuario_temp'";
        if ($conexion->query($query)) {
            unset($_SESSION['usuario_temp']);
            $mensaje = "✅ Contraseña actualizada correctamente. Redirigiendo...";
            echo "<meta http-equiv='refresh' content='2;url=index.php'>";
        } else {
            $mensaje = "❌ Error al actualizar la contraseña.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cambiar Contraseña - Primer Inicio</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .container {
      background-color: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 350px;
      text-align: center;
    }

    h2 {
      color: #333;
      margin-bottom: 15px;
    }

    input {
      width: 100%;
      padding: 10px;
      margin: 8px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }

    button {
      width: 100%;
      padding: 10px;
      background-color: #007bff;
      border: none;
      color: #fff;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      margin-top: 10px;
    }

    button:hover {
      background-color: #0056b3;
    }

    p {
      color: red;
      font-size: 14px;
      margin-top: 10px;
    }

    .success {
      color: green;
    }

    footer {
      margin-top: 20px;
      font-size: 13px;
      color: #555;
    }

    .password-hint {
      font-size: 12px;
      color: #666;
      text-align: left;
      margin-top: 5px;
    }
  </style>
</head>
<body>

  <div class="container">
    <h2>Cambiar Contraseña</h2>
    <p>Hola <b><?= htmlspecialchars($usuario_temp) ?></b>, por seguridad debes crear una nueva contraseña.</p>

    <form method="POST">
      <input type="password" name="nueva" placeholder="Nueva contraseña" required>
      <input type="password" name="confirmar" placeholder="Confirmar contraseña" required>

      <div class="password-hint">
        🔒 Debe contener al menos:
        <ul style="text-align:left; margin:5px 0 10px 15px; font-size:12px;">
          <li>8 caracteres</li>
          <li>Una letra mayúscula</li>
          <li>Una letra minúscula</li>
          <li>Un número</li>
          <li>Un símbolo</li>
        </ul>
      </div>

      <button type="submit" name="cambiar">Guardar Nueva Contraseña</button>

      <?php if (!empty($mensaje)) echo "<p class='".(str_contains($mensaje,'✅')?'success':'error')."'>$mensaje</p>"; ?>
    </form>

    <footer>
      Sistema de Control de Empleados © 2025
    </footer>
  </div>

</body>
</html>

