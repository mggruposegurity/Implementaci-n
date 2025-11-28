<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("conexion.php");

// Incluir PHPMailer
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// Verificar sesión activa
if (!isset($_SESSION['cambiar_clave'])) {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['cambiar_clave'];
$mensaje = "";

// Obtener datos del usuario
$consulta = $conexion->query("SELECT * FROM tbl_ms_usuarios WHERE id='$id_usuario'");
$datos_usuario = $consulta->fetch_assoc();
$email = $datos_usuario['email'];
$nombre = $datos_usuario['nombre'] ?? $datos_usuario['usuario'];

if (isset($_POST['actualizar'])) {
    $nueva = trim($_POST['nueva']);
    $confirmar = trim($_POST['confirmar']);

    // Validaciones
    if (strlen($nueva) < 8) {
        $mensaje = "⚠️ La contraseña debe tener al menos 8 caracteres.";
    } elseif ($nueva !== $confirmar) {
        $mensaje = "❌ Las contraseñas no coinciden.";
    } elseif (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W]).{8,}$/", $nueva)) {
        $mensaje = "⚠️ La contraseña debe incluir mayúsculas, minúsculas, números y caracteres especiales.";
    } else {
        // ✅ Actualizar contraseña
        $hash_nueva = password_hash($nueva, PASSWORD_DEFAULT);
        $conexion->query("UPDATE tbl_ms_usuarios SET contrasena='$hash_nueva', primer_login=0 WHERE id='$id_usuario'");
        unset($_SESSION['cambiar_clave']);

        // 🔎 Datos de auditoría
        date_default_timezone_set("America/Tegucigalpa");
        $fecha = date("d/m/Y");
        $hora = date("H:i:s");
        $ip = $_SERVER['REMOTE_ADDR'];

        // 📧 Enviar correo de confirmación
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'empleadossistema@gmail.com'; // Correo del sistema
            $mail->Password = 'sktxqxmgddbhxchu'; //  de aplicación
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('empleadossistema@gmail.com', 'Sistema de Control de Empleados');
            $mail->addAddress($email, $nombre); // Enviar al usuario
            $mail->addAddress('empleadossistema@gmail.com', 'Administrador'); // Copia al administrador

            $mail->isHTML(true);
            $mail->Subject = 'Confirmación de cambio de contraseña';
            $mail->Body = "
                <h2>Hola, $nombre 👋</h2>
                <p>Se realizó un cambio de contraseña en el <b>Sistema de Control de Empleados</b>.</p>
                <p><b>Detalles de seguridad:</b></p>
                <ul>
                    <li><b>Usuario:</b> $nombre</li>
                    <li><b>Correo:</b> $email</li>
                    <li><b>Fecha:</b> $fecha</li>
                    <li><b>Hora:</b> $hora</li>
                    <li><b>Dirección IP:</b> $ip</li>
                </ul>
                <p>Si tú no realizaste este cambio, contacta de inmediato al administrador del sistema.</p>
                <hr>
                <small>© 2025 Sistema de Control de Empleados</small>
            ";

            $mail->send();
        } catch (Exception $e) {
            $mensaje = "✅ Contraseña actualizada, pero no se pudo enviar el correo de confirmación.";
        }



        // Iniciar sesión directamente sin 2FA forzada
        $_SESSION['usuario'] = $id_usuario;
        header("Location: menu.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cambiar Contraseña</title>
<style>
body {
  font-family: Arial, sans-serif;
  background-color: #f4f4f4;
  margin: 0;
  padding: 0;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.container {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

form {
  background: #fff;
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
  width: 350px;
  text-align: center;
}

h2 {
  color: #000000;
  margin-bottom: 15px;
}

input {
  width: 100%;
  padding: 10px;
  margin: 8px 0;
  border: 1px solid #ccc;
  border-radius: 5px;
}

button {
  background: #000000;
  color: #FFD700;
  border: none;
  padding: 10px;
  width: 100%;
  border-radius: 5px;
  cursor: pointer;
  margin-top: 10px;
}

button:hover {
  background: #FFD700;
  color: #000000;
}

p {
  color: red;
  margin-top: 10px;
}

footer {
  text-align: center;
  padding: 10px;
  background-color: #ffffff;
  color: #666;
  font-size: 14px;
  border-top: 1px solid #ddd;
}
</style>
</head>
<body>

  <div class="container">
    <form method="POST">
    <h2>🔐 Cambia tu Contraseña</h2>
    <p>Por seguridad, debes cambiar la contraseña temporal por una nueva.</p>
    <div style="background:#e7f3ff; padding:10px; border-radius:5px; margin:10px 0; border-left:4px solid #007bff;">
      <strong>📱 Nota importante:</strong> Después de cambiar tu contraseña, la verificación en dos pasos (2FA) estará desactivada por defecto. Podrás activarla desde tu perfil si deseas mayor seguridad.
    </div>
    <input type="password" name="nueva" placeholder="Nueva Contraseña" required>
    <input type="password" name="confirmar" placeholder="Confirmar Contraseña" required>
    <button type="submit" name="actualizar">Actualizar Contraseña</button>
    <?php if (!empty($mensaje)) echo "<p>$mensaje</p>"; ?>
  </form>
  </div>

  <footer>
    Sistema de Control de Empleados © 2025
  </footer>

</body>
</html>
