<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("conexion.php");

// Si no hay usuario pendiente, redirigir
if (!isset($_SESSION['pending_user'])) {
    header("Location: index.php");
    exit();
}

$mensaje = "";

if (isset($_POST['verificar'])) {
    $codigo = trim($_POST['codigo']);
    $id_usuario = $_SESSION['pending_user'];

    // Buscar coincidencia de código e ID
    $query = "SELECT * FROM tbl_ms_usuarios WHERE id='$id_usuario' AND codigo_2fa='$codigo' LIMIT 1";
    $resultado = $conexion->query($query);

    if ($resultado && $resultado->num_rows > 0) {
        // ✅ Código correcto → limpiar y activar sesión
        $conexion->query("UPDATE tbl_ms_usuarios SET codigo_2fa=NULL WHERE id='$id_usuario'");
        
        $_SESSION['usuario'] = $id_usuario; // Activamos sesión completa
        unset($_SESSION['pending_user']);

        header("Location: menu.php");
        exit();
    } else {
        $mensaje = "❌ Código incorrecto. Verifica el correo e inténtalo nuevamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Verificación de Código - Sistema de Control</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
    }
    .container {
      background-color: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 360px;
      text-align: center;
    }
    input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
      text-align: center;
      font-size: 18px;
      letter-spacing: 4px;
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
    }
    button:hover {
      background-color: #0056b3;
    } 
    .btn-regresar {
  display: inline-block;
  width: 100%;
  background-color: #8b1506ff; /* mismo color azul */
  color: white;
  text-align: center;
  border: none;
  border-radius: 6px;
  padding: 10px 0;
  width: 200px;
  font-size: 15px;
  cursor: pointer;
  text-decoration: none;
  transition: background 0.3s ease;
  margin-top: 10px;
}

.btn-regresar:hover {
  background-color: #0056b3; /* azul más oscuro al pasar el mouse */
}

    p {
      color: red;
    }
    footer {
      margin-top: 20px;
      color: #555;
      font-size: 13px;
    }
  </style>
</head>
<body>

  <div class="container">
    <h2>Verificación de Seguridad</h2>
    <p>Ingresa el código que fue enviado a tu correo electrónico.</p>

    <form method="POST">
      <input type="text" name="codigo" maxlength="6" placeholder="Código de verificación" required>
      <button type="submit" name="verificar">Verificar Código</button>
      <a href="index.php" class="btn-regresar" onclick="return confirmarRegreso()">⟵ Regresar al inicio</a>
    </form>

    <?php if (!empty($mensaje)) echo "<p>$mensaje</p>"; ?>

    <footer>
      Sistema de Control de Empleados © 2025
    </footer>
  </div>
<script>
function confirmarRegreso() {
  return confirm("¿Seguro que deseas regresar al inicio? Se perderá el proceso de verificación actual.");
}
</script>

</body>
</html>

